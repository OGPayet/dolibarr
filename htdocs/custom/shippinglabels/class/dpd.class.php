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
 *   	\file       htdocs/logistique/class/dpd.class.php
 *		\ingroup    logistique
 *		\brief      dpd  functions
 *		\author		Cédric Scheyder
 *		\remarks	Put here some comments
 */

//dol_include_once("/logistique/includes/logistique.lib.php");
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
dol_include_once("/shippinglabels/class/shipping_class.class.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


class L_dpd extends shipping_class
{
	var $access;
	var $shipnumber;
	var $centernumber;

    protected $label = null;

	function __construct($db, $user)
	{
		parent::__construct($db, $user);
		global $conf;

		// set webservice
		$this->mode = array(
			'encoding'=>'UTF-8'
		);
	$this->endpointurl = $conf->global->LOGISTIK_DPD_ENDPOINT;

	$this->access = $conf->global->LOGISTIK_DPD_ACCESS;
		$this->userid = $conf->global->LOGISTIK_DPD_USERID;
		$this->passwd = $conf->global->LOGISTIK_DPD_PWD;
		$this->shipnumber = $conf->global->LOGISTIK_DPD_SHIPNUMBER;
		$this->centernumber = $conf->global->LOGISTIK_DPD_CENTERNUMBER;
		$this->wsdl = $conf->global->LOGISTIK_DPD_WSDL;
		$this->predict = $conf->global->LOGISTIK_DPD_PREDICT;
		$this->prime = $conf->global->LOGISTIK_DPD_PRIME;
		// Contrôles
	}

	/**
	 *
	 * Load dpd parameters for specified shipping method
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

		//create soap request
		// Sender
		$address2 = array(
			'name'=>$conf->global->SHIPPERNAME,
			'countryPrefix'=>$conf->global->SHIPPERCOUNTRYCODE,
			'zipCode'=>$conf->global->SHIPPERZIP,
			'city'=>$conf->global->SHIPPERTOWN,
		'street'=>$conf->global->SHIPPERADDRESS,
			'phoneNumber'=>$conf->global->SHIPPERPHONE,
			'faxNumber'=>'',
			'geoX'=>'',
			'geoY'=>'',
		);

	    $soc = new Societe($this->db);
	    $soc->fetch($exped->socid);

	    // adresse de destination
	    $contactid = get_shippingcontact($exped);
	    if ($contactid > 0)
	    {
		$contact = new Contact($this->db);
		$contact->fetch($contactid);
	    }
	    else
	    {
		// adresse société
		$contact = new Contact($this->db);

		$contact->lastname = $soc->name;
		$contact->address = $soc->address;
		$contact->zip = $soc->zip;
		$contact->town = $soc->town;
		$contact->country_code = $soc->country_code;
	    }

	    $contact->phone_pro = $this->get_phone($contact, $soc, $conf->global->LOGISTIK_DPD_SHIPPERPHONE);

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

	    // Recipient
		$array['name1'] = $soc->name;
        $array['name2'] = $contact->lastname. ' '. $contact->firstname;


		// Address
        $adlines = $this->format_address($contact->address);

	   // Adresse destinataire
		$address1 = array(
			'name'=>$contact->lastname. ' '. $contact->firstname,
			'countryPrefix'=>$contact->country_code,
			'zipCode'=>$contact->zip,
			'city'=>$contact->town,
			'street'=>$adlines[0],  //
			'phoneNumber'=>$contact->phone_pro,  // TODO get phone
			'faxNumber'=>'',
			'geoX'=>'',
			'geoY'=>'',

		);
	   // Compléments d'adresse
	    $AddressInfo= array(
			'name2'=> (count($adlines) > 1)?$adlines[1]:'',//'name2',
			'name3'=>(count($adlines) > 2)?$adlines[2]:'',
			'name4'=>(count($adlines) > 3)?$adlines[3]:'',
			'vinfo1'=>($this->prime)?"PRIME URGENT" : '',
			'vinfo2'=>'',
		);

		$label=array(
			'type'=>'PDF',
		);

		$shipment_request = array(
			'receiveraddress'=>$address1,
			'shipperaddress'=>$address2,
			'receiverinfo'=>$AddressInfo,
			'customer_countrycode'=>250,
			// Code agence et compte DPD France
			'customer_centernumber'=>$this->centernumber,
			'customer_number'=>$this->shipnumber,
            'referencenumber'=>$exped->ref,
			'weight'=> $this->getWeight($exped),
			'labelType'=>$label,
	    );

		if (empty($shipment_request["weight"]))
		{
			$this->errors[] =  $langs->trans("MR20");
			$this->error =  $langs->trans("MR20");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount ++;
		}

		// service predict
		if (!empty($this->predict))
		{
			if (!empty($contact->phone_mobile) && !empty($contact->email))
			{
				$predict = array("contact" => array(
											"sms"=> $contact->phone_mobile,
											"email"=>$contact->email,
											"type"=>'Predict'
										)
								);
				$shipment_request["services"] = $predict;
			}
			else dol_syslog(__METHOD__.":: no mobilphone or email no predict ", LOG_WARNING);
		}

	    if ($errorcount) return -1;

	    return $shipment_request;
	}

	function saveLabelFile($label)
	{
		global $conf, $langs;

		$file = $this->labelDir.$this->labelFile;

		$f = fopen($file.".pdf","w");
		fwrite($f, $label);
		fclose($f);
	}

	function ws_call($exped)
	{
		global $conf, $langs;

		if (empty($exped))
		{
			$this->errors[] =  "Shipping is empty";
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		}

		if (empty($this->wsdl))
		{
			$this->errors[] =  "Shipping method not configured";
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			return -1;
		}

		$shipment_request = $this->set_ws_params($exped);
		if (is_array($shipment_request))
		{
			try
			{
				$client = new SoapClient($this->wsdl , $this->mode);
				$auth=array(
				'userid'=>$this->userid,
				'password'=>$this->passwd,
				);
				// Header SOAP
				$header = new SOAPHeader($this->endpointurl, 'UserCredentials', $auth);
	            $client->__setSoapHeaders($header);
				$response = $client->CreateShipmentWithLabels(array('request'=>$shipment_request));

				$result = $response->CreateShipmentWithLabelsResult->shipments->Shipment;
				if( is_array($result))
				{
					foreach($result as $s)
					{
						// var_dump ($s->centernumber);
						// echo '<br/>';
						// var_dump ($s->parcelnumber);
						// echo '<br/>';
						// var_dump ($s->type);
					}
				}
				else
				{
					$this->trackingnumber = "250".sprintf("%'.03d\n",$this->centernumber).$result->parcelnumber;
				}

				$arResultLabel = $response->CreateShipmentWithLabelsResult->labels->Label;
				foreach($arResultLabel as $l)
				{
					// On n'imprime que l'étiquette (voir pour la preuve de dépot...)
					if ($l->type=="EPRINT")
					{
						$f = fopen($this->labelDir.$this->labelFile, "w+");
						fwrite($f, $l->label);
						fclose($f);
					}
				}

			}
			catch(Exception $ex)
			{
				$this->errors[] = $ex->faultstring;
				$errormsg = $ex->detail->Errors->ErrorDetail->PrimaryErrorCode ;
				$this->error =  "Soap fault : ".$errormsg->Code. " : " .$errormsg->Description;
				dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error. " fault ".print_r($ex, true) , LOG_ERR);
				dol_syslog(get_class($this)."::".__FUNCTION__." XML : ".$client->__getLastRequest(), LOG_ERR);
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

		$this->labelFile = "/DPD_".$exped->ref;
		$this->labelFile .= ".pdf";
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

	/**
	 *
	 * returns a valid phone number
	 * 1. phone from contact
	 * 2. phone from sustomer
	 * 3. phone from shipper
	 * @param object $contact
	 * @param object $soc
	 */
/*	function get_phone($contact, $soc)
	{
		global $conf, $mysoc;

		$returned_phone = '';
		dol_syslog(get_class($this)."::".__FUNCTION__."téléphones  ". $contact->phone_pro. " , ".$soc->phone , LOG_DEBUG);
		if (!empty($contact->phone_pro)) return $contact->phone_pro;
// TODO autres n° de téléphones ?
dol_syslog(get_class($this)."::".__FUNCTION__."contact ". $contact->phone_pro , LOG_DEBUG);
		if (!empty($soc->phone)) return $soc->phone;
dol_syslog(get_class($this)."::".__FUNCTION__."société ".$soc->phone , LOG_DEBUG);
		if (!empty($conf->global->LOGISTIK_DPD_SHIPPERPHONE)) return $conf->global->LOGISTIK_DPD_SHIPPERPHONE;
		dol_syslog(get_class($this)."::".__FUNCTION__."conf ". $conf->global->LOGISTIK_DPD_SHIPPERPHONE, LOG_DEBUG);
		if (!empty($mysoc->phone)) return $mysoc->phone;
		dol_syslog(get_class($this)."::".__FUNCTION__."mysoc ". $mysoc->phone , LOG_DEBUG);
		return $returned_phone;
	}
*/
}
?>
