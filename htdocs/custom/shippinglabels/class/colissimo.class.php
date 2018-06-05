<?php
/* Copyright (C) 2014 Jean Heimburger <jean@tiaris.info>
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
 *   	\file       htdocs/logistique/class/colissimo.class.php
 *		\ingroup    shippinglabels
 *		\brief      colissimo  functions
 *		\author		Jean Heimburger
 */

dol_include_once("/shippinglabels/class/shipping_class.class.php");
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");


class L_soco extends shipping_class
{
	function __construct($db, $user)
	{
		parent::__construct($db, $user);
		global $conf;
		// set webservice

	$this->endpointurl = $conf->global->SOCO_ENDPOINT;
	$this->wsdl = $this->endpointurl.'?wsdl';
		$this->userid = $conf->global->SOCO_USERID;
		$this->passwd = $conf->global->SOCO_PWD;
		$this->version = '2.0';
		// Contrôles
		$f = (empty($conf->global->SOCO_FORMATLABEL)) ? '' : $conf->global->SOCO_FORMATLABEL ;
		switch ($f)
		{
			// voir pour les autres cas
			case 'a4':
				$this->labelformat = 'PDF_A4_300dpi';
				break;
			case '10x15':
				$this->labelformat = 'PDF_10x15_300dpi';
				break;
			default:
				$this->labelformat = 'PDF_10x15_300dpi';
		}
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

//		$exp = new Expedition($db);
		$langs->load("admin");
		$langs->load("shippinglabels@shippinglabels");

		if (empty($exped->id))
		{
			$this->error = "Erreur lecture expédition";
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		}

		// le contrat
		switch ($this->contrat)
		{
			case 'OM' :
				$contrat = "COM"; //'ECO'; //outremer
				break;
			case 'INT' :
				$contrat = 'COLI'; //international
				break;
			default :
				$contrat = 'DOM'; // par défaut
		}

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
			$this->errors=null;
			$this->errors[] =  $langs->trans("noContactLinked");
			$this->error =  $langs->trans("noContactLinked");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
			return -1;
		}

		// le poids
		$poids = $this->getWeight($exped);
		if (empty($poids))
		{
			$this->errors[] =  $langs->trans("WH_noweight");
			$this->error =  $langs->trans("WH_noweight");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		// la commande liée
		$exped->fetchObjectLinked();
		$com = array_pop($exped->linkedObjects['commande']);
		// TODO tester ....
		$comref = $com->ref.((!empty($com->ref_client))? " / ".$com->ref_client:"");

		// contrôles
		$errorcount = 0;

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
			$this->error=  $langs->trans("addressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		// adresse dest
		$addest = $this->format_address($contact->address);
		if (count($addest) == 0)
		{
			$this->errors[] =  $langs->trans("addressNotFilled");
			$this->error=  $langs->trans("addressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error address conversion ".$this->error, LOG_ERR);
			$errorcount++;
		}
		// adresse envoi
		$adexp = $this->format_address($conf->global->SHIPPERADDRESS);
		if (count($adexp) == 0)
		{
			$this->errors[] =  $langs->trans("addressNotFilled");
			$this->error=  "Shipper ".$langs->trans("addressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error shipper address conversion ".$this->error, LOG_ERR);
			$errorcount++;
		}

		if (empty($contact->lastname))
		{
			$this->errors[] =  $langs->trans("lastNameNotFilled");
			$this->error=  $langs->trans("lastNameNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($contact->firstname))
		{
			$this->errors[] =  $langs->trans("firstNameNotFilled");
			$this->error =  $langs->trans("firstNameNotFilled");
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
		/*
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
		*/
		if ($errorcount)
		{
			return -1;
		}

		// retrun type
/*	service HS
		$datas = array();
		$datas['contractNumber'] = (string) $conf->global->SOCO_USERID;
		$datas['password'] = $conf->global->SOCO_PWD;
		$datas["productCode"] = $contrat;
		$datas["insurance"] = '0';
		$datas["nonMachinable"] = '0';
		$datas["returnReceipt"] = '0';
		$datas["countryCode"] = $contact->country_code;
		$datas["zipCode"] = $contact->zip;

		$this->get_productinter($datas);
*/

		//create soap request
		$request = array();
		// identification
		$request['contractNumber'] = (string) $conf->global->SOCO_USERID;
		$request['password'] = $conf->global->SOCO_PWD;
		// format de sortie
		$x=0; $y = 0;
		if (!empty($conf->global->SOCO_MARGES_LABEL))
		{
			$d = explode(":", $conf->global->SOCO_MARGES_LABEL);
			$x = $d[0];
			$y = (count($d)) ? $d[1]:0 ;
		}

		$format = array(
			'x' => $x,
			'y' => $y,
		//	'returnType' => '',
			'outputPrintingType' => $this->labelformat
		);
		$request['outputFormat'] = $format;
		// expédition
		$letter = array();
		// bloc service
		$service = array();

		$service["productCode"] = $contrat;
		$service["depositDate"] = dol_print_date(dol_now(), "%Y-%m-%d");

		if (!empty($this->contrat))
		{
			$service['transportationAmount'] = '0';
			$service['totalAmount'] = '500';  // frais de port
		}

		$service["orderNumber"] = $comref; // plutôt ref commande + client
		$service["commercialName"] = $conf->global->SHIPPERNAME; // nom du client
		// autres facultatifs

		if (!empty($this->contrat))
		{
			$service['returnTypeChoice'] = '2';
		}
		$letter["service"] = $service;

		// bloc colis seul poids est obligatoire
		$parcel = array(
				"weight" => $poids, // NB en kg
			//	"insuranceValue" => 0,
			//	"recommendationLevel" => '',
				"nonMachinable" => 0
				// autres facultatifs
		);
		$letter["parcel"] = $parcel;

		// bloc douane non géré
		if (empty($this->contrat)) $customs = array("includeCustomsDeclarations" => 0);
		else
		{
// TODO à vérifier ou baser sur exped->lines
			$articles = array('description'=> 'order',
								'quantity'=> 1, //$exped->lines[$i]->qte;
								'weight'=> $poids,//$exped->lines[$i]->trueWeight;
								'value'=> 1//$exped->lines[$i]->prix;
								);
			$customs = array(	"includeCustomsDeclarations" => 1,
								"contents" => array(
										"article"=>$articles,
										"category" => array("value" => 1)
									)
								);

		}
		$letter['customsDeclarations'] = $customs;

		//bloc sender
		$sender = array(
				"senderParcelRef" => $exped->ref, // ref expéd
				"address" => array(
						"companyName" => $conf->global->SHIPPERNAME,
						"lastName" => $conf->global->SHIPPATTENTION,
						"firstName" => $conf->global->SHIPPATTENTION2,
						"line0" => (empty($adexp[1]))?'':$adexp[1],
						"line1" => (empty($adexp[2]))?'':$adexp[2],
						"line2" => $adexp[0],//$conf->global->SHIPPERADDRESS,
						"line3" => "",
						"countryCode" => $conf->global->SHIPPERCOUNTRYCODE,
						"city" => $conf->global->SHIPPERTOWN,
						"zipCode" => $conf->global->SHIPPERZIP,
						"phoneNumber" => $conf->global->SHIPPERPHONE,
						"mobileNumber" => $conf->global->SHIPPERPHONE,
						"email" => $conf->global->SHIPPERMAIL,
						"intercom" => "",
						"language" => "",
				),
		);
		$letter["sender"] = $sender;

		$destinataire = array(
				"addresseeParcelRef" => "",
				"codeBarForReference" => "false",
				"serviceInfo" => "",
				"address" => array(
						"companyName" =>$soc->name,
						"lastName" => $contact->lastname,
						"firstName" => $contact->firstname,
						// TODO adresse ppale en line 2
						"line0" => (empty($addest[1]))?'':$addest[1],
						"line1" => (empty($addest[2]))?'':$addest[2],
						"line2" => $addest[0],
						"line3" => "", //> (empty($adlines2[3]))?"":$adlines2[3],
						"countryCode" => $contact->country_code,
						"city" => $contact->town,
						"zipCode" => $contact->zip,
						"phoneNumber" => $contact->phone_pro,
						"mobileNumber" => $contact->phone_mobile,
						"doorCode1" => "",
						"doorCode2" => "",
						"email" => $contact->email,
						"intercom" => "",
						"language" => "",
				),
		);
		$letter["addressee"] = $destinataire;

		$request['letter'] = $letter;

		// fields point relais
		$fields = array();
		$request['fields'] = $fields;
		dol_syslog(__METHOD__.":: params ".print_r($request, true), LOG_DEBUG);
		return $request;
	}

	function set_ws_params_old($exped)
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

		// adresse de destination
		$soc = new Societe($this->db);
		$soc->fetch($exped->socid);
		$contactid = get_shippingcontact($exped);
		if ($contactid > 0)
		{
			$contact = new Contact($this->db);
			$contact->fetch($contactid);
		//	dol_syslog(get_class($this).":: contact ::".print_r($contact, true), LOG_DEBUG);
		}
		else
		{
			$this->errors=array();
			$this->errors[] =  $langs->trans("noContactLinked");
			$this->error =  $langs->trans("noContactLinked");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
			return -1;
		}
		//create soap request
	    $requestoption['RequestOption'] = 'nonvalidate';
	    $request['Request'] = $requestoption;

	    $date = date("Y-m-d");
	    $heure = date("H:i:s");

	    $serviceelement['heureLimiteDepot'] = '';
	    $serviceelement['dateDeposite']=$date.'T'.$heure;
	    $serviceelement['returnType']="CreatePDFFile";
	    $serviceelement['serviceType']="SO";
	    $serviceelement['crbt']=false;
	   // $serviceelement['crbtAmount']=0;
	    $serviceelement['VATCode']=0;
	    $serviceelement['VATPercentage']=20;
	    $serviceelement['VATAmount']=0;
	    $serviceelement['transportationAmount']=0;
	    $serviceelement['totalAmount']=$exped->total_tva;
	    $serviceelement['portPaye']=0;
	    $serviceelement['FTD']=0;
	    $serviceelement['FTDAmount']=0;
	    $serviceelement['returnOption']=0;
	    $serviceelement['returnOptionAmmount']=0;
	    $serviceelement['commandNumber']= $exped->linkedObjects[Commande][0];
	    $serviceelement['motiveBack']='';
	    $serviceelement['logo-co-brande']="";
	    $serviceelement['commercialName']=$soc->name;
	    $serviceelement['partnerNetworkCode']='';
	    $serviceelement['languageConsignor']='';
	    $serviceelement['languageConsignee']='';

	    $nb_ligne=count($exped->lines);
	    for ($i=0 ;$i<$nb_ligne;$i++ )
		{
			$article[$i]['description']="";
			$article[$i]['quantite']=1;//$exped->lines[$i]->qte;
			$article[$i]['poids']=1;//$exped->lines[$i]->trueWeight;
			$article[$i]['valeur']=10;//$exped->lines[$i]->prix;
		}

		$contentelement['article']=$article;
		$contentelement['categorie']=3;

		$parcelelement['insuranceRange']='';
		$parcelelement['typeGamme']='';
		$parcelelement['parcelNumber']=$exped->tracking_number;
		$parcelelement['returnTypeChoice']='';
		$parcelelement['insuranceAmount']='';
		$parcelelement['insuranceValue']='';
		$parcelelement['recommendationLevel']='';
		$parcelelement['RecommendationAmount']='';
		$parcelelement['weight']=$this->getWeight($exped);
		$parcelelement['horsGabarit']=0;
		$parcelelement['HorsGabaritAmount']=0;
		$parcelelement['DeliveryMode']="DOM";
		$parcelelement['ReturnReceipt']=0;
		$parcelelement['Recommendation']=0;
		$parcelelement['Instructions']='';
		$parcelelement['RegateCode']='';
		$parcelelement['contents']=$contentelement;

/* poids calculé ailleurs
 * 		if (empty($parcelelement['weight']))
		{
			$totalWeight=0;
			$totalWeight=calculate_weight($exped);
			$parcelelement['weight']=$totalWeight;
			$exped->weight_units=0;
		}
		$poids=0;
		// conversion en kg
		if($exped->weight_units==0)
		{
			$poids=$exped->trueWeight;
		}
		else
		{
			$unit=$exped->weight_units;
			$poids=weight_convert($exped->trueWeight,$exped->weight_units,0);
			$exped->weight_units=$unit;
		}
*/
		$errorcount = 0;


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
			$this->error=  $langs->trans("addressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($contact->lastname))
		{
			$this->errors[] =  $langs->trans("lastNameNotFilled");
			$this->error=  $langs->trans("lastNameNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($contact->firstname))
		{
			$this->errors[] =  $langs->trans("firstNameNotFilled");
			$this->error =  $langs->trans("firstNameNotFilled");
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
// 		if(empty($contact->firstname)&&empty($contact->lastname))
// 		{
// 			$this->errors=null;
// 			$this->errors[] =  $langs->trans("noContactLinked");
// 			$this->error =  $langs->trans("noContactLinked");
// 			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
// 			$errorcount++;
// 		}

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

		$adr_destinaire['companyName']=$soc->name;
		$adr_destinaire['Civility']=$contact->civility_id;
		$adr_destinaire['Name']=$contact->lastname;
		$adr_destinaire['Surname']=$contact->firstname;
		$adr_destinaire['line0']='';
		$adr_destinaire['line1']='';
		$adr_destinaire['line2']=$adlines2[0];
		$adr_destinaire['line3']='';
		for($i=1;$i<count($adlines3);$i++)
		{
			$adr_destinaire['line3'].=$adlines3[$i].' ';
		}

		$adr_destinaire['phone']=$contact->phone_pro;
		//$adr_destinaire['MobileNumber']=$contact->phone_mobile; //provoque une erreur disant que le numéro est incorrect
		$adr_destinaire['DoorCode1']='';
		$adr_destinaire['DoorCode2']='';
		$adr_destinaire['Interphone']='';
		$adr_destinaire['country']=$contact->country;
		$adr_destinaire['countryCode']=$contact->country_code;
		$adr_destinaire['city']=$contact->town;
		$adr_destinaire['email']=$contact->email;
		$adr_destinaire['postalCode']=$contact->zip;

		$destinataire['ref']='';
		$destinataire['alert']="none";
		$destinataire['addressVO']=$adr_destinaire;
		$destinataire['codeBarForreference']=false;
		$destinataire['deliveryInfoLine1']='';
		$destinataire['deliveryInfoLine2']='';
		$destinataire['serviceInfo']='';
		$destinataire['promotionCode']='';

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
			$this->error=  $langs->trans("shipperAddressNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($conf->global->SHIPPERTOWN))
		{
			$this->errors[] =  $langs->trans("shipperTownNotFilled");
			$this->error=  $langs->trans("shipperTownNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($conf->global->SHIPPERZIP))
		{
			$this->errors[] =  $langs->trans("shipperZipNotFilled");
			$this->error=  $langs->trans("shipperZipNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		$adr_expediteur['companyName']=$conf->global->SHIPPERNAME;
		$adr_expediteur['Civility']=$conf->global->SHIPPERCIVILITY;
		$adr_expediteur['Name']=$conf->global->SHIPPATTENTION;
		$adr_expediteur['Surname']=$conf->global->SHIPPATTENTION2;  //N'est pas utile
		$adr_expediteur['line0']='';
		$adr_expediteur['line1']='';
		$adr_expediteur['line2']=$conf->global->SHIPPERADDRESS;
		$adr_expediteur['line3']='';
		$adr_expediteur['phone']=$conf->global->SHIPPERPHONE;
		$adr_expediteur['MobileNumber']=$conf->global->SHIPPERPHONE;
		$adr_expediteur['DoorCode1']='';
		$adr_expediteur['DoorCode2']='';
		$adr_expediteur['Interphone']='';
		$adr_expediteur['country']="France";
		$adr_expediteur['countryCode']=$conf->global->SHIPPERCOUNTRYCODE;;
		$adr_expediteur['city']=$conf->global->SHIPPERTOWN;
		$adr_expediteur['email']=$conf->global->SHIPPERMAIL;
		$adr_expediteur['postalCode']=$conf->global->SHIPPERZIP;

		$expediteur['ref']='';
		$expediteur['alert']="none";
		$expediteur['addressVO']=$adr_expediteur;

		$lettre['password']=$conf->global->SOCO_PWD;
		$lettre['contractNumber']=$conf->global->SOCO_USERID;
		$lettre['profil']='';
		$lettre['coordinate']=array("x"=>0, "y"=>0);
		$lettre['service']=$serviceelement;
		$lettre['parcel']=$parcelelement;
		$lettre['dest']=$destinataire;
		$lettre['exp']=$expediteur;

		if ($errorcount)
		{
			return -1;
		}

		$request=array("letter"=>$lettre);

	    return $request;
	}


	/**
	 * les valeurs possibles de getproductinter
	 *
	 * @param unknown $exped
	 */
	function get_productinter($datas)
	{

		print_r($datas);
		$wsclient = new soapclient($this->wsdl);
		$xml = new SimpleXMLElement('<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" />');
		$xml->addChild("soapenv:Header");
		$children = $xml->addChild("soapenv:Body");
		$children = $children->addChild("sls:getProductInter", null,
				'http://sls.ws.coliposte.fr');
		$children = $children->addChild("getProductInterRequest", null, "");

		$this->array_to_xml($datas, $children);
		//$this->array_to_xml($lettre,$children);
		$requestSoap = $xml->asXML();

		$response = $wsclient->__doRequest($requestSoap, $this->endpointurl, 'getProductInter', $this->version);

		print "<p>reponse <pre>".print_r($response, true)."</pre></p> ";
	}

	function ws_call($exped)
	{
		global $conf,$user, $langs;

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
		dol_syslog(__METHOD__.'::url '.$this->wsdl, LOG_DEBUG);
		$wsclient = new soapclient($this->wsdl);
		$lettre=$this->set_ws_params($exped);
		//print "<p><pre>".print_r($lettre, true)."</pre></p>";
		dol_syslog(__METHOD__.":: lettre ". print_r($lettre, true), LOG_DEBUG);
		if (is_array($lettre))
		{
			try
			{
				$this->get_label($exped);
				// conversion en xml
				//+ Generate SOAPRequest
				$xml = new SimpleXMLElement('<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" />');
				$xml->addChild("soapenv:Header");
				$children = $xml->addChild("soapenv:Body");
				$children = $children->addChild("sls:generateLabel", null,
						'http://sls.ws.coliposte.fr');
				$children = $children->addChild("generateLabelRequest", null, "");
				$this->array_to_xml($lettre, $children);
				//$this->array_to_xml($lettre,$children);
				$requestSoap = $xml->asXML();



				$response = $wsclient->__doRequest($requestSoap, $this->endpointurl, 'generateLabel', $this->version);

				//print "<p>reponse<pre>".print_r($response, true)."</pre></p> ";

				$parseResponse = new MTOM_ResponseReader($response);
				$resultat_tmp = $parseResponse->soapResponse;
				$soap_result = $resultat_tmp["data"];
				$error_code = explode("<id>", $soap_result);
				$error_code = explode("</id>", $error_code[1]);
				//print "<p>errorcode ".print_r($error_code,true)."</p>";
				//- Parse Web Service Response
				//+ Error handling and label saving
				if ($error_code[0]=="0")
				{
					//+ Write result to file <parcel number>.extension in defined folder (ex: ./labels/6A12091920617.zpl)
					$resultat_tmp = $parseResponse->soapResponse;
					$soap_result = $resultat_tmp["data"];
					$resultat_tmp = $parseResponse->attachments;
					$label_content = $resultat_tmp[0];
					$my_datas = $label_content["data"];
					//Save the label
					$my_extension_tmp = $requestParameter["outputFormat"]["outputPrintingType"];
					$my_extension = strtolower(substr($my_extension_tmp,0,3));
					$pieces = explode("<parcelNumber>", $soap_result);
					$pieces = explode("</parcelNumber>", $pieces[1]);
					$this->trackingnumber= $pieces[0]; //Extract the parcel number
					print "<p>parcelnumber ".$this->trackingnumber."</p>";
					$my_file_name=$this->labelDir.$this->labelFile;
					$my_file = fopen($my_file_name, 'a');
					if (fputs($my_file, $my_datas))
					{ //Save the label in defined folder
						fclose($my_file);
						//$this->errors[] = "fichier ".$my_file_name." ok <br>";
						//return -1;
					}
					else
					{
						$this->errors[] = "erreur ecriture etiquette <br>";
						return -1;
					}
				}
				else
				{ //Display errors if exist
					print "<p>soapresult ".print_r($soap_result, true)."</p>";
					$error_message = explode("<messageContent>", $soap_result);
					$error_message = explode("</messageContent>", $error_message[1]);
					//echo 'error code : '.$error_code[0]."\n";
					//echo 'error message : '.$error_message[0]."\n";
					$this->errors[] = " erreur ".$error_code[0]." : ".$error_message[0];
					return -1;
				}

//				$response = $wsclient->generateLabel($lettre);
			//print "<p><pre>".print_r($response, true)."</pre></p> ";

/*				//$response = $wsclient->getLetterColissimo($lettre);
				if(!empty($response->getLetterColissimoReturn->PdfUrl))
				{
					$dir_osencoded=dol_osencode($this->labelDir);
					dol_syslog(get_class($this)."::".__FUNCTION__.':: fichier '.print_r($response,true), LOG_DEBUG);
					if (file_exists($dir_osencoded))
					{
						// Cree fichier en taille origine
						$content = @file_get_contents($response->getLetterColissimoReturn->PdfUrl);
						if( $content)
						{
							$im = fopen(dol_osencode($this->labelDir.$this->labelFile),'wb');
							fwrite($im, $content);
							fclose($im);
						}
					}

					$this->trackingnumber=$response->getLetterColissimoReturn->parcelNumber;

			//		print "<p> réponse <pre>".print_r($response,true).'</pre></p>';
				}
				else
				{
					$this->errors[]=$response->getLetterColissimoReturn->error;
					dol_syslog(__METHOD__."::erreurs ".print_r($response->getLetterColissimoReturn, true), LOG_ERR);
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
*/
			}
			catch(SoapFault $f)
			{

			//	print "<p>FAULT <pre>".print_r($f->faultstring, true)."</pre></p>";
			//	print "<p>FAULT DEBUG<pre>".print_r($f->xdebug_message, true)."</pre></p>";

				dol_syslog(get_class($this)."::".__FUNCTION__."Soapfault ".print_r($f->xdebug_message, true) ." string ".$f->faultstring, LOG_ERR);
				$this->errors[] = $f->faultstring;
				return -1;
			}
			catch(Exception $e)
			{

				//	print "<p>FAULT <pre>".print_r($f->faultstring, true)."</pre></p>";
				//	print "<p>FAULT DEBUG<pre>".print_r($f->xdebug_message, true)."</pre></p>";

				dol_syslog(get_class($this)."::".__FUNCTION__."Exception ".print_r($e, true) ." string ".$e->getMessage(), LOG_ERR);
				$this->errors[] = $e->getMessage();
				return -1;
			}
		}
		else return -1;

		return 0;
	}

	/**
	 *
	 * return pth of label
	 * @param int $expedid
	 */
	function get_label($exped)
	{
		global $conf, $langs;

		$this->labelDir = $conf->expedition->dir_output . "/sending/". $exped->ref ;
		dol_mkdir($this->labelDir);
		dol_syslog(__METHOD__.":: dir : " . $this->labelDir, LOG_DEBUG);

		$this->labelFile = "/COLSUI_".$exped->ref;
		$this->labelFile .= ".pdf";
		dol_syslog(__METHOD__.":: file : " . $this->labelFile, LOG_DEBUG);
	}

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
		//print "<p>retour<pre>".print_r($ret, true)."</pre></p>";
		return $ret;
	}


	/**
	 * Convert array to Xml
	 * @param unknown $soapRequest
	 * @param unknown $soapRequestXml
	 */
	function array_to_xml($soapRequest, $soapRequestXml)
	{
		foreach($soapRequest as $key => $value)
		{
			if(is_array($value))
			{
				if(!is_numeric($key))
				{
					$subnode = $soapRequestXml->addChild("$key");
					$this->array_to_xml($value, $subnode);
				}
				else
				{
					$subnode = $soapRequestXml->addChild("item$key");
					$this->array_to_xml($value, $subnode);
				}
			}
			else
			{
				$soapRequestXml->addChild("$key",htmlspecialchars("$value"));
			}
		}
	}
}


// classe lecture résultat colissimo
class MTOM_ResponseReader
{
	const CONTENT_TYPE = 'Content-Type: application/xop+xml;';
	const UUID = '/--uuid:/'; //This is the separator of each part of the response
	const CONTENT = 'Content-';
	public $attachments = array ();
	public $soapResponse = array ();
	public $uuid = null;

	public function __construct($response)
	{
		if (strpos ( $response, self::CONTENT_TYPE ) !== FALSE)
		{
			$this->parseResponse( $response );
		}
		else
		{
			dol_syslog(__METHOD__.":: reponse ".print_r($response, true), LOG_ERR);
			throw new Exception ( 'This response is not : ' . CONTENT_TYPE );
		}
	}

	private function parseResponse($response)
	{
		$content = array ();
		$matches = array ();
		preg_match_all ( self::UUID, $response, $matches, PREG_OFFSET_CAPTURE );
		for($i = 0; $i < count ( $matches [0] ) -1; $i ++)
		{
			if ($i + 1 < count ( $matches [0] ))
			{
				$content [$i] = substr ( $response, $matches [0] [$i] [1],
					$matches [0] [$i + 1] [1] - $matches [0] [$i] [1] );
			}
			else
			{
				$content [$i] = substr ( $response, $matches [0] [$i] [1],
					strlen ( $response ) );
			}
		}
		foreach ( $content as $part )
		{
			if($this->uuid == null)
			{
				$uuidStart = 0;
				$uuidEnd = 0;
				$uuidStart = strpos($part, self::UUID, 	0)+strlen(self::UUID);
				$uuidEnd = strpos($part, "\r\n", $uuidStart);
				$this->uuid = substr($part, $uuidStart, $uuidEnd-$uuidStart);
			}
			$header = $this->extractHeader($part);
			if(count($header) > 0)
			{
				if(strpos($header['Content-Type'], 'type="text/xml"')!==FALSE)
				{
					$this->soapResponse['header'] = $header;
					$this->soapResponse['data'] = trim(substr($part,
					$header['offsetEnd']));
				}
				else
				{
					$attachment['header'] = $header;
					$attachment['data'] = trim(substr($part, $header['offsetEnd']));
					array_push($this->attachments, $attachment);
				}
			}
		}
	}

	/**
	* Exclude the header from the Web Service response
	* @param string $part
	* @return array $header
	*/
	private function extractHeader($part)
	{
		$header = array();
		$headerLineStart = strpos($part, self::CONTENT, 0);
		$endLine = 0;
		while($headerLineStart !== FALSE)
		{
			$header['offsetStart'] = $headerLineStart;
			$endLine = strpos($part, "\r\n", $headerLineStart);
			$headerLine = explode(': ', substr($part, $headerLineStart,
			$endLine-$headerLineStart));
			$header[$headerLine[0]] = $headerLine[1];
			$headerLineStart = strpos($part, self::CONTENT, $endLine);
		}
		$header['offsetEnd'] = $endLine;
		return $header;
	}
}
?>
