<?php
class ContratosController extends CrudController
{
    private const SLA = ['Básico' => [8, 72, 420], 'Business' => [4, 36, 950], 'Premium' => [2, 24, 1800]];

    protected function cfg(): array
    {
        $planes = ['Básico' => 'Básico', 'Business' => 'Business', 'Premium' => 'Premium'];
        return [
            'slug' => 'contratos', 'table' => 'contratos', 'title' => 'Contratos y SLA', 'singular' => 'Contrato', 'group' => 'Clientes y Activos',
            'subtitle' => 'Planes de soporte, acuerdos de nivel de servicio y vigencias.', 'label_field' => 'codigo',
            'select' => 'k.*, c.razon_social AS cliente, DATEDIFF(k.fecha_fin, CURDATE()) AS dias',
            'from' => 'contratos k JOIN clientes c ON c.id = k.cliente_id', 'order' => 'k.fecha_fin ASC',
            'search' => ['k.codigo', 'c.razon_social'],
            'filters' => [
                ['name' => 'plan', 'col' => 'k.plan', 'label' => 'Plan', 'options' => $planes],
                ['name' => 'estado', 'col' => 'k.estado', 'label' => 'Estado', 'options' => ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido']],
            ],
            'stats' => [
                ['Contratos vigentes', "SELECT COUNT(*) FROM contratos WHERE estado='vigente'", 'bi-file-earmark-check-fill', '#10b981'],
                ['Por vencer (45 días)', "SELECT COUNT(*) FROM contratos WHERE estado='por_vencer'", 'bi-hourglass-split', '#f59e0b'],
                ['Vencidos', "SELECT COUNT(*) FROM contratos WHERE estado='vencido'", 'bi-x-octagon-fill', '#ef4444'],
                ['Ingreso mensual recurrente', "SELECT COALESCE(SUM(monto_mensual),0) FROM contratos WHERE estado<>'vencido'", 'bi-cash-stack', '#4f46e5', 'money'],
            ],
            'columns' => [
                ['label' => 'Contrato', 'key' => 'codigo', 'sub' => 'cliente', 'main' => true],
                ['label' => 'Plan', 'html' => fn($r) => pill($r['plan'], ['Premium' => 'soft-primary', 'Business' => 'soft-info', 'Básico' => 'muted'][$r['plan']])],
                ['label' => 'SLA (resp. / resol.)', 'html' => fn($r) => '<b>' . (int)$r['sla_respuesta_horas'] . ' h</b> / ' . (int)$r['sla_resolucion_horas'] . ' h'],
                ['label' => 'Vigencia', 'html' => fn($r) => fdate($r['fecha_inicio']) . ' → <b>' . fdate($r['fecha_fin']) . '</b>'],
                ['label' => 'Mensualidad', 'key' => 'monto_mensual', 'type' => 'money'],
                ['label' => 'Estado', 'html' => function ($r) {
                    $d = (int)$r['dias'];
                    if ($r['estado'] === 'vencido') return pill('Vencido', 'danger');
                    return $r['estado'] === 'por_vencer' ? pill("Vence en {$d} d", 'warning') : pill('Vigente', 'success');
                }],
            ],
            'fields' => [
                ['name' => 'cliente_id', 'label' => 'Cliente', 'type' => 'select', 'options_sql' => 'SELECT id, razon_social FROM clientes ORDER BY razon_social', 'required' => true, 'col' => 5, 'section' => 'Contrato'],
                ['name' => 'codigo', 'label' => 'Código', 'type' => 'text', 'col' => 3, 'placeholder' => 'Automático', 'maxlength' => 20],
                ['name' => 'plan', 'label' => 'Plan de soporte', 'type' => 'select', 'options' => $planes, 'required' => true, 'col' => 4],
                ['name' => 'sla_respuesta_horas', 'label' => 'SLA respuesta (horas)', 'type' => 'number', 'col' => 4, 'section' => 'Acuerdo de nivel de servicio', 'help' => 'Vacío = según el plan (Básico 8 · Business 4 · Premium 2)'],
                ['name' => 'sla_resolucion_horas', 'label' => 'SLA resolución (horas)', 'type' => 'number', 'col' => 4, 'help' => 'Vacío = según el plan (72 · 36 · 24)'],
                ['name' => 'monto_mensual', 'label' => 'Mensualidad (S/)', 'type' => 'number', 'step' => '0.01', 'col' => 4, 'help' => 'Vacío = precio del plan'],
                ['name' => 'fecha_inicio', 'label' => 'Inicio', 'type' => 'date', 'required' => true, 'col' => 6, 'section' => 'Vigencia', 'default' => date('Y-m-d')],
                ['name' => 'fecha_fin', 'label' => 'Fin', 'type' => 'date', 'required' => true, 'col' => 6, 'default' => date('Y-m-d', strtotime('+1 year'))],
            ],
        ];
    }

    protected function validate(array $d, ?int $id): array
    {
        return (!empty($d['fecha_inicio']) && !empty($d['fecha_fin']) && $d['fecha_fin'] <= $d['fecha_inicio'])
            ? ['fecha_fin' => 'La fecha de fin debe ser posterior al inicio.'] : [];
    }

    protected function beforeSave(array $d, ?int $id): array
    {
        [$resp, $res, $monto] = self::SLA[$d['plan']] ?? self::SLA['Básico'];
        $d['sla_respuesta_horas']  = $d['sla_respuesta_horas']  ?? $resp;
        $d['sla_resolucion_horas'] = $d['sla_resolucion_horas'] ?? $res;
        $d['monto_mensual']        = $d['monto_mensual']        ?? $monto;
        if (empty($d['codigo'])) {
            $n = (int)Database::conn()->query('SELECT COALESCE(MAX(id),0)+1 FROM contratos')->fetchColumn();
            $d['codigo'] = sprintf('CT-%s-%03d', date('Y'), $n);
        }
        $dias = (strtotime($d['fecha_fin']) - strtotime('today')) / 86400;
        $d['estado'] = $dias < 0 ? 'vencido' : ($dias <= 45 ? 'por_vencer' : 'vigente');
        return $d;
    }
}
