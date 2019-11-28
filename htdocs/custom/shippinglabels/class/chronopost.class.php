<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Jean Heimburger <jean@tiaris.info>
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

//dol_include_once("/logistique/includes/logistique.lib.php");
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
dol_include_once("/shippinglabels/class/shipping_class.class.php");
dol_include_once("/core/lib/date.lib.php");

class L_chronopost extends shipping_class
{
	function __construct($db, $user)
	{
		parent::__construct($db, $user);
		global $conf;
		// set webservice
	$this->endpointurl = $conf->global->CHRONOPOSTENDPOINT;
	$this->wsdl = $this->endpointurl.'?WSDL';
		$this->userid = $conf->global->CHRONOPOST_USERID;
		$this->passwd = $conf->global->CHRONOPOST_PWD;

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

	    if (empty($exped->id))
		{
		$this->error = "Erreur lecture expédition";
		$this->errors[] = "Erreur lecture expédition";
		dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
		return -1;
	    }
		dol_syslog(get_class($this)."::".__FUNCTION__."expédition ".print_r($exped, true), LOG_DEBUG);

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

	// 		dol_syslog(get_class($this)."::on se base sur la société ", LOG_DEBUG);
	// 		// adresse société
	// 		$contact = new Contact($this->db);

	// 		// 		$contact->lastname = $soc->name;
	// 		$contact->address = $soc->address;
	// 		$contact->zip = $soc->zip;
	// 		$contact->town = $soc->town;
	// 		$contact->country_code = $soc->country_code;
	// 		$contact->email = $soc->email;
	// 		$contact->state_code=$soc->state_code;
	// 		$contact->phone_pro = $soc->phone;
			$this->errors=null;
			$this->errors[] =  $langs->trans("noContactLinked");
			$this->error =  $langs->trans("noContactLinked");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
			return -1;
		}
		$errorcount=0;
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
			$this->error =  $langs->trans("lastNameNotFilled");
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
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
	// 	if(empty($contact->firstname)&&empty($contact->lastname))
	// 	{
	// 		$this->errors=null;
	// 		$this->errors[] =  $langs->trans("noContactLinked");
	// 		$this->error =  $langs->trans("noContactLinked");
	// 		dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
	// 		$errorcount++;
	// 	}
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
			$this->error=  $langs->trans("shipperZipNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		$date=$exped->date_delivery;

		if (empty($exped->trueHeight))
		{
			$this->errors[] =  $langs->trans("heightNotFilled");
			$this->error=  $langs->trans("heightNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		if (empty($exped->trueWidth))
		{
			$this->errors[] =  $langs->trans("widthNotFilled");
			$this->error=  $langs->trans("widthNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		if (empty($exped->trueDepth))
		{
			$this->errors[] =  $langs->trans("depthNotFilled");
			$this->error=  $langs->trans("depthNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

		//$date=date('d/m/Y',$date);
		$date=dol_getdate($date);
		$hour=$conf->global->CHRONOPOSTClosingTime;
		$hour2=$conf->global->CHRONOPOSTRetrievalTime;
	    $esdValue['closingDateTime']=$date['year'].'-'.$date['mon'].'-'.$date['mday'].'T'.$hour.'Z';
	    $esdValue['height']=$exped->trueHeight;
	    $esdValue['length']=$exped->trueDepth;
	    $esdValue['retrievalDateTime']=$date['year'].'-'.$date['mon'].'-'.$date['mday'].'T'.$hour2.'Z';
	    $esdValue['shipperBuildingFloor']="";
	    $esdValue['shipperCarriesCode']="";
	    $esdValue['shipperServiceDirection']="";
	    $esdValue['specificInstructions']="";
	    $esdValue['width']=$exped->trueWidth;
	    $esdValue['ltAImprimerParChronopost']=0;
	    $esdValue['nombreDePassageMaximum']=1;
		$esdValue['refEsdClient']="";

		$headerValue['accountNumber']=$conf->global->CHRONOPOSTUSERID;
		$headerValue['idEmit']="CHRFR";
		$headerValue['identWebPro']="";
		$headerValue['subAccount']=000;

		$shipperValue['shipperAdress1']=$conf->global->SHIPPERADDRESS;
		$shipperValue['shipperAdress2']="";
		$shipperValue['shipperCity']=$conf->global->SHIPPERTOWN;
		$shipperValue['shipperCivility']=$conf->global->SHIPPERCIVILITY;
		$shipperValue['shipperContactName']=$conf->global->SHIPPERATTENTION;
		$shipperValue['shipperCountry']=$conf->global->SHIPPERCOUNTRYCODE;
		$shipperValue['shipperCountryName']=$conf->global->SHIPPERCOUNTRYCODE;
		$shipperValue['shipperEmail']=$conf->global->SHIPPERMAIL;
		$shipperValue['shipperMobilePhone']=$conf->global->SHIPPERPHONE;
		$shipperValue['shipperName']=$conf->global->SHIPPERNAME;
		$shipperValue['shipperName2']="";
		$shipperValue['shipperPhone']=$conf->global->SHIPPERPHONE;
	    $shipperValue['shipperPreAlert']=0;
	    $shipperValue['shipperZipCode']=$conf->global->SHIPPERZIP;

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
			$customerValue['customerAdress2'].=$adlines3[$i].' ';
	    }

		$customerValue['customerAdress1']=$adlines2[0];
	    $customerValue['customerAdress2'];
	    $customerValue['customerCity']=$contact->town;
		//Spécifique à la configuration Synergies-Tech
	    if($contact->civility_id == 'M.')
	    $customerValue['customerCivility']='M';
	    else if($contact->civility_id=='MME')
	    {
		$customerValue['customerCivility']='E';
	    }
	    else if($contact->civility_id=='MLE')
	    {
		$customerValue['customerCivility']='L';
	    }
	    else
	    {
	    $customerValue['customerCivility']='M';
	    }

		$customerValue['customerContactName']=$contact->firstname.' '.$contact->lastname;
		$customerValue['customerCountry']=$contact->country_code;
		$customerValue['customerCountryName']=$contact->country;
		$customerValue['customerEmail']=$contact->email;
		$customerValue['customerMobilePhone']=$contact->phone_mobile;
		$customerValue['customerName'] = $soc->name;
		$customerValue['customerName2'] = $soc->name_alias;
		$customerValue['customerPhone']=$contact->phone_perso;
		$customerValue['customerPreAlert']=0;
		$customerValue['customerZipCode']=$contact->zip;
		$customerValue['printAsSender']='N';

		$recipientValue['recipientAdress1']=$customerValue['customerAdress1'];
	    $recipientValue['recipientAdress2']=$customerValue['customerAdress2'];
	    $recipientValue['recipientCity']=$contact->town;
	    $recipientValue['recipientCivility']=$contact->civility_id;
		$recipientValue['recipientContactName']=$contact->firstname.''.$contact->lastname;
		$recipientValue['recipientCountry']=$contact->country_code;
		$recipientValue['recipientCountryName']=$contact->country;
		$recipientValue['recipientEmail']=$contact->email;
		$recipientValue['recipientMobilePhone']=$contact->phone_mobile;
		$recipientValue['recipientName']=$soc->name;
		$recipientValue['recipientName2']=$soc->name_alias;
		$recipientValue['recipientPhone']=$contact->phone_perso;
		$recipientValue['recipientPreAlert']=0;
		$recipientValue['recipientZipCode']=$contact->zip;

		$a=$exped->fetchObjectLinked();
		$b=$exped->linkedObjects['commande'][0]->ref;
		$refValue['customerSkybillNumber']=$b;
		//$refValue['PCardTransactionNumber']="2222";
		//$refValue['recipientRef']="0123";
		//$refValue['shipperRef']="0134";

		//$skybillValue['bulkNumber']="1";
		$skybillValue['codCurrency']="EUR";
		$skybillValue['codValue']=0;
		$skybillValue['content1'];
		$skybillValue['content2'];
		$skybillValue['content3'];
		$skybillValue['content4'];
		$skybillValue['content5'];
		$skybillValue['customsCurrency']="EUR";
		$skybillValue['customsValue']=0;
		$skybillValue['evtCode']="DC";
		$skybillValue['insuredCurrency']="EUR";
		$skybillValue['insuredValue']=0;
		$skybillValue['masterSkybillNumber']=1;
		$skybillValue['objectType']='MAR';
		$skybillValue['portCurrency']='EUR';
		$skybillValue['portValue']="0";
		$skybillValue['productCode']="02";
		$skybillValue['service']="0";
	    $skybillValue['shipDate']=$date['year'].'-'.$date['mon'].'-'.$date['mday'].'T'.$date['hours'].':'.$date['minutes'].':0Z';
	    $skybillValue['shipHour']=$date['hours'];
		//$skybillValue['skybillRank']="1";

		$skybillValue['weight']= $this->getWeight($exped);
	    $skybillValue['weightUnit']='KGM';

	    $skybillParamsValue['duplicata'];
	    $skybillParamsValue['mode']="PDF";

	    if (empty($exped->trueWeight))
	    {
		$totalWeight=0;
		$totalWeight=calculate_weight($exped);
		$exped->trueWeight=$totalWeight;
		$exped->weight_units=0;
		$skybillValue['weight']=$exped->trueWeight;
	    }

	    $shippingWithReservationAndESDWithRefClient['esdValue']=$esdValue;
	    $shippingWithReservationAndESDWithRefClient['headerValue']=$headerValue;
	    $shippingWithReservationAndESDWithRefClient['shipperValue']=$shipperValue;
	    $shippingWithReservationAndESDWithRefClient['customerValue']=$customerValue;
	    $shippingWithReservationAndESDWithRefClient['recipientValue']=$recipientValue;
	    $shippingWithReservationAndESDWithRefClient['refValue']=$refValue;
	    $shippingWithReservationAndESDWithRefClient['skybillValue']=$skybillValue;
	    $shippingWithReservationAndESDWithRefClient['skybillParamsValue']=$skybillParamsValue;
	    $shippingWithReservationAndESDWithRefClient['password']=$conf->global->CHRONOPOSTPWD;
	    $shippingWithReservationAndESDWithRefClient['modeRetour']=2;
	    $shippingWithReservationAndESDWithRefClient['version']=2.0;

	    if (empty($exped->trueWeight))
	    {
		$totalWeight=0;
		$totalWeight=calculate_weight($exped);
			$exped->trueWeight=$totalWeight;
			$exped->weight_units=0;
	    }

	    if ($errorcount)
	    {
		return -1;
	    }
	    $request=$shippingWithReservationAndESDWithRefClient;

	    dol_syslog('STR'.print_r($request, true),LOG_DEBUG);

	    return $request;
	}

	function ws_call($exped)
	{
		global $conf,$langs;

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

		$wsclient = new soapclient($this->wsdl);
		$test=$this->set_ws_params($exped);
		dol_syslog(get_class($this)."::".__FUNCTION__."params ".print_r($lettre, true), LOG_DEBUG);
		if (is_array($test))
		{
			try
			{
				$this->get_label($exped);
				$response = $wsclient->shipping($test);
	//  			print "<p> réponse <pre>".print_r($response,true).'</pre></p>';
				$dir_osencoded=dol_osencode($this->labelDir);
				if (!empty($response->return->skybill))
				{
					if (file_exists($dir_osencoded))
					{
						// Cree fichier en taille origine
						$content = $response->return->skybill;
						if( $content)
						{
							$im = fopen(dol_osencode($this->labelDir.$this->labelFile),'wb');
							fwrite($im, $content);
							fclose($im);
						}
					}
					$this->trackingnumber=$response->return->skybillNumber;
				}
				else
				{
					$this->errors[]=$response->return->errorMessage;
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
				print_r($f->faultstring);
				return -1;
			}
		}else return -1;

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
		dol_syslog(get_class($this)."::".__FUNCTION__.":: dir : " . $dir, LOG_DEBUG);

		$this->labelFile = "/CHRONO_".$exped->ref;
		$this->labelFile .= ".pdf";
	}

}
?>
