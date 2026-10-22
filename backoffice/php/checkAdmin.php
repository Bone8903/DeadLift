<?php
session_start();
if ( isset($_SESSION['Usuario'])){
echo json_encode(["mensaje" => true, "r" => $_SESSION['rol'] ?? $_SESSION['Rol'] ?? '']);

}else{

echo json_encode(["mensaje" => false]);

}






?>