<?php
session_start(); // session_start sirve par hacer que la funcion de $_SESSION pueda ser usada
header('Content-Type: application/json; charset=utf-8'); //especifica el tipo de datos que el sistema debe de esperar, ya sea un POST o GET
include "bdd.php"; // Se le incluye la base de datos para que las tablas que estan dentro de ellas se puedan utilizar dentro de la sintaxis SQL que se encuentra abajo
 
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') { //está estructura lógica corrobora si el usuario es instructor
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]); //si el usuario no es instructor, devuelve un mensaje de "no autorizado"
    exit;
}
 
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); //valida si el int es un numero entero, sirve para ver si la id realmente existe o si es null
 
if (!$id) { //si el id no es valido, le envia un mensaje de error
    echo json_encode(["exito" => false, "mensaje" => "ID invalido"]);
    exit;
}
 
try {
    $con->beginTransaction();

    // 1) Ids de Asigna ligados a esta rutina (antes de que el cascade borre los vínculos)
    $q = $con->prepare("SELECT Id_asigna FROM Rutina_agregacion WHERE Id_rutina = ?");
    $q->execute([$id]);
    $asignas = $q->fetchAll(PDO::FETCH_COLUMN);

    // 2) Borra la rutina (el cascade limpia Cliente_rutina y Rutina_agregacion)
    $sen = $con->prepare("DELETE FROM Rutina WHERE Id_rutina = ?");
    $sen->execute([$id]);

    if (!$sen->rowCount()) {
        $con->rollBack();
        echo json_encode(["exito" => false, "mensaje" => "La rutina no existe"]);
        exit;
    }

    // 3) Borra las Asigna que quedaron sin ninguna rutina
    if ($asignas) {
        $marcas = implode(',', array_fill(0, count($asignas), '?'));
        $del = $con->prepare(
            "DELETE FROM Asigna
             WHERE Id_asigna IN ($marcas)
               AND Id_asigna NOT IN (SELECT Id_asigna FROM Rutina_agregacion)"
        );
        $del->execute($asignas);
    }

    $con->commit();
    echo json_encode(["exito" => true, "mensaje" => "Rutina eliminada"]);

} catch (PDOException $e) {
    if ($con->inTransaction()) $con->rollBack();
    error_log($e->getMessage());
    echo json_encode(["exito" => false, "mensaje" => "No se pudo eliminar la rutina"]);
}
 ?>