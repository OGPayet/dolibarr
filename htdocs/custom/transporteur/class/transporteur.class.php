<?php
/* Copyright (C) 2016-2018	Charlene BENKE	<charlie@patas-monkey.com>
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
 */

/**
 *	\file	   htdocs/customline/class/customline.class.php
 *	\ingroup	tools
 *	\brief	  File of class to customline moduls
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage members type
 */
class Transporteur
{

	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	// return weight of lines
	// weight is allways in KG
	function getWeight($lines)
	{
		// on totalise le poid des produits
		$staticproduct= New Product($this->db);
		$totalWeight = 0;
		foreach ($lines as $curline) {
			//  si c'est un produit
			if ($curline->fk_product > 0 && $curline->fk_product_type == 0 ) {
				$staticproduct->fetch($curline->fk_product);
				$weight= $staticproduct->weight;
				$weightunits= $staticproduct->weight_units;
				if ($weightunits < 50) {
					$trueWeightUnit=pow(10, $weightunits);
					$totalWeight += $weight * $curline->qty * $trueWeightUnit;
				} else {
					if ($weightunits == 99) {
						// conversion 1 Livre = 0.45359237 KG
						$trueWeightUnit = 0.45359237;
						$totalWeight += $weight * $curline->qty * $trueWeightUnit;
					} elseif ($weightunits == 98) {
						// conversion 1 once = 0.0283495 KG
						$trueWeightUnit = 0.0283495;
						$totalWeight += $weight * $curline->qty * $trueWeightUnit;
					} else {
						// This may be wrong if we mix different units
						$totalWeight += $weight * $curline->qty;
					}
				}
			}
		}
		return $totalWeight;
	}

	function getRealWeight($weight, $weightunit)
	{
		if ($weightunit < 50) {
			$trueWeightUnit=pow(10, $weightunit);
			$realWeight = $weight * $trueWeightUnit;
		} else {
			if ($weightunit == 99) {
				// conversion 1 Livre = 0.45359237 KG
				$trueWeightUnit = 0.45359237;
				$realWeight = $weight * $trueWeightUnit;
			} elseif ($weightunit == 98) {
				// conversion 1 once = 0.0283495 KG
				$trueWeightUnit = 0.0283495;
				$realWeight= $weight * $trueWeightUnit;
			} else {
				// This may be wrong if we mix different units
				$realWeight = $weight ;
			}
		}
		return $realWeight ;
	}

	function getPrice($totalWeight, $object)
	{
		global $mysoc;
		// on récupère le pays de livraison du contact d'expedition si il y en a un
		$fk_countryToShipp = $object->thirdparty->country_id;

		$arrayidshipping=$object->getIdContact('external', 'SHIPPING');
		if (count($arrayidshipping) > 0) {
			$result=$object->fetch_contact($arrayidshipping[0]);
			$fk_countryToShipp = $object->contact->country_id;
		}

		// TODO prévoir l'expedition depuis un autre pays
//		$arrayidshipping=$object->getIdContact('internal', 'SHIPPING');
//		if (count($arrayidshipping) > 0) {
//			$result=$object->fetch_user($arrayidshipping[0]);
//			$fk_countryFromShipp = $object->contact->country_id;
//		} else {

		$fk_countryFromShipp = $mysoc->country_id;

		// on boucle sur tous les prix
		$sql = "SELECT weightmax, weightunit, subprice";
		$sql .= " FROM ".MAIN_DB_PREFIX."transporteur_rate";
		$sql .= " WHERE active=1";
		$sql .= " AND (fk_pays=".$fk_countryToShipp;

		// si même pays, on aussi le pays non saisie
		if ($fk_countryFromShipp == $fk_countryToShipp)
			$sql .= " OR fk_pays=0";

		$sql .= ")";

		$resql=$this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			$transportweight=0;
			$transportPrice = -1;
			$prixmax = 0;
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);
					if ($obj->weightmax == -1)
						$prixmax = $obj->subprice;
					else {

						$realWeight=$this->getRealWeight($obj->weightmax, $obj->weightunit);

						// si on trouve une borne max pour le produit et que le précédent n'est pas plus léger
						if (($realWeight > $totalWeight) && ($realWeight < $transportweight || $transportweight == 0)) {
							$transportPrice = $obj->subprice;
							$transportweight = $realWeight;
						}
					}
					$i++;
				}
				// si on a pas trouvé de borne supérieur
				if ($transportPrice == -2) {
					// si pas de prix de transport trouvé on prend le prix max
					if ($prixmax > 0)
						$transportPrice = $prixmax;
				}
				return $transportPrice;
			}
			else
				return -1;
		}
		else
			return -3;
	}

	// on ajoute / modifie à l'objet
	function add_transporteur(&$object)
	{
		global $langs, $conf, $user;

		$transporteurservice=(int) $conf->global->TRANSPORTEUR_SERVICE;
		$totalWeight = $this->getWeight($object->lines);
		$transportPresentRowid=0;	//0 pas present , sinon c'est le ID de la ligne
		foreach ($object->lines as $curline) {
			// si on trouve le service lié au transport, on  le mémorise
			if ($curline->fk_product == $transporteurservice)
				$transportPresentRowid = $curline->rowid;
		}

		// on regarde le cout à appliquer
		$priceweight = $this->getPrice($totalWeight, $object);
		if ($priceweight <0)
			return $priceweight;
		$transportfranco=$conf->global->TRANSPORTEUR_FRANCO;

		if ($conf->global->TRANSPORTEUR_FRANCO_TTC == 0)
			$restToFranco=$transportfranco - $object->total_ht+ $priceweight ;
		else
			$restToFranco=$transportfranco - $object->total_ttc+ $priceweight ;

		// récup des infos du produits
		$staticproduct= New Product($this->db);
		$staticproduct->fetch($transporteurservice);

		$txTVA=$staticproduct->tva_tx;
		$txlocaltax1=$staticproduct->localtax1_tx;
		$txlocaltax2=$staticproduct->localtax2_tx;
		if ($restToFranco < 0) {
			$priceweight=0;
			$label=$langs->trans($conf->global->TRANSPORTEUR_FRANCO_TEXTE, $totalWeight);
		} else
			$label=$staticproduct->label." ".$totalWeight;

		// on met à jour le montant du service
		switch($object->element) {
			case 'propal':
				$object->deleteline($transportPresentRowid);

				$result = $object->addline(
								"", price2num($priceweight),
								1, $txTVA, $txlocaltax1, $txlocaltax2,
								$transporteurservice,
								0, 'HT', 0, 0, 1,
								-1, 0, 0, 0,
								0, $label
				);
				break;

			case 'commande':
				if (DOL_VERSION < "5.0.0")
					$object->deleteline($transportPresentRowid);
				else
					$object->deleteline($user, $transportPresentRowid);

				$result = $object->addline(
								"", price2num($priceweight),
								1, $txTVA, $txlocaltax1, $txlocaltax2,
								$transporteurservice,
								0, 0, 0, 'HT', 0,
								'', '', 1,
								-1, 0, 0, 0,
								0, $label
				);
				break;

			case 'facture':
				$object->deleteline($transportPresentRowid);

				$result = $object->addline(
								"", price2num($priceweight),
								1, $txTVA, $txlocaltax1, $txlocaltax2,
								$transporteurservice,
								0, '', '', 0, 0,
								'', 'HT', price2num($priceweight), 1,
								-1, 0, '', 0, 0, 0,
								0, $label, 0, 100, '', 0
				);
				break;
		}

		// Mise a jour info denormalisees
		$object->update_price(1);
		return 0;
	}
}