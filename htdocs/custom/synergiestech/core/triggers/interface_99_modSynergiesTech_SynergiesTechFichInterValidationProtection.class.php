<?php
/* Copyright (C) 2005-2014 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015      Bahfir Abbes         <bafbes@gmail.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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

/**
 *  \file       htdocs/synergiestech/core/triggers/interface_99_all.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for synergiestech module
 */
class InterfaceSynergiesTechFichInterValidationProtection extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "synergiestech";
        $this->description = "Triggers of the module Synergies-Tech..";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = self::VERSION_DOLIBARR;
        $this->picto = 'technic';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string		$action		Event action code
     * @param Object		$object     Object concerned. Some context information may also be provided into array property object->context.
     * @param User		    $user       Object user
     * @param Translate 	$langs      Object langs
     * @param conf		    $conf       Object conf
     * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->synergiestech->enabled)) return 0;     // Module not active, we do nothing
        switch ($action) {
                // Users
                //case 'USER_CREATE':
                //case 'USER_MODIFY':
                //case 'USER_NEW_PASSWORD':
                //case 'USER_ENABLEDISABLE':
                //case 'USER_DELETE':
                //case 'USER_SETINGROUP':
                //case 'USER_REMOVEFROMGROUP':

                // Actions
                //case 'ACTION_MODIFY':
                //case 'ACTION_CREATE':
                //case 'ACTION_DELETE':

                // Groups
                //case 'GROUP_CREATE':
                //case 'GROUP_MODIFY':
                //case 'GROUP_DELETE':

                // Companies
                //case 'COMPANY_CREATE':
                //case 'COMPANY_MODIFY':
                //case 'COMPANY_DELETE':

                // Contacts
                //case 'CONTACT_CREATE':
                //case 'CONTACT_MODIFY':
                //case 'CONTACT_DELETE':
                //case 'CONTACT_ENABLEDISABLE':

                // Products
                //case 'PRODUCT_CREATE':
                //case 'PRODUCT_MODIFY':
                //case 'PRODUCT_DELETE':
                //case 'PRODUCT_PRICE_MODIFY':
                //case 'PRODUCT_SET_MULTILANGS':
                //case 'PRODUCT_DEL_MULTILANGS':

                //Stock mouvement
                //case 'STOCK_MOVEMENT':

                //MYECMDIR
                //case 'MYECMDIR_CREATE':
                //case 'MYECMDIR_MODIFY':
                //case 'MYECMDIR_DELETE':

                // Customer orders
                //case 'ORDER_CREATE':
                //case 'ORDER_MODIFY':
                //case 'ORDER_VALIDATE':
                //case 'ORDER_DELETE':
                //case 'ORDER_CANCEL':
                //case 'ORDER_SENTBYMAIL':
                //case 'ORDER_CLASSIFY_BILLED':
                //case 'ORDER_SETDRAFT':
                //case 'LINEORDER_INSERT':
                //case 'LINEORDER_UPDATE':
                //case 'LINEORDER_DELETE':

                // Supplier orders
                //case 'ORDER_SUPPLIER_CREATE':
                //case 'ORDER_SUPPLIER_MODIFY':
                //case 'ORDER_SUPPLIER_VALIDATE':
                //case 'ORDER_SUPPLIER_DELETE':
                //case 'ORDER_SUPPLIER_APPROVE':
                //case 'ORDER_SUPPLIER_REFUSE':
                //case 'ORDER_SUPPLIER_CANCEL':
                //case 'ORDER_SUPPLIER_SENTBYMAIL':
                //case 'ORDER_SUPPLIER_DISPATCH':
                //case 'LINEORDER_SUPPLIER_DISPATCH':
                //case 'LINEORDER_SUPPLIER_CREATE':
                //case 'LINEORDER_SUPPLIER_UPDATE':
                //case 'LINEORDER_SUPPLIER_DELETE':

                // Proposals
                //case 'PROPAL_CREATE':
                //case 'PROPAL_MODIFY':
                //case 'PROPAL_VALIDATE':
                //case 'PROPAL_SENTBYMAIL':
                //case 'PROPAL_CLOSE_SIGNED':
                //case 'PROPAL_CLOSE_REFUSED':
                //case 'PROPAL_DELETE':
                //case 'LINEPROPAL_INSERT':
                //case 'LINEPROPAL_UPDATE':
                //case 'LINEPROPAL_DELETE':

                // SupplierProposal
                //case 'SUPPLIER_PROPOSAL_CREATE':
                //case 'SUPPLIER_PROPOSAL_MODIFY':
                //case 'SUPPLIER_PROPOSAL_VALIDATE':
                //case 'SUPPLIER_PROPOSAL_SENTBYMAIL':
                //case 'SUPPLIER_PROPOSAL_CLOSE_SIGNED':
                //case 'SUPPLIER_PROPOSAL_CLOSE_REFUSED':
                //case 'SUPPLIER_PROPOSAL_DELETE':
                //case 'LINESUPPLIER_PROPOSAL_INSERT':
                //case 'LINESUPPLIER_PROPOSAL_UPDATE':
                //case 'LINESUPPLIER_PROPOSAL_DELETE':

                // Contracts
                //case 'CONTRACT_CREATE':
                //case 'CONTRACT_MODIFY':
                //case 'CONTRACT_ACTIVATE':
                //case 'CONTRACT_CANCEL':
                //case 'CONTRACT_CLOSE':
                //case 'CONTRACT_DELETE':
                //case 'LINECONTRACT_INSERT':
                //case 'LINECONTRACT_UPDATE':
                //case 'LINECONTRACT_DELETE':

                // Bills
                //case 'BILL_CREATE':
                //case 'BILL_MODIFY':
                //case 'BILL_VALIDATE':
                //case 'BILL_UNVALIDATE':
                //case 'BILL_SENTBYMAIL':
                //case 'BILL_CANCEL':
                //case 'BILL_DELETE':
                //case 'BILL_PAYED':
                //case 'LINEBILL_INSERT':
                //case 'LINEBILL_UPDATE':
                //case 'LINEBILL_DELETE':

                //Supplier Bill
                //case 'BILL_SUPPLIER_CREATE':
                //case 'BILL_SUPPLIER_UPDATE':
                //case 'BILL_SUPPLIER_DELETE':
                //case 'BILL_SUPPLIER_PAYED':
                //case 'BILL_SUPPLIER_UNPAYED':
                //case 'BILL_SUPPLIER_VALIDATE':
                //case 'BILL_SUPPLIER_UNVALIDATE':
                //case 'LINEBILL_SUPPLIER_CREATE':
                //case 'LINEBILL_SUPPLIER_UPDATE':
                //case 'LINEBILL_SUPPLIER_DELETE':

                // Payments
                //case 'PAYMENT_CUSTOMER_CREATE':
                //case 'PAYMENT_SUPPLIER_CREATE':
                //case 'PAYMENT_ADD_TO_BANK':
                //case 'PAYMENT_DELETE':

                // Online
                //case 'PAYMENT_PAYBOX_OK':
                //case 'PAYMENT_PAYPAL_OK':
                //case 'PAYMENT_STRIPE_OK':

                // Donation
                //case 'DON_CREATE':
                //case 'DON_UPDATE':
                //case 'DON_DELETE':

                // Interventions
                //case 'FICHINTER_CREATE':
                //case 'FICHINTER_MODIFY':
            case 'FICHINTER_VALIDATE':
                if ($conf->global->SYNERGIESTECH_FICHINTER_PROTECTVALIDATEFICHINTER) {
                    //We check that user can validate this fichinter
                    dol_include_once('synergiestech/class/extendedInterventionValidation.class.php');
                    $InterventionValidationCheck = new ExtendedInterventionValidation($object, $this->db);
                    if (!$object->noValidationCheck) {
                        if (!$InterventionValidationCheck->canUserValidateThisFichInter($user)) {
                            if ($user->rights->synergiestech->intervention->validateWithStaleContract) {
                                $object->errors[] = $langs->trans("SynergiesTechInterventionValidationAdvancedError");
                            } else {
                                $object->errors[] = $langs->trans("SynergiesTechInterventionValidationStandardError");
                            }
                            setEventMessages("", $object->errors, 'errors');
                            return -1;
                        }
                    } elseif (!$InterventionValidationCheck->canUserValidateThisFichInter($user)) {
                        //Intervention is forced
                        // Insertion action
                        $actionLabel = $langs->trans('SynergiesTechValidateForcedActionLabel', $object->ref);
                        $actionMessage = $langs->trans('SynergiesTechValidateForcedActionMessage', $object->ref);
                        $actionMessage = dol_concatdesc($actionMessage, $langs->trans('SynergiesTechAuthor', $user->login));
                        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                        $now = dol_now();
                        $actioncomm = new ActionComm($this->db);
                        $actioncomm->type_code   = 'AC_OTH_AUTO';        // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                        $actioncomm->code        = 'AC_' . $action;
                        $actioncomm->label       = $actionLabel;
                        $actioncomm->note        = $actionMessage;          // TODO Replace with $actioncomm->email_msgid ? $object->email_content : $object->actionmsg
                        $actioncomm->fk_project  = isset($object->fk_project) ? $object->fk_project : 0;
                        $actioncomm->datep       = $now;
                        $actioncomm->datef       = $now;
                        $actioncomm->percentage  = -1;   // Not applicable
                        $actioncomm->socid       = $object->socid;
                        $actioncomm->authorid    = $user->id;   // User saving action
                        $actioncomm->userownerid = $user->id;    // Owner of action
                        $actioncomm->fk_element  = $object->id;
                        $actioncomm->elementtype = $object->element;
                        return $actioncomm->create($user);       // User creating action
                    }
                }
                break;
                //case 'FICHINTER_DELETE':
                //case 'FICHINTER_CLASSIFY_DONE':
                //case 'LINEFICHINTER_CREATE':
                //case 'LINEFICHINTER_UPDATE':
                //case 'LINEFICHINTER_DELETE':

                // Members
                //case 'MEMBER_CREATE':
                //case 'MEMBER_VALIDATE':
                //case 'MEMBER_SUBSCRIPTION':
                //case 'MEMBER_MODIFY':
                //case 'MEMBER_NEW_PASSWORD':
                //case 'MEMBER_RESILIATE':
                //case 'MEMBER_DELETE':

                // Categories
                //case 'CATEGORY_CREATE':
                //case 'CATEGORY_MODIFY':
                //case 'CATEGORY_DELETE':
                //case 'CATEGORY_SET_MULTILANGS':

                // Projects
                //case 'PROJECT_CREATE':
                //case 'PROJECT_MODIFY':
                //case 'PROJECT_DELETE':

                // Project tasks
                //case 'TASK_CREATE':
                //case 'TASK_MODIFY':
                //case 'TASK_DELETE':

                // Task time spent
                //case 'TASK_TIMESPENT_CREATE':
                //case 'TASK_TIMESPENT_MODIFY':
                //case 'TASK_TIMESPENT_DELETE':
                //case 'PROJECT_ADD_CONTACT':
                //case 'PROJECT_DELETE_CONTACT':
                //case 'PROJECT_DELETE_RESOURCE':

                // Shipping
                //case 'SHIPPING_CREATE':
                //case 'SHIPPING_MODIFY':
                //case 'SHIPPING_VALIDATE':
                //case 'SHIPPING_SENTBYMAIL':
                //case 'SHIPPING_BILLED':
                //case 'SHIPPING_CLOSED':
                //case 'SHIPPING_REOPEN':
                //case 'SHIPPING_DELETE':

                // and more...

            default:
                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }
        return 0;
    }
}
