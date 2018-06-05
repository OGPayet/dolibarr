<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
	@file /htdocs/framework/class/generictabsforobjecttype.class.php
	@ingroup framework, autotabs
*/


/**
	@class GenericTabsForObjectType
	@brief this class is called for construct tab by Autotabs engine;
	This model construct base of called method for normal base of module support
*/
Class GenericTabsForObjectType {
	public
		/**
			@var
		*/
			$db
		/**
			@var
		*/
		,	$printTabMenu='_prepare_head'
		/**
			@var
		*/
// 		,	$type=''

			;



	/**
		@fn __construct($db )
	*/
	function __construct($db){
		$this->db = $db;
	}

	public function PreProcess($AutoTabs){


		if(count($AutoTabs->libs))
			foreach($AutoTabs->libs as $path)
				dol_include_once($path);
		else
			dol_include_once('/'.$AutoTabs->type.'/core/lib/'.$AutoTabs->type.'.lib.php');


		if(!empty($AutoTabs->printTabMenu) && function_exists($AutoTabs->printTabMenu ))
			$this->printTabMenu=$AutoTabs->printTabMenu;
		elseif(!function_exists($this->printTabMenu ))
			$this->printTabMenu= $AutoTabs->type.$this->printTabMenu;

	}

	/**
		@fn dol_fiche_head($db )
	*/
	function dol_fiche_head(&$AutoTabs,$object, $title=''){
		global $langs;

		/*
		* Affichage onglets
		*/
		$function = $this->printTabMenu;
		$head = $function($object);


		if( !empty($AutoTabs->titleTabs))
			$title = $langs->trans($AutoTabs->titleTabs );
		elseif(empty($title))
			$title = $title;
		elseif( !empty($AutoTabs->titleTabs))
			$title = $langs->trans($AutoTabs->FV->GetNameByType() );

		dol_fiche_head($head, $AutoTabs->GetParams('mod').'tabs'.$AutoTabs->type, $title, -1, $AutoTabs->type);
	}

	/**
		@fn DisplayBanner($AutoTabs, $object)
		@param object $AutoTabs
		@param object $object ressource dolibarr object
		@return none
	*/
	public function DisplayBanner($AutoTabs, $object){
		global $langs, $conf, $user, $db;

		$db = $this->db;
		$form = $AutoTabs->form;

		/*
		* Affichage onglets
		*/

		$this->dol_fiche_head($AutoTabs,$object);

		$linkback = '';

		if ($user->rights->{$AutoTabs->type}->lire || $user->admin) {

			$linkback = '<a href="'.dol_buildpath('/'.$AutoTabs->type.'/index.php',2).'">'.$langs->trans("BackToList").'</a>';


		}

    dol_banner_tab(
				$object
			,	$AutoTabs->FV->GetPathByType()
			,	$linkback,$user->rights->{$AutoTabs->type}->lire
				|| $linkback,$user->rights->{$AutoTabs->type}->read
				|| $user->admin
			, ''
			, ''
			, $AutoTabs->morehtmlref
			);

//     _banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom', $AutoTabs->morehtmlref);

	}

}
