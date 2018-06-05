<?php
/* Copyright (C) 2017 	oscss-shop 					<support@oscss-shop.fr>
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


dol_include_once('/framework/class/generictabsforobjecttype.class.php');

class GenericTabsUser
	extends GenericTabsForObjectType{

	public
		/**
			@var
		*/
			$printTabMenu='user_prepare_head'
		;


	/**
		@fn DisplayBanner($AutoTabs, $object)
		@brief
		@param obj  $AutoTabs
		@param obj $object
		@return none but print content
	*/
	public function DisplayBanner($AutoTabs, $object){
		global $langs, $conf, $user, $db;

		$db = $this->db;
		$form = $AutoTabs->form;


			/*
		* Affichage onglets
		*/

		$this->dol_fiche_head($AutoTabs,$object);
// 		$head = user_prepare_head($object);

// 		$title = $langs->trans("User");
// 		dol_fiche_head($head, $AutoTabs->GetParams('mod').'tabs', $title, -1, 'user');

		$linkback = '';

		if ($user->rights->user->user->lire || $user->admin) {
			$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
		}

    dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);



	}

}