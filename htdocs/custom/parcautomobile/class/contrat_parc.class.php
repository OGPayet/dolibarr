<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

class contrat_parc extends Commonobject{

	public $errors = array();
	public $rowid;
	public $vehicule;
	public $litre;
	public $prix;
	public $date;
	public $fournisseur;
	public $ref_facture;
	public $kilometrage;
	public $unite;
	public $conditions;
	public $services_inclus;
	public $couts_recurrent;

	public $element='contrat_parc';
	public $table_element='contrat_parc';

	public function __construct($db){
		$this->db = $db;
		return 1;
    }

	public function create($echo_sql=0)
	{

		$sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

		$sql .= "vehicule, kilometrage, typecontrat, activation_couts, type_montant, montant_recurrent, date_facture, date_debut, date_fin, responsable, fournisseur, conducteur, ref_contrat, etat, `condition`, services_inclus, couts_recurrent)";

		$sql.= " VALUES (";
		$sql.= ($this->vehicule>0?$this->vehicule:"null");
		$sql.= ", ".($this->kilometrage>0?$this->kilometrage:"null");
		$sql.= ", ".($this->typecontrat>0?$this->typecontrat:"null");
		$sql.= ", ".($this->activation_couts>0?$this->activation_couts:"null");
		$sql.= ", ".($this->type_montant?"'".$this->type_montant."'":"null");
		$sql.= ", ".($this->montant_recurrent>0?$this->montant_recurrent:"null");
        $sql.= ", ".($this->date_facture != '' ? "'".$this->db->idate($this->date_facture)."'" : 'null');
        $sql.= ", ".($this->date_debut != '' ? "'".$this->db->idate($this->date_debut)."'" : 'null');
        $sql.= ", ".($this->date_fin != '' ? "'".$this->db->idate($this->date_fin)."'" : 'null');
		$sql.= ", ".($this->responsable>0?$this->responsable:"null");
		$sql.= ", ".($this->fournisseur>0?$this->fournisseur:"null");
		$sql.= ", ".($this->conducteur>0?$this->conducteur:"null");
		$sql.= ", ".($this->ref_contrat?"'".$this->db->escape($this->ref_contrat)."'":"null");
		$sql.= ", ".($this->etat?"'".$this->db->escape($this->etat)."'":"null");
		$sql.= ", ".($this->condition?"'".$this->db->escape($this->condition)."'":"null");
		$sql.= ", ".($this->services_inclus	?"'".$this->db->escape($this->services_inclus	)."'":"null");
		$sql.= ", ".($this->couts_recurrent?"'".$this->db->escape($this->couts_recurrent)."'":"null ");

		$sql.= ")";
		// die($sql);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$result = $this->insertExtraFields();
			return  $this->id;
		}
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			print_r($this->errors);
			die();
			return 0;
		}
	}

	public function update($id)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (!$id || $id <= 0)
			return false;

        $sql = "UPDATE " . MAIN_DB_PREFIX .get_class($this). " SET ";

        $sql.= " vehicule = ".($this->vehicule>0?$this->vehicule:"null");
		$sql.= ", kilometrage = ".($this->kilometrage>0?$this->kilometrage:"null");
		$sql.= ", typecontrat = ".($this->typecontrat>0?$this->typecontrat:"null");
		$sql.= ", activation_couts = ".($this->activation_couts>0?$this->activation_couts:"null");
		$sql.= ", type_montant = ".($this->type_montant? "'".$this->type_montant."' ":"null");
		$sql.= ", montant_recurrent = ".($this->montant_recurrent>0?$this->montant_recurrent:"null");
        $sql.= ", `date_facture` = ".($this->date_facture != '' ? "'".$this->db->idate($this->date_facture)."'" : 'null');
        $sql.= ", `date_debut` = ".($this->date_debut != '' ? "'".$this->db->idate($this->date_debut)."'" : 'null');
        $sql.= ", `date_fin` = ".($this->date_fin != '' ? "'".$this->db->idate($this->date_fin)."'" : 'null');
		$sql.= ", responsable = ".($this->responsable>0?$this->responsable:"null");
		$sql.= ", fournisseur = ".($this->fournisseur>0?$this->fournisseur:"null");
		$sql.= ", conducteur = ".($this->conducteur>0?$this->conducteur:"null");
		$sql.= ", ref_contrat = ".($this->ref_contrat?"'".$this->db->escape($this->ref_contrat)."'":"null");
		$sql.= ", etat = ".($this->etat?"'".$this->db->escape($this->etat)."'":"null");
		$sql.= ", `condition` = ".($this->condition?"'".$this->db->escape($this->condition)."'":"null");
		$sql.= ", services_inclus = ".($this->services_inclus	?"'".$this->db->escape($this->services_inclus	)."'":"null");
		$sql.= ", couts_recurrent = ".($this->couts_recurrent ? "'".$this->db->escape($this->couts_recurrent)."' ":"null ");


        $sql  = substr($sql, 0, -1);
        $sql .= " WHERE rowid = " . $id;
        $resql = $this->db->query($sql);
        if ($resql) {
			$result=$this->insertExtraFields();
			return 1;
		}
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			print_r($this->errors);
			die();
			return -1;
		}
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
	// print_r($sql);die();
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

        $sql = "UPDATE " . MAIN_DB_PREFIX .get_class($this). " SET ";

        $sql.= " vehicule = ".($this->vehicule>0?$this->vehicule:"null");
        $sql.= ", kilometrage = ".($this->kilometrage>0?$this->kilometrage:"null");
        $sql.= ", typecontrat = ".($this->typecontrat>0?$this->typecontrat:"null");
        $sql.= ", activation_couts = ".($this->activation_couts>0?$this->activation_couts:"null");
        $sql.= ", type_montant = ".($this->type_montant>0?$this->type_montant:"null");
        $sql.= ", montant_recurrent = ".($this->montant_recurrent>0?$this->montant_recurrent:"null");
        $sql.= ", date_facture = ".($this->date_facture != '' ? "'".$this->db->idate($this->date_facture)."'" : 'null');
        $sql.= ", date_debut = ".($this->date_debut != '' ? "'".$this->db->idate($this->date_debut)."'" : 'null');
        $sql.= ", date_fin = ".($this->date_fin != '' ? "'".$this->db->idate($this->date_fin)."'" : 'null');
        $sql.= ", responsable = ".($this->responsable>0?$this->responsable:"null");
        $sql.= ", fournisseur = ".($this->fournisseur>0?$this->fournisseur:"null");
        $sql.= ", conducteur = ".($this->conducteur>0?$this->conducteur:"null");
        $sql.= ", ref_contrat = ".($this->ref_contrat ? "'".$this->db->escape($this->ref_contrat)."'":"null");
        $sql.= ", etat = ".($this->etat ? "'".$this->db->escape($this->etat)."'" :"null");
        $sql.= ", condition = ".($this->condition ? "'".$this->db->escape($this->condition)."'":"null");
        $sql.= ", services_inclus = ".($this->services_inclus ? "'".$this->db->escape($this->services_inclus)."'":"null");
        $sql.= ", couts_recurrent = ".($this->vehicule ? "'".$this->db->escape($this->vehicule)."'":"null");

        $sql  = substr($sql, 0, -1);
        $sql .= " WHERE rowid = " . $id;

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
                $line->rowid         =  $obj->rowid;
				$line->vehicule 		 =  $obj->vehicule;
				$line->typecontrat 		 =  $obj->typecontrat;
				$line->ref_contrat 		 =  $obj->ref_contrat;
				$line->date_facture 		 =  $obj->date_facture;
				$line->date_debut 		 =  $obj->date_debut;
				$line->date_fin 		 =  $obj->date_fin;
				$line->conducteur 		 =  $obj->conducteur;
				$line->responsable 		 =  $obj->responsable;
				$line->fournisseur 		 =  $obj->fournisseur;
				$line->activation_couts 		 =  $obj->activation_couts;
				$line->type_montant 		 =  $obj->type_montant;
				$line->montant_recurrent 		 =  $obj->montant_recurrent;
				$line->condition 		 =  $obj->condition;
				$line->kilometrage 		 =  $obj->kilometrage;
				$line->etat 		 =  $obj->etat;
				$line->services_inclus 		 =  $obj->services_inclus;
				$line->couts_recurrent 		 =  $obj->couts_recurrent;
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
		dol_syslog(__METHOD__, LOG_DEBUG);
		$sql = "SELECT ".MAIN_DB_PREFIX.$this->table_element.".* FROM ";
		$sql .= MAIN_DB_PREFIX .$this->table_element;

		if (!empty($join)) {
			$sql .= " ".$join;
		}

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

		// echo $sql;
		$this->rows = array();
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass;
                $line->id    		 =  $obj->rowid;
                $line->rowid         =  $obj->rowid;
				$line->vehicule 		 =  $obj->vehicule;
				$line->typecontrat 		 =  $obj->typecontrat;
				$line->ref_contrat 		 =  $obj->ref_contrat;
				$line->date_facture 		 =  $obj->date_facture;
				$line->date_debut 		 =  $obj->date_debut;
				$line->date_fin 		 =  $obj->date_fin;
				$line->conducteur 		 =  $obj->conducteur;
				$line->responsable 		 =  $obj->responsable;
				$line->fournisseur 		 =  $obj->fournisseur;
				$line->activation_couts 		 =  $obj->activation_couts;
				$line->type_montant 		 =  $obj->type_montant;
				$line->montant_recurrent 		 =  $obj->montant_recurrent;
				$line->condition 		 =  $obj->condition;
				$line->kilometrage 		 =  $obj->kilometrage;
				$line->etat 		 =  $obj->etat;
				$line->services_inclus 		 =  $obj->services_inclus;
				$line->couts_recurrent 		 =  $obj->couts_recurrent;
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
                $this->vehicule 		 =  $obj->vehicule;
				$this->typecontrat 		 =  $obj->typecontrat;
				$this->ref_contrat 		 =  $obj->ref_contrat;
				$this->date_facture 		 =  $obj->date_facture;
				$this->date_debut 		 =  $obj->date_debut;
				$this->date_fin 		 =  $obj->date_fin;
				$this->conducteur 		 =  $obj->conducteur;
				$this->responsable 		 =  $obj->responsable;
				$this->fournisseur 		 =  $obj->fournisseur;
				$this->activation_couts 		 =  $obj->activation_couts;
				$this->type_montant 		 =  $obj->type_montant;
				$this->montant_recurrent 		 =  $obj->montant_recurrent;
				$this->condition 		 =  $obj->condition;
				$this->kilometrage 		 =  $obj->kilometrage;
				$this->etat 		 =  $obj->etat;
				$this->services_inclus 		 =  $obj->services_inclus;
				$this->couts_recurrent 		 =  $obj->couts_recurrent;
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

	public function select_with_filter($selected=0,$name='suivi_essence',$showempty=1,$val="rowid",$opt="label",$id='',$attr=''){

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
        $url='';

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
                $result.= '<img height="16" src="'.DOL_URL_ROOT.'/postes/img/object_postes.png" >&nbsp;';
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


	public function select_user($selected=0,$name='select_',$showempty=1,$val="rowid",$opt="label",$id=''){
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


	public function select_product($selected=0,$name='product')
	{
	    $id = (!empty($id)) ? $id : $name;

	    $select = '';
		// $select.='<select class="flat" id="'.$id.'" name="'.$name.'" >';
	    $select.='<option value="0">&nbsp;</option>';
		global $conf;
	$sql = "SELECT rowid ,ref,entity,label FROM ".MAIN_DB_PREFIX."product WHERE fk_product_type = 0";
		//echo $sql."<br>";
	$resql = $this->db->query($sql);
	$select.='<option value="0"></option>';
		if ($resql) {
			$num = $this->db->num_rows($resql);
			while ($obj = $this->db->fetch_object($resql)) {
				$select.='<option value="'.$obj->rowid.'"';
	            if ($obj->rowid == $selected) $select.='selected';
	            $select.='>'.$obj->label.'</option>';
			}
			$this->db->free($resql);
		}

		// $select.='</select>';
		// $select.='<script>$(function(){$("#'.$id.'").select2()})</script>';
	    return $select;
	}


	public function modifier_stock($prod,$qte,$id_entrepot,$movement)
	{
		global $user;
		$msg='';
        $mouvementstock = new MouvementStock($this->db);
        $product = new Product($this->db);
        $product->fetch($prod);
        $q = $movement.trim($qte);
        $type=0;
        if($movement=="+"){
		$type=1;
        }

        if($id_entrepot){
            $t=$mouvementstock->_create($user,$prod,$id_entrepot,$q,$type,0,'','');
        }
        else{
            $msg.='La quantité demandée de '.$product->label.' n\'est pas disponible <br>';
        }
        return $msg;
	}

	public function select_postes($selected=0,$name='postes')
	{
		global $conf;
		$id = (!empty($id)) ? $id : $name;

		$postes = $this->fetchAll();
		$nb=count($this->rows);
		$select = '<select class="flat" id="select_'.$id.'" name="'.$name.'" >';
		$select.='<option value="0">&nbsp;</option>';
			for ($i=0; $i < $nb; $i++) {
				$item=$this->rows[$i];
				$select.='<option value="'.$item->rowid.'"';
	            if ($item->rowid == $selected) $select.='selected';
	            $select.='>'.$item->ref.'</option>';
			}

		$select.='</select>';
		$select.='<script>$(function(){$("#select_'.$id.'").select2()})</script>';
	    return $select;
	}
	public function select_disponibl($value='',$name)
	{
		$select .= '<select name="'.$name.'" id="'.$name.'" >';
			$select.='<option value=""></option>';
			$select.='<option value="En attent">En attent</option>';
			$select.='<option value="Partiellement disponible">Partiellement disponible</option>';
			$select.='<option value="Disponible">Disponible</option>';
		$select .= '</select>';

		return $select;
	}

	public function types_montant($value='',$name='type_montant')
	{
		global $langs;

		$select .= '<select name="'.$name.'"  id="'.$name.'" >';
			$select .= '<option value=""></option>';
			$select .= '<option value="non">'.$langs->trans('non').'</option>';
			$select .= '<option value="quotidien">'.$langs->trans('quotidien').'</option>';
			$select .= '<option value="hebdomadaire">'.$langs->trans('hebdomadaire').'</option>';
			$select .= '<option value="mensuel">'.$langs->trans('mensuel').'</option>';
			$select .= '<option value="annuel">'.$langs->trans('annuel').'</option>';
		$select .= '</select>';
		$select=str_replace('value="'.$value.'"', 'value="'.$value.'" selected', $select);
		return $select;
	}

	public function select_statut($value='',$name)
	{
		global $langs;
		$select .= '<select name="'.$name.'" id="'.$name.'" >';
			$select .= '<option value=""></option>';
			$select .= '<option value="reception">'.$langs->trans('reception').'</option>';
			$select .= '<option value="encours">'.$langs->trans('encours').'</option>';
			$select .= '<option value="expire_bientot">'.$langs->trans('expire_bientot').'</option>';
			$select .= '<option value="expire">'.$langs->trans('expire').'</option>';
			$select .= '<option value="ferme">'.$langs->trans('ferme').'</option>';
		$select .= '</select>';
		$select = str_replace('value="'.$value.'"', 'value="'.$value.'" selected', $select);
		return $select;
	}

}


?>