<?php
// MSD tested OK
// creates a role with a specific profile name

$f = __FILE__;
include_once("z_intro.php");


foreach($data as $line) {
	XECHO ("Role  " . $line[0] . " ... ");
	if ($line[0] == "") {
		echo " empty\n";
		continue;
	}
	$r = $conn->get_rfc_logon_params($sap_system,$sap_client);
	$sap = new saprfc(array("logindata"=>$r,"show_errors"=>false,"debug"=>false));

	$result=$sap->callFunction("PRGN_RFC_CREATE_ACTIVITY_GROUP",
								array(
								    array("IMPORT","ACTIVITY_GROUP",$line[0]),
									array("IMPORT","ACTIVITY_GROUP_TEXT",$line[1]),
									array("IMPORT","PROFILE_NAME",$line[2]),
									//array("IMPORT","COMMENT_TEXT_LINE_1",""),
									//array("IMPORT","COMMENT_TEXT_LINE_2",""),
									//array("IMPORT","COMMENT_TEXT_LINE_3",""),
									//array("IMPORT","COMMENT_TEXT_LINE_4",""),

									array("IMPORT","COMMENT_TEXT_LINE_1","Regenerate profile in EXPERT MODE"),
									array("IMPORT","COMMENT_TEXT_LINE_2","if this is a master role"),
									array("IMPORT","COMMENT_TEXT_LINE_3","otherwise just link the master"),
									array("IMPORT","COMMENT_TEXT_LINE_4","then delete these lines"),

									//array("IMPORT","TEMPLATE","X"), // SAP_USER_B evita l'errore
									//array("IMPORT","ORG_LEVELS_WITH_STAR"," "),
								  )
								);

	if ($sap->getStatus() == SAPRFC_OK) {
		XECHO (" OK ");
		// now delete the default long texts
		$texts[] = array("TEXT"=>$line[1]); // the first line is the role description
		XECHO(" ... cleaning long texts ... ");
		$result=$sap->callFunction("PRGN_RFC_CHANGE_TEXTS",
									array(
										array("IMPORT","ACTIVITY_GROUP",$line[0]),
										array("TABLE","TEXTS", $texts),
										)
									);

		if ($sap->getStatus() == SAPRFC_OK) {
			$messages = false;
			if (!$messages) {
				XECHO(" OK \n");
			}
		} else {
			XECHO($sap->last_exception . "\n");
		}
		unset($texts);
	} else {
		XECHO($sap->last_exception . "\n");
	}

	$sap->logoff();
}

?>
