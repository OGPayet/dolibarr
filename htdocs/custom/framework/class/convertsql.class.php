<?php



class Convertsql{

	public $tbl = array();

	public $col = array();

	protected
				$dolv = 0
		 , 	$shortdolv = 0;

	public function __construct($db){
		$this->dolv= DOL_VERSION;
		$this->shortdolv =  substr(DOL_VERSION, 0, -2);
	}

	public function GetTbl($name){
		if(isset($this->tbl[$name]))
			return $this->tbl[$name];
		elseif(isset($this->tbl[$this->dolv][$name]))
			return $this->tbl[$this->dolv][$name];
		elseif(isset($this->tbl[$this->shortdolv][$name]))
			return $this->tbl[$this->shortdolv][$name];

		return $name;
	}

	public function GetCol($name){
		if(isset($this->col[$name]))
			return $this->col[$name];
		elseif(isset($this->col[$this->dolv][$name]))
			return $this->col[$this->dolv][$name];
		elseif(isset($this->col[$this->shortdolv][$name]))
			return $this->col[$this->shortdolv][$name];

		return $name;
	}
}

?>
