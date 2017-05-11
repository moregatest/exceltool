<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('memory_limit','2024M');
require_once 'vendor/autoload.php';
require (__DIR__ . "/phpQuery-onefile.php");
require(__DIR__ . "/db/shared/ez_sql_core.php");
require(__DIR__ . "/db/pdo/ez_sql_pdo.php");
include __DIR__ . '/PHPExcel.php';
include __DIR__ . '/functions.php';
include __DIR__ . '/PHPExcel/IOFactory.php';
require(__DIR__ . "/array2excel.class.php");
define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

$cli_cmd = new Commando\Command();
$cli_cmd->option('s')->aka('source')->require()->describedAs("support single file(xls,xlsx) or folder")->must(function($path) {        
        return file_exists($path);
    });
$cli_cmd->option('t')->aka('target')->require()->describedAs("must be a folder path")->must(function($path) {        
        return file_exists($path);
    });
$fds = array();
if(is_dir($cli_cmd['source'])){
    $fds = preg_ls($cli_cmd['source'],false,"#.+\.(xlsx?)$#i");    
}elseif(file_exists($cli_cmd['source'])){
    $fds[] = $cli_cmd['source'];
}

foreach($fds as $fd){
    $fdPaths = pathinfo($fd);
    $sqlite_path = $cli_cmd['target'].DIRECTORY_SEPARATOR. $fdPaths["filename"].'.sqlite3';    
    //exit();    
    //@unlink($sqlite_path);
    if(file_exists($sqlite_path)){echo $sqlite_path," was exist",EOL;continue;}
    echo "building {$sqlite_path}",EOL;
    $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
    $cacheSettings = array( ' memoryCacheSize ' => '8MB');
    PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
    
    $excelReader = PHPExcel_IOFactory::createReaderForFile($fd);    
    $sheets = $excelReader->listWorksheetNames($fd);    
    foreach($sheets as $sheetName){
        $phpExcel = $excelReader->setLoadSheetsOnly($sheetName)
                                ->setReadDataOnly(true)
                                ->load($fd);
        $rows = $phpExcel->getActiveSheet()->toArray('', true, true, true);
        unset($phpExcel);        
        $db = new ezSqlite('sqlite:' . $sqlite_path, '', '');
        $db->dbh->exec("PRAGMA synchronous=OFF");        
        $structure = array();
        foreach($rows[1] as $key => $val){
            $structure[$key] = 'VARCHAR';
        }
        
        $sql = $db->genTableCreateSQL($sheetName, $structure);        
        $db->query($sql);
        foreach($rows as $k => $row){
            //if($k < 2){continue;}
            $data = [];
            $tmp = array_values($row);
            foreach(array_keys($structure) as $index => $fname){
                $tmp[$index] = trim($tmp[$index]);
                $data[$fname] = (string)$tmp[$index];    
            }
            $sql = $db->genTableMapInsertSQL($sheetName, $data);
            $db->query($sql);            
            unset($rows[$k]);            
        }
        
        unset($rows,$phpExcel);
        echo date('H:i:s') , "{$sheetName}: Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB" , EOL;
    }
    unset($sheets,$excelReader);
}

?>