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


/**
 *	\file       htdocs/comm/propal/note.php
 *	\ingroup    propal
 *	\brief      Fiche d'information sur une proposition commerciale
 */

use \Form;
use \Formother;
use \FormQualityReport;
use \UserGroup;
use \User;
use \ExtraFields;

use \Propal;

use \CORE\FRAMEWORK\Societe as Societe;
use \CORE\FRAMEWORK\AutoTabs as AutoTabs;
use \CORE\FRAMEWORK\AutoTabsRequired as AutoTabsRequired;


use \Task;


dol_include_once('/synergies_tech_contrat/class/synergies_tech_contrat.class.php');
// dol_include_once('/synergies_tech_contrat/class/task.class.php');
dol_include_once('/synergies_tech_contrat/class/html.formsynergies_tech_contrat.class.php');
// dol_include_once('/synergies_tech_contrat/core/lib/synergies_tech_contrat.lib.php');


require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load("companies");

$langs->load("synergies_tech_contrat@synergies_tech_contrat");

// Security check
// if ($user->societe_id) $socid=$user->societe_id;
// $result = restrictedArea($user, 'propale', $id, 'propal');




Class TabsOrder_supplier
	extends AutoTabs
	implements
		AutoTabsRequired
	{

	public
		/**
			@var
		*/
			$name
		,
		/**
			@var array
		*/
			$refparam = array(
							'id'=>'int'
						, 'ref'=>'alpha'
						, 'action'=>'alpha'
						, 'mod'=>'alpha'
						, 'tab'=>'alpha'
					)
		;


	/**
		@fn Init()
		@brief
		@param
		@return
	*/
	public function _Init(){
		$file = basename(__FILE__);

		$this->type = substr($file , 0, strpos($file, '.' ) );

		$class = $this->FV->GetClassByType($this->type);
		// load Specific context

		$this->object = new $class($this->db);
		return true;
	}


	/**
		@fn Init()
		@brief
		@param
		@return
	*/
	public function _Process(){
		$this->synergies_tech_contrat = new synergies_tech_contrat($this->db);
		if($this->GetParams('id') != null || $this->GetParams('ref') != null) {

			$this->object->fetch($this->GetParams('id'), $this->GetParams('ref'));
			$this->object->fetch_thirdparty();
		}

		return true;
	}

	/**
		@fn Display()
		@brief
		@param
		@return
	*/
	public function _Display(){
		global $trans, $conf, $user;



			global $formfile;
			$formfile = new FormFile($this->db);


			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';


				DrawLink(	$this->synergies_tech_contrat
									, $this->type
									, $this->object
									, (string)$this->FV->GetClassByType($this->type)
									, (string)$this->FV->GetTableByType($this->type)
									, (string)$this->FV->GetDatefieldnameByType($this->type)
									, (string)$this->FV->GetLangByType($this->type)
									, (string)$this->FV->GetNameByType($this->type)
								);

			print '</div>';
		return true;
	}




}
