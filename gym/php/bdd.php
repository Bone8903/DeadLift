<?php
try{ //Prueba realizar las lineas de codigo que estan dentro de si, si solo si no da un error que le prevenga realizarlo

$con=new PDO("mysql:host=localhost:3306; dbname=gym","root",""); //La coneccion de LocalHost, dentro se define: el nombre de el gestor de la base de datos, el puerto a utilizar (3306), el nombre de la base de datos y el usuario que se utiliza para acceder al LocalHost

}catch(PDOException $e){ //Si el try tiene un error, el catch lo agarra y lo almacena dentro de la variable $e
echo json_encode(["exito" => false, "mensaje" => "Error en la concexion"]); //Devuelve un valor en un formato legible para javascript, gracias al json encode, en este caso, devuelve que exito es falso porque algo fallo en la conexion
exit; //exit
}

?>