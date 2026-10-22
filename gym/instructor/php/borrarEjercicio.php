<?php
session_start(); //session_start sirve para hacer que la funcion de $_SESSION pueda ser usada de manera correcta. Sin el no se podria usar sessions
header('Content-Type: application/json; charset=utf-8'); //especifica el tipo de datos que el sistema debe de esperar, ya sea un POST o GET

include "bdd.php"; // Se le incluye la base de datos para que las tablas que estan dentro de ellas se puedan utilizar dentro de la sintaxis SQL que se encuentra abajo

if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') { //está estructura lógica corrobora si el usuario es instructor
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]); //si el usuario no es instructor, devuelve un mensaje de "no autorizado"
    exit;
}
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    echo json_encode(["exito" => false, "mensaje" => "Falta el id del ejercicio a eliminar"]);
    exit;
}

try {
    $sen = $con->prepare("DELETE FROM Ejercicio WHERE Id_ejercicio = ?");
    $sen->execute([$id]);

    if ($sen->rowCount()) {
        // La imagen se llama {id}.jpg / .png / .gif / .webp: se borra la que exista
        foreach (glob(dirname(__DIR__) . "/img/ejercicio/" . $id . ".*") as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        echo json_encode(["exito" => true, "mensaje" => "Ejercicio eliminado correctamente"]);
    } else {
        echo json_encode(["exito" => false, "mensaje" => "El ejercicio no existe"]);
    }
} catch (PDOException $e) {
    echo json_encode(["exito" => false, "mensaje" => "Error al eliminar el ejercicio" . $e-> getMessage()]);
}