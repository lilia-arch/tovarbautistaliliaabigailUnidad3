
<?php
require_once __DIR__ . "/seguridad.php";

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

function responder($success, $message, $codigo = 200, $datos = []) {
    http_response_code($codigo);

    echo json_encode(
        array_merge([
            "success" => $success,
            "message" => $message
        ], $datos),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/* =====================================
   VERIFICAR SESIÓN
===================================== */

if (!isset($_SESSION["user_id"])) {
    responder(false, "Debes iniciar sesión.", 401);
}

if (($_SESSION["rol"] ?? "") !== "cliente") {
    responder(false, "No tienes permiso para realizar compras.", 403);
}

/* =====================================
   VERIFICAR MÉTODO
===================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método no permitido.", 405);
}

/* =====================================
   LEER DATOS JSON
===================================== */

$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    responder(false, "Los datos enviados no son válidos.", 400);
}

/* =====================================
   VERIFICAR TOKEN CSRF
===================================== */

if (!verificarTokenCSRF($datos["token_csrf"] ?? null)) {
    responder(false, "La sesión de seguridad expiró. Recarga la página e intenta nuevamente.", 403);
}

/* =====================================
   VALIDAR PRODUCTOS
===================================== */

$productos = $datos["productos"] ?? [];

if (!is_array($productos) || count($productos) === 0) {
    responder(false, "El carrito está vacío.", 400);
}

if (count($productos) > 50) {
    responder(false, "Hay demasiados productos en el pedido.", 400);
}

$ids = [];

foreach ($productos as $producto) {
    $id = filter_var($producto["id"] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($producto["cantidad"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1 || !$cantidad || $cantidad < 1 || $cantidad > 100) {
        responder(false, "Hay un producto o una cantidad inválida.", 400);
    }

    if (in_array($id, $ids, true)) {
        responder(false, "El carrito contiene productos repetidos.", 400);
    }

    $ids[] = $id;
}

/* =====================================
   CONEXIÓN A BASE DE DATOS
===================================== */

require_once __DIR__ . "/db_conexion.php";

$conexion = new Conexion();
$pdo = $conexion->getConexion();

if (!$pdo instanceof PDO) {
    responder(false, "No se pudo conectar con la base de datos.", 500);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {

    $pdo->beginTransaction();

    $total = 0;
    $detalle = [];

    /* =====================================
       CONSULTAR Y BLOQUEAR INVENTARIO
    ===================================== */

    $consultaProducto = $pdo->prepare("
        SELECT id, nombre, precio, stock
        FROM productos
        WHERE id = ?
        FOR UPDATE
    ");

    foreach ($productos as $producto) {

        $id = (int) $producto["id"];
        $cantidad = (int) $producto["cantidad"];

        $consultaProducto->execute([$id]);

        $productoBD = $consultaProducto->fetch(PDO::FETCH_ASSOC);

        if (!$productoBD) {
            throw new RuntimeException(
                "Uno de los productos ya no está disponible."
            );
        }

        $stock = (int) $productoBD["stock"];
        $precio = (float) $productoBD["precio"];

        if ($cantidad > $stock) {
            throw new RuntimeException(
                "No hay suficientes existencias de " .
                $productoBD["nombre"] . "."
            );
        }

        if ($precio < 0 || !is_finite($precio)) {
            throw new RuntimeException("El precio de un producto no es válido.");
        }

        $subtotal = round($precio * $cantidad, 2);
        $total += $subtotal;

        $detalle[] = [
            "id" => $id,
            "nombre" => $productoBD["nombre"],
            "precio" => $precio,
            "cantidad" => $cantidad,
            "subtotal" => $subtotal
        ];
    }

    $total = round($total, 2);

    /* =====================================
       GUARDAR PEDIDO
    ===================================== */

    $usuarioId = (int) $_SESSION["user_id"];

    $insertarPedido = $pdo->prepare("
        INSERT INTO pedidos (usuario_id, total, estado, fecha)
        VALUES (?, ?, ?, NOW())
    ");

    $insertarPedido->execute([
        $usuarioId,
        $total,
        "Pendiente"
    ]);

    $pedidoId = (int) $pdo->lastInsertId();

    /* =====================================
       GUARDAR DETALLES Y DESCONTAR STOCK
    ===================================== */

    $insertarDetalle = $pdo->prepare("
        INSERT INTO detalle_pedido
        (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $actualizarStock = $pdo->prepare("
        UPDATE productos
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");

    foreach ($detalle as $producto) {

        $insertarDetalle->execute([
            $pedidoId,
            $producto["id"],
            $producto["nombre"],
            $producto["precio"],
            $producto["cantidad"],
            $producto["subtotal"]
        ]);

        $actualizarStock->execute([
            $producto["cantidad"],
            $producto["id"],
            $producto["cantidad"]
        ]);

        if ($actualizarStock->rowCount() !== 1) {
            throw new RuntimeException(
                "No se pudo actualizar el inventario de " .
                $producto["nombre"] . "."
            );
        }
    }

    /* =====================================
       CONFIRMAR COMPRA
    ===================================== */

    $pdo->commit();

    responder(true, "Compra realizada correctamente.", 200, [
        "pedido_id" => $pedidoId,
        "total" => $total,
        "fecha" => date("Y-m-d H:i:s"),
        "productos" => $detalle
    ]);

} catch (RuntimeException $error) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    responder(false, $error->getMessage(), 400);

} catch (Throwable $error) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error en guardar_pedido.php: " . $error->getMessage());

    responder(false, "Ocurrió un error al procesar el pedido. Revisa la conexión y las tablas de la base de datos.", 500);
}
