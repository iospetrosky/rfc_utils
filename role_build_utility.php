<?php
include_once("inc/my_pdo.php");
include_once("inc/local_db.php");
include_once("inc/file.php");

/*
Utility to generate a pseudo-excel file for easy
copy-paste into the MSD role build file

It requires that role information are previously downloaded
into local mysql tables
*/



class OFormatter extends File {
	function __construct($filepath) {
        parent::__construct($filepath,"w");
        $this->WriteLine('<table border=1>');
    }
    
    function openrow() {
        $this->WriteLine('<tr>');
    }
    function closerow() {
        $this->WriteLine('</tr>');
    }

    function makecell($text) {
        $this->WriteLine("<td>$text</td>");
    }

    function Close() {
        $this->WriteLine('</table>');
        parent::Close();
    }
}


$db = new LocalDB();
try {
    $of = new OFormatter('roleinfo.xls');
} catch (Exception $e) {
    ($e->getMessage());
    die;
}


$the_roles_text = "
R:XX108_RECEIVING_CLERK
R:XX123_PM_UPDATE_NOTIFCTNS
R:XX124_PM_RESERVATIONS
R:XX126_PM_WORK_ORDERS
R:XX127_PM_CAPACITY_PLANNING
R:XX130_PM_TASK_LISTS
R:XX131_PM_MAINTENANCE_PLANS
R:XX133_PM_CONFIRMATIONS
R:XX134_PM_SERVICEENTRY_SHEETS
R:XX135_PM_MNTN_WORK_CENTERS
R:XX136_PM_TECHNICAL_OBJECTS
R:XX137_PM_MATERIAL_MASTERS
R:XX138_PM_BOMS
R:XX139_PM_INSTALL_DISMANTLE
R:XX145_PM_SERIAL_NUMBERS
R:XX146_PM_PMIS
R:XX381_PM_STORE_OPERATOR
R:XX382_PM_STORE_MANAGER
";
$the_roles = split("\n", $the_roles_text);
//$market = 'Brinny'; $suffix = 'BN'; $country = 'IE';
//$market = 'Cherokee'; $suffix = 'CH'; $country = 'US';
//$market = 'Singapore'; $suffix = ''; $country = 'SG';
//$market = 'Indonesia'; $suffix = ''; $country = 'ID';
$market = 'Russia'; $suffix = 'RUS'; $country = 'RU';
$market = 'Greece'; $suffix = ''; $country = 'GR';



$release = 'CA8';
$system = 'ECC';


$sys = 'DR1300';

$akce = 'NEW_ROLES';
//$akce = 'EXIST_ROLES';

switch($akce) {
    case 'EXIST_ROLES':
        foreach($the_roles as $rolename_child) {
            $rolename_child = trim($rolename_child);
            if ($rolename_child != '') {
                echo $rolename_child . "\n";
                $sql = "select text from agr_texts where  os = '$sys' and agr_name = '$rolename_child'";
                $text = $db->query_field($sql,'text');

                $sql = "select profile as P1 from agr_prof where os = '$sys' and agr_name = '$rolename_child'";
                $profile = $db->query_field($sql);

                $sql = "select uname as P1 from agr_users where os = '$sys' and agr_name = '$rolename_child'";
                if ($users = $db->column($sql)) {
                    $users = implode(", ",$users);
                } else {
                    $users = 'No users';
                }


                $of->openrow();
                $of->makecell($rolename_child);
                $of->makecell($text);
                $of->makecell("");
                $of->makecell("Exist");
                $of->makecell($profile);
                $of->makecell($users);
                $of->closerow();
            }
        }
        break;

    case 'NEW_ROLES':
        foreach($the_roles as $rolename_master) {
            $rolename_master = trim($rolename_master);
            if ($rolename_master != '') {
                echo $rolename_master . "\n";
                $sql = "select text from agr_texts where os = '$sys' and agr_name = '$rolename_master'";
                $text = $db->query_field($sql,'text');
                $rolename_child = str_replace('XX',$country,$rolename_master);
                $child_prefix = substr($rolename_child,0,7);
                if ($suffix != '') {
                    $rolename_child .= "_$suffix";
                }
                $text = $text . " ($market)";
                $of->openrow();
                $of->makecell($release);
                $of->makecell($system);
                $of->makecell($market);
                $of->makecell($country);
                $of->makecell($rolename_master);
                $of->makecell($rolename_child);
                $of->makecell('LEN');
                $of->makecell($text);

                // check if there is already a role in this family
                $family = $db->column("select agr_name from agr_define where OS = '$sys' and agr_name like '$child_prefix%'",'agr_name');
                if ($family) {
                    $of->makecell(implode(', ', $family));
                } else {
                    $of->makecell('No family');
                }
                $of->closerow();
            }
        }
        break;
}


$of->Close();


