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
 *	\file	   htdocs/core/boxes/box_equipementevt.php
 *	\ingroup	equipement
 *	\brief	  Module to show box of event equipement
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
dol_include_once("/equipement/class/equipement.class.php");

class box_equipementevt extends ModeleBoxes
{

	var $boxcode="equipementevt";
	var $boximg="equipement@equipement";
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
		$langs->load("equipement@equipement");
		$this->boxlabel=$langs->trans("equipementevt");

	}

	/**
	 *	  \brief	  Charge les donnees en memoire pour affichage ulterieur
	 *	  \param	  $max		Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db; // $conf,

		$this->max=$max;

		$equipement_static = new Equipement($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedEquipementEvt", $max));

		// list the summary of the orders
		if ($user->rights->equipement->lire) {
			$sql = "SELECT e.rowid, e.ref, p.ref as refproduit, p.label, ";
			$sql.= " eet.libelle as statueventlibelle, ee.tms as datem";
			$sql.= " FROM ".MAIN_DB_PREFIX."equipementevt as ee";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet ON ee.fk_equipementevt_type = eet.rowid,";
			$sql.= " ".MAIN_DB_PREFIX."equipement as e";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON e.fk_product = p.rowid";
			$sql.= " WHERE e.rowid = ee.fk_equipement";
			//$sql.= " and e.entity IN (".getEntity($product_static->element, 1).")";
			$sql.= $db->order("ee.tms", "DESC");
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);

			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num) {
					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
						'logo' => 'object_equipement@equipement');

					$objp = $db->fetch_object($result);

					$equipement_static->id=$objp->rowid;
					$equipement_static->ref=$objp->ref;
					$equipement_static->fk_product=$objp->refproduit;

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
						'text' => $equipement_static->getNomUrl(1),
						'url' => dol_buildpath("/equipement/card.php?id=".$objp->rowid, 1));

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
						'text' => $objp->refproduit. " - ".dol_trunc($objp->label, 32)
					);
					if ($objp->statueventlibelle)
						$this->info_box_contents[$i][3] = array(
										'td' => 'align="right"',
										'text' => $langs->trans($objp->statueventlibelle)
						);
					else
						$this->info_box_contents[$i][3] = array(
										'td' => 'align="right"',
										'text' => $langs->trans("None")
						);
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