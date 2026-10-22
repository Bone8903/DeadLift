<?php
// En tmp_name se ecuentra el archivo subido desde el formulario
$origen = $_FILES['imagen']['tmp_name'];
// Tipo de archivo
$tipo = $_FILES['imagen']['type'];
// Tipos de archivos permitidos
$tipos_permitidos = ["image/png", "image/jpg", "image/jpeg"];
// Destino del archivo en el servidor
$destino ="/../img/ejercicio.jpg";
if (in_array($tipo, $tipos_permitidos)) {
// Con esta funcion se sube el archivo definitavemente al servidor
move_uploaded_file($origen, $destino);
} else {
    echo json_encode(["mensaje" => "Solo se permiten imagenes"]);
    exit();
}
    echo json_encode(["img" => $destino]);

?>