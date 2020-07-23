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
class InterfaceSynergiesTech extends DolibarrTriggers
{

    public $family = 'synergiestech';
    public $picto = 'technic';
    public $description = "Triggers of the module Synergies-Tech.";
    public $version = self::VERSION_DOLIBARR;

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
        global $db;
        switch ($action) {
            case 'REQUESTMANAGER_CREATE':
                dol_include_once('synergiestech/class/html.formsynergiestech.class.php');
                $formHtmlSynergiesTech = new FormSynergiesTech($db);
                $listOfContractOfThisBenefactorAndRequester = $formHtmlSynergiesTech->fetch_all_contract_for_these_company($object->socid, $object->socid_benefactor, true, true);
                $object->fetchObjectLinked();
                if (isset($object->linkedObjectsIds['equipement'])) {
                    $equipementListOfId = is_array($object->linkedObjectsIds['equipement']) ? $object->linkedObjectsIds['equipement'] : array(is_array($object->linkedObjectsIds['equipement']));
                    foreach($equipementListOfId as $equipementId){
                        $listOfContract = $formHtmlSynergiesTech->getContractLinkedToEquipementId($equipementId, true);
                        foreach($listOfContract as $contract){
                            $object->setContract($contract->id);
                        }
                    }
                }
                $request_types_add_contract = !empty($conf->global->SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING) ? explode(',', $conf->global->SYNERGIESTECH_AUTO_ADD_CONTRACT_IF_MISSING) : array();
                if (
                    !isset($object->linkedObjectsIds['equipement'])
                    && !isset($object->linkedObjectsIds['contrat'])
                    && in_array($object->fk_type, $request_types_add_contract)
                ) {
                    foreach ($listOfContractOfThisBenefactorAndRequester as $contractToAdd) {
                        $object->setContract($contractToAdd->id);
                    }
                }

                // Set the availability of the request at no by default if not created by API
                if (!$object->context['created_by_api']) {
                    $object->availability_for_thirdparty_principal = 0;
                    $object->availability_for_thirdparty_benefactor = 0;
                    $object->availability_for_thirdparty_watcher = 0;
                }
                $result = $object->update($user, 1);
                if ($result < 0) {
                    array_merge($this->errors, $object->errors);
                    return -1;
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'REQUESTMANAGER_MODIFY':
                // Assign begin and end date of the intervention planned when the request is planned
                if (!empty($conf->requestmanager->enabled) && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE) && !empty($object->context['rm_planning_intervention']) && $user->rights->requestmanager->planning->manage) {
                    dol_include_once('/extendedintervention/class/extendedintervention.class.php');
                    $object->fetchObjectLinked();

                    // Change the date of the intervention planned
                    if (is_array($object->linkedObjects['fichinter'])) {
                        foreach ($object->linkedObjects['fichinter'] as $intervention) {
                            if (!in_array($intervention->id, $object->context['rm_planning_intervention'])) continue;

                            $intervention->fetch_optionals();
                            $intervention->array_options['options_st_estimated_begin_date'] = $object->date_operation;
                            $intervention->array_options['options_st_estimated_end_date'] = $object->date_deadline;
                            if ($intervention->insertExtraFields('', $user) < 0) {
                                $this->error = $intervention->error;
                                $this->errors = $intervention->errors;
                                return -1;
                            }
                        }
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
            case 'REQUESTMANAGER_ADD_LINK':
                $addlink = $object->context['addlink'];
                $addlinkid = $object->context['addlinkid'];
                dol_include_once('synergiestech/class/html.formsynergiestech.class.php');
                $formHtmlSynergiesTech = new FormSynergiesTech($db);
                $listOfContractOfThisBenefactorAndRequester = $formHtmlSynergiesTech->fetch_all_contract_for_these_company($object->socid, $object->socid_benefactor, true, true);

                if ($addlink == 'equipement' && $addlinkid > 0) {
                    $listOfContract = $formHtmlSynergiesTech->getContractLinkedToEquipementId($addlinkid, true);
                        foreach($listOfContract as $contract){
                            $object->setContract($contract->id);
                        }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'RETURN_CREATE':
                if (isset($object->context['synergiestech_create_returnproducts']) && $object->context['synergiestech_create_returnproducts'] > 0) {
                    $id = $object->context['synergiestech_create_returnproducts'];

                    // todo link to create
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'LINEORDER_INSERT':
                if (isset($object->context['synergiestech_addline_not_into_formula'])) {
                    $langs->load('synergiestech@synergiestech');
                    $now = dol_now();

                    // Get order/thirdparty
                    require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                    $order = new Commande($this->db);
                    $order->fetch($object->fk_commande);
                    $order->fetch_thirdparty();

                    // Get product
                    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
                    $product = new Product($this->db);
                    $product->fetch($object->fk_product);

                    // Insertion action
                    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                    $actioncomm = new ActionComm($this->db);

                    $actioncomm->type_code = 'AC_SYN_FPHCC';
                    $actioncomm->code = 'AC_SYN_FPHCC';
                    $actioncomm->label = $langs->trans('SynergiesTechProductOffFormulaEventTitle');
                    $actioncomm->note = $langs->trans(
                        'SynergiesTechProductOffFormulaEventMessage',
                        $user->getNomUrl(1),
                        $product->getNomUrl(1),
                        $object->context['synergiestech_addline_not_into_formula'],
                        $order->getNomUrl(1)
                    );
                    $actioncomm->datep = $now;
                    $actioncomm->datef = $now;
                    $actioncomm->percentage = -1;   // Not applicable
                    $actioncomm->socid = $order->socid;
                    $actioncomm->authorid = $user->id;   // User saving action
                    $actioncomm->userownerid = $user->id;    // Owner of action

                    $actioncomm->fk_element = $order->id;
                    $actioncomm->elementtype = $order->element;

                    $ret = $actioncomm->create($user);       // User creating action
                    if ($ret > 0) {
                        return 1;
                    } else {
                        $error = "Failed to insert event : " . $actioncomm->errorsToString();
                        $this->error = $error;
                        $this->errors = $actioncomm->errors;

                        dol_syslog("interface_99_modSynergiesTech_SynergiesTech.class.php: " . $error, LOG_ERR);
                        return -1;
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'CONTRACT_CREATE':
                if (empty($object->array_options['options_rm_timeslots_periods'])) {
                    if (!empty($object->array_options['options_formule'])) {
                        // fetch optionals attributes and labels
                        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
                        $extrafields = new ExtraFields($this->db);
                        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

                        dol_include_once('/advancedictionaries/class/dictionary.class.php');
                        $dictionary = Dictionary::getDictionary($this->db, 'synergiestech', 'synergiestechtimeslot');
                        $res = $dictionary->getCodeFromFilter('{{time_slots}}', array('formula' => $extrafields->attribute_param['formule']['options'][$object->array_options['options_formule']]));
                        if (!is_numeric($res)) {
                            $object->array_options['options_rm_timeslots_periods'] = $res;
                            $object->insertExtraFields();
                        }
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'PROPAL_CLOSE_SIGNED':
            case 'PROPAL_CLOSE_REFUSED':
                // only for signed propal
                if ($object->statut == Propal::STATUS_SIGNED) {
                    // file is required for signed propal
                    if ($action == "PROPAL_CLOSE_SIGNED" && empty($_FILES['addfile']['name'])) {
                        $error_msg = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('File'));
                        // file is required
                        //setEventMessage($error_msg, 'errors');

                        dol_syslog(__METHOD__ . " Error : " . $error_msg, LOG_ERR);
                        //header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=statut&' . http_build_query($_POST));
                        //exit(0);
                        $this->error = $error_msg;
                        $this->errors[] = $error_msg;
                        $object->statut = $object->oldcopy->statut;
                        $object->date_cloture = $object->oldcopy->date_cloture;
                        $object->note_private = $object->oldcopy->note_private;
                        return -1;
                    }

                    // upload file
                    if (!empty($_FILES['addfile']['name'])) {
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                        // set directory
                        $filedir = $conf->propal->dir_output . "/" . dol_sanitizeFileName($object->ref);
                        $ret = dol_add_file_process($filedir, 0, 1, 'addfile');

                        if ($ret <= 0) {
                            dol_syslog(__METHOD__ . " Error dol_add_file_process : filedir=" . $filedir, LOG_ERR);
                            //header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=statut&' . http_build_query($_POST));
                            //exit(0);
                            $object->statut = $object->oldcopy->statut;
                            $object->date_cloture = $object->oldcopy->date_cloture;
                            $object->note_private = $object->oldcopy->note_private;
                            return -1;
                        }
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'ORDER_CREATE':
                if (isset($object->context['synergiestech_create_order_with_products_not_into_contract'])) {
                    $langs->load('synergiestech@synergiestech');
                    $now = dol_now();

                    // Get order/thirdparty
                    $order = new Commande($this->db);
                    $order->fetch($object->id);
                    $order->fetch_thirdparty();

                    // Insertion action
                    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                    $actioncomm = new ActionComm($this->db);

                    $actioncomm->type_code = 'AC_SYN_FPHCC';
                    $actioncomm->code = 'AC_SYN_FPHCC';
                    $actioncomm->label = $langs->trans('SynergiesTechProductsOffFormulaEventTitle');
                    $actioncomm->note = $langs->trans(
                        'SynergiesTechProductsOffFormulaEventMessage',
                        $user->getNomUrl(1),
                        $order->getNomUrl(1)
                    );
                    $actioncomm->datep = $now;
                    $actioncomm->datef = $now;
                    $actioncomm->percentage = -1;   // Not applicable
                    $actioncomm->socid = $order->socid;
                    $actioncomm->authorid = $user->id;   // User saving action
                    $actioncomm->userownerid = $user->id;    // Owner of action

                    $actioncomm->fk_element = $order->id;
                    $actioncomm->elementtype = $order->element;

                    $ret = $actioncomm->create($user);       // User creating action
                    if ($ret > 0) {
                        return 1;
                    } else {
                        $error = "Failed to insert event : " . $actioncomm->errorsToString();
                        $this->error = $error;
                        $this->errors = $actioncomm->errors;

                        dol_syslog(__METHOD__ . " " . $error, LOG_ERR);
                        return -1;
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'FICHINTER_CREATE':
                // Add all linked object of the contract when the origin is a request
                if ($conf->contrat->enabled && $object->origin == 'requestmanager' && $object->fk_contrat > 0) {
                    $contract = new Contrat($this->db);
                    $contract->fetch($object->fk_contrat);
                    $contract->fetchObjectLinked();
                    foreach ($contract->linkedObjectsIds as $et => $ids_list) {
                        foreach ($ids_list as $olid) {
                            if (($et == $object->origin && $olid == $object->origin_id) || ($et == $object->element && $olid == $object->id)) continue;
                            $object->add_object_linked($et, $olid);
                        }
                    }
                }

                // Delete lines of the inter
                $object->fetch_lines();
                foreach ($object->lines as $line) {
                    if ($line->deleteline($user) < 0) {
                        $this->error = $line->error;
                        $this->errors = $line->errors;
                        return -1;
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'FICHINTER_CLASSIFY_DONE':
                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    // Define output language
                    $outputlangs = $langs;
                    $newlang = '';
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
                    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
                    if (!empty($newlang)) {
                        $outputlangs = new Translate("", $conf);
                        $outputlangs->setDefaultLang($newlang);
                    }

                    require_once DOL_DOCUMENT_ROOT . '/core/modules/fichinter/modules_fichinter.php';
                    ob_start();
                    $result = fichinter_create($this->db, $object, $object->modelpdf, $outputlangs);
                    $result_txt = ob_get_contents();
                    ob_end_clean();
                    if (!($result > 0)) {
                        $this->errors[] = $result_txt;
                        return -1;
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                return 0;
            case 'ORDER_SUPPLIER_SUBMIT':
                if (!empty($conf->global->SYNERGIESTECH_ORDER_SUPPLIER_SUBMIT_CUSTOM_EVENT)) {
                    $langs->load("agenda");
                    $langs->load("other");
                    $langs->load("orders");

                    if (empty($object->actiontypecode)) $object->actiontypecode='AC_OTH_AUTO';

                    $msg = $langs->transnoentities("SupplierOrderSubmitedInDolibarr", ($object->newref ? $object->newref : $object->ref));
                    $msg .= "<br>" . $langs->trans("SynergiesTechSupplierOrderSubmitCustomEventDate", dol_print_date($object->date_commande, "dayhourtext"));
                    if ($object->methode_commande){
                        $msg .= "<br>" . $langs->trans("SynergiesTechSupplierOrderSubmitCustomEventMethod", dol_htmlentitiesbr_decode($object->getInputMethod()));
                    }
                    if(!empty($object->context['comments'])){
                        $msg .= "<br>" . $langs->trans("SynergiesTechSupplierOrderSubmitCustomEventComment", $object->context['comments']);
                    }

                    if (empty($object->actionmsg2)) {
                        $object->actionmsg2 = $langs->transnoentities("SupplierOrderSubmitedInDolibarr", ($object->newref ? $object->newref : $object->ref));;
                    }
                    $object->actionmsg = $msg;

                    $object->sendtoid = 0;
                    $object->actionmsg .= "<br>" . $langs->transnoentities("Author") . ': ' . $user->login;

                    dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

                    // Add entry in event table
                    $now = dol_now();

                    if (isset($_SESSION['listofnames-' . $object->trackid])) {
                        $attachs = $_SESSION['listofnames-' . $object->trackid];
                        if ($attachs && strpos($action, 'SENTBYMAIL')) {
                            $object->actionmsg = dol_concatdesc($object->actionmsg, "\n" . $langs->transnoentities("AttachedFiles") . ': ' . $attachs);
                        }
                    }

                    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
                    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                    $contactforaction = new Contact($this->db);
                    $societeforaction = new Societe($this->db);
                    // Set contactforaction if there is only 1 contact.
                    if (is_array($object->sendtoid)) {
                        if (count($object->sendtoid) == 1) $contactforaction->fetch(reset($object->sendtoid));
                    } else {
                        if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
                    }
                    // Set societeforaction.
                    if ($object->socid > 0)    $societeforaction->fetch($object->socid);

                    $projectid = isset($object->fk_project) ? $object->fk_project : 0;
                    if ($object->element == 'project') $projectid = $object->id;

                    // Insertion action
                    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                    $actioncomm = new ActionComm($this->db);
                    $actioncomm->type_code   = $object->actiontypecode;        // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                    $actioncomm->code        = 'AC_' . $action;
                    $actioncomm->label       = $object->actionmsg2;
                    $actioncomm->note        = $object->actionmsg;          // TODO Replace with $actioncomm->email_msgid ? $object->email_content : $object->actionmsg
                    $actioncomm->fk_project  = $projectid;
                    $actioncomm->datep       = $now;
                    $actioncomm->datef       = $now;
                    $actioncomm->durationp   = 0;
                    $actioncomm->punctual    = 1;
                    $actioncomm->percentage  = -1;   // Not applicable
                    $actioncomm->societe     = $societeforaction;
                    $actioncomm->contact     = $contactforaction;
                    $actioncomm->socid       = $societeforaction->id;
                    $actioncomm->contactid   = $contactforaction->id;
                    $actioncomm->authorid    = $user->id;   // User saving action
                    $actioncomm->userownerid = $user->id;    // Owner of action

                    $actioncomm->fk_element  = $object->id;
                    $actioncomm->elementtype = $object->element;

                    $ret = $actioncomm->create($user);       // User creating action
                    unset($object->actionmsg); unset($object->actionmsg2); unset($object->actiontypecode);	// When several action are called on same object, we must be sure to not reuse value of first action.

                    if ($ret > 0)
                    {
                        $_SESSION['LAST_ACTION_CREATED'] = $ret;
                        return 1;
                    }
                    else
                    {
                        $error ="Failed to insert event : ".$actioncomm->error." ".join(',', $actioncomm->errors);
                        $this->error=$error;
                        $this->errors=$actioncomm->errors;

                        dol_syslog("interface_99_modSynergiesTech_SynergiesTech: ".$this->error, LOG_ERR);
                        return -1;
                    }
                }
                return 0;
        }

        return 0;
    }
}
