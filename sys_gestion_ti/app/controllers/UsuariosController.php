<?php
class UsuariosController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'slug' => 'usuarios', 'table' => 'usuarios', 'title' => 'Usuarios y Roles', 'singular' => 'Usuario', 'group' => 'Sistema',
            'subtitle' => 'Cuentas de acceso del personal de soporte.', 'label_field' => 'usuario', 'roles' => ['Administrador'],
            'select' => 'u.*, r.nombre AS rol', 'from' => 'usuarios u JOIN roles r ON r.id = u.rol_id', 'order' => 'u.nombres',
            'search' => ['u.nombres', 'u.apellidos', 'u.usuario', 'u.email', 'u.cargo'],
            'filters' => [
                ['name' => 'rol', 'col' => 'u.rol_id', 'label' => 'Rol', 'options_sql' => 'SELECT id, nombre FROM roles ORDER BY id'],
                ['name' => 'estado', 'col' => 'u.estado', 'label' => 'Estado', 'options' => ['1' => 'Activo', '0' => 'Bloqueado']],
            ],
            'stats' => [
                ['Usuarios', 'SELECT COUNT(*) FROM usuarios', 'bi-people-fill', '#4f46e5'],
                ['Activos', 'SELECT COUNT(*) FROM usuarios WHERE estado=1', 'bi-person-check-fill', '#10b981'],
                ['Técnicos', "SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE r.nombre='Técnico'", 'bi-tools', '#0ea5e9'],
                ['Bloqueados', 'SELECT COUNT(*) FROM usuarios WHERE estado=0', 'bi-person-lock', '#ef4444'],
            ],
            'columns' => [
                ['label' => 'Usuario', 'html' => fn($r) => '<div class="d-flex align-items-center gap-2">' . avatar_html($r['nombres'] . ' ' . $r['apellidos'], $r['avatar_color']) . '<div><b>' . e($r['nombres'] . ' ' . $r['apellidos']) . '</b><div class="tk-sub">@' . e($r['usuario']) . '</div></div></div>'],
                ['label' => 'Correo', 'key' => 'email', 'sub' => 'telefono', 'main' => true],
                ['label' => 'Cargo', 'key' => 'cargo'],
                ['label' => 'Rol', 'html' => fn($r) => pill($r['rol'], ['Administrador' => 'danger', 'Supervisor' => 'soft-primary', 'Técnico' => 'soft-info', 'Recepción' => 'muted'][$r['rol']] ?? 'muted')],
                ['label' => 'Último acceso', 'html' => fn($r) => $r['ultimo_acceso'] ? e(hace($r['ultimo_acceso'])) : '<span class="text-muted small">Nunca</span>'],
                ['label' => 'Estado', 'html' => fn($r) => $r['estado'] ? pill('Activo', 'success') : pill('Bloqueado', 'danger')],
            ],
            'fields' => [
                ['name' => 'nombres', 'label' => 'Nombres', 'type' => 'text', 'required' => true, 'col' => 6, 'section' => 'Datos personales', 'maxlength' => 80],
                ['name' => 'apellidos', 'label' => 'Apellidos', 'type' => 'text', 'required' => true, 'col' => 6, 'maxlength' => 80],
                ['name' => 'cargo', 'label' => 'Cargo', 'type' => 'text', 'col' => 6, 'maxlength' => 80],
                ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'text', 'col' => 4, 'maxlength' => 25],
                ['name' => 'avatar_color', 'label' => 'Color de avatar', 'type' => 'color', 'col' => 2],
                ['name' => 'usuario', 'label' => 'Usuario', 'type' => 'text', 'required' => true, 'col' => 4, 'section' => 'Acceso al sistema', 'maxlength' => 40],
                ['name' => 'email', 'label' => 'Correo', 'type' => 'email', 'required' => true, 'col' => 4],
                ['name' => 'rol_id', 'label' => 'Rol', 'type' => 'select', 'options_sql' => 'SELECT id, nombre FROM roles ORDER BY id', 'required' => true, 'col' => 4],
                ['name' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'required' => true, 'col' => 6, 'help' => 'Mínimo 6 caracteres. En edición, déjala vacía para no cambiarla.'],
                ['name' => 'estado', 'label' => 'Cuenta activa', 'type' => 'switch', 'col' => 6],
            ],
        ];
    }

    protected function canDelete(int $id): ?string
    {
        return $id === (int)Auth::user()['id'] ? 'No puedes eliminar tu propia cuenta.' : null;
    }
}
