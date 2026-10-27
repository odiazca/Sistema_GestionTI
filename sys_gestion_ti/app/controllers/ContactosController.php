<?php
require_once ROOT_PATH . '/core/CrmBase.php';

/** Contactos de las empresas cliente (varios por cliente, uno principal) */
class ContactosController extends CrmBase
{
    private function sincronizar(int $cid): void
    {
        $p = $this->one('SELECT nombre, email, telefono FROM crm_contactos WHERE cliente_id=? AND principal=1 LIMIT 1', [$cid]);
        if ($p) $this->run('UPDATE clientes SET contacto=?, email=COALESCE(?, email), telefono=COALESCE(?, telefono) WHERE id=?', [$p['nombre'], $p['email'], $p['telefono'], $cid]);
    }

    public function guardar(): void
    {
        $this->guard(); $cid = (int)($_POST['cliente_id'] ?? 0); $back = "clientes/ver/$cid";
        $this->requirePost($back); $id = (int)($_POST['id'] ?? 0);
        $nombre = $this->str('nombre', 100); $email = $this->str('email', 120);
        if (!$this->val('SELECT COUNT(*) FROM clientes WHERE id=?', [$cid])) $this->redirect('clientes');
        if (!$nombre) { $this->flash('danger', 'Escribe el nombre del contacto.'); $this->redirect($back . '?tab=contactos'); }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->flash('danger', 'El correo del contacto no es válido.'); $this->redirect($back . '?tab=contactos'); }
        $principal = !empty($_POST['principal']) || !(int)$this->val('SELECT COUNT(*) FROM crm_contactos WHERE cliente_id=?', [$cid] ) ? 1 : 0;
        if ($principal) $this->run('UPDATE crm_contactos SET principal=0 WHERE cliente_id=?', [$cid]);
        if ($id) $this->run('UPDATE crm_contactos SET nombre=?, cargo=?, email=?, telefono=?, notas=?, principal=? WHERE id=? AND cliente_id=?', [$nombre, $this->str('cargo', 80), $email, $this->str('telefono', 25), $this->str('notas', 255), $principal, $id, $cid]);
        else $this->run('INSERT INTO crm_contactos(cliente_id,nombre,cargo,email,telefono,notas,principal) VALUES(?,?,?,?,?,?,?)', [$cid, $nombre, $this->str('cargo', 80), $email, $this->str('telefono', 25), $this->str('notas', 255), $principal]);
        $this->sincronizar($cid); $this->flash('success', 'Contacto guardado.'); $this->redirect($back . '?tab=contactos');
    }

    public function principal($id = 0): void
    {
        $this->guard(); $c = $this->one('SELECT * FROM crm_contactos WHERE id=?', [(int)$id]); if (!$c) $this->redirect('clientes');
        $this->requirePost("clientes/ver/{$c['cliente_id']}");
        $this->run('UPDATE crm_contactos SET principal=0 WHERE cliente_id=?', [$c['cliente_id']]); $this->run('UPDATE crm_contactos SET principal=1 WHERE id=?', [$c['id']]);
        $this->sincronizar((int)$c['cliente_id']); $this->flash('success', 'Contacto principal actualizado.'); $this->redirect("clientes/ver/{$c['cliente_id']}?tab=contactos");
    }

    public function eliminar($id = 0): void
    {
        $this->guard(); $c = $this->one('SELECT * FROM crm_contactos WHERE id=?', [(int)$id]); if (!$c) $this->redirect('clientes');
        $this->requirePost("clientes/ver/{$c['cliente_id']}");
        $this->run('UPDATE crm_oportunidades SET contacto_id=NULL WHERE contacto_id=?', [$c['id']]); $this->run('UPDATE crm_cotizaciones SET contacto_id=NULL WHERE contacto_id=?', [$c['id']]);
        $this->run('DELETE FROM crm_contactos WHERE id=?', [$c['id']]);
        if ($c['principal']) { $this->run('UPDATE crm_contactos SET principal=1 WHERE cliente_id=? ORDER BY id LIMIT 1', [$c['cliente_id']]); $this->sincronizar((int)$c['cliente_id']); }
        $this->flash('success', 'Contacto eliminado.'); $this->redirect("clientes/ver/{$c['cliente_id']}?tab=contactos");
    }

    /** Crea (o restablece) el acceso al portal del cliente para un contacto */
    public function acceso($id = 0): void
    {
        $this->requireRole(['Administrador', 'Supervisor']); $c = $this->one('SELECT * FROM crm_contactos WHERE id=?', [(int)$id]); if (!$c) $this->redirect('clientes');
        $back = 'clientes/ver/' . $c['cliente_id'] . '?tab=contactos'; $this->requirePost($back);
        if (!$c['email'] || !filter_var($c['email'], FILTER_VALIDATE_EMAIL)) { $this->flash('danger', 'El contacto necesita un correo válido para crear su acceso.'); $this->redirect($back); }
        $rol = (int)$this->val("SELECT id FROM roles WHERE nombre='Cliente'"); if (!$rol) { $this->flash('danger', 'Ejecuta migrar.php para habilitar el portal.'); $this->redirect($back); }
        $pass = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 5) . random_int(10, 99) . strtoupper(substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 2)) . '*';
        $u = $this->one('SELECT id, usuario, cliente_id FROM usuarios WHERE email=?', [$c['email']]);
        if ($u && (int)$u['cliente_id'] !== (int)$c['cliente_id']) { $this->flash('danger', 'Ese correo ya pertenece a otra cuenta del sistema.'); $this->redirect($back); }
        if ($u) { $this->run('UPDATE usuarios SET password=?, estado=1 WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]); $login = $u['usuario']; }
        else {
            $base = preg_replace('/[^a-z0-9._]/', '', strtolower(strstr($c['email'], '@', true))) ?: 'cliente'; $login = $base; $n = 1;
            while ((int)$this->val('SELECT COUNT(*) FROM usuarios WHERE usuario=?', [$login])) $login = $base . (++$n);
            $p = preg_split('/\s+(?=\S+$)/', trim($c['nombre']), 2);
            $this->run('INSERT INTO usuarios(rol_id,nombres,apellidos,usuario,email,password,cargo,avatar_color,cliente_id) VALUES(?,?,?,?,?,?,?,?,?)', [$rol, $p[0], $p[1] ?? '', $login, $c['email'], password_hash($pass, PASSWORD_DEFAULT), $c['cargo'] ?: 'Contacto', '#0ea5e9', $c['cliente_id']]);
        }
        try { require_once ROOT_PATH . '/core/Notifier.php'; Notifier::credenciales((int)$c['cliente_id'], $c['email'], $c['nombre'], $login, $pass); } catch (Throwable $e) {}
        $this->flash('success', "Acceso listo. Usuario: $login · Contraseña temporal: $pass (cópiala ahora; no se vuelve a mostrar).");
        $this->redirect($back);
    }
}
