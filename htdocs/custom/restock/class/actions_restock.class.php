<?php
/* Copyright (C) 2016-2017	Charlie Benke	<charlie@patas-monkey.com>
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
 * 	\file	   /portofolio/class/actions_portofolio.class.php
 * 	\ingroup	portofolio
 * 	\brief	  Fichier de la classe des actions/hooks de portofolio
 */

class ActionsRestock // extends CommonObject
{
	/** Overloading the formContactTpl function : replacing the parent's function with the one below
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 *  @param	  action			 current action (if set). Generally create or edit or null
	 *  @return	   void
	 */

	function addMoreActionsButtons($parameters, $object, $action)
	{
		global $conf, $langs, $db;
		global $user;
		// si sur une facture et que la ligne soit associé à un projet ou l'on vien de mettre à jour le projet
		if (	( $action == '' ||
			( $action=='classin' && GETPOST('projectid', 'int') > 0) )
				&& $conf->global->RESTOCK_ADD_LINKORDERBYPROJECT == "1" ) {
			$langs->load("restock@restock");

			if ($object->fk_project > 0 && count($object->linkedObjectsIds) == 0) {
				print '<div class="inline-block divButAction">';
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addcmdcustproj">';
				print $langs->trans("AddOrderLinkFromProject").'</a></div>'."\n";
			}
		}
	}

	function doActions($parameters, $object, $action)
	{
		global $conf, $langs, $db;
		global $user;

		if ($action == 'addcmdcustproj') {
			// récup de l'id de la cmde client associé au projet
			$sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'commande';
			$sql.=" WHERE fk_projet=".$object->fk_project;
			$resql = $db->query($sql);
			if ($resql) {
				$objp = $db->fetch_object($resql);
				$cmdecliID=$objp->rowid;
			}

			// création du lien entre la cmdfourn et la cmde client
			$object->origin = "commande";
			$object->origin_id = $cmdecliID;
			// on ajoute le lien au autres client
			$ret = $object->add_object_linked();
		}
	}
}