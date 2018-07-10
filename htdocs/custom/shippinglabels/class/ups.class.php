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

//dol_include_once("/logistique/includes/logistique.lib.php");
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
dol_include_once("/shippinglabels/class/shipping_class.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

class L_ups extends shipping_class
{
	var $access;
	var $shipnumber;

	function __construct($db, $user)
	{
		global $conf;
		parent::__construct($db, $user);

		// set webservice
		$this->wsdl = dirname(__FILE__).'/../lib/UPS/Ship.wsdl';
		$this->mode = array(
		'soap_version' => 'SOAP_1_1',  // use soap 1.1 client
		'trace' => 1  // en prod = 0
		);
	$this->endpointurl = $conf->global->LOGISTIK_UPS_ENDPOINT;

	$this->access = $conf->global->LOGISTIK_UPS_ACCESS;
		$this->userid = $conf->global->LOGISTIK_UPS_USERID;
		$this->passwd = $conf->global->LOGISTIK_UPS_PWD;
		$this->shipnumber = $conf->global->LOGISTIK_UPS_SHIPNUMBER;

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
		$this->get_label($exped);

		dol_syslog(get_class($this)."::".__FUNCTION__."expédition ".print_r($exped, true), LOG_DEBUG);
		//create soap request
	    $requestoption['RequestOption'] = 'nonvalidate';
	    $request['Request'] = $requestoption;

	    $shipment['Description'] = $conf->global->SHIPPERDESC;
	    $shipper['Name'] = $conf->global->SHIPPERNAME;
	    $shipper['AttentionName'] = $conf->global->SHIPPERATTENTION.' '.$conf->global->SHIPPERATTENTION2;
	    $shipper['TaxIdentificationNumber'] = $conf->global->LOGISTIK_UPS_TAXNUMBER;
	    $shipper['ShipperNumber'] = $this->shipnumber;
	    $address['AddressLine'] = $conf->global->SHIPPERADDRESS;
	    $address['City'] = $conf->global->SHIPPERTOWN;
	    $address['StateProvinceCode'] = $conf->global->SHIPPERSTATE;
	    $address['PostalCode'] = $conf->global->SHIPPERZIP;
	    $address['CountryCode'] = $conf->global->SHIPPERCOUNTRYCODE;
	    $shipper['Address'] = $address;
	    $phone['Number'] = $conf->global->SHIPPERPHONE;
	    $phone['Extension'] = $conf->global->SHIPPERPHONEXT;
	    $shipper['Phone'] = $phone;
	    $shipper['EMailAddress'] = $conf->global->SHIPPERMAIL;
	    $shipment['Shipper'] = $shipper;
	    dol_syslog(__FUNCTION__.":: ".$conf->global->SHIPPERMAIL, LOG_DEBUG);
		dol_syslog(__FUNCTION__.":: shipper ".print_r($shipper, true), LOG_DEBUG);
	    $soc = new Societe($this->db);
	    $soc->fetch($exped->socid);

	    // adresse de destination
	    $contactid = get_shippingcontact($exped);
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
		//$contact->phone_pro = $soc->phone;
	    }
	    dol_syslog(get_class($this).":: société ".print_r($contact, true), LOG_DEBUG);
		// tests à ajouter pour éviter erreurs SOAP

	    $contact->phone_pro = $this->get_phone($contact, $soc);
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


	    $shipto['Name'] = $soc->name . ' ' . $soc->name_alias; // lastname
	    $shipto['AttentionName'] =  $contact->lastname. ' '. $contact->firstname; //

	    $adlines2 = $this->format_address($contact->address);
	    dol_syslog(__FUNCTION__.":: adresse calculée ".print_r($adlines2, true), LOG_DEBUG);

	    $addressTo['AddressLine'] =  $adlines2;
	    $addressTo['City'] = $contact->town;
	    $addressTo['StateProvinceCode'] = '';
	    $addressTo['PostalCode'] = $contact->zip;
	    $addressTo['CountryCode'] = $contact->country_code; //$contact->country_code;
	    $phone2['Number'] = $contact->phone_pro ;
	    $shipto['Address'] = $addressTo;
	    $shipto['Phone'] = $phone2;
	    $shipto['EMailAddress'] = empty($contact->email)? $conf->global->LOGISTIK_UPS_SHIPPERMAIL:$contact->email;
	    dol_syslog(__FUNCTION__.":: shipto ".print_r($shipto, true).' email '.$contact->email, LOG_DEBUG);
	    $shipment['ShipTo'] = $shipto;

	    $shipfrom['Name'] = $mysoc->name;
	    $shipfrom['AttentionName'] = '1160b_74';
	    $addressFrom['AddressLine'] = $mysoc->address;
	    $addressFrom['City'] = $mysoc->town;
	    $addressFrom['StateProvinceCode'] = '';
	    $addressFrom['PostalCode'] = $mysoc->zip;
	    $addressFrom['CountryCode'] = $mysoc->country_code;
	    $phone3['Number'] = $mysoc->phone;
	    $shipfrom['Address'] = $addressFrom;
	    $shipfrom['Phone'] = $phone3;
	    $shipment['ShipFrom'] = $shipfrom;
		dol_syslog(__FUNCTION__.":: shipfrom ".print_r($shipfrom, true), LOG_DEBUG);
	    $shipmentcharge['Type'] = '01'; // TODO Param
		/*	TODO option pour payer par CB
		$creditcard['Type'] = '06';
	    $creditcard['Number'] = '4716995287640625';
	    $creditcard['SecurityCode'] = '864';
	    $creditcard['ExpirationDate'] = '12/2013';
	    $creditCardAddress['AddressLine'] = '2010 warsaw road';
	    $creditCardAddress['City'] = 'Roswell';
	    $creditCardAddress['StateProvinceCode'] = 'GA';
	    $creditCardAddress['PostalCode'] = '30076';
	    $creditCardAddress['CountryCode'] = 'US';
	    $creditcard['Address'] = $creditCardAddress;
		//  $billshipper['CreditCard'] = $creditcard; */
		$billshipper['AccountNumber'] = $this->shipnumber; // paiment sur compte
	    $shipmentcharge['BillShipper'] = $billshipper;
	    $paymentinformation['ShipmentCharge'] = $shipmentcharge;
	    $shipment['PaymentInformation'] = $paymentinformation;

	    $service['Code'] = $conf->global->LOGISTIK_UPS_SERVICECODE;
	    $service['Description'] = 'Standard';
	    $shipment['Service'] = $service;

	    $package['Description'] = '';
	    $packaging['Code'] = $conf->global->LOGISTIK_UPS_PACKAGINGCODE;
	    $packaging['Description'] = $exped->listmeths[0]["description"]; // A voir
	    $package['Packaging'] = $packaging;
	    $unit['Code'] = 'CM'; // TODO param
	    $unit['Description'] = 'Centimètre'; // TODO param
	    $dimensions['UnitOfMeasurement'] = $unit;
		if (isset($exped->trueDepth) && $exped->trueDepth<>0)
		{
			$dimensions['Length'] = $exped->trueDepth;
		}
		if (isset($exped->trueWidth) && $exped->trueWidth<>0)
		{
			$dimensions['Width'] = $exped->trueWidth;
		}
		if (isset($exped->trueHeight) && $exped->trueHeight<>0)
		{
			$dimensions['Height'] = $exped->trueHeight;
		}
		if (($exped->trueHeight+$exped->trueWidth+$exped->trueDepth)>0)
		{
			$package['Dimensions'] = $dimensions;
		}
	    $unit2['Code'] = 'KGS';
	    $unit2['Description'] = 'Kilogrammes';
	    $packageweight['UnitOfMeasurement'] = $unit2;
	    $packageweight['Weight'] = $this->getWeight($exped);

	    if (empty($packageweight['Weight']))
	    {
		$totalWeight=0;

		$totalWeight=$this->getWeight($exped);
			$packageweight['Weight']=$totalWeight;
	    }



	    $package['PackageWeight'] = $packageweight;
	    $shipment['Package'] = $package;
		dol_syslog(get_class($this)."::".__FUNCTION__.print_r($package, true), LOG_DEBUG);
	    $labelimageformat['Code'] = 'GIF'; // TODO param
	    $labelimageformat['Description'] = 'GIF';
	    $labelspecification['LabelImageFormat'] = $labelimageformat;
	    $labelspecification['HTTPUserAgent'] = 'Mozilla/4.5';
	    $shipment['LabelSpecification'] = $labelspecification;
	    $request['Shipment'] = $shipment;

	    dol_syslog(get_class($this)."::".__FUNCTION__."  ".print_r($request, true), LOG_DEBUG);

	    /*
	    echo "Request.......\n";
		print_r($request);
	echo "\n\n";
		*/

	    if ($errorcount) return -1;

	    $exped->trueWeight=$packageweight['Weight'];

	    return $request;

	}

	function saveLabelFile($Shipres)
	{
		global $conf, $langs;

		$package = $Shipres->PackageResults;
		$file = $this->labelDir.$this->labelFile;
		$labelimage = $package->ShippingLabel->GraphicImage;


		$labelimage = base64_decode($labelimage);
		$img = imagecreatefromstring ($labelimage);

		dol_syslog(get_class($this).":Package:".__FUNCTION__.print_r($package, true), LOG_DEBUG);

		switch($conf->global->LOGISTIK_UPS_SHIPPERFORMAT)
		{
			case "GIF":
				if (imagegif($img, $file.".gif"))
					dol_syslog(get_class($this)."::".__FUNCTION__."Image créée"." || $file", LOG_DEBUG);
				else
					dol_syslog(get_class($this)."::".__FUNCTION__."Erreur création image", LOG_ERR);
				break;
			case "PDF":
				$img = imagerotate($img, -90, 0);
				if (imagegif($img, $file.".gif"))
					dol_syslog(get_class($this)."::".__FUNCTION__."Image créée"." || $file.gif", LOG_DEBUG);
				else
					dol_syslog(get_class($this)."::".__FUNCTION__."Erreur création image", LOG_ERR);
				$pdf=pdf_getInstance();

				$pdf->Open();
				$height=pdf_getHeightForLogo($file.".gif");
				dol_syslog(get_class($this)."::".__FUNCTION__."height for logo: $height", LOG_ERR);
				$pdf->AddPage();
				$pdf->Image($file.".gif", 10, 10, 0, 0);    // width=0 (auto)
				$pdf->Close();

				$pdf->Output($file,'F');
				unlink($file.".gif");
				break;
			default:
		}
	}

	function ws_call($exped)
	{
		global $conf, $langs;

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

		try
		{
			$client = new SoapClient($this->wsdl , $this->mode);
			$client->__setLocation($this->endpointurl);

			//create soap header
		    $usernameToken['Username'] = $this->userid;
		    $usernameToken['Password'] = $this->passwd;
		    $serviceAccessLicense['AccessLicenseNumber'] = $this->access;
		    $upss['UsernameToken'] = $usernameToken;
		    $upss['ServiceAccessToken'] = $serviceAccessLicense;

		    $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0','UPSSecurity',$upss);
		    $client->__setSoapHeaders($header);

		    $params = $this->set_ws_params($exped);
		    if (! is_array($params) )
		    {
			$this->error =  "erreur de paramètres ".$this->error;
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		    }

		    $resp = $client->__soapCall('ProcessShipment',array($params));

		    if (!empty($resp->ShipmentResults->PackageResults->ShippingLabel->GraphicImage))
		    {
				dol_syslog(get_class($this)."::".__FUNCTION__.":: Response Status: " . $resp->Response->ResponseStatus->Description, LOG_DEBUG);
				// TODO error test
				//get status

				//TODO debug infos
				$outputFileName = $conf->expedition->dir_output . "/XOLTResult.xml";
				$outputresult = $conf->expedition->dir_output . "/XOLTResponse.xml";

				$dir = $conf->expedition->dir_output . "/sending/" .$exped->ref ;

				//save soap request and response to file
				$fw = fopen($outputFileName , 'w');
				fwrite($fw , "Request: \n" . $client->__getLastRequest() . "\n");
				fwrite($fw , "Response: \n" . $client->__getLastResponse() . "\n");
				fclose($fw);

				$fw = fopen($outputresult , 'w');
				fwrite($fw , print_r($resp, true));
				fclose($fw);

				// num tracking
				$Shipres = $resp->ShipmentResults;
				$this->trackingnumber = $Shipres->ShipmentIdentificationNumber;

				dol_syslog(get_class($this)."::".__FUNCTION__.":: fichier ".$exped->pdf_filename, LOG_DEBUG);
				$this->saveLabelFile($Shipres);
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
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		    }
		}
		catch(Exception $ex)
		{
		//	dol_syslog(get_class($this)."::".__FUNCTION__."Soapfault ".print_r($f, true). " string ".$f->faultstring, LOG_ERR);
			$this->errors[] = $ex->faultstring;
			$errormsg = $ex->detail->Errors->ErrorDetail->PrimaryErrorCode ;
			$this->errors[] =  "[".$errormsg->Code. "] : " .$errormsg->Description;
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".print_r($this->errors, true) , LOG_ERR);
			return -1;
		}
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

		$this->labelFile = "/UPS_".$exped->ref;
		switch($conf->global->LOGISTIK_UPS_SHIPPERFORMAT)
		{
			case "JPG":
				$this->labelFile .= ".gif";
				break;
			case "PDF":
				$this->labelFile .= ".pdf";
				break;
			default:
		}
	}


	/**
	 * renvoie l'adresse en forme de tableau avec la dernière ligne en premier
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
		//print "<p>retour<pre>".print_r($ret, true)."</pre></p>";
		return $ret;
	}
}
?>
