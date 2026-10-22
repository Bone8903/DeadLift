<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";
 
// // Si el servidor no tiene la extensión mbstring, mb_strlen no existe y el PHP se cae con un error 500
// if (!function_exists('mb_strlen')) {
//     function mb_strlen($texto) { return strlen($texto); }
// }
 
// Solo el instructor puede agregar músculos
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}
 
$nombre = trim(htmlspecialchars($_POST['nombre'] ?? ''));
 
if ($nombre === '') {
    echo json_encode(["exito" => false, "mensaje" => "El nombre del músculo es obligatorio"]);
    exit;
}
 
if (mb_strlen($nombre) > 50) { // musculos.nombre es varchar(50)
    echo json_encode(["exito" => false, "mensaje" => "El nombre no puede superar los 50 caracteres"]);
    exit;
}
 
try {
    // La tabla no tiene índice único, así que se evita el duplicado a mano
    // (la comparación no distingue mayúsculas: "Pecho" y "pecho" cuentan como el mismo)
    $existe = $con->prepare("SELECT Id_ejercicio FROM Musculo WHERE Musculo = ?");
    $existe->execute([$nombre]);
 
    if ($existe->fetch()) {
        echo json_encode(["exito" => false, "mensaje" => "Ese músculo ya existe"]);
        exit;
    }
 
    $sen = $con->prepare("INSERT INTO Musculo (Musculo) VALUES (?)");
    $sen->execute([$nombre]);
 
    echo json_encode([
        "exito"      => true,
        "mensaje"    => "Músculo guardado correctamente",
        "id_musculo" => (int)$con->lastInsertId()
        
    ]);
 
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(["exito" => false, "mensaje" => "No se pudo guardar el músculo" . $e->getMessage()]);
}
?>