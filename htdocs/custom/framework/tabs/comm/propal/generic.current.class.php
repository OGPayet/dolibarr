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
 *       \file       htdocs/framework/tabs/comm/propal/genric.current.class.php
 *       \brief      Home page for module builder module
 */

dol_include_once('/framework/class/generictabsforobjecttype.class.php');


/**
	@class GenericTabsPropal
	@ingroup Frameworks
*/
class GenericTabsPropal
	extends GenericTabsForObjectType{

	public
		/**
			@var
		*/
			$printTabMenu='propal_prepare_head'
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
//
// 			$head = propal_prepare_head($object);


// 			dol_fiche_head($head, $AutoTabs->GetParams('mod').'tabs'.$AutoTabs->GetParams('tab'), $langs->trans('Proposal'), 0, 'propal');

			 $this->dol_fiche_head($AutoTabs,$object);

			$cssclass='titlefield';
			//if ($action == 'editnote_public') $cssclass='titlefieldcreate';
			//if ($action == 'editnote_private') $cssclass='titlefieldcreate';


			// Proposal card

			$linkback = '<a href="' . dol_buildpath('/comm/propal/list.php', 2) . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


			$morehtmlref='<div class="refidno">';
			// Ref customer
			$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
			$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
			// Thirdparty
			$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);


			/// TODO Add hook launch


			// Project
// 			if (! empty($conf->projet->enabled))
// 			{
// 			    $langs->load("projects");
// 			    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
// 			    if ($user->rights->propal->creer)
// 			    {
// 			        if ($action != 'classify')
// 			            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
// 			            $morehtmlref.=' : ';
// 			            if ($action == 'classify') {
// 			                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
// 			                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
// 			                $morehtmlref.='<input type="hidden" name="action" value="classin">';
// 			                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
// 			                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
// 			                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
// 			                $morehtmlref.='</form>';
// 			            } else {
// 			                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
// 			            }
// 			    } else {
// 			        if (! empty($object->fk_project)) {
// 			            $proj = new Project($db);
// 			            $proj->fetch($object->fk_project);
// 			            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
// 			            $morehtmlref.=$proj->ref;
// 			            $morehtmlref.='</a>';
// 			        } else {
// 			            $morehtmlref.='';
// 			        }
// 			    }
// 			}
			$morehtmlref.='</div>';

			dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}

}