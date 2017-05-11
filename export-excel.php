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
$cli_cmd->option('s')->aka('source')->require()->describedAs("support single file(.sqlite3) or folder")->must(function($path) {        
        return file_exists($path);
    });
$cli_cmd->option('t')->aka('target')->require()->describedAs("must be a vail language")->must(function($path) {        
        return file_exists($path);
    });
$fds = array();
if(is_dir($cli_cmd['source'])){
    $fds = preg_ls($cli_cmd['source'],false,"#.+\.(.+)$#i");    
}elseif(file_exists($cli_cmd['source'])){
    $fds[] = $cli_cmd['source'];
}
$logger = new Katzgrau\KLogger\Logger(__DIR__.'/logs');

foreach($fds as $sqlite_path){
    $fdPaths = pathinfo($sqlite_path);    
    $excel_path = $cli_cmd['target'].DIRECTORY_SEPARATOR. $fdPaths["filename"].'.xlsx';
    if(file_exists($excel_path)){continue;}
    $db = new ezSqlite('sqlite:' . $sqlite_path, '', '');
    $sql = 'SELECT name FROM sqlite_master WHERE type ="table"';
    $tables = $db->get_col($sql);
    if(empty($tables)){$logger->info($sqlite_path . "have no table");die();}
    $excel = new array2excel();    
    foreach($tables as $table){
        $sql = "SELECT COUNT(*) FROM  \"{$table}\"";
        $beforeDistinct = $db->get_var($sql);
        $sql = "SELECT DISTINCT * FROM \"{$table}\" ORDER BY _ROWID_";
        $rows = $db->get_results($sql,ARRAY_N);        
        $afterDistinct = count($rows);
        $logger->info("ADD: {$table} (".$afterDistinct.")/(".$beforeDistinct.")");
        echo "ADD: {$table} (".$afterDistinct.")/(".$beforeDistinct.")",EOL;
        $excel->addData($table,$rows);        
        unset($rows,$tmp);
    }
    $excel->genExcel($excel_path);
    echo date('H:i:s') , "{$sqlite_path}: Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB" , EOL;
    $logger->info("{$sqlite_path}: Peak memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB");
}
unset($loger);

?>