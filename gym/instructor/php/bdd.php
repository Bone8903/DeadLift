<?php
try{ //Prueba realizar las lineas de codigo que estan dentro

$con=new PDO("mysql:host=localhost:3306; dbname=gym","root",""); //define el gestor de base de datos, el puerto, la base de datos y el usuario de SQL

}catch(PDOException $e){ //atrapa cualquier error de conexión
echo json_encode(["exito" => false, "mensaje" => "Error en la concexion"]); //Devuelve un valor en un formato legible para javascript, gracias al json encode, en este caso, devuelve que exito es falso porque algo fallo en la conexion
exit;
}

?>