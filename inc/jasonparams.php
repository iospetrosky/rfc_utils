<?php

class JasonParams {
	function __construct($filepath, $assoc = true) {
		if (file_exists($filepath)) {
			// open and read the file ... if exists
			$handle=fopen($filepath,"r");
			$all = '';
			while (!feof($handle)) {
				$all .=  fgets($handle,1024);
			}
			fclose($handle);
			//print_r($all);
			$jasondata = json_decode($all, $assoc);
			if ($jasondata) {
				foreach($jasondata as $field=>$value) {
					$this->$field = $value;
				}
			} else {
				throw(new Exception("JSON file not properly formatted"));
			}
		} else {
			throw(new Exception("File $filepath does not exist"));
		}
	}
}

class SapJasonParams extends JasonParams {
	function __construct($filepath) {
		parent::__construct($filepath);
		if (!isset($this->in_file)) {
			throw(new Exception("Parameter in_file not set in $filepath"));
		} else {
			$this->log_file = str_replace(".txt",".log",$this->in_file);
		}
		if (!isset($this->sap_system)) {
			throw(new Exception("Parameter sap_system not set in $filepath"));
		} else {
			$this->sap_system = strtoupper($this->sap_system);
		}
	}

}

class RfcReadTableParams extends JasonParams {
	function __construct($filepath) {
		parent::__construct($filepath, false);

        if (!isset($this->rules)) {
            // rebuild the object as an array with only ONE rule
            $rules = array(new stdclass());
            foreach(get_object_vars($this) as $k => $v) {
                $rules[0]->$k = $v;
                unset($this->$k);
            }
            $this->rules = $rules;
        }      
        for ($k = 0; $k < count($this->rules); $k++) {
    		if (!isset($this->rules[$k]->tabname) || !isset($this->rules[$k]->tabfields)) {
    			throw(new Exception("Parameters tabname or tabfields not set in $filepath rule $k"));
    		}
    		// strip spaces out of tabfields
    		$this->rules[$k]->tabfields = str_replace(' ','',$this->rules[$k]->tabfields);
    		
    		if (!isset($this->rules[$k]->filters)) {
    			$this->rules[$k]->filters = array("*");
    		}
    		if (!isset($this->rules[$k]->mode)) {
    			$this->rules[$k]->mode = "CLEANUP"; // otherwise it can be APPEND
		    }
		}

	}

}

?>
