<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";
 
// Solo el instructor puede borrar músculos
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}
 
// El id se valida como número entero positivo
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
 
if (!$id || $id <= 0) {
    echo json_encode(["exito" => false, "mensaje" => "ID invalido"]);
    exit;
}
 
try {
    // La clave foránea fk_ejercicio_musculo es ON DELETE SET NULL: los ejercicios que usaban
    // este músculo NO se borran, solo quedan con id_musculo = NULL ("sin músculo").
    $sen = $con->prepare("DELETE FROM Musculo WHERE Id_ejercicio = ?");
    $sen->execute([$id]);
 
    if ($sen->rowCount()) {
        echo json_encode(["exito" => true, "mensaje" => "Músculo eliminado"]);
    } else {
        echo json_encode(["exito" => false, "mensaje" => "El músculo no existe"]);
    }
 
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(["exito" => false, "mensaje" => "No se pudo borrar el músculo"]);
}
?>
