<?php
class Ticket
{
    public const FACTOR = ['baja' => 1.5, 'media' => 1.0, 'alta' => 0.5, 'critica' => 0.25];
    private PDO $db;
    public function __construct() { $this->db = Database::conn(); }

    private function all(string $sql, array $p = []): array { $s = $this->db->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    private function one(string $sql, array $p = []): ?array { $s = $this->db->prepare($sql); $s->execute($p); return $s->fetch() ?: null; }

    // ------------------------------------------------------------ listado
    public function listar(array $f, int $limit, int $offset): array
    {
        [$w, $p] = $this->where($f);
        $total = (int)$this->one("SELECT COUNT(*) n FROM tickets t JOIN clientes c ON c.id=t.cliente_id $w", $p)['n'];
        $rows = $this->all("SELECT t.*, c.razon_social cliente, cat.nombre categoria, cat.color cat_color,
                CONCAT(u.nombres,' ',u.apellidos) tecnico, u.avatar_color color
            FROM tickets t JOIN clientes c ON c.id=t.cliente_id JOIN categorias cat ON cat.id=t.categoria_id
            LEFT JOIN usuarios u ON u.id=t.tecnico_id $w
            ORDER BY " . ($f['orden'] ?? false ? "FIELD(t.prioridad,'critica','alta','media','baja'), " : '') . "t.created_at DESC
            LIMIT $limit OFFSET $offset", $p);
        return [$rows, $total];
    }

    private function where(array $f): array
    {
        $w = []; $p = [];
        if (!empty($f['q'])) {
            $w[] = '(t.codigo LIKE :q1 OR t.asunto LIKE :q2 OR c.razon_social LIKE :q3)';
            $p[':q1'] = $p[':q2'] = $p[':q3'] = '%' . $f['q'] . '%';
        }
        foreach (['estado' => 't.estado', 'prioridad' => 't.prioridad', 'categoria' => 't.categoria_id', 'cliente' => 't.cliente_id'] as $k => $col) {
            if (($f[$k] ?? '') !== '') { $w[] = "$col = :$k"; $p[":$k"] = $f[$k]; }
        }
        if (($f['tecnico'] ?? '') === '0') $w[] = 't.tecnico_id IS NULL';
        elseif (($f['tecnico'] ?? '') !== '') { $w[] = 't.tecnico_id = :tec'; $p[':tec'] = $f['tecnico']; }
        if (!empty($f['vencidos'])) $w[] = "t.estado IN ('abierto','en_proceso','pendiente') AND t.fecha_limite < NOW()";
        return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
    }

    public function conteos(): array
    {
        $r = array_column($this->all('SELECT estado, COUNT(*) n FROM tickets GROUP BY estado'), 'n', 'estado');
        $r['todos'] = array_sum($r);
        $r['vencidos'] = (int)$this->one("SELECT COUNT(*) n FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente') AND fecha_limite < NOW()")['n'];
        return $r;
    }

    // ------------------------------------------------------------ catálogos
    public function clientes(): array { return $this->all('SELECT id, razon_social FROM clientes WHERE estado=1 ORDER BY razon_social'); }
    public function categorias(): array { return $this->all('SELECT id, nombre FROM categorias ORDER BY nombre'); }
    public function tecnicos(): array
    {
        return $this->all("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) nombre FROM usuarios u JOIN roles r ON r.id=u.rol_id
                           WHERE u.estado=1 AND r.nombre IN ('Técnico','Supervisor','Administrador') ORDER BY u.nombres");
    }
    public function activosDe(int $cid): array { return $this->all("SELECT id, codigo, tipo, marca, modelo FROM activos WHERE cliente_id=? AND estado<>'baja' ORDER BY codigo", [$cid]); }

    // ------------------------------------------------------------ detalle
    public function obtener(int $id): ?array
    {
        return $this->one("SELECT t.*, c.razon_social cliente, c.contacto, c.telefono cli_tel, c.email cli_email, cat.nombre categoria, cat.icono cat_icono,
                CONCAT(u.nombres,' ',u.apellidos) tecnico, u.avatar_color color,
                a.codigo act_codigo, a.tipo act_tipo, a.marca act_marca, a.modelo act_modelo,
                CONCAT(cr.nombres,' ',cr.apellidos) creador
            FROM tickets t JOIN clientes c ON c.id=t.cliente_id JOIN categorias cat ON cat.id=t.categoria_id
            LEFT JOIN usuarios u ON u.id=t.tecnico_id LEFT JOIN activos a ON a.id=t.activo_id LEFT JOIN usuarios cr ON cr.id=t.creado_por
            WHERE t.id=?", [$id]);
    }

    public function comentarios(int $id): array
    {
        return $this->all("SELECT m.*, CONCAT(u.nombres,' ',u.apellidos) autor, u.avatar_color color FROM ticket_comentarios m
            LEFT JOIN usuarios u ON u.id=m.usuario_id WHERE m.ticket_id=? ORDER BY m.created_at ASC, m.id ASC", [$id]);
    }

    // ------------------------------------------------------------ SLA
    public function limite(int $clienteId, string $prio, ?int $desde = null): string
    {
        $r = $this->one("SELECT sla_resolucion_horas h FROM contratos WHERE cliente_id=? AND estado<>'vencido' ORDER BY fecha_fin DESC LIMIT 1", [$clienteId]);
        $h = max(2, ($r['h'] ?? 48) * (self::FACTOR[$prio] ?? 1));
        return date('Y-m-d H:i:s', ($desde ?? time()) + (int)round($h * 3600));
    }
    public function slaBase(int $clienteId): int
    {
        $r = $this->one("SELECT sla_resolucion_horas h FROM contratos WHERE cliente_id=? AND estado<>'vencido' ORDER BY fecha_fin DESC LIMIT 1", [$clienteId]);
        return (int)($r['h'] ?? 48);
    }

    // ------------------------------------------------------------ escritura
    public function crear(array $d, int $uid): int
    {
        $n = (int)$this->one('SELECT COALESCE(MAX(id),0)+1 n FROM tickets')['n'];
        $codigo = sprintf('TCK-%05d', 1000 + $n);
        $st = $this->db->prepare('INSERT INTO tickets(codigo,cliente_id,activo_id,categoria_id,asunto,descripcion,prioridad,estado,canal,tecnico_id,creado_por,fecha_limite)
                                  VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$codigo, $d['cliente_id'], $d['activo_id'] ?: null, $d['categoria_id'], $d['asunto'], $d['descripcion'] ?: null, $d['prioridad'],
                      $d['tecnico_id'] ? 'en_proceso' : 'abierto', $d['canal'], $d['tecnico_id'] ?: null, $uid, $this->limite((int)$d['cliente_id'], $d['prioridad'])]);
        $id = (int)$this->db->lastInsertId();
        $this->evento($id, $uid, 'creó el ticket' . ($d['tecnico_id'] ? ' y lo asignó a un técnico' : ''));
        $this->actividad($uid, "Creó el ticket $codigo", $d['asunto'], 'bi-plus-circle-fill', '#0ea5e9');
        return $id;
    }

    public function editar(int $id, array $d, int $uid): void
    {
        $this->db->prepare('UPDATE tickets SET cliente_id=?, activo_id=?, categoria_id=?, asunto=?, descripcion=?, canal=? WHERE id=?')
            ->execute([$d['cliente_id'], $d['activo_id'] ?: null, $d['categoria_id'], $d['asunto'], $d['descripcion'] ?: null, $d['canal'], $id]);
        $this->evento($id, $uid, 'editó los datos del ticket');
    }

    /** Cambia estado / prioridad / técnico registrando cada cambio en el historial */
    public function actualizar(int $id, array $new, int $uid): void
    {
        $old = $this->obtener($id);
        if (!$old) return;
        $set = []; $p = [];
        if (isset($new['estado']) && $new['estado'] !== $old['estado'] && isset(ESTADOS_TK[$new['estado']])) {
            $set[] = 'estado=?'; $p[] = $new['estado'];
            if (in_array($new['estado'], ['resuelto', 'cerrado'])) { if (!$old['resuelto_at']) { $set[] = 'resuelto_at=NOW()'; } }
            else { $set[] = 'resuelto_at=NULL'; }
            $this->evento($id, $uid, 'cambió el estado de <b>' . ESTADOS_TK[$old['estado']] . '</b> a <b>' . ESTADOS_TK[$new['estado']] . '</b>');
            if ($new['estado'] === 'resuelto') $this->actividad($uid, "Resolvió el ticket {$old['codigo']}", $old['asunto'], 'bi-check-circle-fill', '#10b981');
        }
        if (isset($new['prioridad']) && $new['prioridad'] !== $old['prioridad'] && isset(PRIORIDADES[$new['prioridad']])) {
            $set[] = 'prioridad=?'; $p[] = $new['prioridad'];
            $set[] = 'fecha_limite=?'; $p[] = $this->limite((int)$old['cliente_id'], $new['prioridad'], strtotime($old['created_at']));
            $this->evento($id, $uid, 'cambió la prioridad de <b>' . PRIORIDADES[$old['prioridad']] . '</b> a <b>' . PRIORIDADES[$new['prioridad']] . '</b> (SLA recalculado)');
            if ($new['prioridad'] === 'critica') $this->actividad($uid, "Escaló ticket crítico {$old['codigo']}", $old['asunto'], 'bi-exclamation-triangle-fill', '#ef4444');
        }
        if (array_key_exists('tecnico_id', $new) && (int)$new['tecnico_id'] !== (int)$old['tecnico_id']) {
            $tec = $new['tecnico_id'] ? $this->one("SELECT CONCAT(nombres,' ',apellidos) n FROM usuarios WHERE id=?", [$new['tecnico_id']])['n'] ?? null : null;
            $set[] = 'tecnico_id=?'; $p[] = $new['tecnico_id'] ?: null;
            if ($tec && $old['estado'] === 'abierto' && !isset($new['estado'])) { $set[] = "estado='en_proceso'"; }
            $this->evento($id, $uid, $tec ? 'asignó el ticket a <b>' . e($tec) . '</b>' : 'quitó la asignación del ticket');
            if ($tec) $this->actividad($uid, "Asignó el ticket {$old['codigo']}", "A: $tec", 'bi-person-check-fill', '#4f46e5');
        }
        if ($set) { $p[] = $id; $this->db->prepare('UPDATE tickets SET ' . implode(', ', $set) . ' WHERE id=?')->execute($p); }
        if (isset($new['estado']) && $new['estado'] !== $old['estado'] && isset(ESTADOS_TK[$new['estado']]) && (Auth::user()['rol'] ?? '') !== 'Cliente') {
            try { require_once ROOT_PATH . '/core/Notifier.php'; Notifier::ticketEstado($id, $new['estado']); } catch (Throwable $e) { error_log("NOTIF: " . $e->getMessage()); }
        }
    }

    public function comentar(int $id, int $uid, string $txt, bool $interno): void
    {
        $this->db->prepare('INSERT INTO ticket_comentarios(ticket_id,usuario_id,comentario,interno) VALUES(?,?,?,?)')->execute([$id, $uid, $txt, $interno ? 1 : 0]);
        $this->db->prepare('UPDATE tickets SET updated_at=NOW() WHERE id=?')->execute([$id]);
        if (!$interno && (Auth::user()['rol'] ?? '') !== 'Cliente') { try { require_once ROOT_PATH . '/core/Notifier.php'; Notifier::ticketRespuesta($id, $txt); } catch (Throwable $e) {} }
    }

    public function eliminar(int $id): void { $this->db->prepare('DELETE FROM tickets WHERE id=?')->execute([$id]); }

    private function evento(int $id, int $uid, string $html): void
    {
        $this->db->prepare('INSERT INTO ticket_comentarios(ticket_id,usuario_id,comentario,interno) VALUES(?,?,?,2)')->execute([$id, $uid, $html]);
    }
    private function actividad(int $uid, string $a, string $d, string $ic, string $col): void
    {
        $this->db->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([$uid, $a, mb_substr($d, 0, 190), $ic, $col]);
    }
}
