<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";

if (!isset($_SESSION['Usuario']) || ($_SESSION['rol'] ?? $_SESSION['Rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

try {
    $sen = $con->prepare("SELECT * FROM usuario");
    $sen->execute();
    $usuarios = $sen->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode(["exito" => true, "usuarios" => $usuarios]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "exito" => false,
        "mensaje" => "Error al obtener usuarios: " . $e->getMessage()
    ]);
}