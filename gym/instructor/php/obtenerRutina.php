<?php
session_start(); // session_start sirve par hacer que la funcion de $_SESSION pueda ser usada
include "bdd.php";  //Se le incluye la base de datos para que las tablas que estan dentro de ellas se puedan utilizar dentro de la sintaxis SQL que se encuentra abajo
 

if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') { //está estructura lógica corrobora si el usuario es instructor
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]); //si el usuario no es instructor, devuelve un mensaje de "no autorizado"
    exit;
}

try { //selecciona todos los datos dentro de la tabla rutina
    $sen = $con->prepare("SELECT * FROM Rutina ORDER BY Nombre_rutina ASC"); 
    $sen->execute();
    $rutina = $sen->fetchAll(PDO::FETCH_ASSOC);

    

    echo json_encode($rutina);
} catch (PDOException $e) { //agarra un error
    http_response_code(500);
    echo json_encode([]);
}
?>