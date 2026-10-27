<?php
/**
 * CRUD genérico configurable. Cada módulo define cfg() con tabla, campos,
 * columnas, filtros y estadísticas. Evita repetir código en cada mantenimiento.
 */
abstract class CrudController extends Controller
{
    protected array $c = [];
    abstract protected function cfg(): array;

    public function __construct() { $this->c = $this->cfg() + ['pk' => 'id', 'per' => 10, 'filters' => [], 'search' => [], 'stats' => [], 'order' => 't.id DESC']; }

    protected function guard(): void
    {
        if (!empty($this->c['roles'])) $this->requireRole($this->c['roles']); else $this->requireAuth();
    }
    protected function url(string $sub = ''): string { return $this->c['slug'] . ($sub ? '/' . $sub : ''); }

    /** Hooks para personalizar */
    protected function beforeSave(array $data, ?int $id): array { return $data; }
    protected function canDelete(int $id): ?string { return null; } // devuelve mensaje de error o null

    // ---------------------------------------------------------------- LISTADO
    public function index(): void
    {
        $this->guard();
        $db = Database::conn(); $c = $this->c;
        $where = []; $p = [];
        $q = trim($_GET['q'] ?? '');
        if ($q !== '' && $c['search']) {
            $parts = [];
            foreach ($c['search'] as $i => $col) { $parts[] = "$col LIKE :q$i"; $p[":q$i"] = "%$q%"; }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        foreach ($c['filters'] as $f) {
            $v = $_GET[$f['name']] ?? '';
            if ($v !== '') { $where[] = "{$f['col']} = :f_{$f['name']}"; $p[":f_{$f['name']}"] = $v; }
        }
        $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $st = $db->prepare("SELECT COUNT(*) FROM {$c['from']}$w"); $st->execute($p);
        $pg = $this->paginate((int)$st->fetchColumn(), $c['per'], (int)($_GET['page'] ?? 1));
        $st = $db->prepare("SELECT {$c['select']} FROM {$c['from']}$w ORDER BY {$c['order']} LIMIT {$pg['per']} OFFSET {$pg['offset']}");
        $st->execute($p);

        $stats = [];
        foreach ($c['stats'] as $s) { $stats[] = ['label' => $s[0], 'value' => $db->query($s[1])->fetchColumn(), 'icon' => $s[2], 'color' => $s[3], 'fmt' => $s[4] ?? null]; }
        $filters = [];
        foreach ($c['filters'] as $f) { $f['opts'] = $this->options($f); $filters[] = $f; }

        $this->render('crud/index', ['c' => $c, 'rows' => $st->fetchAll(), 'pg' => $pg, 'q' => $q, 'filters' => $filters, 'stats' => $stats], $c['title']);
    }

    protected function options(array $f): array
    {
        if (isset($f['options'])) return $f['options'];
        if (isset($f['options_sql'])) return array_column(Database::conn()->query($f['options_sql'])->fetchAll(PDO::FETCH_NUM), 1, 0);
        return [];
    }

    // ---------------------------------------------------------------- FORM
    public function crear(): void
    {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->save(null); return; }
        $row = [];
        foreach ($this->c['fields'] as $f) $row[$f['name']] = $f['default'] ?? ($f['type'] === 'switch' ? 1 : '');
        $this->form($row, [], null);
    }

    public function editar($id = 0): void
    {
        $this->guard();
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->save($id); return; }
        $row = $this->find($id);
        if (!$row) { $this->flash('danger', 'El registro no existe.'); $this->redirect($this->url()); }
        foreach ($this->c['fields'] as $f) if ($f['type'] === 'password') $row[$f['name']] = '';
        $this->form($row, [], $id);
    }

    protected function find(int $id): ?array
    {
        $st = Database::conn()->prepare("SELECT * FROM {$this->c['table']} WHERE {$this->c['pk']} = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    protected function form(array $row, array $errors, ?int $id): void
    {
        $fields = $this->c['fields'];
        foreach ($fields as &$f) {
            $f['opts'] = in_array($f['type'], ['select']) ? $this->options($f) : [];
            if ($f['type'] === 'datetime-local' && !empty($row[$f['name']]) && strpos((string)$row[$f['name']], 'T') === false)
                $row[$f['name']] = date('Y-m-d\TH:i', strtotime($row[$f['name']]));
        }
        unset($f);
        $this->render('crud/form', ['c' => $this->c, 'fields' => $fields, 'row' => $row, 'errors' => $errors, 'id' => $id],
            ($id ? 'Editar ' : 'Nuevo ') . mb_strtolower($this->c['singular']));
    }

    // ---------------------------------------------------------------- GUARDAR
    protected function save(?int $id): void
    {
        $this->requirePost($this->url($id ? "editar/$id" : 'crear'));
        $data = []; $errors = []; $row = [];
        foreach ($this->c['fields'] as $f) {
            $n = $f['name'];
            if ($f['type'] === 'switch') { $data[$n] = isset($_POST[$n]) ? 1 : 0; $row[$n] = $data[$n]; continue; }
            $v = trim((string)($_POST[$n] ?? ''));
            $row[$n] = $f['type'] === 'password' ? '' : $v;
            if ($f['type'] === 'password') {
                if ($v === '') { if (!$id && !empty($f['required'])) $errors[$n] = 'La contraseña es obligatoria.'; continue; }
                if (mb_strlen($v) < 6) { $errors[$n] = 'Mínimo 6 caracteres.'; continue; }
                $data[$n] = password_hash($v, PASSWORD_DEFAULT); continue;
            }
            if ($v === '') {
                if (!empty($f['required'])) $errors[$n] = 'Este campo es obligatorio.';
                $data[$n] = ($f['type'] === 'text' || $f['type'] === 'textarea') && empty($f['nullable']) && !empty($f['required']) ? '' : null;
                continue;
            }
            if ($f['type'] === 'datetime-local') { $v = str_replace('T', ' ', $v) . (strlen($v) === 16 ? ':00' : ''); if (!strtotime($v)) $errors[$n] = 'Fecha y hora no válidas.'; }
            if ($f['type'] === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[$n] = 'Correo no válido.';
            if ($f['type'] === 'number' && !is_numeric($v)) $errors[$n] = 'Debe ser un número.';
            if ($f['type'] === 'select' && !array_key_exists($v, $this->options($f))) $errors[$n] = 'Selecciona una opción válida.';
            if (!empty($f['maxlength']) && mb_strlen($v) > $f['maxlength']) $errors[$n] = 'Máximo ' . $f['maxlength'] . ' caracteres.';
            $data[$n] = $v;
        }
        if (!$errors) $errors = $this->validate($data, $id);
        if ($errors) { $this->flash('danger', 'Revisa los campos marcados en el formulario.'); $this->form($row + ($id ? ['id' => $id] : []), $errors, $id); return; }

        $data = $this->beforeSave($data, $id);
        $db = Database::conn();
        try {
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
                $st = $db->prepare("UPDATE {$this->c['table']} SET $set WHERE {$this->c['pk']} = :__id");
                $st->execute($this->bind($data) + [':__id' => $id]);
                $this->log('Actualizó ' . mb_strtolower($this->c['singular']), $this->label($data), 'bi-pencil-square', '#0ea5e9');
                $this->flash('success', $this->c['singular'] . ' actualizado correctamente.');
            } else {
                $cols = implode(', ', array_keys($data)); $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
                $db->prepare("INSERT INTO {$this->c['table']} ($cols) VALUES ($ph)")->execute($this->bind($data));
                $this->log('Registró ' . mb_strtolower($this->c['singular']), $this->label($data), 'bi-plus-circle-fill', '#10b981');
                $this->flash('success', $this->c['singular'] . ' registrado correctamente.');
            }
        } catch (PDOException $e) {
            $msg = $e->getCode() === '23000' ? 'Ya existe un registro con el mismo código, usuario o correo.' : 'No se pudo guardar: ' . $e->getMessage();
            $this->flash('danger', $msg);
            $this->form($row, [], $id); return;
        }
        $this->redirect($this->url());
    }

    protected function validate(array $data, ?int $id): array { return []; }
    private function bind(array $d): array { $o = []; foreach ($d as $k => $v) $o[":$k"] = $v; return $o; }
    private function label(array $d): string { return (string)($d[$this->c['label_field'] ?? 'nombre'] ?? $d['razon_social'] ?? $d['codigo'] ?? $d['nombres'] ?? ''); }
    protected function log(string $accion, string $detalle, string $icono, string $color): void
    {
        try {
            Database::conn()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')
                ->execute([Auth::user()['id'], $accion, mb_substr($detalle, 0, 190), $icono, $color]);
        } catch (Throwable $e) {}
    }

    // ---------------------------------------------------------------- ELIMINAR
    public function eliminar($id = 0): void
    {
        $this->guard();
        $this->requirePost($this->url());
        $id = (int)$id;
        if ($msg = $this->canDelete($id)) { $this->flash('warning', $msg); $this->redirect($this->url()); }
        try {
            Database::conn()->prepare("DELETE FROM {$this->c['table']} WHERE {$this->c['pk']} = ?")->execute([$id]);
            $this->log('Eliminó ' . mb_strtolower($this->c['singular']), "ID $id", 'bi-trash-fill', '#ef4444');
            $this->flash('success', $this->c['singular'] . ' eliminado.');
        } catch (PDOException $e) {
            $this->flash('warning', 'No se puede eliminar: el registro tiene información relacionada (tickets, contratos, equipos…).');
        }
        $this->redirect($this->url());
    }
}
