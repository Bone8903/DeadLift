<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "bdd.php";
 
// Solo pueden entrar clientes con la sesión iniciada
if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Id_usuario']) || ($_SESSION['Rol'] ?? '') !== 'cliente') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}
 
try {

 $sen = $con->prepare(
        "SELECT r.Id_rutina          AS id_rutina,
                r.Horario            AS horario,
                r.Descripcion_rutina AS descripcion_rutina,
                a.Nombre_dia         AS nombre_dia,
                e.Id_ejercicio       AS id_ejercicio,
                e.Nombre             AS nombre_ejercicio,
                (SELECT GROUP_CONCAT(m.Musculo SEPARATOR ', ') FROM Musculo m WHERE m.Id_ejercicio = e.Id_ejercicio) AS musculos, -- subconsulta: un ejercicio con varios músculos no duplica filas
                e.Descripcion_ejercicio AS descripcion_ejercicio,
                e.Nota_adicional     AS notas_adicionales,
                a.Series             AS series,
                a.Repeticiones       AS repeticiones,
                a.Notas              AS notas
         FROM Cliente_rutina cr
         INNER JOIN Rutina r             ON r.Id_rutina    = cr.Id_rutina
         INNER JOIN Rutina_agregacion ra ON ra.Id_rutina   = r.Id_rutina
         INNER JOIN Asigna a             ON a.Id_asigna    = ra.Id_asigna
         INNER JOIN Ejercicio e          ON e.Id_ejercicio = a.Id_ejercicio
         WHERE cr.Id_usuario = ?
         ORDER BY r.Id_rutina,
                  FIELD(a.Nombre_dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'),
                  a.Id_asigna"
    );
    $sen->execute([$_SESSION['Id_usuario']]);
    $filas = $sen->fetchAll(PDO::FETCH_ASSOC);
 
    // Las imágenes no están en la base: las guarda el instructor como instructor/img/ejercicio/{id_ejercicio}.jpg (o png, gif, webp)
    foreach ($filas as &$fila) {
        $archivos = glob(__DIR__ . "/../instructor/img/ejercicio/" . $fila['id_ejercicio'] . ".*");
        $fila['imagen'] = $archivos ? "./instructor/img/ejercicio/" . basename($archivos[0]) : null;
    }
    unset($fila);
 
    echo json_encode($filas);
 
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "Error al consultar los ejercicios" . $e -> getMessage()]);
}
?>