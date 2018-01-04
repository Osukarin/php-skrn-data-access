<?php 

/**
 * Clase que contiene la lógica de el almacenamiento de los datos de la bitácora. Esta genera una instruccion SQL insert
 * para dicho registro. En la bitácora se almacena:
 * 
 * valor_anterior: Es el valor que tuvo antes un registro, solo aplica para instrucciones update y delete
 * valor_actual: Es el valor nuevo que contiene un registro, aplica para todas las instrucciones sql (insert, update, delete)
 * fecha: Contiene la fecha y la hora en el cual fue almacenado, cambiado o eliminado el registro
 * id estructura: Contiene cual campo tuvo el cambio, el cual esta asociado a una tabla, la cual esta asociada a un esquema
 * id_usuario: Identificador único del usuario que hizo el cambio
 * id_operacion: Identificador único de la operacion que se realizo en ese campo
 * 
 * @since 1.0
 * @version 2.1
 * */

class MyBitacora{
	
    public $valor_anterior;
	public $valor_actual;
	public $fecha;
	public $id_estructura;
	public $id_usuario;
	public $id_operacion;
    public $esquema;
   
    public function __construct($esquema) {
        $this->esquema = $esquema;
    }
	/**
	 * Método que genera la instrucción insert para la bitácora
	 * 
	 * @return string		Instruccion SQL insert para insertar el historial en la tabla de bitácora
	 * */
	
	public function createLog(){
		$campos = "";
		$valor = "";
	
		$campos .= "fecha";
		$valor .= $this->fecha;
		
		if($this->valor_anterior != null){
			$campos .= ", valor_anterior";
			$valor .= ", LEFT('".$this->valor_anterior."', 300)";
		}
		if($this->valor_actual != null){
			$campos .= ", valor_actual";
			$valor .= ", LEFT('".$this->valor_actual."', 300)";
		}
		if($this->id_estructura != null){
			$campos .= ", id_estructura";
			$valor .= ", ".$this->id_estructura;
		}
		if($this->id_usuario != null){
			$campos .= ", id_usuario";
			$valor .= ", ".$this->id_usuario;
		}
		if($this->id_operacion != null){
			$campos .= ", id_operacion";
			$valor .= ", ".$this->id_operacion;
		}
		
		return "INSERT INTO ".$this->esquema.".log_bitacora(".$campos.") values(".$valor.")";
	}
        
        public function setEsquema($esquema){
            $this->esquema = $esquema;
        }

}
?>