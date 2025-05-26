<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';
include 'funciones.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar insumo al carrito
if (isset($_POST['add_to_cart'])) {
    $insumo = $_POST['insumo'];
    $cantidad = (int) $_POST['cantidad'];

    $_SESSION['carrito'][$insumo] = ($_SESSION['carrito'][$insumo] ?? 0) + $cantidad;
}



// Enviar lista por correo usando Composer
if (isset($_POST['send_email'])) {

    $paciente = $_POST['nombre_paciente'] ?? '';
    $cirugia = $_POST['cirugia'] ?? '';
    $codigo_cirugia = $_POST['codigo_cirugia'] ?? '';
    $pabellon = $_POST['pabellon'] ?? '';
    $cirujano = $_POST['cirujano'] ?? '';
    $equipo = $_POST['equipo'] ?? '';
    $insumos = implode(', ', array_keys($_SESSION['carrito']));

    // Insertar cirugía
    $stmt = $conn->prepare("INSERT INTO cirugias (nombre_paciente, cirugia, cod_cirugia, pabellon, cirujano, equipo, insumos)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $paciente, $cirugia, $codigo_cirugia, $pabellon, $cirujano, $equipo, $insumos);
    $stmt->execute();
    $id_cirugia = $conn->insert_id;

// Recorrer el carrito
foreach ($_SESSION['carrito'] as $nombre_insumo => $cantidad) {
    echo "<pre>🔍 Buscando insumo: " . htmlspecialchars($nombre_insumo) . "</pre>";

    // Buscar el ID del componente por nombre exacto del insumo
    $stmt = $conn->prepare("SELECT id FROM componentes WHERE insumo = ?");
    $stmt->bind_param("s", $nombre_insumo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $componente = $result->fetch_assoc()) {
        $id_componente = $componente['id'];
        echo "<pre>✅ Componente encontrado: ID = $id_componente</pre>";

        // Insertar en tabla de salidas
        $stmt_salida = $conn->prepare("INSERT INTO salidas (id_componente, cantidad, fecha_salida, realizado_por)
                                       VALUES (?, ?, NOW(), ?)");
        $stmt_salida->bind_param("iis", $id_componente, $cantidad, $_SESSION['nombre']);
        $stmt_salida->execute();

        // Insertar en tabla insumos_cirugia
        $stmt_relacion = $conn->prepare("INSERT INTO insumos_cirugia (id_cirugia, id_componente, cantidad_usada)
                                         VALUES (?, ?, ?)");
        $stmt_relacion->bind_param("iii", $id_cirugia, $id_componente, $cantidad);
        $stmt_relacion->execute();

        // Actualizar stock en componentes
        $stmt_descuento = $conn->prepare("UPDATE componentes SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmt_descuento->bind_param("iii", $cantidad, $id_componente, $cantidad);
        $stmt_descuento->execute();

    } else {
        echo "<pre style='color:red;'>❌ Insumo no encontrado en 'componentes': " . htmlspecialchars($nombre_insumo) . "</pre>";
        continue;
    }
}

//Enviar correo
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'usuario@example.com';
    $mail->Password = 'contraseña';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('usuario@example.com', 'Hospital Clínico');
    $mail->addAddress('destino@example.com');

    $mail->Subject = 'Lista de Insumos para Cirugía';
    $mail->Body = "<p>Paciente: $paciente</p><p>Cirugía: $cirugia</p><p>Pabellón: $pabellon</p><p>Cirujano: $cirujano</p><p>Equipo: $equipo</p><p>Insumos: $insumos</p>";

    $mail->send();
    echo 'Correo enviado correctamente.';
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}

    vaciarCarrito();

}
?>
<style>
.carrito-contenedor {
    padding: 15px;
    font-family: sans-serif;
    max-height: 250px;
    overflow-y: auto;
}
.carrito-contenedor ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.carrito-contenedor li {
    margin-bottom: 8px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 4px;
    font-size: 14px;
}
.carrito-botones {
    margin-top: 10px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}
.carrito-botones form button {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn-enviar {
    background-color: #007bff;
    color: white;
}
.btn-limpiar {
    background-color: #dc3545;
    color: white;
}
</style>

<div class="carrito-contenedor">
    <?php if (empty($_SESSION['carrito'])): ?>
        <p>Carrito vacío.</p>
    <?php else: ?>
        <strong>Carrito de insumos:</strong>
        <ul>
            <?php foreach ($_SESSION['carrito'] as $insumo => $cantidad): ?>
                <li><strong><?= htmlspecialchars($insumo) ?></strong> — <?= $cantidad ?> unidad(es)</li>
            <?php endforeach; ?>
        </ul>
        <div class="carrito-botones">
            <form method="POST">
                <input type="text" name="nombre_paciente" placeholder="Nombre paciente" required>
                <input type="text" name="cirugia" placeholder="Cirugía" required>
                <input type="text" name="codigo_cirugia" placeholder="Código cirugía" required>
                <input type="text" name="pabellon" placeholder="Pabellón" required>
                <input type="text" name="cirujano" placeholder="Cirujano" required>
                <input type="text" name="equipo" placeholder="Equipo" required>
                <button class="btn-enviar" name="send_email">Enviar</button>
            </form>
            <form method="POST" action="carrito.php">
                <input type="hidden" name="vaciar_carrito" value="1">
                <button class="btn-limpiar">Limpiar</button>
            </form>
        </div>
    <?php endif; ?>
</div>