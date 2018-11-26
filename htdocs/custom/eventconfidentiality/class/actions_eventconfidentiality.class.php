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
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

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
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $user;

        if (is_array($parameters) && !empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $$key = $value;
            }
        }
        $contexts = explode(':', $parameters['context']);

        if (in_array('actioncard', $contexts)) {
            $id = GETPOST('id', 'int');
            if (!empty($id)) {
                $object->fetch($id);
            }

            if ($object->id > 0 && $user->rights->eventconfidentiality->manage) {
                $cancelbutton = GETPOST('cancel');
                $confirm = GETPOST('confirm', 'alpha');
                $confirm_delete_tags = $action == 'confirm_delete_tags' && $confirm == 'yes';

                if (empty($cancelbutton) && ($action == 'update' || $confirm_delete_tags)) {
                    $fk_tags_interne = fetchAllTagForObject($object->id, 0);
                    $fk_tags_externe = fetchAllTagForObject($object->id, 1);
                    $tags = array_merge($fk_tags_interne, $fk_tags_externe);
                    if (count($tags) > 0) {
                        $error = 0;
                        dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                        $this->db->begin();

                        foreach ($tags as $fk_tag) {
                            $tag_id = $fk_tag['id'];
                            $delete_tag = GETPOST('delete_tag_' . $tag_id);
                            $level_confid = GETPOST('level_confid_' . $tag_id);

                            $eventconfidentiality = new EventConfidentiality($this->db);
                            $eventconfidentiality->fetch($tag_id);
                            if (!empty($delete_tag)) {
                                if ($confirm_delete_tags) {
                                    $result = $eventconfidentiality->delete();
                                    if ($result < 0) {
                                        $error++;
                                        setEventMessages($eventconfidentiality->error, $eventconfidentiality->errors, 'errors');
                                    }
                                } else {
                                    $error++;
                                    $_POST['sub_action'] = 'delete_tags';
                                    break;
                                }
                            } elseif ($eventconfidentiality->level_confid != $level_confid) {
                                $eventconfidentiality->level_confid = $level_confid;
                                $result = $eventconfidentiality->update($user);
                                if ($result < 0) {
                                    $error++;
                                    setEventMessages($eventconfidentiality->error, $eventconfidentiality->errors, 'errors');
                                }
                            }
                        }

                        if ($error) {
                            $this->db->rollback();
                            $action = 'edit';
                            return 1;
                        } else {
                            $this->db->commit();
                        }
                    }
                }
            }
        }

        return 0;
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
        global $db, $langs, $form, $user;

		$element = $object->element;
		if($element == 'action' && $user->rights->eventconfidentiality->manage) {
			$langs->load("eventconfidentiality@eventconfidentiality");
			dol_include_once('/advancedictionaries/class/dictionary.class.php');
			dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
			dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');
            $out = '';

			if ($object->id > 0) {
				$fk_tags_interne = fetchAllTagForObject($object->id, 0);
				$fk_tags_interne_id = array_column($fk_tags_interne, 'fk_dict_tag_confid');
				$fk_tags_externe = fetchAllTagForObject($object->id, 1);
				$fk_tags_externe_id = array_column($fk_tags_externe, 'fk_dict_tag_confid');
				if($action == 'edit') {
				    $sub_action = GETPOST('sub_action', 'alpha');
                    if ($sub_action == 'delete_tags') {
                        $langs->load("eventconfidentiality@eventconfidentiality");
                        $formquestion = array();
                        $params = array_merge($_POST, $_GET);
                        foreach ($params as $k => $v) {
                            if (!in_array($k, array('action', 'id', 'sub_action'))) {
                                if (is_array($v)) {
                                    foreach ($v as $va) {
                                        $formquestion[] =  array('type' => 'hidden', 'name' => $k.'[]', 'value' => $va);
                                    }
                                } else {
                                    $formquestion[] =  array('type' => 'hidden', 'name' => $k, 'value' => $v);
                                }
                            }
                        }

                        print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $object->id, $langs->trans("DeleteEventConfidentiality"), $langs->trans("ConfirmDeleteEventConfidentiality"), "confirm_delete_tags", '', '', 1);
                    }

					//Tags interne
					$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
					$array_tags = $dictionary->fetch_array('rowid', '{{label}}', array("external"=>NULL), array('label' => 'ASC'));
					$out .= '<tr>';
					$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagInterneLabel") . '</td>';
					$out .= '<td colspan="3"><table class="noborder margintable centpercent">';
					if(count($fk_tags_interne) > 0) {
                        $out .= '<tr class="liste_titre"><th class="liste_titre">Tags</th><th class="liste_titre" width="60%">Mode</th><th class="liste_titre" width="5%">Supprimer</th></tr>';
                        foreach ($fk_tags_interne as $fk_tag) {
                            $tag_id = $fk_tag['id'];
                            $level_confid = GETPOST('level_confid_' . $tag_id, 'int') ? GETPOST('level_confid_' . $tag_id, 'int') : $fk_tag['level_confid'];
                            $out .= '<tr id="' . $tag_id . '">';
                            $out .= '<td>' . $fk_tag['label'] . '</td>';
                            $out .= '<td>';
                            $out .= '<input type="radio" id="level_confid_' . $tag_id . '_0" name="level_confid_' . $tag_id . '" value="0"' . ($level_confid == 0 ? ' checked="checked"' : "") . '><label for="level_confid_' . $tag_id . '_0">' . $langs->trans('EventConfidentialityModeVisible') . '</label>';
                            $out .= '<input type="radio" id="level_confid_' . $tag_id . '_1" name="level_confid_' . $tag_id . '" value="1"' . ($level_confid == 1 ? ' checked="checked"' : "") . '><label for="level_confid_' . $tag_id . '_1">' . $langs->trans('EventConfidentialityModeBlurred') . '</label>';
                            $out .= '<input type="radio" id="level_confid_' . $tag_id . '_2" name="level_confid_' . $tag_id . '" value="2"' . ($level_confid == 2 ? ' checked="checked"' : "") . '><label for="level_confid_' . $tag_id . '_2">' . $langs->trans('EventConfidentialityModeHidden') . '</label>';
                            $out .= '</td>';
                            $out .= '<td class="linecoldelete" align="center"><input type="checkbox" id="delete_tag_' . $tag_id . '" name="delete_tag_' . $tag_id . '" value="1"' . (GETPOST('delete_tag_' . $tag_id) ? ' checked="checked"' : "") . '></td>';
                            $out .= '</tr>';
                        }
                    }
					$out .= '<tr class="liste_titre"><th colspan="3" class="liste_titre">'.$langs->trans("AddNewTagInterne").'</th></tr>';
					$out .= '<td colspan="3">'.$form->multiselectarray('edit_tag_interne', $array_tags, GETPOST('edit_tag_interne', 'array') ? GETPOST('edit_tag_interne', 'array') : array(), '', 0, '', 0, '100%').'</td>';
					$out .= '</table></td>';
					$out .= '</tr>';
					//Tags externe
					$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
					$array_tags = $dictionary->fetch_array('rowid', '{{label}}', array("external"=>1), array('label' => 'ASC'));
					$out .= '<tr>';
					$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagExterneLabel") . '</td>';
					$out .= '<td colspan="3"><table class="noborder margintable centpercent">';
					if(count($fk_tags_externe) > 0) {
						$out .= '<tr class="liste_titre"><th class="liste_titre">Tags</th><th class="liste_titre" width="60%">Mode</th><th class="liste_titre" width="5%">Supprimer</th></tr>';
						foreach ($fk_tags_externe as $fk_tag) {
                            $tag_id = $fk_tag['id'];
                            $level_confid = GETPOST('level_confid_' . $tag_id, 'int') ? GETPOST('level_confid_' . $tag_id, 'int') : $fk_tag['level_confid'];
                            $out .= '<tr id="' . $tag_id . '">';
                            $out .= '<td>' . $fk_tag['label'] . '</td>';
                            $out .= '<td>';
                            $out .= '<input type="radio" id="level_confid_' . $tag_id . '_0" name="level_confid_' . $tag_id . '" value="0"' . ($level_confid == 0 ? ' checked="checked"' : "") . '><label for="level_confid_' . $tag_id . '_0">' . $langs->trans('EventConfidentialityModeVisible') . '</label>';
                            $out .= '<input type="radio" id="level_confid_' . $tag_id . '_1" name="level_confid_' . $tag_id . '" value="1"' . ($level_confid == 1 ? ' checked="checked"' : "") . '><label for="level_confid_' . $tag_id . '_1">' . $langs->trans('EventConfidentialityModeBlurred') . '</label>';
                            $out .= '<input type="radio" id="level_confid_' . $tag_id . '_2" name="level_confid_' . $tag_id . '" value="2"' . ($level_confid == 2 ? ' checked="checked"' : "") . '><label for="level_confid_' . $tag_id . '_2">' . $langs->trans('EventConfidentialityModeHidden') . '</label>';
                            $out .= '</td>';
                            $out .= '<td class="linecoldelete" align="center"><input type="checkbox" id="delete_tag_' . $tag_id . '" name="delete_tag_' . $tag_id . '" value="1"' . (GETPOST('delete_tag_' . $tag_id) ? ' checked="checked"' : "") . '></td>';
                            $out .= '</tr>';
						}
					}
					$out .= '<tr class="liste_titre"><th colspan="3" class="liste_titre">'.$langs->trans("AddNewTagExterne").'</th></tr>';
					$out .= '<td colspan="3">'.$form->multiselectarray('edit_tag_externe', $array_tags, GETPOST('edit_tag_externe', 'array') ? GETPOST('edit_tag_externe', 'array') : array(), '', 0, '', 0, '100%').'</td>';
					$out .= '</table></td>';
					$out .= '</tr>';
				} else {
					//Tags interne
					$tags = "";
					foreach ($fk_tags_interne as $fk_tag) {
						$tags .= '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #ff0000;"><img src="/htdocs/theme/owntheme/img/object_category.png" alt="" title="" class="inline-block valigntextbottom">'.$fk_tag['label'].' : '.$fk_tag['level_label'].'</li>';
					}
					$out .= '<tr>';
					$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagInterneLabel") . '</td>';
					$out .= '<td colspan="3"><div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr" style="list-style:none;">'.$tags.'</ul></div></td>';
					$out .= '</tr>';
					//Tags externe
					$tags = "";
					foreach ($fk_tags_externe as $fk_tag) {
						$tags .= '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #ff0000;"><img src="/htdocs/theme/owntheme/img/object_category.png" alt="" title="" class="inline-block valigntextbottom">'.$fk_tag['label'].' : '.$fk_tag['level_label'].'</li>';
					}
					$out .= '<tr>';
					$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagExterneLabel") . '</td>';
					$out .= '<td colspan="3"><div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr" style="list-style:none;">'.$tags.'</ul></div></td>';
					$out .= '</tr>';
				}
			} else {
				//Tags interne
				$array_tags = array();
				$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
				$array_tags = $dictionary->fetch_array('rowid', '{{label}}', array("external"=>NULL), array('label' => 'ASC'));

				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagInterneLabel") . '</td>';
				$out .= '<td colspan="3">'.$form->multiselectarray('add_tag_interne', $array_tags, array(), '', 0, '', 0, '100%').'</td>';
				$out .= '</tr>';

				//Tags externe
				$array_tags = array();
				$dictionary = Dictionary::getDictionary($db, 'eventconfidentiality', 'eventconfidentialitytag', 0);
				$array_tags = $dictionary->fetch_array('rowid', '{{label}}', array("external"=>1), array('label' => 'ASC'));

				$out .= '<tr>';
				$out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagExterneLabel") . '</td>';
				$out .= '<td colspan="3">'.$form->multiselectarray('add_tag_externe', $array_tags, array(), '', 0, '', 0, '100%').'</td>';
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

        // $user_f = isset(DolibarrApiAccess::$user) ? DolibarrApiAccess::$user : $user;
        $user_f = isset($user) ? $user : DolibarrApiAccess::$user;
        if (empty($user_f->array_options) && $user_f->id > 0) {
            $user_f->fetch_optionals();
        }

		$element = $object->element;

		//Get context execution
		$url = dirname($_SERVER['REQUEST_URI']);
		$parts = explode('/',$url);

		$langs->load("eventconfidentiality@eventconfidentiality");
		dol_include_once('/advancedictionaries/class/dictionary.class.php');
		dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
		dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');

		if($object->id > 0) {
			$mode = 2;
			$user_tags = explode(",",$user_f->array_options['options_user_tag']);
			$usergroup = new UserGroup($db);
			$usergroups = $usergroup->listGroupsForUser($user_f->id);
			foreach($usergroups as $group) {
				$user_tags = array_merge($user_tags,explode(",",$group->array_options['options_group_tag']));
			}
			$externe = (empty($user_f->socid)?0:1); //Utilisateur interne ou externe
			$fk_tags = fetchAllTagForObject($object->id, $externe);
			foreach($fk_tags as $fk_tag) {
				if(in_array($fk_tag['fk_dict_tag_confid'],$user_tags)) { //Si on a un tag en commun et que ce tag est interne
					$mode = min($mode,$fk_tag['level_confid']);//Si l'utilisateur un tag en commun avec l'event on considère la visilibité maximale parmi les tags en commun
				}
			}

			//Si aucune confidentialité n'est renseigné sur l'event, pour éviter que ce dernier soit inaccessible, on le laisse accessible (mode 0) pour les utilisateurs internes uniquement
			if(count($fk_tags)==0 && $externe==0) $mode=0;
			//Gestion du mode
			if($mode == 2) {
				if (end($parts) == "action") {
					accessforbidden();
				} else {
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
				}
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

        // $user_f = isset(DolibarrApiAccess::$user) ? DolibarrApiAccess::$user : $user;
        $user_f = isset($user) ? $user : DolibarrApiAccess::$user;
        if (empty($user_f->array_options) && $user_f->id > 0) {
            $user_f->fetch_optionals();
        }

		$element = $object->element;

		$langs->load("eventconfidentiality@eventconfidentiality");
		dol_include_once('/advancedictionaries/class/dictionary.class.php');
		dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
		dol_include_once('/eventconfidentiality/lib/eventconfidentiality.lib.php');

		if($object->id > 0) {
			$mode = 2;
			$user_tags = explode(",",$user_f->array_options['options_user_tag']);
			$usergroup = new UserGroup($db);
			$usergroups = $usergroup->listGroupsForUser($user_f->id);
			foreach($usergroups as $group) {
				$user_tags = array_merge($user_tags,explode(",",$group->array_options['options_group_tag']));
			}
			$externe = (empty($user_f->socid)?0:1); //Utilisateur interne ou externe
			$fk_tags = fetchAllTagForObject($object->id, $externe);
			foreach($fk_tags as $fk_tag) {
				if(in_array($fk_tag['fk_dict_tag_confid'],$user_tags)) { //Si on a un tag en commun et que ce tag est interne
					$mode = min($mode,$fk_tag['level_confid']);//Si l'utilisateur un tag en commun avec l'event on considère la visilibité maximale parmi les tags en commun
				}
			}

			//Si aucune confidentialité n'est renseigné sur l'event, pour éviter que ce dernier soit inaccessible, on le laisse accessible (mode 0) pour les utilisateurs internes uniquement
			if(count($fk_tags)==0 && $externe==0) $mode=0;

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
