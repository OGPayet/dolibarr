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
 * \file    contractlistcolumnsextension/class/actions_contractlistcolumnsextension.class.php
 * \ingroup contractlistcolumnsextension
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsContractListColumnsExtension
 */
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

class ActionsContractListColumnsExtension
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
     * State
    */
    const STATUS_NONE = 0;
	const STATUS_NOT_STARTED = 1;
	const STATUS_IN_PROGRESS = 2;
	const STATUS_FINISHED = 3;
	const STATUS_INDEFINITE = 4;

	/**
     * Contract state translation key label
    */
	public static $contractStateLabel = array(
		self::STATUS_NONE => '',
		self::STATUS_NOT_STARTED => "ModuleContractListNotStarted",
		self::STATUS_IN_PROGRESS => "ModuleContractListInProgress",
		self::STATUS_FINISHED => "ModuleContractListFinished",
		self::STATUS_INDEFINITE => "ModuleContractListIndefinite"
	);

	/**
     * State translated label
    */
    public static $cacheTranslatedContractStateLabel = array();

	/**
	 * Constructor
	 *
	 * @param        DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs;
		$this->db = $db;

		$langs->load('contractlistcolumnsextension@contractlistcolumnsextension');
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

		if (in_array('contractlist', $contexts)) {
			$arrayfields['cd.statut'] = array('label'=>$langs->trans("ModuleContractListState"), 'checked'=>1, 'position'=>1010);
		}
	}

	/**
	 * Overloading the printFieldListHaving function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListHaving(&$parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		$contexts = explode(':', $parameters['context']);

		if (in_array('contractlist', $contexts)) {
			$now = dol_now();

			switch ($this->getSelectedContractState()) {
				case self::STATUS_NOT_STARTED:
					$sql = ' HAVING SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')", 1, 0).') <= 0';
					$sql .= ' AND SUM('.$db->ifsql("cd.statut=5", 1, 0).') <= 0';
					$sql .= ' AND SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')", 1, 0).') <= 0';
					$sql .= ' AND SUM('.$db->ifsql("cd.statut=0", 1, 0).') > 0';
					break;
				case self::STATUS_IN_PROGRESS:
					$sql = ' HAVING SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')", 1, 0).') > 0';
					break;
				case self::STATUS_FINISHED:
					$sql = ' HAVING SUM('.$db->ifsql("cd.statut=0", 1, 0).') = 0';
					$sql .= ' AND SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')", 1, 0).') = 0';
					$sql .= ' AND (SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')", 1, 0).') > 0';
					$sql .= ' OR SUM('.$db->ifsql("cd.statut=5", 1, 0).') > 0)';
					break;
				case self::STATUS_INDEFINITE:
					$sql = ' HAVING (SUM('.$db->ifsql("cd.statut=0", 1, 0).') = 0';
					$sql .= ' AND SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')", 1, 0).') = 0';
					$sql .= ' AND SUM('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')", 1, 0).') = 0';
					$sql .= ' AND SUM('.$db->ifsql("cd.statut=5", 1, 0).') = 0)';
					$sql .= ' OR (SUM('.$db->ifsql("cd.statut=0", 1, 0).') > 1 AND SUM('.$db->ifsql("cd.statut=5", 1, 0).') > 1)';
					break;
			}

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
		global $form, $langs, $conf;

		$contexts = explode(':', $parameters['context']);

		if (in_array('contractlist', $contexts)) {
			if (!empty($parameters['arrayfields']['cd.statut']['checked'])) {
				print '<td class="liste_titre maxwidthonsmartphone center">';
				print $form->selectarray("search_contract_state", self::getStateLabelArrayTranslated(), $this->getSelectedContractState(), 0, 0, 0, '', 0, 0, 0);
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

        if (in_array('contractlist', $contexts)) {
			if (!empty($parameters['arrayfields']['cd.statut']['checked'])) {
				print_liste_field_titre($parameters['arrayfields']['cd.statut']['label'], $_SERVER['PHP_SELF'], 'cd.statut', '', $parameters['param'], 'align="center"', $parameters['sortfield'], $parameters['sortorder']);
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

		if (in_array('contractlist', $contexts)) {
			if (!empty($parameters['arrayfields']['cd.statut']['checked'])) {
				print '<td align="center" class="nowrap">';

				if ($parameters['obj']->nb_running > 0) {
					print $langs->trans("ModuleContractListInProgress");
				} else if ($parameters['obj']->nb_running <= 0 && $parameters['obj']->nb_closed <= 0 && $parameters['obj']->nb_expired <= 0 && $parameters['obj']->nb_initial > 0) {
					print $langs->trans("ModuleContractListNotStarted");
				} else if ($parameters['obj']->nb_running == 0 && $parameters['obj']->nb_initial == 0 && ($parameters['obj']->nb_expired > 0 || $parameters['obj']->nb_closed > 0)) {
					print $langs->trans("ModuleContractListFinished");
				} else if (($parameters['obj']->nb_running == 0 && $parameters['obj']->nb_initial == 0 && $parameters['obj']->nb_expired == 0 && $parameters['obj']->nb_closed == 0) || ($parameters['obj']->nb_initial > 0 && $parameters['obj']->nb_closed > 0)) {
					print $langs->trans("ModuleContractListIndefinite");
				}

				print '</td>';
			}
		}

		return 0;
	}

	/**
     * Function to get current selected intervention context ( = contract or commande ref)
     * @return  string
     */
    private function getSelectedContractState() {
		return GETPOST('search_contract_state', 'alpha');
	}

	/**
     * Function to get translated state
     */
    public static function getStateLabelArrayTranslated() {
        if (empty(self::$cacheTranslatedContractStateLabel)) {
			global $langs;

			self::loadTranslatedState($langs);
		}

		return self::$cacheTranslatedContractStateLabel;
    }

    /**
	 * Function to load translated state
	 * @return void
	 */
	public static function loadTranslatedState($langs) {
		foreach (self::$contractStateLabel as $value => $translateKey) {
            self::$cacheTranslatedContractStateLabel[$value] = $translateKey;

            if (method_exists($langs, "trans")) {
                self::$cacheTranslatedContractStateLabel[$value] = $langs->trans($translateKey);
            }
        }
	}
}
?>