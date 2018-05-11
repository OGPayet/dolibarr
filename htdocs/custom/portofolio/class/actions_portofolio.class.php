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

class ActionsPortofolio // extends CommonObject
{
	/** Overloading the function : replacing the parent's function with the one below
	 *  @param	  parameters	meta datas of the hook (context, etc...)
	 *  @param	  object		the object you want to process
	 *  @param	  action		current action (if set). Generally create or edit or null
	 *  @return	   void
	 */

	var $socid;

	function addMoreActionsButtons($parameters, $object, $action)
	{
		global $conf, $langs;
		global $user;

		// si sur une facture et que la ligne soit associé à un produit
		if ($action == 'view' && $conf->global->PORTOFOLIO_ENABLE_CLONE == "1") {
			$langs->load("portofolio@portofolio");
			if ($user->rights->societe->creer) {
				print '<div class="inline-block divButAction">';
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=clonetiers">';
				print $langs->trans("Cloning").'</a></div>'."\n";
			}
		}
	}

	function doActions($parameters, $object, $action)
	{
		global $conf, $db;
		global $user;

		if ($action == 'clonetiers') {
			$objecttoclone = new Societe($db);
			$idsoc=$parameters['id'];
			$objecttoclone->fetch($idsoc);
			$objecttoclone->name .= " (1)";
			$objecttoclone->code_client = -1;
			$objecttoclone->code_fournisseur = -1;

			// pour désactiver le transfert des extrafields lors du clonage
			if ($conf->global->PORTOFOLIO_ENABLE_EXTRAFIELDS_CLONE == 1)
				$newsocid = $objecttoclone->create($user);
			else {
				$tmp =$conf->global->MAIN_EXTRAFIELDS_DISABLED;
				$conf->global->MAIN_EXTRAFIELDS_DISABLED=1;
				$newsocid = $objecttoclone->create($user);
				$conf->global->MAIN_EXTRAFIELDS_DISABLED = $tmp;
			}

			$objecttoclone->set_parent($idsoc);
			header('Location: '.$_SERVER["PHP_SELF"].'?socid='.$newsocid.'&action=edit');
			exit;
		}
	}

	function printCommonFooter($parameters, $objectvide, $action)
	{
		// le $objectvide n'est pas à utiliser
		// idem le $action qui est réinit en bas de page
		global $conf, $object, $user;

		// pour le moment c'est à tous les éléments transmis
		if ( $conf->global->PORTOFOLIO_CCMAIL_TO_SALESMAN==1
			&& ( GETPOST('action')=='presend' && GETPOST('mode')=='init')) {
			// on regarde si il y a un tiers
			$idtofetch = isset($object->socid) ? $object->socid :
							(isset($object->fk_soc) ? $object->fk_soc : $object->fk_thirdparty);
			if ($idtofetch) {
				$object->fetch_thirdparty();
				$arraycomm=$object->thirdparty->getSalesRepresentatives($user);
				$emailsupp="";
				if (count($arraycomm) >0) {
					foreach ($arraycomm as $key => $value)
						$emailsupp.=($emailsupp? "," : "").$value['email'];
					// on transmet par jquery les emails récupérés
					print "<script>";
					print "jQuery(document).ready(function () {\n";
					print '	$("#sendtocc").val("'.$emailsupp.'")';
					print "})\n;";
					print "</script>\n";
				}
			}
		}
		return 0;
	}
}