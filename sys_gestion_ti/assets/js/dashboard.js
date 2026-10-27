(function () {
  if (!window.Chart || !window.TD_DATA) return;
  var D = window.TD_DATA, charts = [];
  var css = function (v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); };

  function build() {
    charts.forEach(function (c) { c.destroy(); }); charts = [];
    var txt = css('--muted'), grid = css('--line'), card = css('--card');
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = txt;

    // ---- Línea/área: creados vs resueltos
    var ctx = document.getElementById('chSerie').getContext('2d');
    var g1 = ctx.createLinearGradient(0, 0, 0, 300); g1.addColorStop(0, 'rgba(79,70,229,.30)'); g1.addColorStop(1, 'rgba(79,70,229,0)');
    var g2 = ctx.createLinearGradient(0, 0, 0, 300); g2.addColorStop(0, 'rgba(16,185,129,.25)'); g2.addColorStop(1, 'rgba(16,185,129,0)');
    charts.push(new Chart(ctx, {
      type: 'line',
      data: { labels: D.serie.labels, datasets: [
        { label: 'Creados', data: D.serie.creados, borderColor: '#4f46e5', backgroundColor: g1, fill: true, tension: .4, borderWidth: 3, pointRadius: 4, pointBackgroundColor: card, pointBorderColor: '#4f46e5', pointBorderWidth: 2, pointHoverRadius: 6 },
        { label: 'Resueltos', data: D.serie.resueltos, borderColor: '#10b981', backgroundColor: g2, fill: true, tension: .4, borderWidth: 3, pointRadius: 4, pointBackgroundColor: card, pointBorderColor: '#10b981', pointBorderWidth: 2, pointHoverRadius: 6 }
      ]},
      options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false }, tooltip: tip() },
        scales: { x: { grid: { display: false }, border: { display: false } }, y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { padding: 8 } } } }
    }));

    // ---- Doughnut: estados
    var colE = ['#4f46e5', '#0ea5e9', '#f59e0b', '#10b981', '#94a3b8'];
    charts.push(new Chart(document.getElementById('chEstado'), {
      type: 'doughnut',
      data: { labels: D.estados.labels, datasets: [{ data: D.estados.data, backgroundColor: colE, borderColor: card, borderWidth: 4, hoverOffset: 6 }] },
      options: { cutout: '74%', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tip() } }
    }));
    var lg = document.getElementById('lgEstado');
    lg.innerHTML = D.estados.labels.map(function (l, i) {
      return '<div><i style="background:' + colE[i] + '"></i>' + l + '<b>' + D.estados.data[i] + '</b></div>';
    }).join('');

    // ---- Barras horizontales: categorías
    charts.push(new Chart(document.getElementById('chCategoria'), {
      type: 'bar',
      data: { labels: D.categorias.labels, datasets: [{ data: D.categorias.data, backgroundColor: D.categorias.colors, borderRadius: 8, borderSkipped: false, barThickness: 16 }] },
      options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tip() },
        scales: { x: { grid: { color: grid }, border: { display: false }, beginAtZero: true }, y: { grid: { display: false }, border: { display: false } } } }
    }));

    // ---- Gauge SLA (semi-dona)
    charts.push(new Chart(document.getElementById('chSla'), {
      type: 'doughnut',
      data: { datasets: [{ data: [D.sla, 100 - D.sla], backgroundColor: [D.sla >= 95 ? '#10b981' : (D.sla >= 85 ? '#f59e0b' : '#ef4444'), grid], borderWidth: 0, borderRadius: 10 }] },
      options: { rotation: -90, circumference: 180, cutout: '78%', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
    }));

    // ---- Canales
    charts.push(new Chart(document.getElementById('chCanales'), {
      type: 'bar',
      data: { labels: D.canales.labels, datasets: [{ data: D.canales.data, backgroundColor: ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ec4899'], borderRadius: 6, borderSkipped: false }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tip() },
        scales: { x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 } } }, y: { display: false } } }
    }));
  }

  function tip() {
    return { backgroundColor: '#0f172a', titleColor: '#fff', bodyColor: '#e2e8f0', padding: 12, cornerRadius: 10, boxPadding: 6, usePointStyle: true };
  }
  window.TD_redraw = build;
  build();
})();
