<?php
include_once("inc/my_pdo.php");

/*
if (!isset($argv[2])) {
	die("Provide the target cargo and level of the ship\n");
}
*/

class my_lite extends my_pdo {

	function __construct($dbname) {
		parent::__construct('sqlite',$dbname);
	}
}

$fff = 'cargo';  // cargo|marinai
$mult = 16;
//eval("\$target = " . $argv[1] . ";");
$target = 55408-38560;
$level = 700;

$db_name = "c:/cazzarola/app/lite.general.db";
$db = new my_lite($db_name);
$attempts = 0;

// calcolo preventivo. E' fattibilr se mando tutte le navi disponibili 
if ($fff == 'cargo') {
    $total = $db->query_field("select sum(cargo) * $mult as P1 from navi where livello  >= $level");
    if ($total < $target) {
        echo "Target $target can't be reached\n";
        echo "Total cargo available: $total \n";
        echo "Missing " . ($target - $total) . "\n";
        die();
    }
}


while ($attempts < 5) {
    if ($fff == 'cargo') {
        $ships = $db->query("select nome, cargo * $mult as cargo, marinai, livello from navi where livello  >= $level order by cargo desc");
    } else {
        $ships = $db->query("select nome, cargo, marinai * $mult as marinai, livello from navi where livello  >= $level order by marinai desc");
    }
	$fleet = array();
	$c=0;
	$cargo_tot = 0;
	
	do {
		select_ships($c,$cargo_tot,$fff);
		$c++;
		// esce se ha una soluzione o le navi sono finite
	} while (($c<count($ships)) && count($fleet==0));
	
	
	if (count($fleet)>0) {
		$c=0;
		echo "\n";
		printf("%-20s | %6s | %5s | %5s \n","NAVE", substr(strtoupper($fff),0,5), "PROGR", "LIVELLO");
		printf("---------------------+--------+-------+---------\n");
		foreach($fleet as $f) {
			$c+=$f->$fff;
			printf("%-20s | %6d | %5d | %5d \n",$f->nome, $f->$fff, $c, $f->livello);
		}
		echo "\n\nTotal: $c \n";
		die();
	} else {
		echo "Target $target cannot be calculated \n";
		$target = $target + 50;
		$attempts++;
		echo "Trying $target... \n";
	}
}



function select_ships($start,$cargo,$field) {
	global $cargo_tot,$ships,$fleet,$target;
	debug("start $start - $field $cargo");
	debug("adding " . $ships[$start]->nome . " cargo " . $ships[$start]->$field);
	array_push($fleet,$ships[$start]);
	$cargo_tot += $ships[$start]->$field;
	$finish = false;
	
	while (($cargo_tot < $target) && ($start+1 < count($ships)) && (!$finish)) {
		$start++;
		$finish = select_ships($start,$cargo_tot,$field);
	}
	//o e' uguale o maggiore
	if ($cargo_tot == $target) return true;
	$popped = array_pop($fleet);
	$cargo_tot -= $popped->$field;
	debug("removing  " . $popped->nome . " $field " . $popped->$field);

	return false;
}
	
function debug($text) {
	//echo $text . "\n";
}		
		
	

