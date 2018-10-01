<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/companyrelationships/class/actions_companyrelationships.class.php
 * \ingroup companyrelationships
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsCompanyRelationships
 */
class ActionsCompanyRelationships
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
    function beforePDFCreation($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {
            dol_include_once('/companyrelationships/class/companyrelationships.class.php');

            if (!empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {
                if ($object->element == 'fichinter') {
                    // get benefactor of this element
                    $benefactorId = $object->array_options['options_companyrelationships_fk_soc_benefactor'];

                    if ($benefactorId > 0) {
                        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                        $companyRecipient = new Societe($this->db);
                        $companyRecipient->fetch($benefactorId);

                        if ($companyRecipient->id > 0) {
                            $object->thirdparty = $companyRecipient;
                        }
                    }
                }
            }
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
        global $langs, $user;

        /*
        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {
            if ($action == 'create') {
                dol_include_once('/companyrelationships/class/companyrelationships.class.php');


                if (!empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {

                    $elementName = $object->element;

                    if ($action == 'companyrelationships_confirm_socid' && $user->rights->{$elementName}->creer) {
                        $langs->load('companyrelationships@companyrelationships');

                        $fk_soc_benefactor = GETPOST('companyrelationships_fk_soc_benefactor', 'int');
                        $socid = GETPOST('companyrelationships_socid', 'int');

                        if (intval($socid) > 0) {
                            $url = 'card.php?action=create&socid=' . $socid . '&companyrelationships_fk_soc_benefactor=' . $fk_soc_benefactor;
                            header('Location: ' . $url);
                            exit();
                        }
                    }
                }
            }
        }
        */

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
        global $langs, $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {

            if ($action == 'create') {
                dol_include_once('/companyrelationships/class/companyrelationships.class.php');

                if (!empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {
                    dol_include_once('/companyrelationships/class/html.formcompanyrelationships.class.php');

                    $langs->load('companyrelationships@companyrelationships');

                    $out = '';

                    $socid    = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : 0;
                    $confirm  = GETPOST('confirm', 'alpha');
                    $originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility

                    // come from confirm_socid dialog box
                    $fk_soc_benefactor = GETPOST('companyrelationships_fk_soc_benefactor', 'int') ? GETPOST('companyrelationships_fk_soc_benefactor', 'int') : 0;

                    $formcompanyrelationships = new FormCompanyRelationships($this->db);
                    $companyRelationships = new CompanyRelationships($this->db);

                    // get the name of form to keep values and to submit
                    $formName = $companyRelationships->getFormNameForElementAndAction($object->element, $action);

                    // form confirm to choose the principal company
                    $out .= '<tr>';
                    $out .= '<td>';
                    $out .= '<div id="companyrelationships_confirm">';
                    if (empty($confirm) && empty($originid) && intval($socid) > 0) {
                        $principalCompanyList = $companyRelationships->getRelationships($socid, 0, 1);
                        $principalCompanyList = is_array($principalCompanyList) ? $principalCompanyList : array();
                        if (count($principalCompanyList) > 0) {
                            $principalCompanySelectArray = array();

                            // format options in select principal company
                            foreach ($principalCompanyList as $companyId => $company) {
                                $principalCompanySelectArray[$companyId] = $company->getFullName($langs);
                            }

                            $formQuestionList = array();
                            $formQuestionList[] = array('name' => 'companyrelationships_fk_soc_benefactor', 'type' => 'hidden', 'value' => $socid);
                            $formQuestionList[] = array('label' => $langs->trans('CompanyRelationshipsPrincipalCompany'), 'name' => 'companyrelationships_socid', 'type' => 'select', 'values' => $principalCompanySelectArray, 'default' => '');

                            // form confirm to choose the principal company
                            $out .= $formcompanyrelationships->formconfirm_socid($_SERVER['PHP_SELF'], $langs->trans('CompanyRelationshipsConfirmPrincipalCompanyTitle'), $langs->trans('CompanyRelationshipsConfirmPrincipalCompanyChoice'), 'companyrelationships_confirm_socid', $formQuestionList, '', 1, 200, 500, $formName);
                        }
                    }
                    $out .= '</div>';
                    $out .= '</td>';
                    $out .= '</tr>';

                    // company id already posted (an input hidden in this form)
                    if (intval($socid) > 0) {
                        $out .= '<script type="text/javascript" language="javascript">';
                        $out .= 'jQuery(document).ready(function(){';
                        $out .= '   var data = {';
                        $out .= '       action: "getBenefactor",';
                        $out .= '       id: "' . $socid . '",';
                        $out .= '       fk_soc_benefactor: "' . $fk_soc_benefactor . '",';
                        $out .= '       htmlname: "options_companyrelationships_fk_soc_benefactor"';
                        $out .= '   };';
                        $out .= '   var input = jQuery("select#options_companyrelationships_fk_soc_benefactor");';
                        $out .= '   jQuery.getJSON("' . dol_buildpath('/companyrelationships/ajax/benefactor.php', 1) . '", data,';
                        $out .= '       function(response) {';
                        $out .= '           input.html(response.value);';
                        $out .= '           input.change();';
                        $out .= '           if (response.num < 0) {';
                        $out .= '               console.error(response.error);';
                        $out .= '           }';
                        $out .= '       }';
                        $out .= '   );';

                        // company relationships availability for this element
                        if ($user->rights->companyrelationships->update_md->element) {
                            $out .= '   jQuery("#options_companyrelationships_fk_soc_benefactor").change(function(){';
                            $out .= '       jQuery.ajax({';
                            $out .= '           data: {';
                            $out .= '           socid: "' . $socid . '",';
                            $out .= '           socid_benefactor: jQuery("#options_companyrelationships_fk_soc_benefactor").val(),';
                            $out .= '           element: "' . $object->element . '"';
                            $out .= '           },';
                            $out .= '           dataType: "json",';
                            $out .= '           method: "POST",';
                            $out .= '           url: "' . dol_buildpath('/companyrelationships/ajax/publicspaceavailability.php', 1) . '",';
                            $out .= '           success: function(data){';
                            $out .= '               if (data.error > 0) {';
                            $out .= '                   console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_benefactor.change()");';
                            $out .= '               } else {';
                            $out .= '                   jQuery("input[name=options_companyrelationships_availability_principal]").prop("checked", data.principal);';
                            $out .= '                   jQuery("input[name=options_companyrelationships_availability_benefactor]").prop("checked", data.benefactor);';
                            $out .= '               }';
                            $out .= '           },';
                            $out .= '           error: function(){';
                            $out .= '               console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_benefactor.change()");';
                            $out .= '           }';
                            $out .= '       });';
                            $out .= '   });';
                        }
                        $out .= '});';
                        $out .= '</script>';
                    } // no company selected (select options in this form to choose the company)
                    else {
                        $events = array();
                        $events[] = array('action' => 'getPrincipal', 'url' => dol_buildpath('/companyrelationships/ajax/principal.php', 1), 'htmlname' => 'companyrelationships_confirm', 'more_data' => array('form_name' => $formName, 'url_src' => $_SERVER['PHP_SELF']));
                        $events[] = array('action' => 'getBenefactor', 'url' => dol_buildpath('/companyrelationships/ajax/benefactor.php', 1), 'htmlname' => 'options_companyrelationships_fk_soc_benefactor', 'more_data' => array('fk_soc_benefactor' => $fk_soc_benefactor));
                        $out .= $formcompanyrelationships->add_select_events_more_data('socid', $events);

                        // company relationships availability for this element
                        if ($user->rights->companyrelationships->update_md->element) {
                            $out .= '<script type="text/javascript" language="javascript">';
                            $out .= 'jQuery(document).ready(function(){';
                            $out .= '   jQuery("#options_companyrelationships_fk_soc_benefactor").change(function(){';
                            $out .= '       jQuery.ajax({';
                            $out .= '           data: {';
                            $out .= '           socid: jQuery("#socid").val(),';
                            $out .= '           socid_benefactor: jQuery("#options_companyrelationships_fk_soc_benefactor").val(),';
                            $out .= '           element: "' . $object->element . '"';
                            $out .= '           },';
                            $out .= '           dataType: "json",';
                            $out .= '           method: "POST",';
                            $out .= '           url: "' . dol_buildpath('/companyrelationships/ajax/publicspaceavailability.php', 1) . '",';
                            $out .= '           success: function(data){';
                            $out .= '               if (data.error > 0) {';
                            $out .= '                   console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_benefactor.change()");';
                            $out .= '               } else {';
                            $out .= '                   jQuery("input[name=options_companyrelationships_availability_principal]").prop("checked", data.principal);';
                            $out .= '                   jQuery("input[name=options_companyrelationships_availability_benefactor]").prop("checked", data.benefactor);';
                            $out .= '               }';
                            $out .= '           },';
                            $out .= '           error: function(){';
                            $out .= '               console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_benefactor.change()");';
                            $out .= '           }';
                            $out .= '       });';
                            $out .= '   });';
                            $out .= '});';
                            $out .= '</script>';
                        }
                    }

                    print $out;
                }
            }
        }

        return 0;
    }
}
