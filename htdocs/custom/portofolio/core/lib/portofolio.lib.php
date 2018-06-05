<?php
/* Copyright (C) 2015-2016	Charlie BENKE	<charlie@patas-monkey.com>
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
 * or see http://www.gnu.org/
 */

/**
 *		\file	   htdocs/portofolio/core/lib/portofolio.lib.php
 *		\brief	  Ensemble de fonctions de base pour portofolio
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object		 Member
 *  @return array		   		head
 */
function portofolio_admin_prepare_head ()
{
	global $langs;

	$h = 0;
	$head = array();

	$head[$h][0] = 'setup.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';

	$h++;
	$head[$h][0] = 'about.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';

	return $head;
}

function commercial_affected($socid, $userid)
{
	global $db;

	// boucle produit dans l'entrepot
	$sql = "SELECT rowid ";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux ";
	$sql.= " WHERE fk_soc = ".$socid;
	$sql.= " AND fk_user = ".$userid;

	dol_syslog("Portofolio.Lib::commercial_affected sql=".$sql);
	$resql=$db->query($sql);

	if ($resql)
		return $db->num_rows($resql);

	return 0;
}

function aviable_type_contact ($element, $source='internal', $active=1)
{
	global $db;
	$sql = "SELECT tc.rowid, tc.libelle";
	$sql.= " FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
	$sql.= " WHERE tc.element = '".$element."' AND source = '".$source."'";
	$sql.= " and active=".$active;
	$resqltypecontact = $db->query($sql);
	if ($resqltypecontact) {
		$k=0;
		$numtypecontact = $db->num_rows($resqltypecontact);
		if ($numtypecontact > 0) {
			$typecontactselect = array();
			while ($k < $numtypecontact) {
				$objtc = $db->fetch_object($resqltypecontact);
				$typecontactselect[$objtc->rowid]= $objtc->libelle;
				$k++;
			}
		}
	}
	return $typecontactselect;
}