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
 * \file    fichinterlistcolumnsextension/class/actions_fichinterlistcolumnsextension.class.php
 * \ingroup fichinterlistcolumnsextension
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsFichinterListColumnsExtension
 */
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";

class ActionsFichinterListColumnsExtension
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

		$langs->load('fichinterlistcolumnsextension@fichinterlistcolumnsextension');
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

		if (in_array('interventionlist', $contexts)) {
			$arrayfields['co.ref'] = array('label'=>$langs->trans("FichinterListInterventionFramework"), 'checked'=>1, 'position'=>1010);
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

		if (in_array('interventionlist', $contexts)) {

		}
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

		if (in_array('interventionlist', $contexts)) {
			$sql = " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el on f.rowid = el.fk_target";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as co on el.fk_source = co.rowid";

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

		if (in_array('interventionlist', $contexts)) {
			$sql = '';

			if ($this->getSelectedInterventionFramework()) {
				$sql = " AND (c.ref = '" . $this->getSelectedInterventionFramework() . "'"; 
				$sql .= " OR co.ref = '" . $this->getSelectedInterventionFramework() . "')";
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

		if (in_array('interventionlist', $contexts)) {
			$sql = " GROUP BY f.ref";

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

		if (in_array('interventionlist', $contexts)) {
			if (!empty($parameters['arrayfields']['co.ref']['checked'])) {
				print '<td class="liste_titre center">';
				print '<input type="text" class="flat" name="search_intervention_framework" value="'.$this->getSelectedInterventionFramework().'" size="8">';
				print '</td>';
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
		$contexts = explode(':', $parameters['context']);

        if (in_array('interventionlist', $contexts)) {
			if (!empty($parameters['arrayfields']['co.ref']['checked'])) {
				print_liste_field_titre($parameters['arrayfields']['co.ref']['label'], $_SERVER['PHP_SELF'], 'co.ref', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
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
	function printFieldListValue(&$parameters, &$object, &$action, $hookmanager)
	{
		global $object, $langs, $db;

		$contexts = explode(':', $parameters['context']);

		if (in_array('interventionlist', $contexts)) {
			if (!empty($parameters['arrayfields']['co.ref']['checked'])) {
				print '<td align="center" class="nowrap">';

				$contratstatic = new Contrat($db);
				$contratstatic->id = $parameters['obj']->contrat_id;
				$contratstatic->ref = $parameters['obj']->contrat_ref;
				$contratstatic->ref_customer = $parameters['obj']->contrat_ref_customer;
				$contratstatic->ref_supplier = $parameters['obj']->contrat_ref_supplier;

				if ($contratstatic->id > 0) {
					print $contratstatic->getNomUrl(1);
					print '</td>';
				} else {
					$fichinter = new Fichinter($db);
					$fichinter->fetch($parameters['obj']->rowid);
					$fichinter->fetchObjectLinked();

					if ($fichinter->linkedObjectsIds) {
						if ($fichinter->linkedObjectsIds['commande']) {
							$commandeId = '';
							foreach($fichinter->linkedObjectsIds['commande'] as $commId) {
								$commandeId = $commId;
							}

							$commande = new Commande($db);
							$commande->fetch($commandeId);

							print $commande->getNomUrl(1, '');
							print '</td>';
						}
					} else {
						print '<span class="fas fa-exclamation-triangle pictowarning pictowarning" title="' . $langs->trans("FichinterListAlertNoContractAndNoPurchaseOrder") . '"></span>';
						print '</td>';
					}
				}

				if (!$parameters['i']) $parameters['totalarray']['nbfield']++;
			}
		}

		return 0;
	}

	/**
     * Function to get current selected intervention framework ( = contract or commande ref)
     * @return  string
     */
    private function getSelectedInterventionFramework() {
		return GETPOST('search_intervention_framework', 'alpha');
	}
}
?>