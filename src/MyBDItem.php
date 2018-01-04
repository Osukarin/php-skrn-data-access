<?php 
include_once 'MyBDQuery.php';
include_once 'MyConeccion.php';
include_once 'MyBitacora.php';
include_once 'MyBD.php';
include_once 'MyBDException.php';

/**
 * Clase que contiene la lógica transaccional. Esta permite la inserción, eliminación y actualización de datos en la base de datos.
 * Así tambien el control de la bitácora de tercer nivel
 * 
 * @author Oscar Estuardo de la Mora Hernández
 * @since 1.0
 * @version 2.1
 * @package myskrn
 * 
 * */

class MyBDItem extends MyBD{

    public static $NULL = 0;
    public static $OUT_LENGTH = 1;
    public static $NOT_NUMBER = 2;
    
    public static $INSERT = 0;
    public static $UPDATE = 1;
    public static $DELETE = 2;
    public static $LOAD = 3;
    
    public $__logs = array();
    
    private $__tabla = "";
    private $__campo = "";
    private $__valor = "";
    private $__keyAutoInc = "";
    private $__extraXML = array();
    private $__getlogs = false;
    private $__tablahija = null;
    private $__anterior = Array();
    private $__llaves = "";
    
    protected $__mensajes = Array();
    protected $__logEnabled = true;
    protected $__metaFile = null;
    protected $__metadata = null;
    
    public function __construct($tabla = null, $tablahija = null) {
        $this->__tabla = $tabla;
        $this->__tablahija = $tablahija;
        $this->setConnection();
        $this->__metadata = $this->getMetaData();
    }
    
    public function setConnection(){
        $this->__host = "localhost";
        $this->__user = "root";
        $this->__pass = "";
        $this->__db = "";
        $this->__charset = "utf8";
    }
    
    public function getNombreEsquema(){
        return $this->__db;
    }
    
    public function getNombreEsquemaLog(){
        return "";
    }
    
    /**
     * @deprecated since version 2.0
     */
    public function getNombreTabla(){
        return $this->__tabla;
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
    
    public function metadata($tables){
        $tablas = "";
        if(is_array($tables)){
            for($i = 0; $i < count($tables); $i++){
                if($tablas == "")
                    $tablas = "'" . $tables[$i] . "'";
                else
                    $tablas .= ", '" . $tables[$i] . "'";
            }
        }else{
            $tablas = $tables;
        }
        
        $query = "SELECT column_name columna, data_type tipo, column_key llave, extra, CHARACTER_MAXIMUM_LENGTH longitud, numeric_precision preci, IS_NULLABLE nulo, table_name tabla
            FROM information_schema.columns
            WHERE table_schema = '".$this->getNombreEsquema()."' AND table_name IN (" . $tablas . ")";
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        return $querying->toDataTable($query);
    }
    /**
     * @access protected
     * Obtiene los datos de una tabla en un esquema
     * 
     * @return DataSet  Colección de datos consultados
     */
    private function getMetaData(){
        if($this->__metaFile != null){
            if(!file_exists($this->__metaFile))
                $this->MapearXML();
                
            $dom = new DOMDocument();
            $dom->load($this->__metaFile);
            $tablas = $dom->getElementsByTagName("tabla");
            $filas = Array();
            foreach($tablas as $tabla){
                $tablaStr = $tabla->getAttribute("nombre");
                if($tablaStr != $this->__tabla)
                    continue;
                $campos = $tabla->getElementsByTagName("campo");
                foreach($campos as $campo){
                    $campoStr = $campo->getAttribute("nombre");
                    $attrs = $campo->getElementsByTagName("atributo");
                    $fila = Array('tabla' => $tablaStr, 'columna' => $campoStr);
                    foreach($attrs as $attr){
                        $attrStr = $attr->getAttribute("nombre");
                        $fila[$attrStr] = $attr->nodeValue;
                    }
                    $filas[] = $fila;
                }
            }
            $ds = new DataSet();
            $ds->data = $filas;
            return $ds;
        }
        
        $query = "SELECT column_name columna, data_type tipo, column_key llave, extra, CHARACTER_MAXIMUM_LENGTH longitud, numeric_precision preci, IS_NULLABLE nulo
            FROM information_schema.columns
            WHERE table_schema = '".$this->getNombreEsquema()."' AND table_name = '".$this->__tabla."'";
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        return $querying->toDataSet($query);
    }
    
    public function MapearXML(){
        $query = "SELECT table_name tabla, column_name columna, data_type tipo, column_key llave, extra, CHARACTER_MAXIMUM_LENGTH longitud, numeric_precision preci, IS_NULLABLE nulo
            FROM information_schema.columns
            WHERE table_schema = '".$this->getNombreEsquema()."' AND table_name NOT IN ('log_estructura', 'log_bitacora', 'log_operacion')";
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        $dataset = $querying->toDataSet($query);
        $dom = new DOMDocument();
        $esquema = $dom->appendChild($dom->createElement("esquema"));
        $esquema->setAttribute("nombre", $this->getNombreEsquema());
        $tablas = Array();
        foreach($dataset->data as $row){            
            $tablas[$row['tabla']][$row['columna']]['tipo'] = $row['tipo'];
            $tablas[$row['tabla']][$row['columna']]['llave'] = $row['llave'];
            $tablas[$row['tabla']][$row['columna']]['extra'] = $row['extra'];
            $tablas[$row['tabla']][$row['columna']]['longitud'] = $row['longitud'];
            $tablas[$row['tabla']][$row['columna']]['preci'] = $row['preci'];
            $tablas[$row['tabla']][$row['columna']]['nulo'] = $row['nulo'];
        }
        
        foreach($tablas as $keyT => $tabla){
            $tablaXML = $esquema->appendChild($dom->createElement("tabla"));
            $tablaXML->setAttribute("nombre", $keyT);
            foreach($tabla as $keyC => $campo){
                $campoXML = $tablaXML->appendChild($dom->createElement("campo"));
                $campoXML->setAttribute("nombre", $keyC);
                foreach($campo as $keyA => $atributo){
                    $attrXML = $campoXML->appendChild($dom->createElement("atributo"));
                    $attrXML->setAttribute("nombre", $keyA);
                    $attrXML->appendChild($dom->createTextNode($atributo));
                }
            }
        }
        
        $dom->save($this->__metaFile);
    }
    
    public function GetReferidas(){
        $query = "SELECT llaves.table_name tabla, referenced_column_name campo_uno, column_name campo_muchos FROM information_schema.table_constraints constra
                    INNER JOIN information_schema.key_column_usage llaves ON constra.constraint_name = llaves.constraint_name
                    WHERE constra.constraint_type = 'FOREIGN KEY' AND constra.table_schema = '".$this->getNombreEsquema()."' AND llaves.referenced_table_name = '".$this->__tabla."'";
        
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        return $querying->toDataSet($query);
    }
    
    /**
     * @access private
     * 
     * Método que se encarga de escapar comillas, validar longitudes, validar números y demás
     * operaciones de seguridad
     * 
     * @param $meta Array       Metadata de la base de datos para hacer las comparaciones y validaciones
     * @param $data Array       Datos que se van a validar
     */
    private function validar($meta, &$data, $operacion, $excepciones = null){
        
        if($operacion == self::$UPDATE){
            $this->__llaves = $this->getKeys($meta, $data);
            $querying = new MyBDQuery();
            $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
            $this->__anterior = $querying->getOneRow("SELECT * FROM ".$this->__tabla." WHERE ".$this->__llaves);
        }
        
        $mensajes = Array();
        $mensajesHijos = Array();
        if(array_key_exists($this->__tabla, $this->__mensajes))
            $mensajes = $this->__mensajes[$this->__tabla];
        
        if($this->__tablahija != null){
            if(array_key_exists($this->__tablahija, $mensajes))
                    $mensajesHijos = $mensajes[$this->__tablahija];
            else
                throw new MyBDException("La tabla hija no está registrada", MyBDException::$NO_TABLA_HIJA);
        }
        
        foreach($meta->data as $row){
            if(array_key_exists($row['columna'], $data)){
                
                if($excepciones != null)
                    if(in_array($row['columna'], $excepciones))
                        continue;
               
                if($data[$row['columna']] == null && $row['nulo'] == "NO" && !array_key_exists($this->__tabla, $this->__mensajes)){
                    throw new MyBDException("el campo ".$row['columna']." debe llevar un valor", MyBDException::$VALIDACION, 'warning');
                }elseif($data[$row['columna']] == null){// && $row['nulo'] == "NO"){
                    $this->VerificaHijos($row, $mensajes, $mensajesHijos, self::$NULL);
                }
                
                if($data[$row['columna']] == 'null' && $row['nulo'] == 'YES'){
                    continue;
                }
                
                if($row['tipo'] == 'varchar' || $row['tipo'] == 'text'){
                    $data[$row['columna']] = filter_var($data[$row['columna']], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                    $data[$row['columna']] = filter_var($data[$row['columna']], FILTER_SANITIZE_MAGIC_QUOTES);
                    
                    if(strlen($data[$row['columna']]) > $row['longitud']){
                        $this->VerificaHijos($row, $mensajes, $mensajesHijos, self::$OUT_LENGTH);
                        throw new MyBDException("La longitud de ".$row['columna']." es mas grande que el tamaño maximo: ".$row['longitud'], MyBDException::$VALIDACION);
                    }
                }elseif($row['tipo'] == 'int' || $row['tipo'] == 'decimal'){
                    if($data[$row['columna']] == '' && $row['nulo'] == 'YES')
                        $data[$row['columna']] = "null";
                    elseif($data[$row['columna']] == '' && $row['nulo'] == 'NO')
                        $data[$row['columna']] = "0";
                    elseif($data[$row['columna']] == '0.0')
                        continue;
                    elseif(!filter_var($data[$row['columna']], FILTER_VALIDATE_FLOAT)){
                        $this->VerificaHijos($row, $mensajes, $mensajesHijos, self::$NOT_NUMBER);
                        throw new MyBDException("El dato de ".$row['columna']." no es un número válido", MyBDException::$VALIDACION);
                    }
                }elseif($row['tipo'] == 'date' || $row['tipo'] == 'datetime' || $row['tipo'] == 'time'){
                    $data[$row['columna']] = filter_var($data[$row['columna']], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                    $data[$row['columna']] = filter_var($data[$row['columna']], FILTER_SANITIZE_MAGIC_QUOTES);
                    
                }//elseif($row['tipo'] == 'blob'){}
                
            }else{
                if($operacion != self::$DELETE || $operacion != self::$LOAD){
                    if($operacion == self::$UPDATE){
                        
                    }elseif($operacion == self::$INSERT){
                        $this->VerificaHijos($row, $mensajes, $mensajesHijos, self::$NULL);
                        if($row['nulo'] == 'NO' && ($row['llave'] != 'PRI' && $row['extra'] != 'auto_increment'))
                            throw new MyBDException("El campo ".$row['columna']." debe llevar un valor", MyBDException::$VALIDACION);
                    }
                }
            }
        }
    }
    
    private function VerificaHijos($row, &$mensajes, &$mensajesHijos, $tipoError){
        if($this->__tablahija != null){
            if(array_key_exists($row['columna'], $mensajesHijos)){
                $mensajetipos = $mensajesHijos[$row['columna']];
                if(array_key_exists($tipoError, $mensajetipos))
                    throw new MyBDException($mensajetipos[$tipoError]['msg'], MyBDException::$VALIDACION, $mensajetipos[$tipoError]['class']);
                
            }elseif(array_key_exists($row['columna'], $mensajes)){
                $mensajetipos = $mensajes[$row['columna']];
                if(array_key_exists($tipoError, $mensajetipos))
                        throw new MyBDException($mensajetipos[$tipoError]['msg'], MyBDException::$VALIDACION, $mensajetipos[$tipoError]['class']);
            }
        }elseif(array_key_exists($row['columna'], $mensajes)){
            $mensajetipos = $mensajes[$row['columna']];
            if(array_key_exists($tipoError, $mensajetipos)){
                throw new MyBDException($mensajetipos[$tipoError]['msg'], MyBDException::$VALIDACION, $mensajetipos[$tipoError]['class']);
            }
        }
    }
    
    public function save($data = null, $usuario = null, $excepciones = null){
        $meta = $this->__metadata;
        
        if($data == null)
            $data = (array)($this);
        elseif(!is_array($data)){
            $usuario = $data;
            $data = (array)($this);
        }
        
        try{
            $llaves = $this->getKeys($meta, $data);
            return $this->update($data, $usuario, $excepciones);
        }
        catch(MyBDException $ex){
            return $this->insert($data, $usuario, $excepciones);
        }
    }
    
    public function disable($data = null, $usuario = null){
        $meta = $this->__metadata;
        
        if($data == null)
            $data = (array)($this);
        elseif(!is_array($data)){
            $usuario = $data;
            $data = (array)($this);
        }
        
        $esEstado = false;
        foreach($meta->data as $row){
            if($row['columna'] == 'estado'){
                $esEstado = true;
                break;
            }
        }
        
        if($esEstado){
            $llaves = $this->getKeys($meta, $data);

            $querying = new MyBDQuery();
            $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
            $loaded = $querying->howMany("SELECT estado cuantos FROM ".$this->getNombreEsquema().".".$this->getNombreTabla()." WHERE ".$llaves);
            if($loaded == '1'){
                $data['estado'] = '0';
                $this->update($data, $usuario);
                return 'disabled';
            }else{
                $data['estado'] = '1';
                $this->update($data, $usuario);
                return 'enabled';
            }
                
        }else
            throw new MyBDException("El campo estado no existe en la tabla", MyBDException::$VALIDACION, "error");
    }
    
    public function remove($data, $usuario){
        try{
            return $this->delete($data, $usuario);
        }catch(Exception $ex){
            if($ex->getCode() == 1451)
                return $this->disable($data, $usuario);
        }
    }
    
    /**
     * @access public
     * Almacena un objeto en la base de datos
     * 
     * @param $data array       Colección de datos con los índices con los nombres de los campos de un registro de una tabla,
     *                          cada índice se relaciona con un campo y crea un atributo en el objeto
     * @param $usuario Integer  Identificador único del usuario insertando
     * 
     * @return Integer|boolean          Retorna el número autoincrementable si lo tuviera, o true si se insertó y no tiene autoincrementable
     */
    public function insert($data = null, $usuario = null, $excepciones = null){
        $meta = $this->__metadata;
        
        if($data == null)
            $data = (array)($this);
        elseif(!is_array($data)){
            $usuario = $data;
            $data = (array)($this);
        }
        
        $this->validar($meta, $data, self::$INSERT, $excepciones);
        $fecha = "STR_TO_DATE('".date("d/m/Y ").date("H:i:s")."','%d/%m/%Y %H:%i:%s')";
        
        foreach($meta->data as $row){
            if(array_key_exists($row['columna'], $data)){
                if($data[$row['columna']] == null)
                    continue;
                
                if($this->__campo != "")
                    $this->__campo .= ", ";
                if($this->__valor != "")
                    $this->__valor .= ", ";
                
                if($row['tipo'] == 'varchar' || $row['tipo'] == 'text' || $row['tipo'] == 'time' || $row['tipo'] == 'tinytext' || $row['tipo'] == 'timestamp'){
                    $this->__campo .= $row['columna'];
                    $this->__valor .= "'".$data[$row['columna']]."'";
                    
                }elseif($row['tipo'] == 'int' || $row['tipo'] == 'decimal'){
                    $this->__campo .= $row['columna'];
                    $this->__valor .= $data[$row['columna']];
                    
                }elseif($row['tipo'] == 'date' || $row['tipo'] == 'datetime'){
                    $this->__campo .= $row['columna'];
                    if($row['tipo'] == 'date')
                        $this->__valor .= "STR_TO_DATE('".$data[$row['columna']]."', '".$this->setDateFormat()."')";
                    elseif($row['tipo'] == 'datetime')
                        $this->__valor .= "STR_TO_DATE('".$data[$row['columna']]."', '".$this->setDateTimeFormat()."')";
                    
                }elseif($row['tipo'] == 'blob' || $row['tipo'] == 'longblob' || $row['tipo'] == 'mediumblob'){
                    $this->__campo .= $row['columna'];
                    $valor = $data[$row['columna']];
                    $this->__valor .= "'".addslashes($valor)."'";
                }
                    
                if($usuario != null && $this->__logEnabled){
                    if($this->getNombreEsquemaLog() == null)
                        throw new MyBDException("No ha colocado el nombre del esquema de la bitacora", MyBDException::$NO_NOMBRE_LOG);
                    $log = new MyBitacora($this->getNombreEsquemaLog());
                    if($row['tipo'] == 'blob' || $row['tipo'] == 'longblob' || $row['tipo'] == 'mediumblob')
                        $log->valor_actual = base64_encode($data[$row['columna']]);
                    else
                        $log->valor_actual = $data[$row['columna']];
                    $log->id_estructura = $this->VerificaEsquema($this->getFieldId($row['columna']), $row['columna']);
                    $log->id_operacion = $this->getOperationId("Insertar");
                    $log->id_usuario = $usuario;
                    $log->fecha = $fecha;
                    $this->__logs[] = $log;
                }
            }
            
            if($row['extra'] == 'auto_increment'){
                $this->VerificaEsquema($this->getFieldId($row['columna']), $row['columna']);
                $this->__keyAutoInc = $row['columna'];
            }
        }
        
        if($usuario != null && $this->__logEnabled){
            $strLogs = Array();
            $strLogs[] = $this->insertToString();
            
            foreach($this->__logs as $log)
                $strLogs[] = $log->createLog();
            
            if($this->__getlogs)
                return $strLogs;
            
            return $this->execTransact($strLogs, $this->__keyAutoInc == null ? false : true, $fecha, $usuario);
            
        }else{
            $query = $this->insertToString();
            if($this->__getlogs)
                return Array($query);
            
            $conn = $this->getConnection();
            if(function_exists('mysqli_connect'))
                $result = mysqli_query($conn, $query);
            else
                $result = mysql_query($query, $conn);
            
            if(!$result){
                if(function_exists('mysqli_connect')){
                    $myerror = mysqli_error($conn);
                    $myerrno = mysqli_errno($conn);
                    $errclas = null;
                    $mensajes = null;
                    $originalMsg = null;
                    if(array_key_exists($this->__tabla, $this->__mensajes)){
                        $mensajes = $this->__mensajes[$this->__tabla];
                        $sqlerr = null;
                        if(array_key_exists('sqlerr', $mensajes)){
                            $sqlerr = $mensajes['sqlerr'];
                            $errmsg = null;
                            if(array_key_exists($myerrno, $sqlerr)){
                                $errmsg = $sqlerr[$myerrno];
                                $originalMsg = $myerror;
                                if(array_key_exists('fmsg', $errmsg))
                                    $myerror = $errmsg['fmsg']($originalMsg);
                                else
                                    $myerror = $errmsg['msg'];
                                $errclas = $errmsg['class'];
                            }
                        }
                    }
                    throw new MyBDException($myerror, $myerrno, $errclas, $originalMsg);
                }else
                    throw new MyBDException(mysql_error(), mysql_errno());
            }
            if($this->__keyAutoInc){
                if(function_exists('mysqli_connect'))
                    $ident = mysqli_insert_id($conn);
                else
                    $ident = mysql_insert_id($conn);
            }else 
                return true;
            
            if(function_exists('mysqli_connect'))
                mysqli_close($conn);
            else
                mysql_close($conn);
            if($result)
                return $ident;
            else 
                return false;
        }
    }
    
    private function VerificaEsquema($idcol, $columna){
        if($idcol == null){
            $idtabla = $this->TablaExiste();
            if($idtabla == null){
                $idesquema = $this->EsquemaExiste();
                if($idesquema == null){
                    $conn = $this->getConnection();
                    if(function_exists('mysqli_connect')){
                        mysqli_query($conn, 'INSERT INTO '.$this->getNombreEsquema().'.log_estructura(nombre) values ("'.$this->getNombreEsquema().'")');
                        $idesquema = mysqli_insert_id($conn);
                        mysqli_close($conn);
                    }else{
                        mysql_query('INSERT INTO '.$this->getNombreEsquema().'.log_estructura(nombre) values ("'.$this->getNombreEsquema().'")', $conn);
                        $idesquema = mysql_insert_id($conn);
                        mysql_close($conn);
                    }
                }
                $conn = $this->getConnection();
                if(function_exists('mysqli_connect')){
                    $result = mysqli_query($conn, 'INSERT INTO '.$this->getNombreEsquema().'.log_estructura(nombre, id_estructura) values ("'.$this->getNombreTabla().'", "'.$idesquema.'")');
                    $idtabla = mysqli_insert_id($conn);
                    mysqli_close($conn);
                }else{
                    $result = mysql_query('INSERT INTO '.$this->getNombreEsquema().'.log_estructura(nombre, id_estructura) values ("'.$this->getNombreTabla().'", "'.$idesquema.'")', $conn);
                    $idtabla = mysql_insert_id($conn);
                    mysql_close($conn);
                }
            }
            $conn = $this->getConnection();
            if(function_exists('mysqli_connect')){
                $result = mysqli_query($conn, 'INSERT INTO '.$this->getNombreEsquema().'.log_estructura(nombre, id_estructura) values ("'.$columna.'", "'.$idtabla.'")');
                $idcol = mysqli_insert_id($conn);
                mysqli_close($conn);
            }else{
                $result = mysql_query('INSERT INTO '.$this->getNombreEsquema().'.log_estructura(nombre, id_estructura) values ("'.$columna.'", "'.$idtabla.'")', $conn);
                $idcol = mysql_insert_id($conn);
                mysql_close($conn);
            }
        }
        return $idcol;
    }
    
    private function EsquemaExiste(){
        $query = 'SELECT esquema.id cuantos
			FROM '.$this->getNombreEsquemaLog().'.log_estructura esquema
			WHERE esquema.nombre = "'.$this->getNombreEsquema().'"';
        
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        return $querying->howMany($query);
    }
    
    private function TablaExiste(){
        $query = 'SELECT tabla.id cuantos
			FROM '.$this->getNombreEsquemaLog().'.log_estructura tabla
			INNER JOIN '.$this->getNombreEsquemaLog().'.log_estructura esquema ON tabla.id_estructura = esquema.id
			WHERE tabla.nombre = "'.$this->getNombreTabla().'" AND esquema.nombre = "'.$this->getNombreEsquema().'"';
        
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        return $querying->howMany($query);
    }
    
    public function insertToString(){
        return "INSERT INTO ".$this->getNombreEsquema().".".$this->getNombreTabla()."(".$this->__campo.") values(".$this->__valor.")";
    }
        
    public function updateToString($where){
        if($this->__campo == "")
            return null;
        else
            return "UPDATE ".$this->getNombreEsquema().".".$this->getNombreTabla()." SET ".$this->__campo." ".$where;
    }
        
    public function deleteToString($where){
        return "DELETE FROM ".$this->getNombreEsquema().".".$this->getNombreTabla()." ".$where;
    }
    
    /**
     * Método que obtiene la condición para buscar por llaves de una tabla un registro
     * @param $meta Array       Metadata de la base de datos para hacer las comparaciones y validaciones
     * @param $data Array       Datos que se van a validar
     * 
     * @return String           String con la condición
     */
    private function getKeys($meta, $data){
        $llaves = "";
        foreach($meta->data as $row){
            if($row['llave'] == 'PRI'){
                if(!array_key_exists($row['columna'], $data))
                    throw new MyBDException("La llave primaria no se encuentra en los datos", MyBDException::$NO_PRI_KEY);
                
                if($llaves != "")
                    $llaves .= ' AND ';
                
                $llaves .= $row['columna'].' = '.$data[$row['columna']];
            }
        }
        
        return $llaves;
    }
    
    /**
     * @access public
     * Actualiza un objeto en la base de datos
     * 
     * @param $data array       Colección de datos con los índices con los nombres de los campos de un registro de una tabla,
     *                          cada índice se relaciona con un campo y crea un atributo en el objeto
     * @param $usuario Integer  Identificador único del usuario insertando
     * 
     * @return boolean          Retorna  o true si se actualizó
     */
    public function update($data = null, $usuario = null, $excepciones = null){
        $meta = $this->__metadata;
        
        if($data == null)
            $data = (array)($this);
        elseif(!is_array($data)){
            $usuario = $data;
            $data = (array)($this);
        }
        
        $this->validar($meta, $data, self::$UPDATE, $excepciones);
        
        foreach($meta->data as $row){
            if(array_key_exists($row['columna'], $data)){
                if($this->__anterior[$row['columna']] != $data[$row['columna']]){
                    if($this->__campo != "")
                        $this->__campo .= ", ";
                    if($this->__valor != "")
                        $this->__valor .= ", ";

                    if($data[$row['columna']] == "null"){
                        $this->__campo .= $row['columna']." = null";
                    }elseif($row['tipo'] == 'varchar' || $row['tipo'] == 'text' || $row['tipo'] == 'time' || $row['tipo'] == 'tinytext' || $row['tipo'] == 'timestamp'){
                        $this->__campo .= $row['columna']." = '".$data[$row['columna']]."'";

                    }elseif($row['tipo'] == 'int' || $row['tipo'] == 'decimal'){
                        $this->__campo .= $row['columna']." = ".$data[$row['columna']];

                    }elseif($row['tipo'] == 'date' || $row['tipo'] == 'datetime'){
                        if($row['tipo'] == 'date')
                            $this->__campo .= $row['columna']." = STR_TO_DATE('".$data[$row['columna']]."','".$this->setDateFormat()."')";
                        elseif($row['tipo'] == 'datetime')
                            $this->__campo .= $row['columna']." = STR_TO_DATE('".$data[$row['columna']]."','".$this->setDateTimeFormat()."')";

                    }elseif($row['tipo'] == 'blob' || $row['tipo'] == 'longblob' || $row['tipo'] == 'mediumblob')
                        $this->__campo .= $row['columna']." = '".addslashes($data[$row['columna']])."' ";

                    if($usuario != null && $this->__logEnabled){
                        if($this->getNombreEsquemaLog() == null)
                            throw new MyBDException("No ha colocado el nombre del esquema de la bitacora", MyBDException::$NO_NOMBRE_LOG);
                        $log = new MyBitacora($this->getNombreEsquemaLog());
                        if($row['tipo'] == 'blob' || $row['tipo'] == 'longblob' || $row['tipo'] == 'mediumblob')
                            $log->valor_actual = base64_encode($data[$row['columna']]);
                        else
                            $log->valor_actual = $data[$row['columna']];
                        if($row['tipo'] == 'blob' || $row['tipo'] == 'longblob' || $row['tipo'] == 'mediumblob')
                            $log->valor_anterior = base64_encode($this->__anterior[$row['columna']]);
                        else
                            $log->valor_anterior = addslashes($this->__anterior[$row['columna']]);
                        
                        $log->id_estructura = $this->VerificaEsquema($this->getFieldId($row['columna']), $row['columna']);
                        $log->id_operacion = $this->getOperationId("Editar");
                        $log->id_usuario = $usuario;
                        $log->fecha = "STR_TO_DATE('".date('d/m/Y H:i:s')."', '%d/%m/%Y %H:%i:%s')";
                        $this->__logs[] = $log;
                    }
                }
            }
            
            if($usuario != null && $this->__logEnabled){
                if($row['llave'] == 'PRI'){
                    if($this->getNombreEsquemaLog() == null)
                        throw new MyBDException("No ha colocado el nombre del esquema de la bitacora", MyBDException::$NO_NOMBRE_LOG);
                    $log = new MyBitacora($this->getNombreEsquemaLog());
                    $log->valor_anterior = $data[$row['columna']];
                    $log->valor_actual = $data[$row['columna']];
                    $log->id_estructura = $this->getFieldId($row['columna']);
                    $log->id_operacion = $this->getOperationId("Editar");
                    $log->id_usuario = $usuario;
                    $log->fecha = "STR_TO_DATE ('".date('d/m/Y H:i:s')."', '%d/%m/%Y %H:%i:%s')";
                    $this->__logs[] = $log;
                }
            }
        }
        
        if($usuario != null && $this->__logEnabled){
            $query = $this->updateToString('WHERE '.$this->__llaves);
            $strLogs = Array();
            if($query != null){
                $strLogs[] = $query;
                if($this->__campo != ""){
                    foreach($this->__logs as &$log)
        		$strLogs[] = $log->createLog();
                    
                    if($this->__getlogs)
                        return $strLogs;

                    return $this->execTransact($strLogs);
                }
				return true;
            }
            return true;
		}
        
        $query = $this->updateToString('WHERE '.$this->__llaves);
        
        if($this->__getlogs){
            if($query == "")
                return Array();
            else
                return Array($query);
        }
        
        if($query != null){
            $conn = $this->getConnection();
            if(function_exists('mysqli_connect')){
                $result = mysqli_query($conn, $query);
                if(!$result){
                    $myerror = mysqli_error($conn);
                    $myerrno = mysqli_errno($conn);
                    mysqli_close($conn);
                    throw new MyBDException($myerror, $myerrno);
                }
                mysqli_close($conn);
            }else{
                $result = mysql_query($query, $conn);
                if(!$result){
                    mysql_close($conn);
                    throw new MyBDException(mysql_error(), mysql_errno());
                }
                mysql_close($conn);
            }
            if($result)
                return true;
            else
                return false;
        }
        return true;
        
    }
    
    /**
     * @access public
     * Elimina un objeto en la base de datos
     * 
     * @param $data array       Colección de datos con los índices con los nombres de los campos de un registro de una tabla,
     *                          cada índice se relaciona con un campo y crea un atributo en el objeto
     * @param $usuario Integer  Identificador único del usuario insertando
     * 
     * @return boolean          Retorna  o true si se actualizó
     */
    public function delete($data = null, $usuario = null){
        $meta = $this->__metadata;
        
        if($data == null)
            $data = (array)($this);
        elseif(!is_array($data)){
            $usuario = $data;
            $data = (array)($this);
        }
        
        $this->validar($meta, $data, self::$DELETE);
        $llaves = $this->getKeys($meta, $data);
        
        foreach($meta->data as $row){
            
            if($usuario != null && $this->__logEnabled){
                if($row['llave'] == 'PRI'){
                    if($this->getNombreEsquemaLog() == null)
                        throw new MyBDException("No ha colocado el nombre del esquema de la bitacora", MyBDException::$NO_NOMBRE_LOG);
                    $log = new MyBitacora($this->getNombreEsquemaLog());
                    $log->valor_anterior = $data[$row['columna']];
                    $log->valor_actual = $data[$row['columna']];
                    $log->id_estructura = $this->getFieldId($row['columna']);
                    $log->id_operacion = $this->getOperationId("Eliminar");
                    $log->id_usuario = $usuario;
                    $log->fecha = "STR_TO_DATE ('".date('d/m/Y H:i:s')."', '%d/%m/%Y %H:%i:%s')";
                    $this->__logs[] = $log;
                }
            }
        }
        $strLogs = Array();
        if($usuario != null && $this->__logEnabled){
            $strLogs[] = $this->deleteToString('WHERE '.$llaves);
            
            foreach($this->__logs as &$log)
                $strLogs[] = $log->createLog();
			
            if($this->__getlogs)
                return $strLogs;
            
            return $this->execTransact($strLogs);
        }
        
        $query = $this->deleteToString('WHERE '.$llaves);
        
        if($this->__getlogs)
            return Array($query);
        
        $conn = $this->getConnection();
        if(function_exists('mysqli_connect')){
            $result = mysqli_query($conn, $query);
            if(!$result){
                $myerror = mysqli_error($conn);
                $myerrno = mysqli_errno($conn);
                $errclas = null;
                $mensajes = null;
                $originalMsg = null;
                if(array_key_exists($this->__tabla, $this->__mensajes)){
                    $mensajes = $this->__mensajes[$this->__tabla];
                    $sqlerr = null;
                    if(array_key_exists('sqlerr', $mensajes)){
                        $sqlerr = $mensajes['sqlerr'];
                        $errmsg = null;
                        if(array_key_exists($myerrno, $sqlerr)){
                            $errmsg = $sqlerr[$myerrno];
                            $originalMsg = $myerror;
                            if(array_key_exists('fmsg', $errmsg))
                                $myerror = $errmsg['fmsg']($originalMsg);
                            else
                                $myerror = $errmsg['msg'];
                            $errclas = $errmsg['class'];
                        }
                    }
                }
                mysqli_close($conn);
                throw new MyBDException($myerror, $myerrno, $errclas, $originalMsg);
            }
            mysqli_close($conn);
        }else{
            $result = mysql_query($query, $conn);
            if(!$result){
                mysql_close($conn);
                throw new MyBDException(mysql_error(), mysql_errno());
            }
            mysql_close($conn);
        }
        if($result)
            return true;
        else 
            return false;
        
    }
    
    public function load($data = null, $meta = null){
        if($meta == null)
            $meta = $this->__metadata;
        
        if($data == null)
            $data = (array)($this);
        
        $this->validar($meta, $data, self::$LOAD);
        $llaves = $this->getKeys($meta, $data);
        $campos = "";
        foreach($meta->data as $row){
            $campo = "";
            if($row['tipo'] == "date"){
                $campo = "DATE_FORMAT(".$row['columna'].", '".$this->setDateFormat()."') ".$row['columna'];
            }elseif($row['tipo'] == "datetime"){
                $campo = "DATE_FORMAT(".$row['columna'].", '".$this->setDateTimeFormat()."') ".$row['columna'];
            }else
                $campo = $row['columna'];
            
            if($campos == "")
                $campos = $campo;
            else
                $campos = $campos.", ".$campo;
        }
        
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        $loaded = $querying->getOneRow("SELECT ".$campos." FROM ".$this->getNombreEsquema().".".$this->getNombreTabla()." WHERE ".$llaves);
        
        $itemArr = Array();
        foreach($meta->data as $row){
            $atributo = $row['columna'];
            if(array_key_exists($row['columna'], $loaded)){
                if($row['tipo'] == 'blob' || $row['tipo'] == 'longblob' || $row['tipo'] == 'mediumblob'){
                    $this->$atributo = base64_encode($loaded[$row['columna']]);
                    $itemArr[$row['columna']] = base64_encode($loaded[$row['columna']]);
                }else{
                    $this->$atributo = $loaded[$row['columna']];
                    $itemArr[$row['columna']] = $loaded[$row['columna']];
                }
            }else
                $this->$atributo = null;
            
        }
        
        return $itemArr;
    }
    
    public function addExtraXML($key, $data){
        $this->__extraXML[$key] = $data;
    }
    
    public function toXML($data = null){
        $meta = $this->__metadata;
        if($data != null)
            $this->load($data, $meta);
        
        $dom = new DOMDocument('1.0', 'UTF-8');
        $nodo = $dom->appendChild($dom->createElement($this->getNombreTabla()));
        $atributos = (array)($this);
        
        foreach($meta->data as $row){
            if(array_key_exists($row['columna'], $atributos)){
                $valor = $atributos[$row['columna']];
                $campo = $nodo->appendChild($dom->createElement($row['columna']));
                $campo->appendChild($dom->createTextNode($valor));
            }
        }
        
        foreach($this->__extraXML as $key => $value){
            $campo = $nodo->appendChild($dom->createElement($key));
            $campo->appendChild($dom->createTextNode($value));
        }
        
        return $dom;
    }
    
    public function toXML2($data = null){
        $meta = $this->__metadata;
        if($data != null)
            $this->load($data, $meta);
        
        $dom = new DOMDocument('1.0', 'UTF-8');
        $nodo = $dom->appendChild($dom->createElement("tabla"));
        $nodo->setAttribute("nombre", $this->getNombreTabla());
        $atributos = (array)($this);
        
        foreach($meta->data as $row){
            if(array_key_exists($row['columna'], $atributos)){
                $valor = $atributos[$row['columna']];
                $campo = $nodo->appendChild($dom->createElement("campo"));
                $campo->setAttribute("nombre", $row['columna']);
                $campo->appendChild($dom->createTextNode($valor));
            }
        }
        
        foreach($this->__extraXML as $key => $value){
            $campo = $nodo->appendChild($dom->createElement("campo"));
            $campo->setAttribute("nombre", $key);
            $campo->appendChild($dom->createTextNode($value));
        }
        
        return $dom;
    }
	
	/**
	 * Método que obtiene el identificador único en la base de datos de el campo, asociado a la tabla y al esquema, estos dos
	 * ultimos datos se obtienen por medio de los métodos getNombreTabla() y getNombreEsquema() internamente en el método
	 * 
	 * @param string campo			nombre del campo al cual esta asociado un valor almacenado en la base de datos
	 * 
	 * @return int				Identificador único del campo asociado
	 * */
    protected function getFieldId($campo){
        $query = 'SELECT campo.id cuantos
                FROM '.$this->getNombreEsquemaLog().'.log_estructura campo
                INNER JOIN '.$this->getNombreEsquemaLog().'.log_estructura tabla ON campo.id_estructura = tabla.id
                INNER JOIN '.$this->getNombreEsquemaLog().'.log_estructura esquema ON tabla.id_estructura = esquema.id
                WHERE tabla.nombre = "'.$this->getNombreTabla().'" AND campo.nombre = "'.$campo.'" AND esquema.nombre = "'.$this->getNombreEsquema().'";';
		
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        
        try{
            return $querying->howMany($query);
        }catch(MyBDException $ex){
            if($ex->getCode() == 1146){
                $conn = $this->getConnection();
                $sql_struct = "CREATE TABLE IF NOT EXISTS log_estructura (
                                id int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único',
                                nombre varchar(100) CHARACTER SET latin1 NOT NULL COMMENT 'Nombre de la estructura',
                                id_estructura int(11) DEFAULT NULL COMMENT 'Relación reflexiva con la estructura padre',
                                PRIMARY KEY (id),
                                KEY fk_Estructura_Estructura1 (id_estructura),
                                CONSTRAINT fk_Estructura_Estructura1 FOREIGN KEY (id_estructura) REFERENCES log_estructura (id) ON DELETE NO ACTION ON UPDATE NO ACTION
                              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Contiene la estructura de la base de datos'";

                $sql_opera = "CREATE TABLE IF NOT EXISTS `log_operacion` (
                                id int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador unico',
                                nombre varchar(45) NOT NULL COMMENT 'Nombre de la operacion',
                                descripcion varchar(45) DEFAULT NULL COMMENT 'Descripcion de la operacion',
                                PRIMARY KEY (id)
                              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla de bitacora sobre las operaciones en la tabla'";

                $sql_insertopera = "INSERT INTO log_operacion (nombre) value('Insertar'),('Eliminar'),('Editar')";


                $sql_log = "CREATE TABLE IF NOT EXISTS log_bitacora (
                                valor_anterior varchar(300) CHARACTER SET latin1 DEFAULT NULL COMMENT 'Valor anterior de un campo',
                                valor_actual varchar(300) CHARACTER SET latin1 DEFAULT NULL COMMENT 'Valor actual de un campo',
                                fecha datetime NOT NULL COMMENT 'Fecha del cambio',
                                id_estructura int(11) NOT NULL COMMENT 'Relacion con la estructura o campo cambiado',
                                id_usuario int(11) NOT NULL COMMENT 'Identificador del usuario que hizo el cambio',
                                id_operacion int(11) NOT NULL COMMENT 'Relación con la operación en el cambio',
                                KEY fk_log_bitacora_log_estructura1 (id_estructura),
                                KEY fk_log_bitacora_m_usuario1 (id_usuario),
                                KEY fk_log_bitacora_c_operacion1 (id_operacion),
                                CONSTRAINT fk_log_bitacora_c_operacion1 FOREIGN KEY (id_operacion) REFERENCES log_operacion (id) ON DELETE NO ACTION ON UPDATE NO ACTION,
                                CONSTRAINT fk_log_bitacora_log_estructura1 FOREIGN KEY (id_estructura) REFERENCES log_estructura (id) ON DELETE NO ACTION ON UPDATE NO ACTION
                              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bitácora del sistema'";

                if(function_exists('mysqli_connect')){
                    mysqli_query($conn, $sql_struct);
                    mysqli_query($conn, $sql_opera);
                    mysqli_query($conn, $sql_insertopera);
                    mysqli_query($conn, $sql_log);
                    mysqli_close($conn);
                }else{
                    mysql_query($sql_struct, $conn);
                    mysql_query($sql_opera, $conn);
                    mysql_query($sql_insertopera, $conn);
                    mysql_query($sql_log, $conn);
                    mysql_close($conn);
                }
                
                return $this->getFieldId($campo);
            }
        }
    }
	
	/**
	 * Método que obtiene el identificador único de una operación para almacenar en bitácora
	 * 
	 * @param operacion			Nombre de la operación para almacenar en bitácora
	 * 
	 * @return 					Identificador único de la operación
	 * */
    protected function getOperationId($operacion){
        $query = 'SELECT id cuantos
                FROM '.$this->getNombreEsquemaLog().'.log_operacion 
                WHERE nombre = "'.$operacion.'"';
        
        $querying = new MyBDQuery();
        $querying->setAlterConnection($this->__host, $this->__user, $this->__pass, $this->__db, $this->__charset);
        return $querying->howMany($query);
    }
	
	/**
	 * Método que ejecuta transacciones o un conjunto de instrucciones en modo transaccional, para instrucciones de inserción,
	 * si el identificador único es autoincrementable, se usaran los parámetros autoinc, fecha y usuario para la creacion del
	 * log de la bitácora de transacciones, ya que como es un identificador generado, este no es guardado en el objeto de donde
	 * se obtienen los datos, tiene que obtenerse desde el propio DBMS y generar un log de inserción que contenga dicho dato
	 * 
	 * @throws				Lanza una excepcion si una consulta del listado es inválida
	 * 
	 * @param querys 		Conjunto de instrucciones SQL que se desea ejecutar en una transaccion
	 * @param autoinc		Se define si el identificador unico es autoincrementable, solo se utiliza en instrucciones insert
	 * @param fecha			Se manda la fecha, solo se usa si el identificador unico de la tabla es autoincrementable
	 * @param usuario		Se manda el identificador unico del usuario, solo se usa si el identificador unico
	 * 						de la tabla es autoincrementable
	 * 
	 * @return				Si se ejecutan exitosamente retorna true, si hubiera un error lanza una excepcion
	 * 
	 * */
    public function execTransact($querys, $autoinc = false, $fecha = null, $usuario = null){
        $conn = $this->getConnection();
        $restart = false;
        $wasautoinc = $autoinc;
        $ident = false;
        if(function_exists('mysqli_connect')){
            mysqli_query($conn, "SET AUTOCOMMIT=0;");
            mysqli_query($conn, "BEGIN;");
        }else{
            mysql_query("SET AUTOCOMMIT=0;", $conn);
            mysql_query("BEGIN;", $conn);
        }
        foreach($querys as &$query){
            if($restart){
                $conn = $this->getConnection();
                $restart = false;
            }
            if(function_exists('mysqli_connect')){
                if(!mysqli_query($conn, $query)){
                    $no = mysqli_errno($conn);
                    $er = mysqli_error($conn);
                    mysqli_query($conn, "ROLLBACK;");
                    mysqli_close($conn);
                    throw new MyBDException($er, $no);
                }
            }else{
                if(!mysql_query($query, $conn)){
                    $no = mysql_errno($conn);
                    $er = mysql_error($conn);
                    mysql_query("ROLLBACK;", $conn);
                    mysql_close($conn);
                    throw new MyBDException($er, $no);
                }
            }
            if($autoinc){
                if(function_exists('mysqli_connect')){
                    $ident = mysqli_insert_id($conn);
                    mysqli_query($conn, "COMMIT;");
                    mysqli_close($conn);
                }else{
                    $ident = mysql_insert_id($conn);
                    mysql_query("COMMIT;", $conn);
                    mysql_close($conn);
                }
                $bitacora = new MyBitacora($this->getNombreEsquemaLog());
                $bitacora->valor_actual = $ident;
                $bitacora->fecha = $fecha;
                $bitacora->id_usuario = $usuario;
                $bitacora->id_operacion = $this->getOperationId("Insertar");
                $bitacora->id_estructura = $this->getFieldId($this->__keyAutoInc);
                $querys[] = $bitacora->createLog();
                $autoinc = false;
                $restart = true;
            }
        }
		
        if($wasautoinc)
            return $ident;
        else{
            if(function_exists('mysqli_connect')){
                mysqli_query($conn, "COMMIT;");
                mysqli_close($conn);
            }else{
                mysql_query("COMMIT;", $conn);
                mysql_close($conn);
            }
            return true;    
        }
    }
    
    public function getLogs($operacion, $usuario){
        $this->__getlogs = true;
        if($operacion == self::$INSERT)
            return $this->insert($usuario);
        if($operacion == self::$UPDATE)
            return $this->update($usuario);
        if($operacion == self::$DELETE)
            return $this->delete($usuario);
    }
    
    public function ejecutarObjetos($objetos, $usuario = null){
        $querys = Array();
        foreach($objetos as $objeto){
            $logs = $objeto[0]->getLogs($objeto[1], $usuario);
            if(count($querys) == 0)
                $querys = $logs;
            else
                $querys = array_merge($querys, $logs);
        }
        return $this->execTransact($querys);
    }
    
    public function exeQuickQuery($query){
        $conn = $this->getConnection();
        if(function_exists('mysqli_connect')){
            $res = mysqli_query($conn, $query);
            if(!$res){
                $no = mysqli_errno($conn);
                $er = mysqli_error($conn);
                mysqli_close($conn);
                throw new MyBDException($er, $no);
            }
            mysqli_close($conn);
        }else{
            $res = mysql_query($query, $conn);
            if(!$res){
                $no = mysql_errno($conn);
                $er = mysql_error($conn);
                mysql_close($conn);
                throw new MyBDException($er, $no);
            }
            mysql_close($conn);
        }
    }
}