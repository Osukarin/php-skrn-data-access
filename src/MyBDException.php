<?php

/**
 * Identifica una excepción lanzada en el programa
 *
 * @author Oscar Estuardo de la Mora Hernández
 * @since 1.0
 * @version 2.1
 * @package myskrn
 */
class MyBDException extends Exception {
    public static $VALIDACION = 1;
    public static $NO_NOMBRE_LOG = 2;
    public static $NO_PRI_KEY = 3;
    public static $NO_TABLA_HIJA = 4;

    private $__clase = "";
    public $__originalMessage;
    
    public function __construct($message, $code, $clase = null, $originalMsg = null) {
        $this->message = $message;
        $this->code = $code;
        $this->__clase = $clase;
        $this->__originalMessage = $originalMsg;
    }
    
    public function getClase(){
        return $this->__clase;
    }
}
