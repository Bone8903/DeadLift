<?php
session_start(); // sirve para poder usar $_SESSION
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";
 
// Sin sesión (o con una sesión sin Id_usuario, de antes de este cambio): no está logueado
if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Id_usuario'])) {
    echo json_encode(["exito" => false]);
    exit;
}
 
try {
    // Se consulta la base en cada verificación: así un usuario bloqueado mientras estaba conectado queda afuera
    $res = $con->prepare("SELECT Rol, Bloqueado FROM Usuario WHERE Id_usuario = ?");
    $res->execute([$_SESSION['Id_usuario']]);
    $u = $res->fetch(PDO::FETCH_ASSOC);
 
    if (!$u || !empty($u['Bloqueado'])) {
        session_destroy();
        echo json_encode(["exito" => false, "mensaje" => "Tu cuenta está bloqueada"]);
        exit;
    }
 
    $_SESSION['Rol'] = $u['Rol']; // el rol se refresca desde la base de datos
    echo json_encode(["exito" => true, "Rol" => $u['Rol']]);
 
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(["exito" => false, "mensaje" => "Error en el servidor" . $e->getMessage()]);
}
?>