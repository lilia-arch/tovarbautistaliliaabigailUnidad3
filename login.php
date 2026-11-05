
<?php

session_start();

/* RESPUESTA JSON */
function responder($success, $message, $codigo = 200, $datos = []) {

    http_response_code($codigo);
    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $datos), JSON_UNESCAPED_UNICODE);

    exit;
}

/* PROCESAR INICIO DE SESIÓN */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método de solicitud no permitido.", 405);
}

try {

    require_once __DIR__ . "/db_conexion.php";

    /* RECIBIR DATOS */
    $contenido = file_get_contents("php://input");
    $datos = json_decode($contenido, true);

    if (!is_array($datos)) {
        responder(false, "Los datos recibidos no son válidos.", 400);
    }

    $username = trim($datos["username"] ?? "");
    $password = $datos["password"] ?? "";

    /* VALIDAR CAMPOS */
    if ($username === "" || $password === "") {
        responder(false, "Ingresa tu usuario y contraseña.", 400);
    }

    /* CONECTAR CON MYSQL */
    $conexion = (new Conexion())->getConexion();

    /* BUSCAR USUARIO */
    $consulta = $conexion->prepare(
        "SELECT id, username, email, password, rol
         FROM usuarios
         WHERE username = :username
         LIMIT 1"
    );

    $consulta->execute([
        ":username" => $username
    ]);

    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    /* VERIFICAR USUARIO Y CONTRASEÑA */
    if (!$usuario || !password_verify($password, $usuario["password"])) {
        responder(false, "Usuario o contraseña incorrectos.", 401);
    }

    /* REGENERAR ID DE SESIÓN */
    if (!session_regenerate_id(true)) {
        throw new Exception("No fue posible iniciar la sesión.");
    }

    /* GUARDAR DATOS EN SESIÓN */
    $_SESSION["user_id"] = $usuario["id"];
    $_SESSION["username"] = $usuario["username"];
    $_SESSION["rol"] = $usuario["rol"];

    /* RESPUESTA EXITOSA */
    responder(true, "¡Bienvenido a CoffeeTime!", 200, [
        "user" => [
            "id" => $usuario["id"],
            "username" => $usuario["username"],
            "email" => $usuario["email"],
            "rol" => $usuario["rol"]
        ]
    ]);

} catch (PDOException $e) {

    error_log("Error PDO en login.php: " . $e->getMessage());

    responder(false, "No se pudo conectar con la base de datos.", 500);

} catch (Throwable $e) {

    error_log("Error en login.php: " . $e->getMessage());

    responder(false, "Ocurrió un error al iniciar sesión.", 500);
}
