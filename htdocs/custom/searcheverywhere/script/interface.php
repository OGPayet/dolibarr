<?php

	require('../config.php');

	dol_include_once('/product/class/product.class.php');
	dol_include_once('/societe/class/societe.class.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/comm/action/class/actioncomm.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	dol_include_once('/commande/class/commande.class.php');
	dol_include_once('/fourn/class/fournisseur.commande.class.php');
	dol_include_once('/expedition/class/expedition.class.php');
	if($conf->of->enabled) dol_include_once('/of/class/ordre_fabrication_asset.class.php');
	if($conf->nomenclature->enabled) dol_include_once('/nomenclature/class/nomenclature.class.php');
	if($conf->workstation->enabled) dol_include_once('/workstation/class/workstation.class.php');
	if($conf->configurateur->enabled) dol_include_once('/configurateur/class/configurateur.class.php');

	$langs->load('searcheverywhere@searcheverywhere');
	$langs->load('orders');

	$get = GETPOST('get');

	switch ($get) {
		case 'search':

			_search(GETPOST('type'), GETPOST('keyword'));

			break;

		case 'search-all':

		    $TObjectType=array('product','company','contact');
			if ($conf->projet->enabled)
			{
				$TObjectType[] = 'projet';
				$TObjectType[] = 'task';
			}
			if ($conf->agenda->enabled) $TObjectType[] = 'event';
			if ($conf->propal->enabled) $TObjectType[] = 'propal';
			if ($conf->commande->enabled) $TObjectType[] = 'order';
			if ($conf->facture->enabled) $TObjectType[] = 'invoice';
			if ($conf->expedition->enabled) $TObjectType[] = 'expedition';
			if ($conf->fournisseur->enabled) $TObjectType[] = 'supplier_order';
			if ($conf->of->enabled) $TObjectType[] = 'of';
			if ($conf->nomenclature->enabled) $TObjectType[] = 'nomenclature';
			if ($conf->workstation->enabled) $TObjectType[] = 'workstation';
			if ($conf->configurateur->enabled) $TObjectType[] = 'configurateur';

		    $conf->global->SEARCHEVERYWHERE_NB_ROWS = 5;
		    $TResult=array();
		    foreach($TObjectType as $type) {

		        $TResult[$langs->transnoentities(ucfirst($type))] = _search($type, GETPOST('keyword'), true);

		    }

		    echo json_encode($TResult);

		default:

			break;
	}

function _search($type, $keyword, $asArray=false) {
	global $db, $conf, $langs;

	$table = MAIN_DB_PREFIX.$type;
	$objname = ucfirst($type);
	$id_field = 'rowid';
	$complete_label = true;
	$show_find_field = false;
	$sql_join = '';
    if($conf->of->enabled || $conf->nomenclature->enabled ||$conf->workstation->enabled) {
        $PDOdb = new TPDOdb;
    }

	$TResult=array();

	if($type == 'company') {
		$table = MAIN_DB_PREFIX.'societe';
		$objname = 'Societe';
		$complete_label = false;
	}
	elseif($type == 'projet') {
		$id_field = MAIN_DB_PREFIX.'projet.rowid';
		$table = array(MAIN_DB_PREFIX.'projet', MAIN_DB_PREFIX.'societe');
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe ON ('.MAIN_DB_PREFIX.'societe.rowid = '.MAIN_DB_PREFIX.'projet.fk_soc)';
		$objname = 'Project';
	}
	elseif($type == 'task') {
		$table = MAIN_DB_PREFIX.'projet_task';

	}
	elseif($type == 'event') {
		$table = MAIN_DB_PREFIX.'actioncomm';
		$objname = 'ActionComm';
		$id_field = 'id';
		$complete_label = false;
	}
	elseif($type == 'order') {
		$table = array(MAIN_DB_PREFIX.'commande',MAIN_DB_PREFIX.'commande_extrafields',MAIN_DB_PREFIX.'commandedet',MAIN_DB_PREFIX.'commandedet_extrafields', MAIN_DB_PREFIX.'societe');
		$objname = 'Commande';
		$complete_label = false;
		$sql_join = 'LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields ON ('.MAIN_DB_PREFIX.'commande.rowid = '.MAIN_DB_PREFIX.'commande_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet ON ('.MAIN_DB_PREFIX.'commande.rowid = '.MAIN_DB_PREFIX.'commandedet.fk_commande)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet_extrafields ON ('.MAIN_DB_PREFIX.'commandedet.rowid = '.MAIN_DB_PREFIX.'commandedet_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe ON ('.MAIN_DB_PREFIX.'societe.rowid = '.MAIN_DB_PREFIX.'commande.fk_soc)';
		$id_field = MAIN_DB_PREFIX.'commande.rowid';
	}
	elseif($type == 'propal') {
		$table = array(MAIN_DB_PREFIX.'propal',MAIN_DB_PREFIX.'propal_extrafields',MAIN_DB_PREFIX.'propaldet',MAIN_DB_PREFIX.'propaldet_extrafields', MAIN_DB_PREFIX.'societe');
		$objname = 'Propal';
		$complete_label = false;
		$sql_join = 'LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields ON ('.MAIN_DB_PREFIX.'propal.rowid = '.MAIN_DB_PREFIX.'propal_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet ON ('.MAIN_DB_PREFIX.'propal.rowid = '.MAIN_DB_PREFIX.'propaldet.fk_propal)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet_extrafields ON ('.MAIN_DB_PREFIX.'propaldet.rowid = '.MAIN_DB_PREFIX.'propaldet_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe ON ('.MAIN_DB_PREFIX.'societe.rowid = '.MAIN_DB_PREFIX.'propal.fk_soc)';
		$id_field = MAIN_DB_PREFIX.'propal.rowid';
	}
	elseif($type == 'supplier_order') {
		$table = array(MAIN_DB_PREFIX.'commande_fournisseur',MAIN_DB_PREFIX.'commande_fournisseur_extrafields',MAIN_DB_PREFIX.'commande_fournisseurdet',MAIN_DB_PREFIX.'commande_fournisseurdet_extrafields', MAIN_DB_PREFIX.'societe');
		$objname = 'CommandeFournisseur';
		$complete_label = false;
		$sql_join = 'LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseur_extrafields ON ('.MAIN_DB_PREFIX.'commande_fournisseur.rowid = '.MAIN_DB_PREFIX.'commande_fournisseur_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet ON ('.MAIN_DB_PREFIX.'commande_fournisseur.rowid = '.MAIN_DB_PREFIX.'commande_fournisseurdet.fk_commande)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet_extrafields ON ('.MAIN_DB_PREFIX.'commande_fournisseurdet.rowid = '.MAIN_DB_PREFIX.'commande_fournisseurdet_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe ON ('.MAIN_DB_PREFIX.'societe.rowid = '.MAIN_DB_PREFIX.'commande_fournisseur.fk_soc)';
		$id_field = MAIN_DB_PREFIX.'commande_fournisseur.rowid';
	}
	elseif($type == 'invoice') {
		$table = array(MAIN_DB_PREFIX.'facture',MAIN_DB_PREFIX.'facture_extrafields',MAIN_DB_PREFIX.'facturedet',MAIN_DB_PREFIX.'facturedet_extrafields', MAIN_DB_PREFIX.'societe');
		$objname = 'Facture';
		$complete_label = false;
		$sql_join = 'LEFT JOIN '.MAIN_DB_PREFIX.'facture_extrafields ON ('.MAIN_DB_PREFIX.'facture.rowid = '.MAIN_DB_PREFIX.'facture_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet ON ('.MAIN_DB_PREFIX.'facture.rowid = '.MAIN_DB_PREFIX.'facturedet.fk_facture)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet_extrafields ON ('.MAIN_DB_PREFIX.'facturedet.rowid = '.MAIN_DB_PREFIX.'facturedet_extrafields.fk_object)';
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe ON ('.MAIN_DB_PREFIX.'societe.rowid = '.MAIN_DB_PREFIX.'facture.fk_soc)';
		$id_field = MAIN_DB_PREFIX.'facture.rowid';
	}
	elseif($type == 'contact') {
		$id_field = MAIN_DB_PREFIX.'socpeople.rowid';
		$table = array(MAIN_DB_PREFIX.'socpeople', MAIN_DB_PREFIX.'societe');
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe ON ('.MAIN_DB_PREFIX.'societe.rowid = '.MAIN_DB_PREFIX.'socpeople.fk_soc)';

		$complete_label = false;
	}
	elseif($type == 'expedition')
	{
		$id_field = MAIN_DB_PREFIX.'expedition.rowid';
		$table = array(MAIN_DB_PREFIX.'expedition', MAIN_DB_PREFIX.'societe');
		$sql_join.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe ON ('.MAIN_DB_PREFIX.'societe.rowid = '.MAIN_DB_PREFIX.'expedition.fk_soc)';

	}
    elseif($type == 'of') {
        $table = array(MAIN_DB_PREFIX.'assetOf',MAIN_DB_PREFIX.'assetOf_line');
        $objname = 'TAssetOF';
        $complete_label = false;
        $sql_join = 'LEFT JOIN '.MAIN_DB_PREFIX.'assetOf_line ON ('.MAIN_DB_PREFIX.'assetOf.rowid = '.MAIN_DB_PREFIX.'assetOf_line.fk_assetOf)';

        $id_field = MAIN_DB_PREFIX.'assetOf.rowid';
    }
    elseif($type == 'nomenclature') {
        $table = array(MAIN_DB_PREFIX.'nomenclature',MAIN_DB_PREFIX.'nomenclaturedet');
        $objname = 'TNomenclature';
        $complete_label = true;
        $sql_join = 'LEFT JOIN '.MAIN_DB_PREFIX.'nomenclaturedet ON ('.MAIN_DB_PREFIX.'nomenclature.rowid = '.MAIN_DB_PREFIX.'nomenclaturedet.fk_nomenclature)';

        $id_field = MAIN_DB_PREFIX.'nomenclature.rowid';
    }
    elseif($type == 'workstation') {
        $table = array(MAIN_DB_PREFIX.'workstation');
        $objname = 'TWorkstation';
        $complete_label = false;

        $id_field = MAIN_DB_PREFIX.'workstation.rowid';
    }
    elseif($type == 'configurateur') {
        $table = array(MAIN_DB_PREFIX.'configurateur',MAIN_DB_PREFIX.'configurateur_element');
        $objname = 'Configurateur';
        $complete_label = false;
        $sql_join = 'LEFT JOIN '.MAIN_DB_PREFIX.'configurateur_element ON ('.MAIN_DB_PREFIX.'configurateur.rowid = '.MAIN_DB_PREFIX.'configurateur_element.fk_config)';

        $id_field = MAIN_DB_PREFIX.'configurateur.rowid';
    }
	$table=(Array)$table;

	$sql_where = ' 0=1 ';
	$sql_fields = '';

	foreach($table as $table1) {
		if($db->type == "pgsql"){

			$sqlpg = "SELECT column_name AS field, data_type AS Type  FROM information_schema.columns WHERE table_schema = 'public' AND table_name   = '".$table1."'";
			$res = $db->query($sqlpg);
			//$res = $db->query('DESCRIBE '.$table1);

			while($tbl = $db->fetch_object($res)) {
				$fieldname = $tbl->field;

				//var_dump($tbl);
				$sql_fields .=','. $table1.'.'.$fieldname.' as '.$table1.'_'.$fieldname;

				if( strpos($tbl->Type,'varchar') !== false || strpos($tbl->Type,'text') !== false ) {
					$sql_where.=' OR '.$table1.'.'.$fieldname." LIKE '%".$db->escape($keyword)."%'";
				}
				else if( strpos($tbl->Type,'smallint') !== false || strpos($tbl->Type,'double')!== false || strpos($tbl->Type,'float') !== false ) {
					$i_keyword = (double)$keyword;
					if(!empty($i_keyword))$sql_where.=' OR '.$table1.'.'.$fieldname." = ".$i_keyword;

				}
				else if( strpos($tbl->Type,'date') !== false ) {
					$sql_where.=' OR '.$table1.'.'.$fieldname." LIKE '".$db->escape($keyword)."%'";
				}

				else{
					$sql_where.=' OR '.$table1.'.'.$fieldname."::text LIKE '%".$db->escape($keyword)."%'";
				}


			}
		}else{
			$res = $db->query('DESCRIBE '.$table1);

			while($tbl = $db->fetch_object($res)) {
				$fieldname = $tbl->Field;
				//var_dump($tbl);
				$sql_fields .=','. $table1.'.'.$fieldname.' as '.$table1.'_'.$fieldname;

				if( strpos($tbl->Type,'varchar') !== false || strpos($tbl->Type,'text') !== false ) {
					$sql_where.=' OR '.$table1.'.'.$fieldname." LIKE '%".$db->escape($keyword)."%'";
				}
				else if( strpos($tbl->Type,'int') !== false || strpos($tbl->Type,'double')!== false || strpos($tbl->Type,'float') !== false ) {
					$i_keyword = (double)$keyword;
					if(!empty($i_keyword))$sql_where.=' OR '.$table1.'.'.$fieldname." = ".$i_keyword;

				}
				else if( strpos($tbl->Type,'date') !== false ) {
					$sql_where.=' OR '.$table1.'.'.$fieldname." LIKE '".$db->escape($keyword)."%'";
				}

				else{
					$sql_where.=' OR '.$table1.'.'.$fieldname." = '".$db->escape($keyword)."'";
				}


			}
		}
	}

    $sql = 'SELECT DISTINCT '.$id_field.' as rowid FROM '.$table[0].' '.$sql_join.' WHERE ('.$sql_where.') ';
    if(!empty($conf->global->SEARCHEVERYWHERE_SEARCH_ONLY_IN_ENTITY)) $sql.= 'AND '.$table[0].'.entity = '.$conf->entity.' ';
	if(!empty($conf->global->SEARCHEVERYWHERE_NB_ROWS)) $sql.= 'LIMIT '.$conf->global->SEARCHEVERYWHERE_NB_ROWS;
	else $sql.= 'LIMIT 20 ';

	//print $sql;
	$res = $db->query($sql);

	$nb_results = $db->num_rows($res);

	$libelle = ucfirst($objname);
	if($objname == 'CommandeFournisseur') $libelle = 'SupplierOrder';

	if(!$asArray) print '<table class="border" width="100%"><tr class="liste_titre"><td colspan="2">'.$langs->trans( $libelle ).' <span class="badge">'.$nb_results.'</span></td></tr>';

	if($nb_results == 0) {
	    if(!$asArray) 	print '<td colspan="2" class="center">'.$langs->trans("NoResults").'</td>'; // Pas de résultat
	}
	else{
		while($obj = $db->fetch_object($res)) {


			$o=new $objname($db);
			if($objname == 'TAssetOF'|| $objname == 'TNomenclature' || $objname == 'TWorkstation') $o->load($PDOdb, $obj->rowid);
			else $o->fetch($obj->rowid);

			if($o->id<=0) continue;

			$label = '';
			if($complete_label) {
				if(empty($label) && !empty( $o->label )) $label = $o->label;
				else if(empty($label) && !empty( $o->name )) $label = $o->name;
				else if(empty($label) && !empty( $o->nom )) $label = $o->nom;
				else if(empty($label) && !empty( $o->title )) $label = $o->title;
				else if(empty($label) && !empty( $o->ref )) $label = $o->ref;
				else if(empty($label) && !empty( $o->id)) $label = $o->id;
			}

			if(method_exists($o, 'getNomUrl')) {
				$label = trim($o->getNomUrl(1).' '.$label);
			}

			if(method_exists($o, 'getLibStatut')) {
				$statut = $o->getLibStatut(3);

			}

			$desc = '';

			if($show_find_field) {
				foreach($o as $k=>$v) {
					if(is_string($v) && preg_match("/" . $keyword . "/", $v)) {
						$desc .= '<br />'.$k.' : '.preg_replace("/" . $keyword . "/", "<span class='highlight'>" . $keyword . "</span>", $v);
					}


				}

			}

			preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $label, $match);

			$url = is_array($match['href']) ? $match['href'][0] : $match['href'];

			if($asArray) {
			    $TResult[] = array(
			        'label'=>$label
			        ,'label_clean'=>strip_tags($label)
			        ,'url'=>$url
			        ,'desc'=>$desc
			        ,'statut'=>$statut
			    );
			}
			else {

    			print '<tr>
    				<td>'.$label.$desc.'</td>
    				<td align="right">'.$statut.'</td>
    			</tr>';
			}

		}

	}

	if(!$asArray) print '</table>';
    else return $TResult;
}
