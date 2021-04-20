<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2021      Synergies-Tech             <infra@synergies-france.fr>
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
 * \file    invoicelistcolumnsextension/class/actions_invoicelistcolumnsextension.class.php
 * \ingroup invoicelistcolumnsextension
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsInvoiceListColumnsExtension
 */
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

class ActionsInvoiceListColumnsExtension
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
		global $langs;
		$this->db = $db;

		$langs->load('invoicelistcolumnsextension@invoicelistcolumnsextension');
	}

	/**
	 * Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldPreListTitle(&$parameters, &$object, &$action, $hookmanager) {
		global $arrayfields, $langs;

		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			$arrayfields['pm.datep'] = array('label'=>$langs->trans("InvoiceListDateOfFirstPayment"), 'checked'=>1, 'position'=>1010);
		} else if (in_array('invoicelist', $contexts)) {
			$arrayfields['paiement.datep'] = array('label'=>$langs->trans("InvoiceListDateOfFirstPayment"), 'checked'=>1, 'position'=>1010);
		}
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
	function printFieldListSelect(&$parameters, &$object, &$action, $hookmanager)
	{
		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			$sql = ", pm.datep as dp";

			$this->resprints = $sql;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$sql = ", paiement.datep as dp";

			$this->resprints = $sql;
			return 1;
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
	function printFieldListFrom(&$parameters, &$object, &$action, $hookmanager)
	{
		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			$sql = " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn as pm on (pm.rowid = pf.fk_paiementfourn )";

			$this->resprints = $sql;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$sql = " LEFT JOIN ".MAIN_DB_PREFIX."paiement as paiement ON paiement.rowid = pf.fk_paiement";

			$this->resprints = $sql;
			return 1;
		}

		return 0;
	}

	/**
	 * Overloading the printFieldListWhere function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListWhere(&$parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			$sql = '';

			if ($this->getSelectedPaymentStartDate()) {
				$sql .= " AND pm.datep >= '".$db->idate($this->getSelectedPaymentStartDate())."'";
			}

			if ($this->getSelectedPaymentEndDate()) {
				$sql .= " AND pm.datep <= '".$db->idate($this->getSelectedPaymentEndDate())."'";
			}

			$this->resprints = $sql;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$sql = '';

			if ($this->getSelectedPaymentStartDate()) {
				$sql .= " AND paiement.datep >= '".$db->idate($this->getSelectedPaymentStartDate())."'";
			}

			if ($this->getSelectedPaymentEndDate()) {
				$sql .= " AND paiement.datep <= '".$db->idate($this->getSelectedPaymentEndDate())."'";
			}

			$this->resprints = $sql;
			return 1;
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
	function printFieldListGroupBy(&$parameters, &$object, &$action, $hookmanager)
	{
		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			$sql = ", pm.datep";

			$this->resprints = $sql;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$sql = ", paiement.datep";

			$this->resprints = $sql;
			return 1;
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
	function printFieldListOption(&$parameters, &$object, &$action, $hookmanager)
	{
		global $form, $langs;

		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			if (!empty($parameters['arrayfields']['pm.datep']['checked'])) {
				print '<td class="liste_titre center">';
				print '<div class="nowrap">';
				print $form->selectDate($this->getSelectedPaymentStartDate() ? $this->getSelectedPaymentStartDate() : -1, 'search_payment_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
				print '</div>';
				print '<div class="nowrap">';
				print $form->selectDate($this->getSelectedPaymentEndDate() ? $this->getSelectedPaymentEndDate() : -1, 'search_payment_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
				print '</div>';
				print '</td>';
			}

			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			if (!empty($parameters['arrayfields']['paiement.datep']['checked'])) {
				print '<td class="liste_titre center">';
				print '<div class="nowrap">';
				print $form->selectDate($this->getSelectedPaymentStartDate() ? $this->getSelectedPaymentStartDate() : -1, 'search_payment_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
				print '</div>';
				print '<div class="nowrap">';
				print $form->selectDate($this->getSelectedPaymentEndDate() ? $this->getSelectedPaymentEndDate() : -1, 'search_payment_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
				print '</div>';
				print '</td>';
			}

			return 1;
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
		$contexts = explode(':', $parameters['context']);

        if (in_array('supplierinvoicelist', $contexts)) {
			if (!empty($parameters['arrayfields']['pm.datep']['checked'])) {
				print_liste_field_titre($parameters['arrayfields']['pm.datep']['label'], $_SERVER['PHP_SELF'], 'pm.datep', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
			}

			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			if (!empty($parameters['arrayfields']['paiement.datep']['checked'])) {
				print_liste_field_titre($parameters['arrayfields']['paiement.datep']['label'], $_SERVER['PHP_SELF'], 'paiement.datep', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
			}

			return 1;
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
	function printFieldListValue(&$parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			if (!empty($parameters['arrayfields']['pm.datep']['checked'])) {     
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($parameters['obj']->dp), 'day');
                print '</td>';

                if (!$parameters['i']) $parameters['totalarray']['nbfield']++;
			}

			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			if (!empty($parameters['arrayfields']['paiement.datep']['checked'])) {     
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($parameters['obj']->dp), 'day');
                print '</td>';

                if (!$parameters['i']) $parameters['totalarray']['nbfield']++;
			}

			return 1;
		}

		return 0;
	}

	/**
     * Function to get current selected searched payment start date
     * @return  int unix timestamps date
     */
    private function getSelectedPaymentStartDate() {
		return dol_mktime(0, 0, 0, GETPOST('search_payment_date_startmonth', 'int'), GETPOST('search_payment_date_startday', 'int'), GETPOST('search_payment_date_startyear', 'int'));
	}

	/**
     * Function to get current selected searched payment end date
     * @return  int unix timestamps date
     */
    private function getSelectedPaymentEndDate() {
		return dol_mktime(23, 59, 59, GETPOST('search_payment_date_endmonth', 'int'), GETPOST('search_payment_date_endday', 'int'), GETPOST('search_payment_date_endyear', 'int'));
	}
}
?>