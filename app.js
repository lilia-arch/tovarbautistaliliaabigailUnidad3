
document.addEventListener("DOMContentLoaded", () => {

    const productGrid = document.getElementById("productGrid");
    const loadingIndicator = document.getElementById("loadingIndicator");

    // Cargar productos
    async function cargarProductos() {
        try {
            const response = await fetch("productos.php");

            if (!response.ok) {
                throw new Error("Error al consultar productos.");
            }

            const productos = await response.json();

            productGrid.innerHTML = "";

            if (!Array.isArray(productos) || productos.length === 0) {
                productGrid.innerHTML =
                    '<p class="text-center">No hay productos disponibles.</p>';
                return;
            }

            productos.forEach(producto => {
                productGrid.innerHTML += `
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <img src="${producto.imagen}"
                                 class="card-img-top"
                                 alt="${producto.nombre}"
                                 style="height:220px; object-fit:cover;">

                            <div class="card-body">
                                <h5 class="card-title">
                                    ${producto.nombre}
                                </h5>

                                <p class="card-text">
                                    ${producto.descripcion}
                                </p>

                                <h5 class="text-warning">
                                    $${Number(producto.precio).toFixed(2)}
                                </h5>
                            </div>
                        </div>
                    </div>
                `;
            });

        } catch (error) {
            productGrid.innerHTML =
                '<p class="text-danger text-center">Error al cargar el menú.</p>';
            console.error(error);
        } finally {
            loadingIndicator.classList.add("d-none");
        }
    }

    cargarProductos();

    // Cerrar sesión
    const btnLogout = document.getElementById("btnLogout");

    if (btnLogout) {
        btnLogout.addEventListener("click", async () => {
            try {
                const response = await fetch("logout.php");
                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error("Error al cerrar sesión:", error);
            }
        });
    }

});
