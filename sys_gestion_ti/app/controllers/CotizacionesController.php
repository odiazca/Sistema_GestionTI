<?php
require_once ROOT_PATH . '/core/CrmBase.php';

class CotizacionesController extends CrmBase
{
    private const CONDICIONES = "• Precios expresados en soles (S/) más IGV.\n• Forma de pago: 50% al aceptar la propuesta y 50% contra entrega o conformidad.\n• Plazo de implementación según cronograma acordado.\n• Garantía de 12 meses en equipos y 30 días en servicios.\n• Los equipos y licencias están sujetos a disponibilidad de stock.";

    private function vencer(): void { $this->run("UPDATE crm_cotizaciones SET estado='vencida' WHERE estado IN ('borrador','enviada') AND valida_hasta < CURDATE()"); }

    public function index(): void
    {
        $this->guard(); $this->vencer(); $w = []; $p = [];
        if (($q = trim($_GET['q'] ?? '')) !== '') { $w[] = '(q.codigo LIKE :q1 OR c.razon_social LIKE :q2)'; $p[':q1'] = $p[':q2'] = "%$q%"; }
        if (isset(COT_ESTADOS[$_GET['estado'] ?? ''])) { $w[] = 'q.estado = :e'; $p[':e'] = $_GET['estado']; }
        $W = $w ? ' WHERE ' . implode(' AND ', $w) : '';
        $from = 'crm_cotizaciones q JOIN clientes c ON c.id=q.cliente_id LEFT JOIN crm_oportunidades o ON o.id=q.oportunidad_id';
        $total = (int)$this->val("SELECT COUNT(*) FROM $from$W", $p); $pg = $this->paginate($total, 12, (int)($_GET['page'] ?? 1));
        $rows = $this->all("SELECT q.*, c.razon_social AS cliente, o.codigo AS op_codigo FROM $from$W ORDER BY q.created_at DESC, q.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
        $st = $this->one("SELECT COUNT(*) total, COALESCE(SUM(estado='enviada'),0) enviadas, COALESCE(SUM(CASE WHEN estado='enviada' THEN total END),0) monto_env, COALESCE(SUM(CASE WHEN estado='aprobada' AND fecha >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN total END),0) aprobado_mes,
                COALESCE(ROUND(SUM(estado='aprobada')*100/NULLIF(SUM(estado IN ('aprobada','rechazada')),0)),0) tasa FROM crm_cotizaciones");
        $this->render('cotizaciones/index', compact('rows', 'pg', 'st'), 'Cotizaciones');
    }

    private function formData(?array $row, array $items, array $errors = []): array
    {
        return ['row' => $row, 'items' => $items, 'errors' => $errors, 'id' => (int)($row['id'] ?? 0), 'clientes' => $this->clientes(),
                'contactos' => $this->all('SELECT id, cliente_id, nombre, cargo FROM crm_contactos ORDER BY principal DESC, nombre'),
                'ops' => $this->all("SELECT id, codigo, titulo, cliente_id, valor FROM crm_oportunidades WHERE estado='abierta' ORDER BY created_at DESC")];
    }

    public function crear(): void
    {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar(0); return; }
        $op = (int)($_GET['op'] ?? 0); $cli = (int)($_GET['cliente'] ?? 0); $pre = [];
        if ($op && ($o = $this->one('SELECT * FROM crm_oportunidades WHERE id=?', [$op]))) { $pre['oportunidad_id'] = $o['id']; $pre['cliente_id'] = $o['cliente_id']; $pre['contacto_id'] = $o['contacto_id']; }
        elseif ($cli) $pre['cliente_id'] = $cli;
        $pre += ['fecha' => date('Y-m-d'), 'valida_hasta' => date('Y-m-d', strtotime('+15 days')), 'igv_pct' => cfg('igv', 18), 'descuento_pct' => 0, 'condiciones' => self::CONDICIONES, 'estado' => 'borrador'];
        $this->render('cotizaciones/form', $this->formData($pre, [['descripcion' => '', 'cantidad' => 1, 'precio_unit' => '']]), 'Nueva cotización');
    }

    public function editar($id = 0): void
    {
        $this->guard(); $row = $this->one('SELECT * FROM crm_cotizaciones WHERE id=?', [(int)$id]);
        if (!$row) { $this->flash('danger', 'La cotización no existe.'); $this->redirect('cotizaciones'); }
        if (in_array($row['estado'], ['aprobada', 'rechazada'], true)) { $this->flash('warning', 'Una cotización ' . $row['estado'] . ' no se edita: duplícala para crear una nueva versión.'); $this->redirect("cotizaciones/ver/$id"); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar((int)$id); return; }
        $this->render('cotizaciones/form', $this->formData($row, $this->all('SELECT * FROM crm_cotizacion_items WHERE cotizacion_id=? ORDER BY id', [(int)$id])), 'Editar ' . $row['codigo']);
    }

    private function guardar(int $id): void
    {
        $this->requirePost($id ? "cotizaciones/editar/$id" : 'cotizaciones/crear');
        $cli = (int)($_POST['cliente_id'] ?? 0); $op = (int)($_POST['oportunidad_id'] ?? 0) ?: null; $ct = (int)($_POST['contacto_id'] ?? 0) ?: null;
        $fecha = $_POST['fecha'] ?? ''; $vig = $_POST['valida_hasta'] ?? '';
        $dp = max(0, min(100, (float)($_POST['descuento_pct'] ?? 0))); $igvp = max(0, min(100, (float)($_POST['igv_pct'] ?? 18)));
        $items = []; foreach (($_POST['item_desc'] ?? []) as $i => $desc) {
            $desc = trim((string)$desc); $c = (float)str_replace(',', '', (string)($_POST['item_cant'][$i] ?? 0)); $pr = (float)str_replace(',', '', (string)($_POST['item_precio'][$i] ?? 0));
            if ($desc !== '' && $c > 0 && $pr >= 0) $items[] = ['descripcion' => mb_substr($desc, 0, 255), 'cantidad' => $c, 'precio_unit' => $pr];
        }
        $err = [];
        if (!$this->val('SELECT COUNT(*) FROM clientes WHERE id=?', [$cli])) $err['cliente_id'] = 'Selecciona el cliente.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $err['fecha'] = 'Fecha no válida.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vig) || $vig < $fecha) $err['valida_hasta'] = 'La validez debe ser posterior a la fecha de emisión.';
        if (!$items) $err['items'] = 'Agrega al menos un ítem con descripción, cantidad y precio.';
        if ($op && !$this->val('SELECT COUNT(*) FROM crm_oportunidades WHERE id=? AND cliente_id=?', [$op, $cli])) $op = null;
        if ($ct && !$this->val('SELECT COUNT(*) FROM crm_contactos WHERE id=? AND cliente_id=?', [$ct, $cli])) $ct = null;
        $sub = round(array_sum(array_map(fn($x) => $x['cantidad'] * $x['precio_unit'], $items)), 2);
        $desc = round($sub * $dp / 100, 2); $base = $sub - $desc; $igv = round($base * $igvp / 100, 2); $tot = round($base + $igv, 2);
        if ($err) {
            $row = ['id' => $id, 'cliente_id' => $cli, 'oportunidad_id' => $op, 'contacto_id' => $ct, 'fecha' => $fecha, 'valida_hasta' => $vig, 'descuento_pct' => $dp, 'igv_pct' => $igvp, 'condiciones' => $_POST['condiciones'] ?? '', 'notas' => $_POST['notas'] ?? '', 'estado' => 'borrador'];
            $this->render('cotizaciones/form', $this->formData($row, $items ?: [['descripcion' => '', 'cantidad' => 1, 'precio_unit' => '']], $err), $id ? 'Editar cotización' : 'Nueva cotización'); return;
        }
        $db = $this->db(); $cond = $this->str('condiciones', 3000); $nota = $this->str('notas', 255);
        try {
            $db->beginTransaction();
            if ($id) {
                $this->run("UPDATE crm_cotizaciones SET cliente_id=?, oportunidad_id=?, contacto_id=?, fecha=?, valida_hasta=?, descuento_pct=?, igv_pct=?, subtotal=?, descuento=?, igv=?, total=?, condiciones=?, notas=?, estado=IF(estado='vencida' AND ? >= CURDATE(),'borrador',estado) WHERE id=?",
                    [$cli, $op, $ct, $fecha, $vig, $dp, $igvp, $sub, $desc, $igv, $tot, $cond, $nota, $vig, $id]);
                $this->run('DELETE FROM crm_cotizacion_items WHERE cotizacion_id=?', [$id]); $cod = $this->val('SELECT codigo FROM crm_cotizaciones WHERE id=?', [$id]);
            } else {
                $cod = $this->codigo('crm_cotizaciones', 'COT');
                $this->run('INSERT INTO crm_cotizaciones(codigo,cliente_id,oportunidad_id,contacto_id,fecha,valida_hasta,descuento_pct,igv_pct,subtotal,descuento,igv,total,condiciones,notas,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$cod, $cli, $op, $ct, $fecha, $vig, $dp, $igvp, $sub, $desc, $igv, $tot, $cond, $nota, Auth::user()['id']]);
                $id = (int)$db->lastInsertId();
            }
            foreach ($items as $it) $this->run('INSERT INTO crm_cotizacion_items(cotizacion_id,descripcion,cantidad,precio_unit) VALUES(?,?,?,?)', [$id, $it['descripcion'], $it['cantidad'], $it['precio_unit']]);
            $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); $this->flash('danger', 'No se pudo guardar: ' . $e->getMessage()); $this->redirect('cotizaciones'); }
        $this->audit("Guardó cotización $cod", money($tot), 'bi-receipt-cutoff', '#8b5cf6');
        $this->flash('success', "Cotización $cod guardada por " . money($tot) . '.'); $this->redirect("cotizaciones/ver/$id");
    }

    private function cargar(int $id): array
    {
        $this->vencer();
        $q = $this->one("SELECT q.*, c.razon_social AS cliente, c.ruc, c.direccion, c.email AS cli_email, c.telefono AS cli_tel, ct.nombre AS contacto, ct.cargo AS contacto_cargo, ct.email AS contacto_email, o.codigo AS op_codigo, o.titulo AS op_titulo, CONCAT(u.nombres,' ',u.apellidos) AS autor, u.cargo AS autor_cargo
                         FROM crm_cotizaciones q JOIN clientes c ON c.id=q.cliente_id LEFT JOIN crm_contactos ct ON ct.id=q.contacto_id LEFT JOIN crm_oportunidades o ON o.id=q.oportunidad_id LEFT JOIN usuarios u ON u.id=q.creado_por WHERE q.id=?", [$id]);
        if (!$q) { $this->flash('danger', 'La cotización no existe.'); $this->redirect('cotizaciones'); }
        return [$q, $this->all('SELECT * FROM crm_cotizacion_items WHERE cotizacion_id=? ORDER BY id', [$id])];
    }

    public function ver($id = 0): void
    {
        $this->guard(); [$q, $items] = $this->cargar((int)$id); $this->render('cotizaciones/ver', ['q' => $q, 'items' => $items], $q['codigo']);
    }

    public function imprimir($id = 0): void
    {
        $this->guard(); [$q, $items] = $this->cargar((int)$id); $this->renderPlain('cotizaciones/imprimir', ['q' => $q, 'items' => $items]);
    }

    public function estado($id = 0): void
    {
        $this->guard(); $this->requirePost("cotizaciones/ver/$id"); $id = (int)$id; $e = $_POST['estado'] ?? '';
        [$q] = $this->cargar($id);
        $ok = ['borrador' => ['enviada'], 'enviada' => ['aprobada', 'rechazada', 'borrador'], 'vencida' => ['borrador'], 'rechazada' => ['borrador'], 'aprobada' => []][$q['estado']] ?? [];
        if (!in_array($e, $ok, true)) { $this->flash('danger', 'Ese cambio de estado no es válido.'); $this->redirect("cotizaciones/ver/$id"); }
        $this->run('UPDATE crm_cotizaciones SET estado=? WHERE id=?', [$e, $id]);
        if ($q['oportunidad_id']) {
            $o = $this->one("SELECT o.*, e.orden FROM crm_oportunidades o JOIN crm_etapas e ON e.id=o.etapa_id WHERE o.id=? AND o.estado='abierta'", [$q['oportunidad_id']]);
            if ($o) {
                $mover = fn($nombre) => (function () use ($o, $nombre, $q) {
                    $et = $this->one('SELECT * FROM crm_etapas WHERE nombre=?', [$nombre]);
                    if ($et && (int)$o['orden'] < (int)$et['orden']) { $this->run('UPDATE crm_oportunidades SET etapa_id=?, probabilidad=? WHERE id=?', [$et['id'], $et['probabilidad'], $o['id']]); $this->evento('Etapa → ' . $nombre . ' (cotización ' . $q['codigo'] . ')', (int)$o['cliente_id'], null, (int)$o['id']); }
                })();
                if ($e === 'enviada') { $mover('Propuesta'); $this->evento('Cotización ' . $q['codigo'] . ' enviada por ' . money($q['total']), (int)$q['cliente_id'], null, (int)$o['id'], 'correo'); }
                if ($e === 'aprobada') { $this->run('UPDATE crm_oportunidades SET valor=? WHERE id=?', [$q['total'], $o['id']]); $mover('Negociación'); $this->evento('Cotización ' . $q['codigo'] . ' APROBADA por ' . money($q['total']), (int)$q['cliente_id'], null, (int)$o['id']); }
                if ($e === 'rechazada') $this->evento('Cotización ' . $q['codigo'] . ' rechazada por el cliente', (int)$q['cliente_id'], null, (int)$o['id']);
            }
        }
        $this->audit('Cotización ' . $q['codigo'] . ' → ' . COT_ESTADOS[$e], $q['cliente'] . ' · ' . money($q['total']), 'bi-receipt-cutoff', '#8b5cf6');
        $msg = ['enviada' => 'Cotización marcada como enviada.', 'aprobada' => '¡Cotización aprobada! El valor de la oportunidad se actualizó.', 'rechazada' => 'Cotización marcada como rechazada.', 'borrador' => 'Cotización devuelta a borrador.'][$e];
        $this->flash($e === 'aprobada' ? 'success' : 'info', $msg); $this->redirect("cotizaciones/ver/$id");
    }

    public function enviar($id = 0): void
    {
        $this->guard(); $this->requirePost("cotizaciones/ver/$id"); $id = (int)$id; [$q, $items] = $this->cargar($id);
        $to = trim($_POST['para'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $this->flash('danger', 'Ingresa un correo de destino válido.'); $this->redirect("cotizaciones/ver/$id"); }
        require_once ROOT_PATH . '/core/Notifier.php';
        $r = Notifier::cotizacion($q, $items, $to, trim($_POST['mensaje'] ?? ''), (int)$q['cliente_id']);
        if ($q['estado'] === 'borrador' && $r !== 'error') { $this->run("UPDATE crm_cotizaciones SET estado='enviada' WHERE id=?", [$id]); }
        $this->audit('Cotización ' . $q['codigo'] . ' enviada por correo', $to . ' · ' . $r, 'bi-envelope-check-fill', '#8b5cf6');
        $this->flash($r === 'enviado' ? 'success' : ($r === 'simulado' ? 'warning' : 'danger'), $r === 'enviado' ? "Cotización enviada a $to." : ($r === 'simulado' ? 'SMTP sin configurar: el correo quedó «simulado» en Notificaciones.' : 'No se pudo enviar el correo (revisa Notificaciones).'));
        $this->redirect("cotizaciones/ver/$id");
    }

    /** Convierte una cotización aprobada en una orden de servicio (una sola vez) */
    public function aorden($id = 0): void
    {
        $this->guard(); $this->requirePost("cotizaciones/ver/$id"); $id = (int)$id; [$q, $items] = $this->cargar($id);
        if ($q['estado'] !== 'aprobada') { $this->flash('danger', 'Solo se pueden convertir cotizaciones aprobadas.'); $this->redirect("cotizaciones/ver/$id"); }
        if ($ex = $this->one('SELECT id, codigo FROM ordenes_servicio WHERE cotizacion_id=?', [$id])) { $this->flash('info', 'Esta cotización ya tiene la orden ' . $ex['codigo'] . '.'); $this->redirect('ordenes/ver/' . $ex['id']); }
        $desc = "Según cotización {$q['codigo']}:\n"; foreach ($items as $it) $desc .= '• ' . rtrim(rtrim(number_format($it['cantidad'], 2, '.', ''), '0'), '.') . ' × ' . $it['descripcion'] . "\n";
        $tarifa = (float)cfg('tarifa_hora', '45');
        $n = (int)$this->db()->query('SELECT COALESCE(MAX(id),0)+1 FROM ordenes_servicio')->fetchColumn(); $cod = sprintf('OS-%s-%03d', date('Y'), $n);
        $this->run("INSERT INTO ordenes_servicio(codigo,cliente_id,tipo,estado,descripcion_trabajo,tarifa_hora,cotizacion_id) VALUES(?,?,?,?,?,?,?)", [$cod, $q['cliente_id'], 'instalacion', 'pendiente', trim($desc), $tarifa, $id]);
        $oid = (int)$this->db()->lastInsertId();
        $this->audit("Orden $cod creada desde {$q['codigo']}", $q['cliente'], 'bi-clipboard2-check-fill', '#0ea5e9');
        $this->flash('success', "Orden de servicio $cod creada. Asigna técnico y fecha para programarla."); $this->redirect("ordenes/ver/$oid");
    }

    public function duplicar($id = 0): void
    {
        $this->guard(); $this->requirePost("cotizaciones/ver/$id"); [$q, $items] = $this->cargar((int)$id); $db = $this->db();
        try {
            $db->beginTransaction(); $cod = $this->codigo('crm_cotizaciones', 'COT');
            $this->run('INSERT INTO crm_cotizaciones(codigo,cliente_id,oportunidad_id,contacto_id,fecha,valida_hasta,descuento_pct,igv_pct,subtotal,descuento,igv,total,condiciones,notas,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$cod, $q['cliente_id'], $q['oportunidad_id'], $q['contacto_id'], date('Y-m-d'), date('Y-m-d', strtotime('+15 days')), $q['descuento_pct'], $q['igv_pct'], $q['subtotal'], $q['descuento'], $q['igv'], $q['total'], $q['condiciones'], $q['notas'], Auth::user()['id']]);
            $nid = (int)$db->lastInsertId();
            foreach ($items as $it) $this->run('INSERT INTO crm_cotizacion_items(cotizacion_id,descripcion,cantidad,precio_unit) VALUES(?,?,?,?)', [$nid, $it['descripcion'], $it['cantidad'], $it['precio_unit']]);
            $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); $this->flash('danger', 'No se pudo duplicar.'); $this->redirect("cotizaciones/ver/$id"); }
        $this->flash('success', "Nueva versión $cod creada como borrador."); $this->redirect("cotizaciones/editar/$nid");
    }

    public function eliminar($id = 0): void
    {
        $this->guard(); $this->requirePost('cotizaciones'); [$q] = $this->cargar((int)$id);
        if ($q['estado'] === 'aprobada' || (!$this->esGestor() && $q['estado'] !== 'borrador')) { $this->flash('danger', 'Solo se pueden eliminar borradores (o por un gestor, las no aprobadas).'); $this->redirect("cotizaciones/ver/$id"); }
        $this->run('DELETE FROM crm_cotizaciones WHERE id=?', [$q['id']]); $this->audit('Eliminó cotización ' . $q['codigo'], $q['cliente'], 'bi-trash3-fill', '#ef4444');
        $this->flash('success', 'Cotización eliminada.'); $this->redirect('cotizaciones');
    }
}
