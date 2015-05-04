<?php

/*
spl_autoload_register(function ( $class ) {
    $class = str_replace('\\', '/', $class);
    $file = '../Components/'.$class.'.php';
    if (file_exists($file)) {
        require_once($file);
    }
});
*/

require('../vendor/autoload.php');

