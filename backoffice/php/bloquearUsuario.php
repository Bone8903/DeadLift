<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
 
include "bdd.php";
 
// Solo el administrador puede bloquear o desbloquear usuarios
if (!isset($_SESSION['Usuario']) || ($_SESSION['rol'] ?? $_SESSION['Rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}
 
// id del usuario y acción: "1" = bloquear, cualquier otro valor = desbloquear
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$bloquear = (($_POST['bloquear'] ?? '') === '1') ? 1 : 0;
 
if ($id <= 0) {
    echo json_encode(["exito" => false, "mensaje" => "Falta el id del usuario"]);
    exit;
}
 
try {
    // Primero se comprueba que el usuario exista (un UPDATE que no cambia nada devuelve 0 filas afectadas,
    // así que rowCount() no sirve para distinguir "no existe" de "ya estaba en ese estado")
    $existe = $con->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ?");
    $existe->execute([$id]);
 
    if (!$existe->fetch()) {
        echo json_encode(["exito" => false, "mensaje" => "El usuario no existe"]);
        exit;
    }
 
    // bloqueado es tinyint(1): 1 = TRUE (bloqueado), 0 = FALSE (activo)
    $sen = $con->prepare("UPDATE usuario SET bloqueado = ? WHERE id_usuario = ?");
    $sen->execute([$bloquear, $id]);
 
    echo json_encode([
        "exito"   => true,
        "mensaje" => $bloquear ? "Usuario bloqueado exitosamente" : "Usuario desbloqueado exitosamente"
    ]);
 
} catch (PDOException $e) {
    error_log($e->getMessage()); // el detalle queda en el log del servidor, no se muestra al usuario
    echo json_encode(["exito" => false, "mensaje" => "Error al actualizar el usuario"]);
}
?>
 