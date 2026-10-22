<?php
    session_start(); //session_start sirve para hacer que la funcion de $_SESSION pueda ser usada de manera correcta. Sin el no se podria usar sessions
    session_destroy(); //destruye cualquier session activa en el sistema 
    header("Location: ../../index.html"); //redirige al usuario hacia index.html (narnia)
?>