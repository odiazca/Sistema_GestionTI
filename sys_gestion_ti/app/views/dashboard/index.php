<?php
$u = Auth::user(); $hora = (int)date("G");
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$fecha = (function () {
    $d = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $m = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return ucfirst($d[(int)date('w')]) . ', ' . date('j') . ' de ' . $m[(int)date('n')] . ' de ' . date('Y');
})();
$prioClase = ['critica'=>'danger','alta'=>'warning','media'=>'info','baja'=>'muted'];
$estLabel  = ['abierto'=>'Abierto','en_proceso'=>'En proceso','pendiente'=>'Pendiente'];
$estClase  = ['abierto'=>'primary','en_proceso'=>'info','pendiente'=>'warning'];
$maxCarga  = max(1, max(array_column($tecnicos, 'activos') ?: [1]));
$totPrio   = max(1, array_sum(array_column($prioridad, 'total')));
?>

<!-- ======= HERO ======= -->
<section class="welcome">
  <div class="welcome-orb"></div>
  <div class="welcome-text">
    <span class="chip chip-glass"><i class="bi bi-calendar3 me-1"></i><?= e($fecha) ?></span>
    <h1><?= $saludo ?>, <?= e($u['nombres']) ?> 👋</h1>
    <p>Hoy tienes <b><?= $kpi['abiertos'] ?></b> tickets activos<?= $kpi['vencidos'] ? ', de los cuales <b>' . $kpi['vencidos'] . '</b> están fuera de SLA' : '' ?>. El cumplimiento de SLA del equipo es <b><?= $kpi['sla'] ?>%</b>.</p>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <a href="<?= url('tickets') ?>" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-plus-lg me-1"></i>Nuevo ticket</a>
      <a href="<?= url('visitas') ?>" class="btn btn-glass btn-sm"><i class="bi bi-calendar-check me-1"></i>Agendar visita</a>
      <a href="<?= url('reportes') ?>" class="btn btn-glass btn-sm"><i class="bi bi-bar-chart-line me-1"></i>Ver reportes</a>
    </div>
  </div>
  <div class="welcome-stats d-none d-lg-flex">
    <div><b><?= $kpi['hoy'] ?></b><span>Creados hoy</span></div>
    <div><b><?= $kpi['resueltos_hoy'] ?></b><span>Resueltos hoy</span></div>
    <div><b><?= $kpi['tmr_horas'] ?>h</b><span>Tiempo medio</span></div>
  </div>
</section>

<!-- ======= KPIs ======= -->
<?php
$puedeRep = in_array($u['rol'], ['Administrador', 'Supervisor'], true);
$tk = $kpi['tendencia'];
$cards = [
  ['c' => 'indigo', 'icon' => 'bi-ticket-perforated-fill', 'val' => $kpi['abiertos'], 'tit' => 'Tickets abiertos', 'sub' => $kpi['hoy'] . ' creados hoy', 'chip' => ($tk >= 0 ? '+' : '') . $tk . '%', 'link' => 'tickets'],
  ['c' => 'red', 'icon' => 'bi-alarm-fill', 'val' => $kpi['vencidos'], 'tit' => 'Fuera de SLA', 'sub' => $kpi['vencidos'] ? 'Requieren atención inmediata' : 'Todo dentro del plazo', 'link' => 'tickets?vencidos=1'],
  ['c' => 'green', 'icon' => 'bi-shield-check', 'val' => $kpi['sla'] . '%', 'tit' => 'Cumplimiento SLA', 'sub' => 'Meta del equipo: 95%', 'chip' => $kpi['sla'] >= 95 ? 'Meta ok' : 'Bajo meta', 'link' => $puedeRep ? 'reportes/ver/sla' : 'tickets'],
  ['c' => 'blue', 'icon' => 'bi-buildings-fill', 'val' => $kpi['clientes'], 'tit' => 'Clientes activos', 'sub' => $kpi['contratos'] . ' contratos vigentes', 'link' => 'clientes'],
  ['c' => 'amber', 'icon' => 'bi-gear-wide-connected', 'val' => $kpi['ordenes'], 'tit' => 'Órdenes de servicio', 'sub' => 'Pendientes y en curso', 'link' => 'ordenes'],
  ['c' => 'teal', 'icon' => 'bi-geo-alt-fill', 'val' => $kpi['visitas_semana'], 'tit' => 'Visitas esta semana', 'sub' => 'Programadas en 7 días', 'link' => 'visitas'],
  ['c' => 'cyan', 'icon' => 'bi-pc-display-horizontal', 'val' => $kpi['activos'], 'tit' => 'Equipos registrados', 'sub' => 'En inventario de activos', 'link' => 'activos'],
  ['c' => 'violet', 'icon' => 'bi-cash-stack', 'val' => money($kpi['ingresos'], 0), 'tit' => 'Ingreso mensual', 'sub' => 'Contratos de soporte', 'link' => 'contratos'],
];
?>
<div class="row g-3 mt-1">
  <?php foreach ($cards as $c): ?>
  <div class="col-12 col-sm-6 col-xl-3">
    <a href="<?= url($c['link']) ?>" class="kpi-g kpi-g-<?= $c['c'] ?>">
      <i class="bi <?= $c['icon'] ?> kpi-g-bg"></i>
      <div class="kpi-g-val"><?= e($c['val']) ?></div>
      <div class="kpi-g-tit"><?= e($c['tit']) ?></div>
      <div class="kpi-g-sub"><?= e($c['sub']) ?></div>
      <div class="kpi-g-foot"><span class="kpi-g-go">Ver <i class="bi bi-arrow-right"></i></span><?php if (!empty($c['chip'])): ?><span class="kpi-g-chip"><?= e($c['chip']) ?></span><?php endif; ?></div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- ======= GRÁFICOS FILA 1 ======= -->
<div class="row g-3 mt-1">
  <div class="col-xl-8">
    <div class="card-ui h-100">
      <div class="card-head">
        <div><h6>Evolución de tickets</h6><small>Creados vs resueltos · últimos 6 meses</small></div>
        <div class="legend-inline"><span><i style="background:#4f46e5"></i>Creados</span><span><i style="background:#10b981"></i>Resueltos</span></div>
      </div>
      <div class="chart-box" style="height:300px"><canvas id="chSerie"></canvas></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6>Tickets por estado</h6><small>Distribución actual</small></div></div>
      <div class="chart-box donut-box"><canvas id="chEstado"></canvas>
        <div class="donut-center"><b><?= array_sum($estados['data']) ?></b><span>Total</span></div>
      </div>
      <div class="donut-legend" id="lgEstado"></div>
    </div>
  </div>
</div>

<!-- ======= FILA 2 ======= -->
<div class="row g-3 mt-1">
  <div class="col-xl-4 col-md-6">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6>Tickets por categoría</h6><small>Histórico total</small></div></div>
      <div class="chart-box" style="height:280px"><canvas id="chCategoria"></canvas></div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6>Cumplimiento de SLA</h6><small>Tickets resueltos dentro del plazo</small></div></div>
      <div class="chart-box gauge-box"><canvas id="chSla"></canvas>
        <div class="gauge-center"><b><?= $kpi['sla'] ?>%</b><span>Cumplimiento</span></div>
      </div>
      <h6 class="mini-title">Pendientes por prioridad</h6>
      <?php foreach ($prioridad as $p): $pct = round($p['total'] * 100 / $totPrio); ?>
      <div class="prio-row">
        <span class="prio-name"><i class="dot dot-<?= $prioClase[$p['key']] ?>"></i><?= e($p['label']) ?></span>
        <div class="progress-ui"><div class="bar bar-<?= $prioClase[$p['key']] ?>" style="width:<?= $pct ?>%"></div></div>
        <b><?= $p['total'] ?></b>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6>Carga del equipo técnico</h6><small>Tickets activos por técnico</small></div></div>
      <?php foreach ($tecnicos as $t): ?>
      <div class="tech-row">
        <span class="avatar" style="background:<?= e($t['color']) ?>"><?= e(iniciales($t['nombre'])) ?></span>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between"><b><?= e($t['nombre']) ?></b><span class="text-muted small"><?= (int)$t['activos'] ?> activos</span></div>
          <div class="progress-ui mt-1"><div class="bar" style="width:<?= round($t['activos'] * 100 / $maxCarga) ?>%;background:<?= e($t['color']) ?>"></div></div>
          <small class="text-muted"><?= e($t['cargo']) ?> · <?= (int)$t['mes'] ?> resueltos (30 d)</small>
        </div>
      </div>
      <?php endforeach; ?>
      <h6 class="mini-title mt-3">Canales de ingreso (30 días)</h6>
      <div class="chart-box" style="height:120px"><canvas id="chCanales"></canvas></div>
    </div>
  </div>
</div>

<!-- ======= TABLA + ACTIVIDAD ======= -->
<div class="row g-3 mt-1">
  <div class="col-xl-8">
    <div class="card-ui h-100">
      <div class="card-head">
        <div><h6>Tickets que requieren atención</h6><small>Ordenados por prioridad</small></div>
        <a href="<?= url('tickets') ?>" class="btn btn-soft btn-sm">Ver todos <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
      <div class="table-responsive">
        <table class="table-ui">
          <thead><tr><th>Ticket</th><th>Cliente</th><th>Prioridad</th><th>Estado</th><th>Técnico</th><th>SLA</th></tr></thead>
          <tbody>
          <?php foreach ($recientes as $t):
              $venc = strtotime($t['fecha_limite']) < time(); ?>
            <tr>
              <td><b class="tk-code"><?= e($t['codigo']) ?></b><div class="tk-sub"><?= e(mb_strimwidth($t['asunto'], 0, 38, '…')) ?></div></td>
              <td><span class="small"><?= e($t['cliente']) ?></span><div class="tk-sub"><?= e($t['categoria']) ?></div></td>
              <td><span class="pill pill-<?= $prioClase[$t['prioridad']] ?>"><?= ucfirst($t['prioridad']) ?></span></td>
              <td><span class="pill pill-soft-<?= $estClase[$t['estado']] ?>"><?= $estLabel[$t['estado']] ?></span></td>
              <td><?php if ($t['tecnico']): ?><span class="avatar avatar-sm" style="background:<?= e($t['color']) ?>" title="<?= e($t['tecnico']) ?>"><?= e(iniciales($t['tecnico'])) ?></span><?php else: ?><span class="text-muted small">Sin asignar</span><?php endif; ?></td>
              <td><span class="sla <?= $venc ? 'sla-bad' : 'sla-ok' ?>"><i class="bi <?= $venc ? 'bi-alarm' : 'bi-clock' ?>"></i> <?= $venc ? 'Vencido' : date('d/m H:i', strtotime($t['fecha_limite'])) ?></span></td>
            </tr>
          <?php endforeach; if (!$recientes): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay tickets pendientes 🎉</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6>Actividad reciente</h6><small>Últimos movimientos</small></div></div>
      <div class="timeline">
        <?php foreach ($actividad as $a): ?>
        <div class="tl-item">
          <span class="tl-ic" style="background:<?= e($a['color']) ?>1f;color:<?= e($a['color']) ?>"><i class="bi <?= e($a['icono']) ?>"></i></span>
          <div>
            <b><?= e($a['accion']) ?></b>
            <small><?= e($a['detalle']) ?></small>
            <em><?= e($a['usuario'] ?? 'Sistema') ?> · <?= hace($a['created_at']) ?></em>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ======= ALERTAS ======= -->
<div class="row g-3 mt-1 mb-2">
  <div class="col-lg-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6><i class="bi bi-file-earmark-text text-warning me-1"></i>Contratos por vencer</h6></div></div>
      <?php foreach ($alertas['contratos'] as $c): ?>
      <div class="alert-row"><div><b><?= e($c['razon_social']) ?></b><small><?= e($c['codigo']) ?> · <?= date('d/m/Y', strtotime($c['fecha_fin'])) ?></small></div>
        <span class="pill pill-<?= $c['dias'] <= 30 ? 'danger' : 'warning' ?>"><?= (int)$c['dias'] ?> días</span></div>
      <?php endforeach; if (!$alertas['contratos']) echo '<p class="text-muted small">Sin vencimientos próximos.</p>'; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6><i class="bi bi-box-seam text-danger me-1"></i>Repuestos con stock bajo</h6></div></div>
      <?php foreach ($alertas['stock'] as $s): ?>
      <div class="alert-row"><div><b><?= e($s['nombre']) ?></b><small>Stock mínimo: <?= (int)$s['stock_minimo'] ?></small></div>
        <span class="pill pill-danger"><?= (int)$s['stock'] ?> uds</span></div>
      <?php endforeach; if (!$alertas['stock']) echo '<p class="text-muted small">Inventario saludable.</p>'; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6><i class="bi bi-calendar-check text-primary me-1"></i>Próximas visitas técnicas</h6></div></div>
      <?php foreach ($alertas['visitas'] as $v): ?>
      <div class="alert-row"><div><b><?= e($v['razon_social']) ?></b><small><?= ucfirst($v['tipo']) ?> · <?= e($v['tecnico'] ?? '—') ?></small></div>
        <span class="pill pill-soft-primary"><?= date('d/m H:i', strtotime($v['fecha_programada'])) ?></span></div>
      <?php endforeach; if (!$alertas['visitas']) echo '<p class="text-muted small">Sin visitas programadas.</p>'; ?>
    </div>
  </div>
</div>

<?php
$charts = ['serie' => $serie, 'estados' => $estados, 'categorias' => $categorias, 'canales' => $canales, 'sla' => $kpi['sla']];
$scripts = '<script>window.TD_DATA = ' . json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>'
         . '<script src="' . asset('js/dashboard.js') . '"></script>';
?>
