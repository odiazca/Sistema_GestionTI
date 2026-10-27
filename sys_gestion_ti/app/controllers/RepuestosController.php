<?php
class RepuestosController extends CrudController
{
    protected function cfg(): array
    {
        $cats = ['Almacenamiento','Memoria','Componentes','Redes','Consumibles','Baterías','Pantallas','Periféricos','Cables y conectores','Otro'];
        $cats = array_combine($cats, $cats);
        return [
            'slug' => 'repuestos', 'table' => 'repuestos', 'title' => 'Repuestos y Almacén', 'singular' => 'Repuesto', 'group' => 'Logística y Ventas',
            'subtitle' => 'Control de stock, mínimos y valorización del almacén técnico.', 'label_field' => 'nombre',
            'select' => 't.*', 'from' => 'repuestos t', 'order' => 't.nombre',
            'search' => ['t.codigo', 't.nombre', 't.categoria'],
            'filters' => [
                ['name' => 'categoria', 'col' => 't.categoria', 'label' => 'Categoría', 'options' => $cats],
            ],
            'stats' => [
                ['Ítems en catálogo', 'SELECT COUNT(*) FROM repuestos', 'bi-box-seam-fill', '#4f46e5'],
                ['Stock bajo', 'SELECT COUNT(*) FROM repuestos WHERE stock <= stock_minimo AND stock > 0', 'bi-exclamation-triangle-fill', '#f59e0b'],
                ['Agotados', 'SELECT COUNT(*) FROM repuestos WHERE stock = 0', 'bi-x-octagon-fill', '#ef4444'],
                ['Valor del inventario', 'SELECT COALESCE(SUM(stock*precio),0) FROM repuestos', 'bi-currency-dollar', '#10b981', 'money'],
            ],
            'columns' => [
                ['label' => 'Repuesto', 'key' => 'nombre', 'sub' => 'codigo', 'main' => true],
                ['label' => 'Categoría', 'key' => 'categoria'],
                ['label' => 'Stock', 'html' => function ($r) {
                    $cls = $r['stock'] == 0 ? 'danger' : ($r['stock'] <= $r['stock_minimo'] ? 'warning' : 'success');
                    $pct = min(100, (int)round($r['stock'] * 100 / max(1, $r['stock_minimo'] * 3)));
                    return '<div class="d-flex align-items-center gap-2" style="min-width:150px"><b style="width:28px">' . (int)$r['stock'] . '</b><div class="progress-ui"><div class="bar bar-' . ($cls === 'success' ? 'info' : $cls) . '" style="width:' . $pct . '%"></div></div></div><div class="tk-sub">Mínimo: ' . (int)$r['stock_minimo'] . '</div>';
                }],
                ['label' => 'Precio', 'key' => 'precio', 'type' => 'money'],
                ['label' => 'Valorizado', 'html' => fn($r) => e(money($r['stock'] * $r['precio']))],
                ['label' => 'Estado', 'html' => fn($r) => $r['stock'] == 0 ? pill('Agotado', 'danger') : ($r['stock'] <= $r['stock_minimo'] ? pill('Stock bajo', 'warning') : pill('Disponible', 'success'))],
            ],
            'extra_actions' => fn($r) => '<form method="post" action="' . url('repuestos/ajustar/' . $r['id']) . '" class="d-inline-flex gap-1">' . csrf_field()
                . '<input type="number" name="cantidad" class="form-control form-control-sm" style="width:68px;height:34px;border-radius:10px" placeholder="±" title="Positivo = ingreso, negativo = salida" required>'
                . '<button class="act-btn" title="Ajustar stock"><i class="bi bi-arrow-down-up"></i></button></form>',
            'fields' => [
                ['name' => 'nombre', 'label' => 'Nombre del repuesto', 'type' => 'text', 'required' => true, 'col' => 6, 'section' => 'Datos generales', 'maxlength' => 120],
                ['name' => 'codigo', 'label' => 'Código', 'type' => 'text', 'col' => 3, 'placeholder' => 'Automático', 'maxlength' => 20],
                ['name' => 'categoria', 'label' => 'Categoría', 'type' => 'select', 'options' => $cats, 'col' => 3],
                ['name' => 'stock', 'label' => 'Stock actual', 'type' => 'number', 'required' => true, 'col' => 3, 'section' => 'Inventario', 'default' => 0],
                ['name' => 'stock_minimo', 'label' => 'Stock mínimo', 'type' => 'number', 'required' => true, 'col' => 3, 'default' => 5],
                ['name' => 'precio', 'label' => 'Precio unitario (S/)', 'type' => 'number', 'step' => '0.01', 'required' => true, 'col' => 3, 'default' => 0],
                ['name' => 'ubicacion', 'label' => 'Ubicación en almacén', 'type' => 'text', 'col' => 3, 'maxlength' => 60],
            ],
        ];
    }

    protected function validate(array $d, ?int $id): array
    {
        $e = [];
        foreach (['stock', 'stock_minimo', 'precio'] as $k) if (isset($d[$k]) && $d[$k] < 0) $e[$k] = 'No puede ser negativo.';
        return $e;
    }

    protected function beforeSave(array $d, ?int $id): array
    {
        if (empty($d['codigo'])) {
            $n = (int)Database::conn()->query('SELECT COALESCE(MAX(id),0)+1 FROM repuestos')->fetchColumn();
            $d['codigo'] = sprintf('REP-%03d', $n);
        }
        return $d;
    }

    /** Ingreso (+) o salida (−) rápida de stock */
    public function ajustar($id = 0): void
    {
        $this->guard();
        $this->requirePost('repuestos');
        $id = (int)$id; $q = (int)($_POST['cantidad'] ?? 0);
        $r = $this->find($id);
        if (!$r || $q === 0) { $this->flash('warning', 'Indica una cantidad distinta de cero.'); $this->redirect('repuestos'); }
        if ($r['stock'] + $q < 0) { $this->flash('danger', "Stock insuficiente: solo hay {$r['stock']} unidades de {$r['nombre']}."); $this->redirect('repuestos'); }
        Database::conn()->prepare('UPDATE repuestos SET stock = stock + ? WHERE id = ?')->execute([$q, $id]);
        $this->log($q > 0 ? 'Registró ingreso de repuestos' : 'Registró salida de repuestos', $r['nombre'] . ' ' . ($q > 0 ? '+' : '') . $q, $q > 0 ? 'bi-box-arrow-in-down' : 'bi-box-arrow-up', $q > 0 ? '#14b8a6' : '#f59e0b');
        $this->flash('success', ($q > 0 ? 'Ingreso' : 'Salida') . " registrado: {$r['nombre']} ({$q}). Stock actual: " . ($r['stock'] + $q) . '.');
        $this->redirect('repuestos');
    }
}
