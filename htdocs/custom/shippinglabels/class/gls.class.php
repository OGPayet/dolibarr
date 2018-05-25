<?php
/*  Copyright (C) 2014 Jean Heimburger <jean@tiaris.info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *   	\file       htdocs/shipppinglabels/class/gls.class.php
 *		\ingroup    Shippinglabels
 *		\brief      gls  label functions
 *		\author		Cédric Scheyder Jean Heimburger
 */

dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
dol_include_once("/shippinglabels/class/shipping_class.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';

class L_gls extends shipping_class
{
	var $access;
	var $shipnumber;
	var $centernumber;

    protected $label = null;

	function __construct($db, $user)
	{
		parent::__construct($db, $user);
		global $conf;

	$this->endpointurl = $conf->global->LOGISTIK_GLS_ENDPOINT;
	$this->labelformat = empty($conf->global->LOGISTIK_GLS_FORMATLABEL) ? "" :$conf->global->LOGISTIK_GLS_FORMATLABEL;
	switch($conf->global->LOGISTIK_GLS_FORMATLABEL)
	{
		case 'a4':
			$this->labelformat = 'A4';
			break;
		case 'a5':
			$this->labelformat = 'A5';
			break;
		case 'a6':
			$this->labelformat = 'A6';
			break;
		default :
			$this->labelformat = 'A6';
	}

	}

	/**
	 * Effectue l'appel curl rest
	 * @param unknown $params
	 * @return mixed
	 */
	function do_curl_request( $params=array())
	{
		global $conf;
		//$headerarray = array( 'Accept-Language: en', 'Accept-Encoding: gzip,deflate', 'Accept: application/json', 'Content-Type: application/json', 'Authorization: Basic ' ); //<Replace with valid credentials>

		$headerarray = array( 	'Content-Type: application/json',
				'Accept-Language: fr',
				//'Accept-Encoding: gzip,deflate',
				'Accept: application/json',
				'Content-Length: ' . strlen($params),
				'Authorization:Basic '.base64_encode($conf->global->LOGISTIK_GLS_APILOGIN.':'.$conf->global->LOGISTIK_GLS_APIPWD)
		);
		print "<p>".$conf->global->LOGISTIK_GLS_APILOGIN.':'.$conf->global->LOGISTIK_GLS_APIPWD."</p>";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_URL, $this->endpointurl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headerarray);

		$result = curl_exec($ch);

		//close connection
		curl_close($ch);

	/*	echo '<pre>';
		print_r(json_decode($result));
		echo '</pre>';
	*/
		$data = json_decode($result);

		// test d'erreurs $data->errors
		if ($data->errors)
		{
			foreach ($data->errors as $elt) $this->errors[] = $elt->exitCode.' '.$elt->exitMessage.' : '.$elt->description;
		}
		return $data;
	}

	/**
	 * Construit le tableau des paramètres
	 * $exepd expedition object
	 * @see shipping_class::set_ws_params()
	 */
	function set_ws_params($exped)
	{
		global $conf, $mysoc, $user, $langs;

		$langs->load("admin");
		$langs->load("shippinglabels@shippinglabels");

		$errorcount = 0;

		$credentials = $conf->global->LOGISTIK_GLS_CLIENT;
		if ($this->contrat == 'PR') $credentials .= ' '.$conf->global->LOGISTIK_GLS_PRCONTACTID;
		else $credentials .= ' '.$conf->global->LOGISTIK_GLS_CONTACTID;

		$references = $exped->ref ;
		$weight = $this->getWeight($exped);
		if (empty($weight))
		{
			$this->errors[] =  $langs->trans("WH_noweight");
			$this->error =  $langs->trans("WH_noweight");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		$parcel = array("weight"=>$weight);
		if (!empty($exped->note_public)) $parcel["comment"] = $exped->note_public;

		// adresse de destination
		$soc = new Societe($this->db);
		$soc->fetch($exped->socid);
		$type = ($this->contrat == 'PR') ? 'customer':''; // pour les points relais prendre le contact de commande
		$contactid = get_shippingcontact($exped, $type);
		if ($contactid > 0)
		{
			$contact = new Contact($this->db);
			$contact->fetch($contactid);
			dol_syslog(get_class($this).":: contact ".print_r($contact, true), LOG_DEBUG);
		}
		else
		{
			dol_syslog(get_class($this)."::on se base sur la société ", LOG_DEBUG);
			// adresse société
			$contact = new Contact($this->db);

			$contact->lastname = $soc->name;
			$contact->address = $soc->address;
			$contact->zip = $soc->zip;
			$contact->town = $soc->town;
			$contact->country_code = $soc->country_code;
		}
		dol_syslog(get_class($this).":: société ".print_r($contact, true), LOG_DEBUG);

		$contact->phone_pro = $this->get_phone($contact, $soc, $conf->global->SHIPPERPHONE);
		dol_syslog(get_class($this).":: tél dest = ".$contact->phone_pro, LOG_DEBUG);

		$errorcount=0;
		if (empty($contact->address))
		{
			$contact->address = $soc->address;
			$contact->zip = $soc->zip;
			$contact->town = $soc->town;
			$contact->country_code = $soc->country_code;
		}
		if (empty($contact->address))
		{
			$this->errors[] =  $langs->trans("addressNotFilled");
			$this->error =  $langs->trans("addressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		$address = $this->format_address($contact->address);
		if (empty($contact->country_code))
		{
			$this->errors[] =   $langs->trans("countryNotFilled");
			$this->error =   $langs->trans("countryNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		// code pays doit être correct
		if (empty($contact->phone_pro) )
		{
			$this->errors[] =   $langs->trans("phoneNotFilled");
			$this->error =   $langs->trans("phoneNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (strlen($contact->phone_pro) > 15 )
		{
			$this->errors[] =  $langs->trans("phoneTooLong");
			$this->error =  $langs->trans("phoneTooLong");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		if (empty($mysoc->phone))
		{
			$this->errors[] =  $langs->trans("shipperPhoneNotFilled");
			$this->error =  $langs->trans("shipperPhoneNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		if (strlen($mysoc->phone) > 15 )
		{
			$this->errors[] =  $langs->trans("shipperPhoneTooLong");
			$this->error =  $langs->trans("shipperPhoneTooLong");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		if ($this->contrat == 'PR')
		{
			// récupération des infos point Relay
			$exped->fetchObjectLinked();
			foreach($exped->linkedObjectsIds["commande"] as $id) $doli_order = $id;

			dol_include_once("/ecommerce/class/E_order.class.php");
			$eorder = new E_order($db);
			$shippiniginfo = $eorder->get_shippinginfos4order($doli_order);
			$xml = simplexml_load_string($shippiniginfo);
			// TODO point relais
		}

		$delivery = array(
			"name1"=> $soc->name, //nom ou raison sociale
			"street1"=>	$address[0],
			"name2"=>(count($address)>1)?$address[1]:'',
			"name3"=>(count($address)>2)?$address[2]:'',
			"country"=>$contact->country_code,
			"zipCode"=>$contact->zip,
			"city"=>$contact->town,
			"province"=>$contact->state,
			"contact"=>$contact->getFullName($langs),
			"email"=>$contact->email,
			"phone"=>$contact->phone_pro,
			"mobile"=>$contact->phone_mobile,
		//	"incoterm"=>''
		);

		$params = array(
			"shipperId"=>$credentials,
			"references"=>array($exped->ref),
			"addresses"=>array(
					"delivery"=>$delivery,
			),
			"parcels"=>array($parcel),
			"labelFormat"=>"PDF",
			"labelSize"=>$this->labelformat,
		);

		return $params;
	}

/**
 * Appel API, sauvégarde de l'étiquette
 * $exped Expedition object
 * @see shipping_class::ws_call()
 */
	function ws_call($exped)
	{
		global $conf, $langs;

		dol_syslog(get_class($this)."::".__FUNCTION__."=== ws_call begins ===", LOG_DEBUG);

		$this->get_label($exped); // initialiser les chemins
		if (empty($exped))
		{
			$this->error =  "exped is empty";
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		}
		if (empty($conf->global->LOGISTIK_GLS_ENDPOINT))
		{
			$this->errors[] =  "Shipping method not configured";
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		}

		$shipment_request = $this->set_ws_params($exped);
		dol_syslog(get_class($this)."::".__FUNCTION__."--- getFunctions ---".print_r($shipment_request, true), LOG_DEBUG);
		if ($shipment_request!=-1)
		{
			$jparam = json_encode($shipment_request);
			$result = $this->do_curl_request($jparam);
			dol_syslog(get_class($this)."::".__FUNCTION__."=== post_request returns ".print_r($result, true)." ===", LOG_DEBUG);
			if (!$result)
			{
				dol_syslog(__METHOD__.'::erreur pas de résultat ', LOG_ERR);
				return -1;
			}
			if ($result->errors)
			{
				return -1;
			}
			else
			{
				//print "<p><pre>".print_r($result,true)."</pre></p>";
				$this->trackingnumber = $result->parcels[0]->trackId;
				$content =  base64_decode($result->labels[0]);
				return $this->saveLabel($content, $exped->ref);
			}
		}
		else return -1;

		return 0;
	}

	/**
	 * Enregistre l'étiquette
	 * @param unknown $content objet résultant de l'API
	 * @param string $ref de l'expédition
	 */
	function saveLabel($content, $ref = '')
	{
		global $conf, $langs;

		$f = fopen($this->labelDir.'/'.$this->labelFile, "w");
		fwrite($f,$content);
		fclose($f);
	}

	/**
	 * set dir and label filename from exeped
	 * return path of label
	 * @param int $expedid
	 */
	function get_label($exped)
	{
		global $conf, $langs;

		$this->labelDir = $conf->expedition->dir_output . "/sending/". $exped->ref ;
		dol_mkdir($this->labelDir);

		$this->labelFile = "/GLS_".$exped->ref;
		$this->labelFile .= ".pdf";
	}

	/**
	 * Formate une adresse Dolibarr en tableau de lignes 0 est la ligne contenant la rue
	 * @param unknown $address
	 * @return multitype:Ambigous <string, mixed> unknown
	 */
	function format_address($address)
	{
		$ad = explode(PHP_EOL, $address);
		//	print "<p><pre>".print_r($ad, true)."</pre></p>";

		$ret = array();
		if (is_array($ad) && count($ad))
		{
			$last = '';
			while (empty($last) && count($ad))
			{
				$last = array_pop($ad);
			}
			if (!empty($last)) $ret[] = $last;
			foreach ($ad as $elt ) if (!empty($elt)) $ret[] = $elt;
		}
		return $ret;
	}


	// TODO label seccours

	function saveLabelSecours($result, $ref = '')
	{
		$data = $this->set_ws_paramsSecours($result);
		$result["datamatrix"] = $data;
		dol_syslog(__METHOD__.":: ".$result, LOG_DEBUG);
		$this->create_pdf_secours($result, $ref);

		return 0;
	}

	function set_ws_paramsSecours($result)
	{
		global $conf, $mysoc, $user, $langs;

	    $langs->load("admin");
	    $langs->load("shippinglabels@shippinglabels");


		$len = strlen($result['T860'].$result['T861'].$result['T862'].$result['T863'].$result['T864']);
		if($len>100)
		{
			$result['T862'] = substr($result['T862'],0, $len-100);
			$len = strlen($result['T860'].$result['T861'].$result['T862'].$result['T863'].$result['T864']);
			if($len>100)
			{
				$result['T861'] = substr($result['T861'],0, $len-100);
			}
			$len = strlen($result['T860'].$result['T861'].$result['T862'].$result['T863'].$result['T864']);
			if($len>100)
			{
				$result['T860'] = substr($result['T860'],0, $len-100);
			}
		}

		$request="A|";
		$request .= $conf->global->LOGISTIK_GLS_CLIENT."|";
		$request .= $conf->global->LOGISTIK_GLS_CONTACTID."|";
		$request .= "AA|";
		$request .= $conf->global->LOGISTIK_GLS_COUNTRYCODE."|";
		$request .= $result['T330']."|";
		$request .= "001|";
		$request .= "001|";
		$request .= "|";
		$request .= $result['T860']."|";
		$request .= $result['T861']."|";
		$request .= $result['T862']."|";
		$request .= $result['T863']."|";
		$request .= "|";
		$request .= $result['T864']."|";
		$request .= $result['T871']."|";
		$request .= "|";
		$request .= $result['T8975']."|";
		$poids = $result['T530'];
		while(strlen($poids)<5)
		{
			$poids = '0'.$poids;
		}
		$request .= $poids."|";
		while(strlen($request)<303)
		{
			$request .= " ";
		}
		$request .= "|";

		dol_syslog(get_class($this)."::".__FUNCTION__."=== returns ".strlen($request).": ".($request)." ===", LOG_DEBUG);

	    return $request;
	}


	function splitContent($content)
	{
		$ret = null;

		$pos = 0;
		$pos = strpos($content, "T", $pos);
		while($pos!==false)
		{
			$separator = strpos($content, ":", $pos);
			$end = strpos($content, "|", $pos);
			$ret[substr($content, $pos, ($separator-$pos))] = substr($content, $separator+1, ($end-$separator)-1);
			$pos++;
			$pos = strpos($content, "T", $pos);
		}

		return $ret;
	}

	function create_pdf($content, $ref)
	{
		global $conf, $langs;
		// créer la classe selon modèle et appeler write_file

		switch ($this->labelformat)
		{
			case 'a5':
				dol_include_once("/shippinglabels/class/pdf_label_glsa5.class.php");
				$labelclass = new Pdf_label_glsa5($this->db);
				$labelclass->contrat = $this->contrat;
				break;
			case 'a6':
				dol_include_once("/shippinglabels/class/pdf_label_glsa6.class.php");
				$labelclass = new Pdf_label_glsa6($this->db);
				$labelclass->contrat = $this->contrat;
				break;
			default:
				$labelclass = false;
		}

		if ($labelclass) $labelclass->write_file($content, $langs, $ref);

	}

	function create_pdf_secours($content, $ref)
	{
		global $conf, $langs;
		// créer la classe selon modèle et appeler write_file
		dol_include_once("/shippinglabels/class/pdf_label_secours_gls.class.php");
		$labelclass = new Pdf_label_secours_gls($this->db, $this->labelformat);
		$labelclass->write_file($content, $langs, $ref);

	}


}
?>
