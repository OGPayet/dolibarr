<?php
/* Copyright (C) 2020 Alexis LAURIER
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
 * \file    sepamandatmanager/class/actions_sepamandatmanager.class.php
 * \ingroup sepamandatmanager
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */
dol_include_once('/sepamandatmanager/class/sepamandatcompanybankaccountlink.class.php');

/**
 * Class ActionsSepaMandatManager
 */
class ActionsSepaMandatManager
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
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
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
		global $langs;
		$errors = array();
		$contexts = explode(':', $parameters['context']);
		if (in_array('thirdpartybancard', $contexts) && ($action == 'edit' || $action == 'delete' || $action == 'confirm_delete')) {
			$id = GETPOST('id');
			if(SepaMandatCompanyBankAccountLink::isAMandateLinkedToThisCompanyAccountId($this->db, $id)) {
				$errors[] = $langs->trans('SepaMandateManagedByASepaMandate');
				$action = null;
			}
		}

		if (empty($errors)) {
			// $this->results = array('myreturn' => 999);
			// $this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors = array_merge($this->errors, $errors);
			return -1;
		}
	}
}
