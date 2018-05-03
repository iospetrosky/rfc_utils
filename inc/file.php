<?php

class File {
	protected $handle = null;

	function __construct($filepath,$mode) {
        if (($this->handle=fopen($filepath,$mode))===FALSE) {
            throw new Exception("File is not writable");
        }
	}

	function Eof() {
		return 	feof($this->handle);
	}
	
	function ReadAll() {
		$all = '';
		while (!feof($this->handle)) {
			$all .=  fgets($this->handle,1024);
		}

		return $all;
	}
	
	function ArrayReadAll($delim = "\r\n") {
		$all = $this->ReadAll();
		return explode($delim,$all);
	}
	
	function ReadLine() {
		if (!feof($this->handle)) {
			$line = fgets($this->handle,1024);
			return trim($line);
		} else {
			return false;
		}
	}
	
	function Close() {
		fclose($this->handle);
	}
	
	function Write($text) {
		fputs($this->handle ,$text);
	}
	
	function WriteLine($text) {
		fputs($this->handle ,$text . "\r\n" );
	}
	
	function WriteArray($ar,$sep) {
		for ($j=0; $j<count($ar)-1; $j++) {
		//foreach($ar as $l) {
			fputs($this->handle, $ar[$j] . $sep);
			//fputs($this->handle, $l . $sep);
		}
		fputs($this->handle, $ar[$j]);
	}
	
	function WriteLineArray($ar,$sep) {
		$this->WriteArray($ar,$sep);
		fputs($this->handle ,"\r\n" );
	}
	
	function NewLine() {
		fputs($this->handle,"\r\n");
	}
	
	function Merge($filename) {
		$inc = fopen($filename,"r");
		while (!feof($inc)) {
			fputs($this->handle,fread($inc, 1024));
		}
		fclose($inc);
	}	
}

class Debugger extends File {
	private $doDebug = false;	
	
	function __construct($filepath, $do = true) {
		parent::__construct($filepath,"w");
		$this->doDebug = $do;
	}
	
	
	
	function Debug($line, $funcname,  $message) {
		if ($this->doDebug) {
			$this->WriteLine("{$line} : {$funcname} -> {$message}");
		}
	} 
	
}

class SSI_Includer extends File {
	private $entire_file;

	function __construct($filepath) {
		//echo $filepath;
		parent::__construct($filepath,"r");
		if ($this->handle) {
			$this->entire_file = $this->ReadAll();
			$this->Close(); // non serve aperto
		}
			
	}
	
	function getArea($area) {
	/*
		restituisce l'area compresa tra due <!--$area-->
	*/
		if ($this->handle) {
			list($dump1, $ret, $dump2) = explode("<!--{$area}-->",$this->entire_file);
			return "<!--SSI_INCLUDER_BEGIN-->" . $ret . "<!--SSI_INCLUDER_END-->";
		} else {
			return "Bad file handler";
		}
	}
}

?>
