<?php
include_once("inc/my_pdo.php");
include_once("inc/local_db.php");
include_once("inc/jasonparams.php");
require_once("inc/saprfc.php");

if ($argc < 4) {
	die("Insert system, client, json_file (in this exact order)");
}

$sap_system = strtoupper($argv[1]);
$sap_client = strtoupper($argv[2]);
$json_file = $argv[3];

class RfcReader extends LocalDB {
	private $sys,$client,$table;
	function __construct($sys,$cli) {
		parent::__construct();
		$this->sys = $sys;
		$this->client = $cli;
	}

	function grant_table_existence($table,$fields) {
		$fields = explode(',',$fields);
		for($x=0;$x<count($fields);$x++) {
			$fields[$x] = $fields[$x] . ' varchar(50)';
		}

		// collation and engine only for MySql
		$sql = "create table if not exists $table (OS char(6) default 'notset'," . implode(',',$fields) . ") collate 'utf32_unicode_ci' engine=myIsam";
		//echo $sql . "\n";
		$this->exec($sql) . "\n";
		return true;
	}
}

$db = new RfcReader($sap_system,$sap_client);

// new version returns an array of objects even if the json file has a single object
// no need to change the old json files
$actions = new RfcReadTableParams ($json_file); 

$r = 1;
foreach($actions->rules as $action) {
    echo "********************************\n";
    echo "Processing rule $r \n";

    $db->grant_table_existence($action->tabname,$action->tabfields);
    
    if ($action->mode == 'CLEANUP') {
    	echo "Table > {$action->tabname} < cleanup for OS {$sap_system}{$sap_client} ...";
    	$db->exec("delete from {$action->tabname} where OS = '{$sap_system}{$sap_client}'");
    	echo "done\n";
    } else {
    	echo "Appending data to {$action->tabname} for OS {$sap_system}{$sap_client} \n";
    }

    /*
     * Maybe there's a misunderstanding here, or may be a feature
     * to be implemented. Each filter causes a different call, so
     * right now filter are uses to download different sets of data
     * from a specific table.
     * It is NOT a way to combine filters that are longer than 40 chars
     */
    foreach($action->filters as $action->filter) {
    	/*
    	 * action is passed to RFC_CALL which executes one filter at a time
    	 */
    	echo "Processing filter {$action->filter} \n";
    	$start_row = 0;
    	do {
    		echo "Start row $start_row \n";
    		$read_row = RFC_CALL($action,$start_row);
    		$start_row+=$read_row;
    	} while ($read_row==10000);
    }
    $r++;

    echo "Fixing downloaded data...";
    $db->exec("update {$action->tabname} set OS = '{$sap_system}{$sap_client}' where OS='notset'");
    echo "done\n";
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
	foreach($o as $k) {
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
					array("IMPORT","ROWCOUNT","10000"),
					array("TABLE","OPTIONS", $options),
					array("TABLE","FIELDS",$fields),
					array("TABLE","DATA",array())
			)
			);

	$ret = -1;
	echo "RFC call completed -- writing local DB \n";
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

