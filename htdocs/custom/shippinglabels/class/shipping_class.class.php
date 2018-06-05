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
 *   	\file       htdocs/logistique/class/shipping_class.class.php
 *		\ingroup    logistique
 *		\brief      ups  functions
 *		\author		Cédric Scheyder
 *		\remarks	Put here some comments
 */

//dol_include_once("/logistique/includes/logistique.lib.php");
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
abstract class shipping_class
{
	var $db;
	var $user;

	var $wsdl;
	var $mode; // soap mode
	var $userid;
	var $passwd;


	var $trackingnumber;
	var $labelname; // adresse complète de l'image étiquette
	var $endpointurl;

	var $error; //error message
	var $errors = array();

	var $labelFile;
	var $labelDir;

	var $labelformat;
	var $contrat; // pour indiquer les divers types de contrats

	function __construct($db, $user)
	{
		global $conf;
		$this->db = $db;
		$this->user = $user;
		$this->trackingnumber="";
		$this->labelname="";
		$this->endpointurl="";
		$this->labelformat="";
		$this->contrat = '';
	}

	abstract function set_ws_params($exped);

	abstract function ws_call($exped);

	abstract function get_label($exped);


	function getWeight(Expedition $exped)
	{
		global $conf;
		if ($exped->trueWeight > 0 )
		{
			return weight_convert($exped->trueWeight, $exped->weight_units, 0) ;
		}
		if (version_compare(DOL_VERSION, "4.0", ">=" ))
		{
			$tmparray=$exped->getTotalWeightVolume();
			$poids = $tmparray['weight']; // poids en kg
		}
		else
		{
			$poids = (empty($exped->trueWeight))?$exped->weight:$exped->trueWeight;
		    // parfois le poids n'est pas renseigné, on le calcule
		   // utiliser funciton commune de tia_orders
		   if (empty($poids)) $poids = calculate_weight($exped);
		    $from = $exped->weight_units;
		    $poids =  weight_convert($poids, $from, 0) ; // convertir en kg
		}
		return $poids;
	}

	// renvoyer un n° de téléphone valide
	function get_phone($contact, $soc, $default ='' )
	{
		global $conf, $mysoc;

		$returned_phone = '';
		dol_syslog(get_class($this)."::".__FUNCTION__."téléphones  ". $contact->phone_pro. " , ".$soc->phone , LOG_DEBUG);
		if (!empty($contact->phone_pro))
		{
			$returned_phone =  $contact->phone_pro;
			dol_syslog(get_class($this)."::".__FUNCTION__."contact ". $contact->phone_pro , LOG_DEBUG);
		}
// TODO autres n° de téléphones ?

		elseif (!empty($soc->phone))
		{
			$returned_phone = $soc->phone;
			dol_syslog(get_class($this)."::".__FUNCTION__."société ".$soc->phone , LOG_DEBUG);
		}
		elseif (!empty($default) )
		{
			$returned_phone = $default;
			dol_syslog(get_class($this)."::".__FUNCTION__."conf ". $default, LOG_DEBUG);
		}
		elseif (!empty($mysoc->phone))
		{
			$returned_phone = $mysoc->phone;
			dol_syslog(get_class($this)."::".__FUNCTION__."mysoc ". $mysoc->phone , LOG_DEBUG);
		}

		return $returned_phone;
	}
}
?>
