<?php

try{

$con=new PDO("mysql:host=localhost:3306; dbname=gym","root","");

}catch(PDOException $e){
echo json_encode(["exito" => false, "mensaje" => "Error en la concexion"]);
exit;
}

?>