<?php

/*
 * Shop
 * Copyright (C) 2015 Gunnar Beutner
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 */

?>

<h1>Statistik<?php if ($params['data']) { echo ': ' . htmlentities($params['reports'][$params['id']]); } else { echo 'en'; } ?></h1>

<?php if (!$params['data']) { ?>
<form method="get" action="/app/reports" class="aui">
  <div class="field-group">
    <label for="report">Report</label>
    <select class="select" style="max-width: 350px;" id="report" name="report">
<?php

foreach ($params['reports'] as $id => $name) {
    $id_escaped = htmlentities($id);
    $name_escaped = htmlentities($name);

    echo <<<HTML
      <option value="${id_escaped}">${name_escaped}</option>
HTML;
}

?>
    </select>
  </div>
  <div class="buttons-container">
    <div class="buttons">
      <button type="submit" class="aui-button">
        <i class="fa fa-check"></i> Report anzeigen
      </button>
    </div>
  </div>
</form>

<?php } else { ?>
<script src="/vendor/mbostock/d3/d3.min.js" charset="utf-8"></script>

<style>

.bar {
  fill: steelblue;
}

.bar:hover {
  fill: brown;
}

.axis {
  font: 10px sans-serif;
}

.axis path,
.axis line {
  fill: none;
  stroke: #000;
  shape-rendering: crispEdges;
}

.x.axis path {
  display: none;
}
</style>

<svg id="chart"></svg>

<script>
  var data = <?php echo json_encode($params['data']); ?>;

  var margin = {top: 20, right: 20, bottom: 30, left: 40},
    width = 900,
    height = 500;

  var x = d3.scale.ordinal()
    .rangeRoundBands([0, width], .1);

  x.domain(data.map(function(d) { return d.x; }));

  var y = d3.scale.linear()
    .range([height, 0]);

  y.domain([0, 1.1 * d3.max(data, function (d) { return d.y; })]);

  var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

  var chart = d3.select("#chart")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom);

  chart.append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  chart.append("g")
    .attr("class", "x axis")
    .attr("transform", "translate(0," + height + ")")
    .call(xAxis)
  .selectAll(".tick text")
    .call(wrap, x.rangeBand());

  chart.selectAll(".bar")
    .data(data)
  .enter().append("rect")
    .attr("class", "bar")
    .attr("x", function(d) { return x(d.x); })
    .attr("width", x.rangeBand())
    .attr("y", function(d) { return y(d.y); })
    .attr("height", function(d) { return height - y(d.y); });

  chart.selectAll("text.bar")
    .data(data)
  .enter().append("text")
    .attr("class", "bar")
    .attr("text-anchor", "middle")
    .attr("x", function(d) { return x(d.x) + x.rangeBand() / 2; })
    .attr("y", function(d) { return y(d.y) - 10; })
    .text(function(d) { return d.y; });

function getTextWidth(text, fontSize, fontFace) {
  var a = document.createElement('canvas');
  var b = a.getContext('2d');
  b.font = fontSize + 'px ' + fontFace;
  return b.measureText(text).width;
} 

function wrap(text, width) {
  text.each(function() {
    var text = d3.select(this),
        words = text.text().split(/[-\s]+/).reverse(),
        word,
        line = [],
        lineNumber = 0,
        lineHeight = 1.1, // ems
        y = text.attr("y"),
        dy = parseFloat(text.attr("dy")),
        tspan = text.text(null).append("tspan").attr("x", 0).attr("y", y).attr("dy", dy + "em");
    while (word = words.pop()) {
      line.push(word);
      tspan.text(line.join(" "));
      if (tspan.node().getComputedTextLength() > width) {
        line.pop();
        tspan.text(line.join(" "));
        line = [word];
        tspan = text.append("tspan").attr("x", 0).attr("y", y).attr("dy", ++lineNumber * lineHeight + dy + "em").text(word);
      }
    }
  });
}
</script>

<?php } ?>
