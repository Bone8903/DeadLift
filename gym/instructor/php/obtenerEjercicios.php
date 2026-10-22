<?php
 
session_start();
header('Content-Type: application/json; charset=utf-8'); //especifica el tipo de datos que el sistema debe de esperar, ya sea un POST o GET
include "bdd.php"; // Se le incluye la base de datos para que las tablas que estan dentro de ellas se puedan utilizar dentro de la sintaxis SQL que se encuentra abajo
 
// Solo instructores logueados
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

try { // dentro del try se encuentra la sintaxis de sql para buscar los ejercicios
    // LEFT JOIN: los ejercicios sin músculo también aparecen (nombre_musculo queda en null)
    $sen = $con->prepare(
    "SELECT  e.*, e.Id_ejercicio as Id_e, GROUP_CONCAT(m.Musculo SEPARATOR ', ') AS Nombre_musculo FROM Ejercicio e
     LEFT JOIN Musculo m ON m.Id_ejercicio = e.Id_ejercicio
     GROUP BY e.Id_ejercicio"
    ); //
    $sen->execute();
    $ejercicios = $sen->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ejercicios); //esto devuelve ejercicios, osea muestra la cantidad de ejercicios dentro del array que hay
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "Error del servidor :("]); //muestra que hubo un error y el exito es falso
}