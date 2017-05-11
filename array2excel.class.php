<?php

class array2excel{
    /**
     *@param PHPExcel $excel
    */
    private $excel = null;
    /**
     *@param 
     *
     */
    private $sheets = array();
    public function __construct(){
            $this->excel = new PHPExcel();            
    }
    /**
     * 添加SHEET 指定名稱 用陣列寫入資料 $data = array(
        0 => array(*),
        1 => array(*),
        2 => array(*),
     )
     *
     */
    public function addData($sheetname,$data){
        if(count($this->sheets) < 1){
            $index = 0;
            $this->excel->getSheet($index)->setTitle($sheetname);
        }else{
            $index = count($this->sheets);
            $this->excel->createSheet($index);
            $this->excel->getSheet($index)->setTitle($sheetname);
        }
        $this->sheets[$sheetname] = $data;
        if(is_array($data) && (!empty($data))){
            $this->excel->getSheet($index)->fromArray($data);
        }
    }
    public function genExcel($fn,$type='Excel2007'){
        
        if(!($type == 'Excel2007' || $type == 'Excel5')) {
            $type = 'Excel5';
        }
        $objWriter = PHPExcel_IOFactory::createWriter($this->excel,$type);
        return $objWriter->save($fn);
    }
    public function __destruct(){
        unset($this->excel,$this->sheets);
    }
}
?>