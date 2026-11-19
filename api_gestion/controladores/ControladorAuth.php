<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositorios/Conexion.php';

class ControladorAuth
{
    public function login(array $body): array
    {
        $username = trim((string)($body['username'] ?? ''));
        $password = trim((string)($body['password'] ?? ''));

        if ($username === '' || $password === '') {
            return ['codigo' => 422, 'cuerpo' => ['estado' => 422, 'mensaje' => 'Usuario y contraseña son obligatorios.']];
        }

        try {
            $pdo = Conexion::obtener();
            // Buscamos el usuario activo
            $stmt = $pdo->prepare('SELECT id, password FROM usuario WHERE username = :username AND activo = 1');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificamos la contraseña (soporta hash BCRYPT o texto plano para pruebas)
            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                // Generamos un token simple (en un sistema real usarías JWT)
                $token = bin2hex(random_bytes(32));
                
                return ['codigo' => 200, 'cuerpo' => [
                    'estado' => 200,
                    'mensaje' => 'Login exitoso',
                    'token' => $token,
                    'username' => $username
                ]];
            }

            return ['codigo' => 401, 'cuerpo' => ['estado' => 401, 'mensaje' => 'Credenciales incorrectas.']];
        } catch (PDOException $e) {
            return ['codigo' => 500, 'cuerpo' => ['estado' => 500, 'mensaje' => 'Error en la base de datos.']];
        }
    }
}