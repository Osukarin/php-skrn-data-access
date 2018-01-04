<?php
class DataSet{
	var $data = array();
	
	public function __construct($result = null){
        if($result != null){
            if(function_exists('mysqli_connect')){
                while($row = mysqli_fetch_array($result))
                    $this->data[] = $row;
            }else{
                while($row = mysql_fetch_array($result))
                    $this->data[] = $row;
            }
        }
    }
        
    public function getListComa($columna, $comilla = true){
        $codigos = "";
        foreach($this->data as &$row){
            if(!is_null($row[$columna])){
                if($comilla){
                    if ($codigos == "")
                        $codigos = "'".$row[$columna]."'";
                    else
                        $codigos .= ",'".$row[$columna]."'";
                }else{
                    if ($codigos == "")
                        $codigos = $row[$columna];
                    else
                        $codigos .= ",".$row[$columna];
                }
            }
        }
        return $codigos;
    }
}
?>