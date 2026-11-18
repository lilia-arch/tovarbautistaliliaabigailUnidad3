
<?php

require_once __DIR__ . "/seguridad.php";

session_start();

/* =====================================
   PROTECCIÓN DEL PANEL ADMINISTRADOR
===================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit;
}

if (($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: index.html");
    exit;
}

/* =====================================
   TOKEN CSRF
===================================== */

$tokenCSRF = generarTokenCSRF();

/* =====================================
   CONEXIÓN A LA BASE DE DATOS
===================================== */

require_once __DIR__ . "/db_conexion.php";

$conexion = (new Conexion())->getConexion();

$mensaje = "";
$tipoMensaje = "";

/* =====================================
   VALIDAR URL DE IMAGEN
===================================== */

function validarImagen($imagen) {

    if ($imagen === "") {
        return true;
    }

    if (!filter_var($imagen, FILTER_VALIDATE_URL)) {
        return false;
    }

    $esquema = strtolower(
        parse_url($imagen, PHP_URL_SCHEME) ?? ""
    );

    return in_array($esquema, ["http", "https"], true);
}

/* =====================================
   PROCESAR FORMULARIOS
===================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* VALIDAR TOKEN CSRF */

    if (!verificarTokenCSRF($_POST["token_csrf"] ?? null)) {
        http_response_code(403);
        exit("Solicitud no válida. Recarga la página e inténtalo nuevamente.");
    }

    /* =====================================
       AGREGAR PRODUCTO
    ===================================== */

    if (isset($_POST["agregar"])) {

        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = $_POST["precio"] ?? "";
        $stock = $_POST["stock"] ?? "";
        $imagen = trim($_POST["imagen"] ?? "");

        if (
            $nombre === "" ||
            $descripcion === "" ||
            strlen($nombre) > 150 ||
            !is_numeric($precio) ||
            !is_finite((float)$precio) ||
            (float)$precio < 0 ||
            filter_var($stock, FILTER_VALIDATE_INT) === false ||
            (int)$stock < 0 ||
            !validarImagen($imagen)
        ) {

            $mensaje = "Verifica los datos del producto.";
            $tipoMensaje = "danger";

        } else {

            try {

                $consulta = $conexion->prepare(
                    "INSERT INTO productos
                    (nombre, descripcion, precio, stock, imagen)
                    VALUES
                    (:nombre, :descripcion, :precio, :stock, :imagen)"
                );

                $consulta->execute([
                    ":nombre" => $nombre,
                    ":descripcion" => $descripcion,
                    ":precio" => $precio,
                    ":stock" => $stock,
                    ":imagen" => $imagen
                ]);

                header("Location: admin.php?mensaje=agregado");
                exit;

            } catch (PDOException $e) {

                error_log("Error al agregar producto: " . $e->getMessage());

                $mensaje = "No se pudo agregar el producto.";
                $tipoMensaje = "danger";
            }
        }
    }

    /* =====================================
       EDITAR PRODUCTO
    ===================================== */

    elseif (isset($_POST["editar"])) {

        $id = $_POST["id"] ?? "";
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = $_POST["precio"] ?? "";
        $stock = $_POST["stock"] ?? "";
        $imagen = trim($_POST["imagen"] ?? "");

        if (
            filter_var($id, FILTER_VALIDATE_INT) === false ||
            (int)$id <= 0 ||
            $nombre === "" ||
            $descripcion === "" ||
            strlen($nombre) > 150 ||
            !is_numeric($precio) ||
            !is_finite((float)$precio) ||
            (float)$precio < 0 ||
            filter_var($stock, FILTER_VALIDATE_INT) === false ||
            (int)$stock < 0 ||
            !validarImagen($imagen)
        ) {

            $mensaje = "Los datos para editar no son válidos.";
            $tipoMensaje = "danger";

        } else {

            try {

                $consulta = $conexion->prepare(
                    "UPDATE productos
                     SET nombre = :nombre,
                         descripcion = :descripcion,
                         precio = :precio,
                         stock = :stock,
                         imagen = :imagen
                     WHERE id = :id"
                );

                $consulta->execute([
                    ":nombre" => $nombre,
                    ":descripcion" => $descripcion,
                    ":precio" => $precio,
                    ":stock" => $stock,
                    ":imagen" => $imagen,
                    ":id" => $id
                ]);

                header("Location: admin.php?mensaje=editado");
                exit;

            } catch (PDOException $e) {

                error_log("Error al editar producto: " . $e->getMessage());

                $mensaje = "No se pudo actualizar el producto.";
                $tipoMensaje = "danger";
            }
        }
    }

    /* =====================================
       ELIMINAR PRODUCTO
    ===================================== */

    elseif (isset($_POST["eliminar"])) {

        $id = $_POST["id"] ?? "";

        if (
            filter_var($id, FILTER_VALIDATE_INT) === false ||
            (int)$id <= 0
        ) {

            $mensaje = "Identificador de producto no válido.";
            $tipoMensaje = "danger";

        } else {

            try {

                $consulta = $conexion->prepare(
                    "DELETE FROM productos WHERE id = :id"
                );

                $consulta->execute([
                    ":id" => $id
                ]);

                header("Location: admin.php?mensaje=eliminado");
                exit;

            } catch (PDOException $e) {

                error_log("Error al eliminar producto: " . $e->getMessage());

                $mensaje = "No se pudo eliminar el producto. Puede estar relacionado con un pedido.";
                $tipoMensaje = "danger";
            }
        }
    }
}

/* =====================================
   MENSAJES
===================================== */

if (isset($_GET["mensaje"])) {

    $mensajes = [
        "agregado" => "Producto agregado correctamente.",
        "editado" => "Producto actualizado correctamente.",
        "eliminado" => "Producto eliminado correctamente."
    ];

    if (isset($mensajes[$_GET["mensaje"]])) {
        $mensaje = $mensajes[$_GET["mensaje"]];
        $tipoMensaje = "success";
    }
}

/* =====================================
   CONSULTAR PRODUCTOS
===================================== */

try {

    $consulta = $conexion->query(
        "SELECT id, nombre, descripcion, precio, stock, imagen
         FROM productos
         ORDER BY id DESC"
    );

    $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log("Error al consultar productos: " . $e->getMessage());

    $productos = [];
    $mensaje = "No se pudieron cargar los productos. Revisa la tabla productos.";
    $tipoMensaje = "danger";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel Administrador | CoffeeTime</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>

        body {
            background: #f5f1eb;
        }

        .navbar {
            background: #38251b;
        }

        .admin-header {
            background: linear-gradient(135deg, #38251b, #795548);
            color: white;
            padding: 35px;
            border-radius: 15px;
        }

        .card {
            border: none;
            border-radius: 15px;
        }

        .btn-coffee {
            background: #795548;
            color: white;
        }

        .btn-coffee:hover {
            background: #5d4037;
            color: white;
        }

        .product-image {
            width: 65px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
        }

        .table th {
            background: #38251b;
            color: white;
        }

    </style>

</head>

<body>

<!-- =====================================
     NAVBAR
===================================== -->

<nav class="navbar navbar-dark shadow">

    <div class="container">

        <a class="navbar-brand fw-bold" href="index.html">
            ☕ CoffeeTime
        </a>

        <div class="d-flex gap-2">

            <a href="index.html" class="btn btn-outline-light btn-sm">
                Cerrar sesión
            </a>

        </div>

    </div>

</nav>


<div class="container py-5">

    <!-- =====================================
         ENCABEZADO
    ===================================== -->

    <div class="admin-header mb-4">

        <h1 class="fw-bold">
            Panel de administración
        </h1>

        <p class="mb-0">
            Bienvenido, <?= limpiar($_SESSION["username"] ?? "Administrador") ?>.
            Administra los productos de CoffeeTime.
        </p>

    </div>


    <!-- =====================================
         MENSAJES
    ===================================== -->

    <?php if ($mensaje !== ""): ?>

        <div class="alert alert-<?= limpiar($tipoMensaje) ?> alert-dismissible fade show">

            <?= limpiar($mensaje) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Cerrar"></button>

        </div>

    <?php endif; ?>


    <!-- =====================================
         RESUMEN
    ===================================== -->

    <div class="row mb-4">

        <div class="col-md-4">

            <div class="card shadow-sm p-4">

                <h6 class="text-muted">
                    Productos registrados
                </h6>

                <h2 class="fw-bold">
                    <?= count($productos) ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- =====================================
         FORMULARIO AGREGAR PRODUCTO
    ===================================== -->

    <div class="card shadow-sm mb-5">

        <div class="card-header bg-white p-3">

            <h4 class="mb-0">
                ➕ Agregar nuevo producto
            </h4>

        </div>

        <div class="card-body p-4">

            <form method="POST" action="admin.php">

                <input type="hidden"
                       name="token_csrf"
                       value="<?= limpiar($tokenCSRF) ?>">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nombre del producto
                        </label>

                        <input type="text"
                               name="nombre"
                               class="form-control"
                               maxlength="150"
                               required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Precio
                        </label>

                        <input type="number"
                               name="precio"
                               class="form-control"
                               min="0"
                               step="0.01"
                               required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Stock disponible
                        </label>

                        <input type="number"
                               name="stock"
                               class="form-control"
                               min="0"
                               step="1"
                               required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            URL de la imagen
                        </label>

                        <input type="url"
                               name="imagen"
                               class="form-control"
                               placeholder="https://ejemplo.com/imagen.jpg">

                    </div>


                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Descripción
                        </label>

                        <textarea name="descripcion"
                                  class="form-control"
                                  rows="3"
                                  required></textarea>

                    </div>

                </div>


                <button type="submit"
                        name="agregar"
                        class="btn btn-coffee">

                    Agregar producto

                </button>

            </form>

        </div>

    </div>


    <!-- =====================================
         LISTADO DE PRODUCTOS
    ===================================== -->

    <div class="card shadow-sm">

        <div class="card-header bg-white p-3">

            <h4 class="mb-0">
                ☕ Productos registrados
            </h4>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Descripción</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (count($productos) > 0): ?>

                        <?php foreach ($productos as $producto): ?>

                            <tr>

                                <td>
                                    <?= (int)$producto["id"] ?>
                                </td>


                                <td>

                                    <?php

                                    $imagen = trim($producto["imagen"] ?? "");

                                    $imagenValida = validarImagen($imagen);

                                    ?>

                                    <?php if ($imagenValida && $imagen !== ""): ?>

                                        <img src="<?= limpiar($imagen) ?>"
                                             class="product-image"
                                             alt="<?= limpiar($producto["nombre"]) ?>"
                                             loading="lazy">

                                    <?php else: ?>

                                        Sin imagen

                                    <?php endif; ?>

                                </td>


                                <td>
                                    <?= limpiar($producto["nombre"]) ?>
                                </td>


                                <td>
                                    $<?= number_format((float)$producto["precio"], 2) ?>
                                </td>


                                <td>
                                    <?= (int)$producto["stock"] ?>
                                </td>


                                <td>
                                    <?= limpiar($producto["descripcion"]) ?>
                                </td>


                                <td>

                                    <div class="d-flex gap-2">

                                        <button type="button"
                                                class="btn btn-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editar<?= (int)$producto["id"] ?>">

                                            Editar

                                        </button>


                                        <form method="POST"
                                              action="admin.php"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">

                                            <input type="hidden"
                                                   name="token_csrf"
                                                   value="<?= limpiar($tokenCSRF) ?>">

                                            <input type="hidden"
                                                   name="id"
                                                   value="<?= (int)$producto["id"] ?>">

                                            <button type="submit"
                                                    name="eliminar"
                                                    class="btn btn-danger btn-sm">

                                                Eliminar

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="text-center py-4">

                                No hay productos registrados.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- =====================================
         MODALES PARA EDITAR PRODUCTOS
    ===================================== -->

    <?php foreach ($productos as $producto): ?>

        <div class="modal fade"
             id="editar<?= (int)$producto["id"] ?>"
             tabindex="-1"
             aria-hidden="true">

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Editar producto
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Cerrar"></button>

                    </div>


                    <div class="modal-body">

                        <form method="POST" action="admin.php">

                            <input type="hidden"
                                   name="token_csrf"
                                   value="<?= limpiar($tokenCSRF) ?>">

                            <input type="hidden"
                                   name="id"
                                   value="<?= (int)$producto["id"] ?>">


                            <div class="mb-3">

                                <label class="form-label">
                                    Nombre
                                </label>

                                <input type="text"
                                       name="nombre"
                                       class="form-control"
                                       maxlength="150"
                                       value="<?= limpiar($producto["nombre"]) ?>"
                                       required>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Precio
                                </label>

                                <input type="number"
                                       name="precio"
                                       class="form-control"
                                       min="0"
                                       step="0.01"
                                       value="<?= limpiar($producto["precio"]) ?>"
                                       required>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Stock
                                </label>

                                <input type="number"
                                       name="stock"
                                       class="form-control"
                                       min="0"
                                       step="1"
                                       value="<?= (int)$producto["stock"] ?>"
                                       required>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Imagen
                                </label>

                                <input type="url"
                                       name="imagen"
                                       class="form-control"
                                       value="<?= limpiar($producto["imagen"] ?? "") ?>">

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Descripción
                                </label>

                                <textarea name="descripcion"
                                          class="form-control"
                                          rows="3"
                                          required><?= limpiar($producto["descripcion"]) ?></textarea>

                            </div>


                            <button type="submit"
                                    name="editar"
                                    class="btn btn-coffee w-100">

                                Guardar cambios

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>


<!-- =====================================
     BOOTSTRAP
===================================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
