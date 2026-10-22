<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

include "bdd.php";

if (!isset($_SESSION['Usuario']) || ($_SESSION['rol'] ?? $_SESSION['Rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(["exito" => false, "mensaje" => "Falta el id del usuario"]);
    exit;
}

try {

    $sen = $con->prepare("DELETE FROM usuario WHERE id_usuario = ?");
    $resultado = $sen->execute([$id]);
    
    if ($resultado) {
        echo json_encode(["exito" => true, "mensaje" => "Usuario eliminado correctamente"]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "No se pudo eliminar el usuario"]);
            }
            
} catch (PDOException $e) {
    echo json_encode(["exito" => false, "mensaje" => "Error al eliminar usuario: " . $e->getMessage()]);
}
?>