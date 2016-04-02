#!/usr/bin/env python
# -*- coding: utf-8 -*-
import logging
logging.basicConfig()

import requests
import urllib
from fnmatch import fnmatch
from decimal import Decimal
from threading import Thread
from SimpleXMLRPCServer import SimpleXMLRPCServer
from jabberbot import JabberBot, botcmd
from ConfigParser import ConfigParser

config = ConfigParser()
config.read(['lunchbot.ini'])

XMPP_USERNAME = config.get('lunchbot', 'xmpp_username')
XMPP_PASSWORD = config.get('lunchbot', 'xmpp_password')
XMPP_SERVER = config.get('lunchbot', 'xmpp_server')

SHOP_USERNAME = config.get('lunchbot', 'shop_username')
SHOP_PASSWORD = config.get('lunchbot', 'shop_password')
SHOP_URL = config.get('lunchbot', 'shop_url')

class LunchShopMethods(object):
    def notify(self, target, message):
        global bot
        bot.send(target, message)

def rpc_thread_proc():
    server = SimpleXMLRPCServer(("localhost", 9777), allow_none=True)
    server.register_instance(LunchShopMethods())
    server.serve_forever()

thread = Thread(target=rpc_thread_proc)
thread.daemon = True
thread.start()

class UnknownUserError(Exception):
    pass

def get_user_info(jid):
    params = { 'jid': jid }
    res = requests.get(SHOP_URL + '/app/user-info', auth=(SHOP_USERNAME, SHOP_PASSWORD), params=params)
    data = res.json()
    if type(data['current_stores']) is list:
        data['current_stores'] = {}
    return data

def set_user_reminders(email, flag):
    params = { 'email': email, 'flag': "1" if flag else "0" }
    requests.post(SHOP_URL + '/app/user-reminders', auth=(SHOP_USERNAME, SHOP_PASSWORD), params=params)

def add_order(email, store, title, price):
    params = { 'email': email, 'store': store, 'title': title, 'price': price }
    res = requests.post(SHOP_URL + '/app/order-add', auth=(SHOP_USERNAME, SHOP_PASSWORD), params=params)
    if res.status_code != 200:
        raise RuntimeError("Die Bestellung konnte nicht ausgefuehrt werden.")

def change_priority(email, store, direction):
    params = { 'email': email, 'store': store, 'direction': direction }
    res = requests.post(SHOP_URL + '/app/merchant-priority', auth=(SHOP_USERNAME, SHOP_PASSWORD), params=params)
    if res.status_code != 200:
        raise RuntimeError("Die Prioritaet konnte nicht geaendert werden.")

def format_price(price):
    res = Decimal(price).quantize(Decimal('1.00'))
    return str(res).replace('.', ',')

class LunchShopJabberBot(JabberBot):
    MSG_ERROR_OCCURRED = "Es tut mir Leid, aber es ist ein Fehler aufgetreten."

    @botcmd
    def balance(self, msg, args):
        '''Gibt deinen aktuellen Kontostand aus.'''

        info = get_user_info(msg.getFrom().getStripped())
        balance = info['ext_info']['balance']
        return u"Dein aktueller Kontostand beträgt %s Euro" % (format_price(balance))

    @botcmd
    def deposit(self, msg, args):
        '''Liefert Informationen, wie Geld eingezahlt werden kann.'''

        info = get_user_info(msg.getFrom().getStripped())
        uinfo = u"""Das Guthabenkonto kann per SEPA-Überweisung aufgeladen werden:

Kontoinhaber: %s
IBAN: %s
Kreditinstitut: %s
Verwendungszweck: %s

Alternativ kann auch Bargeld (nur Scheine) bei der Kasse eingezahlt werden.""" % (info['ext_info']['tgt_owner'], info['ext_info']['tgt_iban'], info['ext_info']['tgt_org'], info['ext_info']['tgt_reference'])

        return uinfo

    @botcmd
    def order(self, msg, args):
        '''Schickt eine Bestellung ab.'''

        info = get_user_info(msg.getFrom().getStripped())

        if not info['order_status']:
            return 'Aktuell werden leider keine neuen Bestellungen entgegengenommen.'

        history = {}
        history_info = ""
        id = 1

        for store_id, store_info in info['current_stores'].iteritems():
            if history_info != "":
                history_info += "\n"

            history_info += "Deine letzten Bestellungen bei " + store_info['name'] + ":\n"

            if len(store_info['recent_orders']) > 0:
                for order in store_info['recent_orders']:
                    history[id] = order
                    history[id]['store'] = store_id
                    history_info += "#" + str(id) + ": " + order['title'] + " (" + order['date'] + ")\n"
                    id += 1
            else:
                history_info += "Bisher noch keine Bestellungen. :)\n"

        if len(args) == 0:
            params = { 'email': info['email'], 'token': info['login_token'] }
            url_params = urllib.urlencode(params)
            login_url = SHOP_URL + "/app/login?%s" % (url_params)
            return """Bitte gib' die ID einer Bestellung aus der folgenden Liste an, z.B. 'order 3':

%s
Alternativ kannst du deine Bestellungen auch unter %s aktualisieren.""" % (history_info, login_url)

        try:
            item = history[int(args)]
        except KeyError:
            return "Die von dir angegebene ID ist nicht gültig."

        try:
            add_order(info['email'], item['store'], item['title'], item['price'])
        except RuntimeError:
            return "Es ist ein Fehler aufgetreten. Bitte überprüfe deinen Kontostand."

        return u"Deine Bestellung für '%s' wurde aufgenommen. Du kannst den Status mit 'status' nachverfolgen." % (item['title'])

    @botcmd
    def status(self, msg, args):
        '''Zeigt den Status deiner Bestellung an.'''

        info = get_user_info(msg.getFrom().getStripped())

        uinfo = ""

        first_shop = None

        store_items = info['current_stores'].items()
        store_items.sort(key=lambda item: item[1]['priority'])

        shops_with_orders = 0
        for store_id, store_info in store_items:
            if store_info['has_order']:
                shops_with_orders += 1

        for store_id, store_info in store_items:
            if not store_info['has_order']:
                continue

            if not first_shop:
                first_shop = store_info['name']

            if info['order_status'] and shops_with_orders > 1:
                prio_info = " - %s. Wahl" % (store_info['priority'])
            else:
                prio_info = ""

            uinfo += "Deine Bestellung bei %s%s:\n\n" % (store_info['name'], prio_info)

            if len(store_info['current_orders']) > 0:
                for order in store_info['current_orders']:
                    uinfo += "* %s (%s Euro)\n" % (order['title'], format_price(order['price']))

                uinfo += "\n"
            else:
                uinfo += "Bisher noch keine Bestellungen. :)\n\n"

            if not info['order_status']:
                uinfo += "Status: %s\n\n" % (store_info['status'])

        if info['order_status']:
            if not first_shop:
                uinfo += "Du hast bisher für heute noch nichts bestellt. "

            uinfo += "Du kannst deine Bestellung mit dem 'order'-Befehl bearbeiten."

            if shops_with_orders > 1:
                uinfo += u" Die Reihenfolge deiner Bestellungen kann mit 'up' bzw. 'down' geändert werden (z.B. 'down %s')." % (first_shop)
        else:
            uinfo += "Aktuell werden leider keine neuen Bestellungen entgegengenommen."

        return uinfo

    def _change_prio(self, msg, args, direction):
        info = get_user_info(msg.getFrom().getStripped())

        if not info['order_status']:
            return u"Die Bestellung kann aktuell leider nicht mehr geändert werden."

        if args == "":
            return "Bitte gib' den Namen eines Laden als Argument an."

        target_id = None

        for store_id, store_info in info['current_stores'].iteritems():
            if fnmatch(store_info['name'].lower(), "*%s*" % (args.lower())):
                if target_id != None:
                    return "Der angegebene Name kann nicht eindeutig zu einem Laden zugeordnet werden. Bitte gib' eine genauere Beschreibung an."

                target_id = store_id

        if target_id == None:
            return "Es konnte leider kein Laden gefunden werden, der zu dem angegebenen Namen passt."

        change_priority(info['email'], target_id, direction)

        info = get_user_info(msg.getFrom().getStripped())

        store = info['current_stores'][target_id]
        return "%s ist nun deine %s. Wahl." % (store['name'], store['priority'])

    @botcmd
    def up(self, msg, args):
        '''Verschiebt eine Bestellung nach oben.'''

        return self._change_prio(msg, args, 'up')

    @botcmd
    def down(self, msg, args):
        '''Verschiebt eine Bestellung nach unten.'''

        return self._change_prio(msg, args, 'down')

    @botcmd
    def reminders(self, msg, args):
        '''Zeigt an bzw. ändert, ob Bestellerinnerungen aktiviert sind.'''

        info = get_user_info(msg.getFrom().getStripped())

        if len(args) == 0:
            if info['reminders']:
                info_text = "aktiviert. Um 11:00 Uhr wird dir per Jabber eine Erinnerung geschickt, wenn du noch nichts bestellt hast. Bestellerinnerungen kannst du mit 'reminders off' deaktivieren."
            else:
                info_text = "deaktiviert. Wenn du Bestellerinnerungen aktivierst (mit 'reminders on'), wird dir um 11:00 Uhr per Jabber eine Erinnerung geschickt, wenn du noch nichts bestellt hast."
            return "Bestellerinnerungen sind %s." % (info_text)

        if args == 'on':
            set_user_reminders(info['email'], True)
            return "Bestellerinnerungen wurden aktiviert."
        elif args == 'off':
            set_user_reminders(info['email'], False)
            return "Bestellerinnerungen wurden deaktiviert."
        else:
            return "Bitte entweder 'on' oder 'off' als Argument angeben, um Bestellerinnerungen zu aktivieren bzw. deaktivieren."

bot = LunchShopJabberBot(XMPP_USERNAME, XMPP_PASSWORD, res='bot', server=XMPP_SERVER, debug=True)
bot.serve_forever()
