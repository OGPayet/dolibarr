<?php
/* Copyright (C) 2012-2016	Charlie BENKE <charlie@patas-monkey.com>
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
 *	\file	   htdocs/core/boxes/box_equipement.php
 *	\ingroup	equipement
 *	\brief	  Module to show box of equipement
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

class box_factory extends ModeleBoxes
{

	var $boxcode="factory";
	var $boximg="factory@factory";
	var $boxlabel;

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *	  \brief	  Constructeur de la classe
	 */
	function __construct()
	{
		global $langs;
		$langs->load("boxes");
		$langs->load("factory@factory");
		$this->boxlabel="Factory";
	}

	/**
	 *	  \brief	  Charge les donnees en memoire pour affichage ulterieur
	 *	  \param	  $max		Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db; // $conf,

		$this->max=$max;

		include_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
		dol_include_once("/factory/class/factory.class.php");
		$factory_static = new Factory($db);
		$product_static = new Product($db);
		$entrepot_static= new Entrepot($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedFactory", $max));

		// list the summary of the orders
		if ($user->rights->factory->lire) {
			$sql = "SELECT * FROM ".MAIN_DB_PREFIX."factory as f";
			$sql.= $db->order("f.tms", "DESC");
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);

			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num) {

					$objp = $db->fetch_object($result);

					$factory_static->fetch($objp->rowid);
					$product_static->fetch($objp->fk_product);
					$entrepot_static->fetch($objp->fk_entrepot);

					$this->info_box_contents[$i][0] = array('td' => 'align="left"',
						'text' => $factory_static->getNomUrl(1),
						'logo' => 'factory@factory',
						'url' => dol_buildpath("/factory/fiche.php?id=".$objp->rowid, 1));
					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => $product_static->ref,
						'logo' => 'product',
						'url' => DOL_URL_ROOT."/product/card.php?id=".$objp->fk_product);
					$this->info_box_contents[$i][2] = array('td' => 'align="left"',
						'text' => $entrepot_static->getNomUrl(1),
						'logo' => 'stock',
						'url' => DOL_URL_ROOT."/product/stock/card.php?id=".$objp->fk_entrepot);

					$datestart=dol_print_date($db->jdate($objp->date_start_planned), "day");
					$dateend=dol_print_date($db->jdate($objp->date_end_planned), "day");

					if ($objp->fk_statut > 0) // si plus brouillon on prend la date réel
						$datestart=dol_print_date($db->jdate($objp->date_start_made), "day");

					if ($objp->fk_statut == 2) // si terminé on prend la vrai date de fin
						$dateend=dol_print_date($db->jdate($objp->date_end_made), "day");

					$this->info_box_contents[$i][3] = array('td' => 'align="left"',
													'text' => $langs->trans("StartFactory").$datestart);
					$this->info_box_contents[$i][4] = array('td' => 'align="left"',
													'text' => $langs->trans("EndFactory").$dateend);

					$this->info_box_contents[$i][5] = array('td' => 'align="right" ',
													'text' => $factory_static->LibStatut($objp->fk_statut, 3));
					$i++;
				}
			}
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head	   Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null, $nooutput = 0)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}