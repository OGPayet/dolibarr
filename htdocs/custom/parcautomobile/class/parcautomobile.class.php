<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/parcautomobile/class/marques.class.php');


class parcautomobile extends Commonobject{

	public $errors = array();
	public $rowid;
	public $nom;
	public $type;
	public $fichier;
	public $date;
	public $poste;
	public $candidature;


	public $element='parcautomobile';
	public $table_element='parcautomobile';

	public function __construct($db){
		$this->db = $db;
		return 1;
    }

	public function showNavigations($object, $linkback, $paramid = 'id', $fieldid = 'rowid', $moreparam = '')
	{

		global $langs, $conf;

		$ret = $result = '';
		$previous_ref = $next_ref = '';

		$fieldref = $fieldid;

		$object->ref = $object->rowid;

		$object->load_previous_next_ref('', $fieldid, 0);

		$navurl = $_SERVER["PHP_SELF"];

		$page = GETPOST('page');

		// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
		// accesskey is for Mac:               CTRL + key for all browsers
		$stringforfirstkey = $langs->trans("KeyboardShortcut");
		if ($conf->browser->name == 'chrome')
		{
		    $stringforfirstkey .= ' ALT +';
		}
		elseif ($conf->browser->name == 'firefox')
		{
		    $stringforfirstkey .= ' ALT + SHIFT +';
		}
		else
		{
		    $stringforfirstkey .= ' CTL +';
		}

		$previous_ref = $object->ref_previous ? '<a accesskey="p" title="'.$stringforfirstkey.' p" class="classfortooltip" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'"><i class="fa fa-chevron-left"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
		$next_ref     = $object->ref_next ? '<a accesskey="n" title="'.$stringforfirstkey.' n" class="classfortooltip" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'"><i class="fa fa-chevron-right"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';

		$ret = '';
		// print "xx".$previous_ref."x".$next_ref;
		$ret .= '<!-- Start banner content --><div style="vertical-align: middle">';


		if ($previous_ref || $next_ref || $linkback)
		{
		    $ret .= '<div class="pagination paginationref"><ul class="right">';
		}
		if ($linkback)
		{
		    $ret .= '<li class="noborder litext">'.$linkback.'</li>';
		}
		if (($previous_ref || $next_ref))
		{
		    $ret .= '<li class="pagination">'.$previous_ref.'</li>';
		    $ret .= '<li class="pagination">'.$next_ref.'</li>';
		}
		if ($previous_ref || $next_ref || $linkback)
		{
		    $ret .= '</ul></div>';
		}

		$result .= '<div style="height: 41px;">';
		$result .= $ret;
		$result .= '</div>';
		$result .= '</div style="clear:both;"></div>';

		return $result;
    }

	public function create($echo_sql=0,$insert)
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
	print_r($sql);die();
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

	public function update($id, array $data,$echo_sql=0)
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
        // die($sql);

        $resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			print_r($this->errors);
			die();
			return -1;
		}
		return 1;
	}

	public function delete($echo_sql=0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql 	= 'DELETE FROM ' . MAIN_DB_PREFIX .get_class($this).' WHERE rowid = ' . $this->rowid;
		$resql 	= $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();
			return -1;
		}

		return 1;
	}


	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
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
				$line->model 		 =  $obj->model;
				$line->consucteur 		 =  $obj->consucteur;
				$line->lieu 		 =  $obj->lieu;
				$line->date_immatriculation 			 =  $obj->date_immatriculation;
				$line->num_chassi 			 =  $obj->num_chassi;
				$line->status 			 =  $obj->status;
				$line->value_catalogue 			 =  $obj->value_catalogue;
				$line->value_residuelle 			 =  $obj->value_residuelle;
				$line->nb_porte 			 =  $obj->nb_porte;
				$line->nb_place 			 =  $obj->nb_place;
				$line->color 			 =  $obj->color;
				$line->anne_model 			 =  $obj->anne_model;
				$line->transmission 			 =  $obj->transmission;
				$line->type_carburant 			 =  $obj->type_carburant;
				$line->emission_co2 			 =  $obj->emission_co2;
				$line->nb_chevaux 			 =  $obj->nb_chevaux;
				$line->tax 			 =  $obj->tax;
				$line->puisance 			 =  $obj->puisance;
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
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT * FROM ' . MAIN_DB_PREFIX .get_class($this). ' WHERE rowid = ' . $id;
		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);

			if ($numrows) {
				$obj 			  	  = $this->db->fetch_object($resql);
                $this->id         	  = $obj->rowid;
                $this->rowid      	  = $obj->rowid;
                $this->plaque 		 =  $obj->plaque;
				$this->model 		 =  $obj->model;
				$this->consucteur 		 =  $obj->consucteur;
				$this->lieu 		 =  $obj->lieu;
				$this->date_immatriculation 			 =  $obj->date_immatriculation;
				$this->num_chassi 			 =  $obj->num_chassi;
				$this->status 			 =  $obj->status;
				$this->value_catalogue 			 =  $obj->value_catalogue;
				$this->value_residuelle 			 =  $obj->value_residuelle;
				$this->nb_porte 			 =  $obj->nb_porte;
				$this->nb_place 			 =  $obj->nb_place;
				$this->color 			 =  $obj->color;
				$this->anne_model 			 =  $obj->anne_model;
				$this->transmission 			 =  $obj->transmission;
				$this->type_carburant 			 =  $obj->type_carburant;
				$this->emission_co2 			 =  $obj->emission_co2;
				$this->nb_chevaux 			 =  $obj->nb_chevaux;
				$this->tax 			 =  $obj->tax;
				$this->puisance 			 =  $obj->puisance;

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

	public function select_with_filter($selected=0,$name='select_',$showempty=1,$val="rowid",$opt="label",$id='',$attr=''){

	    global $conf;

	    $moreforfilter = '';
	    $nodatarole = '';
	    $id = (!empty($id)) ? $id : $name;

	    $moreforfilter.='<select width="100%" '.$attr.' class="flat" id="select_'.$id.'" name="'.$name.'">';
	    if ($showempty) $moreforfilter.='<option value="0">&nbsp;</option>';

	$sql = "SELECT ".$val.",".$opt." FROM ".MAIN_DB_PREFIX.get_class($this);
		//echo $sql."<br>";
	$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$moreforfilter.='<option value="'.$obj->$val.'"';
	            if ($obj->$val == $selected) $moreforfilter.=' selected';
	            $moreforfilter.='>'.$obj->$opt.'</option>';
			}
			$this->db->free($resql);
		}

	    $moreforfilter.='</select>';
	    $moreforfilter.='<style>#s2id_select_'.$name.'{ width: 100% !important;}</style>';
	    return $moreforfilter;
	}

    function getNomUrl($withpicto=0, $option='', $get_params='', $notooltip=0, $save_lastsearch_value=-1)
    {
        global $langs, $conf, $user;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result='';
        $label='';
        $url= dol_buildpath('/parcautomobile/card.php?id='.$this->id);

        // if ($user->rights->propal->lire){}

        $linkclose='';
        if (empty($notooltip))
        {
            $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';
        }
        $linkstart = "";
        $linkend = "";
        $result = "";
        if(!empty($this->ref)){
		$ref=$this->ref;
        }else
		$ref=$this->rowid;
        if ($ref) {
            $linkstart = '<a href="'.$url.'"';
            $linkstart.=$linkclose.'>';
            $linkend='</a>';

            $result .= $linkstart;
            if ($withpicto)
                $result.= '<img height="16" src="'.dol_buildpath("/parcautomobile/img/object_parcautomobile.png").'" >&nbsp;';
            if ($withpicto != 2) $result.= $ref;
        }

        $result .= $linkend;

        return $result;
    }

    public function getcountrows(){
        $tot = 0;
        $sql = "SELECT COUNT(rowid) as tot FROM ".MAIN_DB_PREFIX.get_class($this);
        $resql = $this->db->query($sql);

        if($resql){
            while ($obj = $this->db->fetch_object($resql))
            {
                $tot = $obj->tot;
            }
        }
        return $tot;
    }

    public function getdateformat($date,$time=true){

        $d = explode(' ', $date);
        $date = explode('-', $d[0]);
        $d2 = explode(':', $d[1]);
        $result = $date[2]."/".$date[1]."/".$date[0];
        if ($time) {
            $result .= " ".$d2[0].":".$d2[1];
        }
        return $result;
    }

    public function getYears($debut="debut")
    {
        $sql = 'SELECT YEAR('.$debut.') as years FROM ' . MAIN_DB_PREFIX.get_class($this);
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

    public function getmonth($year)
    {
        $sql = 'SELECT MONTH(debut) as years FROM ' . MAIN_DB_PREFIX.get_class($this).' WHERE YEAR(debut) = '.$year;
        $resql  = $this->db->query($sql);
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


	public function select_conducteur($selected=0,$name='conducteur',$showempty=1,$val="rowid",$opt="label",$id=''){
	    global $conf;
	    $moreforfilter = '';
	    $nodatarole = '';
	    $id = (!empty($id)) ? $id : $name;

	    $objet = "label";
	    $moreforfilter.='<select class="flat" id="'.$id.'" name="'.$name.'" '.$nodatarole.'>';
	    if ($showempty) $moreforfilter.='<option value="0">&nbsp;</option>';

	$sql= "SELECT * FROM ".MAIN_DB_PREFIX."user";
	$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$moreforfilter.='<option value="'.$obj->$val.'" data-ref="'.$obj->$opt.'"';
	            if ($obj->$val == $selected) $moreforfilter.=' selected';
	            $moreforfilter.='>'.$obj->lastname.' '.$obj->firstname.'</option>';
			}
			$this->db->free($resql);
		}

	    $moreforfilter.='</select>';
	    $moreforfilter.='<style>#s2id_select_'.$name.'{ width: 100% !important;}</style>';
	    return $moreforfilter;
	}


	public function select_fournisseur($selected=0,$name='fournisseur')
	{
	    $id = (!empty($id)) ? $id : $name;

	    $select = '';
		// $select.='<select class="flat" id="'.$id.'" name="'.$name.'" >';
	    $select.='<option value="0">&nbsp;</option>';
		global $conf;
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur = 1";
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

		// $select.='</select>';
		// $select.='<script>$(function(){$("#'.$id.'").select2()})</script>';
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
		$select = '<select name="'.$name.'" id="'.$name.'" >';
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

	public function select_model($value=0,$name='model')
	{
		$modeles = new modeles($this->db);
		$marque = new marques($this->db);
		$modeles->fetchAll();
		$select = '<select name="'.$name.'" id="select_'.$name.'" >';
		for ($i=0; $i < count($modeles->rows); $i++) {
			$item=$modeles->rows[$i];
			$marque->fetch($item->marque);
			$select.='<option value="'.$item->rowid.'">'.$marque->label.'/'.$item->label.'</option>';
		}
		$select.='</select>';
		$select= str_replace('value="'.$item->rowid.'"', 'value="'.$item->rowid.'" selected', $select);
		return $select;
	}

}


?>