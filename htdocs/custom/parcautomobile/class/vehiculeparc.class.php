<?php 
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php'; 

dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/parcautomobile/class/marques.class.php');
dol_include_once('/parcautomobile/class/costsvehicule.class.php');
dol_include_once('/parcautomobile/class/contrat_parc.class.php');
dol_include_once('/parcautomobile/class/interventions_parc.class.php');


class vehiculeparc extends Commonobject{ 

	public $errors = array();
	public $rowid;

	public $element='vehiculeparc';
	public $table_element='vehiculeparc';
	
	public function __construct($db){ 
		$this->db = $db;
		return 1;
    }


	public function create($echo_sql=0)
	{
		global $conf;
		$sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

		$sql.= " plaque, logo, model, conducteur, lieu, date_immatriculation, date_contrat, num_chassi, statut, nb_porte, nb_place, kilometrage, unite, color, value_catalogue, value_residuelle, anne_model, transmission, type_carburant, emission_co2, nb_chevaux, tax, puissance, etiquettes, sendmail, entity)";
		
		$sql.= " VALUES (";
			
			$sql.= ($this->plaque?"'".$this->db->escape($this->plaque)."'":"null");
			$sql.= ", ".($this->logo?"'".$this->db->escape($this->logo)."'":"null");
			$sql.= ", ".($this->model>0?$this->model:"null");	
			$sql.= ", ".($this->conducteur>0?$this->conducteur:"null");	
			$sql.= ", ".($this->lieu?"'".$this->db->escape($this->lieu)."'":"null");
	        $sql .= ", ".($this->date_immatriculation != '' ? "'".$this->db->idate($this->date_immatriculation)."'" : 'null');
	        $sql.= ", ".($this->date_contrat != '' ? "'".$this->db->idate($this->date_contrat)."'" : 'null');
			$sql.= ", ".($this->num_chassi>0?$this->num_chassi:"null");	
			$sql.= ", ".($this->statut?"'".$this->db->escape($this->statut)."'":"null");
			$sql.= ", ".($this->nb_porte>0?$this->nb_porte:"null");	
			$sql.= ", ".($this->nb_place>0?$this->nb_place:"null");	
			$sql.= ", ".($this->kilometrage>0?$this->kilometrage:"null");	
			$sql.= ", ".($this->unite?"'".$this->db->escape($this->unite)."'":"null");
			$sql.= ", ".($this->color?"'".$this->db->escape($this->color)."'":"null");
			$sql.= ", ".($this->value_catalogue?"'".$this->db->escape($this->value_catalogue)."'":"null");
			$sql.= ", ".($this->value_residuelle?"'".$this->db->escape($this->value_residuelle)."'":"null");
			$sql.= ", ".($this->anne_model?"'".$this->db->escape($this->anne_model)."'":"null");
			$sql.= ", ".($this->transmission?"'".$this->db->escape($this->transmission)."'":"null");
			$sql.= ", ".($this->type_carburant?"'".$this->db->escape($this->type_carburant)."'":"null");
	        
			$sql.= ", ".($this->emission_co2>0?$this->emission_co2:"null");	
			$sql.= ", ".($this->nb_chevaux>0?$this->nb_chevaux:"null");	
			$sql.= ", ".($this->tax>0?$this->tax:"null");	
			$sql.= ", ".($this->puissance>0?$this->puissance:"null");	
			$sql.= ", ".($this->etiquettes?"'".$this->db->escape($this->etiquettes)."'":"null");
			$sql.= ", ".($this->sendmail>0?$this->sendmail:"null");	
			$sql.= ", ".$conf->entity;

		$sql.= ")";


		// die($sql);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			print_r($this->errors);
			die();
			return 0;
		} 
		return $this->db->db->insert_id;
	}

	public function update($id)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (!$id || $id <= 0)
			return false;
		
	    $sql = 'UPDATE ' . MAIN_DB_PREFIX .get_class($this). ' SET ';

	   $sql .= "plaque=".($this->plaque ? "'".$this->db->escape($this->plaque)."'":"null").', ';
	   $sql .= "logo=".($this->logo>0?"'".$this->db->escape($this->logo)."'":"null").', ';
	   $sql .= "model=".($this->model>0?$this->db->escape($this->model):"null").', ';
	   $sql .= "conducteur=".($this->conducteur>0?$this->db->escape($this->conducteur):"null").', ';
	   $sql .= "lieu=".($this->lieu>0? "'".$this->db->escape($this->lieu)."'":"null").', ';
	   $sql .= "date_immatriculation=".($this->date_immatriculation? "'".$this->db->idate($this->date_immatriculation)."'" :"null").', ';
	   $sql .= "date_contrat=".($this->date_contrat ? "'".$this->db->idate($this->date_contrat)."'" :"null").', ';
	   $sql .= "num_chassi=".($this->num_chassi>0?$this->db->escape($this->num_chassi):"null").', ';
	   $sql .= "statut=".($this->statut ? "'".$this->db->escape($this->statut)."'":"null").', ';
	   $sql .= "nb_porte=".($this->nb_porte>0?$this->db->escape($this->nb_porte):"null").', ';
	   $sql .= "nb_place=".($this->nb_place>0?$this->db->escape($this->nb_place):"null").', ';
	   $sql .= "kilometrage=".($this->kilometrage>0?$this->db->escape($this->kilometrage):"null").', ';
	   $sql .= "unite=".($this->unite ? "'".$this->db->escape($this->unite)."'":"null").', ';
	   $sql .= "color=".($this->color ? "'".$this->db->escape($this->color)."'":"null").', ';
	   $sql .= "value_catalogue=".($this->value_catalogue>0?$this->db->escape($this->value_catalogue):"null").', ';
	   $sql .= "value_residuelle=".($this->value_residuelle>0?$this->db->escape($this->value_residuelle):"null").', ';
	   $sql .= "anne_model=".($this->anne_model ? "'".$this->db->escape($this->anne_model)."'":"null").', ';
	   $sql .= "transmission=".($this->transmission ? "'".$this->db->escape($this->transmission)."'":"null").', ';
	   $sql .= "type_carburant=".($this->type_carburant ? "'".$this->db->escape($this->type_carburant)."'":"null").', ';
	   $sql .= "emission_co2=".($this->emission_co2>0?$this->db->escape($this->emission_co2):"null").', ';
	   $sql .= "nb_chevaux=".($this->nb_chevaux>0?$this->db->escape($this->nb_chevaux):"null").', ';
	   $sql .= "tax=".($this->tax>0?$this->db->escape($this->tax):"null").', ';
	   $sql .= "puissance=".($this->puissance>0?$this->db->escape($this->puissance):"null").', ';
	   $sql .= "etiquettes=".($this->etiquettes ? "'".$this->db->escape($this->etiquettes)."'":"null").', ';
	   $sql .= "parc=".($this->parc>0?$this->db->escape($this->parc):"null").', ';
	   $sql .= "sendmail=".($this->sendmail>0?$this->db->escape($this->sendmail):"null ").', ';
	   $sql .= "entity=".$this->db->escape($this->entity)." ";

	    // $sql  = substr($sql, 0, -1);
	    $sql .= ' WHERE rowid = ' . $id;
	    $resql = $this->db->query($sql);
	    if ($resql) {
			$result=$this->insertExtraFields();
		}
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			print_r($this->errors);
			die();
			return -1;
		} 
		return 1;
	}


	public function createold($echo_sql=0,$insert)
	{

		$sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

		foreach ($insert as $column => $value) {
			$alias = (is_numeric($value)) ? "" : "'";
			if($value != ''){
				$sql_column .= " , `".$column."`";
				$sql_value .= " , ".$alias.$value.$alias;
			}
		}

		$sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
		// die($sql);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			print_r($this->errors);
			die();
			return 0;
		} 
		return $this->db->db->insert_id;
	}

	public function updateold($id, array $data,$echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (!$id || $id <= 0)
			return false;
		
	    $sql = 'UPDATE ' . MAIN_DB_PREFIX .get_class($this). ' SET ';

	    if (count($data) && is_array($data))
	        foreach ($data as $key => $value) {
	            $val = is_numeric($value) ? $value : '"'. $value .'"';
	        	$val = ($value == '') ? 'NULL' : $val;
            	$sql .= '`'. $key. '` = '. $val .',';
	        }

	    $sql  = substr($sql, 0, -1);
	    $sql .= ' WHERE rowid = ' . $id;
	    $resql = $this->db->query($sql);
	    // die($sql);
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			print_r($this->errors);
			die();
			return -1;
		} 
		return 1;
	}

	// public function create($echo_sql=0,$insert)
	// {

	// 	$sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

	// 	foreach ($insert as $column => $value) {
	// 		$alias = (is_numeric($value)) ? "" : "'";
	// 		$sql_column .= " , `".$column."`";
	// 		$sql_value .= " , ".$alias.$value.$alias;
	// 	}

	// 	$sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
	// 	// die($sql);
	// 	$resql = $this->db->query($sql);
	// 	if (!$resql) {
	// 		$this->db->rollback();
	// 		$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			// print_r($this->errors);
			// die();
	// 		return 0;
	// 	} 
	// 	return $this->db->db->insert_id;
	// }
	
	
	public function delete($echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql 	= 'DELETE FROM ' . MAIN_DB_PREFIX .get_class($this).' WHERE rowid = ' . $this->rowid;
		$resql 	= $this->db->query($sql);
		
		if ($resql)
        {
	        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element."_extrafields";
	        $sql .= " WHERE fk_object=".$this->id;

	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        }
        }
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();
			return -1;
		} 

		return 1;
	}

    
	public function fetchAllold($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);
		$sql = "SELECT * FROM ";
		$sql .= MAIN_DB_PREFIX .get_class($this);

		if (!empty($filter)) {
			$sql .= " WHERE 1>0 ".$filter;
		}
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}

		if (!empty($limit)) {
			if($offset==1)
				$sql .= " limit ".$limit;
			else
				$sql .= " limit ".$offset.",".$limit;				
		}

		$this->rows = array();
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass;
                $line->id    		 =  $obj->rowid;
                $line->rowid    		 =  $obj->rowid;
				$line->plaque 		 =  $obj->plaque;
				$line->logo 		 =  $obj->logo;
				$line->model 		 =  $obj->model;
				$line->conducteur 		 =  $obj->conducteur;
				$line->lieu 		 =  $obj->lieu;
				$line->date_immatriculation 			 =  $obj->date_immatriculation;
				$line->date_contrat 			 =  $obj->date_contrat;
				$line->num_chassi 			 =  $obj->num_chassi;
				$line->statut 			 =  $obj->statut;
				$line->etiquettes 			 =  $obj->etiquettes;
				$line->value_catalogue 			 =  $obj->value_catalogue;
				$line->value_residuelle 			 =  $obj->value_residuelle;
				$line->nb_porte 			 =  $obj->nb_porte;
				$line->nb_place 			 =  $obj->nb_place;
				$line->kilometrage 			 =  $obj->kilometrage;
				$line->unite 			 =  $obj->unite;
				$line->color 			 =  $obj->color;
				$line->anne_model 			 =  $obj->anne_model;
				$line->transmission 			 =  $obj->transmission;
				$line->type_carburant 			 =  $obj->type_carburant;
				$line->emission_co2 			 =  $obj->emission_co2;
				$line->nb_chevaux 			 =  $obj->nb_chevaux;
				$line->tax 			 =  $obj->tax;
				$line->puissance 			 =  $obj->puissance;
				$line->sendmail 			 =  $obj->sendmail;
                // ....

				$this->rows[] 	= $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}


	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '',$join='')
	{
		global $conf;
		dol_syslog(__METHOD__, LOG_DEBUG);
		$sql = "SELECT ".MAIN_DB_PREFIX.$this->table_element.".* FROM ";
		$sql .= MAIN_DB_PREFIX .$this->table_element;

		if (!empty($join)) {
			$sql .= " ".$join; 
		}
		
		$sql .= ' WHERE '.MAIN_DB_PREFIX.$this->table_element.'.entity ='.$conf->entity;
		if (!empty($filter)) {
			$sql .= " ".$filter;
		}
		
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}

		if (!empty($limit)) {
			if($offset==1)
				$sql .= " limit ".$limit;
			else
				$sql .= " limit ".$offset.",".$limit;				
		}

		// echo $sql;
		$this->rows = array();
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass;
                $line->id    		 =  $obj->rowid;
                $line->rowid    	 =  $obj->rowid;
				$line->plaque 		 =  $obj->plaque;
				$line->logo 		 =  $obj->logo;
				$line->model 		 =  $obj->model;
				$line->conducteur 		 =  $obj->conducteur;
				$line->lieu 		 =  $obj->lieu;
				$line->date_immatriculation 			 =  $obj->date_immatriculation;
				$line->date_contrat 			 =  $obj->date_contrat;
				$line->num_chassi 			 =  $obj->num_chassi;
				$line->statut 			 =  $obj->statut;
				$line->etiquettes 			 =  $obj->etiquettes;
				$line->value_catalogue 			 =  $obj->value_catalogue;
				$line->value_residuelle 			 =  $obj->value_residuelle;
				$line->nb_porte 			 =  $obj->nb_porte;
				$line->nb_place 			 =  $obj->nb_place;
				$line->kilometrage 			 =  $obj->kilometrage;
				$line->unite 			 =  $obj->unite;
				$line->color 			 =  $obj->color;
				$line->anne_model 			 =  $obj->anne_model;
				$line->transmission 			 =  $obj->transmission;
				$line->type_carburant 			 =  $obj->type_carburant;
				$line->emission_co2 			 =  $obj->emission_co2;
				$line->nb_chevaux 			 =  $obj->nb_chevaux;
				$line->tax 			 =  $obj->tax;
				$line->puissance 			 =  $obj->puissance;
				$line->sendmail 			 =  $obj->sendmail;
				$line->entity 			 =  $obj->entity;
				// Retreive all extrafield
				// fetch optionals attributes and labels

                // ....

				$this->rows[] 	= $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}



	public function fetch($id)
	{
		global $conf;
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT * FROM ' . MAIN_DB_PREFIX .get_class($this). ' WHERE rowid = ' . $id;
		$sql .= ' AND entity ='.$conf->entity;
		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			
			if ($numrows) {
				$obj 			  	  = $this->db->fetch_object($resql);
                $this->id         	  = $obj->rowid;
                $this->rowid      	  = $obj->rowid;
                $this->plaque 		 =  $obj->plaque;
                $this->logo 		 =  $obj->logo;
				$this->model 		 =  $obj->model;
				$this->conducteur 		 =  $obj->conducteur;
				$this->lieu 		 =  $obj->lieu;
				$this->date_immatriculation 			 =  $obj->date_immatriculation;
				$this->date_contrat 			 =  $obj->date_contrat;
				$this->num_chassi 			 =  $obj->num_chassi;
				$this->statut 			 =  $obj->statut;
				$this->etiquettes 			 =  $obj->etiquettes;
				$this->value_catalogue 			 =  $obj->value_catalogue;
				$this->value_residuelle 			 =  $obj->value_residuelle;
				$this->nb_porte 			 =  $obj->nb_porte;
				$this->nb_place 			 =  $obj->nb_place;
				$this->kilometrage 			 =  $obj->kilometrage;
				$this->unite 			 =  $obj->unite;
				$this->color 			 =  $obj->color;
				$this->anne_model 			 =  $obj->anne_model;
				$this->transmission 			 =  $obj->transmission;
				$this->type_carburant 			 =  $obj->type_carburant;
				$this->emission_co2 			 =  $obj->emission_co2;
				$this->nb_chevaux 			 =  $obj->nb_chevaux;
				$this->tax 			 =  $obj->tax;
				$this->puissance 			 =  $obj->puissance;
				$this->sendmail 			 =  $obj->sendmail;
				$this->entity 			 =  $obj->entity;
				$this->fetch_optionals();
				
                // ....
			}

			$this->db->free($resql);

			if ($numrows) {
				return 1 ;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			return -1;
		}
	}

	public function select_with_filter($selected=0,$name='vehicule',$showempty=1,$val="rowid",$opt="label",$id='',$attr=''){

	    global $conf;
	    $model = new modeles($this->db);
	    $marque = new marques($this->db);
	    $moreforfilter = '';
	    $nodatarole = '';
	    $id = (!empty($id)) ? $id : $name;

	    $moreforfilter.='<select width="100%" '.$attr.' class="flat minwidth200 maxwidth200" id="select_'.$id.'" name="'.$name.'">';
	    if ($showempty) $moreforfilter.='<option value="0">&nbsp;</option>';

    	$sql = "SELECT * FROM ".MAIN_DB_PREFIX.get_class($this);
		$sql .= ' WHERE entity ='.$conf->entity;
		//echo $sql."<br>";
    	$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$moreforfilter.='<option value="'.$obj->$val.'"';
	            if ($obj->$val == $selected) $moreforfilter.=' selected';
	            $moreforfilter.='>'.$this->get_nom($obj->rowid,1).'</option>';
			}
			$this->db->free($resql);
		}

	    $moreforfilter.='</select>';
	    $moreforfilter.='<script>$(function(){$("#select_'.$id.'").select2();})</script>';
	    return $moreforfilter;
	}

    function getNomUrl($withpicto=0, $option='', $get_params='', $notooltip=0, $save_lastsearch_value=-1)
    {
        global $langs, $conf, $user;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result='';
        $label='';
        $url='';


        $id_marq = $this->getMarqVehicul($this->id);
        $marq = new marques($this->db);
        $model = new modeles($this->db);
        $model->fetch($this->model);
        $marq->fetch($id_marq);
		if (!empty($marq->logo))
		{
            $minifile = getImageFileNameForSize($marq->logo, '');  

			$label .= '<div class="photointooltip">';
			// $label .= Form::showphoto('parcautomobile', $marq, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1); 
			$label .= '<img class="photo photowithmargin " height="60"  title="'.$minifile.'" alt="Fichier binaire" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&file=marques/'.$id_marq.'/'.$minifile.'&perm=download" border="0" name="image" >';
			// Force height to 60 so we total height of tooltip can be calculated and collision can be managed
			$label .= '</div><div style="clear: both;"></div>';
		}

		// Info Login
		$label .= '<div class="centpercent">';
		$label .= '<u>'.$langs->trans('Vehicule').'</u><br>';
		$label .= '<br><b>'.$langs->trans('plaque').':</b> '.$this->plaque;
		$label .= '<br><b>'.$langs->trans('marque').':</b> '.$marq->label;
		$label .= '<br><b>'.$langs->trans('model').':</b> '.$model->label;
		if($this->conducteur){
			$user_ = new User($this->db);
			$user_->fetch($this->conducteur);
			$label .= '<br><b>'.$langs->trans("conducteur").':</b> '.$user_->lastname.' '.$user_->firstname;
		}
		$label .= '</div>';
		


        $linkclose='';
        if (empty($notooltip))
        {
            // $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';
        }
        $linkstart = "";
        $linkend = "";
        $result = "";
        $url = dol_buildpath('/parcautomobile/card.php?id='.$this->id,2);
        if(!empty($this->plaque)){
        	$ref=$this->plaque;
        }else
        	$ref=$this->rowid;
        if ($ref) {
            $linkstart = '<a href="'.$url.'"';

            $linkstart.=$linkclose.'>';

            $linkend='</a>';

            $result .= $linkstart;
        	$result.= $ref;
        	$result .= $linkend;
        }

        return $result;
    }

    public function getYears($debut="debut")
    {
    	global $conf;
        $sql = 'SELECT YEAR('.$debut.') as years FROM ' . MAIN_DB_PREFIX.get_class($this);
		$sql .= ' WHERE entity ='.$conf->entity;
        $resql = $this->db->query($sql);
        $years = array();
        if ($resql) {
            $num = $this->db->num_rows($resql);
            while ($obj = $this->db->fetch_object($resql)) {
                $years[$obj->years] = $obj->years;
            }
            $this->db->free($resql);
        }

        return $years;
    }

	public function select_conducteur($selected=0,$name='conducteur',$showempty=1,$type="Internal"){
	    global $conf;
	    $moreforfilter = '';
	    $nodatarole = '';
	    $id = (!empty($id)) ? $id : $name;
	    $objet = "label";
	    $moreforfilter.='<select class="flat minwidth200 maxwidth200" id="'.$name.'" name="'.$name.'" '.$nodatarole.'>';
	    if ($showempty) $moreforfilter.='<option value="0">&nbsp;</option>';

    	$sql= "SELECT * FROM ".MAIN_DB_PREFIX."user";
    	$sql .= ' WHERE entity IN (0,'.$conf->entity.')';

    	if($type == 'Internal' || empty($type)) $sql .= ' AND fk_soc = 0 OR fk_soc IS NULL ';
    	elseif($type == 'External') $sql .= ' AND fk_soc > 0';
    	$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			
			while ($obj = $this->db->fetch_object($resql)) {
				$moreforfilter.='<option value="'.$obj->rowid.'" data-ref="'.$obj->label.'"';
	            if ($obj->rowid == $selected) $moreforfilter.=' selected';
	            $moreforfilter.='>'.$obj->firstname.' '.$obj->lastname.'</option>';
			}
			$this->db->free($resql);
		}

	    $moreforfilter.='</select>';
	    $moreforfilter.='<script>$(function(){$("#'.$id.'").select2();})</script>';

	    return $moreforfilter;
	}


	public function select_fournisseur($selected=0,$name='fournisseur')
	{
	    $id = (!empty($id)) ? $id : $name;

	    $select = '';
		$select.='<select class="flat minwidth200 maxwidth200" id="'.$id.'" name="'.$name.'" >';
	    $select.='<option value="0">&nbsp;</option>';
		global $conf;
    	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur = 1";
		$sql .= ' AND entity IN ('.getEntity('societe').')';
		//echo $sql."<br>";
    	$resql = $this->db->query($sql);
    	$select.='<option value="0"></option>'; 
		if ($resql) {
			$num = $this->db->num_rows($resql);
			while ($obj = $this->db->fetch_object($resql)) {
				$select.='<option value="'.$obj->rowid.'"';
	            if ($obj->rowid == $selected) $select.='selected';
	            $select.='>'.$obj->nom.'</option>';
			}
			$this->db->free($resql);
		}

		$select.='</select>';
		$select.='<script>$(function(){$("#'.$id.'").select2()})</script>';
	    return $select;
	}


	

	public function select_etat($value='',$name)
	{
		$select .= '<select name="'.$name.'" id="'.$name.'" >';
			$select.='<option value=""></option>';
			$select.='<option value="En attent">En attent</option>';
		$select .= '</select>';
		
		return $select;
	}

	public function type_carburant($value='',$name='type_carburant')
	{
		$select = '<select name="'.$name.'" id="'.$name.'" class="minwidth200 maxwidth200">';
			$select .='<option value="false"></option>';
			$select .='<option value="essence">Essence</option>';
			$select .='<option value="diesel">Diesel</option>';
			$select .='<option value="lpg">LPG</option>';
			$select .='<option value="electric">Electrique</option>';
			$select .='<option value="hybrid">Hybride</option>';
		$select .= '</select>';
			$select=str_replace('value="'.$value.'"', 'value="'.$value.'" selected', $select);
		
		return $select;
	}

	// public function select_model($value=0,$name='model')
	// {
	// 	$modeles = new modeles($this->db);
	// 	$marque = new marques($this->db);
	// 	$modeles->fetchAll();
	// 	$select = '<select name="'.$name.'" id="select_'.$name.'" >';
	// 	for ($i=0; $i < count($modeles->rows); $i++) { 
	// 		$item=$modeles->rows[$i];
	// 		$marque->fetch($item->marque);
	// 		$select.='<option value="'.$item->rowid.'">'.$marque->label.'/'.$item->label.'</option>';
	// 	}
	// 	$select.='</select>';
	// 	$select= str_replace('value="'.$item->rowid.'"', 'value="'.$item->rowid.'" selected', $select);
	// 	return $select;
	// }

	public function select_anne($anne=0){
		if($anne==0){
			$anne=date('Y');
		}
		$select='<select name="anne_model" class="select_anne" onchange="change_anne(this)" >';
		$min=$anne-10;
		for ($i=$min; $i <= $anne ; $i++) { 
			$select.='<option value="'.$i.'">'.$i.'</option>';
		}
		$select.='</select>';
		$select=str_replace('value="'.$anne.'"', 'value="'.$anne.'" selected', $select);
		return $select;
	}
	public function getMarqVehicul($id_vehicule)
	{
		$id_marque = '';
		if($id_vehicule){
			$vehicule = new vehiculeparc($this->db);
			$model = new modeles($this->db);
			$marque = new marques($this->db);
			$vehicule->fetch($id_vehicule);
			$model->fetch($vehicule->model);
	        $marque->fetch($model->marque);
	        $id_marque = $marque->rowid;
	    }
	    return $id_marque;
	}

	public function get_nom($id,$ref=0){
		$nom = '';
		if($id > 0){
			$vehicule = new vehiculeparc($this->db);
			$model = new modeles($this->db);
			$marque = new marques($this->db);
			$vehicule->fetch($id);
			$model->fetch($vehicule->model);
	        $marque->fetch($model->marque);
	        $nom=$marque->label.'/'.$model->label;
	        if($ref){
	        	$nom.='/'.$vehicule->plaque;
	        }
		}
        return $nom;
	}
	public function get_nom_url($id,$ref=0){
		global $langs;
		$nom = '';
		if($id > 0){
			$vehicule = new vehiculeparc($this->db);
			$model = new modeles($this->db);
			$marque = new marques($this->db);
			$vehicule->fetch($id);
			$model->fetch($vehicule->model);
	        $marque->fetch($model->marque);
	        $nom=$marque->label.'/'.$model->label;
	        if($ref){
	        	$nom.='/'.$vehicule->plaque;
	        }
	        $label='';
	        if (!empty($marque->logo))
			{
	            $minifile = getImageFileNameForSize($marque->logo, '');  

				$label .= '<div class="photointooltip">';
				// $label .= Form::showphoto('parcautomobile', $marq, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1); 
				$label .= '<img class="photo photowithmargin " height="60"  title="'.$minifile.'" alt="Fichier binaire" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&file=marques/'.$marque->rowid.'/'.$minifile.'&perm=download" border="0" name="image" >';
				// Force height to 60 so we total height of tooltip can be calculated and collision can be managed
				$label .= '</div><div style="clear: both;"></div>';
			}

			// Info Login
			$label .= '<div class="centpercent">';
			$label .= '<u>'.$langs->trans('Vehicule').'</u><br>';
			$label .= '<br><b>'.$langs->trans('plaque').':</b> '.$vehicule->plaque;
			$label .= '<br><b>'.$langs->trans('marque').':</b> '.$marque->label;
			$label .= '<br><b>'.$langs->trans('model').':</b> '.$model->label;
			if($this->conducteur){
				$user_ = new User($this->db);
				$user_->fetch($this->conducteur);
				$label .= '<br><b>'.$langs->trans("conducteur").':</b> '.$user_->lastname.' '.$user_->firstname;
			}
			$label .= '</div>';
		}
        return '<a href="'.dol_buildpath("parcautomobile/card.php?id=".$this->rowid,2).'" class="classfortooltip" title="'.dol_escape_htmltag($label, 1).'">'.$nom.'</a>';
	}
	public function datachart($anne=0)
	{
		if(empty($anne)){
			$anne=date('Y');
		}
		$data=[];
		$costs = new costsvehicule($this->db);
		$this->fetchAll();
		for ($i=0; $i < count($this->rows); $i++) {
		$mont=0; 
			$vehicule=$this->rows[$i];
			if($vehicule->rowid){
				$costs->fetchAll('','',0,0,'AND vehicule ='.$vehicule->rowid.' AND YEAR(date) ="'.$anne.'"');
				for ($k=0; $k < count($costs->rows); $k++) { 
					$cost=$costs->rows[$k];
					$mont+=$cost->prix;
				}
				$data[$vehicule->rowid] =$mont;
			}
		}
		return $data;
	}
	
	public function select_unite($value='',$name='unite')
	{
		$select .='<select name="'.$name.'" class="'.$name.' minwidth100 maxwidth100" id="select_unite">';
			$select .='<option value=""></option>';
			$select .='<option value="kilometers">kilomètres</option>';
			$select .='<option value="miles" >Miles</option>';
		$select .='</select>';
		$select =str_replace('value="'.$value.'"', 'value="'.$value.'" selected', $select);
		return $select;
	}

	public function datachart2($vehicule)
	{
		// if(empty($anne)){
		// 	$anne=date('Y');
		// }
	
		$contrat = new contrat_parc($this->db);
		$service = new interventions_parc($this->db);
		$data=[];
		$contrat->fetchAll('','',0,0,'AND vehicule ='.$vehicule);
		$service->fetchAll('','',0,0,'AND vehicule ='.$vehicule);
		$total=0;
		for ($i=0; $i < count($contrat->rows); $i++) { 
			$elem = $contrat->rows[$i];
			if(!empty($elem->services_inclus)){
				$contrats = json_decode($elem->services_inclus);
				foreach ($contrats as $key => $value) {
					if($value->prix){
						$total+=$value->prix;
					}
				}
			}
		}
		$data[$vehicule]['contrat']=$total;
		$total_2=0;
		
		for ($k=0; $k < count($service->rows); $k++) { 
			$elem = $service->rows[$k];
			if(!empty($elem->service_inclus)){
				$services=json_decode($elem->service_inclus);
				foreach ($services as $key => $value) {
					if($value->prix){
						$total_2+=$value->prix;
					}
				}
			}
		}
		$data[$vehicule]['services']=$total_2;
		return $data;
	}

	public function parcautomobilepermissionto($source){
	    if(is_dir($source)) {
	    	@chmod($source, 0775);
	        $dir_handle=opendir($source);
	        while($file=readdir($dir_handle)){
	            if($file!="." && $file!=".."){
	                if(is_dir($source."/".$file)){
	                    @chmod($source."/".$file, 0775);
	                    $this->parcautomobilepermissionto($source."/".$file);
	                } else {
	                    @chmod($source."/".$file, 0664);
	                }
	            }
	        }
	        closedir($dir_handle);
	    } else {
	        @chmod($source, 0664);
	    }
	}


	public function upgradeModulePrcAutmobil()
    {
        global $conf, $langs;
        
		dol_include_once('/parcautomobile/core/modules/modparcautomobile.class.php');

        $modcore = new modparcautomobile($this->db);
        
        $lastversion    = $modcore->version;
        $currentversion = dolibarr_get_const($this->db, 'PRCAUTMOBIL_LAST_VERSION_OF_MODULE', $conf->entity);
        
        if (!$currentversion || ($currentversion && $lastversion != $currentversion)){
            $res = $this->InitPrcAutmobil();
            if($res)
                dolibarr_set_const($this->db, 'PRCAUTMOBIL_LAST_VERSION_OF_MODULE', $lastversion, 'chaine', 0, '', $conf->entity);
            return 1;
        }

        return 0;
    }

	public function InitPrcAutmobil()
	{
		global $conf;


		if (!dolibarr_get_const($this->db,'PARCAUTOMOBILE_CHECKINTERVENTIONSFORMAIL',$conf->entity))
			dolibarr_set_const($this->db,'PARCAUTOMOBILE_CHECKINTERVENTIONSFORMAIL',0,'chaine',0,'',$conf->entity);
		if (!dolibarr_get_const($this->db,'PARCAUTOMOBILE_NUMBEROFDAYSBEFORETOSENDMAIL',$conf->entity))
			dolibarr_set_const($this->db,'PARCAUTOMOBILE_NUMBEROFDAYSBEFORETOSENDMAIL',0,'chaine',0,'',$conf->entity);

		dol_include_once('/parcautomobile/core/modules/modparcautomobile.class.php');
		$modlcore = new modparcautomobile($this->db);

		$modlcore->cronjobs[0]['entity']  	= $conf->entity;
        $modlcore->cronjobs[0]['label']  	= 'parcautomobilecheckInterventionsMails';
        $modlcore->cronjobs[0]['jobtype']  	= 'method';
        $modlcore->cronjobs[0]['class']  	= 'parcautomobile/class/interventions_parc.class.php';
        $modlcore->cronjobs[0]['objectname']  = 'interventions_parc';
        $modlcore->cronjobs[0]['method'] 	= 'checkInterventionsMails';
        $modlcore->cronjobs[0]['frequency'] = 1;
        $modlcore->cronjobs[0]['unitfrequency'] = 86400;
        $modlcore->cronjobs[0]['priority'] 	= 10;
        $modlcore->cronjobs[0]['datestart'] = date('Y-m-d H:i:s');
        $modlcore->cronjobs[0]['status'] 	= 1;
        $modlcore->cronjobs[0]['test'] 		= '$conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL';
        $modlcore->cronjobs[0]['note'] 		= 'parcautomobilecheckInterventionsMails';


        $modlcore->insert_cronjobs();



        $sql2 = "UPDATE " . MAIN_DB_PREFIX. "cronjob SET status = 1 WHERE module_name = 'parcautomobile' AND classesname = 'parcautomobile/class/interventions_parc.class.php' AND objectname = 'interventions_parc' AND methodename = 'checkInterventionsMails'";
        $resql = $this->db->query($sql2);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."parc` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`adress` varchar(355) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."parc` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."parc` MODIFY label varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."parc` MODIFY adress varchar(355) NULL";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."vehiculeparc` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`plaque` varchar(255) NULL,
		  	`logo` varchar(255) NULL,
		  	`model`  int(11) NULL,
		  	`conducteur` int(11) NULL,
		  	`lieu` varchar(255) NULL,
		  	`date_immatriculation` date NULL,
		  	`date_contrat` date NULL,
		  	`num_chassi` int(11) NULL,
		  	`statut` varchar(255) NULL,
		  	`nb_porte` int(11) NULL,
		  	`nb_place` int(11) NULL,
		  	`kilometrage` DECIMAL NULL,
		  	`unite` varchar(255) NULL,
		  	`color` varchar(255) NULL,
		  	`value_catalogue` DECIMAL NULL,
		  	`value_residuelle` DECIMAL NULL,
		  	`anne_model` varchar(255) NULL,
		  	`transmission` varchar(255) NULL,
		  	`type_carburant` varchar(255) NULL,
		  	`emission_co2` float NULL,
		  	`nb_chevaux` DECIMAL NULL ,
		  	`tax` float NULL,
		  	`puissance` DECIMAL NULL,
		  	`etiquettes` varchar(255) NULL,
		  	`parc` int(11) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."vehiculeparc` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."vehiculeparc` ADD `etiquettes` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table `".MAIN_DB_PREFIX."vehiculeparc` add sendmail boolean NULL DEFAULT 0";
		$resql = $this->db->query($sql);
		

		$sql = "ALTER table  `".MAIN_DB_PREFIX."vehiculeparc` MODIFY plaque varchar(255) NULL,
			MODIFY logo varchar(255) NULL,
			MODIFY model  int(11) NULL, 
			MODIFY conducteur int(11) NULL, 
			MODIFY `lieu` varchar(255) NULL ,
			MODIFY `date_immatriculation` date NULL,
			MODIFY `date_contrat` date NULL,
			MODIFY `num_chassi` int(11) NULL,
			MODIFY `statut` varchar(255) NULL,
			MODIFY `nb_porte` int(11) NULL,
			MODIFY `nb_place` int(11) NULL,
			MODIFY `kilometrage` DECIMAL NULL,
			MODIFY `unite` varchar(255) NULL,
			MODIFY `color` varchar(255) NULL,
			MODIFY `value_catalogue` DECIMAL NULL,
			MODIFY `value_residuelle` DECIMAL NULL,
			MODIFY `anne_model` varchar(255) NULL,
			MODIFY `transmission` varchar(255) NULL,
			MODIFY `emission_co2` float NULL,
			MODIFY `nb_chevaux` DECIMAL NULL,
			MODIFY `tax` float NULL,
			MODIFY `puissance` DECIMAL NULL,
			MODIFY `etiquettes` varchar(255)` NULL,
			MODIFY `parc` int(11) NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."kilometrage` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`vehicule` int(11) NULL,
		  	`kilometrage` DECIMAL NULL,
		  	`unite` varchar(255) NULL,
		  	`date` date NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."kilometrage` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);
		
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."kilometrage` ADD `unite` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."kilometrage` MODIFY `date` date NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."kilometrage` 
			MODIFY `vehicule` int(11) NULL,
			MODIFY `kilometrage` DECIMAL NULL,
			MODIFY `unite` varchar(255) NULL,
			MODIFY `date` date NULL";
		$resql = $this->db->query($sql);



		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."interventions_parc` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`typeintervention` int(11) NULL,
		  	`vehicule` int(11) NULL,
		  	`acheteur` int(11) NULL,
		  	`kilometrage` DECIMAL NULL,
		  	`fournisseur` int(11) NULL,
		  	`ref_facture`  varchar(255) NULL,
		  	`prix` DECIMAL NULL,
		  	`date` date NULL,
		  	`service_inclus` text NULL,
		  	`notes` text NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."interventions_parc` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);


		$sql = "ALTER table `".MAIN_DB_PREFIX."interventions_parc` add service_inclus text NULL";
		$resql = $this->db->query($sql);
		
	

		$sql = "ALTER table  `".MAIN_DB_PREFIX."interventions_parc` 
				MODIFY `typeintervention` int(11) NULL,
				MODIFY `vehicule` int(11) NULL,
				MODIFY `acheteur` int(11) NULL,
				MODIFY `kilometrage` DECIMAL NULL,
				MODIFY `fournisseur` int(11) NULL,
				MODIFY `ref_facture` varchar(255) NULL,
				MODIFY `prix` DECIMAL NULL,
				MODIFY `date` date NULL,
				MODIFY `service_inclus` text NULL,
				MODIFY `notes` text NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."costsvehicule` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`type` varchar(255) NULL,
		  	`vehicule` int(11) NULL,
		  	`id_contrat` int(11) NULL,
		  	`id_intervention` int(11) NULL,
		  	`id_suiviessence` int(11) NULL,
		  	`prix` DECIMAL NULL,
		  	`date` date NULL,
		  	`notes` text NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."costsvehicule` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."costsvehicule` ADD `id_suiviessence` int(11) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."costsvehicule`
		MODIFY `type` varchar(255) NULL,
		MODIFY `vehicule` int(11) NULL,
		MODIFY `id_contrat` int(11) NULL,
		MODIFY `id_intervention` int(11) NULL,
		MODIFY `id_suiviessence` int(11) NULL,
		MODIFY `prix` DECIMAL NULL,
		MODIFY `date` date NULL,
		MODIFY `notes` text NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."typeintervention` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."typeintervention` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."typeintervention` MODIFY `label` varchar(255) NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."suivi_essence` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`vehicule` int(11) NULL,
		  	`litre` float NULL,
		  	`prix` float NULL,
		  	`date` date NULL,
		  	`acheteur` int(11) NULL,
		  	`fournisseur` int(11) NULL,
		  	`ref_facture` varchar(255) NULL,
		  	`kilometrage` DECIMAL NULL,
		  	`remarques` text NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."suivi_essence` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."suivi_essence` 
				MODIFY `vehicule` int(11) NULL,
				MODIFY `litre` float NULL,
				MODIFY `prix` float NULL,
				MODIFY `date` date NULL,
				MODIFY `acheteur` int(11) NULL,
				MODIFY `fournisseur` int(11) NULL,
				MODIFY `ref_facture` varchar(255) NULL,
				MODIFY `kilometrage` DECIMAL NULL,
				MODIFY `remarques` text NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."contrat_parc` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`vehicule` int(11) NULL,
		  	`kilometrage` DECIMAL NULL,
		  	`typecontrat` int(11) NULL,
		  	`activation_couts` float NULL,
		  	`type_montant`  varchar(255) NULL,
		  	`montant_recurrent` float NULL,
		  	`date_facture` date NULL,
		  	`date_debut` date NULL,
		  	`date_fin` date NULL,
		  	`responsable` int(11) NULL,
		  	`fournisseur` int(11) NULL,
		  	`conducteur` int(11) NULL,
		  	`ref_contrat` varchar(255) NULL,
		  	`etat` varchar(255) NULL,
		  	`condition` text NULL,
		  	`services_inclus` text NULL,
		  	`couts_recurrent` varchar(255) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."contrat_parc` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."contrat_parc` ADD `services_inclus` text NULL";
		$resql = $this->db->query($sql);
		$sql = "ALTER table  `".MAIN_DB_PREFIX."contrat_parc` ADD `couts_recurrent` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."contrat_parc` 
				MODIFY `vehicule` int(11) NULL,
				MODIFY `kilometrage` DECIMAL NULL,
				MODIFY `typecontrat` int(11) NULL,
				MODIFY `activation_couts` float NULL,
				MODIFY `type_montant` varchar(255) NULL,
				MODIFY `montant_recurrent` float NULL,
				MODIFY `date_facture` date NULL,
				MODIFY `date_debut` date NULL,
				MODIFY `date_fin` date NULL,
				MODIFY `responsable` int(11) NULL,
				MODIFY `fournisseur` int(11) NULL,
				MODIFY `conducteur` int(11) NULL,
				MODIFY `ref_contrat` varchar(255) NULL,
				MODIFY `etat` varchar(255) NULL,
				MODIFY `condition` text NULL,
				MODIFY `services_inclus` text NULL,
				MODIFY `couts_recurrent` varchar(255) NULL";
		$resql = $this->db->query($sql);



		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."typecontrat` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."typecontrat` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."typecontrat` MODIFY `label` varchar(255) NULL";
		$resql = $this->db->query($sql);


		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."statut` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`color` varchar(255) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."statut` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);

		$sql = "ALTER table  `".MAIN_DB_PREFIX."statut` ADD `color` varchar(255) NULL";
		$resql = $this->db->query($sql);

		$sql = "INSERT INTO `".MAIN_DB_PREFIX."statut` (`rowid`, `label`, `color`) VALUES
		(1, 'annuler', '#DBE270'),
		(2, 'active', '#F59A9A'),
		(3, 'inshop', '#62B0F7'),
		(4, 'inactive', '#FFB164'),
		(5, 'sold', '#59D859');";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."etiquettes_parc` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`color` varchar(255) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."etiquettes_parc` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);

		$sql = "INSERT INTO `".MAIN_DB_PREFIX."etiquettes_parc` (`rowid`, `label`, `color`, `entity`) VALUES
		(1, 'Automobile', '#e62828'),
		(2, '4x4', '#00d5d5', ".$conf->entity."),
		(3, 'Toyota', '#cc0066', ".$conf->entity."),
		(4, 'Pickup', '#caca39', ".$conf->entity."),
		(5, 'Profilé', '#000000', ".$conf->entity."),
		(6, 'Camion', '#004080', ".$conf->entity."),
		(7, 'Remorque', '#ff8000', ".$conf->entity.");";
		
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."marques` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`logo` varchar(255) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."marques` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);
		
		$sql = "ALTER table  `".MAIN_DB_PREFIX."marques` 
				MODIFY `label` varchar(255) NULL,
				MODIFY `logo` varchar(255) NULL";
		$resql = $this->db->query($sql);



		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."modeles` (
		  	`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  	`label` varchar(255) NULL,
		  	`marque` int(11) NULL,
		  	`entity` int(11) NOT NULL DEFAULT ".$conf->entity."
		);";
		$resql = $this->db->query($sql);
		$resql = $this->db->query("ALTER TABLE `".MAIN_DB_PREFIX."modeles` ADD `entity` int(11) NOT NULL DEFAULT ".$conf->entity);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."modeles` 
				MODIFY `label` varchar(255) NULL,
				MODIFY `marque` int(11) NULL";
		$resql = $this->db->query($sql);


		$sql = "ALTER table  `".MAIN_DB_PREFIX."interventions_parc` ADD `datevalidate` date NULL";
		$resql = $this->db->query($sql);
		
		$sql = "ALTER table  `".MAIN_DB_PREFIX."interventions_parc` ADD `checkmail` date NULL";
		$resql = $this->db->query($sql);
		

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."kilometrage_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."suivi_essence_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."contrat_parc_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."interventions_parc_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."costsvehicule_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."vehiculeparc_extrafields` (
	  		`rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    `fk_object` int(11) NOT NULL,
		    `import_key` varchar(14) DEFAULT NULL
		);";
		$resql = $this->db->query($sql);


		return 1;
	}
	
} 


?>