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
        global $conf;

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
                } else {
                    $object->fetch_thirdparty();

                    // find principal company of this thirdparty
                    $companyRelationships = new CompanyRelationships($this->db);
                    $companies = $companyRelationships->getRelationships($object->thirdparty->id, 0, 1);
                    if (is_array($companies) && count($companies) == 1) {
                        $companyRecipient = current($companies);
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
        global $db, $langs, $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {
            dol_include_once('/companyrelationships/class/companyrelationships.class.php');

            if (!empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {
                dol_include_once('/companyrelationships/class/html.formcompanyrelationships.class.php');

                $langs->load('companyrelationships@companyrelationships');

                $out = '';

                if ($object->element == 'fichinter') {
                    $out .= '<script type="text/javascript" language="javascript">';
                    $out .= 'jQuery(document).ready(function(){';
                    $out .= '   var data = {';
                    $out .= '       action: "getBenefactor",';
                    $out .= '       id: jQuery("input[name=socid]").val(),';
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
                        $out .= '           socid: jQuery("input[name=socid]").val(),';
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
                } else {
                    $events = array();
                    $events[] = array('action' => 'getBenefactor', 'url' => dol_buildpath('/companyrelationships/ajax/benefactor.php', 1), 'htmlname' => 'options_companyrelationships_fk_soc_benefactor');
                    $formcompanyrelationships = new FormCompanyRelationships($this->db);
                    $out .= $formcompanyrelationships->add_select_events('socid', $events);

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

        return 0;
    }
}
