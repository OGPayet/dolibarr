<?php

class TBDDConnector extends TObjetStd {

	function __construct() {

        parent::set_table(MAIN_DB_PREFIX.'query_bdd_connector');
        parent::add_champs('host,login,password,db_name,db_type,charset',array('type'=>'string','length'=>128));
		parent::add_champs('port',array('type'=>'integer'));
		parent::add_champs('entity',array('type'=>'integer','index'=>true));
		parent::_init_vars();
        parent::start();

		$this->db_type='mysql';

		$this->TDBType=array(
			'mysql'=>'MySQL'
		);

		$this->pdodb = null;

		$this->is_connected = false;

	}

	function save(&$PDOdb) {

		global $db,$conf,$user;
		$this->entity = $conf->entity;

		parent::save($PDOdb);

	}
	function connect() {

		if(empty($this->db_name)) $this->pdodb = new TPDOdb;
		else{

			$cs = $this->db_type.':dbname='.$this->db_name.';host='.$this->host;
			if(!empty($port)) 			$cs.= ';port='.$port;
			if(!empty($this->charset) ) $cs.=';charset='.$this->charset;

			$this->pdodb=new TPDOdb('', $cs, $this->login, $this->password);

		}

		if(empty($this->pdodb->error)) $this->is_connected = true;

		return $this->is_connected;
	}

	static function getCombo(&$PDOdb) {
		global $langs;
		return array(0=>$langs->trans('DolibarrDB')) + TRequeteCore::get_keyval_by_sql($PDOdb,"SELECT rowid,CONCAT(login,'@', host,':',db_name) as host FROM ". MAIN_DB_PREFIX.'query_bdd_connector','rowid','host');

	}

}
