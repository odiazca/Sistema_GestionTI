<?php
class Usuario
{
    public function buscarLogin(string $login): ?array
    {
        $st = Database::conn()->prepare(
            'SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id
             WHERE (u.usuario = :l1 OR u.email = :l2) AND u.estado = 1 LIMIT 1');
        $st->execute([':l1' => $login, ':l2' => $login]);
        return $st->fetch() ?: null;
    }
    public function registrarAcceso(int $id): void
    {
        Database::conn()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')->execute([$id]);
    }
}
