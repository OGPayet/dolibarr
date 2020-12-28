<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

class interventions_parc extends Commonobject{

	public $errors = array();
	public $rowid;
	public $typeintervention;
	public $vehicule;
	public $acheteur;
	public $kilometrage;
	public $fournisseur;
	public $prix;
	public $notes;
	public $date;
	public $datevalidate;
	public $checkmail;
	public $service_inclus;

	public $element='interventions_parc';
	public $table_element='interventions_parc';

	public function __construct($db){
		$this->db = $db;
		return 1;
    }

    public function checkInterventionsMails()
	{
		global $conf, $langs;

		$langs->load('parcautomobile@parcautomobile');

		$nbrtotal = $this->fetchAll();

		$datenow = date('Y-m-d');
		$diffday = 0;

		$nbd = 7;
	    if(!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_EMAIL_DAYS_BEFORE))
	        $nbd = $conf->global->PARCAUTOMOBILE_INTERVENTION_EMAIL_DAYS_BEFORE;

	    if(!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL)){

		    $societename = '';
		    if(!empty($conf->global->MAIN_INFO_SOCIETE_NOM))
			$societename = $conf->global->MAIN_INFO_SOCIETE_NOM;

		    if($conf->parcautomobile->enabled){

			$body = '';
			$frlng = 0;
			$lgs = explode("_", $conf->global->MAIN_LANG_DEFAULT);
			if (isset($lgs[0]) && $lgs[0] == 'fr'){
				$frlng = 1;
			}



				$title = html_entity_decode("Monitoring intervention on vehicle");
			if($frlng) $title = html_entity_decode("Suivi d'intervention sur véhicule");

			    if (count($this->rows) > 0) {
					for ($i=0; $i < count($this->rows) ; $i++) {
						$item = $this->rows[$i];

						if(!empty($item->datevalidate)){

							$datevalid = '';
							$date2= explode('-', $item->datevalidate);
					        $datevalid=$date2[2].'/'.$date2[1].'/'.$date2[0];
							$vehicules = new vehiculeparc($this->db);
							$vehicules->fetch($item->vehicule);

							if(!empty($vehicules->sendmail)){

								$diffday = $this->calculateDatesBetween($item->datevalidate, $datenow);
								$diffday = $diffday * 1;

								// echo "diffday : ".$diffday."<br>";
								// die;
								if(($diffday < 0 && $diffday == (-1*$nbd)) || (!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_BEFORE) && $diffday < 0 && $diffday > (-1*$nbd)) ){
									$body = "Hello,<br><br>";
									$body .= "We inform you that there are only <b>__RESTEDAYS__</b> __NBRDAYS__ left before the validity date (__VALIDATEDAY__) of intervention number <b>__REFOFINTERV__</b> of the vehicle <b>__VEHICULEINFO__</b>.<br><br>";
									$body .= "Best regards,<br>";

								if($frlng){
										$body = "Bonjour,<br><br>";
										$body .= "Nous vous informons qu'il ne reste que <b>__RESTEDAYS__</b> __NBRDAYS__ avant la date de validité (__VALIDATEDAY__) de l'intervention numéro <b>__REFOFINTERV__</b> du véhicule <b>__VEHICULEINFO__</b>.<br><br>";
										$body .= "Bien Cordialement,<br>";
								}

									// echo $body."<br>";
								if(!empty($societename)) $body .= $societename."<br>";



									$item->substitutionarray['__RESTEDAYS__'] = ($diffday*-1);

									if(($diffday*-1) > 1) $dayors = "days"; else  $dayors = "day";
									if($frlng){
										if(($diffday*-1) > 1) $dayors = "jours"; else  $dayors = "jour";
									}

									$item->substitutionarray['__NBRDAYS__'] = $dayors;
									$item->substitutionarray['__VALIDATEDAY__'] = $datevalid;
									$item->substitutionarray['__REFOFINTERV__'] = '<a href="'.dol_buildpath('/parcautomobile/interventions_parc/card.php?id='.$item->rowid,2).'" target="_blank">#'.$item->rowid.'</a> ';
									$item->substitutionarray['__VEHICULEINFO__'] = '<a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'" >'.$vehicules->get_nom($item->vehicule,1).'</a> ';


									$mail = $this->sendMailToAdmin($item,$title,$body);

								}
								elseif($diffday > 0 && !empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_AFTER)){
									$body = "Hello,<br><br>";
									$body .= "We inform you that the validity date (__VALIDATEDAY__) of intervention number <b>__REFOFINTERV__</b> of the vehicle <b>__VEHICULEINFO__</b> has expired <b>__RESTEDAYS__</b> __NBRDAYS__ ago.<br><br>";
									$body .= "Best regards,<br>";

								if ($frlng){
										$body = "Bonjour,<br><br>";
										$body .= "Nous vous informons que la date de validité (__VALIDATEDAY__) de l'intervention numéro <b>__REFOFINTERV__</b> du véhicule <b>__VEHICULEINFO__</b> est expiré depuis <b>__RESTEDAYS__</b> __NBRDAYS__.<br><br>";
										$body .= "Bien Cordialement,<br>";
								}
								// echo $body."<br>";
								if(!empty($societename)) $body .= $societename."<br>";



									$item->substitutionarray['__RESTEDAYS__'] = ($diffday*1);

									if(($diffday*1) > 1) $dayors = "days"; else  $dayors = "day";
									if($frlng){
										if(($diffday*1) > 1) $dayors = "jours"; else  $dayors = "jour";
									}

									$item->substitutionarray['__NBRDAYS__'] = $dayors;
									$item->substitutionarray['__VALIDATEDAY__'] = $datevalid;
									$item->substitutionarray['__REFOFINTERV__'] = '<a href="'.dol_buildpath('/parcautomobile/interventions_parc/card.php?id='.$item->rowid,2).'" target="_blank">#'.$item->rowid.'</a> ';
									$item->substitutionarray['__VEHICULEINFO__'] = '<a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'" >'.$vehicules->get_nom($item->vehicule,1).'</a> ';


									$mail = $this->sendMailToAdmin($item,$title,$body);


								}
								elseif($diffday == 0){
									$body = "Hello,<br><br>";
									$body .= "We inform you that the end of validity day (__VALIDATEDAY__) of intervention number <b>__REFOFINTERV__</b> of the vehicle <b>__VEHICULEINFO__</b> has been reached.<br><br>";
									$body .= "Best regards,<br>";

								if ($frlng){
										$body = "Bonjour,<br><br>";
										$body .= "Nous vous informons que le jour de fin de validité (__VALIDATEDAY__) de l'intervention numéro <b>__REFOFINTERV__</b> du véhicule <b>__VEHICULEINFO__</b> est atteint.<br><br>";
										$body .= "Bien Cordialement,<br>";
								}
								// echo $body."<br>";
								if(!empty($societename)) $body .= $societename."<br>";

								// echo $body;die;


									$item->substitutionarray['__VALIDATEDAY__'] = $datevalid;
									$item->substitutionarray['__REFOFINTERV__'] = '<a href="'.dol_buildpath('/parcautomobile/interventions_parc/card.php?id='.$item->rowid,2).'" target="_blank">#'.$item->rowid.'</a> ';
									$item->substitutionarray['__VEHICULEINFO__'] = '<a href="'.dol_buildpath('/parcautomobile/card.php?id='.$item->vehicule,2).'" >'.$vehicules->get_nom($item->vehicule,1).'</a> ';


									$mail = $this->sendMailToAdmin($item,$title,$body);


								}
							}

						}

					}
				}
		    }
	    }


		return 0;

    }

    public function sendMailToAdmin($object, $title="", $body=""){



	global $langs, $conf;

	$langs->load('parcautomobile@parcautomobile');

	$object->sujet = $title;
	$object->body = $body;

	$socemail = "";
		if($conf->global->MAIN_INFO_SOCIETE_MAIL)
			$socemail = $conf->global->MAIN_INFO_SOCIETE_MAIL;

		if(!empty($socemail)){

		$object->email_from = $socemail;
		$object->sendto = $socemail;


		// Le message est-il en html
			$msgishtml = -1; // Inconnu par defaut
			if (preg_match('/[\s\t]*<html>/i', $object->body)) $msgishtml = 1;

			// other are set at begin of page
			$object->substitutionarray['__EMAIL__'] = $object->sendto;
			$object->substitutionarray['__MAILTOEMAIL__'] = '<a href="mailto:'.$object->sendto.'">'.$object->sendto.'</a>';

			// Pratique les substitutions sur le sujet et message
	        complete_substitutions_array($object->substitutionarray, $langs);
			$tmpsujet = make_substitutions($object->sujet, $object->substitutionarray);
			$tmpbody = make_substitutions($object->body, $object->substitutionarray);

			$arr_file = array();
			$arr_mime = array();
			$arr_name = array();
			$arr_css  = array();

	        // Ajout CSS
	        if (!empty($object->bgcolor)) $arr_css['bgcolor'] = (preg_match('/^#/', $object->bgcolor) ? '' : '#').$object->bgcolor;
	        if (!empty($object->bgimage)) $arr_css['bgimage'] = $object->bgimage;

	        $trackid = 'emailingtest';
			$mailfile = new CMailFile($tmpsujet, $object->sendto, $object->email_from, $tmpbody, $arr_file, $arr_mime, $arr_name, '', '', 0, $msgishtml, $object->email_errorsto, $arr_css, $trackid, '', 'emailing');

			$result = $mailfile->sendfile();

			if ($result)
			{
				setEventMessages($langs->trans("MailSuccessfulySent", $mailfile->getValidAddress($object->email_from, 2), $mailfile->getValidAddress($object->sendto, 2)), null, 'mesgs');
				return 'MailSuccessfulySent';
			}
			else
			{
				setEventMessages($langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result, null, 'errors');
				return 'ResultKo';
			}
		}else{

			return 'AddEmailToYourSociete';

		}


    }

	public function calculateDatesBetween($start, $end)
	{
		$date1 = date_create($start);
		$date2 = date_create($end);
		$diff = date_diff($date1,$date2);

		$diffday = $diff->format("%R%a");
		return $diffday;
    }



	public function create($echo_sql=0)
	{

		$sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

		$sql.= " typeintervention, vehicule, acheteur, kilometrage, fournisseur, ref_facture, prix, date, service_inclus, notes, datevalidate, checkmail )";

		$sql.= " VALUES (";
		$sql.= ($this->typeintervention>0?$this->typeintervention:"null");
		$sql.=  ", ".($this->vehicule>0?$this->vehicule:"null");
		$sql.=  ", ".($this->acheteur>0?$this->acheteur:"null");
		$sql.= ", ".($this->kilometrage>0?$this->kilometrage:"null");
		$sql.= ", ".($this->fournisseur>0?$this->fournisseur:"null");
		$sql.= ", ".($this->ref_facture?"'".$this->db->escape($this->ref_facture)."' ":"null");
		$sql.= ", ".($this->prix>0?$this->prix:"null");
        $sql .= ", ".($this->date != '' ? "'".$this->db->idate($this->date)."' " : 'null');
		$sql.= ", ".($this->service_inclus?"'".$this->db->escape($this->service_inclus)."' ":"null");
		$sql.= ", ".($this->notes?"'".$this->db->escape($this->notes)."'":"null");
        $sql .= ", ".($this->datevalidate != '' ? "'".$this->db->idate($this->datevalidate)."' " : 'null');
        $sql .= ", ".($this->checkmail != '' ? "'".$this->db->idate($this->checkmail)."' " : 'null');

		$sql.= ")";
		// die($sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$result = $this->insertExtraFields();
			return $this->id;
		}
		else {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			// print_r($this->errors);
			// die();
			return 0;
		}
		return $this->db->db->insert_id;
	}

	public function update($id)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (!$id || $id <= 0)
			return false;

        $sql = "UPDATE " . MAIN_DB_PREFIX .get_class($this). " SET ";



		$sql .= " typeintervention = ".($this->typeintervention>0?$this->db->escape($this->typeintervention):"null");
		$sql .= ", vehicule = ".($this->vehicule>0?$this->db->escape($this->vehicule):"null");
		$sql .= ", acheteur = ".($this->acheteur>0?$this->db->escape($this->acheteur):"null");
		$sql .= ", kilometrage = ".($this->kilometrage>0?$this->db->escape($this->kilometrage):"null");
		$sql .= ", ref_facture = ".($this->ref_facture? "'".$this->db->escape($this->ref_facture)."'":"null");
		$sql .= ", fournisseur = ".($this->fournisseur>0?$this->db->escape($this->fournisseur):"null");
		$sql .= ", prix = ".($this->prix>0?$this->db->escape($this->prix):"null");
		$sql .= ", date = ".($this->date ? "'".$this->db->idate($this->date)."'" :"null");
		$sql .= ", service_inclus = ".($this->service_inclus ? "'".$this->db->escape($this->service_inclus)."'":"null");
		$sql .= ", notes = ".($this->notes  ? "'".$this->db->escape($this->notes)."' " :"null");
		$sql .= ", datevalidate = ".($this->datevalidate ? "'".$this->db->idate($this->datevalidate)."'" :"null");
		$sql .= ", checkmail = ".($this->checkmail? "'".$this->db->idate($this->checkmail)."'" :"null ");



        $sql  = substr($sql, 0, -1);
        $sql .= " WHERE rowid = " . $id;

        $resql = $this->db->query($sql);
        if ($resql) {
			$result=$this->insertExtraFields();
		}
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			// print_r($this->errors);
			// die();
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
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			// print_r($this->errors);
			// die();
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

        if (count($data) && is_array($data))
            foreach ($data as $key => $value) {
	            $val = is_numeric($value) ? $value : '"'. $value .'"';
			$val = ($value == '') ? 'NULL' : $val;
		$sql .= '`'. $key. '` = '. $val .',';
	        }

        $sql  = substr($sql, 0, -1);
        $sql .= " WHERE rowid = " . $id;
        // die($sql);
        $resql = $this->db->query($sql);

		if (!$resql) {
			$this->db->rollback();
			$this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
			// print_r($this->errors);
			// die();
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
			print_r($this->errors);die();
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
		// die($sql);
		$this->rows = array();
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new stdClass;
                $line->id    		     =  $obj->rowid;
                $line->rowid    		 =  $obj->rowid;
                $line->ref    		 =  $obj->rowid;
				$line->typeintervention  =  $obj->typeintervention;
				$line->vehicule 		 =  $obj->vehicule;
				$line->prix 		 	 =  $obj->prix;
				$line->date 		     =  $obj->date;
				$line->datevalidate 	 =  $obj->datevalidate;
				$line->checkmail 	 	 =  $obj->checkmail;
				$line->notes 		     =  $obj->notes;
				$line->ref_facture 		 =  $obj->ref_facture;
				$line->fournisseur 		 =  $obj->fournisseur;
				$line->kilometrage 		 =  $obj->kilometrage;
				$line->service_inclus    =  $obj->service_inclus;

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
				$line->ref    		 =  $obj->rowid;
				$line->typeintervention  =  $obj->typeintervention;
				$line->vehicule 		 =  $obj->vehicule;
				$line->prix 		 	 =  $obj->prix;
				$line->date 		     =  $obj->date;
				$line->datevalidate 	 =  $obj->datevalidate;
				$line->checkmail 	 	 =  $obj->checkmail;
				$line->notes 		     =  $obj->notes;
				$line->ref_facture 		 =  $obj->ref_facture;
				$line->fournisseur 		 =  $obj->fournisseur;
				$line->kilometrage 		 =  $obj->kilometrage;
				$line->service_inclus    =  $obj->service_inclus;

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
				$obj 			  	     = $this->db->fetch_object($resql);
                $this->id         	     = $obj->rowid;
                $this->rowid      	  	 = $obj->rowid;
                $this->ref      	  	 = $obj->rowid;
                $this->typeintervention  =  $obj->typeintervention;
				$this->vehicule 		 =  $obj->vehicule;
				$this->prix 		 	 =  $obj->prix;
				$this->date 		     =  $obj->date;
				$this->datevalidate 	 =  $obj->datevalidate;
				$this->checkmail 	 	 =  $obj->checkmail;
				$this->notes 		     =  $obj->notes;
				$this->ref_facture 		 =  $obj->ref_facture;
				$this->fournisseur 		 =  $obj->fournisseur;
				$this->kilometrage 		 =  $obj->kilometrage;
				$this->service_inclus   =  $obj->service_inclus;
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

}


?>