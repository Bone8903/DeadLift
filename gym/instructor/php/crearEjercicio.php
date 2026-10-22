<?php
error_reporting(5);
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";
 
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') {
    http_response_code(403);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}
// Los nombres de $_POST tienen que coincidir con los "name" de los inputs de instructor.html.
// Los músculos llegan como un arreglo: musculos[] (uno por cada chip marcado, ver instructor.js)
$n  = trim(htmlspecialchars($_POST['Nombre'] ?? ''));
$d  = trim(htmlspecialchars($_POST['Descripcion_ejercicio'] ?? ''));
$na = trim(htmlspecialchars($_POST['Nota_adicional'] ?? ''));
$musculosPost = $_POST['musculos'] ?? [];
if (!is_array($musculosPost)) {
    $musculosPost = [];
}
 
// Id del instructor que crea el ejercicio (Ejercicio.Id_usuario). Se guarda en la sesión al loguearse
$idUsuario = isset($_SESSION['Id_usuario']) ? (int)$_SESSION['Id_usuario'] : null;
 
if ($n === '') {
    echo json_encode(["exito" => false, "mensaje" => "El nombre es obligatorio"]);
    exit;
}
 
// Largos máximos de las columnas de la tabla Ejercicio
if (mb_strlen($n) > 50 || mb_strlen($d) > 255 || mb_strlen($na) > 255) {
    echo json_encode(["exito" => false, "mensaje" => "Un campo es demasiado largo (nombre 50, descripción y notas 255)"]);
    exit;
}
 
// Se limpian los músculos: sin vacíos, máximo 50 caracteres (Musculo.Musculo es varchar(50))
// y sin repetidos (la PK es Id_ejercicio + Musculo, y MySQL no distingue "Pecho" de "pecho")
$musculos = [];
foreach ($musculosPost as $m) {
    $m = trim(htmlspecialchars((string)$m));
 
    if ($m === '') {
        continue;
    }
    if (mb_strlen($m) > 50) {
        echo json_encode(["exito" => false, "mensaje" => "El nombre de un músculo no puede superar los 50 caracteres"]);
        exit;
    }
    $musculos[mb_strtolower($m)] = $m; // la clave en minúscula evita duplicados
}

try {
 
    // El orden de los valores tiene que coincidir con el de las columnas:
    // Nombre, Nota_adicional, Descripcion_ejercicio, Id_usuario
    $sen = $con->prepare(
        "INSERT INTO Ejercicio (`Nombre`, `Nota_adicional`, `Descripcion_ejercicio`, `Id_usuario`) VALUES (?, ?, ?, ?)"
    );
    $sen->execute([$n, $na, $d, $idUsuario]); // null se guarda como NULL
    $id = (int)$con->lastInsertId();
 
    // Un INSERT por músculo, con el Id_ejercicio recién creado (la PK de Musculo es Id_ejercicio + Musculo)
    if ($musculos) {
        $mus = $con->prepare("INSERT INTO Musculo (`Id_ejercicio`, `Musculo`) VALUES (?, ?)");
        foreach ($musculos as $m) {
            $mus->execute([$id, $m]);
        }
    }

// En tmp_name se ecuentra el archivo subido desde el formulario
$origen = $_FILES['imagen']['tmp_name'];
// Tipo de archivo
$tipo = $_FILES['imagen']['type'];
// Tipos de archivos permitidos
$tipos_permitidos = ["image/png", "image/jpg", "image/jpeg"];
// Destino del archivo en el servidor
$destino ="../img/ejercicio/".$id.".jpg";
if (in_array($tipo, $tipos_permitidos)) {
// Con esta funcion se sube el archivo definitavemente al servidor
move_uploaded_file($origen, $destino);
echo json_encode(["exito" => true, "mensaje" => "Se guardo el ejercicio de manera correcta", "img" => $destino]);

}else if($tipo == null || $tipos_permitidos == null){
    
    echo json_encode(["exito" => true]);

 } else {
    echo json_encode(["exito" => false, "mensaje" => "Solo se permiten imagenes"]);
    exit();
}

} catch (PDOException $e) {
    echo json_encode(["exito" => false, "mensaje" => "No se guardó el ejercicio" . $e->getMessage()]);
}
?>