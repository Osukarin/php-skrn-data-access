<?php 
include_once 'MyConeccion.php';
include_once 'MyDataSet.php';
include_once 'DataTable.php';
include_once 'MyBD.php';
include_once 'MyBDException.php';
/**
 * Clase encargada del acceso a datos mediante consultas y la ejecuciÃ³n de transacciones
 * 
 * @author              Oscar Estuardo de la Mora HernÃ¡ndez
 * @version             2.1
 * */
class MyBDQuery extends MyBD{

    public function __construct() {
        $this->setConnection();
    }

    public function setConnection(){
        $this->__host = "localhost";
        $this->__user = "root";
        $this->__pass = "";
        $this->__db = "";
        $this->__charset = "utf8";
    }
    
    public function setAlterConnection($host, $user, $pass, $db = null, $charset = null){
        $this->__host = $host;
        $this->__user = $user;
        $this->__pass = $pass;
        $this->__db = $db;
        $this->__charset = $charset;
    }
    
    /**
     * @access public
     * @var array Arreglo que almacena los parÃ¡metros a filtrar y a consultar
     */
    public $__params;
    
    
    /**
     * @access public
     * Método que escapa las comillas y doble comilla y quita los tags de las variables
     * 
     * @param $excepciones array|string Arreglo o string simple que lleva el nombre de un Ã­ndice para que no sea filtrado
     */
    public function sanitize($excepciones = null) {
        if(is_array($this->__params)){
            foreach($this->__params as $key => &$param){
                
                if($excepciones == null){
                    /// elimina los tags que se ingresen en un input
                    $param = filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                    ///aplica slash al string
                    $param = filter_var($param, FILTER_SANITIZE_MAGIC_QUOTES);
                }else{
                    if(is_array($excepciones)){
                        if(!array_key_exists($key, $excepciones)){
                            $param = filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                            $param = filter_var($param, FILTER_SANITIZE_MAGIC_QUOTES);
                        }
                    }else{
                        if($key != $excepciones){
                            $param = filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                            $param = filter_var($param, FILTER_SANITIZE_MAGIC_QUOTES);
                        }
                    }
                }
            }
        }else{
            $this->__params = filter_var($this->__params, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $this->__params = filter_var($this->__params, FILTER_SANITIZE_MAGIC_QUOTES);
        }

    }
    
    /**
     * Método que crea una coneccion a la base de datos
     * 
     * @return MyConeccion
     */
    public function getConnection(){
        $conn = new MyConeccion();
        return $conn->createConeccion($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
    }


    /**
     * Método encargado de ejecutar una consulta y retornar un objeto DataSet con el resultado
     * 
     * @param string query  	Consulta que requiere la obtenciÃ³n de muchos valores
     * 
     * @return DataSet              Objeto DataSet donde vienen todos los valores requeridos en la consulta
     * */
    public function toDataSet($query){
        try{
            $conn = $this->getConnection();
        }catch(MyBDException $ex){
            throw $ex;
        }
        if(function_exists('mysqli_connect'))
            $result = mysqli_query($conn, $query);
        else
            $result = mysql_query($query, $conn);
        if(!$result){
            if(function_exists('mysqli_connect'))
                throw new MyBDException(mysqli_error($conn), mysqli_errno($conn));
            else
                throw new MyBDException(mysql_error(), mysql_errno());
        }
        if(function_exists('mysqli_connect'))
            mysqli_close($conn);
        else
            mysql_close($conn);
        return $data = new DataSet($result);
    }
    
    public function toDataTable($query, $name = "default"){
        $conn = null;
        try{
            $conn = $this->getConnection();
        }catch(MyBDException $ex){
            throw $ex;
        }
        $result = mysqli_query($conn, $query);
        if(!$result){
            throw new MyBDException(mysqli_error($conn), mysqli_errno($conn));
        }
        mysqli_close($conn);
        
        return $data = new DataTable($result, $name);
    }
    
    /**
     * Ejecuta una consulta y retorna los resultados en forma de result
     * 
     * @param string query  	Consulta que requiere la obtenciÃ³n de muchos valores
     * 
     * @return Resource
     */
    public function getResource($query){
        try{
            $conn = $this->getConnection();
        }catch(MyBDException $ex){
            throw $ex;
        }
        if(function_exists('mysqli_connect'))
            $result = mysqli_query($conn, $query);
        else
            $result = mysql_query($query, $conn);
        
        if(!$result){
            if(function_exists('mysqli_connect'))
                throw new MyBDException(mysqli_error($conn), mysqli_errno($conn));
            else
                throw new MyBDException(mysql_error(), mysql_errno());
        }
        if(function_exists('mysqli_connect'))
            mysqli_close($conn);
        else
            mysql_close($conn);
        return $result;
    }
	
	/**
	 * Método encargado de ejecutar una consulta y retornar un string como una lista de opciones para un select de HTML
	 * 
	 * @param string query  	Consulta que requiere la conversion a una lista de opciones, en la consulta debe ir obligatoriamente
	 * 				para el id del option que la llave lleve el nombre "id" y que el valor a mostrar lleve el nombre de "nombre"
         *                              para mostrar un tool tip debe llevar el campo "descripcion"
	 * 
	 * @return string               Retorna un string en forma de lista de opciones para el select HTML
	 */
	public function toOptionList($query){
        try{
            $conn = $this->getConnection();
        }catch(MyBDException $ex){
            throw $ex;
        }
        if(function_exists('mysqli_connect'))
            $result = mysqli_query($conn, $query);
        else
            $result = mysql_query($query, $conn);
        if(!$result){
            if(function_exists('mysqli_connect'))
                throw new MyBDException(mysqli_error($conn), mysqli_errno($conn));
            else
                throw new MyBDException(mysql_error(), mysql_errno());
        }
		$opciones = "";
        if(function_exists('mysqli_connect')){
            while ($dataSet = mysqli_fetch_array($result)){
                if(array_key_exists("descripcion", $dataSet))
                    $opciones .= '<option title="'.$dataSet['descripcion'].'" value="'.$dataSet['id'].'">'.$dataSet['nombre'].'</option>';
                else
                    $opciones .= '<option value="'.$dataSet['id'].'">'.$dataSet['nombre'].'</option>';
            }
            mysqli_close($conn);
        }else{
            while ($dataSet = mysql_fetch_array($result)){
                if(array_key_exists("descripcion", $dataSet))
                    $opciones .= '<option title="'.$dataSet['descripcion'].'" value="'.$dataSet['id'].'">'.$dataSet['nombre'].'</option>';
                else
                    $opciones .= '<option value="'.$dataSet['id'].'">'.$dataSet['nombre'].'</option>';
            }
            mysql_close($conn);
        }
		return $opciones;
	}
	
	/**
	 * Método que retorna un dato numerico
	 * 
	 * @param string query		Consulta que requiere la devolucion de un dato numerico, para que funcione obligatoriamente el campo devuelto
	 * 				debe tener el nombre de "cuantos"
	 * 
	 * @return var			Retorna un nÃºmero entero
	 * */
	public function howMany($query){
        try{
            $conn = $this->getConnection();
        }catch(MyBDException $ex){
            throw $ex;
        }
        if(function_exists('mysqli_connect'))
            $result = mysqli_query($conn, $query);
        else
            $result = mysql_query($query, $conn);
        if(!$result){
            if(function_exists('mysqli_connect'))
                throw new MyBDException(mysqli_error($conn), mysqli_errno($conn));
            else
                throw new MyBDException(mysql_error(), mysql_errno());
        }
        if(function_exists('mysqli_connect')){
            mysqli_close($conn);
            $dataSet = mysqli_fetch_array($result);
        }else{
            mysql_close($conn);
            $dataSet = mysql_fetch_array($result);
        }
        
        return $dataSet["cuantos"];
            
	}
	
	/**
	 * Método que regresa de una consulta un solo dato, perfecto cuando se requiere todos los campos para un registro con una llave
	 * en especÃ­fico
	 * 
	 * @param query		Consulta que debe traer un solo registro de una tabla de la base de datos
	 * 
	 * @return			Arreglo de datos de una consulta
	 * */
	public function getOneRow($query){
            try{
                $conn = $this->getConnection();
            }catch(MyBDException $ex){
                throw $ex;
            }
            if(function_exists('mysqli_connect'))
                $result = mysqli_query($conn, $query);
            else
                $result = mysql_query($query, $conn);
        
            if(!$result){
                if(function_exists('mysqli_connect'))
                    throw new MyBDException(mysqli_error($conn), mysqli_errno($conn));
                else
                    throw new MyBDException(mysql_error(), mysql_errno());
            }
            if(function_exists('mysqli_connect')){
                $dataSet = mysqli_fetch_array($result);
                mysqli_close($conn);
            }else{
                $dataSet = mysql_fetch_array($result);
                mysql_close($conn);
            }
            if(!$dataSet)
                return null;
            
            foreach ($dataSet as $clave => $valor){
                if(is_int($clave)){
                    unset($dataSet[$clave]);
                }
            }
            return $dataSet;
	}
	
	/**
	 * Método que ejecuta una instruccion que no es consulta
	 * 
	 * @param string query		Consulta a ejecutar
	 * 
	 * @return boolean		Si fue ejecutada retorna true, si no, false
	 * */
	public function execQuery($query){
        $conn = $this->getConnection();
        if(function_exists('mysqli_connect')){
            $result = mysqli_query($query, $conn) or die(mysqli_error($conn));
            mysqli_close($conn);
        }else{
            $result = mysql_query($query, $conn) or die(mysql_error());
            mysql_close($conn);
        }
		if($result)
			return true;
		else 
			return false;
	}
	
	/**
	 * Método que ejecuta transacciones o un conjunto de instrucciones en modo transaccional
	 * @throws				Lanza una excepcion si una consulta es invÃ¡lida
	 * 
	 * @param Array <string> querys 		Conjunto de instrucciones SQL que se desea ejecutar en una transaccion
	 * 
	 * @return boolean      			Si se ejecutan exitosamente retorna true, si hubiera un error lanza una excepcion
	 * */
	public function execTransacts($querys){
        $conn = $this->getConnection();
        if(function_exists('mysqli_connect')){
            mysqli_query($conn, "SET AUTOCOMMIT=0;");
            mysqli_query($conn, "BEGIN;");
            foreach($querys as &$query){
                if(!mysqli_query($query, $conn)){
                    $no = mysqli_errno($conn);
                    mysqli_query($conn, "ROLLBACK;");
                    mysqli_close($conn);
                    throw new Exception($no);
                }
            }

            mysqli_query("COMMIT;", $conn);
            mysqli_close($conn);
        }else{
            mysql_query("SET AUTOCOMMIT=0;", $conn);
            mysql_query("BEGIN;", $conn);
            foreach($querys as &$query){
                if(!mysql_query($query, $conn)){
                    $no = mysql_errno($conn);
                    mysql_query("ROLLBACK;", $conn);
                    mysql_close($conn);
                    throw new Exception($no);
                }
            }

            mysql_query("COMMIT;", $conn);
            mysql_close($conn);
        }
		return true;
	}
        
        protected function autoPage($query){
            $actual = $this->__params['page'];
            $xpage = $this->__params['xpage'];
            $inicio = $xpage*($actual-1);
            $pageQuery = "SELECT * FROM (".$query.") consulta LIMIT ".$inicio.", ".$xpage;
            
            return $pageQuery;
        }
        
        protected function autoCount($query){
            return $this->howMany("SELECT count(*) cuantos FROM (".$query.") cuantos");
        }
}
?>
