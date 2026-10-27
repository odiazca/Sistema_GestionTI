<?php
class ActivosController extends CrudController
{
    protected function cfg(): array
    {
        $tipos = ['Laptop','PC Escritorio','Servidor','Switch','Router','Firewall','Access Point','Impresora','Monitor','UPS','Teléfono IP','Cámara IP','Otro'];
        $tipos = array_combine($tipos, $tipos);
        $est = ['operativo' => 'Operativo', 'en_reparacion' => 'En reparación', 'baja' => 'De baja'];
        return [
            'slug' => 'activos', 'table' => 'activos', 'title' => 'Inventario de Activos', 'singular' => 'Equipo', 'group' => 'Clientes y Activos',
            'subtitle' => 'Equipos, dispositivos y garantías de cada cliente.', 'label_field' => 'codigo',
            'select' => 'a.*, c.razon_social AS cliente, DATEDIFF(a.garantia_hasta, CURDATE()) AS dias_gar',
            'from' => 'activos a JOIN clientes c ON c.id = a.cliente_id', 'order' => 'a.codigo',
            'search' => ['a.codigo', 'a.marca', 'a.modelo', 'a.serie', 'c.razon_social', 'a.ubicacion'],
            'filters' => [
                ['name' => 'cliente', 'col' => 'a.cliente_id', 'label' => 'Cliente', 'options_sql' => 'SELECT id, razon_social FROM clientes ORDER BY razon_social'],
                ['name' => 'tipo', 'col' => 'a.tipo', 'label' => 'Tipo', 'options' => $tipos],
                ['name' => 'estado', 'col' => 'a.estado', 'label' => 'Estado', 'options' => $est],
            ],
            'stats' => [
                ['Equipos registrados', 'SELECT COUNT(*) FROM activos', 'bi-pc-display-horizontal', '#4f46e5'],
                ['Operativos', "SELECT COUNT(*) FROM activos WHERE estado='operativo'", 'bi-check-circle-fill', '#10b981'],
                ['En reparación', "SELECT COUNT(*) FROM activos WHERE estado='en_reparacion'", 'bi-tools', '#f59e0b'],
                ['Garantía vence en 60 días', 'SELECT COUNT(*) FROM activos WHERE garantia_hasta BETWEEN CURDATE() AND CURDATE() + INTERVAL 60 DAY', 'bi-shield-exclamation', '#ef4444'],
            ],
            'columns' => [
                ['label' => 'Código', 'key' => 'codigo', 'html' => fn($r) => '<b class="tk-code">' . e($r['codigo']) . '</b><div class="tk-sub">' . e($r['serie'] ?: 'Sin serie') . '</div>'],
                ['label' => 'Equipo', 'html' => fn($r) => '<b>' . e($r['tipo']) . '</b><div class="tk-sub">' . e(trim($r['marca'] . ' ' . $r['modelo'])) . '</div>'],
                ['label' => 'Cliente', 'key' => 'cliente', 'sub' => 'ubicacion', 'main' => true],
                ['label' => 'Garantía', 'html' => function ($r) {
                    if ($r['garantia_hasta'] === null) return '<span class="text-muted small">—</span>';
                    $d = (int)$r['dias_gar'];
                    return $d < 0 ? pill('Vencida', 'danger') : ($d <= 60 ? pill("Vence en {$d} d", 'warning') : pill('Vigente', 'success'));
                }],
                ['label' => 'Estado', 'html' => fn($r) => pill($est[$r['estado']], ['operativo' => 'success', 'en_reparacion' => 'warning', 'baja' => 'muted'][$r['estado']])],
            ],
            'fields' => [
                ['name' => 'cliente_id', 'label' => 'Cliente', 'type' => 'select', 'options_sql' => 'SELECT id, razon_social FROM clientes ORDER BY razon_social', 'required' => true, 'col' => 6, 'section' => 'Identificación'],
                ['name' => 'codigo', 'label' => 'Código', 'type' => 'text', 'col' => 3, 'placeholder' => 'Automático', 'help' => 'Déjalo vacío para generar ACT-0000', 'maxlength' => 20],
                ['name' => 'tipo', 'label' => 'Tipo de equipo', 'type' => 'select', 'options' => $tipos, 'required' => true, 'col' => 3],
                ['name' => 'marca', 'label' => 'Marca', 'type' => 'text', 'col' => 4, 'maxlength' => 40, 'section' => 'Detalle técnico'],
                ['name' => 'modelo', 'label' => 'Modelo', 'type' => 'text', 'col' => 4, 'maxlength' => 60],
                ['name' => 'serie', 'label' => 'N.º de serie', 'type' => 'text', 'col' => 4, 'maxlength' => 60],
                ['name' => 'ubicacion', 'label' => 'Ubicación', 'type' => 'text', 'col' => 4, 'maxlength' => 100, 'section' => 'Ubicación y garantía'],
                ['name' => 'fecha_compra', 'label' => 'Fecha de compra', 'type' => 'date', 'col' => 4],
                ['name' => 'garantia_hasta', 'label' => 'Garantía hasta', 'type' => 'date', 'col' => 4],
                ['name' => 'estado', 'label' => 'Estado', 'type' => 'select', 'options' => $est, 'required' => true, 'col' => 4, 'default' => 'operativo'],
            ],
        ];
    }

    protected function beforeSave(array $d, ?int $id): array
    {
        if (empty($d['codigo'])) {
            $n = (int)Database::conn()->query('SELECT COALESCE(MAX(id),0)+1 FROM activos')->fetchColumn();
            $d['codigo'] = sprintf('ACT-%04d', $n);
        }
        return $d;
    }
}
