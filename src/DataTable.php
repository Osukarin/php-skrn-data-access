<?php

class DataTable{
    var $data = array(),
        $name = "";
    
    public function __construct($result = null, $name = null){
        if($name != null){
            $this->name;
        }
        if($result != null){
            while($row = mysqli_fetch_array($result)){
                $this->data[] = $row;
            }
        }
    }
    
    public function toJSON(){
        $jsontxt = "";
        foreach($this->data as $key => $row){
            foreach($row as $key => $data){
                if(is_numeric($key)){
                    unset($row[$key]);
                }
            }
            if($jsontxt == ""){
                $jsontxt = json_encode($row) . $jsontxt;
            }else{
                $jsontxt .= "," .json_encode($row);
            }
        }

        return '{ "type" : "DataTable", "name" : "' . $this->name . '", "data" : [' . $jsontxt . "]}";
    }
}

