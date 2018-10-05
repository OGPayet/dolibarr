<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/eventconfidentiality/class/actions_eventconfidentiality.class.php
 * \ingroup eventconfidentiality
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsEventConfidentiality
 */
class ActionsEventConfidentiality
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     meta datas of the hook (context, etc...)
     * @param   CommonObject    $object         the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    current hook manager
     * @return  void
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $db, $langs, $form;

		$element = $object->element;

		if($element == 'action') {
			$langs->load("eventconfidentiality@eventconfidentiality");
			dol_include_once('/advancedictionaries/class/dictionary.class.php');
			dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
			dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');

			if ($object->id > 0) {
				$fk_tags = fetchAllTagForObject($object->id);
				$fk_tags_id = array_column($fk_tags, 'id');
				if($action == 'edit') {
					//Tags
					$array_tags = array();
					$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
					$array_tags = $dictionary->fetch_array('rowid', '{{label}}', array(), array('label' => 'ASC'));
					$out .= '<tr>';
					$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagLabel") . '</td>';
					$out .= '<td colspan="3">'.$form->multiselectarray('add_tag', $array_tags, $fk_tags_id, '', 0, '', 0, '100%').'</td>';
					$out .= '</tr>';
				} else if($action == '') {
					//Tags
					$tags = "";
					foreach ($fk_tags as $fk_tag) {
					$tags .= '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #ff0000;"><img src="/htdocs/theme/owntheme/img/object_category.png" alt="" title="" class="inline-block valigntextbottom">'.$fk_tag['label'].' : '.$fk_tag['level_label'].'</li>';
					}
					$out .= '<tr>';
					$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagLabel") . '</td>';
					$out .= '<td colspan="3"><div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr" style="list-style:none;">'.$tags.'</ul></div></td>';
					$out .= '</tr>';
				}
			} else {
				//Tags
				$array_tags = array();
				$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
				$array_tags = $dictionary->fetch_array('rowid', '{{label}}', array(), array('label' => 'ASC'));

				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagLabel") . '</td>';
				$out .= '<td colspan="3">'.$form->multiselectarray('add_tag', $array_tags, array(), '', 0, '', 0, '100%').'</td>';
				$out .= '</tr>';
			}
			$this->resprints = $out;
		}
        return 0;
    }

	/**
     * Overloading the afterObjectFetch function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     meta datas of the hook (context, etc...)
     * @param   CommonObject    $object         the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    current hook manager
     * @return  void
     */
    function afterObjectFetch($parameters, &$object, &$action, $hookmanager)
    {
        global $db, $langs, $form, $user;

		$element = $object->element;

		$langs->load("eventconfidentiality@eventconfidentiality");
		dol_include_once('/advancedictionaries/class/dictionary.class.php');
		dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
		dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');

		if($object->id > 0) {
			$mode = 2;
			$user_tags = explode(",",$user->array_options['options_user_tag']);
			$fk_tags = fetchAllTagForObject($object->id);
			$tmp_mode = -1;
			$externe = (empty($user->socid)?0:1); //Utilisateur interne ou externe

			foreach($fk_tags as $fk_tag) {
				if(in_array($fk_tag['fk_dict_tag_confid'],$user_tags) && $fk_tag['externe'] == $externe) { //Si on a un tag en commun et que ce tag est interne
					$tmp_mode = max($tmp_mode,$fk_tag['level_confid']);
				}
			}
			if($tmp_mode > -1) { //Si l'utilisateur un tag en commun avec l'event on considère la visilibité minimal parmi les tags en commun
				$mode = $tmp_mode;
			}

			//Gestion du mode
			if($mode == 2) {
				// accessforbidden('',0,0,1);
				unset($object->id);
				unset($object->ref);
				unset($object->ref_ext);
				unset($object->type_id);
				unset($object->type_code);
				unset($object->type_color);
				unset($object->type_picto);
				unset($object->type);
				unset($object->code);
				unset($object->label);
				unset($object->datep);
				unset($object->datef);
				unset($object->durationp);
				unset($object->datec);
				unset($object->datem);
				unset($object->note);
				unset($object->percentage);
				unset($object->authorid);
				unset($object->usermodid);
				unset($object->author);
				unset($object->usermod);
				unset($object->userownerid);
				unset($object->userdoneid);
				unset($object->priority);
				unset($object->fulldayevent);
				unset($object->location);
				unset($object->transparency);
				unset($object->punctual);
				unset($object->socid);
				unset($object->contactid);
				unset($object->fk_project);
				unset($object->societe);
				unset($object->contact);
				unset($object->fk_element);
				unset($object->elementtype);
			} elseif($mode == 1) {
				unset($object->datec);
				unset($object->datem);
				unset($object->datep);
				unset($object->datef);
				unset($object->type);
				unset($object->code);
				unset($object->label);
			} else {
				//Do nothing
			}
		}
        return 0;
    }

	/**
     * Overloading the afterObjectFetch function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     meta datas of the hook (context, etc...)
     * @param   CommonObject    $object         the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    current hook manager
     * @return  void
     */
    function afterSQLFetch($parameters, &$object, &$action, $hookmanager)
    {
        global $db, $langs, $form, $user;

		$element = $object->element;

		$langs->load("eventconfidentiality@eventconfidentiality");
		dol_include_once('/advancedictionaries/class/dictionary.class.php');
		dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
		dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');

		if($object->id > 0) {
			$mode = 2;
			$user_tags = explode(",",$user->array_options['options_user_tag']);
			$fk_tags = fetchAllTagForObject($object->id);
			$tmp_mode = -1;
			$externe = (empty($user->socid)?0:1); //Utilisateur interne ou externe

			foreach($fk_tags as $fk_tag) {
				if(in_array($fk_tag['fk_dict_tag_confid'],$user_tags) && $fk_tag['externe'] == $externe) { //Si on a un tag en commun et que ce tag est interne
					$tmp_mode = max($tmp_mode,$fk_tag['level_confid']);
				}
			}
			if($tmp_mode > -1) { //Si l'utilisateur un tag en commun avec l'event on considère la visilibité minimal parmi les tags en commun
				$mode = $tmp_mode;
			}

			//Gestion du mode
			if($mode == 2) {
				// accessforbidden('',0,0,1);
				unset($object->id);
				unset($object->ref);
				unset($object->ref_ext);
				unset($object->type_id);
				unset($object->type_color);
				unset($object->type_picto);
				unset($object->type);
				unset($object->code);
				unset($object->label);
				unset($object->datep);
				unset($object->datef);
				unset($object->durationp);
				unset($object->datec);
				unset($object->datem);
				unset($object->note);
				unset($object->percentage);
				unset($object->authorid);
				unset($object->usermodid);
				unset($object->author);
				unset($object->usermod);
				unset($object->userownerid);
				unset($object->userdoneid);
				unset($object->priority);
				unset($object->fulldayevent);
				unset($object->location);
				unset($object->transparency);
				unset($object->punctual);
				unset($object->socid);
				unset($object->contactid);
				unset($object->fk_project);
				unset($object->societe);
				unset($object->contact);
				unset($object->fk_element);
				unset($object->elementtype);
				unset($object->date_start_in_calendar);
				unset($object->date_end_in_calendar);
				unset($object->client);
				unset($object->dp);
				unset($object->dp2);
				unset($object->fk_user_author);
				unset($object->fk_user_action);
				unset($object->fk_contact);
				unset($object->percent);
				unset($object->type_code);
				unset($object->type_label);
				unset($object->lastname);
				unset($object->firstname);
			} elseif($mode == 1) {
				unset($object->datec);
				unset($object->datem);
				unset($object->datep);
				unset($object->datef);
				unset($object->type);
				unset($object->code);
				unset($object->label);
				unset($object->date_start_in_calendar);
				unset($object->date_end_in_calendar);
				unset($object->type_code);
				unset($object->type_label);
				unset($object->dp);
				unset($object->dp2);
				unset($object->date_start_in_calendar);
				unset($object->date_end_in_calendar);
			} else {
				//Do nothing
			}
		}
        return 0;
    }
}
