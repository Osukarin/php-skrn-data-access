<?php
include_once 'MyBDException.php';

/**
* Clase que permite la coneccion hacia una base de datos MySQL
*
* @author Oscar Estuardo de la Mora
* @since 1.0
* @version 2.1
* @package myskrn
*/
class MyConeccion{

    /**
     * @access private
     * @var resource Variable que representa una conección a la base de datos
     */
        private $conn = null;
        /**
         * Método que permite la coneccion con la base de datos
         * 
         * @param string $host              Nombre del host donde se encuentra la base de datos
         * @param string $user              Nombre del usuario de la coneccion
         * @param string $pass              Contraseña del usuario asociado a la coneccion
         * @param string $database          Nombre de la base de datos
         * @param string $charset           Nombre del juego de caracteres
         * 
         * @return resource                 Coneccion a base de datos MySQL
         */
        
	public function createConeccion($host, $user, $pass, $database = null, $charset = null){
        if(function_exists('mysqli_connect'))
            $this->conn = mysqli_connect($host, $user, $pass);
        else
            $this->conn = mysql_connect($host, $user, $pass);
        
        if(!$this->conn){
            if(function_exists('mysqli_connect'))
                throw new MyBDException(mysqli_error(), mysqli_errno());
            else
                throw new MyBDException(mysql_error(), mysql_errno());
        }
        
        if($database != null){
            if(function_exists('mysqli_connect'))
                mysqli_select_db($this->conn, $database);
            else
                mysql_select_db($database, $this->conn);
        }
        if($charset != null){
            if(function_exists('mysqli_connect'))
                mysqli_set_charset($this->conn, $charset);
            else
                mysql_set_charset($charset, $this->conn);
        }
		return $this->conn;
	}
        
        public function setDataBase($database){
            if(function_exists('mysqli_connect'))
                mysqli_select_db($this->conn, $database);
            else
                mysql_select_db($database, $this->conn);
        }
        
        public function setCharSet($charset){
            if(function_exists('mysqli_connect'))
                mysqli_set_charset($this->conn, $charset);
            else
                mysql_set_charset($charset, $this->conn);
        }
}

?>
