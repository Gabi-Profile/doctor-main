<?php
if (!function_exists('vaciarCarrito')) {
    function vaciarCarrito() {
        $_SESSION['carrito'] = [];
    }
}
