<?php
    session_start(); // El session_start sirve para inicar el uso de funciones como $_SESSION
    session_destroy(); // Destruye o borra cualquier sesion ya existente dentro del sistema
    header("Location: ../../index.html"); // Lo regresa al usuario al indice
?>
?>