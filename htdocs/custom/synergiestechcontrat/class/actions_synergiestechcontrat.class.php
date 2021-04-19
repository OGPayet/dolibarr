<?php
/* Copyright (C)  SuperAdmin
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
 * \file    class/actions_synergiestechcontrat.class.php
 * \ingroup synergiestechcontrat
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class Actionssynergiestechcontrat
 */
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

class ActionsSynergiestechcontrat
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
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function ODTSubstitutionLine(&$parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        // si c'est un ouvrage
        if ($parameters['line']->product_type == 9) {
            $parameters['substitutionarray']['bold_text'] = 1;
        } else {
            $parameters['substitutionarray']['bold_text'] = 0;
        }

        $checkedHideDetails = (!isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DETAIL']) && $conf->global->OUVRAGE_HIDE_PRODUCT_DETAIL == 1) || (isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DETAIL']) && $_SESSION['OUVRAGE_HIDE_PRODUCT_DETAIL']
            == 1) ? 1 : 0;
        $checkedHideDesc    = (!isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DESCRIPTION']) && $conf->global->OUVRAGE_HIDE_PRODUCT_DESCRIPTION == 1) || (isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DESCRIPTION']) && $_SESSION['OUVRAGE_HIDE_PRODUCT_DESCRIPTION']
            == 1) ? 1 : 0;
        $checkedHideAmount  = (!isset($_SESSION['OUVRAGE_HIDE_MONTANT']) && $conf->global->OUVRAGE_HIDE_MONTANT == 1) || (isset($_SESSION['OUVRAGE_HIDE_MONTANT']) && $_SESSION['OUVRAGE_HIDE_MONTANT'] == 1)
                ? 1 : 0;

//        $conf->global->OUVRAGE_TYPE;
        //on cache la ligne
        if (!$checkedHideDetails) {
            $parameters['substitutionarray']['show_line'] = 0;
//            foreach ($parameters['substitutionarray'] as $key => $value) {
//                $parameters['substitutionarray'][$key]='';
//            }
        } else {
            $parameters['substitutionarray']['show_line'] = 1;
        }

//
        echo '<hr><pre>';
        //var_dump($parameters['substitutionarray']);
//        error_log(var_export($parameters, true));
//        die();
    }


    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0; // Error counter

        /*
          print_r($parameters);
          print_r($object);
          echo "action: " . $action;
         */

        if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {  // do something only for the context 'somecontext1' or 'somecontext2'
            foreach ($parameters['toselect'] as $objectid) {
                // Do action on each object id
            }
        }

        if (!$error) {
            $this->results   = array('myreturn' => 999);
            $this->resprints = 'A text to show';
            return 0;                                    // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

    /**
     * Overloading the addMoreMassActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0; // Error counter

        if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {  // do something only for the context 'somecontext'
            $this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("synergiestechcontratMassAction").'</option>';
        }

        if (!$error) {
            return 0;                                    // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

	function addMoreActionsButtons($parameters=false, &$object, &$action='') {
		global $conf,$user,$langs;

		if (is_array($parameters) && ! empty($parameters)) {
			foreach($parameters as $key=>$value) {
				$key=$value;
			}
		}

		$element = $object->element;
		if ($element == 'contrat' && $user->rights->synergiestechcontrat->terminate) {
	        print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/custom/synergiestechcontrat/tabs/terminate.php?id='.$object->id.'">Résiliation</a></div>';
		}
        return 0;
	}

    /**
     * Execute action
     *
     * @param	array	$parameters     Array of parameters
     * @param   Object	$object		   	Object output on PDF
     * @param   string	$action     	'add', 'update', 'view'
     * @return  int 		        	<0 if KO,
     *                          		=0 if OK but we want to process standard actions too,
     *  	                            >0 if OK and we want to replace standard actions.
     */
    public function beforePDFCreation($parameters, &$object, &$action) {
        global $db;

        $ret=0; $deltemp=array();
        dol_syslog(get_class($this).'::executeHooks action='.$action);

        $contexts = explode(':', $parameters['context']);

        if (in_array('pdfgeneration', $contexts)) {
            if ($object->element == "facture" && $object->model_pdf == "ouvrage_fact_st") {
                // Fetch linked objects to add commande and fichinter ref inside public note
                $object->fetchObjectLinked();
                if ($object->linkedObjectsIds) {
                    if ($object->linkedObjectsIds['commande']) {
                        foreach($object->linkedObjectsIds['commande'] as $commandeId) {
                            $commande = new Commande($db);
                            
                            $commande->fetch($commandeId);
                            
                            $object->note_public .= $parameters['outputlangs']->transnoentities("STCPurchaseOrderNumber") . ' : ' . $commande->ref . '<br />';
                        }
                    }

                    if ($object->linkedObjectsIds['fichinter']) {
                        foreach($object->linkedObjectsIds['fichinter'] as $fichinterId) {
                            $fichinter = new Fichinter($db);
                            
                            $fichinter->fetch($fichinterId);
                            
                            $object->note_public .= $parameters['outputlangs']->transnoentities("STCFichinterNumber") .  ' : ' . $fichinter->ref . '<br />';
                        }
                    }
                }

                if ($object->array_options) {
                    $customerPurchaseOrderNumber = $object->array_options['options_customer_purchase_order_number'];
                    if ($customerPurchaseOrderNumber) {
                        $object->note_public .= $parameters['outputlangs']->transnoentities("STCCustomerPurchaseOrderNumber") . ' : ' . $customerPurchaseOrderNumber . '<br />';
                    }
                }

                $ret = 1;
            }
        }

        return $ret;
    }
}
