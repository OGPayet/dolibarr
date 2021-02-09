<?php
/* Copyright (C) 2014-2016		charlie Benke	<charlie@patas-monkey.com>
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
 * 	\file	   htdocs/customlink/class/actions_customlink.class.php
 * 	\ingroup	customlink
 * 	\brief	  Fichier de la classe des actions/hooks des customlink
 */

class ActionsFactory
{
		/** Overloading the doActions function : replacing the parent's function with the one below
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process
	 *  @param	  action			 current action (if set). Generally create or edit or null
	 *  @return	   void
	 */
	// function printSearchForm($parameters, $object, $action)
	// {
	// 	global $conf, $langs;

	// 	if (DOL_VERSION < "3.9.1" || $conf->global->MAIN_USE_OLD_SEARCH_FORM == 1) {
	// 		$langs->load("factory@factory");
	// 		$title = img_object('', 'factory@factory').' '.$langs->trans("Factory");
	// 		$ret='';
	// 		$ret.='<form action="'.dol_buildpath('/factory/list.php', 1).'" method="post">';
	// 		$ret.='<div class="menu_titre menu_titre_search">';
	// 		$ret.='<label for="tag">';
	// 		$ret.='<a class="vsmenu" href="'.dol_buildpath('/factory/list.php', 1).'">';
	// 		$ret.=$title.'</a>';
	// 		$ret.='</label>';
	// 		$ret.='</div>';

	// 		$ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	// 		$ret.='<input type="text" class="flat" ';
	// 		if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER))
	// 			$ret.=' placeholder="'.$langs->trans("SearchOf").''.strip_tags($title).'"';
	// 		else
	// 			$ret.=' title="'.$langs->trans("SearchOf").''.strip_tags($title).'"';

	// 		$ret.=($accesskey?' accesskey="'.$accesskey.'"':'');

	// 		$ret.=' name="tag" size="10" />&nbsp;';
	// 		$ret.='<input type="submit" class="button" ';
	// 		$ret.=' style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px"';
	// 		$ret.=' value="'.$langs->trans("Go").'">';
	// 		$ret.="</form>\n";
	// 		$this->resprints=$ret;
	// 	}
	// 	return 0;
	// }

	function addSearchEntry ($parameters, $object, $action)
	{
		global $confg, $langs;
		$resArray=array();
		$resArray['searchintofactory']=array(
						'text'=>img_picto('','object_factory@factory').' '.$langs->trans("Factory", GETPOST('q')),
						'url'=>dol_buildpath('/factory/list.php?sall='.urlencode(GETPOST('q')), 1),
						'position' => 50
		);
		$this->results = $resArray;
		return 0;
	}

	function printElementTab($parameters, $object, $action)
	{
		global $db, $langs, $form, $user;

		$element = $parameters['element'];
		$element_id = $parameters['element_id'];

		if ($element == 'factory') {
			dol_include_once('/factory/class/factory.class.php');
			dol_include_once('/factory/core/lib/factory.lib.php');

			$factorystatic = new Factory($db);
			$factorystatic->fetch($element_id);

			if ($user->societe_id > 0) $socid=$user->societe_id;
			$result = restrictedArea($user, 'factory', $id);

			$head = factory_prepare_head($factorystatic);
			dol_fiche_head($head, 'resource', $langs->trans("Factory"), 0, 'factory@factory');
			print '<table class="border" width="100%">';
			$linkback = '<a href="'.dol_buildpath('/factory/list.php', 1).'">'.$langs->trans("BackToList").'</a>';

			// Ref
			print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
			print $form->showrefnav($factorystatic, 'ref', $linkback, 1, 'ref', 'ref', '');
			print '</td></tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$factorystatic->title.'</td></tr>';

			print "</table>";
			dol_fiche_end();
		}
		return 0;
	}
}
