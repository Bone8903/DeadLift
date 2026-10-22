<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";

// Solo admin
if (!isset($_SESSION['Usuario']) || ($_SESSION['rol'] ?? $_SESSION['Rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

// Genera un código aleatorio criptográficamente seguro (ej: A7K9-XP2M)
function generarCodigo() {
    // Sin caracteres confusos (0/O, 1/I/L)
    $caracteres = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";
    $codigo = "";
    for ($i = 0; $i < 8; $i++) {
        $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        if ($i === 3) {
            $codigo .= "-";
        }
    }
    return $codigo;
}

try {

    // Limpia códigos vencidos
    $con->exec("DELETE FROM Codigo WHERE Fecha_expiracion < NOW()");

    // Nombre_codigo no es UNIQUE en la tabla, así que se verifica a mano que no exista
    $existe = $con->prepare("SELECT 1 FROM Codigo WHERE Nombre_codigo = ?");

    for ($intento = 0; $intento < 5; $intento++) {
        $codigo = generarCodigo();

        $existe->execute([$codigo]);
        if ($existe->fetch()) {
            continue; // ya existe: se genera otro
        }

        // Activo = 0 significa "sin usar" (registrarseInstructor.php lo pasa a 1 al canjearlo)
        $sen = $con->prepare("INSERT INTO Codigo (Nombre_codigo, Activo, Fecha_expiracion) VALUES (?, 0, DATE_ADD(NOW(), INTERVAL 3 DAY))");
        $sen->execute([$codigo]);

        echo json_encode(["exito" => true, "codigo" => $codigo]);
        exit;
    }

    echo json_encode(["exito" => false, "mensaje" => "No se pudo generar un código único"]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(["exito" => false, "mensaje" => "Error al guardar el código"]);
}
?>