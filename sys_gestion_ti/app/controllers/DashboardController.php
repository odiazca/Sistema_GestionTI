<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        if ((Auth::user()['rol'] ?? '') === 'Administrador') { try { require_once ROOT_PATH . '/core/Respaldo.php'; Respaldo::auto(); } catch (Throwable $e) {} }
        $m = $this->model('Dashboard');
        $data = [
            'kpi'       => $m->kpis(),
            'serie'     => $m->serieMensual(),
            'estados'   => $m->porEstado(),
            'categorias'=> $m->porCategoria(),
            'prioridad' => $m->porPrioridad(),
            'tecnicos'  => $m->cargaTecnicos(),
            'recientes' => $m->recientes(),
            'actividad' => $m->actividad(),
            'alertas'   => $m->alertas(),
            'canales'   => $m->canales(),
            'spark'     => $m->sparkline(),
        ];
        $this->render('dashboard/index', $data, 'Dashboard');
    }
}
