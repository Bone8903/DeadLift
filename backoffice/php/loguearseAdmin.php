<?php
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";

$m = trim(($_POST['Mail'] ?? ''));
$c = ($_POST['Contraseña'] ?? ''); // la contraseña NO se recorta: se compara tal cual la escribió

if ($m === '' || $c === '') {
    echo json_encode(["exito" => false, "mensaje" => "Faltan datos del formulario"]);
    exit;
}

try {
    //Se busca al administrador SOLO por mail (ya no se manda la contraseña dentro del SQL)
    $res = $con->prepare("SELECT * FROM usuario WHERE Mail = ?");
    $res->execute([$m]);
    $usuario = $res->fetch(PDO::FETCH_ASSOC);

    //La contraseña se verifica contra el hash guardado con password_verify
    if ($usuario && password_verify($c, $usuario['Contraseña'])) {
        $id = $usuario['Id_usuario'];

        //Se busca al administrador SOLO por mail (ya no se manda la contraseña dentro del SQL)
        $res = $con->prepare("SELECT Id_usuario FROM administrador WHERE Id_usuario = ?");
        $res->execute([$id]);
        $admin = $res->fetch(PDO::FETCH_ASSOC);

        if ($admin) {

            session_start();

            session_regenerate_id(true); // previene session fixation

            $_SESSION['Usuario'] = true;
            $_SESSION['Rol'] = $usuario['Rol'];

            //Solo se devuelve lo que el JS necesita (exito y rol).
            echo json_encode(["exito" => true, "r" => $usuario['Rol']]);
        } else {

            echo json_encode(["exito" => false, "mensaje" => "No eres un Administrador"]);
        }
    } else {
        // Mismo mensaje si el mail no existe o la contraseña es incorrecta: no revela qué mails son admin
        echo json_encode(["exito" => false, "mensaje" => "Mail o contraseña incorrectos"]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage()); // el detalle queda en el log del servidor, no en el navegador
    echo json_encode(["exito" => false, "mensaje" => "Error en el servidor" . $e->getMessage()]);
}
