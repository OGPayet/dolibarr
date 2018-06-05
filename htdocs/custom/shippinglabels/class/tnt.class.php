<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Jean Heimburger <jean@tiaris.info>
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
 *   	\file       htdocs/logistique/class/ups.class.php
 *		\ingroup    logistique
 *		\brief      ups  functions
 *		\author		Jean Heimburger
 *		\remarks	Put here some comments
 */

dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
dol_include_once("/shippinglabels/class/shipping_class.class.php");

class L_tnt extends shipping_class
{

	function __construct($db, $user)
	{
		parent::__construct($db, $user);
		global $conf;

		// set webservice
	$this->endpointurl = $conf->global->TNT_ENDPOINT;
	$this->wsdl = $this->endpointurl.'?WSDL';
		$this->userid = $conf->global->TNT_USERID;
		$this->passwd = $conf->global->TNT_PWD;

		// Contrôles
	}

	/**
	 *
	 * Load Ups parameters for specified shipping method
	 * @param string $code
	 */
	function load_params($code)
	{

	}

	function set_ws_params($exped)
	{
	    global $conf, $mysoc, $user, $langs;
	    $langs->load("admin");
	    $langs->load("shippinglabels@shippinglabels");
	    if (empty($exped->id))
		{
		$this->error = "Erreur lecture expédition";
		dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
		return -1;
	    }
		//	dol_syslog(get_class($this)."::".__FUNCTION__."expédition ".print_r($exped, true), LOG_DEBUG);

		// adresse de destination
		$soc = new Societe($this->db);
		$soc->fetch($exped->socid);
		$contactid = get_shippingcontact($exped);
		if ($contactid > 0)
		{
			$contact = new Contact($this->db);
			$contact->fetch($contactid);
		}
		else
		{

// 		// adresse société
			$contact = new Contact($this->db);

			$contact->lastname = $soc->name;
			//$contact->firstname = "  ";
			$contact->address = $soc->address;
			$contact->zip = $soc->zip;
			$contact->town = $soc->town;
			$contact->country_code = $soc->country_code;
			$contact->email = $soc->email;
			$contact->zip=$soc->zip;
			$contact->phone_pro = $soc->phone;
		}

		$errorcount = 0;
		if (empty($conf->global->SHIPPERNAME))
		{
			$this->errors[] =  $langs->trans("shipperFirmNotFilled");
			$this->error =  $langs->trans("shipperFirmNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($conf->global->SHIPPERADDRESS))
		{
			$this->errors[] =  $langs->trans("shipperAddressNotFilled");
			$this->error =  $langs->trans("shipperAddressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($conf->global->SHIPPERTOWN))
		{
			$this->errors[] =  $langs->trans("shipperTownNotFilled");
			$this->error =  $langs->trans("shipperTownNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($conf->global->SHIPPERZIP))
		{
			$this->errors[] =  $langs->trans("shipperZipNotFilled");
			$this->error =  $langs->trans("shipperZipNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		$sender['address1']=$conf->global->SHIPPERADDRESS; //Obligatoire
		//$sender['address2']="2 rue du test";
		$sender['city']=$conf->global->SHIPPERTOWN; //Obligatoire
		$sender['contactFirstName']=$conf->global->SHIPPERATTENTION2;
		$sender['contactLastName']=$conf->global->SHIPPERATTENTION;
		$sender['emailAddress']=$conf->global->SHIPPERMAIL;
		$sender['name']=$conf->global->SHIPPERNAME; //Obligatoire
		$sender['phoneNumber']=$conf->global->SHIPPERPHONE; //Numéro à 10 chiffres au moins et qui commence par 0
		$sender['zipCode']=$conf->global->SHIPPERZIP; //Obligatoire

		if (empty($contact->address))
		{
			$contact->address=$soc->address;
			$contact->zip = $soc->zip;
			$contact->town = $soc->town;
			$contact->country_code = $soc->country_code;
			$contact->state_code=$soc->state_code;

		}
		if (empty($contact->address))
		{
			$this->errors[] =  $langs->trans("addressNotFilled");
			$this->error =  $langs->trans("addressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($contact->lastname))
		{
			$this->errors[] =  $langs->trans("lastNameNotFilled");
			$this->error =  $langs->trans("lastNameNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($contact->email))
		{
			$this->errors[] =  $langs->trans("mailNotFilled");
			$this->error =  $langs->trans("mailNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		$adlines= array();
		$adlines = explode("\r", trim($contact->address));
		$adlines2 = array();
		$adlines2 = explode("\n", $adlines[0]);

		$adresse2='';
		for($i=1;$i<count($adlines);$i++)
		{
		$adresse2.=$adlines[$i].' ';
		}
		$adlines3 = array();
		$adlines3 = explode("\n", $adresse2);
		$receiver['address2']='';
		for($i=1;$i<count($adlines3);$i++)
		{
			$receiver['address2'].=$adlines3[$i].' ';
		}

		//$receiver['accessCode']="";
		$receiver['address1']=$adlines2[0];
		//$receiver['address2']='';
		//$receiver['buildingId']='1';
		$receiver['city']="$contact->town";
		$receiver['contactFirstName']=$contact->firstname;
		$receiver['contactLastName']=$contact->lastname;
		$receiver['emailAddress']=$contact->email;
		//$receiver['instructions'];
		$receiver['name']=$soc->name;
		$receiver['phoneNumber']= $this->get_phone($contact, $soc);//$contact->phone_mobile;
		$receiver['sendNotification']="0";
// 		$receiver['type']="INDIVIDUAL";
// 		$receiver['typeId']='';
		$receiver['zipCode']=$contact->zip;

		$parcelRequest['weight'] = $this->getWeight($exped);

		if (empty($parcelRequest['weight']))
		{
			$totalWeight=0;

			$totalWeight=calculate_weight($exped);
			$parcelRequest['weight']=$totalWeight;
		// ne pas mettre l'expéd à jour	$exped->trueWeight=$totalWeight;
		}

		// test sur téléphone
		if (empty($receiver['phoneNumber']))
		{
			$this->errors[] =  $langs->trans("phoneNotFilled");
			$this->error =  $langs->trans("phoneNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		// poids doit être renseigné
		if (empty($parcelRequest['weight']))
		{
			$this->errors[] =  $langs->trans("WH_noweight");
			$this->error =  $langs->trans("WH_noweight");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		$parcelRequest['sequenceNumber']=1;
		$date = date("Y-m-d");
		$parameters['shippingDate']=$date;
		$parameters['accountNumber']=$conf->global->TNT_ACCESS;
		$parameters['sender']=$sender;
		$parameters['receiver']=$receiver;
		$parameters['serviceCode']="A";
		$parameters['quantity']="1";
		$parameters['parcelsRequest']=array("parcelRequest"=>$parcelRequest);
		$parameters['saturdayDelivery']="0";
		$parameters['labelFormat']="STDA4";

		$request=array('parameters' => $parameters);

		if ($errorcount)
		{
			return -1;
		}
		return $request;
	}

	function ws_call($exped)
	{
		global $conf;
		if (empty($exped))
		{
			$this->error =  "exped is empty";
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		}
		if (empty($this->wsdl))
		{
			$this->errors[] =  "Shipping method not configured";
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		}

		$username = $conf->global->TNT_USERID;
		$password = $conf->global->TNT_PWD;

		// Generation "en dur" de l'en tête d'authentification pour WS-Security
		$authheader = sprintf('
	<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	  <wsse:UsernameToken>
		<wsse:Username>%s</wsse:Username>
		<wsse:Password>%s</wsse:Password>
	 </wsse:UsernameToken>
	</wsse:Security>', htmlspecialchars($username), htmlspecialchars( $password ));

		$authvars = new SoapVar($authheader, XSD_ANYXML);
		$header = new SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", "Security", $authvars);
		$file = "http://www.tnt.fr/service/?wsdl";

		try {
			// Construction du client et ajout de l'en tête WS-Security
			// Précision EXPLICITE du user-agent pour ne pas être bloqué par les règles de filtrage
			$soapclient = new SoapClient($this->wsdl);

			$soapclient->__setSOAPHeaders(array($header));

		}
		catch( SoapFault $e ) {
			dol_syslog(get_class($this)."::".__FUNCTION__."Soapfault ".print_r($e, true). " string ".$f->faultstring, LOG_ERR);
			$this->errors[] = $e->faultstring;
			return -1;
		}
		catch( Exception $e ) {
			dol_syslog(get_class($this)."::".__FUNCTION__."Soapfault ".print_r($e, true). " string ".$f->faultstring, LOG_ERR);
			$this->errors[] = $e->message;
			return -1;
		}

		$test=$this->set_ws_params($exped);
		dol_syslog(get_class($this)."::".__FUNCTION__."params ".print_r($test, true), LOG_DEBUG);
		if (is_array($test))
		{
			try
			{
				$this->get_label($exped);
				$dir_osencoded=dol_osencode($this->labelDir);
				$response = $soapclient->expeditionCreation($test);
				if (!empty($response->Expedition->parcelResponses->parcelNumber))
				{
					dol_syslog(get_class($this)."::".__FUNCTION__."response ".print_r($response, true), LOG_DEBUG);
					$fp = fopen($this->labelDir.$this->labelFile, 'wb');
					fwrite($fp, $response->Expedition->PDFLabels);
					fclose($fp);
					$this->trackingnumber=$response->Expedition->parcelResponses->parcelNumber;
				}
				else
				{
					if(file_exists($this->labelDir.$this->labelFile)==false)
					{
						$this->errors[]= $langs->trans("LabelError");
					}
					else
					{
						$this->errors[]= $langs->trans("LabelError2");
					}
					return -1;
				}
			}
			catch(SoapFault $f)
			{
				dol_syslog(get_class($this)."::".__FUNCTION__."Soapfault ".print_r($f, true). " string ".$f->faultstring, LOG_ERR);
				$this->errors[] = $f->faultstring;
				return -1;
			}
		}else return -1;

		return 0;
	}

	function get_label($exped)
	{
		global $conf, $langs;

		$this->labelDir = $conf->expedition->dir_output . "/sending/". $exped->ref ;
		dol_mkdir($this->labelDir);
		dol_syslog(get_class($this)."::".__FUNCTION__.":: dir : " . $dir, LOG_DEBUG);

		$this->labelFile = "/TNT_".$exped->ref.".pdf" ; // ref de l'expédition
	}
}
?>
