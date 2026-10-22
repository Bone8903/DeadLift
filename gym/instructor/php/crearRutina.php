<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "bdd.php";

// Con esto cualquier error de SQL salta como PDOException (y se atrapa abajo) en vez de fallar en silencio.
// El SET NAMES asegura que "Mañana" o "Miércoles" viajen bien entre PHP y MySQL.
$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$con->exec("SET NAMES utf8mb4");

// Si el servidor no tiene la extensión mbstring, mb_strlen no existe y el PHP se cae con un error 500
if (!function_exists('mb_strlen')) {
    function mb_strlen($texto) { return strlen($texto); }
}

// Corta la ejecución respondiendo un error en el formato que lee instructor.js
function fallar($mensaje) {
    echo json_encode(["exito" => false, "mensaje" => $mensaje]);
    exit;
}

// Mismo criterio que el resto del proyecto: se guarda el texto ya escapado
function limpiar($valor) {
    return trim(htmlspecialchars((string)$valor));
}

// Solo el instructor puede crear rutinas
if (!isset($_SESSION['Usuario']) || ($_SESSION['Rol'] ?? '') !== 'instructor') {
    http_response_code(403);
    fallar("No autorizado");
}

$idUsuario = isset($_SESSION['Id_usuario']) ? (int)$_SESSION['Id_usuario'] : 0;
if ($idUsuario <= 0) {
    fallar("No se pudo identificar al instructor, volvé a iniciar sesión");
}

// ---------- 1) Datos generales de la rutina ----------
// Los nombres de $_POST coinciden con los "name" del formulario de instructor.html
$nombre      = limpiar($_POST['Nombre_rutina'] ?? '');
$horario     = trim((string)($_POST['Horario'] ?? ''));
$descripcion = limpiar($_POST['Descripcion_rutina'] ?? '');

if ($nombre === '') {
    fallar("El nombre de la rutina es obligatorio");
}
if (!in_array($horario, ['Mañana', 'Tarde'], true)) { // dominio del atributo Horario según el documento de diseño
    fallar("El horario tiene que ser Mañana o Tarde");
}
// Largos máximos de la tabla Rutina: Nombre_rutina varchar(50), Descripcion_rutina varchar(255)
if (mb_strlen($nombre) > 50 || mb_strlen($descripcion) > 255) {
    fallar("Un campo es demasiado largo (nombre 50, descripción 255)");
}

// Cliente al que se le asigna la rutina (llega del <select name="Id_cliente"> de instructor.html)
$idCliente = filter_var($_POST['Id_cliente'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
if ($idCliente === false) {
    fallar("Tenés que elegir un cliente para asignarle la rutina");
}

// ---------- Aqui llegan los días y ejercicios (llegan como JSON en el campo "dias") ----------

$dias = json_decode($_POST['dias'] ?? '', true);
if (!is_array($dias) || count($dias) === 0) {
    fallar("La rutina necesita al menos un día con ejercicios");
}

$DIAS_VALIDOS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
if (count($dias) > count($DIAS_VALIDOS)) {
    fallar("Una rutina no puede tener más de 7 días");
}

$filas        = [];   // lista plana, ya validada, lista para insertar en Asigna
$diasVistos   = [];
$idsEjercicio = [];

//Esta perte de aca se encarga de fijarse si no se encuentran errores al crear las rutinas como espacios vacios, excesos de caracteres o simbolos invalidos

foreach ($dias as $dia) {
    $nombreDia = is_array($dia) ? (string)($dia['nombre'] ?? '') : '';
    if (!in_array($nombreDia, $DIAS_VALIDOS, true)) {//si el dia esta vacio, entonces avisa del error y no permite crear la rutina
        fallar("Hay un día con un nombre inválido");
    }
    if (isset($diasVistos[$nombreDia])) {
        fallar("El día $nombreDia está repetido");
    }
    $diasVistos[$nombreDia] = true;

    $ejercicios = $dia['ejercicios'] ?? null;
    if (!is_array($ejercicios) || count($ejercicios) === 0) {
        fallar("El día $nombreDia no tiene ningún ejercicio");
    }
    if (count($ejercicios) > 50) {
        fallar("El día $nombreDia tiene demasiados ejercicios (máximo 50)");
    }

    $ejerciciosDelDia = [];
    foreach ($ejercicios as $ej) {
        if (!is_array($ej)) {
            fallar("Hay un ejercicio con datos inválidos");
        }

        // El ejercicio es obligatorio (flujo alternativo A1 del CU03)
        $idEjercicio = filter_var($ej['id'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if ($idEjercicio === false) {
            fallar("Hay un ejercicio sin elegir en el día $nombreDia");
        }
        if (isset($ejerciciosDelDia[$idEjercicio])) { // recomendación del documento: sin filas duplicadas por día
            fallar("Un mismo ejercicio no puede repetirse en el día $nombreDia");
        }
        $ejerciciosDelDia[$idEjercicio] = true;

        // Series y repeticiones son opcionales, pero si vienen tienen que ser enteros entre 1 y 999 (columnas int(3))
        $numeros = [];
        foreach (['series' => 'Series', 'repeticiones' => 'Repeticiones'] as $clave => $etiqueta) {
            $valor = $ej[$clave] ?? null;
            if ($valor === null || $valor === '') {
                $numeros[$clave] = null;
                continue;
            }
            $entero = filter_var($valor, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 999]]);
            if ($entero === false) {
                fallar("$etiqueta tiene que ser un número entero entre 1 y 999 (día $nombreDia)");
            }
            $numeros[$clave] = $entero;
        }

        $notas = limpiar($ej['notas'] ?? '');
        if (mb_strlen($notas) > 255) {
            fallar("Una nota supera los 255 caracteres (día $nombreDia)");
        }

        $idsEjercicio[$idEjercicio] = true;
        $filas[] = [
            "dia"          => $nombreDia,
            "ejercicio"    => $idEjercicio,
            "series"       => $numeros['series'],
            "repeticiones" => $numeros['repeticiones'],
            "notas"        => $notas === '' ? null : $notas,
        ];
    }
}

// ---------- Se guarda todo lo que el usuario escribio en la rutina ----------
try {
    $con->beginTransaction();

    // El usuario elegido tiene que existir y ser realmente un cliente (no se confía en lo que manda el navegador)
    $qc = $con->prepare("SELECT 1 FROM Usuario WHERE Id_usuario = ? AND Rol = 'cliente'");
    $qc->execute([$idCliente]);
    if (!$qc->fetch()) {
        $con->rollBack();
        fallar("El usuario elegido no es un cliente");
    }

    // Los ejercicios elegidos tienen que existir de verdad en la base
    $ids    = array_keys($idsEjercicio);
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $q = $con->prepare("SELECT COUNT(*) FROM Ejercicio WHERE Id_ejercicio IN ($marcas)");
    $q->execute($ids);
    if ((int)$q->fetchColumn() !== count($ids)) {
        $con->rollBack();
        fallar("Alguno de los ejercicios elegidos ya no existe. Recargá la página e intentá de nuevo");
    }

    // Asigna.Nombre_dia es FK de Dia.Nombre_dia: se asegura que los días usados estén cargados (si ya existen, no hace nada)
    $dia = $con->prepare("INSERT IGNORE INTO Dia (Nombre_dia) VALUES (?)");
    foreach (array_keys($diasVistos) as $nombreDia) {
        $dia->execute([$nombreDia]);
    }

    // La rutina, junto con el instructor que la creó
    $rut = $con->prepare("INSERT INTO Rutina (Nombre_rutina, Horario, Descripcion_rutina, Id_usuario) VALUES (?, ?, ?, ?)");
    $rut->execute([$nombre, $horario, $descripcion, $idUsuario]);
    $idRutina = (int)$con->lastInsertId();

    // Cada fila va a Asigna (día + ejercicio + series/repeticiones/notas) y se vincula a la rutina en Rutina_agregacion
    $asigna = $con->prepare("INSERT INTO Asigna (Nombre_dia, Id_ejercicio, Repeticiones, Notas, Series) VALUES (?, ?, ?, ?, ?)");
    $vinculo= $con->prepare("INSERT INTO Rutina_agregacion (Id_rutina, Id_asigna) VALUES (?, ?)");

    // Se asigna la rutina al cliente: Cliente_rutina.Id_usuario = cliente, Cliente_rutina.Id_rutina = rutina recién creada
    $rutina = $con->prepare("INSERT INTO Cliente_rutina (Id_usuario, Id_rutina) VALUES (?, ?)");
    $rutina->execute([$idCliente, $idRutina]);

    foreach ($filas as $f) {
        $asigna->execute([$f['dia'], $f['ejercicio'], $f['repeticiones'], $f['notas'], $f['series']]);
        $vinculo->execute([$idRutina, (int)$con->lastInsertId()]);
    }

    $con->commit();

    echo json_encode([
        "exito"     => true,
        "mensaje"   => "La rutina se registró de manera correcta",
        "id_rutina" => $idRutina
    ]);

} catch (PDOException $e) {
    if ($con->inTransaction()) $con->rollBack();
    error_log($e->getMessage()); // el detalle queda en el log del servidor, no en el navegador
    fallar("No se guardó la rutina");
}
?>