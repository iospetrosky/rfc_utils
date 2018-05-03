<?php

class my_pdo extends PDO {
	private $fetch_mode = PDO::FETCH_OBJ;
	public $last_sql = "";
	protected $engine = "";

	function __construct($engine,$pdo_host,$pdo_dbname="",$pdo_user="",$pdo_password="") {
		$this->engine = $engine;
		switch ($engine) {
			case 'mysql':
				parent::__construct("mysql:host=" . $pdo_host . ";dbname=" . $pdo_dbname, $pdo_user, $pdo_password);
				break;
			case 'sqlite':
				// se il database non esiste viene creato automaticamente
				parent::__construct("sqlite:$pdo_host");
				break;
			case 'odbc': // MS Access
				parent::__construct("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=$pdo_host;Uid=Admin");
				break;
			case 'hana': // ODBC actually
				//parent::__construct("DRIVER={HDBODBC32};UID=$pdo_user;PWD=$pdo_password; SERVERNODE=qlctcst7000:32115;DATABASENAME=DL1");
				parent::__construct("odbc:DL1","pedrotti","Feder1c0");
				break;

		}
	}

	function lastInsertId($seq = NULL) {
		if ($this->engine == 'odbc') {
			// not supported function in driver
			return -1;
		}
		return parent::lastInsertId();
	}

	function mode_object() {
		$this->fetch_mode = PDO::FETCH_OBJ;
	}
	function mode_array() {
		$this->fetch_mode = PDO::FETCH_ASSOC;
	}

	function call_procedure($procedure, $params = false, $retvalues = false) {
	/*
	Esegue la procedura PROCEDURE passando eventuali parametri indicati in
	PARAMS racchiusi tra apici. Se indicato RETVALUES ci si aspetta che la procedura
	esegua un SELECT in chiusura per restituire i valori (non sono i valori di OUT della procedura)
	*/
		$sql = "call $procedure (";
		if ($params) {
			if (is_array($params)) {
		 		$sql .= "'" . implode("','",$params) . "'";
			} else {
				$sql .= "'" . $params . "'";
			}
		}
		$sql .= ")";
		$this->last_sql = $sql;
		if ($retvalues) {
			return $this->query($sql);
		} else {
			return $this->exec($sql);
		}
	}

	function exec_prepared($sql, $params) {
	/*
		esegue un comando preparato (esempio "update tabella set campo = ? where campo2 = ?)
	*/
		if (is_object($params)) {
			$params = (array)$params;
		}
		if ($stmt = $this->prepare($sql)) {
			if ($stmt->execute($params)) {
				$this->last_sql = $stmt->queryString;
				return $stmt->rowCount();
			} else {
				return false;
			}
		} else {
			return false;
		}
	}




	function query_prepared($sql, $params, $one = false) {
	/*
		esegue una query preparata (esempio "select * from tabella where campo = ? and campo2 = ?)
	*/

	if (is_object($params)) {
			$params = (array)$params;
		}

		if ($stmt = $this->prepare($sql)) {
			if ($stmt->execute($params)) {
				return $stmt->fetchAll($this->fetch_mode);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function query($sql, $one = false, $class = false) {
	/*
	esegue la query indicata in SQL. Se specificato ONE restituisce direttamente
	un oggetto corrispondente al primo elemento ottenuto. Se indicato anche CLASS
	l'oggetto sara'  istanziato della classe specificata. CLASS viene ignorato
	se ONE = false
	*/

		$this->last_sql = $sql;
		$stmt = parent::query($sql, $this->fetch_mode);
		if ($stmt) {
			if ($one) {
				if ($class) {
					// se specificata una classe restituisce un oggetto di quel tipo
					$ret = $stmt->fetchObject($class);
				} else {
					// altrimenti restituisce un oggetto o un array a seconda del parametro fetch_mode
					$ret = $stmt->fetch();
				}
				$stmt->closeCursor();
				return $ret;
			} else {
				return $stmt->fetchAll($this->fetch_mode);
			}
		} else {
			return false;
		}
	}

	function column($sql,$field = 'P1') {
	/*
	restitusce sotto forma di array il contenuto della colonna FIELD della
	query indicata in SQL. Usare per query piccole perchÃ© si passa tutti i
	risultati
	*/
		$data = $this->query($sql);
		$ret = array();
		foreach ($data as $d) {
			$ret[] = $d->$field;
		}
		return $ret;
	}

	function query_field($sql,$field = 'P1') {
	/*
	restituisce il valore del campo FIELD della query indicata in SQL
	*/
		if ($r = $this->query($sql)) {
			$a = $r[0]->$field;
			return $a;
		} else {
			return false;
		}
	}


	function query_field_prepared($sql, $params, $field) {
	/*
	uguale a query_field ma con una query preparata
	(per SQL e PARAMS vedere query_prepared)
	*/
		if ($r = $this->query_prepared($sql,$params)) {
			$a = $r[0]->$field;
			return $a;
		} else {
			return false;
		}
	}

	function record_exists($sql) {
		if ($this->query($sql)) {
			return true;
		} else {
			return false;
		}
	}


	function insert_object($table, $in_obj) {
		return $this->insert_array($table,(array)$in_obj);
	}

	function insert_array($table, $in_arr) {
	/*
	crea un comando INSERT INTO TABLE a partire dall'array associativo indicato
	*/
		$fld_list = "";
		$val_list = "";
		foreach($in_arr as $field=>$value) {
			if (!is_numeric($field)) {
				$fld_list .= $field . ",";
				$val_list .= "'" . $value . "',";
			}
		}
		$fld_list = substr($fld_list,0,-1);
		$val_list = substr($val_list,0,-1);
		$sql = "INSERT INTO $table ($fld_list) VALUES ($val_list)";
		//echo $sql . "\n";
		if ($this->exec($sql) == 1) {
			if ($this->lastInsertId()) {
				return $this->lastInsertId();
				// non tutte le tabelle hanno un campo auto_increment
				// sqlite non funziona?
			} else {
				return 1;
			}
		} else {
			return false;
		}
	}

	function update_object($table,$upd_obj,$key='id') {
		return $this->update_array($table, (array)$upd_obj,$key);
	}

	function update_array($table, $upd_arr,$key='id') {
	/*
	crea un comando UPDATE TABLE ... WHERE KEY = ... a partire dall'array associativo indicato
	La clausola where viene creata usando l'elemento UPD_ARR[KEY]
	*/

	// ODBC (or the backend) does not allow the quoting of numeric values!!!
		if (is_numeric($upd_arr[$key])) {
			$where = " WHERE $key = " . $upd_arr[$key] ;
		} else {
			$where = " WHERE $key = '" . $upd_arr[$key] . "'";
		}
		$statm = "UPDATE $table SET ";
		$setvalues = array();
		foreach($upd_arr as $field=>$value) {
			if ((!is_numeric($field)) && ($field != $key)) {
				if (is_numeric($value)) {
					$setvalues[] = $field . " = $value ";
				} else {
					$setvalues[] = $field . " = '$value' ";
				}
			}
		}
		$statm .= implode(",",$setvalues) . $where;
		echo $statm . "\n";
		return $this->exec($statm);
	}

	function delete_one($table, $kval, $key = 'id') {
		// in realta'� ONE si riferisce al fatto che specifico un solo valore
		// cancella di fatto tutti i record che corrispondono alla condizione
		$sql = "DELETE FROM $table WHERE $key = '{$kval}'";
		//echo $sql;
		return $this->exec($sql);
	}

	function delete_many($table, $kvalues, $key = 'id') {
		// un po' inutile, ma per completezza ...
		foreach($kvalues as $kval) {
			$this->delete_one($table, $kval, $key);
		}
	}

}

// classe da usare con fetch_object
// può essere passata anche a insert_object e update_object

class MyRecord {
	private $__key = 'id';
	private $__table = '';
	private $__conn = null;

	public function SetTable($table) {
		$this->__table = $table;
	}

	public function SetKey($key) {
		$this->__key = $key;
	}

	public function SetConnection(&$conn) {
	/*
	CONN e' il riferimento ad un oggetto MY_PDO
	*/
		$this->__conn = $conn;
	}

	public function Insert() {
		if ($this->__conn) {
			return $this->__conn->exec($this->GetInsertSql());
		} else {
			die("MYRECORD: Connection not set!");
		}
	}
	public function Update() {
		if ($this->__conn) {
			return $this->__conn->exec($this->GetUpdateSql());
		} else {
			die("MYRECORD: Connection not set!");
		}
	}

	public function Delete() {
		if ($this->__conn) {
			return $this->__conn->exec($this->GetDeleteSql());
		} else {
			die("MYRECORD: Connection not set!");
		}

	}


	public function GetInsertSql() {
		// utile se si recupera un record, lo si modifica e lo si
		// salva come nuovo record
		if ($this->__table == '') {
			return "No TABLE specified. Use SetTable('tablename')";
		}

		$sql = "insert into " . $this->__table . " ";
		$fields = array();
		$values = array();
		foreach ($this as $name=>$value) {
			if (substr($name,0,2)!='__') {
				if ($name != $this->__key) {
					$fields[] = $name;
					$values[] = "'" . $value . "'";
				}
			}
		}
		$sql .= "(" . implode(",",$fields) . ") values (" . implode(",",$values) . ")";
		return $sql;
	}

	public function GetDeleteSql() {
		if ($this->__table == '') {
			return "No TABLE specified. Use SetTable('tablename')";
		}
		$v = $this->__key;
		$sql = sprintf("delete from %s where %s = '%s'",$this->__table,$this->__key, $this->$v);
		return $sql;
	}

	public function GetUpdateSql() {
		if ($this->__table == '') {
			return "No TABLE specified. Use SetTable('tablename')";
		}
		$sql = sprintf("update %s set ", $this->__table);
		$values = array();
		$keyvalue='';
		foreach ($this as $name=>$value) {
			if (substr($name,0,2)!='__') {
				if ($name != $this->__key) {
					$values[] = sprintf("%s = '%s'",$name,$value);
				} else {
					$keyvalue=$value;
				}
			}
		}
		$sql .= implode(",",$values);
		$sql .= sprintf(" where %s = '%s' \n",$this->__key,$keyvalue);
		return $sql;


	}


}


function GET_UPDATE_SQL($object, $table, $key = 'id') {
	$sql = sprintf("update %s set ", $table);
	$values = array();
	$keyvalue='';
	foreach ($object as $name=>$value) {
		if ($name != $key) {
			$values[] = sprintf("%s = '%s'",$name,$value);
		} else {
			$keyvalue=$value;
		}
	}
	if ($keyvalue == '') {
		return false;
	} else {
		$sql .= implode(",",$values);
		$sql .= sprintf(" where %s = '%s' \n",$key,$keyvalue);
		return $sql;
	}
}


?>
