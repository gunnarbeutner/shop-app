var addressSource = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace,
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  remote: {
    url: '/app/address-search?q=%QUERY',
    wildcard: '%QUERY'
  }
});

Handlebars.registerHelper('gravatar', function(context, options) {
  var email = context;
  return "https://www.gravatar.com/avatar/" + md5(email.toLowerCase()) + "?s=35&amp;d=mm&amp;r=g";
}); 

$('.address').typeahead(null, {
  name: 'user-address',
  display: 'email',
  source: addressSource,
  templates: {
    suggestion: Handlebars.compile('<div class="suggestion-container"><div><img src="{{#gravatar email}}{{/gravatar}}" class="suggestion-icon"></img></div><div class="suggestion-label"><strong>{{name}}</strong><div>{{email}}</div></div></div>')
  }
});

$('.article').each(function(key, value) {
  var articleSource = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace,
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    remote: {
      url: '/app/article-search?store=' + value.dataset.store + '&q=%QUERY',
      wildcard: '%QUERY'
    }
  });

  $(value).typeahead(null, {
    name: 'article',
    display: 'title',
    source: articleSource,
    templates: {
      suggestion: Handlebars.compile('<div class="suggestion-container"><div class="suggestion-label"><strong>{{title}}</strong><div>{{description}}</div></div></div>')
    }
  });

  $(value).parent().css('width', '250px');
});

$('.address').parent().css('width', '250px');

$(document).ready(function() {

$('.aui-nav li a').each(function() {
    var current = window.location.pathname;
    var url = $(this).attr('href');
    if (url == current) {
        $(this).addClass('menu-active');
    };
});

});
