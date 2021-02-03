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
     * @var FormSepaMandateDigitalSignatureRequest
     */
    public $formSepaMandateDigitalSignatureRequest;

    /**
     * Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        dol_include_once('/sepamandatmanager/class/html.formsepamandatedigitalsignaturerequest.class.php');
        $this->formSepaMandateDigitalSignatureRequest = new FormSepaMandateDigitalSignatureRequest($db);
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
        global $langs, $user, $conf;
        $errors = array();
        $contexts = explode(':', $parameters['context']);
        if (in_array('thirdpartybancard', $contexts) && ($action == 'edit' || $action == 'delete' || $action == 'confirm_delete')) {
            $id = GETPOST('id');
            if (SepaMandatCompanyBankAccountLink::isAMandateLinkedToThisCompanyAccountId($this->db, $id)) {
                $errors[] = $langs->trans('SepaMandateManagedByASepaMandate');
                $action = null;
            }
        }
        if ($action == FormSepaMandateDigitalSignatureRequest::DIGITAL_SIGNATURE_REQUEST_SET_SIGNERS_ACTION_NAME
            && $this->isUserAllowedToAddSepaMandate($object, $user)
        ) {
            $errors = $this->formSepaMandateDigitalSignatureRequest->checkSepaMandateInformationIntoPost();
            if (!empty($errors)) {
                $action = FormSepaMandateDigitalSignatureRequest::DIGITAL_SIGNATURE_REQUEST_REQUEST_SIGN_ACTION_NAME;
            }
        }

        if (!empty($conf->global->SEPAMANDATE_ADVANCEDBANKDIRECTDEBITASK_CONTROL) && $action == "new" && in_array('directdebitcard', $contexts)) {
            global $id;
            if ($this->isThisInvoiceIdInAnInProgressDebitVoucher($id)) {
                global $object;
                $errors[] = $langs->trans('SepaMandateInvoiceAlreadyInAnInProgressDebitVoucher', $object->ref);
            }
        }

        if (empty($errors)) {
            return 0;
        } else {
            $this->errors += $errors;
            return -1;
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
    public function addMoreFormQuestion($parameters, &$object, &$action, $hookmanager)
    {
        global $user;
        if ($this->isUserAllowedToAddSepaMandate($object, $user)) {
            if ($action == FormSepaMandateDigitalSignatureRequest::DIGITAL_SIGNATURE_REQUEST_REQUEST_SIGN_ACTION_NAME) {
                $this->results = array_merge($this->results ?? array(), $this->formSepaMandateDigitalSignatureRequest->getFormConfirmQuestionsToCreateSepaMandate($object->socid ?? $object->fk_soc));
            } elseif ($action == FormSepaMandateDigitalSignatureRequest::DIGITAL_SIGNATURE_REQUEST_SET_SIGNERS_ACTION_NAME) {
                $this->results = array_merge($this->results ?? array(), $this->formSepaMandateDigitalSignatureRequest->getHiddenQuestionToKeepSepaPostContent());
            }
        }
        return 0;
    }

    /**
     * Overloading the addMoreDocuments function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreDocuments($parameters, &$object, &$action, $hookmanager)
    {
        global $user;
        if ($this->isUserAllowedToAddSepaMandate($object, $user)) {
            if ($action == FormSepaMandateDigitalSignatureRequest::DIGITAL_SIGNATURE_REQUEST_SET_SIGNERS_ACTION_NAME
                && $this->formSepaMandateDigitalSignatureRequest->isThereSepaMandateInformationIntoPost()
            ) {
                $this->results = array_merge($this->results ?? array(), $this->formSepaMandateDigitalSignatureRequest->getFakeSepaMandateEcmDocuments());
                return 0;
            }
            if ($action == FormSepaMandateDigitalSignatureRequest::DIGITAL_SIGNATURE_REQUEST_CONFIRM_SET_SIGNERS_ACTION_NAME) {
                $documentsToAdd = $this->formSepaMandateDigitalSignatureRequest->manageCreateEcmSepaMandateToSign($user, $object->socid ?? $object->fk_soc);
                $this->errors += $this->formSepaMandateDigitalSignatureRequest->errors;
                if (is_array($documentsToAdd)) {
                    $this->results = array_merge($this->results ?? array(), $documentsToAdd);
                    return 0;
                } else {
                    return -1;
                }
            }
        }
    }

    /**
     * Overloading the afterPDFCreation function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function afterPDFCreation(&$parameters, &$object, &$action, $hookmanager)
    {
        global $user;
        $commonObject = &$parameters['object'];
        //We update sepa mandat manager
        if ($commonObject && $commonObject->element == SepaMandat::$staticElement) {
            if (!class_exists("ExtendedEcm")) {
                dol_include_once("/sepamandatmanager/class/extendedEcm.class.php");
            }
            $fileFullPath = $parameters['file'];
            $extendedEcm = new ExtendedEcm($this->db);
            $ecmFile = $extendedEcm->getInstanceFileFromItsAbsolutePath($fileFullPath);
            if ($ecmFile) {
                $commonObject->fk_generated_ecm = $ecmFile->id;
                if ($commonObject->update($user) > 0) {
                    return 0;
                } else {
                    $this->errors = $commonObject->errors;
                    return -1;
                }
            }
        }
        return 0;
    }

        /**
     * Overloading the doMassActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user, $conf;
        $errors = array();
        $contexts = explode(':', $parameters['context']);
        $massAction = $parameters['massaction'];
        if (!empty($conf->global->SEPAMANDATE_ADVANCEDBANKDIRECTDEBITASK_CONTROL) && in_array("invoicelist", $contexts) && $massAction == "withdrawrequest") {
            global $toselect;
            $invoiceIdsThatCantBeWithDrawNow = $this->getInvoiceIdsThatAreInAnInProgressDebitVoucher($toselect);
            foreach ($invoiceIdsThatCantBeWithDrawNow as $id) {
                $objectStatic = new Facture($this->db);
                $objectStatic->fetch($id);
                $errors[] = $langs->trans('SepaMandateInvoiceAlreadyInAnInProgressDebitVoucher', $objectStatic->ref);
            }
            foreach ($toselect as $index => $id) {
                if (in_array($id, $invoiceIdsThatCantBeWithDrawNow)) {
                    unset($toselect[$index]);
                }
            }
        }
        if (empty($errors)) {
            return 0;
        } else {
            $this->errors += $errors;
            return -1;
        }
    }

    /**
     * Function to know if user is allowed to add a Sepa Mandate on an object
     * @param CommonObject $object object to check
     * @param User $user User to check
     * @return bool
     */
    public function isUserAllowedToAddSepaMandate($object, $user)
    {
        return !empty($object) && !empty($user->rights->sepamandatmanager->sepamandat->write);
    }

    /**
     * Function to check if an invoice id is already in an inprogress debit voucher
     * @param int $invoiceId
     * @return bool
     */
    private function isThisInvoiceIdInAnInProgressDebitVoucher($invoiceId)
    {
        return !empty($this->getInvoiceIdsThatAreInAnInProgressDebitVoucher(array($invoiceId)));
    }

    /**
     * Function to get invoice that are already in an inprogress debit voucher
     * @param int[] $invoiceIds
     * @return int[]|null
     */
    private function getInvoiceIdsThatAreInAnInProgressDebitVoucher($invoiceIds)
    {
        $result = array();
        $sql = "SELECT DISTINCT pf.fk_facture as ref";
        $sql .= " FROM " . MAIN_DB_PREFIX . "prelevement_facture as pf";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "prelevement_lignes as pl ON (pf.fk_prelevement_lignes = pl.rowid)";
        $sql .= " WHERE pl.statut = 0";
        $sql .= " AND pf.fk_facture IN ("  . implode(",", $invoiceIds) . ")";
        $resql = $this->db->query($sql);
        if ($resql) {
            $i = 0;
            $num_rows = $this->db->num_rows($resql);
            while ($i < $num_rows) {
                $obj = $this->db->fetch_object($resql);
                $result[] = $obj->ref;
                $i++;
            }
        } else {
            $this->errors[] = $this->db->error();
            return null;
        }
        return $result;
    }
}
