<?php
/* Copyright (C) 2015 	   Jean Heimburger      <jean@tiaris.info>
 *
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*
*/
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";

dol_include_once("/tikehau/lib/tia_orders.lib.php");

function prepare_head_admin()
{
	global $langs, $conf, $user;
	$langs->load("shippinglabels@shippinglabels");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/shippinglabels/admin/shippinglabels.php',1);
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'Admin';
	$h++;

	$exist = shipmethod_active('UPS');
	if($exist->active == 1 ){
	$head[$h][0] = dol_buildpath('/shippinglabels/admin/upsadmin.php',1);
	$head[$h][1] = $langs->trans("ConfUPS");
	$head[$h][2] = 'AdminUPS';
	$h++;
	}

	$exist = shipmethod_active('COLSUI');
	if($exist->active == 1 ){
	$head[$h][0] = dol_buildpath('/shippinglabels/admin/colissimoadmin.php',1);
	$head[$h][1] = $langs->trans("ConfColissimo");
	$head[$h][2] = 'AdminColissimo';
	$h++;
	}

	$exist = shipmethod_active('CHRONO');
	if($exist->active == 1 ){
	$head[$h][0] = dol_buildpath('/shippinglabels/admin/chronopostadmin.php',1);
	$head[$h][1] = $langs->trans("ConfChronoPost");
	$head[$h][2] = 'AdminChronoPost';
	$h++;
	}


	$exist = shipmethod_active('TNT');
	if($exist->active == 1 ){
	$head[$h][0] = dol_buildpath('/shippinglabels/admin/tntadmin.php',1);
	$head[$h][1] = $langs->trans("ConfTnt");
	$head[$h][2] = 'AdminTNT';
	$h++;
	}

	$exist = shipmethod_active('MR');
	if($exist->active == 1 ){
	$head[$h][0] = dol_buildpath('/shippinglabels/admin/mondialRelayadmin.php',1);
	$head[$h][1] = $langs->trans("ConfMondialRelay");
	$head[$h][2] = 'AdminMR';
	$h++;
	}

	$exist = shipmethod_active('DPD');
	if($exist->active == 1 ){
	$head[$h][0] = dol_buildpath('/shippinglabels/admin/dpdadmin.php',1);
	$head[$h][1] = $langs->trans("ConfDPD");
	$head[$h][2] = 'AdminDPD';
	$h++;
	}

	$exist = shipmethod_active('GLS');
	if($exist->active == 1 ){
	$head[$h][0] = dol_buildpath('/shippinglabels/admin/glsadmin.php',1);
	$head[$h][1] = $langs->trans("ConfGLS");
	$head[$h][2] = 'AdminGLS';
	$h++;
	}


	$head[$h][0] = dol_buildpath('/shippinglabels/admin/about.php',1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'About';
	$h++;

	return $head;
}

function calculate_weight2($exped)
{
	dol_syslog("DEPREC utiliser fonction de tia_order", LOG_WARNING);
	$lines = $exped->lines;
	$num_prod = count($lines);
	$totalWeight = 0;
	$weightUnit=0;
	for ($i = 0 ; $i < $num_prod ; $i++)
	{
		$weightUnit=0;
		if (! empty($lines[$i]->weight_units)) $weightUnit = $lines[$i]->weight_units;
		if ($lines[$i]->weight_units < 50)
		{
			$trueWeightUnit=pow(10,$weightUnit);
			$totalWeight += $lines[$i]->weight*$lines[$i]->qty_shipped*$trueWeightUnit;
		}
		else
		{
			$trueWeightUnit=$weightUnit;
			$totalWeight += $lines[$i]->weight*$lines[$i]->qty_shipped;
		}
	}
	return $totalWeight;
}


function get_exped_labelfile($expedid, $code='UPS', $format = 0)
{
	global $conf;
// TODO ne fonctionne pas ?

	$retstring = '';
	$dir = "labels/" .get_exdir($expedid,3);
	$file = $conf->logistique->dir_output . "/".$dir;
	if ($code == 'UPS') $file .= '/UPS_'.$expedid.'.gif';
	dol_syslog(__FUNCTION__.'::fichier '.$file, LOG_DEBUG);
	if (file_exists($file))
	{
		$retstring = $file;
		if ($format == 1)
		{
			$retstring = '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=logistique&file='.$dir.'/UPS_'.$expedid.'.gif'.'" > <img src="'.DOL_URL_ROOT.'/logistique/img/label.png" /></a>';
		}
	}

	return $retstring;
}

/**
 * TODO doublon avec même fonciton dans logistique.lib
 * @param unknown $exped
 * @return number|Ambigous <NULL>
 */
function get_shippingcontact($exped, $mode = "")
{
	global $db;

	$commande = new Commande($db);


	$origin 	= $exped->origin;
	$origin_id 	= $exped->origin_id;
	dol_syslog(__FUNCTION__.":: exped ".$exid." commande ".$origin." id ".$origin_id, LOG_DEBUG);

	if (empty($origin_id)) return -1;

	// charger contact de livraison de la commande
	$commande->fetch($origin_id);

// Si un mode est spécifié on renvoie ce type de contact sinon on continue
	if ($mode)
	{
		$contact_array = $commande->getIdContact('external', $mode);
		dol_syslog(__FUNCTION__.":: mode ".$mode ."  ".print_r($contact_array, true), LOG_DEBUG);
		if (is_array($contact_array) && sizeof($contact_array) > 0) return $contact_array[0];
	}

	$contact_array = $commande->getIdContact('external', 'shipping');

	if (is_array($contact_array) && sizeof($contact_array) > 0) return $contact_array[0];

	// sinon contact de commande
	$contact_array = $commande->getIdContact('external', 'customer');

	if (is_array($contact_array) && sizeof($contact_array) > 0) return $contact_array[0];

	// sinon prendre la société (on envoie un entier)
	return 0;
}


function stripAccents($str, $encoding='utf-8')
{
    // transformer les caractères accentués en entités HTML
    $str = htmlentities($str, ENT_NOQUOTES, $encoding);

    // remplacer les entités HTML pour avoir juste le premier caractères non accentués
    // Exemple : "&ecute;" => "e", "&Ecute;" => "E", "Ã " => "a" ...
    $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);

    // Remplacer les ligatures tel que : Œ, Æ ...
    // Exemple "Å“" => "oe"
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
    // Supprimer tout le reste
    $str = preg_replace('#&[^;]+;#', '', $str);

    return $str;
}

function UpdateUrlTrackingStatus($exped)
{
	global $langs, $db;


	$contactid = get_shippingcontact($exped);
	if ($contactid > 0)
	{
		$contact = new Contact($db);
		$contact->fetch($contactid);
	}
	else
	{
		$contact = new Contact($db);
		$contact->zip = $soc->zip;

	}
	if (!empty($exped->shipping_method_id))
	{
		$sql = "SELECT em.code, em.tracking";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
		$sql.= " WHERE em.rowid = ".$exped->shipping_method_id;

		$resql = $db->query($sql);
		if ($resql)
		{
			if ($obj = $db->fetch_object($resql))
			{
				$tracking = $obj->tracking;
			}
		}
	}
	if (!empty($tracking) && !empty($exped->tracking_number))
	{
		$url = str_replace('{TRACKID}', $exped->tracking_number, $tracking);
		$url = str_replace('{ZIPCODE}', $contact->zip, $url);

		$exped->tracking_url = sprintf('<a target="_blank" href="%s">'.($exped->tracking_number?$exped->tracking_number:'url').'</a>',$url,$url);
	}
	else
	{

		$exped->tracking_url = $exped->tracking_number;

	}
	return $url;
}


function delivery_methods($id)
{
	global $langs,$db;

	$sql = "SELECT em.code";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
	$sql.= " WHERE em.active = 1";
	$sql.= " AND em.rowid=".$id;
	$dm=false;
	$req = $db->query($sql);
	if ($req)
	{
		while ($obj= $db->fetch_object($req))
		{
			$dm=$obj;
		}
	}

	return $dm;
}

function shipmethod_active($code)
{
	global $langs,$db;

	$sql = "SELECT em.active";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
	$sql.= " WHERE em.code ='".$code."'";
	$rep;

	$req = $db->query($sql);
	if ($req)
	{
		while ($obj= $db->fetch_object($req))
		{
			$rep=$obj;
		}
	}
	return $rep;
}

/**
 * renvoie un objet shippinglabel à partir de l'id de la méthode
 * @param unknown $id
 * @return number
 */
function getShippingService($id)
{
	global $db, $user;

	$shipping_method = delivery_methods($id);
	switch($shipping_method->code)
	{
		case "GLS":
			dol_include_once("/shippinglabels/class/gls.class.php");
			$service=new L_gls($db, $user);
			break;
		case "GLSPR":
			dol_include_once("/shippinglabels/class/gls.class.php");
			$service=new L_gls($db, $user);
			$service->contrat = 'PR';
			break;
		case "DPD":
			dol_include_once("/shippinglabels/class/dpd.class.php");
			$service=new L_dpd($db, $user);
			break;
		case "UPS":
			dol_include_once("/shippinglabels/class/ups.class.php");
			$service=new L_ups($db, $user);
			break;
		case "COLSUI":
			dol_include_once("/shippinglabels/class/colissimo.class.php");
			$service=new L_soco($db, $user);
			break;
		case "COLDOM":
			dol_include_once("/shippinglabels/class/colissimo.class.php");
			$service=new L_soco($db, $user);
			$service->contrat = 'OM';
			break;
		case "COLINT":
			dol_include_once("/shippinglabels/class/colissimo.class.php");
			$service=new L_soco($db, $user);
			$service->contrat = 'INT';
			break;
		case "CHRONO":
			dol_include_once("/shippinglabels/class/chronopost.class.php");
			$service=new L_chronopost($db, $user);
			break;
		case "TNT":
			dol_include_once("/shippinglabels/class/tnt.class.php");
			$service=new L_tnt($db, $user);
			break;
		case "MR":
			dol_include_once("/shippinglabels/class/mondialRelay.class.php");
			$service=new L_MondialRelay($db, $user);
			break;
		default:
			$service=0;
	}
	return $service;
}
