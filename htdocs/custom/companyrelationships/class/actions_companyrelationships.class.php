<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/companyrelationships/class/actions_companyrelationships.class.php
 * \ingroup companyrelationships
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsCompanyRelationships
 */
class ActionsCompanyRelationships
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
        $this->db = $db;
    }


    /**
     * Overloading the beforePDFCreation function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function beforePDFCreation($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {
            dol_include_once('/companyrelationships/class/companyrelationships.class.php');

            if (!empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {
                if ($object->element == 'fichinter') {
                    // get benefactor of this element
                    $benefactorId = $object->array_options['options_companyrelationships_fk_soc_benefactor'];

                    if ($benefactorId > 0) {
                        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                        $companyRecipient = new Societe($this->db);
                        $companyRecipient->fetch($benefactorId);

                        if ($companyRecipient->id > 0) {
                            $object->thirdparty = $companyRecipient;
                        }
                    }
                }
            }
        }

        return 0;
    }


    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    /*
    function createFrom($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractcard', $contexts)) {
            // specific for contract only
            if ($object->element == "contrat") {
                if ($object->context['createfromclone'] == 'createfromclone') {
                    if (isset($parameters['objFrom']) && isset($parameters['clonedObj'])) {
                        $fk_soc_benefactor = GETPOST('options_companyrelationships_fk_soc_benefactor', 'int');

                        $objFrom  = $parameters['objFrom'];
                        $cloneObj = $parameters['clonedObj'];

                        // /!\ socid maybe different of fk_soc if cloned
                        $cloneObj->fk_soc = $cloneObj->socid;
                        $cloneObj->array_options['options_companyrelationships_fk_soc_benefactor'] = $fk_soc_benefactor;

                        // /!\ update is non common in all objects (socid maybe diiferent of fk_soc)
                        $result = $cloneObj->update($user, 1);
                        if ($result < 0) {
                            $objFrom->errors[] = $cloneObj->errorsToString();
                            return -1;
                        }
                    }
                }
            }
        }

        return 0;
    }
    */


    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {

            dol_include_once('/companyrelationships/class/companyrelationships.class.php');

            if (!empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {

                if ($object->element == 'fichinter') {
                    $userRightsElementCreer = $user->rights->ficheinter->creer;
                } else if ($object->element == 'order_supplier') {
                    $userRightsElementCreer = $user->rights->fournisseur->commande->creer;
                } else {
                    $userRightsElementCreer = $user->rights->{$object->table_element}->creer;
                }

                $object->cr_confirm_socid = GETPOST('cr_confirm_socid', 'int');

                // action confirm principal company on create
                if ($action == 'add' && $userRightsElementCreer) {
                    if (!$conf->extendedintervention->enabled || $object->element != 'fichinter' || !$object->force_out_of_quota) {
                        $socid = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : 0;
                        $originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility

                        if (!$object->cr_confirm_socid && empty($originid) && intval($socid) > 0) {
                            $companyRelationships = new CompanyRelationships($this->db);
                            $principalCompanyList = $companyRelationships->getRelationshipsThirdparty($socid, CompanyRelationships::RELATION_TYPE_BENEFACTOR, 0, 1);
                            $principalCompanyList = is_array($principalCompanyList) ? $principalCompanyList : array();
                            if (count($principalCompanyList) > 0) {
                                // it doesn't work because object is new in create mode
                                //$object->cr_must_confirm_socid = true;
                                $action = 'create';
                                return 1;
                            }
                        }
                    }
                }

                // action confirm principal company on create
                if ($action == 'companyrelationships_confirm_socid' && $userRightsElementCreer) {
                    // it doesn't work because object is new in create mode
                    //$object->cr_confirm_socid = 1;
                    $action = 'create';
                }
                // update extra fields
                else if ($action == 'update_extras' && $userRightsElementCreer) {
                    $attribute = GETPOST('attribute', 'alpha');

                    // update benefactor company
                    if ($attribute=='companyrelationships_fk_soc_benefactor') {

                        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

                        $langs->load('companyrelationships@companyrelationships');

                        $error = 0;

                        $relation_type  = CompanyRelationships::RELATION_TYPE_BENEFACTOR;
                        $relation_socid = GETPOST('options_companyrelationships_fk_soc_benefactor', 'int');

                        // if benefactor company changed
                        if (!empty($relation_socid) && $relation_socid!=$object->array_options['options_companyrelationships_fk_soc_benefactor']) {
                            // save a copy of this object
                            $objectClone = clone $object;

                            $companyRelationships    = new CompanyRelationships($this->db);
                            $publicSpaceAvailability = $companyRelationships->getPublicSpaceAvailabilityThirdparty($object->socid, $relation_type, $relation_socid, $object->element);

                            if (!is_array($publicSpaceAvailability)) {
                                $error++;
                                $object->error = $companyRelationships->error;
                                $object->errors = $companyRelationships->errors;
                            }

                            if (! $error) {
                                // modify options with company relationships default availability
                                $objectClone->array_options['options_companyrelationships_fk_soc_benefactor']       = $relation_socid;
                                $objectClone->array_options['options_companyrelationships_availability_principal']  = $publicSpaceAvailability['principal'];
                                $objectClone->array_options['options_companyrelationships_availability_benefactor'] = $publicSpaceAvailability['benefactor'];

                                $result = $objectClone->insertExtraFields();
                                if ($result < 0) {
                                    $error++;
                                    $object->error  = $objectClone->error;
                                    $object->errors = $objectClone->errors;
                                }
                            }

                            if ($error) {
                                setEventMessages($object->error, $object->errors, 'errors');
                                $action = 'edit_extras';
                            } else {
                                header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                                exit();
                            }
                        }
                    }
                    // update watcher company
                    else if ($attribute=='companyrelationships_fk_soc_watcher') {

                        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

                        $langs->load('companyrelationships@companyrelationships');

                        $error = 0;

                        $relation_type  = CompanyRelationships::RELATION_TYPE_WATCHER;
                        $relation_socid = GETPOST('options_companyrelationships_fk_soc_watcher', 'int');

                        // if watcher company changed
                        if (!empty($relation_socid) && $relation_socid!=$object->array_options['options_companyrelationships_fk_soc_watcher']) {
                            // save a copy of this object
                            $objectClone = clone $object;

                            $companyRelationships    = new CompanyRelationships($this->db);
                            $publicSpaceAvailability = $companyRelationships->getPublicSpaceAvailabilityThirdparty($object->socid, $relation_type, $relation_socid, $object->element);

                            if (!is_array($publicSpaceAvailability)) {
                                $error++;
                                $object->error  = $companyRelationships->error;
                                $object->errors = $companyRelationships->errors;
                            }

                            if (! $error) {
                                // modify options with company relationships default availability
                                $objectClone->array_options['options_companyrelationships_fk_soc_watcher']       = $relation_socid;
                                $objectClone->array_options['options_companyrelationships_availability_watcher'] = $publicSpaceAvailability['watcher'];

                                $result = $objectClone->insertExtraFields();
                                if ($result < 0) {
                                    $error++;
                                    $object->error  = $objectClone->error;
                                    $object->errors = $objectClone->errors;
                                }
                            }

                            if ($error) {
                                setEventMessages($object->error, $object->errors, 'errors');
                                $action = 'edit_extras';
                            } else {
                                header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                                exit();
                            }
                        }
                    }
                }
                // action clone object
                /*
                else if ($action == 'confirm_clone' && $confirm == 'yes' && $userRightsElementCreer)
                {
                    if (!GETPOST('socid', 'int')) {
                        setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
                    } else {
                        $socid = GETPOST('socid', 'int');
                        $fk_soc_benefactor = GETPOST('options_companyrelationships_fk_soc_benefactor', 'int');

                        if ($object->id > 0) {

                            // Because createFromClone modifies the object, we must clone it so that we can restore it later
                            $orig = clone $object;

                            $object->array_options['options_companyrelationships_fk_soc_benefactor'] = $fk_soc_benefactor;

                            // propal only
                            if ($object->element == "propal") {
                                if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY)) {
                                    //Get difference between old and new delivery date and change lines according to difference
                                    $date_delivery = dol_mktime(12, 0, 0,
                                        GETPOST('date_deliverymonth', 'int'),
                                        GETPOST('date_deliveryday', 'int'),
                                        GETPOST('date_deliveryyear', 'int')
                                    );
                                    if (!empty($object->date_livraison) && !empty($date_delivery)) {
                                        //Attempt to get the date without possible hour rounding errors
                                        $old_date_delivery = dol_mktime(12, 0, 0,
                                            dol_print_date($object->date_livraison, '%m'),
                                            dol_print_date($object->date_livraison, '%d'),
                                            dol_print_date($object->date_livraison, '%Y')
                                        );
                                        //Calculate the difference and apply if necessary
                                        $difference = $date_delivery - $old_date_delivery;
                                        if ($difference != 0) {
                                            $object->date_livraison = $date_delivery;
                                            foreach ($object->lines as $line) {
                                                if (isset($line->date_start)) $line->date_start = $line->date_start + $difference;
                                                if (isset($line->date_end)) $line->date_end = $line->date_end + $difference;
                                            }
                                        }
                                    }
                                }
                            }

                            $result = $object->createFromClone($socid);
                            if ($result > 0) {
                                header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
                                exit();
                            } else {
                                //setEventMessages($object->error, $object->errors, 'errors');
                                $object->error = 'New error on clone';
                                $object = $orig;
                                $action = '';
                            }
                        }
                    }
                }
                */
            }
        }

        return 0;
    }


    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */

    function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {
            dol_include_once('/companyrelationships/class/companyrelationships.class.php');

            if (!empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {

                if ($action == 'clone') {
                    dol_include_once('/companyrelationships/class/html.formcompanyrelationships.class.php');

                    $langs->load('companyrelationships@companyrelationships');

                    $formcompanyrelationships = new FormCompanyRelationships($this->db);
                    $form = $formcompanyrelationships->form;

                    $out = '';

                    $socid = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : $object->socid;
                    //$fk_soc_benefactor = $object->array_options['options_companyrelationships_fk_soc_benefactor'];

                    // events
                    //$events = array();
                    //$events[] = array('action' => 'getBenefactor', 'url' => dol_buildpath('/companyrelationships/ajax/benefactor.php', 1), 'htmlname' => 'options_companyrelationships_fk_soc_benefactor', 'more_data' => array('fk_soc_benefactor' => $fk_soc_benefactor));

                    // Create an array for form
                    $formquestion = array(
                        // 'text' => $langs->trans("ConfirmClone"),
                        // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
                        // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' =>
                        // 1),
                        array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company($socid, 'socid', '(s.client=1 OR s.client=2 OR s.client=3) AND status=1', '', 0, 0, null, 0, 'minwidth300'))
                    );
                    if ($object->element == "propal") {
                        if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY) && !empty($object->date_livraison)) {
                            $formquestion[] = array('type' => 'date','name' => 'date_delivery','label' => $langs->trans("DeliveryDate"),'value' => $object->date_livraison);
                        }
                    }

                    // add warning
                    $formquestion[] = array('type' => 'onecolumn', 'value' => '<div style="color: red;">' . $langs->trans('CompanysRelationshipsWarningCloneConfirmBenefactor') . '</div>');

                    // add benefactor list
                    //$formquestion[] = array('label' => $langs->trans('CompanyRelationshipsBenefactorCompany'), 'name' => 'options_companyrelationships_fk_soc_benefactor', 'type' => 'select', 'values' => array($fk_soc_benefactor), 'default' => '');

                    // form confirm
                    $fomrConfirmUrlId = 'id=' . $object->id;
                    $fomrConfirmTitle = 'Clone';
                    if ($object->element == "commande") {
                        $fomrConfirmTitle .= 'Order';
                    } else if ($object->element == "facture") {
                        $fomrConfirmTitle .= 'Invoice';
                        $fomrConfirmUrlId = 'facid=' . $object->id;
                    } else if ($object->element == "fichinter") {
                        $fomrConfirmTitle .= 'Intervention';
                    } else if ($object->element == "contrat") {
                        $fomrConfirmTitle .= 'Contract';
                    } else {
                        $fomrConfirmTitle .= ucfirst($object->element);
                    }
                    $fomrConfirmQuestion = 'Confirm' . $fomrConfirmTitle;
                    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?' . $fomrConfirmUrlId, $langs->trans($fomrConfirmTitle), $langs->trans($fomrConfirmQuestion, $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 300, 800);

                    $out .= $formconfirm;

                    $this->resprints = $out;

                    return 1;
                }
            }
        }

        return 0;
    }


    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $contexts = explode(':', $parameters['context']);

        if (in_array('globalcard', $contexts)) {
            dol_include_once('/companyrelationships/class/companyrelationships.class.php');

            // /!\ element shipping (uses expediton/shipment.php for edit mode and uses expedition/card.php has only create card)
            if (!empty($object->table_element) && !empty($object->element) && in_array($object->element, CompanyRelationships::$psa_element_list)) {

                if ($object->element == 'fichinter') {
                    $userRightsElementCreer = $user->rights->ficheinter->creer;
                } else if ($object->element == 'order_supplier') {
                    $userRightsElementCreer = $user->rights->fournisseur->commande->creer;
                } else {
                    $userRightsElementCreer = $user->rights->{$object->table_element}->creer;
                }

                // create
                if ($action == 'create' && $userRightsElementCreer) {
                    dol_include_once('/companyrelationships/class/html.formcompanyrelationships.class.php');

                    $langs->load('companyrelationships@companyrelationships');

                    $out = '';

                    $socid = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : 0;
                    $confirm = GETPOST('confirm', 'alpha');
                    $origin = GETPOST('origin', 'alpha');
                    $originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility

                    // come from confirm_socid dialog box
                    $fk_soc_benefactor = GETPOST('companyrelationships_fk_soc_benefactor', 'int') ? GETPOST('companyrelationships_fk_soc_benefactor', 'int') : 0;
                    $fk_soc_watcher    = GETPOST('companyrelationships_fk_soc_watcher', 'int') ? GETPOST('companyrelationships_fk_soc_watcher', 'int') : 0;

                    // set default values for socid and fk_soc_benefactor if this element linked to a previous element (origin)
                    if (!empty($originid) && intval($fk_soc_benefactor) <= 0) {

                        if (intval($fk_soc_benefactor) <= 0) {
                            $fk_soc_benefactor = $object->array_options['options_companyrelationships_fk_soc_benefactor'];
                        }

                        // object src is defined (create from original card)
                        if (isset($parameters['objectsrc']) && is_object($parameters['objectsrc'])) {
                            $objectsrc = $parameters['objectsrc'];

                            dol_syslog("Try to find source object origin=" . $origin . " originid=" . $originid);
                            $objectsrc->fetch($originid);

                            if (intval($socid) <= 0 && $objectsrc->socid > 0) {
                                $socid = $objectsrc->socid;
                            }

                            if (intval($fk_soc_benefactor) <= 0 && isset($objectsrc->array_options['options_companyrelationships_fk_soc_benefactor'])) {
                                $fk_soc_benefactor = $objectsrc->array_options['options_companyrelationships_fk_soc_benefactor'];
                            }
                        } else {
                            // for shipping (no object src in parameters) with order origin
                            if ($origin == 'commande' && $originid > 0) {
                                // fetch origin object (commande)
                                require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                                $objectsrc = new Commande($this->db);

                                dol_syslog("Try to find order source object origin=" . $origin . " originid=" . $originid);
                                $objectsrc->fetch($originid);

                                if (intval($socid) <= 0 && $objectsrc->socid > 0) {
                                    $socid = $objectsrc->socid;
                                }

                                if (intval($fk_soc_benefactor) <= 0 && isset($objectsrc->array_options['options_companyrelationships_fk_soc_benefactor'])) {
                                    $fk_soc_benefactor = $objectsrc->array_options['options_companyrelationships_fk_soc_benefactor'];
                                }
                            }
                        }
                    }

                    $formcompanyrelationships = new FormCompanyRelationships($this->db);
                    $companyRelationships = new CompanyRelationships($this->db);

                    // get the name of form to keep values and to submit
                    $formName = $companyRelationships->getFormNameForElementAndAction($object->element, $action);

                    // form confirm to choose the principal company
                    $out .= '<tr>';
                    $out .= '<td>';
                    $out .= '<div id="companyrelationships_confirm">';
                    // it doesn't work because object is new in create mode
                    //if (!empty($object->cr_must_confirm_socid)) {
                    if (empty($originid) && intval($socid)>0) {
                        $principalCompanyList = $companyRelationships->getRelationshipsThirdparty($socid,CompanyRelationships::RELATION_TYPE_BENEFACTOR, 0, 1);
                        $principalCompanyList = is_array($principalCompanyList) ? $principalCompanyList : array();
                        if (count($principalCompanyList) > 0) {
                            $principalCompanySelectArray = array();

                            // format options in select principal company
                            foreach ($principalCompanyList as $companyId => $company) {
                                $principalCompanySelectArray[$companyId] = $company->getFullName($langs);
                            }

                            $formQuestionList = array();
                            if ($object->ei_created_out_of_quota) {
                                $ei_free = GETPOST('ei_free', "alpha");
                                $ei_reason = GETPOST('ei_reason', "alpha");
                                $formQuestionList[] = array('name' => 'ei_free', 'type' => 'hidden', 'value' => $ei_free);
                                $formQuestionList[] = array('name' => 'ei_reason', 'type' => 'hidden', 'value' => $ei_reason);
                                $formQuestionList[] = array('name' => 'ei_created_out_of_quota', 'type' => 'hidden', 'value' => ($object->ei_created_out_of_quota ? 1 : 0));
                            }
                            $formQuestionList[] = array('name' => 'companyrelationships_fk_soc_benefactor', 'type' => 'hidden', 'value' => $socid);
                            $formQuestionList[] = array('label' => $langs->trans('CompanyRelationshipsPrincipalCompany'), 'name' => 'companyrelationships_socid', 'type' => 'select', 'values' => $principalCompanySelectArray, 'default' => '');

                            // form confirm to choose the principal company
                            $out .= $formcompanyrelationships->formconfirm_socid($_SERVER['PHP_SELF'], $langs->trans('CompanyRelationshipsConfirmPrincipalCompanyTitle'), $langs->trans('CompanyRelationshipsConfirmPrincipalCompanyChoice'), 'companyrelationships_confirm_socid', $formQuestionList, '', 1, 200, 500, $formName);
                        }
                    }
                    $out .= '</div>';
                    $out .= '</td>';
                    $out .= '</tr>';
                    // it doesn't work because object is new in create mode
                    //$out .= '<input type="hidden" name="cr_confirm_socid" value="'.(!empty($object->cr_confirm_socid) ? 1 : 0).'">';
                    $out .= '<input type="hidden" name="cr_confirm_socid" value="1" />';

                    // company id already posted (an input hidden in this form)
                    if (intval($socid) > 0) {
                        $jquery_socid = $socid;

                        $out .= '<script type="text/javascript" language="javascript">';
                        $out .= 'jQuery(document).ready(function(){';

                        // benefactor
                        $out .= '   var data = {';
                        $out .= '       action: "getBenefactor",';
                        $out .= '       id: "' . $socid . '",';
                        $out .= '       htmlname: "options_companyrelationships_fk_soc_benefactor",';
                        $out .= '       relation_type: "' . CompanyRelationships::RELATION_TYPE_BENEFACTOR . '",';
                        $out .= '       relation_socid: "' . $fk_soc_benefactor . '",';
                        $out .= '       origin: "' . $origin . '",';
                        $out .= '       originid: "' . $originid . '"';
                        $out .= '   };';
                        $out .= '   jQuery.getJSON("' . dol_buildpath('/companyrelationships/ajax/benefactor.php', 1) . '", data,';
                        $out .= '       function(response) {';
                        $out .= '           jQuery("select#options_companyrelationships_fk_soc_benefactor").html(response.value);';
                        $out .= '           jQuery("select#options_companyrelationships_fk_soc_benefactor").change();';
                        $out .= '           if (response.num < 0) {';
                        $out .= '               console.error(response.error);';
                        $out .= '           }';
                        $out .= '       }';
                        $out .= '   );';

                        // watcher
                        $out .= '   var data = {';
                        $out .= '       action: "getWatcher",';
                        $out .= '       id: "' . $socid . '",';
                        $out .= '       htmlname: "options_companyrelationships_fk_soc_watcher",';
                        $out .= '       relation_type: "' . CompanyRelationships::RELATION_TYPE_WATCHER . '",';
                        $out .= '       relation_socid: "' . $fk_soc_watcher . '",';
                        $out .= '       origin: "' . $origin . '",';
                        $out .= '       originid: "' . $originid . '"';
                        $out .= '   };';
                        $out .= '   jQuery.getJSON("' . dol_buildpath('/companyrelationships/ajax/watcher.php', 1) . '", data,';
                        $out .= '       function(response) {';
                        $out .= '           jQuery("select#options_companyrelationships_fk_soc_watcher").html(response.value);';
                        $out .= '           jQuery("select#options_companyrelationships_fk_soc_watcher").change();';
                        $out .= '           if (response.num < 0) {';
                        $out .= '               console.error(response.error);';
                        $out .= '           }';
                        $out .= '       }';
                        $out .= '   );';

                        $out .= '});';
                        $out .= '</script>';
                    } // no company selected (select options in this form to choose the company)
                    else {
                        $jquery_socid = 'jQuery("#socid").val()';

                        $events = array();
                        $events[] = array('action' => 'getPrincipal', 'url' => dol_buildpath('/companyrelationships/ajax/principal.php', 1), 'htmlname' => 'companyrelationships_confirm', 'more_data' => array('form_name' => $formName, 'url_src' => $_SERVER['PHP_SELF']));
                        $events[] = array('action' => 'getBenefactor', 'url' => dol_buildpath('/companyrelationships/ajax/benefactor.php', 1), 'htmlname' => 'options_companyrelationships_fk_soc_benefactor', 'more_data' => array('relation_type' => CompanyRelationships::RELATION_TYPE_BENEFACTOR, 'relation_socid' => $fk_soc_benefactor));
                        $events[] = array('action' => 'getWatcher', 'url' => dol_buildpath('/companyrelationships/ajax/watcher.php', 1), 'htmlname' => 'options_companyrelationships_fk_soc_watcher', 'more_data' => array('relation_type' => CompanyRelationships::RELATION_TYPE_WATCHER, 'relation_socid' => $fk_soc_watcher));
                        $out .= $formcompanyrelationships->add_select_events_more_data('socid', $events);
                    }

                    // company relationships availability for this element
                    if ($user->rights->companyrelationships->update_md->element) {
                        $out .= '<script type="text/javascript" language="javascript">';
                        $out .= 'jQuery(document).ready(function(){';

                        // benefactor
                        $out .= '   jQuery("#options_companyrelationships_fk_soc_benefactor").change(function(){';
                        $out .= '       jQuery.ajax({';
                        $out .= '           data: {';
                        $out .= '           socid: ' . $jquery_socid . ',';
                        $out .= '           relation_type: ' . CompanyRelationships::RELATION_TYPE_BENEFACTOR . ',';
                        $out .= '           relation_socid: jQuery(this).val(),';
                        $out .= '           element: "' . $object->element . '"';
                        $out .= '           },';
                        $out .= '           dataType: "json",';
                        $out .= '           method: "POST",';
                        $out .= '           url: "' . dol_buildpath('/companyrelationships/ajax/publicspaceavailability.php', 1) . '",';
                        $out .= '           success: function(data){';
                        $out .= '               if (data.error > 0) {';
                        $out .= '                   console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_benefactor.change()");';
                        $out .= '               } else {';
                        $out .= '                   jQuery("input[name=options_companyrelationships_availability_principal]").prop("checked", data.principal);';
                        $out .= '                   jQuery("input[name=options_companyrelationships_availability_benefactor]").prop("checked", data.relation);';
                        $out .= '               }';
                        $out .= '           },';
                        $out .= '           error: function(){';
                        $out .= '               console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_benefactor.change()");';
                        $out .= '           }';
                        $out .= '       });';
                        $out .= '   });';

                        // watcher
                        $out .= '   jQuery("#options_companyrelationships_fk_soc_watcher").change(function(){';
                        $out .= '       jQuery.ajax({';
                        $out .= '           data: {';
                        $out .= '           socid: ' . $jquery_socid . ',';
                        $out .= '           relation_type: ' . CompanyRelationships::RELATION_TYPE_WATCHER . ',';
                        $out .= '           relation_socid: jQuery(this).val(),';
                        $out .= '           element: "' . $object->element . '"';
                        $out .= '           },';
                        $out .= '           dataType: "json",';
                        $out .= '           method: "POST",';
                        $out .= '           url: "' . dol_buildpath('/companyrelationships/ajax/publicspaceavailability.php', 1) . '",';
                        $out .= '           success: function(data){';
                        $out .= '               if (data.error > 0) {';
                        $out .= '                   console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_watcher.change()");';
                        $out .= '               } else {';
                        $out .= '                   jQuery("input[name=options_companyrelationships_availability_watcher]").prop("checked", data.relation);';
                        $out .= '               }';
                        $out .= '           },';
                        $out .= '           error: function(){';
                        $out .= '               console.error("Error : ", "' . dol_buildpath('/companyrelationships/class/actions_companyrelationships.class.php', 1) . '", "in formObjectOptions() on #options_companyrelationships_fk_soc_watcher.change()");';
                        $out .= '           }';
                        $out .= '       });';
                        $out .= '   });';

                        $out .= '});';

                        $out .= '</script>';
                    }

                    // ajax search
                    if ($conf->use_javascript_ajax) {
                        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';

                        // benefactor
                        $comboenhancement = ajax_combobox('options_companyrelationships_fk_soc_benefactor', array(), $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
                        $out.= $comboenhancement;

                        // watcher
                        $comboenhancement = ajax_combobox('options_companyrelationships_fk_soc_watcher', array(), $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
                        $out.= $comboenhancement;
                    }

                    print $out;
                }
                // edit extrafields
                else if ($action=='edit_extras' && $userRightsElementCreer) {

                    $attribute = GETPOST('attribute', 'alpha');

                    // benefactor
                    if ($attribute=='companyrelationships_fk_soc_benefactor') {
                        $langs->load('companyrelationships@companyrelationships');

                        $out = '';

                        $relation_type = CompanyRelationships::RELATION_TYPE_BENEFACTOR;
                        $socid = $object->socid;
                        $relation_socid = $object->array_options['options_companyrelationships_fk_soc_benefactor'];

                        // company id already posted (an input hidden in this form)
                        if (intval($socid)>0) {
                            if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
                                $hidelabel = 1;
                                // No immediate load of all database
                                $placeholder='';
                                if ($relation_socid && empty($selected_input_value)) {
                                    $companyrelationships = new CompanyRelationships($this->db);
                                    $relation_ids = $companyrelationships->getRelationshipsThirdparty($socid, $relation_type,1);
                                    $relation_ids = is_array($relation_ids) ? $relation_ids : array();

                                    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                                    $societetmp = new Societe($this->db);
                                    $societetmp->fetch($relation_socid);
                                    $selected_input_value = $societetmp->name;
                                    if (in_array($relation_socid, $relation_ids)) {
                                        $selected_input_value .= ' *';
                                    }

                                    unset($societetmp);
                                }
                                // mode 1
                                $urloption='htmlname=options_companyrelationships_fk_soc_benefactor&outjson=1&socid='.$socid.'&relation_type='.$relation_type;
                                $out .= ajax_autocompleter($relation_socid, 'options_companyrelationships_fk_soc_benefactor', dol_buildpath('/companyrelationships/ajax/benefactor2.php', 1), $urloption, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
                                $out .= '<style type="text/css">
					            .ui-autocomplete {
						            z-index: 250;
					            }
				                </style>';
                                if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
                                else if ($hidelabel > 1) {
                                    if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
                                    else $placeholder=' title="'.$langs->trans("RefOrLabel").'"';
                                    if ($hidelabel == 2) {
                                        $out .= img_picto($langs->trans("Search"), 'search');
                                    }
                                }
                                $out.=  '<input type="text" name="search_options_companyrelationships_fk_soc_benefactor" id="search_options_companyrelationships_fk_soc_benefactor" value="'.$selected_input_value.'"'.$placeholder.' '.(!empty($conf->global->THIRDPARTY_SEARCH_AUTOFOCUS) ? 'autofocus' : '').' />';
                                if ($hidelabel == 3) {
                                    $out.= img_picto($langs->trans("Search"), 'search');
                                }
                                $out .= '<script type="text/javascript" language="javascript">';
                                $out .= 'jQuery(document).ready(function(){';
                                $out .= '   var cr_input = $("input#options_companyrelationships_fk_soc_benefactor");';
                                $out .= '   var cr_input_search = $("input#search_options_companyrelationships_fk_soc_benefactor");';
                                $out .= '   var cr_select = $("select#options_companyrelationships_fk_soc_benefactor");';
                                $out .= '   var cr_select_form = cr_select.closest("form");';
                                $out .= '   cr_input.detach().prependTo(cr_select_form);';
                                $out .= '   cr_input_search.detach().prependTo(cr_select_form);';
                                $out .= '   cr_select.remove();';
                                $out .= '});';
                                $out .= '</script>';
                            } else {
                                $out .= '<script type="text/javascript" language="javascript">';
                                $out .= 'jQuery(document).ready(function(){';
                                $out .= '   var data = {';
                                $out .= '       action: "getBenefactor",';
                                $out .= '       id: "' . $socid . '",';
                                $out .= '       htmlname: "options_companyrelationships_fk_soc_benefactor",';
                                $out .= '       relation_type: "' . $relation_type . '",';
                                $out .= '       relation_socid: "' . $relation_socid . '"';
                                $out .= '   };';
                                $out .= '   jQuery.getJSON("' . dol_buildpath('/companyrelationships/ajax/benefactor.php', 1) . '", data,';
                                $out .= '       function(response) {';
                                $out .= '           jQuery("select#options_companyrelationships_fk_soc_benefactor").html(response.value);';
                                $out .= '           jQuery("select#options_companyrelationships_fk_soc_benefactor").change();';
                                $out .= '           if (response.num < 0) {';
                                $out .= '               console.error(response.error);';
                                $out .= '           }';
                                $out .= '       }';
                                $out .= '   );';
                                $out .= '});';
                                $out .= '</script>';

                                if ($conf->use_javascript_ajax) {
                                    include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                                    $comboenhancement = ajax_combobox('options_companyrelationships_fk_soc_benefactor', array(), $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
                                    $out.= $comboenhancement;
                                }
                            }
                        }

                        print $out;
                    }
                    // watcher
                    else if ($attribute=='companyrelationships_fk_soc_watcher') {
                        $langs->load('companyrelationships@companyrelationships');

                        $out = '';

                        $relation_type = CompanyRelationships::RELATION_TYPE_WATCHER;
                        $socid = $object->socid;
                        $relation_socid = $object->array_options['options_companyrelationships_fk_soc_watcher'];

                        // company id already posted (an input hidden in this form)
                        if (intval($socid)>0) {
                            if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
                                $hidelabel = 1;
                                // No immediate load of all database
                                $placeholder='';
                                if ($relation_socid && empty($selected_input_value)) {
                                    $companyrelationships = new CompanyRelationships($this->db);
                                    $relation_ids = $companyrelationships->getRelationshipsThirdparty($socid, $relation_type,1);
                                    $relation_ids = is_array($relation_ids) ? $relation_ids : array();

                                    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                                    $societetmp = new Societe($this->db);
                                    $societetmp->fetch($relation_socid);
                                    $selected_input_value = $societetmp->name;
                                    if (in_array($relation_socid, $relation_ids)) {
                                        $selected_input_value .= ' *';
                                    }

                                    unset($societetmp);
                                }
                                // mode 1
                                $urloption='htmlname=options_companyrelationships_fk_soc_watcher&outjson=1&socid='.$socid.'&relation_type='.$relation_type;
                                $out .= ajax_autocompleter($relation_socid, 'options_companyrelationships_fk_soc_watcher', dol_buildpath('/companyrelationships/ajax/watcher2.php', 1), $urloption, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
                                $out .= '<style type="text/css">
					            .ui-autocomplete {
						            z-index: 250;
					            }
				                </style>';
                                if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
                                else if ($hidelabel > 1) {
                                    if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
                                    else $placeholder=' title="'.$langs->trans("RefOrLabel").'"';
                                    if ($hidelabel == 2) {
                                        $out .= img_picto($langs->trans("Search"), 'search');
                                    }
                                }
                                $out.=  '<input type="text" name="search_options_companyrelationships_fk_soc_watcher" id="search_options_companyrelationships_fk_soc_watcher" value="'.$selected_input_value.'"'.$placeholder.' '.(!empty($conf->global->THIRDPARTY_SEARCH_AUTOFOCUS) ? 'autofocus' : '').' />';
                                if ($hidelabel == 3) {
                                    $out.= img_picto($langs->trans("Search"), 'search');
                                }
                                $out .= '<script type="text/javascript" language="javascript">';
                                $out .= 'jQuery(document).ready(function(){';
                                $out .= '   var cr_input = $("input#options_companyrelationships_fk_soc_watcher");';
                                $out .= '   var cr_input_search = $("input#search_options_companyrelationships_fk_soc_watcher");';
                                $out .= '   var cr_select = $("select#options_companyrelationships_fk_soc_watcher");';
                                $out .= '   var cr_select_form = cr_select.closest("form");';
                                $out .= '   cr_input.detach().prependTo(cr_select_form);';
                                $out .= '   cr_input_search.detach().prependTo(cr_select_form);';
                                $out .= '   cr_select.remove();';
                                $out .= '});';
                                $out .= '</script>';
                            } else {
                                $out .= '<script type="text/javascript" language="javascript">';
                                $out .= 'jQuery(document).ready(function(){';
                                $out .= '   var data = {';
                                $out .= '       action: "getWatcher",';
                                $out .= '       id: "' . $socid . '",';
                                $out .= '       htmlname: "options_companyrelationships_fk_soc_watcher",';
                                $out .= '       relation_type: "' . $relation_type . '",';
                                $out .= '       relation_socid: "' . $relation_socid . '"';
                                $out .= '   };';
                                $out .= '   jQuery.getJSON("' . dol_buildpath('/companyrelationships/ajax/watcher.php', 1) . '", data,';
                                $out .= '       function(response) {';
                                $out .= '           jQuery("select#options_companyrelationships_fk_soc_watcher").html(response.value);';
                                $out .= '           jQuery("select#options_companyrelationships_fk_soc_watcher").change();';
                                $out .= '           if (response.num < 0) {';
                                $out .= '               console.error(response.error);';
                                $out .= '           }';
                                $out .= '       }';
                                $out .= '   );';
                                $out .= '});';
                                $out .= '</script>';

                                if ($conf->use_javascript_ajax) {
                                    include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                                    $comboenhancement = ajax_combobox('options_companyrelationships_fk_soc_watcher', array(), $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
                                    $out.= $comboenhancement;
                                }
                            }
                        }

                        print $out;
                    }
                }
            }
        }

        return 0;
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
    function printFieldListOption($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $form;
        $contexts = explode(':', $parameters['context']);

        if (count(array_diff(array('propallist', 'orderlist', 'invoicelist', 'shipmentlist', 'interventionlist', 'contractlist'), $contexts)) != 6) {
            if (!is_object($form)) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
                $form = new Form($this->db);
            }

            $selected = GETPOST('search_options_companyrelationships_fk_soc_benefactor', 'int');
            $out = $form->select_company($selected, 'new_search_options_companyrelationships_fk_soc_benefactor', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'maxwidth300');

            $out .= '<script type="text/javascript" language="javascript">';
            $out .= '  $(document).ready(function(){';
            $out .= '    var cr_old_select = $("select#options_companyrelationships_fk_soc_benefactor");';
            $out .= '    var cr_new_select = $("select#new_search_options_companyrelationships_fk_soc_benefactor");';
            $out .= '    if (cr_new_select.length == 0) cr_new_select = $("input#new_search_options_companyrelationships_fk_soc_benefactor");';
            $out .= '    var cr_new_select_div = $("div#s2id_new_search_options_companyrelationships_fk_soc_benefactor");';
            $out .= '    cr_new_select_div.detach().prependTo(cr_old_select.parent());';
            $out .= '    cr_new_select.detach().prependTo(cr_old_select.parent());';
            $out .= '    cr_old_select.remove();';
            $out .= '    cr_new_select.attr("name", "search_options_companyrelationships_fk_soc_benefactor");';
            $out .= '    cr_new_select.find(\'option[value="-1"]\').attr("value", "0");';
            $out .= '  });';
            $out .= '</script>';

            if (!empty($conf->use_javascript_ajax) && !empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
                $urloption = 'htmlname=search_options_companyrelationships_fk_soc_benefactor&outjson=1&filter=' . urlencode('(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1') . '&showtype=SelectThirdParty';
                $out .= ajax_autocompleter($selected, 'search_options_companyrelationships_fk_soc_benefactor', DOL_URL_ROOT . '/societe/ajax/company.php', $urloption, $conf->global->COMPANY_USE_SEARCH_TO_SELECT, 0);
            } elseif ($conf->use_javascript_ajax) {
                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                $out .= ajax_combobox('search_options_companyrelationships_fk_soc_benefactor', null, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
            }

            $this->resprints = $out;
        }

        return 0;
    }
}
