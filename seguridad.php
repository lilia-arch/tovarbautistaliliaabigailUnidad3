
<?php
/**
 * ==========================================
 * COFFEETIME | SISTEMA DE SEGURIDAD
 * Protección contra ataques XSS
 * ==========================================
 */

/**
 * Escapa datos para evitar que se ejecuten
 * etiquetas HTML o código JavaScript malicioso.
 */
function limpiar($dato) {

    return htmlspecialchars(
        (string) $dato,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

}

/**
 * Limpia los datos de entrada de formularios.
 */
function limpiarEntrada($dato) {

    if (is_array($dato)) {
        return array_map('limpiarEntrada', $dato);
    }

    return trim((string) $dato);

}

/**
 * Comprueba que una cadena no esté vacía.
 */
function campoVacio($dato) {

    return !isset($dato) || trim((string) $dato) === '';

}

/**
 * Genera un token CSRF para proteger formularios.
 */
function generarTokenCSRF() {

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_csrf'];

}

/**
 * Verifica el token CSRF recibido.
 */
function verificarTokenCSRF($token) {

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (
        empty($_SESSION['token_csrf']) ||
        !is_string($token) ||
        !hash_equals($_SESSION['token_csrf'], $token)
    ) {
        return false;
    }

    return true;

}
?>
