<?php
session_start();
include 'db.php';

// Configuración de paginación
$cantidad_por_pagina = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 20;
$cantidad_por_pagina = in_array($cantidad_por_pagina, [10, 20, 30, 40, 50]) ? $cantidad_por_pagina : 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $cantidad_por_pagina;

// Consultas
$sql_total = "SELECT COUNT(*) as total FROM componentes WHERE especialidad IN ('trauma','quemados')";
$total_resultado = mysqli_query($conn, $sql_total);
$total_filas = mysqli_fetch_assoc($total_resultado)['total'];
$total_paginas = ceil($total_filas / $cantidad_por_pagina);

$sql_final = "SELECT * FROM componentes WHERE especialidad IN ('trauma','quemados') ORDER BY fecha_ingreso DESC LIMIT $cantidad_por_pagina OFFSET $offset";
$resultado = mysqli_query($conn, $sql_final);
$insumos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="asset/styles.css">
    <title>Administración de Insumos de Traumatología</title>
    <div class="header">
        <img src="asset/logo.png" alt="Logo">
        <div class="header-text">
            <div class="main-title">Solicitar insumos médicos</div>
            <div class="sub-title">Hospital Clínico Félix Bulnes</div>
        </div>
        <button id="cuenta-btn" onclick="toggleAccountInfo()"><?php echo $_SESSION['nombre']; ?></button>
        <div id="accountInfo" style="display: none;">
            <p><strong>Usuario: </strong><?php echo $_SESSION['nombre']; ?></p>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">Salir</button>
            </form>
        <button type="button" class="volver-btn" onclick="window.location.href='principal.php'">Volver</button>
        </div>
    </div>
    <style>
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .insumo-card {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: transform 0.2s;
        }

        .insumo-card:hover {
            transform: scale(1.05);
        }

        .insumo-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .insumo-card h3 {
            margin: 10px 0;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($insumos)): ?>
            <?php foreach ($insumos as $componente): 
                $codigo = $componente['codigo'];
                $imagen = "imagenes/$codigo.jpg";
                $existe_imagen = file_exists($imagen);
            ?>
                <div class="insumo-card">
                    <?php if ($existe_imagen): ?>
                        <img src="<?= $imagen ?>" alt="Imagen de <?= htmlspecialchars($componente['insumo']) ?>">
                    <?php else: ?>
                        <img src="asset/no_image_available.png" alt="Sin imagen disponible">
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($componente['insumo']) ?></h3>

                    <form method="POST" action="carrito.php" target="carrito-frame" onsubmit="mostrarCarrito()" style="display: flex; flex-direction: column; align-items: center;">
                        <input type="hidden" name="add_to_cart" value="1">
                        <input type="hidden" name="insumo" value="<?= htmlspecialchars($componente['insumo']) ?>">
                        <input type="number" name="cantidad" value="1" min="1" style="width: 60px; margin: 5px 0;">
                        <button type="submit" style="background-color: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                            +
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron insumos de traumatología.</p>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <div class="pagination-container">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?= $i ?>&cantidad=<?= $cantidad_por_pagina ?>" class="<?= $pagina_actual == $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>

    <!-- Carrito iframe -->
    <iframe id="carrito-frame" name="carrito-frame" style="display:none; position: fixed; bottom: 20px; right: 20px; width: 300px; height: 300px; border: 1px solid #ccc; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); background: white; z-index: 1000;"></iframe>
    <script>
        function toggleAccountInfo() {
            const info = document.getElementById('accountInfo');
            info.style.display = info.style.display === 'none' ? 'block' : 'none';
        }
        function mostrarCarrito() {
            const frame = document.getElementById('carrito-frame');
            setTimeout(() => {
                frame.style.display = 'block';
            }, 300); // pequeña pausa para que reciba la respuesta
        }

        // Cierra el carrito al hacer clic fuera del iframe
        document.addEventListener("click", function(e) {
            const frame = document.getElementById("carrito-frame");
            if (!frame.contains(e.target) && e.target.tagName !== "BUTTON") {
                frame.style.display = "none";
            }
        });
    </script>
</body>
</html>