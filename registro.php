
<?php
session_start();

/* RESPUESTA JSON */
function responder($success, $message, $codigo = 200) {
    http_response_code($codigo);
    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* PROCESAR REGISTRO */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        require_once __DIR__ . "/db_conexion.php";

        $contenido = file_get_contents("php://input");
        $datos = json_decode($contenido, true);

        if (!is_array($datos)) {
            responder(false, "Los datos recibidos no son válidos.", 400);
        }

        $username = trim($datos["username"] ?? "");
        $email = trim($datos["email"] ?? "");
        $password = $datos["password"] ?? "";

        /* VALIDAR CAMPOS */
        if ($username === "" || $email === "" || $password === "") {
            responder(false, "Todos los campos son obligatorios.", 400);
        }

        /* VALIDAR USUARIO */
        if (strlen($username) < 3 || strlen($username) > 50) {
            responder(false, "El usuario debe tener entre 3 y 50 caracteres.", 400);
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            responder(false, "El usuario solo puede contener letras, números, puntos, guiones y guiones bajos.", 400);
        }

        /* VALIDAR CORREO */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            responder(false, "Ingresa un correo electrónico válido.", 400);
        }

        /* VALIDAR CONTRASEÑA */
        if (strlen($password) < 8) {
            responder(false, "La contraseña debe tener al menos 8 caracteres.", 400);
        }

        /* CONEXIÓN */
        $conexion = (new Conexion())->getConexion();

        /* COMPROBAR SI YA EXISTE */
        $consulta = $conexion->prepare(
            "SELECT id FROM usuarios
             WHERE username = :username OR email = :email
             LIMIT 1"
        );

        $consulta->execute([
            ":username" => $username,
            ":email" => $email
        ]);

        if ($consulta->fetch(PDO::FETCH_ASSOC)) {
            responder(false, "El nombre de usuario o correo ya está registrado.", 409);
        }

        /* CIFRAR CONTRASEÑA */
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new Exception("No fue posible proteger la contraseña.");
        }

        /* INSERTAR USUARIO */
        $insertar = $conexion->prepare(
            "INSERT INTO usuarios (username, email, password, rol)
             VALUES (:username, :email, :password, 'cliente')"
        );

        $insertar->execute([
            ":username" => $username,
            ":email" => $email,
            ":password" => $passwordHash
        ]);

        responder(
            true,
            "¡Tu cuenta CoffeeTime fue creada correctamente!",
            201
        );

    } catch (PDOException $e) {

        error_log("Error PDO en registro.php: " . $e->getMessage());

        if ($e->getCode() === "23000") {
            responder(false, "El usuario o correo ya está registrado.", 409);
        }

        responder(
            false,
            "Error de base de datos. Revisa la conexión y la tabla usuarios.",
            500
        );

    } catch (Throwable $e) {

        error_log("Error en registro.php: " . $e->getMessage());

        responder(
            false,
            "Error interno al procesar el registro. Revisa el registro de errores de PHP.",
            500
        );
    }
}

/* REDIRECCIÓN SI YA INICIÓ SESIÓN */
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrarse | CoffeeTime</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="style.css">

</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">

    <div class="container">

        <a class="navbar-brand fw-bold fs-4" href="index.php">
            ☕ CoffeeTime
        </a>

        <a href="index.php" class="btn btn-outline-light">
            Volver al inicio
        </a>

    </div>

</nav>

<!-- FORMULARIO -->
<main class="container py-5">

    <div class="row justify-content-center">

        <div class="col-12 col-sm-10 col-md-7 col-lg-5">

            <div class="card border-0 shadow-lg">

                <div class="card-header bg-dark text-white text-center py-4">

                    <h2 class="fw-bold mb-2">
                        ☕ Crear cuenta
                    </h2>

                    <p class="mb-0">
                        Forma parte de CoffeeTime
                    </p>

                </div>

                <div class="card-body p-4">

                    <div
                        id="mensajeRegistro"
                        class="alert d-none"
                        role="alert"
                    ></div>

                    <form id="formRegistro">

                        <!-- USUARIO -->
                        <div class="mb-3">

                            <label for="username" class="form-label fw-semibold">
                                Nombre de usuario
                            </label>

                            <input
                                type="text"
                                id="username"
                                class="form-control"
                                placeholder="Escribe tu usuario"
                                minlength="3"
                                maxlength="50"
                                autocomplete="username"
                                required
                            >

                        </div>

                        <!-- CORREO -->
                        <div class="mb-3">

                            <label for="email" class="form-label fw-semibold">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="email"
                                class="form-control"
                                placeholder="ejemplo@correo.com"
                                maxlength="100"
                                autocomplete="email"
                                required
                            >

                        </div>

                        <!-- CONTRASEÑA -->
                        <div class="mb-3">

                            <label for="password" class="form-label fw-semibold">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                id="password"
                                class="form-control"
                                placeholder="Mínimo 8 caracteres"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                        <!-- CONFIRMAR CONTRASEÑA -->
                        <div class="mb-4">

                            <label for="confirmPassword" class="form-label fw-semibold">
                                Confirmar contraseña
                            </label>

                            <input
                                type="password"
                                id="confirmPassword"
                                class="form-control"
                                placeholder="Repite tu contraseña"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                        <!-- BOTÓN -->
                        <button
                            type="submit"
                            id="btnRegistrar"
                            class="btn btn-warning w-100 fw-bold py-2"
                        >
                            Crear mi cuenta
                        </button>

                    </form>

                    <hr class="my-4">

                    <div class="text-center">

                        <p class="mb-2">
                            ¿Ya tienes una cuenta?
                        </p>

                        <a
                            href="index.php"
                            class="btn btn-outline-dark w-100"
                        >
                            Iniciar sesión
                        </a>

                    </div>

                </div>

            </div>

            <p class="text-center text-muted mt-4">
                Regístrate y disfruta de nuestros productos ☕
            </p>

        </div>

    </div>

</main>

<!-- FOOTER -->
<footer class="bg-dark text-white text-center py-4 mt-4">

    <p class="mb-0">
        © 2026 CoffeeTime - Todos los derechos reservados ☕
    </p>

</footer>

<!-- JAVASCRIPT -->
<script>

const formulario = document.getElementById("formRegistro");
const mensaje = document.getElementById("mensajeRegistro");
const boton = document.getElementById("btnRegistrar");

formulario.addEventListener("submit", async function(event) {

    event.preventDefault();

    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    /* VALIDAR CONTRASEÑAS */
    if (password !== confirmPassword) {

        mensaje.className = "alert alert-danger";
        mensaje.textContent = "Las contraseñas no coinciden.";

        return;
    }

    if (password.length < 8) {

        mensaje.className = "alert alert-danger";
        mensaje.textContent = "La contraseña debe tener al menos 8 caracteres.";

        return;
    }

    boton.disabled = true;
    boton.textContent = "Registrando...";

    mensaje.className = "alert alert-info";
    mensaje.textContent = "Estamos creando tu cuenta...";

    try {

        const respuesta = await fetch("registro.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },

            body: JSON.stringify({
                username: username,
                email: email,
                password: password
            })

        });

        /* LEER RESPUESTA */
        const texto = await respuesta.text();

        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (error) {
            console.error("Respuesta recibida del servidor:", texto);
            throw new Error("El servidor no devolvió JSON válido. Revisa los errores de PHP.");
        }

        if (!respuesta.ok || resultado.success !== true) {
            throw new Error(
                resultado.message || "No se pudo completar el registro."
            );
        }

        mensaje.className = "alert alert-success";
        mensaje.textContent = resultado.message;

        formulario.reset();

        setTimeout(function() {
            window.location.href = "index.php";
        }, 2000);

    } catch (error) {

        console.error(error);

        mensaje.className = "alert alert-danger";
        mensaje.textContent = error.message;

    } finally {

        boton.disabled = false;
        boton.textContent = "Crear mi cuenta";

    }

});

</script>

</body>
</html>
