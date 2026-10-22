<?php
session_start(); //session_start sirve para hacer que la funcion de $_SESSION pueda ser usada de manera correcta. Sin el no se podria usar sessions
header('Content-Type: application/json; charset=utf-8'); //especifica el tipo de datos que el sistema debe de esperar, ya sea un POST o GET
include "bdd.php"; // Se le incluye la base de datos para que las tablas que estan dentro de ellas se puedan utilizar dentro de la sintaxis SQL que se encuentra abajo

//cada trim no permite que se utilizen caracteres espceciales dentro de la variable, impidiendo ataques XSS e injecciones de JS
$n= trim($_POST['Nombre'] ?? '');
$a= trim($_POST['Apellido'] ?? '');
$m= trim($_POST['Mail'] ?? '');
$nt= trim($_POST['Telefono'] ?? '');
$c= $_POST['Contraseña'] ?? '';        // sin trim ni htmlspecialchars
$confC= $_POST['ConfContraseña'] ?? '';
$codigo= strtoupper(trim($_POST['Codigo'] ?? ''));

// Sin código = cliente. Con código = instructor (se valida abajo)
$r = ($codigo === '') ? 'cliente' : 'instructor';

//valida que todos los espacios obligatorios no queden vacios
if ($n === '' || $a === '' || $m === '' || $c === '' || $confC === '') {
    echo json_encode(["exito" => false, "mensaje" => "Todos los campos obligatorios deben completarse", "r" => $r]);
    exit;
}

//valida que las contraseñas sean iguales
if ($c !== $confC) {
    echo json_encode(["exito" => false, "mensaje" => "Las contraseñas no coinciden", "r" => $r]);
    exit;
}

//valida que las contraseñas sean mas largas de 12 caracteres
if (strlen($c) < 12) {
    echo json_encode(["exito" => false, "mensaje" => "La contraseña debe tener al menos 12 caracteres", "r" => $r]);
    exit;
}

// Validar email para que contenga el @gmail.com
if (!filter_var($m, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["exito" => false, "mensaje" => "El mail no es válido", "r" => $r]);
    exit;
}

//hashea las contraseñas
$passwordHash = password_hash($c, PASSWORD_DEFAULT);

try {
    $con->beginTransaction();

    // 1) Email repetido
    $verificar = $con->prepare("SELECT 1 FROM usuario WHERE `mail` = ?");
    $verificar->execute([$m]);
    if ($verificar->fetch()) {
        $con->rollBack();
        echo json_encode(["exito" => false, "mensaje" => "El email ya está registrado", "r" => $r]);
        exit;
    }

    // Instructor: canjear código (existe, no usado, no vencido) en una sola operación
    if ($r === 'instructor') {
        $cod = $con->prepare("UPDATE Codigo SET Activo = 1 WHERE Nombre_codigo = ? AND Activo = 0 AND Fecha_expiracion > NOW()");
        $cod->execute([$codigo]);

        if ($cod->rowCount() !== 1) {
            $con->rollBack();
            echo json_encode(["exito" => false, "mensaje" => "El código no es válido, ya fue usado o venció", "r" => $r]);
            exit;
        }
    }

    //Crear usuario
    $sen = $con->prepare("INSERT INTO Usuario(`Nombre`, `Apellido`, `Mail`, `Telefono`, `Contraseña`, `Rol`) VALUES(?,?,?,?,?,?)");
    $sen->execute([$n, $a, $m, $nt, $passwordHash, $r]);

    $con->lastInsertId();
    
    $con->commit();

    // Sesión, para que los PHP de instructor lo reconozcan
    $_SESSION['Usuario'] = true;
    $_SESSION['Rol'] = $r;

    echo json_encode(["exito" => true, "mensaje" => "El usuario se registró de manera correcta", "r" => $r]);

} catch (Throwable $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo json_encode(["exito" => false, "mensaje" => "No se pudo completar el registro", "r" => $r]);
}
?>