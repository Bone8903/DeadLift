<?php
    session_start(); //session_start sirve para hacer que la funcion de $_SESSION pueda ser usada de manera correcta. Sin el no se podria usar sessions
    session_destroy(); // previene session fixation
    header("Location: ../index.html"); //Ese envía al usuario devuelta al index
?>