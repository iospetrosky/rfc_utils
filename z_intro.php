<?php
require_once("inc/saprfc.php");
require_once("inc/utils.php");
require_once("inc/local_db.php");
require_once("../lib2/file.php");
require_once("../lib2/php_excel/Classes/PHPExcel.php");

if (!isset($f)) {
	die('You MUST declare $f = __FILE__ before including z_intro' . "\n");
}

$in_file = "input_files/x_" . basename($f, ".php") . ".xlsx"; // per default il nome dello script .XLSX
$log_file = "log_files/x_" . basename($f, ".php") . ".log";


if ($argc < 3) {
	die("Insert system and client (in this exact order)");
}

$sap_system = strtoupper($argv[1]);
$sap_client = strtoupper($argv[2]);

try {
    $inputFileType = PHPExcel_IOFactory::identify($in_file);
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    $objPHPExcel = $objReader->load($in_file);
} catch (Exception $e) {
    die('Error loading file "' . pathinfo($in_file, PATHINFO_BASENAME)
    . '": ' . $e->getMessage());
}

$sheet = $objPHPExcel->getSheet(0);
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$data = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, NULL, TRUE, FALSE);

$log = new File($log_file,'w');

$conn = new LocalDB ();
?>
