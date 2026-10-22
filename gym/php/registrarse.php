<?php 

session_start();  //session_start sirve para hacer que la funcion de $_SESSION pueda ser usada de manera correcta. Sin el no se podria usar sessions

header('Content-Type: application/json; charset=utf-8'); //especifica el tipo de datos que el sistema debe de esperar, ya sea un POST o GET
include "bdd.php"; // Se le incluye la base de datos para que las tablas que estan dentro de ellas se puedan utilizar dentro de la sintaxis SQL que se encuentra abajo

//cada trim no permite que se utilizen caracteres espceciales dentro de la variable, impidiendo ataques XSS e injecciones de JS
$n=trim(htmlspecialchars($_POST['Nombre'])); 
$a=trim(htmlspecialchars($_POST['Apellido']))?? "";
$m=trim(htmlspecialchars($_POST['Mail']));
$nt=trim(htmlspecialchars($_POST['Telefono']))?? "";
$c=$_POST['Contraseña'];
$confC=($_POST['ConfContraseña']);

$r = 'cliente'; //como está registrándose como cliente, el rol será definido como cliente


// Validar que los campos obligatorios no estén vacíos
if(empty($n) || empty($a) || empty($m) || empty($c) || empty($confC)){
    echo json_encode(["exito" => false, "mensaje" => "Todos los campos son obligatorios", "r" => $r]);
    exit;
}

// Validar que las contraseñas coincidan
if($c !== $confC){
    echo json_encode(["exito" => false, "mensaje" => "Las contraseñas no coinciden", "r" => $r]);
    exit;
}

// Validar email para que contenga el @gmail.com
if(!filter_var($m, FILTER_VALIDATE_EMAIL)){
    echo json_encode(["exito" => false, "mensaje" => "Contraseña o Mail incorrecto", "r" => $r]);
    exit;
}

$passwordHash = password_hash($c, PASSWORD_DEFAULT); //hashea las contraseñas


try { //este try que inidca que solamente si no hay errores se puede realizar lo que está dentro

    // Verificar si el email ya existe dentro de la base de datos asi no se repite
    $verificar = $con->prepare("SELECT `Mail` FROM usuario WHERE `Mail` = ?"); 
    $verificar->execute([$m]);
    
    if($verificar->rowCount() > 0){ //verifica si el mail ya está en la base de datos; para evitar duplicados
        echo json_encode(["exito" => false, "mensaje" => "El email ya está registrado", "r" => $r]);
        exit;
    }

    // Esto sirve para insertar nuevo usuario
    $sen = $con->prepare("INSERT INTO usuario(`Nombre`, `Apellido`, `Mail`, `Telefono`, `Contraseña`, `rol`) VALUES(?,?,?,?,?,?)");
    $resultado = $sen->execute([$n, $a, $m, $nt, $passwordHash, $r]);

    if ($sen->rowCount()){

    $id=(int)$con->lastInsertId(); //se guarda la id del usuario recien creado
    session_regenerate_id(true);        

    //si hay una sesison ya inicializada, se puede entrar a esa sesion si no se cerro
        $_SESSION['Usuario']    = true;   // "hay una sesión iniciada"
        $_SESSION['Id_usuario'] = $id;    // lo usan check.php (bloqueo) y las consultas del cliente
        $_SESSION['Rol']        = $r;     // check.php y cliente.js leen este value     

    }

    if($sen->rowCount()){ //Verifica las columnas para ver si ha habido algún cambio; si la respuesta es sí, devuelve un mensaje de éxito
        echo json_encode(["exito" => true, "mensaje" => "El usuario se registro de manera correcta :D", "r" => $r]);
    } else {
        echo json_encode(["exito" => false, "mensaje" => "Falle causa :(", "r" => $r]); //si no es exitoso, devuelve un mensaje de error
    }

    } catch (PDOException $e) { //atrapa un error
    error_log($e->getMessage()); // log interno, no lo muestres al usuario
    echo json_encode(["exito" => false, "mensaje" => "hurray ", "r" => $r]);
}

?>