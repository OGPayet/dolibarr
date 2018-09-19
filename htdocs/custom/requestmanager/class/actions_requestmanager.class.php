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
     * Overloading the addSearchEntry function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
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
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('thirdpartycard', $contexts) ||
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
        global $db, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('actioncard', $contexts)) {
            dol_include_once('/requestmanager/class/requestmanager.class.php');
            dol_include_once('/requestmanager/class/requestmanagermessage.class.php');

            $out = '';

            $requestManagerMessage = new RequestManagerMessage($db);
            if ($object->code == RequestManager::ACTIONCOMM_TYPE_CODE_IN || $object->code == RequestManager::ACTIONCOMM_TYPE_CODE_OUT) {
                $requestManagerMessage->loadByFkAction($object->id, TRUE);
                if ($requestManagerMessage->knowledgeBase) {
                    $out .= '<tr>';
                    $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("RequestManagerKnowledgeBaseDictionaryLabel") . '</td>';
                    $out .= '<td colspan="3">' . $requestManagerMessage->knowledgeBase->code . ' - ' . $requestManagerMessage->knowledgeBase->title . '</td>';
                    $out .= '</tr>';
                }
            } else {
                $requestManagerMessage->loadByFkAction($object->id, FALSE);
            }

            if($requestManagerMessage->id > 0)
            {
                // Other attributes
                require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
                $extrafields = new ExtraFields($db);
                $extralabels = $extrafields->fetch_name_optionals_label('requestmanager_message');
                $out .= $requestManagerMessage->showOptionals($extrafields);
            }

            $this->resprints = $out;
        }

        return 0;
    }


    /**
     * 	Show my assigned requests button (with nb or +)
     */
    private function _outMyAssignedRequestsButton()
    {
        global $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        dol_include_once('/requestmanager/class/requestmanager.class.php');

        $out = '';

        // last view date
        if (isset($_SESSION['rm_lists_follow_last_date'])) {
            $lastViewDate = $_SESSION['rm_lists_follow_last_date'];
        } else if ($user->datepreviouslogin) {
            $lastViewDate = $user->datepreviouslogin;
        } else {
            $lastViewDate = '';
        }

        // nb requests assigned to me
        $nbRequestsLimit  = 9;

        $requestManager        = new RequestManager($this->db);
        $isListsFollowModified = $requestManager->isListsFollowModified($lastViewDate);
        if ($isListsFollowModified === TRUE) {
            $linkStyleBgColor = '#fff000';
        } else {
            $linkStyleBgColor = '#ffffff';
        }
        $nbRequests      = $requestManager->countMyAssignedRequests(array(RequestManager::STATUS_TYPE_INITIAL, RequestManager::STATUS_TYPE_IN_PROGRESS));
        $nbRequestsLabel = $nbRequests<=$nbRequestsLimit ? $nbRequests : $nbRequestsLimit.'+';

        $text  = '<a href="' . dol_buildpath('/requestmanager/lists_follow.php', 1) . '" style="background-color: ' . $linkStyleBgColor . '; border-radius: 4px;">';
        $text .= '<span style="color: #770000; font-size: 12px; font-weight: bold;">&nbsp;' . $nbRequestsLabel . '&nbsp;</span>';
        //$text .= img_picto('', 'object_requestmanager@requestmanager', 'id="myassignedrequests"');
        $text .= '</a>';

        $htmltext  = '<u>' . $langs->trans("RequestManagerMenuTopRequestsFollow") . '</u>' . "\n";
        $htmltext .= '<br /><b>' . $langs->trans("Total") . '</b> : ' . $nbRequests . "\n";
        if ($lastViewDate) {
            $htmltext .= '<br /><b>' . $langs->trans("RequestManagerMenuTopDateLastView") . '</b> : ' . dol_print_date($lastViewDate, 'dayhour') . "\n";
        }
        $out .= Form::textwithtooltip('', $htmltext,2,1, $text,'login_block_elem',2);

        return $out;
    }


    /**
     * 	Show request create fast
     */
    private function _outCreateFast()
    {
        global $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

        $out = '';

        $text  = '<a href="' . dol_buildpath('/requestmanager/createfast.php?action=createfast', 1) . '" target="_blank">';
        $text .= img_picto('', 'object_requestmanager@requestmanager', 'id="requestmanager_createfast"');
        $text .= '</a>';

        $htmltext  = '<u>' . $langs->trans("RequestManagerMenuTopCreateFast") . '</u>' . "\n";
        $out .= Form::textwithtooltip('', $htmltext,2,1, $text,'login_block_elem',2);

        return $out;
    }


    /**
     * Print a specific button in top right menu (to show my assigned requests)
     *
     * @param   array    $parameters     Parameters
     * @return  int
     */
    function printTopRightMenu($parameters, &$object, &$action, $hookmanager)
    {
        global $user;

        if (in_array('toprightmenu', explode(':', $parameters['context']))) {
            if ($user->rights->requestmanager->lire) {
                // show my assigned requests button
                $out  = $this->_outMyAssignedRequestsButton();
                $out .= $this->_outCreateFast();
                $this->resprints = $out;
            }
        }

        return 0;
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
        return 0;
    }

    /**
	 * Overloading the showLinkToObjectBlock function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function showLinkToObjectBlock($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        $contexts = explode(':',$parameters['context']);

        if (in_array('requestmanagercard', $contexts)) {
            if (!is_object($object->thirdparty)) $object->fetch_thirdparty();

            if (is_object($object->thirdparty) && !empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
                $listofidcompanytoscan = $object->thirdparty->id;
                if (($object->thirdparty->parent > 0) && !empty($conf->global->THIRDPARTY_INCLUDE_PARENT_IN_LINKTO)) $listofidcompanytoscan .= ',' . $object->thirdparty->parent;
                if (($object->fk_project > 0) && !empty($conf->global->THIRDPARTY_INCLUDE_PROJECT_THIRDPARY_IN_LINKTO)) {
                    include_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                    $tmpproject = new Project($this->db);
                    $tmpproject->fetch($object->fk_project);
                    if ($tmpproject->socid > 0 && ($tmpproject->socid != $object->thirdparty->id)) $listofidcompanytoscan .= ',' . $tmpproject->socid;
                    unset($tmpproject);
                }

                $possiblelinks = array(
                    'propal' => array(
                        'enabled' => $conf->propal->enabled,
                        'perms' => 1,
                        'label' => 'LinkToProposal',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "propal as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'propal' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'propal' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('propal') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                    'order' => array(
                        'enabled' => $conf->commande->enabled,
                        'perms' => 1,
                        'label' => 'LinkToOrder',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "commande as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'commande' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'commande' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('commande') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                    'invoice' => array(
                        'enabled' => $conf->facture->enabled,
                        'perms' => 1,
                        'label' => 'LinkToInvoice',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.facnumber as ref, t.ref_client, t.total as total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "facture as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'facture' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'facture' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('facture') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                    'contrat' => array(
                        'enabled' => $conf->contrat->enabled,
                        'perms' => 1,
                        'label' => 'LinkToContract',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, '' as total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "contrat as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'contrat' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'contrat' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('contract') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                    'fichinter' => array(
                        'enabled' => $conf->ficheinter->enabled,
                        'perms' => 1,
                        'label' => 'LinkToIntervention',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "fichinter as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'fichinter' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'fichinter' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('intervention') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                    'supplier_proposal' => array(
                        'enabled' => $conf->supplier_proposal->enabled,
                        'perms' => 1,
                        'label' => 'LinkToSupplierProposal',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, '' as ref_supplier, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "supplier_proposal as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'supplier_proposal' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'supplier_proposal' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('supplier_proposal') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                    'order_supplier' => array(
                        'enabled' => $conf->supplier_order->enabled,
                        'perms' => 1,
                        'label' => 'LinkToSupplierOrder',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "commande_fournisseur as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'order_supplier' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'order_supplier' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('commande_fournisseur') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                    'invoice_supplier' => array(
                        'enabled' => $conf->supplier_invoice->enabled,
                        'perms' => 1,
                        'label' => 'LinkToSupplierInvoice',
                        'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM " . MAIN_DB_PREFIX . "societe as s" .
                            " INNER JOIN  " . MAIN_DB_PREFIX . "facture_fourn as t ON t.fk_soc = s.rowid" .
                            " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                            "   ON (ee.sourcetype = 'invoice_supplier' AND ee.fk_source = t.rowid AND ee.targettype = '".$object->element."' AND ee.fk_target = " . $object->id . ")" .
                            "   OR (ee.targettype = 'invoice_supplier' AND ee.fk_target = t.rowid AND ee.sourcetype = '".$object->element."' AND ee.fk_source = " . $object->id . ")" .
                            " WHERE t.fk_soc IN (" . $listofidcompanytoscan . ') AND t.entity IN (' . getEntity('facture_fourn') . ')' .
                            ' AND ee.rowid IS NULL' .
                            ' GROUP BY t.rowid, s.rowid',
                    ),
                );

                $this->results = $possiblelinks;
                return 1;
            }
        }

        return 0;
    }
}
