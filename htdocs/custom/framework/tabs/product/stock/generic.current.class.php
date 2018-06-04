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



require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
/**
	@file /htdocs/framework/class/generictabssociete.class.php
	@ingroup framework, autotabs
*/
dol_include_once('/framework/class/generictabsforobjecttype.class.php');

/**
	@class GenericTabsEntrepot
	@brief this class is called for construct tab by Autotabs engine;
	This model construct base of called method for normal base of module support

	Specififc Thirdparty page
*/
class GenericTabsEntrepot
	extends GenericTabsForObjectType{

	public
		/**
			@var
		*/
			$printTabMenu='_prepare_head'
		/**
			@var
		*/
// 		,	$type=''
			;



	public function DisplayBanner($AutoTabs, $object){
		global $langs, $conf, $user, $db;

		$db = $this->db;
		$form = $AutoTabs->form;

		/*
		* Affichage onglets
		*/

		$this->dol_fiche_head($AutoTabs,$object);

		$linkback = '';

		if ($user->rights->user->user->lire || $user->admin) {

			$linkback = '<a href="'.dol_buildpath('/product/'.$AutoTabs->type.'/index.php',2).'">'.$langs->trans("BackToList").'</a>';
		}

    dol_banner_tab(
				$object
			,	$AutoTabs->FV->GetPathByType()
			,	$linkback,$user->rights->{$AutoTabs->type}->user->lire
				|| $linkback,$user->rights->{$AutoTabs->type}->user->read
				|| $user->admin
			, ''
			, ''
			, $AutoTabs->morehtmlref
			);

//     _banner_tab($object, 'socid', $linkback, ($user->entrepot_id?0:1), 'rowid', 'nom', $AutoTabs->morehtmlref);

	}

}