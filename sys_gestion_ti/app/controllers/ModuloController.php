<?php
class ModuloController extends Controller
{
    public function mostrar(string $slug): void
    {
        $this->requireAuth();
        $menu = require ROOT_PATH . '/config/menu.php';
        $mod = null;
        foreach ($menu as $items) foreach ($items as $it) if ($it['slug'] === $slug) $mod = $it;
        if (!$mod) { http_response_code(404); $this->render('modulos/404', [], 'No encontrado'); return; }
        $this->render('modulos/proximamente', ['mod' => $mod], $mod['label']);
    }
}
