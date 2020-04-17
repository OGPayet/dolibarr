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
 * \file    htdocs/requestmanager/class/actions_requestmanager.class.php
 * \ingroup requestmanager
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsRequestManager
 */
class ActionsRequestManager
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
     * Overloading the updateSession function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function updateSession($parameters, &$object, &$action, $hookmanager)
    {
        return $this->_managerModuleHistory($parameters, $object, $action, $hookmanager);
    }

    /**
     * Overloading the afterLogin function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function afterLogin($parameters, &$object, &$action, $hookmanager)
    {
        return $this->_managerModuleHistory($parameters, $object, $action, $hookmanager);
    }

    /**
     * Manage for the module History
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    protected function _managerModuleHistory($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        if ($conf->history->enabled) {
            if (preg_match('/\/history\/history\.php/i', $_SERVER["PHP_SELF"]) && GETPOST('type_object') == 'requestmanager') {
                dol_include_once('/requestmanager/lib/requestmanager.lib.php');
                dol_include_once('/requestmanager/class/requestmanager.class.php');
            }
        }

        return 0;
    }

    /**
     * Overloading the addSearchEntry function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function addSearchEntry($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        $langs->load('requestmanager@requestmanager');

        $search_boxvalue = $parameters['search_boxvalue'];
        //$arrayresult = $parameters['arrayresult'];

        $arrayresult['searchintorequestmanager']=array(
            'position'=>16,
            'shortcut'=>'R',
            'img'=>'object_requestmanager@requestmanager',
            'label'=>$langs->trans("RequestManagerSearchIntoRequests", $search_boxvalue),
            'text'=>img_picto('','object_requestmanager@requestmanager').' '.$langs->trans("RequestManagerSearchIntoRequests", $search_boxvalue),
            'url'=>dol_buildpath('/requestmanager/list.php', 1).($search_boxvalue?'?sall='.urlencode($search_boxvalue):'')
        );

        $this->results = $arrayresult;

        return 0; // or return 1 to replace standard code
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('thirdpartycard', $contexts) ||
            in_array('commcard', $contexts) ||
            in_array('suppliercard', $contexts) ||
            in_array('propalcard', $contexts) ||
            in_array('ordercard', $contexts) ||
            in_array('invoicecard', $contexts) ||
            in_array('interventioncard', $contexts) ||
            in_array('contractcard', $contexts)
        ) {
            $langs->load('requestmanager@requestmanager');

            $socid = $object->element == 'societe' ? $object->id : $object->socid;
            $origin = $object->element != 'societe' ? '&origin=' . urlencode($object->element) . '&originid=' . $object->id : '';

            if ($user->rights->requestmanager->creer)
                print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/requestmanager/createfast.php', 2) . '?action=createfast&socid=' . $socid . '&socid_origin=' . $socid . $origin . '">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
            else
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
        }

        return 0;
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
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('actioncard', $contexts)) {
            $id = GETPOST('id', 'int');

			if($id > 0) {
				$object->fetch($id);

				if ($object->type_code == 'AC_RM_OUT' || $object->type_code == 'AC_RM_PRIV' || $object->type_code == 'AC_RM_IN') {
					$langs->load('requestmanager@requestmanager');

					if ($action == 'create' || $action == 'add') {
						$this->errors[] = $langs->trans('RequestManagerErrorCanOnlyCreateMessageFromRequest');
						$action = '';
						return -1;
					} elseif ($action == 'edit' || $action == 'update') {
						$this->errors[] = $langs->trans('RequestManagerErrorMessageCanNotBeModified');
						$action = '';
						return -1;
						/*$error = 0;
						$cancel = GETPOST('cancel', 'alpha');
						if (empty($cancel) && $user->rights->requestmanager->creer) {
							dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
							$requestmanagermessage = new RequestManagerMessage($this->db);

							require_once DOL_DOCUMENT_ROOT . '/comm/action/class/cactioncomm.class.php';
							$cactioncomm = new CActionComm($this->db);

							$backtopage = GETPOST('backtopage', 'alpha');

							$fulldayevent = GETPOST('fullday');
							$aphour = GETPOST('aphour');
							$apmin = GETPOST('apmin');
							$p2hour = GETPOST('p2hour');
							$p2min = GETPOST('p2min');
							$percentage = in_array(GETPOST('status'), array(-1, 100)) ? GETPOST('status') : (in_array(GETPOST('complete'), array(-1, 100)) ? GETPOST('complete') : GETPOST("percentage"));    // If status is -1 or 100, percentage is not defined and we must use status

							// Clean parameters
							if ($aphour == -1) $aphour = '0';
							if ($apmin == -1) $apmin = '0';
							if ($p2hour == -1) $p2hour = '0';
							if ($p2min == -1) $p2min = '0';

							$requestmanagermessage->fetch($id);
							$requestmanagermessage->fetch_userassigned();

							$datep = dol_mktime($fulldayevent ? '00' : $aphour, $fulldayevent ? '00' : $apmin, 0, $_POST["apmonth"], $_POST["apday"], $_POST["apyear"]);
							$datef = dol_mktime($fulldayevent ? '23' : $p2hour, $fulldayevent ? '59' : $p2min, $fulldayevent ? '59' : '0', $_POST["p2month"], $_POST["p2day"], $_POST["p2year"]);

							$requestmanagermessage->fk_action = dol_getIdFromCode($this->db, GETPOST("actioncode"), 'c_actioncomm');
							$requestmanagermessage->label = GETPOST("label");
							$requestmanagermessage->datep = $datep;
							$requestmanagermessage->datef = $datef;
							$requestmanagermessage->percentage = $percentage;
							$requestmanagermessage->priority = GETPOST("priority");
							$requestmanagermessage->fulldayevent = GETPOST("fullday") ? 1 : 0;
							$requestmanagermessage->location = GETPOST('location');
							$requestmanagermessage->socid = GETPOST("socid");
							$requestmanagermessage->contactid = GETPOST("contactid", 'int');
							//$requestmanagermessage->societe->id = $_POST["socid"];			// deprecated
							//$requestmanagermessage->contact->id = $_POST["contactid"];		// deprecated
							$requestmanagermessage->fk_project = GETPOST("projectid", 'int');
							$requestmanagermessage->note = GETPOST("note");
							$requestmanagermessage->pnote = GETPOST("note");
							$requestmanagermessage->fk_element = GETPOST("fk_element");
							$requestmanagermessage->elementtype = GETPOST("elementtype");

							if (!$datef && $percentage == 100) {
								$error++;
								$donotclearsession = 1;
								setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateEnd")), $requestmanagermessage->errors, 'errors');
								$action = 'edit';
							}

							$transparency = (GETPOST("transparency") == 'on' ? 1 : 0);

							// Users
							$listofuserid = array();
							if (!empty($_SESSION['assignedtouser']))    // Now concat assigned users
							{
								// Restore array with key with same value than param 'id'
								$tmplist1 = json_decode($_SESSION['assignedtouser'], true);
								$tmplist2 = array();
								foreach ($tmplist1 as $key => $val) {
									if ($val['id'] > 0 && $val['id'] != $assignedtouser) $listofuserid[$val['id']] = $val;
								}
							} else {
								$assignedtouser = (!empty($requestmanagermessage->userownerid) && $requestmanagermessage->userownerid > 0 ? $requestmanagermessage->userownerid : 0);
								if ($assignedtouser) $listofuserid[$assignedtouser] = array('id' => $assignedtouser, 'mandatory' => 0, 'transparency' => ($user->id == $assignedtouser ? $transparency : ''));    // Owner first
							}

							$requestmanagermessage->userassigned = array();
							$requestmanagermessage->userownerid = 0; // Clear old content
							$i = 0;
							foreach ($listofuserid as $key => $val) {
								if ($i == 0) $requestmanagermessage->userownerid = $val['id'];
								$requestmanagermessage->userassigned[$val['id']] = array('id' => $val['id'], 'mandatory' => 0, 'transparency' => ($user->id == $val['id'] ? $transparency : ''));
								$i++;
							}

							$requestmanagermessage->transparency = $transparency;        // We set transparency on event (even if we can also store it on each user, standard says this property is for event)

							if (!empty($conf->global->AGENDA_ENABLE_DONEBY)) {
								if (GETPOST("doneby")) $requestmanagermessage->userdoneid = GETPOST("doneby", "int");
							}

							// Check parameters
							if (!GETPOST('actioncode') > 0) {
								$error++;
								$donotclearsession = 1;
								$action = 'edit';
								setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
							} else {
								$result = $cactioncomm->fetch(GETPOST('actioncode'));
							}
							if (empty($requestmanagermessage->userownerid)) {
								$error++;
								$donotclearsession = 1;
								$action = 'edit';
								setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ActionsOwnedBy")), null, 'errors');
							}

							// Fill array 'array_options' with data from add form
							$extrafields = new ExtraFields($this->db);
							$extralabels = $extrafields->fetch_name_optionals_label($requestmanagermessage->table_element);
							$ret = $extrafields->setOptionalsFromPost($extralabels, $requestmanagermessage);
							if ($ret < 0) $error++;

							if (!$error) {
								$this->db->begin();

								$result = $requestmanagermessage->update($user);

								if ($result > 0) {
									unset($_SESSION['assignedtouser']);

									$this->db->commit();
								} else {
									setEventMessages($requestmanagermessage->error, $requestmanagermessage->errors, 'errors');
									$this->db->rollback();
								}
							}
						}

						if (!$error) {
							if (!empty($backtopage)) {
								unset($_SESSION['assignedtouser']);
								header("Location: " . $backtopage);
								exit;
							}
						}

						return 1;*/
					} elseif ($action == 'confirm_delete' && GETPOST("confirm") == 'yes' && $user->rights->requestmanager->creer) {
						dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
						$requestmanagermessage = new RequestManagerMessage($this->db);

						$id = GETPOST('id', 'int');
						$requestmanagermessage->fetch($id);

						$result = $requestmanagermessage->delete();

						if ($result >= 0) {
							header("Location: index.php");
							exit;
						} else {
							setEventMessages($requestmanagermessage->error, $requestmanagermessage->errors, 'errors');
						}
						return 1;
					}
				}
			}
        }

        return 0;
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('actioncard', $contexts) &&
            ($object->type_code == 'AC_RM_OUT' || $object->type_code == 'AC_RM_PRIV' || $object->type_code == 'AC_RM_IN') &&
            $action != "create" && empty($object->context['requestmanager_hook'])
        ) {
            $langs->load('requestmanager@requestmanager');

            dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
            $requestManagerMessage = new RequestManagerMessage($this->db);
            $requestManagerMessage->fetch($object->id);
            $requestManagerMessage->fetch_optionals();

            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            $extralabels = $extrafields->fetch_name_optionals_label($requestManagerMessage->table_element);

            $requestManagerMessage->fetch_knowledge_base(1);
            print '<tr><td class="nowrap" class="titlefield">' . $langs->trans("RequestManagerMessageKnowledgeBase") . '</td>';
//            if ($action == 'edit') {
//                print '<td>';
//            } else {
            print '<td colspan="3">';
            $toprint = array();
            foreach ($requestManagerMessage->knowledge_base_list as $knowledge_base) {
                $toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories style="background: #aaa">' . $knowledge_base->fields['code'] . ' - ' . $knowledge_base->fields['title'] . '</li>';
//                }
                print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
            }
            print '</td>';
            print '</tr>';

            print '<tr><td class="nowrap" class="titlefield">' . $langs->trans("RequestManagerMessageNotify") . '</td>';
            print ($action == "edit" ? '<td>' : '<td colspan="3">');
            print '<input type="checkbox" id="rm_notify_assigned" name="rm_notify_assigned" value="1"' . (!empty($requestManagerMessage->notify_assigned) ? ' checked="checked"' : '') . ' disabled="disabled" />';
            print '&nbsp;' . $langs->trans("RequestManagerAssigned");
            print ' &nbsp; ';
            print '<input type="checkbox" id="rm_notify_requesters" name="rm_notify_requesters" value="1"' . (!empty($requestManagerMessage->notify_requesters) ? ' checked="checked"' : '') . ' disabled="disabled" />';
            print '&nbsp;' . $langs->trans("RequestManagerRequesterContacts");
            print ' &nbsp; ';
            print '<input type="checkbox" id="rm_notify_watchers" name="rm_notify_watchers" value="1"' . (!empty($requestManagerMessage->notify_watcher) ? ' checked="checked"' : '') . ' disabled="disabled" />';
            print '&nbsp;' . $langs->trans("RequestManagerWatcherContacts");
            print "</td></tr>";

            // Other attributes
//            if ($action == "edit") {
//                print $requestManagerMessage->showOptionals($extrafields, 'edit', array('colspan'=>3), 'rm_message_');
//            } else {
            print $requestManagerMessage->showOptionals($extrafields, 'view', array('colspan' => 3));
//            }

            return 1;
        } elseif (in_array('contractcard', $contexts)) {
            global $extrafields;

            if (empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE)) {
                unset($extrafields->attributes[$object->table_element]['label']['rm_timeslots_separator']);
                unset($extrafields->attributes[$object->table_element]['label']['rm_timeslots_periods']);
                unset($extrafields->attribute_label['rm_timeslots_separator']);
                unset($extrafields->attribute_label['rm_timeslots_periods']);
            } else {
                global $form;

                if (!isset($form)) {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
                    $form = new Form($this->db);
                }
                $langs->load('requestmanager@requestmanager');
                $help = str_replace("'", "\\'", $form->textwithpicto('', $langs->trans("RequestManagerTimeSlotsPeriodsDesc"), 1, 'help', '', 0, 2, 'help_timeslots_periods'));

                $this->resprints = <<<SCRIPT
            <script type="text/javascript">
                $(document).ready(function () {
                    // Help button
                    var rm_timeslots_periods = $("td a[href*='&action=edit_extras&attribute=rm_timeslots_periods']").closest('tr').find('td:first-child');
                    if (!rm_timeslots_periods.length) {
                        rm_timeslots_periods = $("textarea#options_rm_timeslots_periods").closest('tr').find('table td:first-child');
                    }
                    rm_timeslots_periods.append(' $help');

                    // Disabled CKEDITOR
                    if (typeof CKEDITOR == "object") {
                        setTimeout(function () {
                            if (typeof CKEDITOR.instances != "undefined" && "options_rm_timeslots_periods" in CKEDITOR.instances) {
                                CKEDITOR.instances["options_rm_timeslots_periods"].destroy();
                            }
                        }, 500);
                    }
                });
            </script>
SCRIPT;
            }
        }

        // Management of the user group(s) in charge for the planning
        //----------------------------------------------------------------------
        if (!empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE) && $user->rights->requestmanager->usergroup_in_charge->lire && (in_array('thirdpartycard', $contexts) || in_array('commcard', $contexts))) {
            $langs->load('requestmanager@requestmanager');
            dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
            $formrequestmanager = new FormRequestManager($this->db);

            $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $requestmanagerrequesttype = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerrequesttype');
            $requestmanagerrequesttype->fetch_lines(1);

            dol_include_once('/requestmanager/class/requestmanagerplanning.class.php');
            $requestmanagerplanning = new RequestManagerPlanning($this->db);
            $usergroups_in_charge = $action != 'create' ? $requestmanagerplanning->getUserGroupsInChargeForCompany($object->id) : array();

            // User groups in charge
            if ($action == 'create' || $action == 'edit') {
                foreach ($requestmanagerrequesttype->lines as $request_type) {
                    if (!in_array($request_type->id, $request_types_planned)) continue;
                    $LabelTagName = 'RequestManagerPlanningUserGroupsInChargeLabel_' . $request_type->fields['code'];
                    print '<tr><td>' . $langs->trans($langs->trans($LabelTagName) != $LabelTagName ? $LabelTagName : 'RequestManagerPlanningUserGroupsInChargeLabel', $request_type->fields['label']) . '</td>';
                    print '<td colspan="3">';
                    $usergroups_in_charge_for_request_type = isset($Post['usergroups_in_charge_' . $request_type->id]) ? $Post['usergroups_in_charge_' . $request_type->id] : (isset($usergroups_in_charge[$request_type->id]) ? $usergroups_in_charge[$request_type->id] : array());
                    print $formrequestmanager->multiselect_dolgroups($usergroups_in_charge_for_request_type, 'usergroups_in_charge_' . $request_type->id);
                    print '</td></tr>';
                }
            } else {
                foreach ($requestmanagerrequesttype->lines as $request_type) {
                    if (!in_array($request_type->id, $request_types_planned)) continue;
                    $LabelTagName = 'RequestManagerPlanningUserGroupsInChargeLabel_' . $request_type->fields['code'];
                    print '<tr><td>';
                    print '<table class="nobordernopadding" width="100%"><tr><td>';
                    print $langs->trans($langs->trans($LabelTagName) != $LabelTagName ? $LabelTagName : 'RequestManagerPlanningUserGroupsInChargeLabel', $request_type->fields['label']);
                    print '</td>';
                    if ($action != 'edit_usergroups_in_charge_' . $request_type->id && $user->rights->requestmanager->usergroup_in_charge->manage)
                        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit_usergroups_in_charge_' . $request_type->id . '&socid=' . $object->id . '">' . img_edit('', 1) . '</a></td>';
                    print '</tr></table>';
                    print '</td><td>';
                    if ($action == 'edit_usergroups_in_charge_' . $request_type->id && $user->rights->requestmanager->usergroup_in_charge->manage) {
                        print '<form name="edit_usergroups_in_charge" action="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '" method="post">';
                        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="set_edit_usergroups_in_charge">';
                        $usergroups_in_charge_for_request_type = isset($Post['usergroups_in_charge_' . $request_type->id]) ? $Post['usergroups_in_charge_' . $request_type->id] : (isset($usergroups_in_charge[$request_type->id]) ? $usergroups_in_charge[$request_type->id] : array());
                        print $formrequestmanager->multiselect_dolgroups($usergroups_in_charge_for_request_type, 'usergroups_in_charge_' . $request_type->id);
                        print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
                        print '</form>';
                    } else {
                        require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
                        $usergroup_static = new UserGroup($this->db);
                        $toprint = array();
                        $usergroups_in_charge_for_request_type = isset($usergroups_in_charge[$request_type->id]) ? $usergroups_in_charge[$request_type->id] : array();
                        foreach ($usergroups_in_charge_for_request_type as $usergroup_id) {
                            $usergroup_static->fetch($usergroup_id);
                            $toprint[] = $usergroup_static->name;
                        }
                        print implode(', ', $toprint);
                    }
                    print '</td></tr>';
                }
            }
        }

        return 0;
    }

    /**
     * Overloading the printTopRightMenu function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function printTopRightMenu($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        if (in_array('toprightmenu', explode(':', $parameters['context']))) {
            if ($user->rights->requestmanager->lire) {
                $langs->load('requestmanager@requestmanager');

                // Request status
                //----------------------------------------------------------------------
                $my_request_updated_url  = dol_buildpath('/requestmanager/lists_follow.php', 1);
                $my_request_updated_text  = str_replace('"', '\\"', $langs->trans('RequestManagerMenuTopRequestsFollow'));

                // Last view date
                $user->fetch_optionals();
                $lastViewDate = isset($user->array_options['options_rm_last_check_follow_list_date']) ? $user->array_options['options_rm_last_check_follow_list_date'] : '';
                if (is_string($lastViewDate)) $lastViewDate = strtotime($lastViewDate);

                dol_include_once('/requestmanager/class/requestmanager.class.php');
                $requestManager = new RequestManager($this->db);

                $isListsFollowModified  = $requestManager->isListsFollowModified($lastViewDate) ? 1 : 0;
                $nbRequests             = $requestManager->countMyAssignedRequests(array(RequestManager::STATUS_TYPE_INITIAL, RequestManager::STATUS_TYPE_IN_PROGRESS));

                // Create request
                //----------------------------------------------------------------------
                $create_request_url  = dol_buildpath('/requestmanager/createfast.php', 1) . '?action=createfast';
                $create_request_text  = str_replace('"', '\\"', $langs->trans('RequestManagerMenuTopCreateFast'));
                $create_request_img  = img_picto($langs->trans('RequestManagerMenuTopCreateFast'), 'filenew.png');


                // <li class="tmenu" id="mainmenutd_requestmanager"><div class="tmenuleft tmenusep"></div><div class="tmenucenter"><a class="tmenuimage" tabindex="-1" href="/synergies-tech/custom/requestmanager/list.php?mylist=1&amp;idmenu=402&amp;mainmenu=requestmanager&amp;leftmenu="><div class="mainmenu requestmanager topmenuimage"><span class="mainmenu tmenuimage" id="mainmenuspan_requestmanager"></span></div></a><a class="tmenu" id="mainmenua_requestmanager" href="/synergies-tech/custom/requestmanager/list.php?mylist=1&amp;idmenu=402&amp;mainmenu=requestmanager&amp;leftmenu="><span class="mainmenuaspan">Demandes</span></a></div></li>

                // Add create request button and my request status button
                //----------------------------------------------------------------------
                $out = <<<SCRIPT
            <script type="text/javascript">
		$(document).ready(function () {
		    var requestmanager_my_request_updated = $isListsFollowModified;
			var requestmanager_menu_div = $("#mainmenutd_requestmanager");

			// Add request status button
			//requestmanager_menu_div.after('<li class="tmenu" id="mainmenutd_requestmanager_my_request_updated"><div class="tmenucenter"><a class="tmenu" href="$my_request_updated_url" title="$my_request_updated_text"><span class="mainmenuaspan">$nbRequests</span></a></div></li>');
			requestmanager_menu_div.after('<li class="tmenu" id="mainmenutd_requestmanager_my_request_updated"><a class="tmenuimage" href="$my_request_updated_url" title="$my_request_updated_text"><div class="mainmenuaspan">$nbRequests</div></a></li>');

			// Add create request button in same tab
			//requestmanager_menu_div.after('<li class="tmenu" id="mainmenutd_requestmanager_create"><div class="tmenucenter"><a class="tmenuimage" tabindex="-1" href="$create_request_url" target="_blank" title="$create_request_text"><div class="mainmenu topmenuimage"><span class="mainmenu tmenuimage">$create_request_img</span></div></a></div></li>');
			requestmanager_menu_div.after('<li class="tmenu" id="mainmenutd_requestmanager_create"><a class="tmenuimage" href="$create_request_url" target="_blank" title="$create_request_text">$create_request_img</a></li>');

					// Add create request button in same tab
			//requestmanager_menu_div.after('<li class="tmenu" id="mainmenutd_requestmanager_create"><div class="tmenucenter"><a class="tmenuimage" tabindex="-1" href="$create_request_url" title="$create_request_text"><div class="mainmenu topmenuimage"><span class="mainmenu tmenuimage">$create_request_img</span></div></a></div></li>');
			requestmanager_menu_div.after('<li class="tmenu" id="mainmenutd_requestmanager_create"><a class="tmenuimage" href="$create_request_url" title="$create_request_text">$create_request_img</a></li>');

			// Blink managment if new status of my request
			var requestmanager_my_request_updated_a = $("#mainmenutd_requestmanager_my_request_updated a");
			var requestmanager_my_request_updated_blink = null;
			requestmanager_update_my_request_updated();

			function requestmanager_update_my_request_updated() {
                        if (requestmanager_my_request_updated && !requestmanager_my_request_updated_blink) {
                            // Start blink
                            requestmanager_my_request_updated_blink = setInterval(function() { requestmanager_my_request_updated_a.toggleClass("rm_my_request_updated_blink_color"); }, 1000);
                        } else if (!requestmanager_my_request_updated && requestmanager_my_request_updated_blink) {
                            // Stop blink
                            clearInterval(requestmanager_my_request_updated_blink);
                            requestmanager_my_request_updated_blink = null;
                        }
			}
                });
            </script>
SCRIPT;

                // Chronometer
                //----------------------------------------------------------------------
                if (!empty($conf->global->REQUESTMANAGER_CHRONOMETER_ACTIVATE) && isset($_SESSION['requestmanager_chronometer_activated'])) {
                    if (GETPOST('rm_action', 'alpha') == 'requestmanager_stop_chronometer') {
                        unset($_SESSION['requestmanager_chronometer_activated']);
                    } else {
                        $is_create_request_page = $_SERVER['PHP_SELF'] == dol_buildpath('/requestmanager/createfast.php', 1);

                        $parameters = array_merge($_POST, $_GET);
                        $parameters['rm_action'] = 'requestmanager_stop_chronometer';
                        $request_chronometer_stop_url = $is_create_request_page ? '#' : ($_SERVER['PHP_SELF'] . '?' . http_build_query($parameters));
                        $request_chronometer_stop_text = $is_create_request_page ? '' : str_replace('"', '\\"', $langs->trans('RequestManagerChronometerStop'));
                        $elapsed_time = dol_now() - $_SESSION['requestmanager_chronometer_activated'];
                        $limit_time = (!empty($conf->global->REQUESTMANAGER_CHRONOMETER_TIME) ? $conf->global->REQUESTMANAGER_CHRONOMETER_TIME : 20) * 60;

                        $out .= <<<SCRIPT
            <script type="text/javascript">
                $(document).ready(function () {
                    var requestmanager_chronometer_elpased_time = $elapsed_time;
                    var requestmanager_chronometer_limit_time = $limit_time;

                    // Add chronometer button
                    $("#mainmenutd_requestmanager_create").after('<li class="tmenu" id="mainmenutd_requestmanager_chronometer"><a class="tmenuimage" href="$request_chronometer_stop_url" title="$request_chronometer_stop_text"><div class="mainmenuaspan">' + rmGetDurationText(requestmanager_chronometer_elpased_time) + '</div></a></li>');

                    var requestmanager_chronometer_a = $("#mainmenutd_requestmanager_chronometer a");
                    var requestmanager_chronometer_text = $("#mainmenutd_requestmanager_chronometer a div.mainmenuaspan");
                    var requestmanager_chronometer_blink = null;

                    // start chronometer
                    setInterval(function() {
                        requestmanager_chronometer_elpased_time += 1;
                        requestmanager_chronometer_text.text(rmGetDurationText(requestmanager_chronometer_elpased_time));

                        if (requestmanager_chronometer_limit_time <= requestmanager_chronometer_elpased_time && !requestmanager_chronometer_blink) {
                            // Start blink
                            requestmanager_chronometer_blink = setInterval(function() { requestmanager_chronometer_a.toggleClass("rm_chronometer_blink_color"); }, 1000);
                        }
                    }, 1000);

                    function rmGetDurationText(time) {
                        var hours = Math.floor(time / 3600);
                        time -= hours * 3600;

                        var minutes = Math.floor(time / 60);
                        time -= minutes * 60;

                        var seconds = time;

                        return (hours > 9 ? hours : "0" + hours) + ":" + (minutes > 9 ? minutes : "0" + minutes) + ":" + (seconds > 9 ? seconds : "0" + seconds);
                    }
                });
            </script>
SCRIPT;
                    }
                }

                $this->resprints = $out;
            }
        }

        // Management of the user group(s) in charge for the planning
        //----------------------------------------------------------------------
        if (!empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE) && (in_array('thirdpartycard', $contexts) || in_array('commcard', $contexts))) {
            if ($action == 'set_edit_usergroups_in_charge' && $user->rights->requestmanager->usergroup_in_charge->manage) {
                if (!($object->id > 0)) {
                    $id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
                    $object->fetch($id);
                }
                $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();
                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $requestmanagerrequesttype = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerrequesttype');
                $requestmanagerrequesttype->fetch_lines(1);

                dol_include_once('/requestmanager/class/requestmanagerplanning.class.php');
                $requestmanagerplanning = new RequestManagerPlanning($this->db);

                foreach ($requestmanagerrequesttype->lines as $request_type) {
                    if (!in_array($request_type->id, $request_types_planned)) continue;

                    $usergroups_in_charge = GETPOST('usergroups_in_charge_' . $request_type->id, 'array');

                    // Save users in charge for the request type
                    if ($requestmanagerplanning->setUserGroupsInChargeForCompany($object->id, $request_type->id, $usergroups_in_charge) > 0) {
                        header('Location: ' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id);
                        exit;
                    } else {
                        setEventMessages($requestmanagerplanning->error, $requestmanagerplanning->errors, 'errors');
                    }
                }

                return 1;
            }
        }

        return 0;
    }

    /**
     * Overloading the addEventConfidentialityElementOrigin function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function addEventConfidentialityElementOrigin($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if (in_array('eventconfidentialitydao', explode(':', $parameters['context']))) {
            $this->results = array(
                'requestmanager' => $langs->trans('RequestManagerRequest'),
            );

            return 1;
        }

        return 0;
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function showLinkToObjectBlock($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        $contexts = explode(':', $parameters['context']);

        $thirdparty = null;
        $thirdparty_benefactor = null;
        $listofidcompanytoscan = array();
        $possiblelinks = array();

        if (!is_object($object->thirdparty)) $object->fetch_thirdparty();
        $thirdparty = $object->thirdparty;
        if (in_array('requestmanagercard', $contexts) && !is_object($object->thirdparty_benefactor) && method_exists($object, "fetch_thirdparty_benefactor")) {
            if (!is_object($object->thirdparty_benefactor)) $object->fetch_thirdparty_benefactor();
            $thirdparty_benefactor = $object->thirdparty_benefactor;
        } elseif ($conf->companyrelationships->enabled) {
            if (empty($object->array_options) && method_exists($object, "fetch_optionals")) {
                $object->fetch_optionals();
            }
            if (!empty($object->array_options['options_companyrelationships_fk_soc_benefactor']) && $object->array_options['options_companyrelationships_fk_soc_benefactor'] > 0) {
                $thirdparty_benefactor = new Societe($this->db);
                $thirdparty_benefactor->fetch($object->array_options['options_companyrelationships_fk_soc_benefactor']);
            }
        }

        if (is_object($thirdparty) && !empty($thirdparty->id) && $thirdparty->id > 0) {
            $listofidcompanytoscan[$thirdparty->id] = $thirdparty->id;
            if (($thirdparty->parent > 0) && !empty($conf->global->THIRDPARTY_INCLUDE_PARENT_IN_LINKTO)) $listofidcompanytoscan[$thirdparty->parent] = $thirdparty->parent;
        }
        if (is_object($thirdparty_benefactor) && !empty($thirdparty_benefactor->id) && $thirdparty_benefactor->id > 0) {
            $listofidcompanytoscan[$thirdparty_benefactor->id] = $thirdparty_benefactor->id;
            if (($thirdparty_benefactor->parent > 0) && !empty($conf->global->THIRDPARTY_INCLUDE_PARENT_IN_LINKTO)) $listofidcompanytoscan[$thirdparty_benefactor->parent] = $thirdparty_benefactor->parent;
        }
        if (($object->fk_project > 0) && !empty($conf->global->THIRDPARTY_INCLUDE_PROJECT_THIRDPARY_IN_LINKTO)) {
            include_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
            $tmpproject = new Project($this->db);
            $tmpproject->fetch($object->fk_project);
            if ($tmpproject->socid > 0) $listofidcompanytoscan[$tmpproject->socid] = $tmpproject->socid;
            unset($tmpproject);
        }

        $listofidcompanytoscan = implode(',', $listofidcompanytoscan);

        $possiblelinks['requestmanager'] = array(
            'enabled' => $conf->requestmanager->enabled,
            'perms' => 1,
            'label' => 'LinkToRequestManager',
            'sql' => "SELECT s.rowid AS socid, GROUP_CONCAT(s.nom SEPARATOR ', ') AS `name`, s.client, t.rowid, t.ref, CONCAT(crmrt.label, ' - ', t.label) AS ref_client FROM " . MAIN_DB_PREFIX . "societe as s" .
                " INNER JOIN  " . MAIN_DB_PREFIX . "requestmanager as t ON (t.fk_soc = s.rowid OR t.fk_soc_benefactor = s.rowid)" .
                " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                "   ON (ee.sourcetype = 'requestmanager' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                "   OR (ee.targettype = 'requestmanager' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                " LEFT JOIN  " . MAIN_DB_PREFIX . "c_requestmanager_request_type as crmrt ON crmrt.rowid = t.fk_type" .
                ' WHERE (t.fk_soc IN (' . $listofidcompanytoscan . ') OR t.fk_soc_benefactor IN (' . $listofidcompanytoscan . ')) AND t.entity IN (' . getEntity('requestmanager') . ')' .
                ' AND ee.rowid IS NULL' .
                ' GROUP BY t.rowid',
        );

        if (in_array('requestmanagercard', $contexts) && empty($conf->global->REQUESTMANAGER_DISABLE_SHOW_LINK_TO_OBJECT_BLOCK)) {
            $possiblelinks['propal'] = array(
                'enabled' => $conf->propal->enabled,
                'perms' => 1,
                'label' => 'LinkToProposal',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "propal as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'propal' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'propal' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('propal') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );
            $possiblelinks['order'] = array(
                'enabled' => $conf->commande->enabled,
                'perms' => 1,
                'label' => 'LinkToOrder',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "commande as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'commande' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'commande' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('commande') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );
            $possiblelinks['invoice'] = array(
                'enabled' => $conf->facture->enabled,
                'perms' => 1,
                'label' => 'LinkToInvoice',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.facnumber as ref, t.ref_client, t.total as total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "facture as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'facture' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'facture' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('facture') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );
            $possiblelinks['contrat'] = array(
                'enabled' => $conf->contrat->enabled,
                'perms' => 1,
                'label' => 'LinkToContract',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, '' as total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "contrat as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'contrat' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'contrat' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('contract') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );
            $possiblelinks['fichinter'] = array(
                'enabled' => $conf->ficheinter->enabled,
                'perms' => 1,
                'label' => 'LinkToIntervention',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "fichinter as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'fichinter' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'fichinter' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('intervention') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );
            $possiblelinks['supplier_proposal'] = array(
                'enabled' => $conf->supplier_proposal->enabled,
                'perms' => 1,
                'label' => 'LinkToSupplierProposal',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, '' as ref_supplier, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "supplier_proposal as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'supplier_proposal' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'supplier_proposal' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('supplier_proposal') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );
            $possiblelinks['order_supplier'] = array(
                'enabled' => $conf->supplier_order->enabled,
                'perms' => 1,
                'label' => 'LinkToSupplierOrder',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "commande_fournisseur as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'order_supplier' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'order_supplier' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('commande_fournisseur') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );
            $possiblelinks['invoice_supplier'] = array(
                'enabled' => $conf->supplier_invoice->enabled,
                'perms' => 1,
                'label' => 'LinkToSupplierInvoice',
                'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                    " INNER JOIN  " . MAIN_DB_PREFIX . "facture_fourn as t ON t.fk_soc = s.rowid" .
                    " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                    "   ON (ee.sourcetype = 'invoice_supplier' AND ee.fk_source = t.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                    "   OR (ee.targettype = 'invoice_supplier' AND ee.fk_target = t.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                    " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('facture_fourn') . ')' .
                    ' AND ee.rowid IS NULL' .
                    ' GROUP BY t.rowid, s.rowid',
            );

            if ($conf->equipement->enabled) {
                $possiblelinks['equipement'] = array(
                    'enabled' => $conf->equipement->enabled,
                    'perms' => 1,
                    'label' => 'LinkToEquipement',
                    'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, e.rowid, e.ref, CONCAT(p.ref, ' - ', p.label, IF(eef.machineclient = 1, ' (Machine)', '')) AS ref_client FROM " . MAIN_DB_PREFIX . "societe as s" .
                        " INNER JOIN  " . MAIN_DB_PREFIX . "equipement as e ON e.fk_soc_client = s.rowid" .
                        " LEFT JOIN  " . MAIN_DB_PREFIX . "equipement_extrafields as eef ON eef.fk_object = e.rowid" .
                        " LEFT JOIN  " . MAIN_DB_PREFIX . "product as p ON p.rowid = e.fk_product" .
                        " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                        "   ON (ee.sourcetype = 'equipement' AND ee.fk_source = e.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                        "   OR (ee.targettype = 'equipement' AND ee.fk_target = e.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                        " WHERE e.entity IN (" . getEntity('equipement') . ')' .
                        " AND e.fk_soc_client IN (" . $listofidcompanytoscan . ")" .
                        " AND eef.machineclient = 1" .
                        ' AND ee.rowid IS NULL' .
                        ' GROUP BY e.rowid, s.rowid',
                );
                $conf->global->EQUIPEMENT_DISABLE_SHOW_LINK_TO_OBJECT_BLOCK = true;
            }

            $this->results = $possiblelinks;
            return 1;
        }

        $this->results = $possiblelinks;
        return 0;
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    /*function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $form, $langs, $user;

        if (!empty($conf->synergiestech->enabled)) {
            $contexts = explode(':', $parameters['context']);

            if (in_array('requestmanagercard', $contexts)) {
                if ($action == 'addline' && $user->rights->requestmanager->creer) {
                    $langs->load('synergiestech@synergiestech');

                    // Create the confirm form
                    $predef = '';
                    $inputList = array();
                    $inputList[] = array('type' => 'hidden', 'name'=> 'product_desc', 'value' => GETPOST('dp_desc'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'price_ht', 'value' => GETPOST('price_ht'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'multicurrency_price_ht', 'value' => GETPOST('multicurrency_price_ht'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'prod_entry_mode', 'value' => GETPOST('prod_entry_mode'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'tva_tx', 'value' => GETPOST('tva_tx'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'idprod', 'value' => GETPOST('idprod'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'qty' . $predef, 'value' => GETPOST('qty' . $predef));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'remise_percent' . $predef, 'value' => GETPOST('remise_percent' . $predef));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'type', 'value' => GETPOST('type'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_start' . $predef . 'hour', 'value' => GETPOST('date_start' . $predef . 'hour'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_start' . $predef . 'min', 'value' => GETPOST('date_start' . $predef . 'min'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_start' . $predef . 'sec', 'value' => GETPOST('date_start' . $predef . 'sec'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_start' . $predef . 'month', 'value' => GETPOST('date_start' . $predef . 'month'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_start' . $predef . 'day', 'value' => GETPOST('date_start' . $predef . 'day'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_start' . $predef . 'year', 'value' => GETPOST('date_start' . $predef . 'year'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_end' . $predef . 'hour', 'value' => GETPOST('date_end' . $predef . 'hour'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_end' . $predef . 'min', 'value' => GETPOST('date_end' . $predef . 'min'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_end' . $predef . 'sec', 'value' => GETPOST('date_end' . $predef . 'sec'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_end' . $predef . 'month', 'value' => GETPOST('date_end' . $predef . 'month'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_end' . $predef . 'day', 'value' => GETPOST('date_end' . $predef . 'day'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'date_end' . $predef . 'year', 'value' => GETPOST('date_end' . $predef . 'year'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'price_base_type', 'value' => GETPOST('price_base_type'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'product_label', 'value' => GETPOST('product_label'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'price_ttc', 'value' => GETPOST('price_ttc'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'units', 'value' => GETPOST('units'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'fournprice' . $predef, 'value' => GETPOST('fournprice' . $predef));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'buying_price' . $predef, 'value' => GETPOST('buying_price' . $predef));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'fk_parent_line', 'value' => GETPOST('fk_parent_line'));
                    $inputList[] = array('type' => 'hidden', 'name'=> 'lang_id', 'value' => GETPOST('lang_id'));

                    $this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('SynergiesTechProductOffFormula'), $langs->trans('SynergiesTechConfirmProductOffFormula'), 'addline', $inputList, '', 1);

                    return 1;
                }
            }
        } elseif (!empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE) && in_array('contractcard', $contexts)) {
            if ($action == 'update_extras' && GETPOST('attribute') == 'rm_timeslots_periods') {
                // fetch optionals attributes and labels
                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
                $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));

                dol_include_once('/requestmanager/lib/requestmanagertimeslots.lib.php');
                $res = requestmanagertimeslots_get_periods($object->array_options['options_rm_timeslots_periods']);
                if (!is_array($res)) {
                    $action = 'edit_extras';
                    $this->errors[] = $langs->trans('RequestManagerTimeSlotsPeriodsName') . ': ' . $res;
                    return 1;
                }
            }
        }

        return 0;
    }*/
}
