
<?php
session_start();

/* CERRAR SESIÓN */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $datos = json_decode(file_get_contents("php://input"), true);

    if (is_array($datos) && ($datos["action"] ?? "") === "logout") {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => true,
            "message" => "Sesión cerrada correctamente."
        ]);
        exit;
    }
}

/* DATOS DE SESIÓN */
$sesionIniciada = isset($_SESSION["user_id"]);
$username = $_SESSION["username"] ?? "";
$rol = $_SESSION["rol"] ?? "";
$esAdmin = $sesionIniciada && $rol === "admin";
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CoffeeTime | Cafetería</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="style.css">

    <style>

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: #f8f5f0;
            color: #33251d;
        }

        .navbar {
            background-color: #38251c !important;
        }

        .navbar-brand {
            color: #ffc857 !important;
        }

        .nav-link {
            color: white !important;
        }

        .nav-link:hover {
            color: #ffc857 !important;
        }

        .hero-img {
            height: 430px;
            object-fit: cover;
            filter: brightness(55%);
        }

        .carousel-caption {
            bottom: 25%;
        }

        .carousel-caption h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: bold;
        }

        .section-title {
            color: #5c3825;
            font-weight: bold;
        }

        .product-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: transform .25s, box-shadow .25s;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(0,0,0,.15);
        }

        .product-img {
            height: 210px;
            width: 100%;
            object-fit: cover;
            background: #eee;
        }

        .btn-coffee {
            background-color: #70452d;
            color: white;
            border: none;
        }

        .btn-coffee:hover {
            background-color: #4d2e1d;
            color: white;
        }

        .category-btn {
            border-radius: 25px;
            margin: 4px;
        }

        .cart-panel {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,.08);
        }

        .service-card {
            border: none;
            border-radius: 15px;
            height: 100%;
            transition: transform .2s;
        }

        .service-card:hover {
            transform: translateY(-5px);
        }

        .service-icon {
            font-size: 40px;
            color: #9a623b;
        }

        .about-section {
            background-color: #efe3d5;
        }

        footer {
            background-color: #38251c;
        }

        footer a {
            color: #ffc857;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        #mensajeCatalogo {
            display: none;
        }

        .user-badge {
            color: #ffc857;
        }

        @media (max-width: 768px) {

            .hero-img {
                height: 320px;
            }

            .carousel-caption {
                bottom: 12%;
            }

            .carousel-caption p {
                font-size: 14px;
            }

        }

    </style>

</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow">

    <div class="container">

        <a class="navbar-brand fw-bold fs-4" href="#inicio">
            ☕ CoffeeTime
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menuPrincipal"
            aria-controls="menuPrincipal"
            aria-expanded="false"
            aria-label="Abrir menú"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link" href="#inicio">Inicio</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#catalogo">Menú</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#nosotros">Nosotros</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#servicios">Servicios</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#contacto">Contacto</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#carrito">
                        <i class="bi bi-cart3"></i>
                        Carrito
                        (<span id="contadorCarrito">0</span>)
                    </a>
                </li>

                <?php if ($sesionIniciada): ?>

                    <li class="nav-item ms-lg-2">
                        <span class="nav-link user-badge">
                            <i class="bi bi-person-circle"></i>
                            Hola, <?= htmlspecialchars($username, ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </li>

                    <?php if ($esAdmin): ?>

                        <li class="nav-item ms-lg-2">
                            <a href="admin.php" class="btn btn-warning btn-sm fw-bold">
                                <i class="bi bi-gear"></i>
                                Administrar
                            </a>
                        </li>

                    <?php endif; ?>

                    <li class="nav-item ms-lg-2">
                        <button
                            type="button"
                            class="btn btn-outline-light btn-sm"
                            id="btnLogout"
                        >
                            Cerrar sesión
                        </button>
                    </li>

                <?php else: ?>

                    <li class="nav-item ms-lg-2">
                        <button
                            type="button"
                            class="btn btn-outline-light btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#authModal"
                        >
                            Iniciar sesión
                        </button>
                    </li>

                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a href="registro.php" class="btn btn-warning btn-sm fw-bold">
                            Registrarse
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>


<!-- ================= INICIO / CARRUSEL ================= -->

<section id="inicio">

    <div
        id="carruselCoffee"
        class="carousel slide"
        data-bs-ride="carousel"
    >

        <div class="carousel-indicators">

            <button
                type="button"
                data-bs-target="#carruselCoffee"
                data-bs-slide-to="0"
                class="active"
                aria-label="Diapositiva 1"
            ></button>

            <button
                type="button"
                data-bs-target="#carruselCoffee"
                data-bs-slide-to="1"
                aria-label="Diapositiva 2"
            ></button>

            <button
                type="button"
                data-bs-target="#carruselCoffee"
                data-bs-slide-to="2"
                aria-label="Diapositiva 3"
            ></button>

        </div>

        <div class="carousel-inner">

            <div class="carousel-item active">

                <img
                    src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1600"
                    class="d-block w-100 hero-img"
                    alt="Taza de café"
                >

                <div class="carousel-caption">

                    <h1>Bienvenido a CoffeeTime</h1>

                    <p class="fs-5">
                        El mejor sabor para acompañar tu día.
                    </p>

                    <a href="#catalogo" class="btn btn-warning btn-lg fw-bold">
                        Ver nuestro menú
                    </a>

                </div>

            </div>

            <div class="carousel-item">

                <img
                    src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=1600"
                    class="d-block w-100 hero-img"
                    alt="Café preparado"
                >

                <div class="carousel-caption">

                    <h1>Disfruta cada momento</h1>

                    <p class="fs-5">
                        Bebidas preparadas con dedicación.
                    </p>

                    <a href="#catalogo" class="btn btn-warning btn-lg fw-bold">
                        Descubrir productos
                    </a>

                </div>

            </div>

            <div class="carousel-item">

                <img
                    src="https://images.unsplash.com/photo-1498804103079-a6351b050096?w=1600"
                    class="d-block w-100 hero-img"
                    alt="Cafetería"
                >

                <div class="carousel-caption">

                    <h1>Tu cafetería favorita</h1>

                    <p class="fs-5">
                        Encuentra bebidas, postres y alimentos.
                    </p>

                    <a href="#servicios" class="btn btn-warning btn-lg fw-bold">
                        Conócenos
                    </a>

                </div>

            </div>

        </div>

        <button
            class="carousel-control-prev"
            type="button"
            data-bs-target="#carruselCoffee"
            data-bs-slide="prev"
        >
            <span class="carousel-control-prev-icon"></span>
        </button>

        <button
            class="carousel-control-next"
            type="button"
            data-bs-target="#carruselCoffee"
            data-bs-slide="next"
        >
            <span class="carousel-control-next-icon"></span>
        </button>

    </div>

</section>


<!-- ================= CATÁLOGO ================= -->

<section id="catalogo" class="container py-5">

    <div class="text-center mb-4">

        <h2 class="section-title">
            ☕ Nuestro menú
        </h2>

        <p class="text-muted">
            Descubre nuestras bebidas, postres y alimentos.
        </p>

    </div>

    <!-- BUSCADOR -->

    <div class="row justify-content-center mb-4">

        <div class="col-12 col-md-7">

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>

                <input
                    type="search"
                    id="buscadorProductos"
                    class="form-control"
                    placeholder="Buscar productos..."
                >

            </div>

        </div>

    </div>

    <!-- FILTROS -->

    <div class="text-center mb-4">

        <button
            class="btn btn-coffee category-btn"
            data-categoria="todos"
        >
            Todos
        </button>

        <button
            class="btn btn-outline-dark category-btn"
            data-categoria="bebidas calientes"
        >
            Bebidas calientes
        </button>

        <button
            class="btn btn-outline-dark category-btn"
            data-categoria="bebidas frías"
        >
            Bebidas frías
        </button>

        <button
            class="btn btn-outline-dark category-btn"
            data-categoria="repostería"
        >
            Repostería
        </button>

        <button
            class="btn btn-outline-dark category-btn"
            data-categoria="alimentos"
        >
            Alimentos
        </button>

    </div>

    <!-- MENSAJE -->

    <div
        id="mensajeCatalogo"
        class="alert alert-danger text-center"
        role="alert"
    ></div>

    <!-- PRODUCTOS -->

    <div class="row g-4" id="contenedorProductos">

        <div class="col-12 text-center py-5">
            <div class="spinner-border text-warning"></div>
            <p class="mt-2">Cargando productos...</p>
        </div>

    </div>

</section>


<!-- ================= CARRITO ================= -->

<section id="carrito" class="container py-5">

    <div class="cart-panel">

        <div class="text-center mb-4">

            <h2 class="section-title">
                <i class="bi bi-cart3"></i>
                Mi carrito
            </h2>

            <p class="text-muted">
                Revisa tus productos antes de confirmar tu pedido.
            </p>

        </div>

        <div class="table-responsive">

            <table class="table align-middle">

                <thead class="table-dark">

                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>

                </thead>

                <tbody id="contenidoCarrito">

                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            Tu carrito está vacío.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <div class="row justify-content-end">

            <div class="col-12 col-md-5 col-lg-4">

                <div class="d-flex justify-content-between mb-3">

                    <h5 class="fw-bold">Total:</h5>

                    <h5 class="fw-bold text-success" id="totalCarrito">
                        $0.00
                    </h5>

                </div>

                <button
                    type="button"
                    class="btn btn-warning w-100 fw-bold"
                    id="btnConfirmarPedido"
                >
                    <i class="bi bi-check-circle"></i>
                    Confirmar pedido
                </button>

                <button
                    type="button"
                    class="btn btn-outline-danger w-100 mt-2"
                    id="btnVaciarCarrito"
                >
                    Vaciar carrito
                </button>

            </div>

        </div>

    </div>

</section>


<!-- ================= NOSOTROS ================= -->

<section id="nosotros" class="about-section py-5">

    <div class="container">

        <div class="row align-items-center g-4">

            <div class="col-12 col-md-6">

                <img
                    src="https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=1000"
                    alt="Interior de cafetería"
                    class="img-fluid rounded shadow"
                >

            </div>

            <div class="col-12 col-md-6">

                <h2 class="section-title mb-3">
                    Sobre CoffeeTime
                </h2>

                <p>
                    CoffeeTime es una cafetería que busca ofrecer
                    una experiencia agradable a sus clientes mediante
                    bebidas, postres y alimentos de calidad.
                </p>

                <p>
                    Nuestra plataforma web permite consultar el menú,
                    conocer los precios, agregar productos al carrito
                    y realizar pedidos de manera sencilla.
                </p>

                <p>
                    Nuestro objetivo es facilitar la atención al cliente
                    y mejorar la administración de los pedidos.
                </p>

                <a href="#catalogo" class="btn btn-coffee">
                    Explorar menú
                </a>

            </div>

        </div>

    </div>

</section>


<!-- ================= SERVICIOS ================= -->

<section id="servicios" class="container py-5">

    <div class="text-center mb-5">

        <h2 class="section-title">
            Nuestros servicios
        </h2>

        <p class="text-muted">
            Pensamos en ofrecerte una experiencia práctica y agradable.
        </p>

    </div>

    <div class="row g-4">

        <div class="col-12 col-md-4">

            <div class="card service-card shadow-sm text-center p-4">

                <div class="service-icon">
                    <i class="bi bi-cup-hot"></i>
                </div>

                <h5 class="fw-bold mt-3">
                    Variedad de productos
                </h5>

                <p class="text-muted">
                    Consulta nuestras bebidas calientes, frías,
                    repostería y alimentos.
                </p>

            </div>

        </div>

        <div class="col-12 col-md-4">

            <div class="card service-card shadow-sm text-center p-4">

                <div class="service-icon">
                    <i class="bi bi-cart-check"></i>
                </div>

                <h5 class="fw-bold mt-3">
                    Pedidos en línea
                </h5>

                <p class="text-muted">
                    Agrega tus productos al carrito y confirma
                    tu pedido desde nuestra plataforma.
                </p>

            </div>

        </div>

        <div class="col-12 col-md-4">

            <div class="card service-card shadow-sm text-center p-4">

                <div class="service-icon">
                    <i class="bi bi-person-check"></i>
                </div>

                <h5 class="fw-bold mt-3">
                    Atención al cliente
                </h5>

                <p class="text-muted">
                    Crea tu cuenta para realizar pedidos
                    y disfrutar de nuestros servicios.
                </p>

                <?php if (!$sesionIniciada): ?>

                    <a href="registro.php" class="btn btn-coffee mt-2">
                        Crear cuenta
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>


<!-- ================= CONTACTO ================= -->

<section id="contacto" class="about-section py-5">

    <div class="container">

        <div class="text-center mb-4">

            <h2 class="section-title">
                Contáctanos
            </h2>

            <p class="text-muted">
                Estamos para atenderte.
            </p>

        </div>

        <div class="row justify-content-center">

            <div class="col-12 col-md-7">

                <div class="card border-0 shadow p-4">

                    <div class="mb-3">

                        <i class="bi bi-cup-hot-fill text-warning"></i>
                        <strong> CoffeeTime</strong>

                    </div>

                    <p>
                        <i class="bi bi-envelope"></i>
                        Correo: contacto@coffeetime.com
                    </p>

                    <p>
                        <i class="bi bi-telephone"></i>
                        Teléfono: 000 000 0000
                    </p>

                    <p>
                        <i class="bi bi-clock"></i>
                        Horario: Lunes a sábado
                    </p>

                    <p class="mb-0">
                        <i class="bi bi-geo-alt"></i>
                        Ubicación: Nuestra cafetería
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= FOOTER ================= -->

<footer class="text-white py-4">

    <div class="container text-center">

        <h4 class="fw-bold">
            ☕ CoffeeTime
        </h4>

        <p>
            Tu momento favorito comienza con un buen café.
        </p>

        <div class="mb-3">

            <a href="#inicio" class="mx-2">Inicio</a>
            <a href="#catalogo" class="mx-2">Menú</a>
            <a href="#nosotros" class="mx-2">Nosotros</a>
            <a href="#contacto" class="mx-2">Contacto</a>

        </div>

        <hr class="border-light">

        <p class="mb-0">
            © 2026 CoffeeTime - Todos los derechos reservados.
        </p>

    </div>

</footer>


<!-- ================= MODAL DE INICIO DE SESIÓN ================= -->

<div
    class="modal fade"
    id="authModal"
    tabindex="-1"
    aria-labelledby="authModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header bg-dark text-white">

                <h5 class="modal-title fw-bold" id="authModalLabel">
                    ☕ Iniciar sesión en CoffeeTime
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>

            </div>

            <div class="modal-body p-4">

                <div
                    id="mensajeLogin"
                    class="alert d-none"
                    role="alert"
                ></div>

                <form id="authForm">

                    <div class="mb-3">

                        <label for="loginUsername" class="form-label fw-semibold">
                            Nombre de usuario
                        </label>

                        <input
                            type="text"
                            id="loginUsername"
                            class="form-control"
                            placeholder="Ingresa tu usuario"
                            autocomplete="username"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label for="loginPassword" class="form-label fw-semibold">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="loginPassword"
                            class="form-control"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        id="btnLogin"
                        class="btn btn-warning w-100 fw-bold"
                    >
                        Iniciar sesión
                    </button>

                </form>

                <hr>

                <div class="text-center">

                    <p class="mb-2">
                        ¿Todavía no tienes cuenta?
                    </p>

                    <a href="registro.php" class="btn btn-outline-dark w-100">
                        Registrarse
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ================= JAVASCRIPT ================= -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

/* =====================================
   VARIABLES
===================================== */

let productos = [];
let carrito = [];

const contenedorProductos =
    document.getElementById("contenedorProductos");

const contenidoCarrito =
    document.getElementById("contenidoCarrito");

const contadorCarrito =
    document.getElementById("contadorCarrito");

const totalCarrito =
    document.getElementById("totalCarrito");

const mensajeCatalogo =
    document.getElementById("mensajeCatalogo");


/* =====================================
   FORMATO DE PRECIOS
===================================== */

function formatoPrecio(precio) {

    return Number(precio).toLocaleString("es-MX", {
        style: "currency",
        currency: "MXN"
    });

}


/* =====================================
   CARGAR PRODUCTOS
===================================== */

async function cargarProductos() {

    try {

        const respuesta = await fetch("productos.php", {
            method: "GET",
            cache: "no-store",
            headers: {
                "Accept": "application/json"
            }
        });

        const texto = await respuesta.text();

        let datos;

        try {
            datos = JSON.parse(texto);
        } catch (error) {
            console.error("Respuesta de productos.php:", texto);
            throw new Error("El servidor no devolvió un catálogo JSON válido.");
        }

        if (!respuesta.ok || !Array.isArray(datos)) {
            throw new Error("No se pudo cargar el catálogo.");
        }

        productos = datos;

        mensajeCatalogo.style.display = "none";

        mostrarProductos(productos);

    } catch (error) {

        console.error(error);

        contenedorProductos.innerHTML = "";

        mensajeCatalogo.textContent =
            "Error al cargar el catálogo. Verifica la conexión con la base de datos y el archivo productos.php.";

        mensajeCatalogo.style.display = "block";

    }

}


/* =====================================
   MOSTRAR PRODUCTOS
===================================== */

function mostrarProductos(lista) {

    contenedorProductos.innerHTML = "";

    if (!lista.length) {

        contenedorProductos.innerHTML = `
            <div class="col-12 text-center py-5">
                <h5>No se encontraron productos.</h5>
            </div>
        `;

        return;
    }

    lista.forEach(producto => {

        const precio = Number(producto.precio);
        const stock = Number(producto.stock ?? 0);

        const imagen = producto.imagen
            ? producto.imagen
            : "https://placehold.co/600x400?text=CoffeeTime";

        const nombreSeguro = String(producto.nombre ?? "")
            .replace(/[&<>"']/g, caracter => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            })[caracter]);

        const descripcionSegura = String(producto.descripcion ?? "")
            .replace(/[&<>"']/g, caracter => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            })[caracter]);

        const tarjeta = document.createElement("div");

        tarjeta.className = "col-12 col-sm-6 col-lg-4 col-xl-3";

        tarjeta.innerHTML = `
            <div class="card product-card shadow-sm">

                <img
                    src="${imagen}"
                    class="product-img"
                    alt="${nombreSeguro}"
                    onerror="this.src='https://placehold.co/600x400?text=CoffeeTime'"
                >

                <div class="card-body d-flex flex-column">

                    <h5 class="card-title fw-bold">
                        ${nombreSeguro}
                    </h5>

                    <p class="text-muted small">
                        ${descripcionSegura}
                    </p>

                    <p class="mb-2">
                        <span class="badge bg-secondary">
                            ${String(producto.categoria ?? "General")}
                        </span>
                    </p>

                    <h5 class="text-success fw-bold">
                        ${formatoPrecio(precio)}
                    </h5>

                    <p class="small text-muted">
                        Disponibles: ${stock}
                    </p>

                    <button
                        class="btn btn-coffee w-100 mt-auto"
                        onclick="agregarAlCarrito(${Number(producto.id)})"
                        ${stock <= 0 ? "disabled" : ""}
                    >
                        <i class="bi bi-cart-plus"></i>
                        ${stock <= 0 ? "Agotado" : "Agregar al carrito"}
                    </button>

                </div>

            </div>
        `;

        contenedorProductos.appendChild(tarjeta);

    });

}


/* =====================================
   BUSCAR PRODUCTOS
===================================== */

document.getElementById("buscadorProductos")
.addEventListener("input", function() {

    const busqueda = this.value.toLowerCase().trim();

    const resultado = productos.filter(producto => {

        const nombre = String(producto.nombre ?? "").toLowerCase();
        const descripcion = String(producto.descripcion ?? "").toLowerCase();
        const categoria = String(producto.categoria ?? "").toLowerCase();

        return (
            nombre.includes(busqueda) ||
            descripcion.includes(busqueda) ||
            categoria.includes(busqueda)
        );

    });

    mostrarProductos(resultado);

});


/* =====================================
   FILTRAR POR CATEGORÍA
===================================== */

document.querySelectorAll(".category-btn").forEach(boton => {

    boton.addEventListener("click", function() {

        const categoria = this.dataset.categoria;

        document.querySelectorAll(".category-btn").forEach(btn => {
            btn.classList.remove("btn-coffee");
            btn.classList.add("btn-outline-dark");
        });

        this.classList.remove("btn-outline-dark");
        this.classList.add("btn-coffee");

        if (categoria === "todos") {

            mostrarProductos(productos);

            return;
        }

        const resultado = productos.filter(producto => {

            return String(producto.categoria ?? "")
                .toLowerCase()
                .trim() === categoria;

        });

        mostrarProductos(resultado);

    });

});


/* =====================================
   AGREGAR AL CARRITO
===================================== */

function agregarAlCarrito(id) {

    const producto = productos.find(p => Number(p.id) === Number(id));

    if (!producto) {
        alert("No se encontró el producto.");
        return;
    }

    const stock = Number(producto.stock ?? 0);

    if (stock <= 0) {
        alert("Este producto está agotado.");
        return;
    }

    const existente = carrito.find(p => Number(p.id) === Number(id));

    if (existente) {

        if (existente.cantidad >= stock) {
            alert("No hay más unidades disponibles.");
            return;
        }

        existente.cantidad++;

    } else {

        carrito.push({
            id: Number(producto.id),
            nombre: producto.nombre,
            precio: Number(producto.precio),
            cantidad: 1,
            stock: stock
        });

    }

    actualizarCarrito();

}


/* =====================================
   ACTUALIZAR CARRITO
===================================== */

function actualizarCarrito() {

    contenidoCarrito.innerHTML = "";

    let total = 0;
    let cantidadTotal = 0;

    if (carrito.length === 0) {

        contenidoCarrito.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted">
                    Tu carrito está vacío.
                </td>
            </tr>
        `;

    }

    carrito.forEach(producto => {

        const subtotal = producto.precio * producto.cantidad;

        total += subtotal;
        cantidadTotal += producto.cantidad;

        const fila = document.createElement("tr");

        fila.innerHTML = `
            <td>${String(producto.nombre)}</td>

            <td>${formatoPrecio(producto.precio)}</td>

            <td>
                <div class="d-flex align-items-center gap-2">

                    <button
                        class="btn btn-sm btn-outline-secondary"
                        onclick="cambiarCantidad(${producto.id}, -1)"
                    >
                        -
                    </button>

                    <span>${producto.cantidad}</span>

                    <button
                        class="btn btn-sm btn-outline-secondary"
                        onclick="cambiarCantidad(${producto.id}, 1)"
                    >
                        +
                    </button>

                </div>
            </td>

            <td>${formatoPrecio(subtotal)}</td>

            <td>
                <button
                    class="btn btn-sm btn-danger"
                    onclick="eliminarDelCarrito(${producto.id})"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        contenidoCarrito.appendChild(fila);

    });

    contadorCarrito.textContent = cantidadTotal;

    totalCarrito.textContent = formatoPrecio(total);

}


/* =====================================
   CAMBIAR CANTIDAD
===================================== */

function cambiarCantidad(id, cambio) {

    const producto = carrito.find(p => Number(p.id) === Number(id));

    if (!producto) return;

    const nuevaCantidad = producto.cantidad + cambio;

    if (nuevaCantidad <= 0) {
        eliminarDelCarrito(id);
        return;
    }

    if (nuevaCantidad > producto.stock) {
        alert("No hay suficiente existencia.");
        return;
    }

    producto.cantidad = nuevaCantidad;

    actualizarCarrito();

}


/* =====================================
   ELIMINAR PRODUCTO
===================================== */

function eliminarDelCarrito(id) {

    carrito = carrito.filter(p => Number(p.id) !== Number(id));

    actualizarCarrito();

}


/* =====================================
   VACIAR CARRITO
===================================== */

document.getElementById("btnVaciarCarrito")
.addEventListener("click", function() {

    if (carrito.length === 0) {
        alert("Tu carrito ya está vacío.");
        return;
    }

    if (confirm("¿Deseas vaciar tu carrito?")) {

        carrito = [];

        actualizarCarrito();

    }

});


/* =====================================
   CONFIRMAR PEDIDO
===================================== */

document.getElementById("btnConfirmarPedido")
.addEventListener("click", async function() {

    if (carrito.length === 0) {

        alert("Agrega productos antes de confirmar tu pedido.");

        return;
    }

    <?php if (!$sesionIniciada): ?>

    const modal = new bootstrap.Modal(
        document.getElementById("authModal")
    );

    modal.show();

    alert("Debes iniciar sesión para realizar un pedido.");

    return;

    <?php else: ?>

    const boton = this;

    boton.disabled = true;
    boton.textContent = "Procesando pedido...";

    try {

        const respuesta = await fetch("pedidos.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },

            body: JSON.stringify({

                productos: carrito.map(producto => ({
                    id: producto.id,
                    cantidad: producto.cantidad
                })),

                total: carrito.reduce(
                    (suma, producto) =>
                        suma + producto.precio * producto.cantidad,
                    0
                )

            })

        });

        const texto = await respuesta.text();

        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (error) {
            console.error("Respuesta de pedidos.php:", texto);
            throw new Error("El servidor no devolvió una respuesta JSON válida.");
        }

        if (!respuesta.ok || resultado.success !== true) {
            throw new Error(
                resultado.message || "No se pudo registrar el pedido."
            );
        }

        alert(
            resultado.message ||
            "¡Tu pedido fue registrado correctamente!"
        );

        carrito = [];

        actualizarCarrito();

        await cargarProductos();

    } catch (error) {

        console.error(error);

        alert(error.message);

    } finally {

        boton.disabled = false;
        boton.innerHTML = `
            <i class="bi bi-check-circle"></i>
            Confirmar pedido
        `;

    }

    <?php endif; ?>

});


/* =====================================
   INICIAR SESIÓN
===================================== */

document.getElementById("authForm")
.addEventListener("submit", async function(event) {

    event.preventDefault();

    const username = document.getElementById("loginUsername").value.trim();
    const password = document.getElementById("loginPassword").value;

    const mensaje = document.getElementById("mensajeLogin");
    const boton = document.getElementById("btnLogin");

    boton.disabled = true;
    boton.textContent = "Verificando...";

    mensaje.className = "alert alert-info";
    mensaje.textContent = "Comprobando tus datos...";

    try {

        const respuesta = await fetch("login.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },

            body: JSON.stringify({
                username: username,
                password: password
            })

        });

        const texto = await respuesta.text();

        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (error) {
            console.error("Respuesta de login.php:", texto);
            throw new Error("El servidor no devolvió JSON válido.");
        }

        if (!respuesta.ok || resultado.success !== true) {
            throw new Error(
                resultado.message || "No se pudo iniciar sesión."
            );
        }

        mensaje.className = "alert alert-success";
        mensaje.textContent = resultado.message;

        setTimeout(() => {
            window.location.reload();
        }, 1000);

    } catch (error) {

        mensaje.className = "alert alert-danger";
        mensaje.textContent = error.message;

    } finally {

        boton.disabled = false;
        boton.textContent = "Iniciar sesión";

    }

});


/* =====================================
   CERRAR SESIÓN
===================================== */

const btnLogout = document.getElementById("btnLogout");

if (btnLogout) {

    btnLogout.addEventListener("click", async function() {

        if (!confirm("¿Estás seguro de que deseas cerrar sesión?")) {
            return;
        }

        try {

            const respuesta = await fetch("index.php", {

                method: "POST",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify({
                    action: "logout"
                })

            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || resultado.success !== true) {
                throw new Error("No se pudo cerrar la sesión.");
            }

            window.location.reload();

        } catch (error) {

            alert(error.message);

        }

    });

}


/* =====================================
   INICIAR CATÁLOGO Y CARRITO
===================================== */

cargarProductos();

actualizarCarrito();

</script>

</body>
</html>
