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

		$langs->load("eventconfidentiality@eventconfidentiality");
		dol_include_once('/advancedictionaries/class/dictionary.class.php');

		if ($object->id > 0) {
			$sql = "SELECT * FROM llx_event_agenda WHERE fk_object = ".$object->id;
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
			}
			if($action == 'edit') {
				//Tags
				$list_tag = array();
				if(!empty($obj->fk_dict_tag_confid)) {
					$list_tag = explode(',',$obj->fk_dict_tag_confid);
				}
				$array_tags = array();
				$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
				$dictionary->fetch_lines();
				foreach ($dictionary->lines as $line) {
					$array_tags[$line->fields[0]] = $line->fields['label'];
				}
				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagLabel") . '</td>';
				$out .= '<td colspan="3">'.$form->multiselectarray('add_tag', $array_tags, $list_tag, '', 0, '', 0, '100%').'</td>';
				$out .= '</tr>';

				//Interne
				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityExternalLabel") . '</td>';
				$out .= '<td colspan="3"><input type="checkbox" class="flat maxwidthonsmartphone" id="edit_external" name="edit_external" '.($obj->interne?'checked':'').'></td>';
				$out .= '</tr>';

				// Level
				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityModeLabel") . '</td>';
				$out .= '<td colspan="3">';
				$out .= '<select class="flat minwidth200 maxwidthonsmartphone" id="add_mode" name="add_mode">';
				$out .= '<option value="-1" '.($obj->level_confid==""?'selected':'').'>&nbsp;</option>';
				$out .= '<option value="0" '.($obj->level_confid==0?'selected':'').'>'.$langs->trans('EventConfidentialityModeVisible').'</option>';
				$out .= '<option value="1" '.($obj->level_confid==1?'selected':'').'>'.$langs->trans('EventConfidentialityModeBlurred').'</option>';
				$out .= '<option value="2" '.($obj->level_confid==2?'selected':'').'>'.$langs->trans('EventConfidentialityModeHidden').'</option>';
				$out .= '</td>';
				$out .= '</tr>';
			} else if($action == '') {
				//Tags
				$list_tag = array();
				if(!empty($obj->fk_dict_tag_confid)) {
					$list_tag = explode(',',$obj->fk_dict_tag_confid);
				}
				$array_tags = array();
				$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
				$dictionary->fetch_lines();
				foreach ($dictionary->lines as $line) {
					$array_tags[$line->fields[0]] = $line->fields['label'];
				}
				$tags = "";
				foreach ($dictionary->lines as $line) {
					if(in_array($line->fields[0],$list_tag)) {
						$tags .= '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #ff0000;"><img src="/htdocs/theme/owntheme/img/object_category.png" alt="" title="" class="inline-block valigntextbottom">'.$line->fields['label'].'</li>';
					}
				}
				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagLabel") . '</td>';
				$out .= '<td colspan="3"><div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr" style="list-style:none;">'.$tags.'</ul></div></td>';
				$out .= '</tr>';

				//Interne
				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityExternalLabel") . '</td>';
				$out .= '<td colspan="3"><input type="checkbox" class="flat maxwidthonsmartphone" '.($obj->interne?'checked':'').' disabled></td>';
				$out .= '</tr>';

				// Level
				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityModeLabel") . '</td>';
				$out .= '<td colspan="3">';
				if($obj->level_confid == 0) {
					$out .= $langs->trans('EventConfidentialityModeVisible');
				} else if($obj->level_confid == 1) {
					$out .= $langs->trans('EventConfidentialityModeBlurred');
				} else if($obj->level_confid == 2) {
					$out .= $langs->trans('EventConfidentialityModeHidden');
				} else {
					$out .= "";
				}
				$out .= '</td>';
				$out .= '</tr>';
			}
		} else {
			//Tags
			$array_tags = array();
			$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
			$dictionary->fetch_lines();
			foreach ($dictionary->lines as $line) {
				$array_tags[$line->fields[0]] = $line->fields['label'];
			}
			$out .= '<tr>';
			$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagLabel") . '</td>';
			$out .= '<td colspan="3">'.$form->multiselectarray('add_tag', $array_tags, array(), '', 0, '', 0, '100%').'</td>';
			$out .= '</tr>';

			//Interne
			$out .= '<tr>';
			$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityExternalLabel") . '</td>';
			$out .= '<td colspan="3"><input type="checkbox" class="flat maxwidthonsmartphone" id="add_external" name="add_external"></td>';
			$out .= '</tr>';

			//Level
			$out .= '<tr>';
			$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityModeLabel") . '</td>';
			$out .= '<td colspan="3">';
			$out .= '<select class="flat minwidth200 maxwidthonsmartphone" id="add_mode" name="add_mode">';
			$out .= '<option value="-1">&nbsp;</option>';
			$out .= '<option value="0">'.$langs->trans('EventConfidentialityModeVisible').'</option>';
			$out .= '<option value="1">'.$langs->trans('EventConfidentialityModeBlurred').'</option>';
			$out .= '<option value="2">'.$langs->trans('EventConfidentialityModeHidden').'</option>';
			$out .= '</td>';
			$out .= '</tr>';
		}
		$this->resprints = $out;

        return 0;
    }
}
