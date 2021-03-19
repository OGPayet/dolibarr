<?php
/* Copyright (C) 2021 Infra <infra@synergies-france.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    invoicebetterstatus/class/actions_invoicebetterstatus.class.php
 * \ingroup invoicebetterstatus
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

 dol_include_once('/invoicebetterstatus/class/invoicebetterstatustool.class.php');
/**
 * Class ActionsInvoiceBetterStatus
 */
class ActionsInvoiceBetterStatus
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
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
     * @var Form
     */
    public $form;

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * @var string const to define name of search by status form
     */

     const SEARCH_FORM_HTML_NAME = "invoicebetterstatus_search";

     /**
      * @var string const to define set as contentious action name
      */
     const SET_AS_CONTENTIOUS_ACTION_NAME = "invoicebetterstatus_setAsContentious";

     /**
      * @var string const to define set as contentious action name
      */
     const SET_AS_NOT_ANYMORE_CONTENTIOUS_ACTION_NAME = "invoicebetterstatus_setAsNotAnymoreContentious";

    /**
     * Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->form = new Form($this->db);
		global $langs;
		$langs->load('invoicebetterstatus@invoicebetterstatus');
    }

    /**
     * Execute action
     *
     * @param   array           $parameters     Array of parameters
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         'add', 'update', 'view'
     * @return  int                             <0 if KO,
     *                                          =0 if OK but we want to process standard actions too,
     *                                          >0 if OK and we want to replace standard actions.
     */
    public function getNomUrl($parameters, &$object, &$action)
    {
		global $user;
        if ($object->element == 'facture' && $user->rights->invoicebetterstatus->invoicebetterstatus->read) {
            $notooltip = $parameters['notooltip'];
            $addlinktonotes = $parameters['addlinktonotes'];
            $save_lastsearch_value = $parameters['save_lastsearch_value'];
            $target = $parameters['target'];
			if($object->alreadypaid === null && method_exists($object, "getSommePaiement")) {
				$alreadypaid = $object->getSommePaiement();
				$object->alreadypaid = $alreadypaid ? $alreadypaid : 0;
			}
            $this->resprints = InvoiceBetterStatusTool::getNomUrl($object, 1, '', 0, 0, '', $notooltip, $addlinktonotes, $save_lastsearch_value, $target);
            return 1;
        }
        return 0;
    }
    /**
     * Overloading the printFieldListSelect function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	public function printFieldListSelect($parameters, &$object, &$action, $hookmanager)
	{
		$contexts = explode(':', $parameters['context']);
        if (in_array('invoicelist', $contexts)) {
			$result = ', SUM(pf.amount) as alreadypaid, SUM(pf.multicurrency_amount) as multicurrency_alreadypaid';
			$sql = 'CASE';
			foreach(InvoiceBetterStatusTool::$sqlSearchInvoiceList as $status => $sqlValue) {
				$sql .= ' WHEN (' . $sqlValue . ') THEN "' . $status . '"';
			}
			$sql.= ' ELSE NULL END';
			$result .= ',(' . $sql . ') as invoicebetterstatus';
			$this->resprints = $result;
		}
	}

    /**
     * Overloading the printFieldListWhere function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListHaving($parameters, &$object, &$action, $hookmanager)
    {
        //We add filter to sql list request
        $contexts = explode(':', $parameters['context']);
        if (in_array('invoicelist', $contexts)) {
            $searchedStatus = GETPOST(self::SEARCH_FORM_HTML_NAME, 'array');
            if (!empty($searchedStatus)) {
                $this->resprints  = ' AND invoicebetterstatus IN (' . implode(',', $searchedStatus) . ')';
            }
        }
        return 0;
    }

    /**
     * Overloading the printFieldListOption function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListOption($parameters, &$object, &$action, $hookmanager)
    {
        //We add search form
        $contexts = explode(':', $parameters['context']);
        if (in_array('invoicelist', $contexts)) {
            global $arrayfields;
            if ($arrayfields['invoicebetterstatus']['checked']) {
                $labelStatus = InvoiceBetterStatusTool::getStatusArrayTranslatedForSearch();
                $searchForm = $this->form->multiselectarray(self::SEARCH_FORM_HTML_NAME, $labelStatus, GETPOST(self::SEARCH_FORM_HTML_NAME, 'array'));
                print '<td class="liste_titre right">' . $searchForm . '</td>';
            }
        }
    }

    /**
     * Overloading the printFieldListTitle function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
    {
        //We add column title of the tab
        $contexts = explode(':', $parameters['context']);
        if (in_array('invoicelist', $contexts)) {
            global $arrayfields;
            if ($arrayfields['invoicebetterstatus']['checked']) {
                $arrayfields = $parameters['arrayfields'];
                $param = $parameters['param'];
                $sortfield = $parameters['sortfield'];
                $sortorder = $parameters['sortorder'];
                print_liste_field_titre($arrayfields['invoicebetterstatus']['label'], $_SERVER['PHP_SELF'], 'invoicebetterstatus', '', $param, 'class="right"', $sortfield, $sortorder);
            }
        }
    }

    /**
     * Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printFieldPreListTitle($parameters, &$object, &$action, $hookmanager)
    {
        global $arrayfields, $user;
        //We add the ability to the field to be checked
        $contexts = explode(':', $parameters['context']);
        if (in_array('invoicelist', $contexts)) {
            $arrayfields['invoicebetterstatus'] = array('label'=>"InvoiceBetterStatusLabel", 'checked'=>1, 'position'=>1000, 'enabled' => !empty($user->rights->invoicebetterstatus->invoicebetterstatus->read));
        }
    }

    /**
     * Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListValue($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);
        if (in_array('invoicelist', $contexts)) {
            global $facturestatic, $arrayfields;
			$obj = $parameters['obj'];
			$facturestatic->array_options['options_classified_as_contentious'] = $obj->options_classified_as_contentious;
            if ($arrayfields['invoicebetterstatus']['checked']) {
				if($facturestatic->alreadypaid === null && method_exists($object, "getSommePaiement")) {
					$alreadypaid = $facturestatic->getSommePaiement();
					$facturestatic->alreadypaid = $alreadypaid ? $alreadypaid : 0;
				}
                print '<td class="nowrap right">' . InvoiceBetterStatusTool::getLibStatus($facturestatic, 5) . '</td>';
            }
        }
    }
    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        //We use to set invoice as in a contentious state
        global $user, $langs;
        $contexts = explode(':', $parameters['context']);
        if (in_array('invoicecard', $contexts) && $user->rights->invoicebetterstatus->invoicebetterstatus->setascontentious) {
            if ($action == self::SET_AS_CONTENTIOUS_ACTION_NAME && InvoiceBetterStatusTool::getCurrentStatus($object) == InvoiceBetterStatusTool::STATUS_LATE_PAYMENT) {
                //Save as contentious
                $object->fetch_optionals();
                $object->array_options['options_classified_as_contentious'] = 1;
                if ($object->insertExtraFields('', $user) > 0) {
                    setEventMessages($langs->trans("InvoiceBetterStatusSucessfullySetAsContentious"), array());
                } else {
                    setEventMessages($langs->trans("InvoiceBetterStatusErrorWhileSettingAsContentious"), $object->errors, 'errors');
                }
            }
            if ($action == self::SET_AS_NOT_ANYMORE_CONTENTIOUS_ACTION_NAME && InvoiceBetterStatusTool::getCurrentStatus($object) == InvoiceBetterStatusTool::STATUS_CONTENTIOUS_PAYMENT) {
                //Save as not anymore contentious
                $object->fetch_optionals();
                $object->array_options['options_classified_as_contentious'] = 0;
                if ($object->insertExtraFields('', $user) > 0) {
                    setEventMessages($langs->trans("InvoiceBetterStatusSucessfullySetAsNotAnymoreContentious"), array());
                } else {
                    setEventMessages($langs->trans("InvoiceBetterStatusErrorWhileSettingNotAnymoreAsContentious"), $object->errors, 'errors');
                }
            }
        }
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        //We use to display setAsContentious and setAsNotAnymoreContentious buttons
        global $user, $langs;
        $contexts = explode(':', $parameters['context']);
        if (in_array('invoicecard', $contexts) && $user->rights->invoicebetterstatus->invoicebetterstatus->read) {
            if (InvoiceBetterStatusTool::getCurrentStatus($object) == InvoiceBetterStatusTool::STATUS_CONTENTIOUS_PAYMENT) {
                //display button to set back invoice
                print '<div class="inline-block divButAction">';
                if ($user->rights->invoicebetterstatus->invoicebetterstatus->setascontentious) {
                    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action='. self::SET_AS_NOT_ANYMORE_CONTENTIOUS_ACTION_NAME . '">' . $langs->trans("InvoiceBetterStatusSetAsNotContentiousAnymore") . '</a>';
                } else {
                    print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('InvoiceBetterStatusSetAsNotContentiousAnymore').'</a>';
                }
                print '</div>';
            }
            if (InvoiceBetterStatusTool::getCurrentStatus($object) == InvoiceBetterStatusTool::STATUS_LATE_PAYMENT) {
                //display button to set invoice as contentious
                print '<div class="inline-block divButAction">';
                if ($user->rights->invoicebetterstatus->invoicebetterstatus->setascontentious) {
                    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action='. self::SET_AS_CONTENTIOUS_ACTION_NAME . '">' . $langs->trans("InvoiceBetterStatusSetAsContentious") . '</a>';
                } else {
                    print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('InvoiceBetterStatusSetAsContentious').'</a>';
                }
                print '</div>';
            }
        }
    }

    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);
		global $user;
        if (in_array('invoicecard', $contexts) && $user->rights->invoicebetterstatus->invoicebetterstatus->read) {
			if($object->alreadypaid === null && method_exists($object, "getSommePaiement")) {
				$alreadypaid = $object->getSommePaiement();
				$object->alreadypaid = $alreadypaid ? $alreadypaid : 0;
			}
            $statusContent = InvoiceBetterStatusTool::getLibStatus($object, 6);
            if (empty($statusContent)) {
                $statusContent = InvoiceBetterStatusTool::getLibStatus($object, 4);
            }
            $statusContent = dol_escape_js($statusContent);
            print '<script>$(".statusref").not(".statusrefbis").html("' . $statusContent . '")</script>';
        }
    }

    /**
     * Overloading the showLinkedObjectBlock function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
	public function showLinkedObjectBlock($parameters, &$object, &$action, $hookmanager)
	{
		global $user;
		if ($object && $object->linkedObjects && $object->linkedObjects['facture'] && $user->rights->invoicebetterstatus->invoicebetterstatus->read) {
			$linkedInvoice = $object->linkedObjects['facture'];
			if(!empty($linkedInvoice)) {
				//We will update display status by jquery
				$resprint = "<script>$(document).ready(function(){";
					foreach($linkedInvoice as $invoice) {
						if($invoice->alreadypaid === null && method_exists($object, "getSommePaiement")) {
							$alreadypaid = $invoice->getSommePaiement();
							$invoice->alreadypaid = $alreadypaid ? $alreadypaid : 0;
						}
						$displayedContent = InvoiceBetterStatusTool::getLibStatus($invoice, 3);
						$displayedContent = dol_escape_js($displayedContent);
						$resprint .= '$("tr[data-element=\'facture\'][data-id=' . $invoice->id . ']").find("td.linkedcol-statut").html("' . $displayedContent . '");';
					}
				$resprint .= "}); </script>";
				print $resprint;
			}
        }
	}
}
