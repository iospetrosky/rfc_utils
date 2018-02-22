<?php

class HanaDB extends my_pdo {
	private $__admin = false;
	protected $user_id = 0;

	function __construct() {
		// parent::__construct('hana', 'hannlsd2:31315', 'DB1', 'pedrotti', 'Feder1c0');
		parent::__construct('odbc', 'Hana32', '', 'pedrotti', 'Feder1c0');
	}

	function get_rfc_logon_params($sys, $client) {
		if ($row = $this->query("select * from z_connections where sap_sid = '{$sys}' and sap_client='{$client}'", true)) {
			return array ( // Set login data to R/3
					"ASHOST" => $row->SAP_SERVER, // application server host name
					"SYSNR" => $row->SAP_SYSNUM, // system number
					"CLIENT" => $row->SAP_CLIENT, // client
					"USER" => "pedrotti", // user
					"PASSWD" => Crypto::decrypt($row->PASSWORD), // password
					"CODEPAGE" => "1404"
			);
		} else {
			return null;
		}
	}
}

class AccessRFC extends my_pdo {

	function __construct() {
		parent::__construct('msacc', 'databases/rfc_data.accdb');
	}

	function get_rfc_logon_params($sys, $client) {
		if ($row = $this->query("select * from z_connections where sap_sid = '{$sys}' and sap_client='{$client}'", true)) {
			return array ( // Set login data to R/3
					"ASHOST" => $row->sap_server, // application server host name
					"SYSNR" => $row->sap_sysnum, // system number
					"CLIENT" => $row->sap_client, // client
					"USER" => "pedrotti", // user
					"PASSWD" => $row->password, // password
					"CODEPAGE" => "1404"
			);
		} else {
			return null;
		}
	}
}


class LocalDB extends my_pdo {
	private $__admin = false;
	protected  $user_id = 0;

	function __construct() {
		parent::__construct("mysql","localhost;charset=utf8","rfc_data","odbc","shorena1");
		//parent::__construct("sqlite","lite/rfc_data.db");
	}

	function get_rfc_logon_params($sys,$client) {

		if ($row =  $this->query("select * from z_connections where sap_sid = '{$sys}' and sap_client='{$client}'",true)) {
			return array (                                 // Set login data to R/3
					"ASHOST"=>$row->sap_server,                // application server host name
					"SYSNR"=>$row->sap_sysnum,                 // system number
					"CLIENT"=>$row->sap_client,                // client
					"USER"=>"pedrotti",                    // user
					"PASSWD" =>$row->password,                  // password
					"CODEPAGE"=>"1404");
		} else {
			return null;
		}
	}


}
