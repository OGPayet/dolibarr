<?php

require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/expedition/modules_expedition.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

/**
 * Créer une expédition à partir de la commande
 * @param unknown $orderid
 * @param string $meth_exp
 * @param number $id_entrepot
 * @return number
 */
function create_expedition4order( $orderid, $meth_exp = '', $id_entrepot = 0, $colis=array())
{
	global $langs, $db, $user, $conf;

	$commande = new commande($db);
	$commande->fetch($orderid);
	$commande->loadExpeditions(1);

	if ($commande->fetch_lines(1) < 0 )
	{
		dol_syslog(__FUNCTION__."::create_exped No shippable products ".$orderid , LOG_WARNING);
		return 0;
	}

	if ($commande->statut < 1 )
	{
		dol_syslog(__FUNCTION__."::create_exped Order not validated ".$orderid , LOG_WARNING);
		return -1;
	}

	$expedition = new Expedition($db);
	$expedition->date_expedition  = dol_now(); // voir la date agilio
	$expedition->note             = $commande->note_public;
	$expedition->origin           = 'commande';
	$expedition->origin_id        = $orderid;
	$expedition->ref_customer = $commande->ref_client;
	$expedition->weight				= "NULL";
	$expedition->sizeS				= "NULL";
	$expedition->sizeW				= "NULL";
	$expedition->sizeH				= "NULL";
	$expedition->tracking_number	='';
	$expedition->size_units 		= "NULL";
	$expedition->shipping_method_id = empty($meth_exp)?$commande->shipping_method_id:$meth_exp ;

	$expedition->socid = $commande->socid;

	// traitement de lignes
	$errors = 0;
	if (! count($commande->lines))
	{
		dol_syslog(__FUNCTION__.":: No products to send", LOG_ERR);
		return -1;
	}
	foreach($commande->lines as $line)
	{
		if (count($colis) == 0)
		{
			if ($id_entrepot < 0) $id_entrepot = find_warehouse4product($line->fk_product);
			dol_syslog(__FUNCTION__.":: entrepot ".$id_entrepot." produit ".$line->fk_product, LOG_DEBUG);
			if ($id_entrepot >= 0) $expedition->addline($id_entrepot, $line->rowid, $line->qty);  // TODO tester résultat...
			else
			{
				dol_syslog(__FUNCTION__.":: no stock for ".$line->fk_product, LOG_ERR);
				$errors ++;
			}
		}
		else
		{
			if (in_array($line->rowid, $colis))
			{
				if ($id_entrepot < 0) $id_entrepot = find_warehouse4product($line->fk_product);
				dol_syslog(__FUNCTION__.":: entrepot ".$id_entrepot." produit ".$line->fk_product, LOG_DEBUG);
				if ($id_entrepot >= 0) $expedition->addline($id_entrepot, $line->rowid, $line->qty);  // TODO tester résultat...
				else
				{
					dol_syslog(__FUNCTION__.":: no stock for ".$line->fk_product, LOG_ERR);
					$errors ++;
				}
			}
		}
	}

	dol_syslog(__FUNCTION__.":: calcul du poids", LOG_DEBUG);
	$expedition->trueWeight = calculate_weight4object($commande);
	$expedition->weight = $expedition->trueWeight ;
	$expedition->weight_units = 0; // en unité de base KG

	// création de l'expédition
	if ($errors ) return -1;

	$db->begin();
	$error = 0;

	$exp_id=$expedition->create($user);
	if ($exp_id > 0)
	{
		// création document expédition
		$expedition->fetch($exp_id);
		$outputlangs = $langs;
		//$result=expedition_pdf_create($db, $expedition, $expedition->modelpdf, $outputlangs);
		$result = $expedition->generateDocument($expedition->modelpdf, $outputlangs);
		if ($result <= 0)
		{
			//dol_print_error($db,$result);
			dol_syslog(__FUNCTION__."::erreur création fichier pdf", LOG_ERR);
		}
	}
	else
	{
		dol_syslog(__FUNCTION__."::erreur création expédition", LOG_ERR);
		$error ++;
	}

	// commit rollback
	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
		return -1;
	}

	// validation + étiquette
	if (!empty($conf->global->LOGISTIK_SHIPPINGVALIDATE)) $res = valid_expedition($exp_id,true);

	return $exp_id ;
}

/**
 * DEPREC ??
 * renvoie le poids des produits du paquet
 * @param Expedition $exped
 * @return number
 */
function calculate_weight(Expedition $exped)
{
	global $db, $langs, $conf;
	// NB l'expédition n'est pas créée
	dol_syslog(__FUNCTION__.":: deprec ", LOG_WARNING);
	dol_syslog(basename(__FILE__)."::".__FUNCTION__.":: lignes ".print_r($exped->lines, true), LOG_DEBUG);
	if (! count($exped->lines)) return 0.0;

	if (version_compare(DOL_VERSION, "4.0", ">=" ))
	{
		$tmparray=$exped->getTotalWeightVolume();
		$totalWeight = $tmparray['weight']; // poids en kg
		dol_syslog(__FUNCTION__.":: poids ".print_r($totalWeight,true), LOG_DEBUG);
	}
	else
	{
		// Deprec
		$totalWeight = 0;
		$weightUnit=0;
		foreach ($exped->lines as $line)
		{
			$weightUnit=0;
			if (! empty($line->weight_units)) $weightUnit = $line->weight_units;
			if ($line->weight_units < 50)
			{
				$trueWeightUnit=pow(10,$weightUnit);
				$totalWeight += $line->weight*$line->qty_shipped*$trueWeightUnit;
			}
			else
			{
				$trueWeightUnit=$weightUnit;
				$totalWeight += $line->weight*$line->qty_shipped;
			}
		}
	}
	dol_syslog(__FUNCTION__.":: poids calculé pour ".$exped->ref ." : ".$totalWeight, LOG_DEBUG);
	return $totalWeight;

		// calculer le poids à partir de la commande  car fk_product n'est pas renseigné.
/*		$sql = "SELECT p.ref, p.weight, p.weight_units FROM ".MAIN_DB_PREFIX."product p ";
		$sql .= " JOIN ".MAIN_DB_PREFIX."commandedet cd ON p.rowid = cd.fk_product ";
		$sql .= " WHERE  cd.rowid = ".$line->origin_line_id;
		dol_syslog(__FUNCTION__."::sql = ".$sql, LOG_DEBUG);

		$resql = $db->query($sql);

		if ($resql) {
			$obj =  $db->fetch_object($resql);
			$weight = $obj->weight;
			$units = $obj->weight_units;
			$ref = $obj->ref;

			dol_syslog(__FUNCTION__.":: produit ".$obj->ref." poids ".$weight." unité ".$units, LOG_DEBUG);
			// conversion en grammes TODO paramétrer l'unité de destination
			$weight=weight_convert($weight, $units, -3);
			$poids += $line->qty * floatval($weight);
		}
		else dol_syslog(__FUNCTION__.":: produit for order line ".$line->origin_line_id." not found", LOG_DEBUG);

	}
	dol_syslog(__FUNCTION__.":: poids calculé pour ".$exped->ref ." : ".$poids, LOG_DEBUG);
	return $poids;
	*/
}

/**
 * valide l'expédition et recherche une éventuelle étiquette
 * @param unknown $expedid
 * @param string $label
 * @param string $numenvoi
 * @return number
 */
function valid_expedition( $expedid, $label = false, $numenvoi='')
{
	global $conf, $db, $user, $langs;

	$expedition = new Expedition($db);
	$expedition->fetch($expedid);

	//validation
	if ($expedition->valid($user) < 0 )
	{
		dol_syslog(__FUNCTION__.":: erreur valid expedition ".$expedition->error, LOG_ERR);
		$error ++;
	}

	if ($conf->shippinglabels->enabled && $label )
	{
		dol_syslog(__FUNCTION__.":: creation de l'étiquette", LOG_DEBUG);
		dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
		$service = getShippingService($expedition->shipping_method_id);
		if (is_object($service))
		{
			dol_syslog(__FUNCTION__."classe ".get_class($service), LOG_DEBUG);
			if($service->ws_call($expedition)<0)
			{
				dol_syslog(__FUNCTION__." erreur ".$service->error, LOG_ERR);
				// TODO setEventMessage($service->errors,'errors');
			}
			else
			{
				$expedition->tracking_number = $service->trackingnumber;
				if ($expedition->update($user) < 0)
				{
					dol_syslog(__FUNCTION__.":: erreur maj tracking ".$expedition->error, LOG_ERR);
					// TODO logging
				}
			}
		}
	}

	// création nouveau fichier
	$expedition->fetch($expedid);
	$outputlangs = $langs;

	//$result=expedition_pdf_create($db,$expedition,$expedition->modelpdf,$outputlangs);
	$result = $expedition->generateDocument($expedition->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_syslog(__FUNCTION__."::erreur création fichier pdf", LOG_ERR);
	}

	return 1;
}

/**
 * renvoie un entrepot où l'objet existe
 * @param unknown $idproduct
 * @return Ambigous <number, multitype:unknown NULL object >
 */
function find_warehouse4product($idproduct)
{
	$sql = "SELECT fk_entrepot FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product = ".$idproduct;
	$sql .= " ORDER BY reel DESC ";
	dol_syslog(__FUNCTION__.":: sql $sql", LOG_DEBUG);
	$liste = tia_sqlarray($sql);
	$entid = 0;
	if (count($liste)) $entid = $liste[0]->fk_entrepot;

	return $entid;
}

/**
 * renvoie une chaîne de poids avec unité
 * s'assurer que $object->id est renseigné
 * @param unknown $object
 */
function tia_showtotalweight($object)
{
	global $conf;

	if (version_compare(DOL_VERSION, '4.0', ">="))
	{
		$object->fetch($object->id);
		$tmparray=$object->getTotalWeightVolume();
		$totalWeight = $tmparray['weight'];
	}
	else
	{
		$object->fetch($object->id);
		$totalWeight = calculate_weight4object($object);
	}
	$ret = showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND)?$conf->global->MAIN_WEIGHT_DEFAULT_ROUND:-1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT)?$conf->global->MAIN_WEIGHT_DEFAULT_UNIT:'yes');
	return $ret;
}

/**
 * Calcule le poids pour un objet (DEPREC en 4.0 et + et ne fonctionne pas avant sauf pour expéditions
 * Objet doitêtre renseigné
 * @param unknown $object
 * @return number
 */
function calculate_weight4object($object)
{
	global $db, $langs, $conf;

	dol_syslog(basename(__FILE__)."::".__FUNCTION__.":: lignes ".print_r($object->lines, true), LOG_DEBUG);

	if (! count($object->lines)) return 0.0;

	if (version_compare(DOL_VERSION, '4.0', ">="))
	{
		$object->fetch($object->id);
		$tmparray=$object->getTotalWeightVolume();
		$totalWeight = $tmparray['weight'];
	}
	else
	{
		//DEPREC autres versions
		$totalWeight = 0;
		$weightUnit=0;
		foreach ($object->lines as $line)
		{
			$weightUnit=0;
			if (! empty($line->weight_units)) $weightUnit = $line->weight_units;
			if ($line->weight_units < 50)
			{
				$trueWeightUnit=pow(10,$weightUnit);
				$totalWeight += $line->weight*$line->qty_shipped*$trueWeightUnit;
			}
			else
			{
				$trueWeightUnit=$weightUnit;
				$totalWeight += $line->weight*$line->qty_shipped;
			}
		}
		dol_syslog(__FUNCTION__.":: poids calculé pour ".$object->ref ." : ".$totalWeight, LOG_DEBUG);
	}
	return $totalWeight;
}


/**
 * Forge an set tracking url
 * replace expedition method to handle other Fields
 *
 * @param	string	$value		Value
 * @return	void
 */
function tia_GetUrlTrackingStatus(Expedition $exped)
{
	global $db, $conf;

	if (! empty($exped->shipping_method_id))
	{
		$sql = "SELECT em.code, em.tracking";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		$sql.= " WHERE em.rowid = ".$exped->shipping_method_id;
dol_syslog(__FUNCTION__.':: sql '.$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			if ($obj = $db->fetch_object($resql))
			{
				$tracking = $obj->tracking;
				$method = $obj->code;
			}
		}
	}
	if (!empty($tracking) && !empty($exped->tracking_number))
	{
		$url = str_replace('{TRACKID}', $exped->tracking_number, $tracking);

		// mondialrelay
		dol_syslog("methode ".$method." tracking ".$tracking, LOG_DEBUG);
		if ($method == "MR")
		{
			$tabcont = $exped->getIdContact("external", 'shipping');
			if (is_array($tabcont) && count($tabcont) > 0)
			{
				$idcont = $tabcont[0];
				$contact = new Contact($db);
				$contact->fetch($idcont);
				$cp = trim($contact->zip);
			}
			else
			{
				$soc = new Societe($db);
				$soc->fetch($exped->socid);
				$cp = $soc->zip;
			}
			$url = str_replace('{ZIPCODE}', $cp, $url)  ;
		}
		$tracking_url = sprintf('<a target="_blank" href="%s">'.($exped->tracking_number?$exped->tracking_number:'url').'</a>',$url,$url);
	}
	else
	{
		$tracking_url = $exped->tracking_number;
	}
dol_syslog(__FUNCTION__.":: method *$method* cp $cp tracking ".$exped->tracking_number." url ".$tracking_url, LOG_DEBUG);
	return $tracking_url;
}