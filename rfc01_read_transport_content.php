<?php
//include_once("../lib2/file.php");
require_once("../lib2/my_pdo.php");
require_once("inc/local_db.php");
require_once("inc/saprfc.php");

if ($argc < 4) {
	die("Insert system, client, transport_id (in this exact order)");
}

$sap_system = strtoupper($argv[1]);
$sap_client = strtoupper($argv[2]);
$tr_id = strtoupper($argv[3]);


class MsdReader extends AccessRFC {
	private $sys,$client,$table;
	function __construct($sys,$cli) {
		parent::__construct();
		$this->sys = $sys;
		$this->client = $cli;

	}
}

$db = new MsdReader($sap_system,$sap_client);

// first read E070
$action= new stdClass();
$action->tabname = "E070";
$action->tabfields = "TRKORR,STRKORR";
$action->filter = "STRKORR EQ '$tr_id'";

$start_row = 0;
// table cleanup. Transformed to primary key
//$db->exec("delete from E070 where STRKORR = '$tr_id'");

$read_row = RFC_CALL($action,$start_row);
echo "Transport tasks identified \n";

$tasks = $db->column("select TRKORR from E070 where STRKORR = '$tr_id'","TRKORR");
//print_r($tasks);

$action->tabname = "E071";
$action->tabfields = "TRKORR,OBJ_NAME";

foreach($tasks as $task) {
	echo "Reading task $task \n";
	$db->exec("delete from E071 where TRKORR = '$task'");
	$action->filter = "TRKORR EQ '$task' AND PGMID EQ 'R3TR' AND OBJECT EQ 'ACGR'";
	$start_row = 0;
	do {
		echo "Start row $start_row \n";
		$read_row = RFC_CALL($action,$start_row);
		$start_row+=$read_row;
	} while ($read_row==1000);
}


function RFC_CALL($tabdata,$skip) {
	global $db;
	global $sap_system,$sap_client,$sap_pass;

	$rows=0;

	$logon = $db->get_rfc_logon_params($sap_system,$sap_client);
	if (!$logon) {
		die ("No data defined for $sap_system $sap_client \n");
	}

	$sap = new saprfc(array("logindata"=>$logon,"show_errors"=>false,"debug"=>false));
	$o = split("#",$tabdata->filter);

	$options = array();
	//$rolefilter = $conn->get_rolefilter();
	foreach($o as $k) {
		//$k = str_replace("@ROLEFILTER@",$rolefilter,$k);
		$options[]= array("TEXT"=>$k);
	}
	//print_r($options);

	$o = split(",",$tabdata->tabfields);
	$fields = array();
	foreach($o as $k) { $fields[] = array("FIELDNAME"=>$k); }

	$result=$sap->callFunction("RFC_READ_TABLE",
								array(
								    array("IMPORT","QUERY_TABLE",$tabdata->tabname),
									array("IMPORT","DELIMITER",""),
									array("IMPORT","ROWSKIPS",$skip),
									array("IMPORT","ROWCOUNT","1000"),
									array("TABLE","OPTIONS", $options),
									array("TABLE","FIELDS",$fields),
									array("TABLE","DATA",array())
								  )
								);

	$ret = -1;
	if ($sap->getStatus() == SAPRFC_OK) {
		$fields = $sap->call_function_result['FIELDS'];

		$ret = count($result['DATA']);
		foreach($sap->call_function_result['DATA'] as $retval) {
			$db->exec(make_insert_sql($tabdata->tabname,$fields,$retval['WA']));
		}

	} else {
		$sap->printStatus(false);
	}
	$sap->logoff();
	echo "Downloaded $ret lines \n";
	return $ret;
}

function make_insert_sql($table,$tfields,$wa) {
	$sql = "insert into $table ";
	$fields = array();
	$values = array();
	foreach ($tfields as $field) {
		$fields[] = $field['FIELDNAME'];
		$values[] = trim(substr($wa,$field['OFFSET'],$field['LENGTH']));
	}
	//print_r($fields);print_r($values);
	$sql .= "(" . implode(',',$fields) . ") values ('" . implode("','",$values) . "')";
	//echo $sql . "\n";
	return $sql;
}

?>
