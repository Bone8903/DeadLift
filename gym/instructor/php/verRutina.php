<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";

// Con esto cualquier error de SQL salta como PDOException (y se atrapa abajo)
$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$con->exec("SET NAMES utf8mb4");

// Solo el instructor puede ver las rutinas desde su panel (mismo criterio que obtenerRutina.php)
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
if ($id === false) {
    echo json_encode(["exito" => false, "mensaje" => "ID invalido"]);
    exit;
}

try {
    // 1) Datos generales de la rutina
    $sen = $con->prepare("SELECT Id_rutina, Nombre_rutina, Horario, Descripcion_rutina FROM Rutina WHERE Id_rutina = ?");
    $sen->execute([$id]);
    $rutina = $sen->fetch(PDO::FETCH_ASSOC);

    if (!$rutina) {
        echo json_encode(["exito" => false, "mensaje" => "La rutina ya no existe"]);
        exit;
    }

    // 2) Sus filas: Rutina_agregacion -> Asigna (día, series, repeticiones, notas) -> Ejercicio
    //    Se piden las columnas una por una (sin SELECT *) porque Rutina, Asigna y Ejercicio comparten nombres de columna.
    //    El orden respeta los días de la semana y, dentro de cada día, el orden en que se cargaron.
    $sen = $con->prepare(
        "SELECT a.Nombre_dia, a.Series, a.Repeticiones, a.Notas,
                e.Id_ejercicio, e.Nombre, e.Descripcion_ejercicio, e.Nota_adicional,
                (SELECT GROUP_CONCAT(m.Musculo SEPARATOR ', ') FROM Musculo m WHERE m.Id_ejercicio = e.Id_ejercicio) AS Musculos
         FROM Rutina_agregacion ra
         INNER JOIN Asigna a    ON a.Id_asigna    = ra.Id_asigna
         INNER JOIN Ejercicio e ON e.Id_ejercicio = a.Id_ejercicio
         WHERE ra.Id_rutina = ?
         ORDER BY FIELD(a.Nombre_dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'),
                  a.Id_asigna"
    );
    $sen->execute([$id]);

    // 3) Se agrupa por día, que es como las muestra la pantalla
    $dias = [];
    foreach ($sen->fetchAll(PDO::FETCH_ASSOC) as $f) {
        // La imagen no está en la base: crearEjercicio.php la guarda como instructor/img/ejercicio/{id}.ext
        $archivos = glob(__DIR__ . "/../img/ejercicio/" . (int)$f['Id_ejercicio'] . ".*");
        $imagen = $archivos ? "./img/ejercicio/" . basename($archivos[0]) : null;

        $dias[$f['Nombre_dia']][] = [
            "id"                => (int)$f['Id_ejercicio'],
            "nombre"            => $f['Nombre'],
            "descripcion"       => $f['Descripcion_ejercicio'],
            "nota_ejercicio"    => $f['Nota_adicional'],
            "musculos"          => $f['Musculos'],
            "series"            => $f['Series'],
            "repeticiones"      => $f['Repeticiones'],
            "notas"             => $f['Notas'],
            "imagen"            => $imagen
        ];
    }

    $salida = [];
    foreach ($dias as $nombreDia => $ejercicios) {
        $salida[] = ["nombre" => $nombreDia, "ejercicios" => $ejercicios];
    }

    echo json_encode([
        "exito"  => true,
        "rutina" => [
            "id"          => (int)$rutina['Id_rutina'],
            "nombre"      => $rutina['Nombre_rutina'],
            "horario"     => $rutina['Horario'],
            "descripcion" => $rutina['Descripcion_rutina']
        ],
        "dias"   => $salida
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage()); // el detalle queda en el log del servidor, no en el navegador
    http_response_code(500);
    echo json_encode(["exito" => false, "mensaje" => "No se pudo cargar la rutina"]);
}
?>
