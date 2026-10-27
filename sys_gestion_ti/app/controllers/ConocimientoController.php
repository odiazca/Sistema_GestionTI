<?php
class ConocimientoController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'slug' => 'conocimiento', 'table' => 'kb_articulos', 'title' => 'Base de Conocimiento', 'singular' => 'Artículo', 'group' => 'Mesa de Ayuda',
            'subtitle' => 'Soluciones documentadas, manuales y procedimientos del equipo.', 'label_field' => 'titulo', 'view' => true,
            'select' => "k.*, c.nombre AS categoria, c.color AS cat_color, CONCAT(u.nombres,' ',u.apellidos) AS autor",
            'from' => 'kb_articulos k LEFT JOIN categorias c ON c.id=k.categoria_id LEFT JOIN usuarios u ON u.id=k.autor_id', 'order' => 'k.vistas DESC',
            'search' => ['k.titulo', 'k.contenido'],
            'filters' => [['name' => 'categoria', 'col' => 'k.categoria_id', 'label' => 'Categoría', 'options_sql' => 'SELECT id, nombre FROM categorias ORDER BY nombre']],
            'stats' => [
                ['Artículos publicados', 'SELECT COUNT(*) FROM kb_articulos', 'bi-journal-bookmark-fill', '#4f46e5'],
                ['Consultas totales', 'SELECT COALESCE(SUM(vistas),0) FROM kb_articulos', 'bi-eye-fill', '#0ea5e9'],
                ['Categorías cubiertas', 'SELECT COUNT(DISTINCT categoria_id) FROM kb_articulos', 'bi-tags-fill', '#10b981'],
                ['Publicados este mes', "SELECT COUNT(*) FROM kb_articulos WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')", 'bi-calendar-plus', '#f59e0b'],
            ],
            'columns' => [
                ['label' => 'Artículo', 'html' => fn($r) => '<a class="fw-bold" style="color:var(--ink)" href="' . url('conocimiento/ver/' . $r['id']) . '">' . e($r['titulo']) . '</a><div class="tk-sub">' . e(mb_strimwidth(preg_replace('/\s+/', ' ', (string)$r['contenido']), 0, 70, '…')) . '</div>'],
                ['label' => 'Categoría', 'html' => fn($r) => $r['categoria'] ? '<i class="dot" style="background:' . e($r['cat_color']) . '"></i> ' . e($r['categoria']) : '—'],
                ['label' => 'Autor', 'key' => 'autor'],
                ['label' => 'Consultas', 'html' => fn($r) => '<i class="bi bi-eye text-muted"></i> ' . (int)$r['vistas']],
                ['label' => 'Publicado', 'key' => 'created_at', 'type' => 'date'],
            ],
            'fields' => [
                ['name' => 'titulo', 'label' => 'Título', 'type' => 'text', 'required' => true, 'col' => 8, 'maxlength' => 180],
                ['name' => 'categoria_id', 'label' => 'Categoría', 'type' => 'select', 'options_sql' => 'SELECT id, nombre FROM categorias ORDER BY nombre', 'col' => 4],
                ['name' => 'contenido', 'label' => 'Contenido / procedimiento', 'type' => 'textarea', 'rows' => 14, 'required' => true, 'col' => 12, 'help' => 'Escribe los pasos numerados, uno por línea.'],
            ],
        ];
    }

    protected function beforeSave(array $d, ?int $id): array
    {
        if (!$id) $d['autor_id'] = Auth::user()['id'];
        return $d;
    }

    public function ver($id = 0): void
    {
        $this->guard(); $id = (int)$id; $db = Database::conn();
        $st = $db->prepare("SELECT k.*, c.nombre categoria, c.color cat_color, CONCAT(u.nombres,' ',u.apellidos) autor FROM kb_articulos k LEFT JOIN categorias c ON c.id=k.categoria_id LEFT JOIN usuarios u ON u.id=k.autor_id WHERE k.id=?");
        $st->execute([$id]);
        $a = $st->fetch();
        if (!$a) { $this->flash('danger', 'El artículo no existe.'); $this->redirect('conocimiento'); }
        $db->prepare('UPDATE kb_articulos SET vistas = vistas + 1 WHERE id=?')->execute([$id]);
        $rel = $db->prepare('SELECT id, titulo, vistas FROM kb_articulos WHERE categoria_id <=> ? AND id<>? ORDER BY vistas DESC LIMIT 4');
        $rel->execute([$a['categoria_id'], $id]);
        $this->render('conocimiento/ver', ['a' => $a, 'rel' => $rel->fetchAll()], $a['titulo']);
    }
}
