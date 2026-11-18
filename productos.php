
<?php
session_start();

require_once __DIR__ . "/seguridad.php";

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit;
}

$usuario = limpiar($_SESSION["username"] ?? "Cliente");
$tokenCSRF = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Menú | CoffeeTime</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f5f1;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #4b2e24;
        }

        .navbar-brand,
        .navbar .nav-link {
            color: white !important;
        }

        .titulo {
            color: #4b2e24;
            font-weight: bold;
        }

        .producto-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0,0,0,.10);
            height: 100%;
        }

        .producto-card img {
            height: 190px;
            width: 100%;
            object-fit: cover;
        }

        .producto-card .card-body {
            display: flex;
            flex-direction: column;
        }

        .btn-cafe {
            background: #6f4e37;
            color: white;
            border: none;
        }

        .btn-cafe:hover {
            background: #4b2e24;
            color: white;
        }

        .carrito {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
        }

        .ticket {
            background: white;
            border: 1px dashed #777;
            padding: 25px;
            border-radius: 10px;
            max-width: 550px;
            margin: 25px auto;
        }

        .ticket-linea {
            border-bottom: 1px dashed #aaa;
            padding: 8px 0;
        }

        .cantidad-input {
            width: 65px;
        }

        @media print {
            body * {
                visibility: hidden !important;
            }

            #ticket, #ticket * {
                visibility: visible !important;
            }

            #ticket {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
            }

            .no-imprimir {
                display: none !important;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.html">☕ CoffeeTime</a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-white">
                Hola, <?= $usuario ?>
            </span>

            <a href="index.html" class="btn btn-outline-light btn-sm">
                Cerrar sesión
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="text-center mb-4">
        <h1 class="titulo">☕ Nuestro menú</h1>
        <p class="text-muted">
            Disfruta de nuestros productos preparados especialmente para ti.
        </p>
    </div>

    <div id="mensaje" class="alert d-none" role="alert"></div>

    <h3 class="titulo mb-4">Nuestros productos</h3>

    <div id="productos" class="row g-4">
        <div class="col-12 text-center">
            <div class="spinner-border text-secondary"></div>
            <p>Cargando productos...</p>
        </div>
    </div>

    <hr class="my-5">

    <div class="row">
        <div class="col-lg-8 mx-auto">

            <div class="carrito">
                <h3 class="titulo mb-3">🛒 Mi carrito</h3>

                <div id="contenidoCarrito">
                    <p class="text-muted">Tu carrito está vacío.</p>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <h5>Total:</h5>
                    <h5 id="totalCarrito">$0.00</h5>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-outline-danger"
                            onclick="vaciarCarrito()">
                        Vaciar carrito
                    </button>

                    <button class="btn btn-cafe"
                            id="btnComprar"
                            onclick="comprar()">
                        Confirmar compra
                    </button>
                </div>
            </div>

        </div>
    </div>

    <div id="contenedorTicket" class="d-none mt-5">

        <div id="ticket" class="ticket">

            <div class="text-center">
                <h2 class="fw-bold">☕ CoffeeTime</h2>
                <p>Comprobante de compra</p>
                <hr>
            </div>

            <p><strong>Pedido:</strong> <span id="ticketPedido"></span></p>
            <p><strong>Cliente:</strong> <span id="ticketCliente"></span></p>
            <p><strong>Fecha:</strong> <span id="ticketFecha"></span></p>

            <hr>

            <div id="ticketProductos"></div>

            <hr>

            <div class="d-flex justify-content-between">
                <h5>Total pagado:</h5>
                <h5 id="ticketTotal"></h5>
            </div>

            <hr>

            <p class="text-center mb-0">
                ¡Gracias por tu compra!
            </p>
            <p class="text-center">
                CoffeeTime te espera nuevamente.
            </p>
        </div>

        <div class="text-center no-imprimir">
            <button class="btn btn-cafe" onclick="descargarTicket()">
                Descargar ticket PDF
            </button>
        </div>

    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
const tokenCSRF = <?= json_encode($tokenCSRF) ?>;
const nombreCliente = <?= json_encode($usuario, JSON_UNESCAPED_UNICODE) ?>;

let productosDisponibles = [];
let carrito = [];

let ultimoTicket = null;

const CLAVE_CARRITO = "coffeeTimeCarrito";

function mostrarMensaje(texto, tipo = "success") {
    const mensaje = document.getElementById("mensaje");

    mensaje.textContent = texto;
    mensaje.className = "alert alert-" + tipo;
    mensaje.classList.remove("d-none");

    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
}

function guardarCarrito() {
    localStorage.setItem(CLAVE_CARRITO, JSON.stringify(carrito));
}

function escaparHTML(texto) {
    return String(texto ?? "").replace(/[&<>"']/g, function (caracter) {
        const equivalencias = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        };

        return equivalencias[caracter];
    });
}

function formatoPrecio(precio) {
    return Number(precio).toLocaleString("es-MX", {
        style: "currency",
        currency: "MXN"
    });
}

async function cargarProductos() {
    const contenedor = document.getElementById("productos");

    try {
        const respuesta = await fetch("api_productos.php", {
            method: "GET",
            cache: "no-store"
        });

        const datos = await respuesta.json();

        if (!respuesta.ok || datos.success === false) {
            throw new Error(datos.message || "No se pudieron cargar los productos.");
        }

        productosDisponibles = Array.isArray(datos)
            ? datos
            : (datos.productos || datos.data || []);

        mostrarProductos();

        // Recuperar carrito guardado
        try {
            const carritoGuardado = JSON.parse(
                localStorage.getItem(CLAVE_CARRITO) || "[]"
            );

            carrito = Array.isArray(carritoGuardado)
                ? carritoGuardado
                : [];

            // Actualizar datos y existencias con la información del servidor
            carrito = carrito.filter(item => {
                const producto = productosDisponibles.find(
                    p => Number(p.id) === Number(item.id)
                );

                if (!producto || Number(producto.stock) <= 0) {
                    return false;
                }

                item.nombre = producto.nombre;
                item.precio = Number(producto.precio);
                item.cantidad = Math.min(
                    Math.max(1, Number(item.cantidad) || 1),
                    Number(producto.stock)
                );

                return true;
            });

        } catch (error) {
            carrito = [];
        }

        guardarCarrito();
        actualizarCarrito();

    } catch (error) {
        contenedor.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    Error al cargar el catálogo: ${escaparHTML(error.message)}
                </div>
            </div>
        `;
    }
}

function mostrarProductos() {
    const contenedor = document.getElementById("productos");

    if (productosDisponibles.length === 0) {
        contenedor.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info">
                    No hay productos disponibles.
                </div>
            </div>
        `;
        return;
    }

    contenedor.innerHTML = "";

    productosDisponibles.forEach(producto => {
        const stock = Number(producto.stock);
        const precio = Number(producto.precio);

        let imagen = producto.imagen || "";

        if (!imagen) {
            imagen = "https://placehold.co/600x400?text=CoffeeTime";
        } else if (!/^https?:\/\//i.test(imagen)) {
            imagen = new URL(imagen, window.location.href).href;
        }

        const tarjeta = document.createElement("div");
        tarjeta.className = "col-md-6 col-lg-4";

        tarjeta.innerHTML = `
            <div class="card producto-card">
                <img src="${escaparHTML(imagen)}"
                     alt="${escaparHTML(producto.nombre)}"
                     onerror="this.src='https://placehold.co/600x400?text=CoffeeTime'">

                <div class="card-body">
                    <h5 class="card-title">
                        ${escaparHTML(producto.nombre)}
                    </h5>

                    <p class="text-muted">
                        ${escaparHTML(producto.descripcion || "")}
                    </p>

                    <h5 class="text-success">
                        ${formatoPrecio(precio)}
                    </h5>

                    <p class="small text-muted">
                        Existencias: ${stock}
                    </p>

                    <button class="btn btn-cafe mt-auto"
                        onclick="agregarCarrito(${Number(producto.id)})"
                        ${stock <= 0 ? "disabled" : ""}>
                        ${stock <= 0 ? "Agotado" : "Agregar al carrito"}
                    </button>
                </div>
            </div>
        `;

        contenedor.appendChild(tarjeta);
    });
}

function agregarCarrito(id) {
    const producto = productosDisponibles.find(
        p => Number(p.id) === Number(id)
    );

    if (!producto) {
        mostrarMensaje("No se encontró el producto.", "danger");
        return;
    }

    if (Number(producto.stock) <= 0) {
        mostrarMensaje("Este producto no tiene existencias.", "warning");
        return;
    }

    const existente = carrito.find(
        p => Number(p.id) === Number(id)
    );

    if (existente) {
        if (Number(existente.cantidad) >= Number(producto.stock)) {
            mostrarMensaje("No hay más existencias disponibles.", "warning");
            return;
        }

        existente.cantidad++;
    } else {
        carrito.push({
            id: Number(producto.id),
            nombre: producto.nombre,
            precio: Number(producto.precio),
            cantidad: 1
        });
    }

    guardarCarrito();
    actualizarCarrito();

    mostrarMensaje("Producto agregado al carrito.", "success");
}

function cambiarCantidad(id, cantidad) {
    const producto = productosDisponibles.find(
        p => Number(p.id) === Number(id)
    );

    const item = carrito.find(
        p => Number(p.id) === Number(id)
    );

    if (!producto || !item) return;

    cantidad = Number(cantidad);

    if (!Number.isInteger(cantidad) || cantidad < 1) {
        eliminarCarrito(id);
        return;
    }

    if (cantidad > Number(producto.stock)) {
        mostrarMensaje("No hay suficientes existencias.", "warning");
        cantidad = Number(producto.stock);
    }

    item.cantidad = cantidad;

    guardarCarrito();
    actualizarCarrito();
}

function eliminarCarrito(id) {
    carrito = carrito.filter(
        p => Number(p.id) !== Number(id)
    );

    guardarCarrito();
    actualizarCarrito();

    mostrarMensaje("Producto eliminado del carrito.", "info");
}

function vaciarCarrito() {
    carrito = [];

    guardarCarrito();
    actualizarCarrito();

    mostrarMensaje("El carrito está vacío.", "info");
}

function actualizarCarrito() {
    const contenido = document.getElementById("contenidoCarrito");
    const totalElemento = document.getElementById("totalCarrito");

    if (carrito.length === 0) {
        contenido.innerHTML = `
            <p class="text-muted">Tu carrito está vacío.</p>
        `;

        totalElemento.textContent = formatoPrecio(0);

        document.getElementById("btnComprar").disabled = true;
        return;
    }

    let total = 0;

    contenido.innerHTML = "";

    carrito.forEach(item => {
        const subtotal = Number(item.precio) * Number(item.cantidad);
        total += subtotal;

        const fila = document.createElement("div");
        fila.className = "d-flex justify-content-between align-items-center border-bottom py-3 gap-2";

        fila.innerHTML = `
            <div>
                <strong>${escaparHTML(item.nombre)}</strong>
                <br>
                <small class="text-muted">
                    ${formatoPrecio(item.precio)} c/u
                </small>
            </div>

            <div class="d-flex align-items-center gap-2">
                <input
                    type="number"
                    min="1"
                    class="form-control cantidad-input"
                    value="${Number(item.cantidad)}"
                    onchange="cambiarCantidad(${Number(item.id)}, this.value)"
                >

                <strong>${formatoPrecio(subtotal)}</strong>

                <button class="btn btn-sm btn-outline-danger"
                    onclick="eliminarCarrito(${Number(item.id)})"
                    title="Eliminar producto">
                    ✕
                </button>
            </div>
        `;

        contenido.appendChild(fila);
    });

    totalElemento.textContent = formatoPrecio(total);
    document.getElementById("btnComprar").disabled = false;
}

async function comprar() {
    if (carrito.length === 0) {
        mostrarMensaje("Tu carrito está vacío.", "warning");
        return;
    }

    const boton = document.getElementById("btnComprar");

    boton.disabled = true;
    boton.textContent = "Procesando compra...";

    try {
        const respuesta = await fetch("guardar_pedido.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                productos: carrito.map(item => ({
                    id: Number(item.id),
                    cantidad: Number(item.cantidad)
                })),
                token_csrf: tokenCSRF
            })
        });

        const texto = await respuesta.text();

        let resultado;

        try {
            resultado = JSON.parse(texto);
        } catch (error) {
            throw new Error(
                "El servidor no devolvió JSON válido. Revisa guardar_pedido.php."
            );
        }

        if (!respuesta.ok || !resultado.success) {
            throw new Error(
                resultado.message || "No se pudo registrar la compra."
            );
        }

        // Guardar información del comprobante
        ultimoTicket = {
            pedido_id: resultado.pedido_id,
            cliente: nombreCliente,
            fecha: resultado.fecha || new Date().toLocaleString("es-MX"),
            total: Number(resultado.total),
            productos: resultado.productos || []
        };

        // Vaciar carrito después de confirmar la compra
        carrito = [];
        guardarCarrito();
        actualizarCarrito();

        // Mostrar comprobante
        mostrarTicket(ultimoTicket);

        // Descargar automáticamente el PDF
        descargarTicket();

        // Actualizar existencias
        await cargarProductos();

        mostrarMensaje(
            "¡Compra realizada correctamente! Tu ticket PDF se descargó automáticamente.",
            "success"
        );

    } catch (error) {
        mostrarMensaje(error.message, "danger");

    } finally {
        boton.disabled = carrito.length === 0;
        boton.textContent = "Confirmar compra";
    }
}

function mostrarTicket(datos) {
    document.getElementById("contenedorTicket").classList.remove("d-none");

    document.getElementById("ticketPedido").textContent =
        "#" + datos.pedido_id;

    document.getElementById("ticketCliente").textContent =
        datos.cliente;

    document.getElementById("ticketFecha").textContent =
        datos.fecha;

    document.getElementById("ticketTotal").textContent =
        formatoPrecio(datos.total);

    const contenedor = document.getElementById("ticketProductos");

    contenedor.innerHTML = "";

    datos.productos.forEach(producto => {
        const fila = document.createElement("div");
        fila.className = "ticket-linea";

        const cantidad = Number(producto.cantidad);
        const precio = Number(producto.precio);
        const subtotal = Number(
            producto.subtotal ?? precio * cantidad
        );

        fila.innerHTML = `
            <strong>${escaparHTML(producto.nombre)}</strong>
            <br>
            ${cantidad} x ${formatoPrecio(precio)}
            <span class="float-end">
                ${formatoPrecio(subtotal)}
            </span>
        `;

        contenedor.appendChild(fila);
    });
}

function descargarTicket() {
    if (!ultimoTicket) {
        mostrarMensaje("No hay ningún ticket para descargar.", "warning");
        return;
    }

    if (!window.jspdf || !window.jspdf.jsPDF) {
        mostrarMensaje(
            "No se pudo cargar el generador de PDF. Revisa tu conexión a internet.",
            "danger"
        );
        return;
    }

    const { jsPDF } = window.jspdf;

    const doc = new jsPDF({
        orientation: "portrait",
        unit: "mm",
        format: "a4"
    });

    const margen = 20;
    const ancho = 170;
    let y = 20;

    doc.setFont("helvetica", "bold");
    doc.setFontSize(22);
    doc.text("CoffeeTime", 105, y, { align: "center" });

    y += 9;

    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.text("Comprobante de compra", 105, y, { align: "center" });

    y += 12;

    doc.setLineWidth(0.4);
    doc.line(margen, y, 190, y);

    y += 10;

    doc.setFontSize(10);
    doc.text("Pedido: #" + ultimoTicket.pedido_id, margen, y);

    y += 7;
    doc.text("Cliente: " + ultimoTicket.cliente, margen, y);

    y += 7;
    doc.text("Fecha: " + ultimoTicket.fecha, margen, y);

    y += 10;

    doc.line(margen, y, 190, y);

    y += 9;

    doc.setFont("helvetica", "bold");
    doc.text("Producto", margen, y);
    doc.text("Cant.", 125, y);
    doc.text("Precio", 145, y);
    doc.text("Subtotal", 190, y, { align: "right" });

    y += 5;
    doc.line(margen, y, 190, y);

    y += 8;

    doc.setFont("helvetica", "normal");

    ultimoTicket.productos.forEach(producto => {
        const cantidad = Number(producto.cantidad);
        const precio = Number(producto.precio);
        const subtotal = Number(
            producto.subtotal ?? precio * cantidad
        );

        const nombre = doc.splitTextToSize(
            String(producto.nombre),
            75
        );

        const altura = Math.max(7, nombre.length * 5);

        if (y + altura > 260) {
            doc.addPage();
            y = 20;
        }

        doc.text(nombre, margen, y);
        doc.text(String(cantidad), 125, y);
        doc.text("$" + precio.toFixed(2), 145, y);
        doc.text("$" + subtotal.toFixed(2), 190, y, {
            align: "right"
        });

        y += altura + 4;
    });

    y += 5;

    doc.line(margen, y, 190, y);

    y += 12;

    doc.setFont("helvetica", "bold");
    doc.setFontSize(14);

    doc.text(
        "TOTAL: $" + Number(ultimoTicket.total).toFixed(2),
        190,
        y,
        { align: "right" }
    );

    y += 18;

    doc.setFontSize(11);
    doc.setFont("helvetica", "normal");

    doc.text(
        "Gracias por tu compra. ¡Te esperamos pronto!",
        105,
        y,
        { align: "center" }
    );

    // Descargar el comprobante
    doc.save("Ticket_CoffeeTime_Pedido_" + ultimoTicket.pedido_id + ".pdf");
}

document.addEventListener("DOMContentLoaded", cargarProductos);
</script>

</body>
</html>
