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
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";

class L_MondialRelay extends shipping_class
{
	function __construct($db, $user)
	{
		parent::__construct($db, $user);
		global $conf;
		// set webservice
	$this->endpointurl = $conf->global->MR_ENDPOINT;
	$this->wsdl = $this->endpointurl.'?WSDL';

		$this->userid = $conf->global->MR_USERID;
		$this->passwd = $conf->global->MR_PWD;
		// Contrôles
		// TODO Initialiser le service
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
		dol_syslog(get_class($this)."::".__FUNCTION__."expédition ".print_r($exped, true), LOG_DEBUG);

		// adresse de destination
		$soc = new Societe($this->db);
		$soc->fetch($exped->socid);
		$contactid = get_shippingcontact($exped);
		if ($contactid > 0)
		{
			$contact = new Contact($this->db);
			$contact->fetch($contactid);
			dol_syslog(get_class($this).":: contact ::".print_r($contact, true), LOG_DEBUG);
			$dest=$contact->civility_id.' '.$contact->firstname.' '.$contact->lastname;
		}
		else
		{
			dol_syslog(get_class($this)."::on se base sur la société ", LOG_DEBUG);
			// adresse société
			$contact = new Contact($this->db);
			$dest=$soc->name;
	// 		$contact->lastname = $soc->name;
			$contact->address = $soc->address;
			$contact->zip = $soc->zip;
			$contact->town = $soc->town;
			$contact->country_code = $soc->country_code;
			$contact->email = $soc->email;
			$contact->state_code=$soc->state_code;
			$contact->phone_pro = $soc->phone;
		}

		if (empty($exped->trueWeight))
		{
			$totalWeight=0;
			$totalWeight=calculate_weight($exped);
			$exped->trueWeight=$totalWeight;
			$exped->weight_units=0;
		}
		$poids=0;
		if($exped->weight_units==-3)
		{
			$poids=$exped->trueWeight;
		}
		else
		{
			$from = $exped->weight_units;
			$poids=weight_convert($exped->trueWeight,$from,-3);
		}

		if ($poids < 100)
		{
			$this->errors[] =  $langs->trans("MRtropleger");
			$this->error=  $langs->trans("MRtropleger");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}

	dol_syslog(__METHOD__.':'.__LINE__.":: poids ".$poids."  truewieght ".$exped->trueWeight." units ".$exped->weight_units, LOG_DEBUG);
		$errorcount = 0;
		if (empty($conf->global->SHIPPERNAME))
		{
			$this->errors[] =  $langs->trans("shipperFirmNotFilled");
			$this->error=  $langs->trans("shipperFirmNotFilled");
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
		if (empty($contact->lastname) && $contactid > 0 )
		{
			$this->error =  $langs->trans("lastNameNotFilled");
			$this->errors[] =  $langs->trans("lastNameNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($contact->firstname) && $contactid > 0 )
		{
			$this->errors[] =  $langs->trans("firstNameNotFilled");
			$this->error =  $langs->trans("firstNameNotFilled");
			dol_syslog(get_class($this)."::".__FUNCTION__." error ".$this->error, LOG_ERR);
			$errorcount++;
		}
		if (empty($contact->email) )
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

	    dol_syslog(get_class($this).":: société ".print_r($contact, true), LOG_DEBUG);
	    $params=Array(	"Enseigne"=>$conf->global->MR_USERID,
								"ModeCol"=>$conf->global->MR_MODECOLLECTE,
								"ModeLiv"=>$conf->global->MR_MODELIVRAISON,
								"NDossier"=>$exped->ref,
								"NClient" => (empty($exped->ref_customer) ? '' : $exped->ref_customer),
								"Expe_Langage"=>$conf->global->SHIPPERCOUNTRYCODE,
								"Expe_Ad1"=>$conf->global->SHIPPERNAME,
								"Expe_Ad2"=>$conf->global->SHIPPERCIVILITY." ".$conf->global->SHIPPERATTENTION." ".$conf->global->SHIPPERATTENTION2,
								"Expe_Ad3"=>stripAccents($conf->global->SHIPPERADDRESS),
								"Expe_Ville"=>stripAccents($conf->global->SHIPPERTOWN),
								"Expe_CP"=>$conf->global->SHIPPERZIP,
								"Expe_Pays"=>$conf->global->SHIPPERCOUNTRYCODE,
								"Expe_Tel1"=>$conf->global->SHIPPERPHONE,
								"Expe_Mail"=>$conf->global->SHIPPERMAIL,
								"Dest_Langage"=>$contact->country_code,
								"Dest_Ad1"=>stripAccents($dest),
								"Dest_Ad3"=>stripAccents($adlines2[0]),
								"Dest_Ad4"=>'',
								"Dest_Ville"=>stripAccents($contact->town),
								"Dest_CP"=>$contact->zip,
								"Dest_Pays"=>$contact->country_code,
								"Dest_Tel1"=>$contact->phone_mobile,
								"Dest_Tel2"=>(empty($contact->phone_perso)?'':$contact->phone_perso),
								"Dest_Mail"=>(empty($contact->email)? '': $contact->email),
								"Poids"=>$poids,
								"NbColis"=>"1",
								"CRT_Valeur"=>"0",
								"COL_Rel_Pays"=>"FR",
									);

	    // cas d'un point relais
	    if ($conf->ecommerce->enabled)
	    {
		$exped->fetchObjectLinked();
			foreach($exped->linkedObjectsIds["commande"] as $id) $doli_order = $id;
		dol_include_once("/ecommerce/class/E_order.class.php");
		$eorder = new E_order($db);
		$shippiniginfo = $eorder->get_shippinginfos4order($doli_order);
		$xml = simplexml_load_string($shippiniginfo);
		$params["LIV_Rel_Pays"] = (string)$xml->MR_Selected_Pays;
		$params["LIV_Rel"] = (string)$xml->MR_Selected_num;

	    }
	    for($i=1;$i<count($adlines3);$i++)
	    {
			$params['Dest_Ad4'].=  stripAccents($adlines3[$i]).' ';
	    }
	    $params['Dest_Ad4'] = trim($params['Dest_Ad4']);
	    $code = implode("", $params);
	    $code .=$conf->global->MR_PWD;
	    $params["Security"] = strtoupper(md5($code));
	    $request=$params;
	    if ($errorcount)
	    {
		return -1;
	    }

	    return $request;
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

		$wsclient = new soapclient($this->wsdl);
		$test=$this->set_ws_params($exped);
		dol_syslog(get_class($this)."::".__FUNCTION__."params ".print_r($test, true), LOG_DEBUG);
		if (is_array($test))
		{
			try
			{
				$this->get_label($exped);
				$dir_osencoded=dol_osencode($dir);
				$response = $wsclient->WSI2_CreationEtiquette($test);
				dol_syslog(get_class($this)."::".__FUNCTION__."result ".print_r($response, true), LOG_DEBUG);
				if (!empty($response->WSI2_CreationEtiquetteResult->URL_Etiquette))
				{
					$a="http://www.mondialrelay.com";
					$a.=$response->WSI2_CreationEtiquetteResult->URL_Etiquette;
					if (!empty($conf->global->MR_FORMATLABEL) ) $a =str_replace("format=A4", "format=".$conf->global->MR_FORMATLABEL, $a);
					$dir_osencoded=dol_osencode($this->labelDir);
					if (file_exists($dir_osencoded))
					{
						// Cree fichier en taille origine
						$content = @file_get_contents($a);
						if( $content)
						{
							$im = fopen(dol_osencode($this->labelDir.$this->labelFile),'wb');
							fwrite($im, $content);
							fclose($im);
						}
					}
					$this->trackingnumber=$response->WSI2_CreationEtiquetteResult->ExpeditionNum;
				}
				else
				{
					$num=$response->WSI2_CreationEtiquetteResult->STAT;
					$this->errors[]=$langs->trans("MR".$num);
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

		$this->labelFile = "/MR_".$exped->ref;
		$this->labelFile .= ".pdf";
	}

}
?>
