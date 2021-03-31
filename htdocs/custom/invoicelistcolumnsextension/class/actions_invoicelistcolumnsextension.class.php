<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
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
			$arrayfields['pm.datep'] = array('label'=>$langs->trans("PayementDate"), 'checked'=>1, 'position'=>1010);
			$arrayfields['pm.ref'] = array('label'=>$langs->trans("PayementRef"), 'checked'=>1, 'position'=>1020);
		} else if (in_array('invoicelist', $contexts)) {
			$arrayfields['paiement.datep'] = array('label'=>$langs->trans("PayementDate"), 'checked'=>1, 'position'=>2010);
			$arrayfields['paiement.ref'] = array('label'=>$langs->trans("PayementRef"), 'checked'=>1, 'position'=>1020);
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
			$sql .= ", pm.ref as rp";

			$this->resprints = $sql;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$sql = ", paiement.datep as dp";
			$sql .= ", paiement.ref as rp";

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

			if ($this->getSelectedPaymentRef()) {
				if (is_numeric($this->getSelectedPaymentRef())) {
					$sql .= natural_search(array('pm.ref'), $this->getSelectedPaymentRef());
				} else {
					$sql .= natural_search('pm.ref', $this->getSelectedPaymentRef());
				}
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

			if ($this->getSelectedPaymentRef()) {
				if (is_numeric($this->getSelectedPaymentRef())) {
					$sql .= natural_search(array('paiement.ref'), $this->getSelectedPaymentRef());
				} else {
					$sql .= natural_search('paiement.ref', $this->getSelectedPaymentRef());
				}
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
			$sql .= ", pm.ref";

			$this->resprints = $sql;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$sql = ", paiement.datep";
			$sql .= ", paiement.ref";

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
		global $langs;

		$contexts = explode(':', $parameters['context']);

		if (in_array('supplierinvoicelist', $contexts)) {
			$out = '';

			if (!empty($parameters['arrayfields']['pm.datep']['checked'])) {
				$out .= '<td class="liste_titre center">';
				$out .= '<div class="nowrap">';
				$out .= $parameters['form']->selectDate($this->getSelectedPaymentStartDate() ? $this->getSelectedPaymentStartDate() : -1, 'search_payment_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
				$out .= '</div>';
				$out .= '<div class="nowrap">';
				$out .= $parameters['form']->selectDate($this->getSelectedPaymentEndDate() ? $this->getSelectedPaymentEndDate() : -1, 'search_payment_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
				$out .= '</div>';
				$out .= '</td>';
			}

			if (!empty($parameters['arrayfields']['pm.ref']['checked'])) {
				$out .= '<td class="liste_titre left">';
				$out .= '<input class="flat maxwidth50" type="text" name="search_payment_ref" value="'.$this->getSelectedPaymentRef().'">';
				$out .= '</td>';
			}

			$this->resprints = $out;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$out = '';

			if (!empty($parameters['arrayfields']['paiement.datep']['checked'])) {
				$out .= '<td class="liste_titre center">';
				$out .= '<div class="nowrap">';
				$out .= $parameters['form']->selectDate($this->getSelectedPaymentStartDate() ? $this->getSelectedPaymentStartDate() : -1, 'search_payment_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
				$out .= '</div>';
				$out .= '<div class="nowrap">';
				$out .= $parameters['form']->selectDate($this->getSelectedPaymentEndDate() ? $this->getSelectedPaymentEndDate() : -1, 'search_payment_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
				$out .= '</div>';
				$out .= '</td>';
			}

			if (!empty($parameters['arrayfields']['paiement.ref']['checked'])) {
				$out .= '<td class="liste_titre left">';
				$out .= '<input class="flat maxwidth50" type="text" name="search_payment_ref" value="'.$this->getSelectedPaymentRef().'">';
				$out .= '</td>';
			}

			$this->resprints = $out;
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
				$out = print_liste_field_titre($parameters['arrayfields']['pm.datep']['label'], $_SERVER['PHP_SELF'], 'pm.datep', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
			}

			if (!empty($parameters['arrayfields']['pm.ref']['checked'])) {
				$out = print_liste_field_titre($parameters['arrayfields']['pm.ref']['label'], $_SERVER['PHP_SELF'], 'pm.ref', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
			}

			$this->resprints = $out;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			if (!empty($parameters['arrayfields']['paiement.datep']['checked'])) {
				$out = print_liste_field_titre($parameters['arrayfields']['paiement.datep']['label'], $_SERVER['PHP_SELF'], 'paiement.datep', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
			}

			if (!empty($parameters['arrayfields']['paiement.ref']['checked'])) {
				$out = print_liste_field_titre($parameters['arrayfields']['paiement.ref']['label'], $_SERVER['PHP_SELF'], 'paiement.ref', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
			}

			$this->resprints = $out;
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
			$out = '';

			if (!empty($parameters['arrayfields']['pm.datep']['checked'])) {     
                $out .= '<td align="center" class="nowrap">';
                $out .= dol_print_date($db->jdate($parameters['obj']->dp), 'day');
                $out .= '</td>';

                if (!$parameters['i']) $parameters['totalarray']['nbfield']++;
			}

			if (!empty($parameters['arrayfields']['pm.ref']['checked'])) {   
				$payment = new PaiementFourn($db);
				$paymentRef = "'" . $parameters['obj']->rp . "'";
  
                $out .= '<td class="nowrap tdoverflowmax200">';
				if ($payment->fetch(-1, $paymentRef)) {
					$out .= $payment->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
				}
                $out .= '</td>';

                if (!$parameters['i']) $parameters['totalarray']['nbfield']++;
			}

			$this->resprints = $out;
			return 1;
		} else if (in_array('invoicelist', $contexts)) {
			$out = '';

			if (!empty($parameters['arrayfields']['paiement.datep']['checked'])) {     
                $out .= '<td align="center" class="nowrap">';
                $out .= dol_print_date($db->jdate($parameters['obj']->dp), 'day');
                $out .= '</td>';

                if (!$parameters['i']) $parameters['totalarray']['nbfield']++;
			}

			if (!empty($parameters['arrayfields']['paiement.ref']['checked'])) {     
				$payment = new Paiement($db);
				$paymentRef = "'" . $parameters['obj']->rp . "'";

                $out .= '<td class="nowrap tdoverflowmax200">';
				if ($payment->fetch(-1, $paymentRef)) {
                	$out .= $payment->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
				}
                $out .= '</td>';

                if (!$parameters['i']) $parameters['totalarray']['nbfield']++;
			}

			$this->resprints = $out;
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

	/**
     * Function to get current selected searched payment ref
     * @return  string
     */
    private function getSelectedPaymentRef() {
		return GETPOST('search_payment_ref', 'alpha');
	}
}
?>