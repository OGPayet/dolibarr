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
     * Overloading the formConfirm function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $form, $langs, $user;

        if (!empty($conf->synergiestech->enabled)) {
            $contexts = explode(':', $parameters['context']);

            if (in_array('companyremationshipscard', $contexts)) {
/*                if ($action == 'addline' && $user->rights->requestmanager->creer) {
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
                }*/
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
        global $db, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('propalcard', $contexts)) {
            dol_include_once('/companyrelationships/class/html.formcompanyrelationships.class.php');

            $out = '';

            $events = array();
            $events[] = array('action' => 'getBenefactor', 'url' => dol_buildpath('/companyrelationships/ajax/benefactor.php', 1), 'htmlname' => 'options_companyrelationships_fk_soc_benefactor');
            $formcompanyrelationships = new FormCompanyRelationships($this->db);

            $out .= $formcompanyrelationships->add_select_events('socid', $events);

            print $out;
        }

        return 0;
    }
}
