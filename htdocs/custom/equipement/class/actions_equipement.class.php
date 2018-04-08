<?php
/* Copyright (C) 2012-2017 charlie Benke	<charlie@patas-monkey.com>
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
 * 	\file	   htdocs/equipement/class/actions_equipement.class.php
 * 	\ingroup	equipement
 * 	\brief	  Fichier de la classe des actions/hooks des équipements
 */

class ActionsEquipement // extends CommonObject
{

	/** Overloading the doActions function : replacing the parent's function with the one below
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 *  @param	  action			 current action (if set). Generally create or edit or null
	 *  @return	   void
	 */
	function printSearchForm($parameters, $object, $action)
	{
		global $conf, $langs;

		// pour les anciennes version ou si on a activé l'ancienne recherche
		if (DOL_VERSION < "3.9.1" || $conf->global->MAIN_USE_OLD_SEARCH_FORM == 1) {
			$langs->load("equipement@equipement");
			$title = img_object('', 'equipement@equipement').' '.$langs->trans("Equipements");
			$ret='';
			$ret.='<form action="'.dol_buildpath('/equipement/list.php',1 ).'" method="post">';
			$ret.='<div class="menu_titre menu_titre_search">';
			$ret.='<a class="vsmenu" href="'.dol_buildpath('/equipement/list.php', 1).'">';
			$ret.=$title.'</a><br>';
			$ret.='</div>';
			$ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$ret.='<input type="hidden" name="mode" value="search">';
			$ret.='<input type="text" class="flat" ';
			if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER))
				$ret.=' placeholder="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
			else
				$ret.=' title="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
			$ret.=' name="search_ref" size="10" />&nbsp;';
			$ret.='<input type="submit" class="button" style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px" value="'.$langs->trans("Go").'">';
			$ret.="</form>\n";
			$this->resprints=$ret;
		}
		return 0;
	}

	function addSearchEntry ($parameters, $object, $action)
	{
		global $confg, $langs;
		$resArray=array();
		$resArray['searchintoequipement']=array(
						'text'=>img_picto('', 'object_equipement@equipement').' '.$langs->trans("Equipement", GETPOST('q')),
						'url'=>dol_buildpath('/equipement/list.php?sall='.urlencode(GETPOST('q')), 1)
		);
		$this->results = $resArray;
		return 0;
	}
}