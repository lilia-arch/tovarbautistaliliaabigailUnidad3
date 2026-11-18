
<?php

ob_start();

ini_set("display_errors", "0");
ini_set("log_errors", "1");

header("Content-Type: application/json; charset=UTF-8");

/* =====================================
   RESPUESTA JSON
===================================== */

function responder($success, $message, $codigo = 200, $datos = []) {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($codigo);

    echo json_encode(
        array_merge([
            "success" => $success,
            "message" => $message
        ], $datos),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/* =====================================
   VALIDAR MÉTODO
===================================== */

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    responder(false, "Método no permitido.", 405);
}

/* =====================================
   CONSULTAR PRODUCTOS
===================================== */

try {

    require_once __DIR__ . "/db_conexion.php";

    $conexion = (new Conexion())->getConexion();

    $consulta = $conexion->prepare(
        "SELECT id, nombre, descripcion, precio, stock, imagen
         FROM productos
         ORDER BY id DESC"
    );

    $consulta->execute();

    $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    /* =====================================
       VALIDAR Y NORMALIZAR DATOS
    ===================================== */

    foreach ($productos as &$producto) {

        $producto["id"] = (int) $producto["id"];

        $producto["nombre"] = (string) $producto["nombre"];

        $producto["descripcion"] = (string) ($producto["descripcion"] ?? "");

        $producto["precio"] = (float) $producto["precio"];

        $producto["stock"] = (int) $producto["stock"];

        $producto["imagen"] = (string) ($producto["imagen"] ?? "");

    }

    unset($producto);

    /* =====================================
       RESPUESTA
    ===================================== */

    responder(
        true,
        "Productos obtenidos correctamente.",
        200,
        [
            "productos" => $productos
        ]
    );

} catch (Throwable $e) {

    error_log("Error en api_productos.php: " . $e->getMessage());

    responder(
        false,
        "No se pudieron obtener los productos. Revisa la conexión y la tabla productos.",
        500
    );

}
