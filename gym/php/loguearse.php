<?php

include "bdd.php"; // Se le incluye la base de datos para que las tablas que estan dentro de ellas se puedan utilizar dentro de la sintaxis SQL que se encuentra abajo

header('Content-Type: application/json; charset=utf-8'); //especifica el tipo de datos que el sistema debe de esperar, ya sea un POST o GET

$m = trim($_POST['Mail'] ); // Se crea la variable mail y no permite que se utilizen caracteres espceciales dentro de la variable, impidiendo ataques XSS e injecciones de JS
$c = $_POST['Contraseña'] ;   // No hacer trim ni htmlspecialchars a la contraseña

if ($m === '' || $c === '') { // Un if para detectar si algunas de las variables se dejo vacias, impidiendo el acceso ya que no pueden ser null
    echo json_encode(["exito" => false, "mensaje" => "Faltan datos"]); // le avisa a javascript que el exito fue un fracaso
    exit;
}

try { //un try que permite el manejo de si una accion es exitosa o si resultara en fracaso
    $res = $con->prepare("SELECT * FROM usuario WHERE `Mail` = ?"); // almacena la respuesta de SQL dentro de una variable res, la que almacena la informacion de la fila mail dentro de la tabla usuario 
    $res->execute([$m]); // ejecuta la variable mail dentro de una respuesta ajena de el input, previniendo ataques maliciosos 
    $usuario = $res->fetch(PDO::FETCH_ASSOC);

    if (!empty($usuario['bloqueado'])) {
    echo json_encode(["exito" => false, "mensaje" => "Tu cuenta está bloqueada"]);
    exit;
}

    if ($usuario && password_verify($c, $usuario['Contraseña'])) { /*si el usuario y la contraseña están correctos 
    (la contraseña se verifica por medio de la función password_verify para desencriptar la contraseña hasheada)
    le permite acceso adentro de lo que dice el if
    */

        session_start(); //session_start sirve para hacer que la funcion de $_SESSION pueda ser usada de manera correcta. Sin el no se podria usar sessions
        session_regenerate_id(true); // previene session fixation

        $_SESSION['Usuario'] = true; //se explica que el usuario tiene una sesión inicializada, también dicho como que la sesión del usuario == true
        $_SESSION['Id_usuario'] = $usuario['Id_usuario']; //Se está definiendo el ID del usuario para que se guarde y perdure en la sesión
        $_SESSION['Rol'] = $usuario['Rol']; //Se está definiendo el rol del usuario para que se guarde y perdure en la sesión
        $rol = $usuario['Rol']; //Qué rol se le está relacionando al usuario

        unset($usuario['Contraseña']); //Hace que no se guarde la contraseña en el frontend; esto es por seguridad (lo que sí se guarda es la contraseña hasheade en la base de datos)

        echo json_encode(["exito" => true, "datos" => $usuario, "Rol" => $rol, "r" => $rol]); //Devuelve un JSON encode (formato legible para JS) que incluye si la acción fue exitosa y el rol

    } else { //si el try no fue exitoso, lo trae en un nmensaje advirtiendo que el mail o contraseña es incorrecto
        // Mismo mensaje para ambos casos: no revela si el mail existe
        echo json_encode(["exito" => false, "mensaje" => "Mail o contraseña incorrectos"]);
    }

} catch (PDOException $e) { //atrapa un error
    error_log($e->getMessage()); // log interno, no lo muestres al usuario
    echo json_encode(["exito" => false, "mensaje" => "Error en el servidor"]);
}