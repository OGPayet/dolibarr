<?php
/* Copyright (C) 2016      Garcia MICHEL <garcia@soamichel.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

dol_include_once('/parcautomobile/class/parcautomobile.class.php');
dol_include_once('/compta/facture/class/facture.class.php');

class ActionsParcautomobile{
	protected $db;
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();


	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();
	function ActionsParcautomobile($db){
		$this->db = $db;
	}

	function doActions($parameters, &$object, &$action, $hookmanager){
		global $langs, $user, $confirm, $conf;

		$error = 0; // Error counter
		
		$documents = new parcautomobile($this->db);
		$langs->load('parcautomobile@parcautomobile');

		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		dol_include_once('/parcautomobile/class/parcautomobile.class.php');

		$extrafields 	= new ExtraFields($this->db);
		$parcautomobile = new parcautomobile($this->db);

		// print_r($parameters);
		// $context = $parameters['currentcontext'];

	 	// echo '<br>'.$context.'<br> <br>';


		// // print_r($user->rights->agenda->myactions->create);
		// if($context == 'invoicelist' || $context == 'invoicecard'){ /* Facture */
		// $parcautomobile->checkifmailsent("invoice","facture","facture_extrafields","AC_BILL_SENTBYMAIL","parcautomobilecode");
		// }
		// elseif($context == 'supplierinvoicelist' || $context == 'invoicesuppliercard'){ /* Facture Fournisseur*/
		// $parcautomobile->checkifmailsent("invoice_supplier","facture_fourn","facture_fourn_extrafields","AC_BILL_SUPPLIER_SENTBYMAIL","parcautomobilecode2");
		// }
		// elseif($context == 'propallist' || $context == 'propalcard'){ /* Propals */
		// $parcautomobile->checkifmailsent("propal","propal","propal_extrafields","AC_PROPAL_SENTBYMAIL","parcautomobilecode3");
		// }

		// // if($context == 'propallist' || $context == 'invoicelist' || $context == 'supplierinvoicelist')
		// $_SESSION["parcautomobile"] = $context;
		


	}


	/**
	 * Overloading the interface function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActionInterface($parameters, &$object, &$action, $hookmanager)
	{
	    $error = 0; // Error counter
	    global $langs, $db, $conf, $user;
	    
	}
	
}
