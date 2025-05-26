<?php
session_start();

include 'db.php';
include 'funciones.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Vaciar carrito si se hace clic en el botón "Limpiar"
if (isset($_POST['vaciar_carrito'])) {
    vaciarCarrito();
}

// Agregar insumo
if (isset($_POST['add_to_cart'])) {
    $insumo = $_POST['insumo'];
    $cantidad = (int) $_POST['cantidad'];

    if (isset($_SESSION['carrito'][$insumo])) {
        $_SESSION['carrito'][$insumo] += $cantidad;
    } else {
        $_SESSION['carrito'][$insumo] = $cantidad;
    }
}

include 'carrito_insumos.php';

