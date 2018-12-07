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

                if ($action == 'add' && $user->rights->ficheinter->creer) {
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
                        $extendedinterventioncountintervention = new ExtendedInterventionQuota($this->db);

                        $res = $extendedinterventioncountintervention->isCreatedOutOfQuota($contract, array($contract->socid), $array_options['options_ei_type']);
                        if ($res) {
                            $object->force_out_of_quota = true;
                            $action = 'create';
                            return 1;
                        }
                    }
                } elseif ($action == 'confirm_force_out_of_quota' && $confirm == "yes" && $user->rights->ficheinter->creer) {
                    $ei_free = GETPOST('ei_free', "alpha");
                    if (empty($ei_free)) $object->ei_created_out_of_quota = true;
                    $action = "add";
                }
            }
        } elseif (in_array('contractcard', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE) && $object->id > 0) {
                $langs->load('extendedintervention@extendedintervention');

                $values = array();
                $this->_fetchInterventionType();
                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_type_' . $line->fields['code'];
                    if (isset($_POST[$htmlname]) || isset($_GET[$htmlname])) {
                        $values[$line->id] = GETPOST($htmlname, 'int') ? GETPOST($htmlname, 'int') : 0;
                    }
                }

                if (count($values) > 0) {
                    dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                    $extendedinterventioncountintervention = new ExtendedInterventionQuota($this->db);

                    $res = $extendedinterventioncountintervention->setCountInterventionOfContract($object->id, $values);
                }
            }
        } elseif (in_array('contractlist', $contexts)) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                global $arrayfields;

                $langs->load('extendedintervention@extendedintervention');
                $this->_fetchInterventionType();

                $added_arrayfields = array();
                foreach (self::$intervention_type_cached as $line) {
                    $htmlname = 'ei_type_' . $line->fields['code'];
                    $label = $langs->trans('ExtendedInterventionQuotaFor', $line->fields['label']);
                    $added_arrayfields[$htmlname] = array('label' => $label, 'checked' => 0);
                }

                $arrayfields = array_merge($arrayfields, $added_arrayfields);
            }
        }

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
                $extendedinterventioncountintervention = new ExtendedInterventionQuota($this->db);

                $ajaxUrl = dol_buildpath('/extendedintervention/ajax/countinterventions.php', 1);
                $out_block = $extendedinterventioncountintervention->showBlockCountInterventionOfContract($contract_list, $fk_c_intervention_type);

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
        } elseif (in_array('interventioncard', $contexts) || (in_array('requestmanagercard', $contexts) && $object->element == 'requestmanager')) {
            if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                if (in_array('interventioncard', $contexts)) {
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
                } else {
                    $object->fetchObjectLinked('', 'contrat', $object->id, $object->element);
                    $contract_list = $object->linkedObjects['contrat'];
                    $fk_c_intervention_type = 0;
                }

                dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                $extendedinterventioncountintervention = new ExtendedInterventionQuota($this->db);

                $out = $extendedinterventioncountintervention->showBlockCountInterventionOfContract($contract_list, $fk_c_intervention_type);
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
                $langs->load('extendedintervention@extendedintervention');

                $this->_fetchInterventionType();

                if ($action == 'create') {
                    foreach (self::$intervention_type_cached as $line) {
                        $htmlname = 'ei_type_'.$line->fields['code'];
                        $label = $langs->trans('ExtendedInterventionQuotaFor', $line->fields['label']);
                        print '<tr id="ei_count_intervention_block"><td>' . $label . '</td>';
                        print '<td><input type="text" class="maxwidth150" name="'.$htmlname.'" id="'.$htmlname.'" value="' . GETPOST($htmlname, 'int') . '"></td></tr>';
                    }
                } else {
                    global $form;

                    if (!isset($form)) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
                        $form = new Form($this->db);
                    }

                    dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                    $extendedinterventioncountintervention = new ExtendedInterventionQuota($this->db);

                    $counts = $extendedinterventioncountintervention->getCountInterventionOfContract($object->id);

                    foreach (self::$intervention_type_cached as $line) {
                        $htmlname = 'ei_type_'.$line->fields['code'];
                        $value = GETPOST($htmlname, 'int') ? GETPOST($htmlname, 'int') : (isset($counts[$line->id]) ? $counts[$line->id] : '');
                        $label = $langs->trans('ExtendedInterventionQuotaFor', $line->fields['label']);

                        print '<tr id="ei_count_intervention_block">';
                        print '<td class="titlefield">';
                        print $form->editfieldkey($label, $htmlname, $value, $object, $user->rights->contrat->creer);
                        print '</td><td>';
                        print $form->editfieldval($label, $htmlname, $value, $object, $user->rights->contrat->creer);
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
                    $htmlname = 'ei_type_' . $line->fields['code'];
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
                $out = ' LEFT JOIN ' . MAIN_DB_PREFIX . 'extendedintervention_contract_count_type AS eicct ON eicct.fk_contrat = c.rowid';

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
                    $htmlname = 'ei_type_' . $line->fields['code'];
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
                    $htmlname = 'ei_type_' . $line->fields['code'];
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
                    $htmlname = 'ei_type_' . $line->fields['code'];
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
                    $htmlname = 'ei_type_' . $line->fields['code'];
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
