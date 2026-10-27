(function () {
  var C = window.TD_REPORT, el = document.getElementById('chReporte');
  if (!C || !window.Chart || !el) return;
  var chart;
  function css(v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); }
  function build() {
    if (chart) chart.destroy();
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif"; Chart.defaults.color = css('--muted');
    var grid = css('--line'), card = css('--card'), t = C.type;
    var ds = C.datasets.map(function (d) {
      var o = { label: d.label, data: d.data };
      if (t === 'doughnut') { o.backgroundColor = d.color; o.borderColor = card; o.borderWidth = 4; }
      else if (t === 'line') { o.borderColor = d.color; o.backgroundColor = d.color + '22'; o.fill = true; o.tension = .35; o.borderWidth = 3; o.pointRadius = C.labels.length > 40 ? 0 : 3; o.pointHoverRadius = 5; }
      else { o.backgroundColor = d.color; o.borderRadius = 8; o.borderSkipped = false; o.maxBarThickness = 38; }
      return o;
    });
    var opt = { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
      plugins: { legend: { display: t === 'doughnut' || ds.length > 1, position: t === 'doughnut' ? 'right' : 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                 tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10, boxPadding: 6, usePointStyle: true } } };
    if (t === 'doughnut') opt.cutout = '66%';
    else {
      if (C.horizontal) opt.indexAxis = 'y';
      opt.scales = { x: { stacked: !!C.stacked, grid: { display: !!C.horizontal, color: grid }, border: { display: false } },
                     y: { stacked: !!C.stacked, beginAtZero: true, max: C.max || undefined, grid: { color: C.horizontal ? 'transparent' : grid }, border: { display: false } } };
    }
    chart = new Chart(el, { type: t === 'doughnut' ? 'doughnut' : (t === 'line' ? 'line' : 'bar'), data: { labels: C.labels, datasets: ds }, options: opt });
  }
  window.TD_redraw = build; build();
})();
