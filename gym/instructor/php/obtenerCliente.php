<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";

// Solo el instructor puede ver la lista de clientes
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

try {
    // Solo las columnas que el <select> necesita (antes SELECT * mandaba también el hash de la contraseña al navegador)
    $res = $con->prepare("SELECT Id_usuario, Nombre, Apellido, Mail FROM Usuario WHERE Rol = ? ORDER BY Nombre, Apellido");
    $res->execute(["cliente"]);

    echo json_encode($res->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudieron cargar los clientes"]);
}
?>