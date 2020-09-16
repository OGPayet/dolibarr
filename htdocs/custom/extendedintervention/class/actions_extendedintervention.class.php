<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2020      Alexis LAURIER       <contact@alexislaurier.fr>
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
 * \file    htdocs/extendedintervention/class/actions_extendedintervention.class.php
 * \ingroup extendedintervention
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsExtendedIntervention
 */
class ActionsExtendedIntervention
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
     * @var DictionaryLine[]    Cache of the list of the intervention type
     */
    protected static $intervention_type_cached = null;

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
     * Load the list of the intervention type in the cache
     *
     * @return void
     */
    protected function _fetchInterventionType() {
        if (!isset(self::$intervention_type_cached)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $inter_type_dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventiontype');
            $inter_type_dictionary->fetch_lines(1, array('count' => 1), array('label' => 'DESC'));
            self::$intervention_type_cached = $inter_type_dictionary->lines;
        }
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

        if (in_array('interventioncard', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                $confirm = GETPOST('confirm', 'alpha');

                if ($action == 'add' && $user->rights->ficheinter->creer && (!$conf->extendedintervention->enabled || !$object->cr_must_confirm_socid)) {
                    $fk_contrat = GETPOST('contratid', 'int');

                    // Extrafields
                    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
                    $extrafields = new ExtraFields($this->db);
                    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
                    $array_options = $extrafields->getOptionalsFromPost($extralabels);

                    if ($fk_contrat > 0 && $array_options['options_ei_type'] > 0) {
                        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                        $contract = new Contrat($this->db);
                        $contract->fetch($fk_contrat);

                        dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                        $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                        $res = $extendedinterventionquota->isCreatedOutOfQuota($contract, $array_options['options_ei_type']);
                        if ($res) {
                            $object->force_out_of_quota = true;
                            $action = 'create';
                            return 1;
                        }
                    }
                } elseif ($action == 'confirm_force_out_of_quota' && $confirm == "yes" && $user->rights->ficheinter->creer) {
                    $ei_free = GETPOST('ei_free', "alpha");
                    //Ugly fix to manage double dom input with name contratid on force interventionc creation which lost chosen contract
                    $_GET['contratid'] = $_GET['contratId_sav'];
                    if (empty($ei_free)) $object->ei_created_out_of_quota = true;
                    //Ugly fix - otherwise extrafields which are now set in $_GET and not in $_POST are not retrieved
                    $_POST = $_GET;
                    $action = "add";
                }
            }
        }
        elseif (in_array('contractcard', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE) && $object->id > 0) {
                if (preg_match('/^setei_count_type_/i', $action) || preg_match('/^setei_planning_times_type_/i', $action)) {
                    $error = 0;
                    $langs->load('extendedintervention@extendedintervention');

                    $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();

                    dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                    $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                    if ($conf->requestmanager->enabled && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
                        $info = $extendedinterventionquota->getInfoInterventionOfContract($object->id);
                    }

                    $values = array();
                    $this->_fetchInterventionType();
                    foreach (self::$intervention_type_cached as $line) {
                        $htmlname = 'ei_count_type_' . $line->fields['code'];
                        if (isset($_POST[$htmlname]) || isset($_GET[$htmlname])) {
                            $values[$line->id]['count'] = GETPOST($htmlname, 'int') ? GETPOST($htmlname, 'int') : 0;
                        }

                        if ($conf->requestmanager->enabled && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
                            $count = isset($values[$line->id]['count']) ? $values[$line->id]['count'] : (isset($info[$line->id]['count']) ? $info[$line->id]['count'] : '');

                            $found = false;
                            $planning_count = 0;
                            $htmlname = 'ei_planning_times_type_' . $line->fields['code'];

                            foreach ($request_types_planned as $request_type) {
                                $sub_htmlname = $htmlname . '_' . $request_type;

                                if (isset($_POST[$sub_htmlname]) || isset($_GET[$sub_htmlname])) {
                                    $values[$line->id]['planning_times'][$request_type] = GETPOST($sub_htmlname, 'array') ? GETPOST($sub_htmlname, 'array') : array();
                                    $planning_count += count($values[$line->id]['planning_times'][$request_type]);
                                    $found = true;
                                }
                            }

                            if ($found && $planning_count != $count) {
                                $action = 'edit' . $htmlname;
                                if (isset($_POST['action'])) $_POST['action'] = $action;
                                if (isset($_GET['action'])) $_GET['action'] = $action;
                                setEventMessages($langs->trans("ExtendedInterventionErrorPlanningTimesPlannedMismatchedWithQuota", $planning_count, $line->fields['label'], $count), null, 'errors');
                                $error++;
                                break;
                            }
                        }
                    }

                    if (!$error && count($values) > 0) {
                        $res = $extendedinterventionquota->setInfoInterventionOfContract($object->id, $values);
                        if ($res < 0) {
                            setEventMessages($extendedinterventionquota->error, $extendedinterventionquota->errors, 'errors');
                        }
                    }
                }
            }
        }
        elseif (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                global $arrayfields;

                $langs->load('extendedintervention@extendedintervention');
                $this->_fetchInterventionType();

                $added_arrayfields = array();
                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_count_type_' . $line->fields['code'];
                    $label = $langs->trans('ExtendedInterventionQuotaFor', $line->fields['label']);
                    $added_arrayfields[$htmlname] = array('label' => $label, 'checked' => 0);
                }

                $arrayfields = array_merge($arrayfields, $added_arrayfields);
            }
        }

        // Auto save survey when the equipment link with the intervention is deleted
        //----------------------------------------------------------------------------
       /*  if (in_array('interventioncard', $contexts) || in_array('equipementcard', $contexts)) {
            // Get intervention ID and equipment ID
            $intervention_id = 0;
            $equipment_id = 0;
            $dellinkid = GETPOST('dellinkid','int');
            if ($dellinkid > 0) {
                $sql = "SELECT fk_source, sourcetype, fk_target FROM " . MAIN_DB_PREFIX . "element_element WHERE rowid = " . $dellinkid;

                $resql = $this->db->query($sql);
                if ($resql) {
                     if ($obj = $this->db->fetch_object($resql)) {
                         $intervention_id = $obj->sourcetype == "equipement" ? $obj->fk_target : $obj->fk_source;
                         $equipment_id = $obj->sourcetype == "equipement" ? $obj->fk_source : $obj->fk_target;
                     }
                } else {
                    $this->errors[] = $this->db->lasterror();
                    return -1;
                }
            }

            if ($intervention_id > 0 && $equipment_id > 0) {
                dol_include_once('/extendedintervention/class/extendedintervention.class.php');
                $extendedintervention = new ExtendedIntervention($this->db);
                if ($extendedintervention->fetch($intervention_id) > 0) {
                    $can_save = $extendedintervention->statut == ExtendedIntervention::STATUS_VALIDATED;
                } else {
                    $can_save = false;
                }

                if ($can_save) {
                    dol_include_once('/extendedintervention/class/extendedinterventionsurveybloc.class.php');
                    $survey_bloc = new EISurveyBloc($this->db);

                    if ($survey_bloc->fetch(0, $intervention_id, $equipment_id) > 0) {
                        if (!($survey_bloc->id > 0)) {
                            // Create
                            $result = $survey_bloc->create($user);
                            if ($result < 0) {
                                dol_syslog(__METHOD__ . " Error when creating the survey bloc (fk_fichinter:{$survey_bloc->fk_fichinter} fk_equipment:{$survey_bloc->fk_equipment}) when the equipment link with the intervention is deleted; Error: " . $survey_bloc->errorsToString(), LOG_ERR);
                                $this->error = $survey_bloc->error;
                                $this->errors[] = $survey_bloc->errors;
                                return -1;
                            }
                        }

                        foreach ($survey_bloc->survey as $question_bloc) {
                            if (!($question_bloc->id > 0)) {
                                // Create
                                $question_bloc->fk_survey_bloc = $survey_bloc->id;
                                $result = $question_bloc->create($user);
                                if ($result < 0) {
                                    dol_syslog(__METHOD__ . " Error when creating the question bloc (fk_fichinter:{$survey_bloc->fk_fichinter} fk_equipment:{$survey_bloc->fk_equipment} fk_c_question_bloc:{$question_bloc->fk_c_question_bloc}) when the equipment link with the intervention is deleted; Error: " . $question_bloc->errorsToString(), LOG_ERR);
                                    $this->error = $question_bloc->error;
                                    $this->errors[] = $question_bloc->errors;
                                    return -1;
                                }
                            }

                            foreach ($question_bloc->lines as $question) {
                                if (!($question->id > 0)) {
                                    // Create
                                    $question->fk_question_bloc = $question_bloc->id;
                                    $result = $question->insert($user);
                                    if ($result < 0) {
                                        dol_syslog(__METHOD__ . " Error when inserting the question (fk_fichinter:{$survey_bloc->fk_fichinter} fk_equipment:{$survey_bloc->fk_equipment} fk_c_question_bloc:{$question_bloc->fk_c_question_bloc} fk_c_question:{$question->fk_c_question}) when the equipment link with the intervention is deleted; Error: " . $question->errorsToString(), LOG_ERR);
                                        $this->error = $question->error;
                                        $this->errors[] = $question->errors;
                                        return -1;
                                    }
                                }
                            }
                        }
                    } else {
                        dol_syslog(__METHOD__ . " Error when fetching the survey bloc for auto save survey when the equipment link with the intervention is deleted; Error: " . $survey_bloc->errorsToString(), LOG_ERR);
                        $this->errors[] = $survey_bloc->errorsToString();
                        return -1;
                    }
                }
            }
        } */

        return 0;
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $form, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('interventioncard', $contexts) && $action == 'create' && $user->rights->ficheinter->creer) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                // Show comfirm box: Force create intervention out of quota
                if ($object->force_out_of_quota) {
                    $langs->load('extendedintervention@extendedintervention');

                    $formquestion = array();
                    foreach ($_POST as $k => $v) {
                        if ($k == 'action') continue;
                        if (is_array($v)) {
                            foreach ($v as $va) {
                                $formquestion[] = array('type' => 'hidden', 'name' => $k . '[]', 'value' => $va);
                            }
                        } else {
                            $formquestion[] = array('type' => 'hidden', 'name' => $k, 'value' => $v);
                        }
                    }
                    $contratid = GETPOST('contratid', "int");
                    $formquestion[] = array('type' => 'hidden', 'name' => 'contratId_sav', 'value' => $contratid);


                    $ei_free = GETPOST('ei_free', "alpha");
                    $ei_reason = GETPOST('ei_reason', "alpha");
                    $doleditor = new DolEditor('ei_reason', $ei_reason,
                        '', 200, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '70%');

                    $formquestion[] = array('type' => 'checkbox', 'name' => 'ei_free', 'label' => $langs->trans("ExtendedInterventionFree"), 'value' => !empty($ei_free));
                    $formquestion[] = array('type' => 'other', 'name' => 'ei_reason', 'label' => $langs->trans("ExtendedInterventionReason"), 'value' => $doleditor->Create(1));

                    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ExtendedInterventionForceCreateInterventionOutOfQuota'),
                        $langs->trans('ExtendedInterventionConfirmForceCreateInterventionOutOfQuota', $object->ref), 'confirm_force_out_of_quota', $formquestion, 'yes', 1, 500, 900);

                    // Move out of the table
                    $out = '<tr id="ei_formconfirm_block"><td><div id="ei_formconfirm_block">';
                    $out .= <<<SCRIPT
<script type="text/javascript" language="javascript">
    $(document).ready(function () {
        var ei_formconfirm_block_tr = $("tr#ei_formconfirm_block");
        var ei_formconfirm_balise = ei_formconfirm_block_tr.closest('table');
        var ei_formconfirm_block_balise = $("div#ei_formconfirm_block");
        var ei_free = $('#ei_free');
        var ei_reason = $('#ei_reason');

        ei_formconfirm_block_balise.detach().insertAfter(ei_formconfirm_balise);
        ei_formconfirm_block_tr.remove();

        ei_disable_reason();
        $("#dialog-confirm").on("dialogopen", function() {
            ei_free.on('click', function() {
                ei_disable_reason();
            });

            if (typeof CKEDITOR.instances != "undefined" && "ei_reason" in CKEDITOR.instances) {
                CKEDITOR.instances["ei_reason"].on('change', function() {
                    ei_reason.text(CKEDITOR.instances["ei_reason"].getData());
                });
            }
        });

        function ei_disable_reason() {
           if (typeof CKEDITOR.instances != "undefined" && "ei_reason" in CKEDITOR.instances) {
               CKEDITOR.instances["ei_reason"].setReadOnly(ei_free.is(':checked'));
           } else {
               ei_reason.prop('disabled', ei_free.is(':checked'));
           }
        }
    });
</script>
SCRIPT;
                    $out .= $formconfirm . '</div></tr></td>';

                    print $out;
                }

                // Show count of interventions
                $contract_id = GETPOST('contratid', 'int');
                if ($contract_id > 0) {
                    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                    $contract = new Contrat($this->db);
                    $contract->fetch($contract_id);
                    $contract_list = array($contract->id => $contract);
                } else {
                    $contract_list = array();
                }

                // Extrafields
                require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
                $array_options = $extrafields->getOptionalsFromPost($extralabels);
                $fk_c_intervention_type = isset($array_options['options_ei_type']) ? $array_options['options_ei_type'] : 0;

                dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                $ajaxUrl = dol_buildpath('/extendedintervention/ajax/countinterventions.php', 1);
                $out_block = $extendedinterventionquota->showBlockCountInterventionOfContract($contract_list, 0);

                $out = '<tr id="ei_count_intervention_block"><td>';
                $out .= '<div id="ei_count_intervention_ajax_block">';
                $out .= $out_block;
                // Move out of the table
                $out .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            var ei_count_tr = $("tr#ei_count_intervention_block");
            var ei_balise = ei_count_tr.closest('table');
            var ei_count_balise = $("div#ei_count_intervention_ajax_block");
            var ei_contract_id = $('select[name="contratid"]');
            var ei_options_ei_type = $('select[name="options_ei_type"]');

            ei_count_balise.detach().insertAfter(ei_balise);
            ei_count_tr.remove();

            ei_contract_id.on('change', function () {
                update_ei_count_balise();
            });
            ei_options_ei_type.on('change', function () {
                update_ei_count_balise();
            });
            function update_ei_count_balise() {
                $.ajax({
                  url: '$ajaxUrl',
                  method: "POST",
                  data: {
                    contratid: ei_contract_id.val(),
                    ei_type: ei_options_ei_type.val()
                  }
                }).done(function(msg) {console.log(msg);
                  ei_count_balise.html(msg);
                });
            }
        });
    </script>
SCRIPT;
                $out .= '</div></tr></td>';

                print $out;
            }
        } elseif (in_array('interventioncard', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                if ($object->fk_contrat > 0) {
                    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                    $contract = new Contrat($this->db);
                    $contract->fetch($object->fk_contrat);
                    $contract_list = array($contract->id => $contract);
                } else {
                    $contract_list = array();
                }

                if (empty($object->array_options) && $object->id > 0) $object->fetch_optionals();
                $fk_c_intervention_type = isset($object->array_options['options_ei_type']) ? $object->array_options['options_ei_type'] : 0;

                dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                $out = $extendedinterventionquota->showBlockCountInterventionOfContract($contract_list, 0);
                if (!empty($out)) {
                    // Move out of the table
                    $out = '<tr id="ei_count_intervention_block"><td>' . $out;
                    $out .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            var ei_count_tr = $("tr#ei_count_intervention_block");
            var ei_balise = ei_count_tr.closest('table').closest('div.fichecenter').next('div.clearboth');
            var ei_count_balise = $("div#ei_count_intervention_block");

            ei_count_balise.detach().insertAfter(ei_balise);
            ei_count_tr.remove();
        });
    </script>
SCRIPT;
                    $out .= '</tr></td>';

                    print $out;
                }
            }
        } elseif (in_array('contractcard', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                if ($conf->requestmanager->enabled && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
                    dol_include_once('/extendedintervention/class/html.formextendedintervention.class.php');
                    $formextendedintervention = new FormExtendedIntervention($this->db);
                }

                $langs->load('extendedintervention@extendedintervention');

                $this->_fetchInterventionType();

                if ($action == 'create') {
                    foreach (self::$intervention_type_cached as $line) {
                        if ($conf->requestmanager->enabled && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
                            $htmlname = 'ei_planning_times_type_' . $line->fields['code'];
                            $label = $langs->trans('ExtendedInterventionPlanningForInterventionType', $line->fields['label']);

                            print '<tr id="ei_count_intervention_block"><td>' . $label . '</td>';
                            print '<td>';
                            print $formextendedintervention->multiselect_planning_times($line->id, $htmlname);
                            print '</td></tr>';
                        }

                        $htmlname = 'ei_count_type_' . $line->fields['code'];
                        $label = $langs->trans('ExtendedInterventionQuotaFor', $line->fields['label']);
                        print '<tr id="ei_count_intervention_block"><td>' . $label . '</td>';
                        print '<td><input type="text" class="maxwidth150" name="' . $htmlname . '" id="' . $htmlname . '" value="' . GETPOST($htmlname, 'int') . '"></td></tr>';
                    }
                } else {
                    global $form;

                    if (!isset($form)) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
                        $form = new Form($this->db);
                    }

                    dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                    $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                    $info = $extendedinterventionquota->getInfoInterventionOfContract($object->id);

                    foreach (self::$intervention_type_cached as $line) {
                        $count_htmlname = 'ei_count_type_' . $line->fields['code'];
                        $count_value = GETPOST($count_htmlname, 'int') ? GETPOST($count_htmlname, 'int') : (isset($info[$line->id]['count']) ? $info[$line->id]['count'] : '');
                        $count_label = $langs->trans('ExtendedInterventionQuotaFor', $line->fields['label']);

                        if ($conf->requestmanager->enabled && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
                            $can_edit = !empty($count_value) && $count_value > 0;
                            $htmlname = 'ei_planning_times_type_' . $line->fields['code'];
                            $label = $langs->trans('ExtendedInterventionPlanningForInterventionType', $line->fields['label']);

                            if ($can_edit) {
                                print '<tr id="ei_count_intervention_block">';
                                print '<td class="titlefield">';
                                print $form->editfieldkey($label, $htmlname, '', $object, $user->rights->contrat->creer && $can_edit);
                                print '</td><td>';
                                print $formextendedintervention->form_planning_times($object, $line->id, $htmlname, isset($info[$line->id]['planning_times']) ? $info[$line->id]['planning_times'] : array(), $can_edit);
                                print '</td>';
                                print '</tr>';
                            }
                        }

                        print '<tr id="ei_count_intervention_block">';
                        print '<td class="titlefield">';
                        print $form->editfieldkey($count_label, $count_htmlname, $count_value, $object, $user->rights->contrat->creer);
                        print '</td><td>';
                        print $form->editfieldval($count_label, $count_htmlname, $count_value, $object, $user->rights->contrat->creer);
                        print '</td>';
                        print '</tr>';
                    }
                }

                print <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            var ei_count_balise = $("td a[href*='&action=edit_extras&attribute=ei_count_period_size']").closest('table').closest('tr');
            if (!ei_count_balise.length) {
                ei_count_balise = $("input[name='options_ei_count_period_size']").closest('tr');
            }

            if (ei_count_balise && ei_count_balise.length) {
                $('tr#ei_count_intervention_block').map(function () { $(this).detach().insertAfter(ei_count_balise); });
            }
        });
    </script>
SCRIPT;
            } else {
                // Hide extrafields
                global $extrafields;

                unset($extrafields->attributes[$object->table_element]['label']['ei_count_separator']);
                unset($extrafields->attributes[$object->table_element]['label']['ei_count_period_size']);
                unset($extrafields->attribute_label['ei_count_separator']);
                unset($extrafields->attribute_label['ei_count_period_size']);
            }
        }

        return 0;
    }

    /**
	 * Overloading the addMoreSpecificsInformation function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreSpecificsInformation($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);

        if (in_array('requestmanagercard', $contexts)) {
            global $conf;

            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                $object->fetchObjectLinked('', 'contrat', $object->id, $object->element);
                $contract_list = $object->linkedObjects['contrat'];

                dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                $extendedinterventionquota = new ExtendedInterventionQuota($this->db);
                $blocs = $extendedinterventionquota->showBlockCountInterventionOfContract($contract_list, 0);

                if (!empty($blocs)) {
                    $out = '<div class="fichecenter">';
                    $out .= '<div class="underbanner clearboth"></div>';
                    $out .= $blocs;
                    $out .= '</div>';
                    $out .= '<div class="clearboth"></div>';

                    $this->results = array('1_ei' => $out);
                }
            }
        }

        return 0;
    }

    /**
     * Overloading the printFieldListSelect function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function printFieldListSelect($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                $out = '';

                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_count_type_' . $line->fields['code'];
                    $out .= ', MAX(IF(eicct.fk_c_intervention_type = ' . $line->id . ', IFNULL(eicct.count, 0), 0)) AS ' . $htmlname . '_quota';
                }

                $this->resprints = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the printFieldListFrom function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function printFieldListFrom($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                $out = ' LEFT JOIN ' . MAIN_DB_PREFIX . 'extendedintervention_contract_type_info AS eicct ON eicct.fk_contrat = c.rowid';

                $this->resprints = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the printFieldListGroupBy function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function printFieldListGroupBy($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                $out = '';

                $search = array();
                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_count_type_' . $line->fields['code'];
                    $value = GETPOST('search_' . $htmlname, 'alpha');
                    if ($value) $search[] = natural_search($htmlname . '_quota', $value, 1, 1);
                }

                if (count($search)) {
                    $out = ' HAVING ' . implode(' AND ', $search);
                }

                $this->resprints = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function printFieldListOption($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                global $arrayfields;
                $out = '';

                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_count_type_' . $line->fields['code'];
                    if (!empty($arrayfields[$htmlname]['checked'])) {
                        $out .= '<td class="liste_titre">';
                        $out .= '<input class="flat" size="4" type="text" name="search_' . $htmlname . '" value="' . dol_escape_htmltag(GETPOST('search_' . $htmlname, 'alpha')) . '">';
                        $out .= '</td>';
                    }
                }

                $this->resprints = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the printFieldListTitle function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                global $arrayfields, $param, $sortfield, $sortorder;
                $out = '';

                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_count_type_' . $line->fields['code'];
                    if (! empty($arrayfields[$htmlname]['checked']))
                        $out .= getTitleFieldOfList($arrayfields[$htmlname]['label'], 0, $_SERVER["PHP_SELF"], $htmlname . '_quota', "", "$param", 'align="center"', $sortfield, $sortorder);
                }

                $this->resprints = $out;
            }
        }

        return 0;
    }

    /**
     * Overloading the printFieldListValue function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	function printFieldListValue($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                global $arrayfields, $obj, $i, $totalarray;
                $out = '';

                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_count_type_' . $line->fields['code'];
                    if (!empty($arrayfields[$htmlname]['checked'])) {
                        $out .= '<td align="center" class="nowrap">';
                        $out .= $obj->{$htmlname.'_quota'};
                        $out .= '</td>';
                        if (!$i) $totalarray['nbfield']++;
                    }
                }

                $this->resprints = $out;
            }
        }

        return 0;
    }
}
