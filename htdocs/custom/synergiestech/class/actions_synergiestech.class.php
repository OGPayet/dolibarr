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
 * \file    htdocs/synergiestech/class/actions_synergiestech.class.php
 * \ingroup synergiestech
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsSynergiesTech
 */
class ActionsSynergiesTech
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
     * Overloading the updateSession function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function updateSession($parameters, &$object, &$action, $hookmanager)
    {
        return $this->_redirection($parameters, $object, $action, $hookmanager);
    }

    /**
     * Overloading the afterLogin function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function afterLogin($parameters, &$object, &$action, $hookmanager)
    {
        return $this->_redirection($parameters, $object, $action, $hookmanager);
    }

    /**
     * Overloading the printTopRightMenu function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    /*function printTopRightMenu($parameters, &$object, &$action, $hookmanager) {
        $this->block_page_set = false;
        return $this->_block_page($parameters, $object, $action, $hookmanager);
    }*/

    /**
     * Overloading the printLeftBlock function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    /*function printLeftBlock($parameters, &$object, &$action, $hookmanager) {
        return $this->_block_page($parameters, $object, $action, $hookmanager);
    }*/

    /**
	 * Overloading the sqlLinesToSerialize function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function sqlLinesToSerialize($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':',$parameters['context']);

        if (in_array('tab_supplier_order', $contexts)) {
            // List of lines to serialize
            $dispatched_sql = "SELECT p.ref, p.label, p.description, p.fk_product_type, SUM(IFNULL(eq.quantity, 0)) as nb_serialized,";
            $dispatched_sql .= " e.rowid as warehouse_id, e.label as entrepot,";
            $dispatched_sql .= " cfd.rowid as dispatchlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status";
            $dispatched_sql .= " FROM " . MAIN_DB_PREFIX . "product as p,";
            $dispatched_sql .= " " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
            $dispatched_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields AS pe ON pe.fk_object = cfd.fk_product";
            $dispatched_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS eq ON eq.fk_commande_fournisseur_dispatch = cfd.rowid";
            $dispatched_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e ON cfd.fk_entrepot = e.rowid";
            $dispatched_sql .= " WHERE cfd.fk_commande = " . $object->id;
            $dispatched_sql .= " AND cfd.fk_product = p.rowid";
            $dispatched_sql .= " AND pe.synergiestech_to_serialize = 1";
            $dispatched_sql .= " GROUP BY cfd.rowid";
            $dispatched_sql .= " ORDER BY cfd.rowid ASC";

            $this->resprints = $dispatched_sql;
        }

        return 0;
    }

    /**
	 * Overloading the sqlLinesToAttach function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function sqlLinesToAttach($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':',$parameters['context']);

        if (in_array('tab_expedition_add', $contexts)) {
            // List of lines to attach
            $attached_sql = "SELECT p.label as product_label, SUM(IFNULL(eq.quantity, 0)) as nb_attached,";
            $attached_sql .= " e.rowid as entrepot_id, ed.rowid as lineid, cd.fk_product, ed.qty as qty_shipped";
            $attached_sql .= " FROM " . MAIN_DB_PREFIX . "product as p,";
            $attached_sql .= " " . MAIN_DB_PREFIX . "expeditiondet as ed";
            $attached_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet as cd ON ed.fk_origin_line = cd.rowid";
            $attached_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields AS pe ON pe.fk_object = cd.fk_product";
            $attached_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipementevt AS ee ON ee.fk_expeditiondet = ed.rowid";
            $attached_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS eq ON eq.rowid = ee.fk_equipement";
            $attached_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e ON ed.fk_entrepot = e.rowid";
            $attached_sql .= " WHERE ed.fk_expedition = " . $object->id;
            $attached_sql .= " AND cd.fk_product = p.rowid";
            $attached_sql .= " AND pe.synergiestech_to_serialize = 1";
            $attached_sql .= " GROUP BY ed.rowid";
            $attached_sql .= " ORDER BY ed.rowid ASC";

            $this->resprints = $attached_sql;
        }

        return 0;
    }

    /**
	 * Overloading the addRequestManagerAuthorizedButton function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addRequestManagerAuthorizedButton($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':',$parameters['context']);

        if (in_array('requestmanagerdao', $contexts)) {
            $langs->load("retourproduits@retourproduits");

            $authorized_buttons_list = array(
                'retourproduits' => $langs->trans('returnProducts'),
            );

            $this->results = $authorized_buttons_list;

            return 1;
        }

        return 0;
    }

    /**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':',$parameters['context']);

        if (in_array('requestmanagercard', $contexts)) {
            $langs->load('synergiestech@synergiestech');

            $requestManagerStatusDictionaryLine = $parameters['status_infos'];
            $authorizedButtons = !empty($requestManagerStatusDictionaryLine->fields['authorized_buttons']) ? explode(',', $requestManagerStatusDictionaryLine->fields['authorized_buttons']) : array();

            dol_include_once('/requestmanager/class/requestmanager.class.php');
            if ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS && !empty($conf->retourproduits->enabled) && (count($authorizedButtons) == 0 || in_array('retourproduits', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                $langs->load("retourproduits@retourproduits");
                if ($object->socid > 0) {
                    dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                    $return_products_list = synergiestech_get_return_products_list($this->db, $object->socid);
                    if (!empty($return_products_list)) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=synergiestech_returnproducts">' . $langs->trans('returnProducts') . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("RetourProduitsErrorNoProductSent")) . '" href="#">' . $langs->trans("returnProducts") . '</a></div>';
                    }
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("SynergiesTechThridPartyNotDefined")) . '" href="#">' . $langs->trans("returnProducts") . '</a></div>';
                }
            }

            if ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS) {
                // Add child request
                if ($user->rights->requestmanager->creer) {
                    dol_include_once('/advancedictionaries/class/dictionary.class.php');
                    $requestManagerRequestTypeDictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerrequesttype');
                    $requestManagerRequestTypeDictionary->fetch_lines(1);

                    if (!empty($requestManagerStatusDictionaryLine->fields['new_request_type'])) {
                        foreach (explode(',', $requestManagerStatusDictionaryLine->fields['new_request_type']) as $request_type_id) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=create_request_child&new_request_type=' . $request_type_id . '">'
                                . $langs->trans('RequestManagerCreateRequestChild', $requestManagerRequestTypeDictionary->lines[$request_type_id]->fields['label']) . '</a></div>';
                        }
                    }
                }

                $backtopage = dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $object->id;
                $commun_params = '&originid=' . $object->id . '&origin=' . $object->element . '&socid=' . $object->socid . '&backtopage=' . urlencode($backtopage);
                $benefactor_params = !empty($conf->companyrelationships->enabled) ? '&companyrelationships_fk_soc_benefactor=' . $object->socid_benefactor : '';
                $watcher_params = !empty($conf->companyrelationships->enabled) ? '&companyrelationships_fk_soc_watcher=' . $object->socid_watcher : '';
                if (!empty($conf->global->REQUESTMANAGER_TITLE_TO_REF_CUSTOMER_WHEN_CREATE_OTHER_ELEMENT)) {
                    $ref_client = '&ref_client=' . urlencode($object->label);
                } else {
                    $ref_client = '';
                }

                // Add proposal
                if (!empty($conf->propal->enabled) && (count($authorizedButtons) == 0 || in_array('create_propal', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("propal");
                    if ($user->rights->propal->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/propal/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . $ref_client . '">' . $langs->trans("AddProp") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddProp") . '</a></div>';
                    }
                }

                // Add order
                if (!empty($conf->commande->enabled) && (count($authorizedButtons) == 0 || in_array('create_order', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("orders");
                    if ($user->rights->commande->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/commande/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . $ref_client . '">' . $langs->trans("AddOrder") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddOrder") . '</a></div>';
                    }
                }

                // Add invoice
                if ($user->socid == 0 && !empty($conf->facture->enabled) && (count($authorizedButtons) == 0 || in_array('create_invoice', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("bills");
                    $langs->load("compta");
                    if ($user->rights->facture->creer) {
                        $object->fetch_thirdparty();
                        if ($object->thirdparty->client != 0 && $object->thirdparty->client != 2) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/facture/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . '">' . $langs->trans("AddBill") . '</a></div>';
                        } else {
                            print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")) . '" href="#">' . $langs->trans("AddBill") . '</a></div>';
                        }
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddBill") . '</a></div>';
                    }
                }

                // Add supplier proposal
                if (!empty($conf->supplier_proposal->enabled) && (count($authorizedButtons) == 0 || in_array('create_supplier_proposal', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("supplier_proposal");
                    if ($user->rights->supplier_proposal->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/supplier_proposal/card.php?action=create' . $commun_params . '">' . $langs->trans("AddSupplierProposal") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddSupplierProposal") . '</a></div>';
                    }
                }

                // Add supplier order
                if (!empty($conf->fournisseur->enabled) && (count($authorizedButtons) == 0 || in_array('create_supplier_order', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("suppliers");
                    if ($user->rights->fournisseur->commande->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fourn/commande/card.php?action=create' . $commun_params . '">' . $langs->trans("AddSupplierOrder") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddSupplierOrder") . '</a></div>';
                    }
                }

                // Add supplier invoice
                if (!empty($conf->fournisseur->enabled) && (count($authorizedButtons) == 0 || in_array('create_supplier_invoice', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("suppliers");
                    if ($user->rights->fournisseur->facture->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fourn/facture/card.php?action=create' . $commun_params . '">' . $langs->trans("AddSupplierInvoice") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddSupplierInvoice") . '</a></div>';
                    }
                }

                // Add contract
                if (!empty($conf->contrat->enabled) && (count($authorizedButtons) == 0 || in_array('create_contract', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("contracts");
                    if ($user->rights->contrat->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . $ref_client . '">' . $langs->trans("AddContract") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddContract") . '</a></div>';
                    }
                }

                // Add intervention
                if (!empty($conf->ficheinter->enabled) && (count($authorizedButtons) == 0 || in_array('create_inter', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("interventions");
                    if ($user->rights->ficheinter->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fichinter/card.php?action=create' . $commun_params . $benefactor_params . $watcher_params . '">' . $langs->trans("AddIntervention") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddIntervention") . '</a></div>';
                    }
                }

                // Add project
                if (!empty($conf->projet->enabled) && (count($authorizedButtons) == 0 || in_array('create_project', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("projects");
                    if ($user->rights->projet->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/projet/card.php?action=create' . $commun_params . '">' . $langs->trans("AddProject") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddProject") . '</a></div>';
                    }
                }

                // Add trip
                if (!empty($conf->deplacement->enabled) && (count($authorizedButtons) == 0 || in_array('create_trip', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("trips");
                    if ($user->rights->deplacement->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/compta/deplacement/card.php?action=create' . $commun_params . '">' . $langs->trans("AddTrip") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddTrip") . '</a></div>';
                    }
                }

                // Add request
                if ((count($authorizedButtons) == 0 || in_array('create_request_manager', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    if ($user->rights->requestmanager->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/requestmanager/createfast.php', 2) . '?action=createfast&socid_origin=' . $object->socid . $commun_params . '">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
                    }
                }

                // Add event
                if (!empty($conf->agenda->enabled) && (count($authorizedButtons) == 0 || in_array('create_event', $authorizedButtons)) && !in_array('no_buttons', $authorizedButtons)) {
                    $langs->load("commercial");
                    if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create' . $commun_params . '">' . $langs->trans("AddAction") . '</a></div>';
                    } else {
                        print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">' . $langs->trans("AddAction") . '</a></div>';
                    }
                }
            }

            // Not Resolved
            if ($object->statut_type == RequestManager::STATUS_TYPE_RESOLVED) {
                if ($user->rights->requestmanager->creer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=notresolved">'
                        . $langs->trans('ReOpen') . '</a></div>';
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">'
                        . $langs->trans("ReOpen") . '</a></div>';
                }
            }

            // ReOpen
            if ($object->statut_type == RequestManager::STATUS_TYPE_CLOSED) {
                if ($user->rights->requestmanager->cloturer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=reopen">'
                        . $langs->trans('ReOpen') . '</a></div>';
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">'
                        . $langs->trans("ReOpen") . '</a></div>';
                }
            }

            // Delete
            if ($object->statut_type == RequestManager::STATUS_TYPE_INITIAL) {
                if ($user->rights->requestmanager->supprimer) {
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete">'
                        . $langs->trans('Delete') . '</a></div>';
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" title="' . dol_escape_js($langs->trans("NotAllowed")) . '" href="#">'
                        . $langs->trans("Delete") . '</a></div>';
                }
            }

            return 1;
        } elseif (in_array('contractcard', $contexts)) {
            $langs->load('synergiestech@synergiestech');
            if ($action == 'synergiestech_generate_ticket_report') {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
                $now = dol_getdate(dol_now(), true);
                $prevmonth = dol_get_prev_month($now['mon'], $now['year']);
                $date_start = dol_get_first_day($prevmonth['year'], $prevmonth['month']);
                $date_end = dol_get_last_day($prevmonth['year'], $prevmonth['month']);

                $form = new Form($this->db);
                $formquestion = array(
                    array(
                        'name' => 'synergiestech_generate_ticket_report_date_start',
                        'label' => $langs->trans('DateStart'),
                        'type' => 'date',
                        'value' => $date_start
                    ),
                    array(
                        'name' => 'synergiestech_generate_ticket_report_date_end',
                        'label' => $langs->trans('DateEnd'),
                        'type' => 'date',
                        'value' => $date_end
                    ),
                );

                print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $object->id, $langs->trans("SynergiesTechTicketGenerateReportConfirm"), '', 'synergiestech_generate_ticket_report_confirm', $formquestion, 'yes', 1, 200);
            }

            if ($user->rights->synergiestech->generate->ticket_report)
                print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=synergiestech_generate_ticket_report">' . $langs->trans("SynergiesTechTicketGenerateReport") . '</a></div>';
            else
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans("SynergiesTechTicketGenerateReport") . '</a></div>';
        }

        return 0;
    }

    /**
     * Overloading the formConfirm function : replacing the parent's function with the one below
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
        global $form;

        $contexts = explode(':', $parameters['context']);

        if (in_array('requestmanagercard', $contexts)) {
            if ($action == 'synergiestech_returnproducts') {
                $langs->load("orders");
                $langs->load("retourproduits@retourproduits");
                $langs->load("equipement@equipement");

                dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                $lines = synergiestech_get_return_products_list($this->db, $object->socid);

                if (empty($lines)) {
                    setEventMessage($langs->trans('RetourProduitsErrorNoProductSent'), 'errors');
                    return 0;
                }

                require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
                $formproduct = new FormProduct($this->db);

                $products_list = array();
                $orders_list = array();
                $equipments_list = array();
                foreach ($lines as $product_id => $line) {
                    $products_list[$product_id] = $line['product'];
                    foreach ($line['orders'] as $order_line_id => $order_line) {
                        $orders_list[$order_line_id] = $order_line['label'];
                        foreach ($order_line['equipments'] as $equipment_id => $equipment) {
                            $equipments_list[$equipment_id] = $equipment;
                        }
                    }
                }

                $formquestion = array(array(
                    'type' => 'other',
                    'label' => $form->selectarray('synergiestech_product', $products_list,'', 1, 0, 0, '', 0, 0, 0, '', ' minwidth200', 1),
                    'value' =>
                        '<table width="100%">'.
                        '<tr>'.
                        '<td>'.
                        $langs->trans('Order') . ' * : '.$form->selectarray('synergiestech_order', array(),'', 1, 0, 0, '', 0, 0, 0, '', ' minwidth200', 1).'<br>' .
                        $langs->trans('Qty') . ' * : <input type="number" id="synergiestech_qty" min="0" max="0"> ' .
                        ' ' . $langs->trans('Warehouse') . ' * : ' . $formproduct->selectWarehouses('', 'synergiestech_warehouse', 'warehouseopen,warehouseinternal', 1) .
                        '<br>' . $langs->trans('Equipements') . ' : ' . $form->multiselectarray('synergiestech_equipments', array(), array(), 0, 0, '', 0, 0, 'style="min-width:300px"') .
                        '</td>'.
                        '<td style="vertical-align: middle;" align="center" width="24px"><a href="#" id="synergiestech_add">'.img_edit_add($langs->trans('Add')).'</a></td>'.
                        '</tr>'.
                        '<table>'
                ));
                $selected_lines = array();
                foreach ($lines as $product_id => $line) {
                    foreach ($line['orders'] as $line_id => $order_line) {
                        if( GETPOST('s-' . $line_id, 'int') == $line_id) {
                            $selected_lines[$line_id] = array(
                                'p' => GETPOST('p-' . $line_id, 'int'),
                                'q' => GETPOST('q-' . $line_id, 'int'),
                                'w' => GETPOST('w-' . $line_id, 'int'),
                                'e' => GETPOST('e-' . $line_id, 'alpha'), // Todo check si le multi-select renvoie une liste d'ids séparer par des virgules ou un array d'ids
                            );
                        }

                        $formquestion[] = array(
                            'name' => array('s-' . $line_id, 'p-' . $line_id, 'q-' . $line_id, 'w-' . $line_id, 'e-' . $line_id),
                        );
                    }
                }

                $useAjax = $conf->use_javascript_ajax ? 'true' : 'false';
                $orderText = str_replace("'", "\\'",$langs->trans('Order') . ': ');
                $qtyText = str_replace("'", "\\'",$langs->trans('Qty') . ': ');
                $yesText = str_replace("'", "\\'",$langs->transnoentities("Yes"));
                $warehouseText = str_replace("'", "\\'",$langs->trans('Warehouse') . ': ');
                $equipmentsText = str_replace("'", "\\'",$langs->trans('Equipements') . ': ');
                $del_img = str_replace("'", "\\'", img_edit_remove($langs->trans('Delete')));
                $lines = json_encode(empty($lines) ? new stdClass() : $lines);
                $selected_lines = json_encode(empty($selected_lines) ? new stdClass() : $selected_lines);
                $products_list = json_encode(empty($products_list) ? new stdClass() : $products_list);
                $orders_list = json_encode(empty($orders_list) ? new stdClass() : $orders_list);
                $equipments_list = json_encode(empty($equipments_list) ? new stdClass() : $equipments_list);
                $out = <<<SCRIPT
    <script>
        $(document).ready(function() {
            var synergiestech_products_list = $products_list;
            var synergiestech_orders_list = $orders_list;
            var synergiestech_equipments_list = $equipments_list;
            var synergiestech_returnproducts_list = $lines;
            var synergiestech_returnproducts_selected_list = $selected_lines;
            var synergiestech_table = $('#synergiestech_product').closest('tbody');
            if (synergiestech_table.length == 0) synergiestech_table = $('#synergiestech_product').closest('table');

            // Define function if not exist
            if(!Object.keys) {
              Object.keys = function(obj) {
                return $.map(obj, function(v, k) { return k; });
              };
            }

            // Initialization
            $.map(synergiestech_returnproducts_selected_list, function(line, order_line_id) {
              synergiestech_add_line(order_line_id, line.p, line.q, line.w, line.e);
            });
            synergiestech_update_select_order();
            synergiestech_update_select_infos();

            // Events
            $('#synergiestech_product').on('change', function() {
              synergiestech_update_select_order();
              synergiestech_update_select_infos();
            });
            $('#synergiestech_order').on('change', function() {
              synergiestech_update_select_infos();
            });
            $('#synergiestech_add').on('click', function() {
              var order_line_id = $('#synergiestech_order').val();
              var product_id = $('#synergiestech_product').val();
              var qty = $('#synergiestech_qty').val();
              var warehouse_id = $('#synergiestech_warehouse').val();
              var equipments_ids = $('#synergiestech_equipments').val();

              synergiestech_add_line(order_line_id, product_id, qty, warehouse_id, equipments_ids);
            });
            $("#dialog-confirm").on("dialogcreate", function() {
              synergiestech_update_confirm_button();
            });

            // Functions
            function synergiestech_update_confirm_button() {
              if (Object.keys(synergiestech_returnproducts_selected_list).length == 0) {
                $('div.ui-dialog-buttonset button:contains(\'$yesText\')').addClass('ui-state-disabled');
              } else {
                $('div.ui-dialog-buttonset button:contains(\'$yesText\')').removeClass('ui-state-disabled');
              }
            }
            function synergiestech_update_select_product() {
              $('#synergiestech_product').empty();
              $('#synergiestech_product').append($('<option>', {
                value: -1,
                text: ''
              }));
              $('#synergiestech_product').val(-1);
              if($useAjax) {
                $('#synergiestech_product').select2().val(-1);
              }

              $.map(synergiestech_returnproducts_list, function(product, product_id) {
                var has_order = false;

                if (product_id in synergiestech_returnproducts_list) {
                  $.map(synergiestech_returnproducts_list[product_id].orders, function(order_line, order_line_id) {
                    if (!(order_line_id in synergiestech_returnproducts_selected_list)) {
                      has_order = true;
                      return false;
                    }
                  });
                }

                if (has_order) {
                  $('#synergiestech_product').append($('<option>', {
                    value: product_id,
                    text: product.product
                  }));
                }
              });

              synergiestech_update_select_order();
              synergiestech_update_select_infos();
            }
            function synergiestech_update_select_order() {
              var product_id = $('#synergiestech_product').val();

              $('#synergiestech_order').empty();
              $('#synergiestech_order').append($('<option>', {
                value: -1,
                text: ''
              }));
              $('#synergiestech_order').val(-1);
              if($useAjax) {
                $('#synergiestech_order').select2().val(-1);
              }

              if (product_id in synergiestech_returnproducts_list) {
                $.map(synergiestech_returnproducts_list[product_id].orders, function(order_line, order_line_id) {
                  if (!(order_line_id in synergiestech_returnproducts_selected_list)) {
                    $('#synergiestech_order').append($('<option>', {
                      value: order_line_id,
                      text: order_line.label
                    }));
                  }
                });
              }
            }
            function synergiestech_update_select_infos() {
              var product_id = $('#synergiestech_product').val();
              var order_line_id = $('#synergiestech_order').val();

              $('#synergiestech_qty').attr('min', 0).attr('max', 0).val(0);
              $('#synergiestech_warehouse').val(-1);
              if($useAjax) {
                $('#synergiestech_warehouse').select2().val(-1);
              }
              $('#synergiestech_equipments').empty();

              if (product_id in synergiestech_returnproducts_list && order_line_id in synergiestech_returnproducts_list[product_id].orders) {
                  var order_line = synergiestech_returnproducts_list[product_id].orders[order_line_id];

                  $('#synergiestech_qty').attr('min', '1').attr('max', order_line.qty_sent).val(order_line.qty_sent);
                  $.map(order_line.equipments, function(elem, index) {
                    $('#synergiestech_order').append($('<option>', {
                        value: index,
                        text: elem
                    }));
                  });

              }
            }
            function synergiestech_add_line(order_line_id, product_id, qty, warehouse_id, equipments_ids) {
              if (product_id in synergiestech_returnproducts_list && order_line_id in synergiestech_returnproducts_list[product_id].orders && parseFloat(qty) > 0 && parseInt(warehouse_id) > 0) {
                var order_id = synergiestech_returnproducts_list[product_id].orders[order_line_id].order_id;
                var equipements_selected = [];
                $.each(equipments_ids, function(elem) {
                  equipements_selected.push(synergiestech_equipments_list[parseInt(elem.trim())]);
                });
                synergiestech_table.append(
                  '<tr><td style="vertical-align: middle;">'+
                  '<input type="hidden" id="s-'+order_line_id+'" name="s-'+order_line_id+'" value="'+order_line_id+'">'+
                  '<input type="hidden" id="p-'+order_line_id+'" name="p-'+order_line_id+'" value="'+product_id+'">'+
                  '<input type="hidden" id="q-'+order_line_id+'" name="q-'+order_line_id+'" value="'+qty+'">'+
                  '<input type="hidden" id="w-'+order_line_id+'" name="w-'+order_line_id+'" value="'+warehouse_id+'">'+
                  '<input type="hidden" id="e-'+order_line_id+'" name="e-'+order_line_id+'" value="'+equipments_ids+'">'+
                  synergiestech_products_list[product_id]+
                  '</td><td colspan="2">'+
                  '<table width="100%"><tr><td>'+
                  '$orderText'+synergiestech_orders_list[order_line_id]+'<br>'+
                  '$qtyText'+qty+'<br>'+
                  '$warehouseText'+$('#synergiestech_warehouse option[value="'+warehouse_id+'"]').text()+'<br>'+
                  '$equipmentsText'+equipements_selected.join(", ")+'<br>'+
                  '</td><td style="vertical-align: middle;" align="center" width="24px"><a href="#" id="synergiestech_del">$del_img</a></td>'+
                  '</tr><table>'+
                  '</td></tr>'
                );

                $('#synergiestech_del').on('click', function() {
                  $(this).closest('table').closest('tr').remove();
                  if (order_line_id in synergiestech_returnproducts_selected_list) {
                    delete synergiestech_returnproducts_selected_list[order_line_id];
                    synergiestech_update_select_product();
                    synergiestech_update_confirm_button();
                  }
                });

                synergiestech_returnproducts_selected_list[order_line_id] = { p: product_id, q: qty, w: warehouse_id, e: equipments_ids };
                synergiestech_update_select_product();
                synergiestech_update_confirm_button();
              }
            }
        });
    </script>
SCRIPT;
                // Create the confirm form
                $out.= $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CreateReturnProducts'), $langs->trans('SelectProductsToReturn'), 'synergiestech_create_returnproducts', $formquestion, 'yes', 1, 400, 700);

                $this->resprints = $out;

                return 1;
            }
        } elseif (in_array('ordercard', $contexts)) {
            if ($action == 'synergiestech_addline' && $user->rights->commande->creer) {
                $langs->load('synergiestech@synergiestech');

                // Create the confirm form
                $this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('SynergiesTechProductOffFormula'), $langs->trans('SynergiesTechConfirmProductOffFormula'), 'confirm_synergiestech_addline', '', 0, 1);

                return 1;
            }
        } else if (in_array('propalcard', $contexts)) {
            // accept or refuse proposal
            if ($action == 'statut') {
                $out = '';

                dol_include_once('/synergiestech/class/html.formsynergiestech.class.php');

                $langs->load('synergiestech@synergiestech');

                $formsynergiestech = new FormSynergiesTech($this->db);

                //Form to close proposal (signed or not)
                $formquestion = array(
                    array('type' => 'select','name' => 'statut','label' => $langs->trans("CloseAs"),'values' => array(2=>$object->LibStatut(Propal::STATUS_SIGNED), 3=>$object->LibStatut(Propal::STATUS_NOTSIGNED)), 'default' => (GETPOST('statut', 'int') ? GETPOST('statut', 'int') : '')),
                    array('type' => 'text', 'name' => 'note_private', 'label' => $langs->trans("Note"),'value' => (GETPOST('note_private', 'alpha') ? GETPOST('note_private', 'alpha') : ''))				// Field to complete private note (not replace)
                );

                // add file
                $formquestion[] = array('type' => 'other', 'name' => 'addfile', 'label' =>  $langs->trans("File"), 'value' => '<input type="file" name="addfile" />');

                /* Not used yet (Dolibarr 8.0)
                if (! empty($conf->notification->enabled)) {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
                    $notify = new Notify($this->db);
                    $formquestion = array_merge($formquestion, array(
                        array('type' => 'onecolumn', 'value' => $notify->confirmMessage('PROPAL_CLOSE_SIGNED', $object->socid, $object)),
                    ));
                }
                */

                $out .= $formsynergiestech->formconfirmfile($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('SetAcceptedRefused'), '', 'setstatut', $formquestion, '', 1, 300);

                // javascript for field file required
                $out .= <<<SCRIPT
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('select[name="statut"]').change(function(){
            var statutLabelTd = jQuery('input[name="addfile"]').parent().parent().find('td:first');
            statutLabelTd.prop('class', 'fieldrequired');

            // proposal signed
            if (this.value == 2) {
                statutLabelTd.addClass('fieldrequired');
            } else {
                statutLabelTd.removeClass('fieldrequired');
            }
        });
    });
</script>
SCRIPT;

                $this->resprints = $out;

                return 1;
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
    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);
        $confirm = GETPOST('confirm');

        if (in_array('requestmanagercard', $contexts)) {
            if ($action == 'synergiestech_set_thirdparty') {
                $object->socid = GETPOST('socid', 'int');
                if ($object->updateCommon($user) < 0) {
                    array_merge($this->errors, $object->errors);
                    return -1;
                }
                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                exit;
            } elseif ($action == 'addlink') {
                $addlink = GETPOST('addlink', 'alpha');
                $idtolinkto = GETPOST('idtolinkto', 'int');
                if ($addlink == 'equipement' && $idtolinkto > 0) {
                    if ($object->addContractsOfEquipment($idtolinkto) < 0) {
                        array_merge($this->errors, $object->errors);
                        return -1;
                    }
                }
            } elseif ($action == 'synergiestech_create_returnproducts' && $confirm == 'yes') {
                $langs->load("retourproduits@retourproduits");

                dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                $lines = synergiestech_get_return_products_list($this->db, $object->socid);

                // Get selected lines for each order
                $selected_lines = array();
                foreach ($lines as $product_id => $line) {
                    foreach ($line['orders'] as $line_id => $order_line) {
                        if (GETPOST('s-' . $line_id, 'int') == $line_id) {
                            $selected_lines[$order_line['order_id']][$line_id] = array(
                                'p' => GETPOST('p-' . $line_id, 'int'),
                                'q' => GETPOST('q-' . $line_id, 'int'),
                                'w' => GETPOST('w-' . $line_id, 'int'),
                                'e' => GETPOST('e-' . $line_id, 'alpha'), // Todo check si le multi-select renvoie une liste d'ids séparer par des virgules ou un array d'ids
                                'product' => $line['product'],
                                'order' => $order_line['label'],
                                'qty_sent' => $order_line['qty_sent'],
                                'equipments' => $order_line['equipments'],
                            );
                        }
                    }
                }

                if (!empty($selected_lines)) {
                    $langs->load('errors');
                    $langs->load("equipement@equipement");
                    $error = 0;
                    $ndCreated = 0;
                    $this->db->begin();

                    dol_include_once('/retourproduits/class/retourproduits.class.php');

                    foreach ($selected_lines as $order_id => $order) {
                        // Create RetourProduits object
                        $rpds = new RetourProduits($this->db);

                        // Set variables
                        $rpds->socid = $object->socid;
                        $rpds->origin = 'commande';
                        $rpds->origin_id = $order_id;
                        $rpds->context['synergiestech_create_returnproducts'] = $object->id;

                        // Add lines
                        foreach ($order as $line_id => $line) {
                            $fk_product = $line['p'];
                            $qty = $line['q'];
                            $fk_entrepot_dest = $line['w'];

                            // Test variables
                            if ($qty <= 0) {
                                setEventMessages($line['product'] . ' - ' . $line['order'] . ': ' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
                                $error++;
                            }
                            if ($qty <= 0 || $qty > $line['qty_sent']) {
                                setEventMessages($line['product'] . ' - ' . $line['order'] . ': ' . $langs->trans("ErrorBadValueForParameter", $qty, $langs->transnoentitiesnoconv("Qty")), null, 'errors');
                                $error++;
                            }
                            if ($fk_entrepot_dest <= 0) {
                                setEventMessages($line['product'] . ' - ' . $line['order'] . ': ' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
                                $error++;
                            }

                            $equipments = empty($line['e']) ? array() : explode(',', $line['e']); // Todo check si le multi-select renvoie une liste d'ids séparer par des virgules ou un array d'ids
                            if (!empty($equipments)) {
                                if (count($equipments) > min($qty, $line['qty_sent'])) {
                                    setEventMessages($line['product'] . ' - ' . $line['order'] . ': ' . $langs->trans("RetourProduitsErrorTooManyEquipmentSelected"), null, 'errors');
                                    $error++;
                                }

                                foreach ($equipments as $equipment_id) {
                                    $line = new RetourProduitsLigne($this->db);
                                    $line->fk_product = $fk_product;
                                    $line->qty = 1;
                                    $line->fk_entrepot_dest = $fk_entrepot_dest;
                                    $line->fk_origin_line = $line_id;
                                    $line->fk_equipement = $equipment_id;

                                    $qty--;
                                    $rpds->lines[] = $line;
                                }
                            }

                            if ($qty > 0) {
                                $line = new RetourProduitsLigne($this->db);
                                $line->fk_product = $fk_product;
                                $line->qty = $qty;
                                $line->fk_entrepot_dest = $fk_entrepot_dest;
                                $line->fk_origin_line = $line_id;
                                $line->fk_equipement = -1;
                                $rpds->lines[] = $line;
                            }
                        }

                        if (!$error) {
                            $retourId = $rpds->create($user);
                            if ($retourId < 0) {
                                $error++;
                                setEventMessages($line['product'] . ' - ' . $line['order'] . ': ' . $rpds->error, $rpds->errors, 'errors');
                            } else {
                                $ndCreated++;
                            }
                        }

                        if ($error) break;
                    }

                    if (!$error) {
                        $this->db->commit();
                        setEventMessage($langs->trans('SynergiesTechCreateNbRetourProduitsSuccessed', $ndCreated));
                    } else {
                        $this->db->rollback();
                        $action = "synergiestech_returnproducts";
                    }
                } else {
                    setEventMessage($langs->trans('RetourProduitsErrorNoProductSelected'), 'errors');
                    $action = "synergiestech_returnproducts";
                }
            } // Add message
            elseif ($action == 'stpremessage' && GETPOST('addmessage', 'alpha') && !$_POST['addfile'] && !$_POST['removAll'] && !$_POST['removedfile'] && !$_POST['modelselected'] &&
                $user->rights->requestmanager->creer && $object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS
            ) {
                $error = 0;
                $this->db->begin();

                dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
                $formrequestmanagermessage = new FormRequestManagerMessage($this->db, $object);

                dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
                $requestmanagermessage = new RequestManagerMessage($this->db);

                $requestmanagermessage->message_type = GETPOST('message_type', 'int');
                $requestmanagermessage->notify_assigned = GETPOST('notify_assigned', 'int');
                $requestmanagermessage->notify_requesters = GETPOST('notify_requesters', 'int');
                $requestmanagermessage->notify_watchers = GETPOST('notify_watchers', 'int');
                $requestmanagermessage->attached_files = $formrequestmanagermessage->get_attached_files();
                $requestmanagermessage->knowledge_base_ids = null;
                $requestmanagermessage->label = GETPOST('subject', 'alpha');
                $requestmanagermessage->note = GETPOST('message');
                $requestmanagermessage->requestmanager = $object;

                // Get extra fields of the message
                require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
                $message_extrafields = new ExtraFields($this->db);
                $message_extralabels = $message_extrafields->fetch_name_optionals_label($requestmanagermessage->table_element);
                $ret = $message_extrafields->setOptionalsFromPost($message_extralabels, $requestmanagermessage);

                // Save tags/categories
                $rmanager = new RequestManager($this->db);
                $rmanager->fetch($object->id);
                $rmanager->fetch_tags(1);
                $rmanager->oldcopy = clone $object;
                $tags_categories = GETPOST('tags_categories');
                $result = $rmanager->setCategories($tags_categories);
                if ($result < 0) {
                    setEventMessages($rmanager->error, $rmanager->errors, 'errors');
                    $error++;
                }

                // create message
                if (!$error) {
                    $result = $requestmanagermessage->create($user);
                    if ($result < 0) {
                        setEventMessages($requestmanagermessage->error, $requestmanagermessage->errors, 'errors');
                        $error++;
                    }
                }

                // Add knowledge base list into the message
                if (!$error) {
                    $knowledgebaselist = GETPOST('knowledgebaselist', 'alpha');
                    $knowledgebaselist = !empty($knowledgebaselist) ? explode(',', $knowledgebaselist) : array();

                    if (count($knowledgebaselist) > 0) {
                        $idx = 0;
                        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'requestmanager_message_knowledge_base(fk_actioncomm, position, fk_knowledge_base) VALUES';
                        foreach ($knowledgebaselist as $kb_id) {
                            $sql .= '(' . $requestmanagermessage->id . ',' . $idx . ',' . $kb_id . '),';
                            $idx++;
                        }
                        $sql = substr($sql, 0, -1);

                        $resql = $this->db->query($sql);
                        if (!$resql) {
                            setEventMessages($this->db->lasterror(), null, 'errors');
                            $error++;
                        }
                    }
                }

                if ($error) {
                    $this->db->rollback();
                    $action = 'stpremessage';
                } else {
                    $formrequestmanagermessage->remove_all_attached_files();
                    $this->db->commit();
                    header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
                    exit();
                }
            } // Add file
            elseif (GETPOST('addfile', 'alpha')) {
                dol_include_once('/requestmanager/lib/requestmanagermessage.lib.php');

                // Set tmp user directory
                $vardir = $conf->user->dir_output . "/" . $user->id;
                $upload_dir_tmp = $vardir . '/temp/rm-' . $object->id . '/';             // TODO Add $keytoavoidconflict in upload_dir path

                requestmanagermessage_add_file_process($object, $upload_dir_tmp, 0, 0, 'addedfile', '', null);
                $action = 'stpremessage';
                return 1;
            } // Remove file
            elseif (!empty($_POST['removedfile']) && empty($_POST['removAll'])) {
                dol_include_once('/requestmanager/lib/requestmanagermessage.lib.php');

                // Set tmp user directory
                $vardir = $conf->user->dir_output . "/" . $user->id;
                $upload_dir_tmp = $vardir . '/temp/rm-' . $object->id . '/';             // TODO Add $keytoavoidconflict in upload_dir path

                // TODO Delete only files that was uploaded from email form. This can be addressed by adding the trackid into the temp path then changing donotdeletefile to 2 instead of 1 to say "delete only if into temp dir"
                // GETPOST('removedfile','alpha') is position of file into $_SESSION["listofpaths"...] array.
                requestmanagermessage_remove_file_process($object, GETPOST('removedfile', 'alpha'), 0, 0);   // We do not delete because if file is the official PDF of doc, we don't want to remove it physically
                $action = 'stpremessage';
                return 1;
            }

            if ($action == 'premessage') $action == 'stpremessage';
        } elseif (in_array('contractcard', $contexts)) {
            $langs->load('synergiestech@synergiestech');
            if ($user->rights->synergiestech->generate->ticket_report && $action == 'synergiestech_generate_ticket_report_confirm' && $confirm == 'yes') {
                if (GETPOST('synergiestech_generate_ticket_report_date_startmonth') && GETPOST('synergiestech_generate_ticket_report_date_startday') && GETPOST('synergiestech_generate_ticket_report_date_startyear') &&
                    GETPOST('synergiestech_generate_ticket_report_date_endmonth') && GETPOST('synergiestech_generate_ticket_report_date_endday') && GETPOST('synergiestech_generate_ticket_report_date_endyear')
                ) {
                    $synergiestech_generate_ticket_report_date_start = dol_mktime(0, 0, 0, GETPOST('synergiestech_generate_ticket_report_date_startmonth'), GETPOST('synergiestech_generate_ticket_report_date_startday'), GETPOST('synergiestech_generate_ticket_report_date_startyear'));
                    $synergiestech_generate_ticket_report_date_end = dol_mktime(23, 59, 59, GETPOST('synergiestech_generate_ticket_report_date_endmonth'), GETPOST('synergiestech_generate_ticket_report_date_endday'), GETPOST('synergiestech_generate_ticket_report_date_endyear'));
                    dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                    $res = synergiestech_ticket_generate_report($this->db, $object->id, $synergiestech_generate_ticket_report_date_start, $synergiestech_generate_ticket_report_date_end);
                    if ($res > 0) {
                        setEventMessages($langs->trans('SynergiesTechTicketGenerateReportSuccess'), null);
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                        exit;
                    }
                } else {
                    $this->errors[] = $langs->trans('SynergiesTechTicketGenerateReportErrorDatesRequired');
                    return -1;
                }
            }
        } elseif (in_array('ordercard', $contexts)) {
            if ($action == 'addline' && $user->rights->commande->creer) {
                $product_id = GETPOST('idprod', 'int');

                if (GETPOST('prod_entry_mode') != 'free' && $product_id > 0) {
                    $langs->load('synergiestech@synergiestech');
                    // Gat all contracts of the thirdparty
                    require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                    $contract_static = new Contrat($this->db);
                    $contract_static->socid = $object->socid;
                    $list_contract = $contract_static->getListOfContracts();

                    // Get extrafields of the contract
                    $contract_extrafields = new ExtraFields($this->db);
                    $contract_extralabels = $contract_extrafields->fetch_name_optionals_label($contract_static->table_element);

                    // Get categories who has the contract formule category in the full path (exclude the contract formule category)
                    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
                    $categorie_static = new Categorie($this->db);
                    $all_categories = $categorie_static->get_full_arbo('product');
                    $contract_formule_categories = array();
                    foreach ($all_categories as $cat) {
                        if ((preg_match('/^' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '$/', $cat['fullpath']) ||
                                preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '$/', $cat['fullpath']) ||
                                preg_match('/^' . $conf->global->lSYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '_/', $cat['fullpath']) ||
                                preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '_/', $cat['fullpath'])
                            ) && $cat['id'] != $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE
                        ) {
                            $contract_formule_categories[$cat['label']] = $cat['id'];
                        }
                    }

                    // Get product categories for the contract formula
                    $contract_categories = array();
                    $contracts_list = array();
                    $formules_list = array();
                    $formules_not_found_list = array();
                    if (!empty($list_contract)) {
                        foreach ($list_contract as $contract) {
                            if (($contract->nbofserviceswait + $contract->nbofservicesopened) > 0 && $contract->statut != 2) {
                                $contract->fetch_optionals();
                                $formule_id = $contract->array_options['options_formule'];
                                $formule_label = $contract_extrafields->attribute_param['formule']['options'][$formule_id];
                                if (!empty($formule_label)) {
                                    $contract_category_id = $contract_formule_categories[$formule_label];
                                    if (isset($contract_category_id)) {
                                        $formules_list[$formule_id] = $formule_label;
                                        $contracts_list[] = $contract->getNomUrl(1);
                                        $contract_categories[$contract_category_id] = $contract_category_id;
                                    } else {
                                        $formules_not_found_list[$formule_label] = $formule_label;
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($contract_categories)) {
                        // if product is into the formula
                        $sql = "SELECT p.rowid";
                        $sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
                        $sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "categorie_product as cp ON cp.fk_product = p.rowid";
                        $sql .= ' WHERE p.entity IN (' . getEntity('product') . ')';
                        $sql .= ' AND p.rowid = ' . $product_id;
                        $sql .= ' AND cp.fk_categorie IN (' . implode(',', $contract_categories) . ')';

                        $resql = $this->db->query($sql);
                        if ($resql) {
                            // if product is not into the formula
                            if ($this->db->num_rows($resql) <= 0) {
                                $_SESSION['synergiestech_addline_formulas'] = implode(', ', $contracts_list)
                                    . ' - ' . $langs->trans('SynergiesTechFormules') . ' : ' . implode(', ', $formules_list);
                                $_SESSION['synergiestech_addline_get'] = $_GET;
                                $_SESSION['synergiestech_addline_post'] = $_POST;
                                $action = 'synergiestech_addline';
                            }
                        }
                    }
                }
            } elseif ($action == 'add' && $user->rights->commande->creer) {
                $origin = GETPOST('origin', 'alpha');
                $originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility

                if (!empty($origin) && !empty($originid) && $origin == 'requestmanager') {
                    // Parse element/subelement (ex: project_task)
                    $element = $subelement = $origin;
                    if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
                        $element = $regs [1];
                        $subelement = $regs [2];
                    }

                    // For compatibility
                    if ($element == 'order') {
                        $element = $subelement = 'commande';
                    }
                    if ($element == 'propal') {
                        $element = 'comm/propal';
                        $subelement = 'propal';
                    }
                    if ($element == 'contract') {
                        $element = $subelement = 'contrat';
                    }

                    dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

                    $classname = ucfirst($subelement);
                    $srcobject = new $classname($this->db);

                    dol_syslog("Try to find source object origin=" . $origin . " originid=" . $originid . " to add lines");
                    $result = $srcobject->fetch($originid);
                    if ($result > 0) {
                        $lines = $srcobject->lines;
                        if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
                            $srcobject->fetch_lines();
                            $lines = $srcobject->lines;
                        }

                        $num = count($lines);
                        if ($num > 0) {
                            dol_include_once('/synergiestech/class/html.formsynergiestech.class.php');
                            $formsynergiestech = new FormSynergiesTech($this->db);
                            $coloredproductlabelinfo = $formsynergiestech->loadColoredProductLabelInfo($srcobject);
                            $contract_categories = isset($coloredproductlabelinfo['contract_categories']) ? $coloredproductlabelinfo['contract_categories'] : array();

                            for ($i = 0; $i < $num; $i++) {
                                if (!(($lines[$i]->info_bits & 2) == 2) && !empty($lines[$i]->fk_product)) {
                                    $product_categories = $formsynergiestech->loadProductCategoriesList($lines[$i]->fk_product);

                                    $is_into_contract_categories = count(array_diff($contract_categories, $product_categories)) != count($contract_categories);
                                    // if product is not into the contract
                                    if (!$is_into_contract_categories) {
                                        $action = 'create';
                                        $object->context['products_not_in_contract'] = true;
                                    }
                                }
                            }
                        }
                    } else {
                        setEventMessages($srcobject->error, $srcobject->errors, 'errors');
                        $action = 'create';
                    }
                }
            } elseif ($action == 'confirm_synergiestech_addline' && $confirm == 'yes' && $user->rights->commande->creer) {
                $object->context['synergiestech_addline_not_into_formula'] = $_SESSION['synergiestech_addline_formulas'];
                unset($_SESSION['synergiestech_addline_formulas']);
                $_GET = $_SESSION['synergiestech_addline_get'];
                unset($_SESSION['synergiestech_addline_get']);
                $_POST = $_SESSION['synergiestech_addline_post'];
                unset($_SESSION['synergiestech_addline_post']);
                $action = 'addline';
            } elseif ($action == 'confirm_synergiestech_add' && $confirm == 'yes' && $user->rights->commande->creer) {
                $object->context['synergiestech_create_order_with_products_not_into_contract'] = true;
                $action = 'add';
            }
        } elseif (in_array('propalcard', $contexts)) {
            if ($object->id > 0) {
                $object->fetch_optionals();
                if (!$user->rights->synergiestech->propal->installation_value && !empty($object->array_options['options_sitevalue']))
                    accessforbidden();
            }
//        } elseif (in_array('requestmanagerfastcard', $contexts)) {
//            $langs->load('synergiestech@synergiestech');
//            if ($cancel) $action = '';
//            if ($action == 'confirm_force_principal_company' && $confirm == "yes" && $user->rights->requestmanager->creer) {
//                $force_principal_company = true;
//                $action = "addfast";
//            } elseif ($action == 'confirm_force_out_of_time' && $confirm == "yes" && $user->rights->requestmanager->creer) {
//                $force_out_of_time = true;
//                $action = "addfast";
//            }
//            // Create request
//            if ($action == 'addfast' && $user->rights->requestmanager->creer) {
//                $selectedCategories = GETPOST('categories', 'array') ? GETPOST('categories', 'array') : (GETPOST('categories', 'alpha') ? explode(',', GETPOST('categories', 'alpha')) : array());
//                $selectedContacts = GETPOST('contact_ids', 'array') ? GETPOST('contact_ids', 'array') : (GETPOST('contact_ids', 'alpha') ? explode(',', GETPOST('contact_ids', 'alpha')) : array());
//
//                $object->fk_type = GETPOST('type', 'int');
//                $object->label = GETPOST('label', 'alpha');
//                $object->socid_origin = GETPOST('socid_origin', 'int');
//                $object->socid = GETPOST('socid', 'int');
//                $object->socid_benefactor = GETPOST('socid_benefactor', 'int');
//                $object->socid_watcher = GETPOST('socid_watcher', 'int');
//                $object->requester_ids = $selectedContacts;
//                $object->fk_source = GETPOST('source', 'int');
//                $object->fk_urgency = GETPOST('urgency', 'int');
//                $object->description = GETPOST('description');
//                $selectedActionCommId = GETPOST('actioncomm_id') ? GETPOST('actioncomm_id') : -1;
//                $object->date_creation = dol_now();
//                $object->notify_requester_by_email = GETPOST('notify_requester_by_email', 'int') > 0 ? 1 : 0;
//                if ($object->socid_origin === '' && $object->socid > 0) {
//                    $object->socid_origin = $object->socid;
//                }
//                if (empty($conf->companyrelationships->enabled)) {
//                    $object->socid = $object->socid_origin;
//                    $object->socid_benefactor = $object->socid_origin;
//                }
//
//                // Add equipment links
//                $selectedEquipementId = GETPOST('equipement_id', 'int') ? intval(GETPOST('equipement_id', 'int')) : -1;
//                if ($selectedEquipementId > 0) {
//                    $object->linkedObjectsIds['equipement'][] = $selectedEquipementId;
//                }
//
//                // Possibility to add external linked objects with hooks
//                $object->origin = GETPOST('origin', 'alpha');
//                $object->origin_id = GETPOST('originid', 'int');
//                if ($object->origin && $object->origin_id > 0) {
//                    $object->linkedObjectsIds[$object->origin] = $object->origin_id;
//                    if (is_array($_POST['other_linked_objects']) && !empty($_POST['other_linked_objects'])) {
//                        $object->linkedObjectsIds = array_merge($object->linkedObjectsIds, $_POST['other_linked_objects']);
//                    }
//                }
//
//                $btnAction = '';
//                if (GETPOST('btn_create')) {
//                    $btnAction = 'create';
//                } else if (GETPOST('btn_associate')) {
//                    $btnAction = 'associate';
//                }
//
//                $db->begin();
//                if ($btnAction == 'create' || $force_principal_company || $force_out_of_time) {
//                    $res = requestmanagertimeslots_is_in_time_slot($object->socid, $object->date_creation);
//                    $object->created_out_of_time = is_array($res) ? 0 : ($res ? 0 : 1);
//                    if (!empty($conf->companyrelationships->enabled)) {
//                        $principal_companies_ids = $companyrelationships->getRelationships($object->socid_benefactor, 0);
//                        $not_principal_company = !in_array($object->socid, $principal_companies_ids) && $object->socid != $object->socid_benefactor;
//                    } else {
//                        $not_principal_company = false;
//                    }
//                    if ($not_principal_company && !$force_principal_company) {
//                        $error++;
//                        $action = 'force_principal_company';
//                    } elseif (!empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE) && $object->created_out_of_time && !$force_out_of_time) {
//                        $error++;
//                        $action = 'force_out_of_time';
//                    } else {
//                        $id = $object->create($user);
//                        if ($id < 0) {
//                            setEventMessages($object->error, $object->errors, 'errors');
//                            $error++;
//                        }
//
//                        if (!$error && $not_principal_company && $force_principal_company) {
//                            // Principal company forced for the benefactor
//                            $result = $object->addActionForcedPrincipalCompany($user);
//                            if ($result < 0) {
//                                setEventMessages($object->error, $object->errors, 'errors');
//                                $error++;
//                            }
//                        }
//
//                        if (!$error && !empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE) && $object->created_out_of_time && $force_out_of_time) {
//                            // Create forced out of time
//                            $result = $object->addActionForcedCreatedOutOfTime($user);
//                            if ($result < 0) {
//                                setEventMessages($object->error, $object->errors, 'errors');
//                                $error++;
//                            }
//                        }
//
//                        if (!$error) {
//                            // Category association
//                            $result = $object->setCategories($selectedCategories);
//                            if ($result < 0) {
//                                setEventMessages($object->error, $object->errors, 'errors');
//                                $error++;
//                            }
//                        }
//
//                        if (!$error && $selectedActionCommId > 0) {
//                            // link event to this request
//                            $result = $object->linkToActionComm($selectedActionCommId);
//                            if ($result < 0) {
//                                setEventMessages($object->error, $object->errors, 'errors');
//                                $error++;
//                            }
//                        }
//
//                        if ($error) {
//                            $action = 'createfast';
//                        }
//                    }
//                } else if ($btnAction == 'associate') {
//                    $associateList = GETPOST('associate_list', 'array') ? GETPOST('associate_list', 'array') : array();
//                    if (count($associateList) <= 0) {
//                        $object->errors[] = $langs->trans("RequestManagerCreateFastErrorNoRequestSelected");
//                        setEventMessages($object->error, $object->errors, 'errors');
//                        $error++;
//                    }
//
//                    if ($selectedActionCommId <= 0) {
//                        $object->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RequestManagerCreateFastActionCommLabel"));
//                        setEventMessages($object->error, $object->errors, 'errors');
//                        $error++;
//                    }
//
//                    if (!$error) {
//                        $object->fetch(intval($associateList[0]));
//
//                        // link event to this request
//                        $result = $object->linkToActionComm($selectedActionCommId);
//                        if ($result < 0) {
//                            setEventMessages($object->error, $object->errors, 'errors');
//                            $error++;
//                        }
//                    }
//
//                    if ($error) {
//                        $action = 'createfast';
//                    }
//                }
//
//                if (!$error) {
//                    $db->commit();
//                    if ($object->id > 0) {
//                        header('Location: ' . dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $object->id);
//                    } else {
//                        header('Location: ' . dol_buildpath('/requestmanager/list.php', 1));
//                    }
//                    exit();
//                } else {
//                    $db->rollback();
//                }
//            }
        }

        return 0;
    }

    /**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('invoicelist', $contexts)) {
            $langs->load('synergiestech@synergiestech');

            $enabled = (empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->creer)) ||
                (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->invoice_advance->validate));

            $this->resprints = '<option value="synergiestech_valid"' . (!$enabled ? ' disabled="disabled"' : '') . '>' .
                $langs->trans("ValidateBill") . '</option>';
        } elseif (in_array('contractlist', $contexts)) {
            $langs->load('synergiestech@synergiestech');

            $disabled = !$user->rights->synergiestech->generate->ticket_report;
            $this->resprints = '<option value="synergiestech_generate_ticket_report"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("SynergiesTechTicketGenerateReport") . '</option>';
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
        global $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('contractlist', $contexts)) {
            $massaction=GETPOST('massaction','alpha');
            if ($user->rights->synergiestech->generate->ticket_report && $massaction == 'synergiestech_generate_ticket_report') {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
                $langs->load('synergiestech@synergiestech');
                $params = array();
                $invalid_params = [ 'token', 'confirm', 'formfilteraction', 'selectedfields',
                    'button_search_y', 'button_search.y', 'button_search',
                    'button_removefilte_x', 'button_removefilter.x', 'button_removefilter',
                    'action', 'massaction', 'confirmmassaction', 'checkallactions',
                ];
                foreach (array_merge($_POST, $_GET) as $key => $value) {
                    if (!in_array($key, $invalid_params) && $value != '') {
                        $params[$key] = $value;
                    }
                }
                $base_url = $_SERVER["PHP_SELF"] . (count($params) ? "?" . http_build_query($params, '', '&') : '');
                $now = dol_getdate(dol_now(), true);
                $prevmonth = dol_get_prev_month($now['mon'], $now['year']);
                $date_start = dol_get_first_day($prevmonth['year'], $prevmonth['month']);
                $date_end = dol_get_last_day($prevmonth['year'], $prevmonth['month']);

                $form = new Form($this->db);
                $formquestion = array(
                    array(
                        'name' => 'synergiestech_generate_ticket_report_date_start',
                        'label' => $langs->trans('DateStart'),
                        'type' => 'date',
                        'value' => $date_start
                    ),
                    array(
                        'name' => 'synergiestech_generate_ticket_report_date_end',
                        'label' => $langs->trans('DateEnd'),
                        'type' => 'date',
                        'value' => $date_end
                    ),
                );

                $formconfirm = $form->formconfirm($base_url, $langs->trans("SynergiesTechTicketGenerateReportConfirm"), '', 'synergiestech_generate_ticket_report_confirm', $formquestion, 'yes', 1, 200);

                dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                synergiestech_print_confirmform($formconfirm);
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
	function doMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs, $mysoc, $hidedetails, $hidedesc, $hideref;

        $contexts = explode(':', $parameters['context']);
        $confirm  = GETPOST('confirm');

        if (in_array('invoicelist', $contexts)) {
            $massaction = GETPOST('massaction', 'alpha');

            if ($massaction == 'synergiestech_valid' &&
                ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->creer)) ||
                    (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->invoice_advance->validate)))
            ) {
                $langs->load('synergiestech@synergiestech');
                $langs->load("errors");
                $warnings = array();
                $errors = array();
                $nb_valided = 0;

                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

                foreach ($parameters['toselect'] as $invoice_id) {
                    $invoice = new Facture($this->db);
                    if ($invoice->fetch($invoice_id) > 0) {
                        // Classify to validated
                        if ($invoice->statut == 0 && count($invoice->lines) > 0 &&
                            ((($invoice->type == Facture::TYPE_STANDARD || $invoice->type == Facture::TYPE_REPLACEMENT ||
                                        $invoice->type == Facture::TYPE_DEPOSIT || $invoice->type == Facture::TYPE_PROFORMA || $invoice->type == Facture::TYPE_SITUATION) &&
                                    (!empty($conf->global->FACTURE_ENABLE_NEGATIVE) || $invoice->total_ttc >= 0)) || ($invoice->type == Facture::TYPE_CREDIT_NOTE && $invoice->total_ttc <= 0))
                        ) {
                            $error = 0;
                            $invoice->fetch_thirdparty();

                            // On verifie signe facture
                            if ($invoice->type == Facture::TYPE_CREDIT_NOTE) {
                                // Si avoir, le signe doit etre negatif
                                if ($invoice->total_ht >= 0) {
                                    $error++;
                                    $errors[] = $invoice->ref . ' - ' . $langs->trans("ErrorInvoiceAvoirMustBeNegative");
                                }
                            } else {
                                // Si non avoir, le signe doit etre positif
                                if (empty($conf->global->FACTURE_ENABLE_NEGATIVE) && $invoice->total_ht < 0) {
                                    $error++;
                                    $errors[] = $invoice->ref . ' - ' . $langs->trans("ErrorInvoiceOfThisTypeMustBePositive");
                                }
                            }

                            if (!$error) {
                                // Check for mandatory prof id (but only if country is than than ours)
                                if ($mysoc->country_id > 0 && $invoice->thirdparty->country_id == $mysoc->country_id) {
                                    for ($i = 1; $i <= 6; $i++) {
                                        $idprof_mandatory = 'SOCIETE_IDPROF' . ($i) . '_INVOICE_MANDATORY';
                                        $idprof = 'idprof' . $i;
                                        if (!$invoice->thirdparty->$idprof && !empty($conf->global->$idprof_mandatory)) {
                                            $error++;
                                            $errors[] = $invoice->ref . ' - ' . $langs->trans('ErrorProdIdIsMandatory',
                                                    $langs->transcountry('ProfId' . $i, $invoice->thirdparty->country_code));
                                        }
                                    }
                                }

                                if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
                                    $qualified_for_stock_change = $invoice->hasProductsOrServices(2);
                                } else {
                                    $qualified_for_stock_change = $invoice->hasProductsOrServices(1);
                                }

                                // Check for warehouse
                                $idwarehouse = '';
                                if ($invoice->type != Facture::TYPE_DEPOSIT && !empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change) {
                                    if (!$idwarehouse || $idwarehouse == -1) {
                                        $error++;
                                        $errors[] = $invoice->ref . /*' - ' . $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")) .*/
                                            ' - ' . $langs->trans('SynergiesTechNotSupportStockCalculateOnBill');
                                    }
                                }

                                if (!$error) {
                                    $result = $invoice->validate($user, '', $idwarehouse);
                                    if ($result >= 0) {
                                        // Define output language
                                        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                                            $outputlangs = $langs;
                                            $newlang = '';
                                            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
                                            if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $invoice->thirdparty->default_lang;
                                            if (!empty($newlang)) {
                                                $outputlangs = new Translate("", $conf);
                                                $outputlangs->setDefaultLang($newlang);
                                                $outputlangs->load('products');
                                            }
                                            $model = $invoice->modelpdf;
                                            $ret = $invoice->fetch($invoice_id); // Reload to get new records

                                            $result = $invoice->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                                            if ($result < 0) {
                                                $warnings[] = $invoice->ref . ' - ' . $invoice->errorsToString();
                                            }
                                        }
                                    } else {
                                        $error++;
                                        $errors[] = $invoice->ref . ' - ' . $invoice->errorsToString();
                                    }
                                }

                                if (!$error) {
                                    $nb_valided++;
                                }
                            }
                        }
                    }
                }

                if ($nb_valided > 0) {
                    setEventMessage($langs->trans('SynergiesTechInvoiceMassValidate', $nb_valided));
                }

                if (!empty($warnings)) {
                    setEventMessages(null, $warnings, 'warnings');
                }

                if (!empty($errors)) {
                    $this->errors = $errors;
                    return -1;
                }
            }
        } elseif (in_array('contractlist', $contexts)) {
            $langs->load('synergiestech@synergiestech');
            if ($user->rights->synergiestech->generate->ticket_report && $action == 'synergiestech_generate_ticket_report_confirm' && $confirm == 'yes') {
                if (GETPOST('synergiestech_generate_ticket_report_date_startmonth') && GETPOST('synergiestech_generate_ticket_report_date_startday') && GETPOST('synergiestech_generate_ticket_report_date_startyear') &&
                    GETPOST('synergiestech_generate_ticket_report_date_endmonth') && GETPOST('synergiestech_generate_ticket_report_date_endday') && GETPOST('synergiestech_generate_ticket_report_date_endyear')) {
                    $synergiestech_generate_ticket_report_date_start = dol_mktime(0, 0, 0, GETPOST('synergiestech_generate_ticket_report_date_startmonth'), GETPOST('synergiestech_generate_ticket_report_date_startday'), GETPOST('synergiestech_generate_ticket_report_date_startyear'));
                    $synergiestech_generate_ticket_report_date_end = dol_mktime(23, 59, 59, GETPOST('synergiestech_generate_ticket_report_date_endmonth'), GETPOST('synergiestech_generate_ticket_report_date_endday'), GETPOST('synergiestech_generate_ticket_report_date_endyear'));
                    $error = 0;
                    dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                    foreach ($parameters['toselect'] as $objectid) {
                        $res = synergiestech_ticket_generate_report($this->db, $objectid, $synergiestech_generate_ticket_report_date_start, $synergiestech_generate_ticket_report_date_end);
                        if ($res < 0) {
                            $error++;
                        }
                    }
                    if (!$error) {
                        setEventMessages($langs->trans('SynergiesTechTicketGenerateReportSuccess'), null);
                    }
                } else {
                    $this->errors[] = $langs->trans('SynergiesTechTicketGenerateReportErrorDatesRequired');
                    return -1;
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
        global $user, $langs;

        $origin = GETPOST('origin', 'alpha');
        $originid = GETPOST('originid', 'int');

        // Propagation of references
        if ($action == 'create' && !empty($origin) && !empty($originid)) {
            // Parse element/subelement (ex: project_task)
            $element = $subelement = $origin;
            if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
                $element = $regs [1];
                $subelement = $regs [2];
            }

            // For compatibility
            if ($element == 'order' || $element == 'commande') {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal') {
                $element = 'comm/propal';
                $subelement = 'propal';
            }
            if ($element == 'contract') {
                $element = $subelement = 'contrat';
            }

            dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($this->db);
            $objectsrc->fetch($originid);

            $ref_client = !empty($objectsrc->ref_client) ? str_replace('"', '\\"', $objectsrc->ref_client) : (!empty($objectsrc->ref_customer) ? str_replace('"', '\\"', $objectsrc->ref_customer) : '');

            print <<<SCRIPT
<script type="text/javascript">
    $(document).ready(function() {
        $('input[name="ref_client"]').val("$ref_client");
        $('input[name="ref_customer"]').val("$ref_client");
    });
</script>
SCRIPT;
        }

        $contexts = explode(':', $parameters['context']);
        if (in_array('ordercard', $contexts) && $action == 'create' && $object->context['products_not_in_contract'] && $user->rights->commande->creer) {
            $langs->load('synergiestech@synergiestech');

            dol_include_once('/synergiestech/class/html.formsynergiestech.class.php');
            $formsynergiestech = new FormSynergiesTech($this->db);

            $formconfirm = $formsynergiestech->formconfirm($_SERVER['PHP_SELF'], $langs->trans('SynergiesTechProductsOffFormula'), $langs->trans('SynergiesTechConfirmProductsOffFormula'), 'confirm_synergiestech_add', '', 0, 1, 200, 500, 'crea_commande');
            // Create the confirm form
            print '<tr><td colspan="2">' . $formconfirm . '</tr></td>';
        }

        return 0;
    }

    /**
     * _redirection function
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    private function _redirection($parameters, &$object, &$action, $hookmanager) {
        global $conf, $langs;

        // Force attach equipment
        if (!empty($conf->global->SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED)) {
            if (!preg_match('/\/equipement\/tabs\/expeditionAdd\.php/i', $_SERVER["PHP_SELF"]) &&
                preg_match('/\/equipement\/tabs\/expeditionAdd\.php/i', $_SERVER["HTTP_REFERER"])
            ) {
                dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                $query = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_QUERY);
                parse_str($query, $params);

                if (isset($params['id']) && $params['id'] > 0 && !synergiestech_has_shipping_equipment_to_validate($this->db, $params['id']) &&
                    synergiestech_has_shipping_equipment_to_serialize($this->db, $params['id'])) {
                    $langs->load('synergiestech@synergiestech');
                    setEventMessage($langs->trans('SynergiesTechShippingHasEquipmentToSerialize'), 'errors');
                    header("Location: " . $_SERVER["HTTP_REFERER"]);
                    exit;
                }
            } elseif (!preg_match('/\/equipement\/tabs\/expedition\.php/i', $_SERVER["PHP_SELF"]) &&
                preg_match('/\/equipement\/tabs\/expedition\.php/i', $_SERVER["HTTP_REFERER"])
            ) {
                dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                $query = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_QUERY);
                parse_str($query, $params);

                if (isset($params['id']) && $params['id'] > 0 && synergiestech_has_shipping_equipment_to_validate($this->db, $params['id'])) {
                    $langs->load('synergiestech@synergiestech');
                    setEventMessage($langs->trans('SynergiesTechShippingHasEquipmentToValidate'), 'errors');
                    header("Location: " . $_SERVER["HTTP_REFERER"]);
                    exit;
                }
            } elseif (preg_match('/\/equipement\/tabs\/expedition\.php/i', $_SERVER["PHP_SELF"])) {
                dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                $shipping_id = GETPOST('id');

                if ($shipping_id > 0 && !synergiestech_has_shipping_equipment_to_validate($this->db, $shipping_id) &&
                    synergiestech_has_shipping_equipment_to_serialize($this->db, $shipping_id)) {
                    header("Location: " . dol_buildpath('/equipement/tabs/expeditionAdd.php', 2) . '?id=' . $shipping_id);
                    exit;
                }
            }
        }

        // Force set equipment
        if (!empty($conf->global->SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH)) {
           if (!preg_match('/\/equipement\/tabs\/supplier_order\.php/i', $_SERVER["PHP_SELF"]) &&
               preg_match('/\/equipement\/tabs\/supplier_order\.php/i', $_SERVER["HTTP_REFERER"])
           ) {
               dol_include_once('/synergiestech/lib/synergiestech.lib.php');
               $query = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_QUERY);
               parse_str($query, $params);

               if (isset($params['id']) && $params['id'] > 0 && synergiestech_has_dispatching_equipment_to_serialize($this->db, $params['id'])) {
                   $langs->load('synergiestech@synergiestech');
                   setEventMessage($langs->trans('SynergiesTechOrderSupplierHasEquipmentToSerialize'), 'errors');
                   header("Location: " . $_SERVER["HTTP_REFERER"]);
                   exit;
               }
           }
        }

        // Redirect to tab equipment when equipement is attached to a shipping
        if (!empty($conf->global->SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATED_TO_ATTACH_EQUIPMENTS)) {
            if (preg_match('/\/equipement\/tabs\/expeditionAdd\.php/i', $_SERVER["PHP_SELF"])) {
                dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                $shipping_id = GETPOST('id');

                if ($shipping_id > 0 && synergiestech_has_shipping_equipment_to_validate($this->db, $shipping_id)) {
                    header("Location: " . dol_buildpath('/equipement/tabs/expedition.php', 2) . '?id=' . $shipping_id);
                    exit;
                }
            }
        }

        // Redirect to shipping creation
        if (!empty($conf->global->SYNERGIESTECH_ENABLED_WORKFLOW_SHIPPING_CREATE_AFTER_ORDER)) {
            if (preg_match('/\/expedition\/shipment\.php/i', $_SERVER["PHP_SELF"])) {
                $order_id = GETPOST('id');

                if ($order_id > 0) {
                    require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

                    $commande = new Commande($this->db);
                    if ($commande->fetch($order_id)) {
                        // check status of order before redirect to shipping creation
                        if ($commande->statut > Commande::STATUS_DRAFT && $commande->statut < Commande::STATUS_CLOSED) {
                            header("Location: " . dol_buildpath('/expedition/card.php', 1) . '?action=create&shipping_method_id=&origin=commande&origin_id=' . $order_id . '&projectid=&entrepot_id=-1');
                            exit;
                        }
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }

    /**
	 * Overloading the findAllBenefactorEquipmentsSQL function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function findAllBenefactorEquipmentsSQL($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('requestmanagerdao', $contexts)) {
            $fkSocPrincipal = $parameters['socid_principal'] > 0 ? $parameters['socid_principal'] : 0;
            $fkSocBenefactor = $parameters['socid_benefactor'] > 0 ? $parameters['socid_benefactor'] : 0;

            // Get contracts of the principal company
            /*require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
            $contract = new Contrat($this->db);
            $contract->socid = $fkSocPrincipal;
            $thirdPartyContractList = $contract->getListOfContracts();
            $thirdPartyContractList = is_array($thirdPartyContractList) ? $thirdPartyContractList : array();

            // Get extrafields of the contract
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $contractExtraFields = new ExtraFields($this->db);
            $contractExtraLabels = $contractExtraFields->fetch_name_optionals_label($contract->table_element);

            // Get formulas of the principal company contracts
            $product_category_names = array();
            foreach ($thirdPartyContractList as $c) {
                if (($c->nbofserviceswait + $c->nbofservicesopened) > 0 && $c->statut != 2) {
                    $c->fetch_optionals();
                    $label_formula = $contractExtraFields->attribute_param['formule']['options'][$c->array_options['options_formule']];
                    $product_category_names[$label_formula] = $label_formula;
                }
            }

            // Get product categories of the principal company contracts formula
            $product_categories = array();
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
            $categorie_static = new Categorie($this->db);
            $all_categories = $categorie_static->get_full_arbo('product');
            foreach ($all_categories as $cat) {
                if (!isset($product_categories[$cat['id']]) &&
                    (preg_match('/^' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '$/', $cat['fullpath']) ||
                     preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '$/', $cat['fullpath']) ||
                     preg_match('/^' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '_/', $cat['fullpath']) ||
                     preg_match('/_' . $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE . '_/', $cat['fullpath'])) &&
                    $cat['id'] != $conf->global->SYNERGIESTECH_PRODUCT_CATEGORY_FOR_CONTRACT_FORMULE &&
                    in_array($cat['label'], $product_category_names)
                ) {
                    $product_categories[$cat['id']] = $cat['id'];
                }
            }*/

            $sql = "SELECT e.rowid , e.ref";
            $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as e";
            $sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "equipement_extrafields as eef ON eef.fk_object = e.rowid";
            //$sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "categorie_product as cp ON cp.fk_product = e.fk_product";
            $sql .= " WHERE e.entity IN (" . getEntity('equipement') . ")";
            //$sql .= ' AND cp.fk_categorie IN (' . (count($product_categories) > 0 ? implode(',', $product_categories) : '0') . ')';
            $sql .= " AND e.fk_soc_client = " . $fkSocBenefactor;
            $sql .= " AND eef.machineclient = 1";

            $this->resprints = $sql;
            return 1;
        }

        return 0;
    }

    /**
	 * Overloading the showLinkToObjectBlock function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function showLinkToObjectBlock($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        $contexts = explode(':', $parameters['context']);

        $thirdparty = null;
        if (in_array('requestmanagercard', $contexts) && !is_object($object->thirdparty_benefactor) && method_exists($object, "fetch_thirdparty_benefactor")) {
            $object->fetch_thirdparty_benefactor();
            $thirdparty = $object->thirdparty_benefactor;
        } elseif ($conf->companyrelationships->enabled) {
            if (empty($object->array_options) && method_exists($object, "fetch_optionals")) {
                $object->fetch_optionals();
            }
            if (!empty($object->array_options['options_companyrelationships_fk_soc_benefactor']) && $object->array_options['options_companyrelationships_fk_soc_benefactor'] > 0) {
                $societe = new Societe($this->db);
                $societe->fetch($object->array_options['options_companyrelationships_fk_soc_benefactor']);
                $thirdparty = $societe;
            }
        }

        if (is_object($thirdparty) && !empty($thirdparty->id) && $thirdparty->id > 0) {
            $listofidcompanytoscan = $thirdparty->id;
            if (($thirdparty->parent > 0) && !empty($conf->global->THIRDPARTY_INCLUDE_PARENT_IN_LINKTO)) $listofidcompanytoscan .= ',' . $thirdparty->parent;
            if (($object->fk_project > 0) && !empty($conf->global->THIRDPARTY_INCLUDE_PROJECT_THIRDPARY_IN_LINKTO)) {
                include_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                $tmpproject = new Project($this->db);
                $tmpproject->fetch($object->fk_project);
                if ($tmpproject->socid > 0 && ($tmpproject->socid != $thirdparty->id)) $listofidcompanytoscan .= ',' . $tmpproject->socid;
                unset($tmpproject);
            }

            $langs->load('equipement@equipement');

            $possiblelinks = array(
                'equipement' => array(
                    'enabled' => $conf->equipement->enabled,
                    'perms' => 1,
                    'label' => 'LinkToEquipement',
                    'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, e.rowid, e.ref, CONCAT(p.ref, ' - ', p.label, IF(eef.machineclient = 1, ' (Machine)', '')) AS ref_client FROM " . MAIN_DB_PREFIX . "societe as s" .
                        " INNER JOIN  " . MAIN_DB_PREFIX . "equipement as e ON e.fk_soc_client = s.rowid" .
                        " LEFT JOIN  " . MAIN_DB_PREFIX . "equipement_extrafields as eef ON eef.fk_object = e.rowid" .
                        " LEFT JOIN  " . MAIN_DB_PREFIX . "product as p ON p.rowid = e.fk_product" .
                        " LEFT JOIN  " . MAIN_DB_PREFIX . "element_element as ee" .
                        "   ON (ee.sourcetype = 'equipement' AND ee.fk_source = e.rowid AND ee.targettype = '" . $object->element . "' AND ee.fk_target = " . $object->id . ")" .
                        "   OR (ee.targettype = 'equipement' AND ee.fk_target = e.rowid AND ee.sourcetype = '" . $object->element . "' AND ee.fk_source = " . $object->id . ")" .
                        " WHERE e.entity IN (" . getEntity('equipement') . ')' .
                        " AND e.fk_soc_client IN (" . $listofidcompanytoscan . ")" .
                        " AND eef.machineclient = 1" .
                        ' AND ee.rowid IS NULL' .
                        ' GROUP BY e.rowid, s.rowid',
                ),
            );

            $conf->global->EQUIPEMENT_DISABLE_SHOW_LINK_TO_OBJECT_BLOCK = 1;
            $conf->global->REQUESTMANAGER_DISABLE_SHOW_LINK_TO_OBJECT_BLOCK = 1;
            $this->results = $possiblelinks;
        }

        return 0;
    }

    /**
	 * Overloading the getBlackWhitelistOfProperties function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function getBlackWhitelistOfProperties($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);

        if (in_array('globalapi', $contexts)) {
//                    // Overwrite the whitelist for the object
//                    $parameters['whitelist_of_properties'] = array(
//                    );
//                    // Overwrite the whitelist for the object if is a linked object
//                    $parameters['whitelist_of_properties_linked_object'] = array(
//                    );
//                    // Overwrite the blacklist for the object
//                    $parameters['blacklist_of_properties'] = array(
//                    );
//                    // Overwrite the blacklist for the object if is a linked object
//                    $parameters['blacklist_of_properties_linked_object'] = array(
//                    );

            switch ($object->element) {
//                case 'contrat':
//                    unset($parameters['whitelist_of_properties']['ref_customer']);
//                    break;
                case 'product':
                    $parameters['whitelist_of_properties_linked_object'] = array(
                        "description" => '', "ref" => '', "id" => '', "array_options" => array('options_publiclabel' => ''), "label" => '',
                    );
                    break;
                case 'contact':
                    $parameters['whitelist_of_properties_linked_object'] = array(
                        "civility_code" => '', "lastname" => '', "firstname" => '',
                    );
                    $parameters['whitelist_of_properties'] = array(
                        "civility_code" => '', "civility_id" => '', "lastname" => '', "firstname" => '', "socname" => '', "mail" => '', "id" => '', "user_id" => '', "phone_pro" => '', "phone_perso" => '', "phone_mobile" => '', "photo" => '', "email" => '', "statut" => '', "socid" => '', "poste" => '', "address" => '', "zip" => '', "town" => '',
                    );
                    break;
                case 'user':
                    $parameters['whitelist_of_properties_linked_object'] = array(
                        "lastname" => '', "firstname" => '',
                    );
                    $parameters['whitelist_of_properties'] = array(
                        "lastname" => '', "firstname" => '', "id" => '', "gender" => '', "email" => '', "signature" => '', "address" => '', "zip" => '', "town" => '', "office_phone" => '', "office_fax" => '', "user_mobile" => '', "socid" => '', "contactid" => '', "photo" => '', "lang" => '', "rights" => '', "array_options" => '', "thirdparty" => ''
                    );
                    break;
                case 'usergroup':
                    $parameters['whitelist_of_properties_linked_object'] = array(
                        "name" => '',
                    );
                    break;
				case 'requestmanager':
				$parameters['whitelist_of_properties'] = array(
                        "id" => '', "fk_parent"=>'',"ref"=>'',"ref_ext"=>'',"socid_origin"=>'',"socid"=>'',"socid_benefactor"=>'',"label"=>'',"description"=>'',"fk_type"=>'',"fk_category"=>'',"fk_source"=>'',"fk_urgency"=>'',"fk_impact"=>'',"fk_priority"=>'',"fk_reason_resolution"=>'',"requester_ids"=>'',"statut"=>'',"statut_type"=>'',"entity"=>'',"date_creation"=>'',"date_modification"=>'',"linkedObjectsIds"=>'',"thirdparty_origin"=>'',"thirdparty"=>'',"thirdparty_benefactor"=>'',"children_request_ids"=>'',"children_request_list"=>''
                    );
				break;
                case 'societe':
                    $parameters['whitelist_of_properties_linked_object'] = array(
                        "name" => '',"nom" => '', "name_alias" => '', "address" => '',"zip" => '', "town" => '',
                        "state_id" => '', "state_code" => '', "state" => '', "departement_code" => '', "departement" => '', "pays" => ''
                    );
					$parameters['whitelist_of_properties'] = array(
            "name" => '', "nom" => '', "name_alias" => '', "address" => '', "zip" => '', "town" => '',
            "state_id" => '', "state_code" => '', "state" => '', "departement_code" => '', "departement" => '', "pays" => '',
            "phone" => '', "fax" => '', "email" => '', "code_client" => '',
            "code_fournisseur" => '',"ref" => '',
            "id" => '', "linkedObjectsIds" => '',"thirdparty_principal_ids" => '',
                        "thirdparty_benefactor_ids" => ''
        );
<<<<<<< HEAD
=======
		break;
		case 'propal':
		if (DolibarrApiAccess::$user->societe_id > 0) {
		$parameters['blacklist_of_properties'] = array(
            "lines" => ''
        );
		}
					
>>>>>>> Ajustement confidentialité api Module synergies-tech
                    break;
                case 'propaldet':
                    if (DolibarrApiAccess::$user->societe_id > 0) {
                        $parameters['blacklist_of_properties'] = array(
                            "vat_src_code" => '', "tva_tx" => '', "localtax1_tx" => '', "localtax2_tx" => '', "localtax1_type" => '',
                            "localtax2_type" => '', "subprice" => '', "fk_remise_except" => '', "remise_percent" => '', "info_bits" => '',
                            "total_ht" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '', "total_ttc" => '',
                            "fk_fournprice" => '', "pa_ht" => '', "marge_tx" => '', "marque_tx" => '', "special_code" => '',
                            "fk_multicurrency" => '', "multicurrency_code" => '', "multicurrency_subprice" => '', "multicurrency_total_ht" => '',
                            "multicurrency_total_tva" => '', "multicurrency_total_ttc" => '',
                        );
                        $parameters['blacklist_of_properties_linked_object'] = array(
                            "vat_src_code" => '', "tva_tx" => '', "localtax1_tx" => '', "localtax2_tx" => '', "localtax1_type" => '',
                            "localtax2_type" => '', "subprice" => '', "fk_remise_except" => '', "remise_percent" => '', "info_bits" => '',
                            "total_ht" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '', "total_ttc" => '',
                            "fk_fournprice" => '', "pa_ht" => '', "marge_tx" => '', "marque_tx" => '', "special_code" => '',
                            "fk_multicurrency" => '', "multicurrency_code" => '', "multicurrency_subprice" => '', "multicurrency_total_ht" => '',
                            "multicurrency_total_tva" => '', "multicurrency_total_ttc" => '',
                        );
                    }
                    break;
                case 'shipping':
                    if (DolibarrApiAccess::$user->societe_id > 0) {
                        $parameters['blacklist_of_properties'] = array(
                            "lines" => '',
                            "array_options" => array("options_companyrelationships_availability_principal" => '', "options_companyrelationships_availability_benefactor" => '')
                        );
                    }
                    break;
                case 'contrat':
                    if (DolibarrApiAccess::$user->societe_id > 0) {
                        $parameters['whitelist_of_properties'] = array(
                            "id" => '', "ref" => '', "ref_customer" => '', "ref_supplier" => '', "statut" => '',
                            "mise_en_service" => '', "date_contrat" => '', "date_creation" => '', "fin_validite" => '', "date_modification" => '',
                            "date_validation" => '', "user_author_id" => '', "commercial_signature_id" => '', "commercial_suivi_id" => '',
                            "note_public" => '', "fk_project" => '', "socid" => '', "thirdparty" => '',
                            "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '', "array_options" => array("options_companyrelationships_fk_soc_benefactor" => '', "options_formule" => '', "options_signaturedate" => '', "options_startdate" => '', "options_duration" => ''),
                        );

                    }
                    break;
                case 'contratdet':
                    if (DolibarrApiAccess::$user->societe_id > 0) {
                        $parameters['whitelist_of_properties'] = array(
                            "id" => '', "ref" => '', "tms" => '', "fk_contrat" => '', "fk_product" => '', "statut" => ''
                        );
                    }
                    break;
                case 'facture':
                    if (DolibarrApiAccess::$user->societe_id > 0) {
                        $parameters['whitelist_of_properties'] = array(
                            "id" => '', "ref" => '', "ref_client" => '', "type" => '', "date" => '',
                            "date_validation" => '', "datem" => '',
                            "total_ht" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '', "total_ttc" => '',
                            "paye" => '', "close_code" => '', "socid" => '', "statut" => '',
                            "date_lim_reglement" => '', "mode_reglement_id" => '', "mode_reglement_code" => '', "mode_reglement" => '',
                            "cond_reglement_id" => '', "cond_reglement_code" => '', "cond_reglement_doc" => '',
                            "thirdparty" => '', "array_options" => '', "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '',
                        );

                        $parameters['blacklist_of_properties'] = array("array_options" => array("options_companyrelationships_availability_principal" => '', "options_companyrelationships_availability_benefactor" => ''));
                    }
                    break;
                case 'facturedet':
                    if (DolibarrApiAccess::$user->societe_id > 0) {
                        $parameters['whitelist_of_properties'] = array(
                            "id" => '',
                            "fk_product" => '', "total_ht" => '', "total_tva" => '', "total_ttc" => ''
                        );
                    }
                    break;
            }
        }

        return 0;
    }

    /**
	 * Overloading the addMoreInfoBlocs function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreInfoBlocs($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);

        if (in_array('requestmanagercard', $contexts)) {
            global $user;

            dol_include_once('/requestmanager/class/requestmanager.class.php');
            if ($object->statut_type == RequestManager::STATUS_TYPE_IN_PROGRESS && $user->rights->requestmanager->creer) {
                global $langs, $file;

                /*
                 * Affiche formulaire message
                 */

                print '<div id="formmessagebeforetitle" name="formmessagebeforetitle"></div>';
                print '<div class="clearboth"></div>';
                print '<br>';
                print load_fiche_titre($langs->trans('RequestManagerAddMessage'));

                dol_fiche_head();

                // Cree l'objet formulaire message
                dol_include_once('/synergiestech/class/html.formsynergiestechmessage.class.php');
                $formsynergiestechmessage = new FormSynergiesTechMessage($this->db, $object);

                // Tableau des parametres complementaires du post
                $formsynergiestechmessage->param['action'] = $action;
                $formsynergiestechmessage->param['models_id'] = GETPOST('stmodelmessageselected', 'int');
                $formsynergiestechmessage->param['knowledgebaselist'] = GETPOST('knowledgebaselist', 'alpha');
                $formsynergiestechmessage->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $object->id;
                $formsynergiestechmessage->withcancel = 0;

                // Init list of files
                if (GETPOST("messagemode") == 'init') {
                    $formsynergiestechmessage->clear_attached_files();
                    $formsynergiestechmessage->add_attached_files($file, basename($file), dol_mimetype($file));
                }

                // Show form
                print $formsynergiestechmessage->get_message_form();

                dol_fiche_end();
            }
        }
    }

    /**
	 * Overloading the printFieldListWhereCustomerOrderToBill function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function printFieldListWhereCustomerOrderToBill($parameters, &$object, &$action, $hookmanager)
    {
       return " AND c.total_ttc > 0 ";
    }

    /**
	 * Overloading the addNextBannerTab function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addNextBannerTab($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('requestmanagercard', $contexts)) {
            require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extrafields_contract = new ExtraFields($this->db);
            $extralabels_contract = $extrafields_contract->fetch_name_optionals_label('contrat');
            $object->fetchObjectLinked();
            $list_contract = is_array($object->linkedObjects['contrat']) ? $object->linkedObjects['contrat'] : array();
            $to_print = array();
            if (!empty($list_contract)) {
                foreach ($list_contract as $contract) {
                    if (($contract->nbofserviceswait + $contract->nbofservicesopened) > 0 && $contract->statut != 2) {
                        $contract->fetch_optionals();
                        $to_print[] = "<a href='" . DOL_URL_ROOT . "/contrat/card.php?id=" . $contract->id . "'> " . $extrafields_contract->showOutputField('formule', $contract->array_options['options_formule']) . " - " . $contract->ref . "</a> ";
                    }
                }
            }
            if (count($to_print) > 0) {
                print '<h1 style="color:green;text-align:center;font-size: 4em;">Avec contrat : ' . implode(', ', $to_print) . '</h1>';
            } else {
                print '<h1 style="color:red;text-align:center;font-size: 4em;">Sans contrat</h1>';
            }
        }

        return 0;
    }

    /**
     * _block_page function
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    /*private function _block_page($parameters, &$object, &$action, $hookmanager) {
        global $conf;

        if (!$this->block_page_set) {
            // Force attach equipment
            if (!empty($conf->global->SYNERGIESTECH_FORCE_ATTACH_EQUIPMENTS_AFTER_SHIPPING_CREATED)) {
                if (preg_match('/\/equipement\/tabs\/expeditionAdd\.php/i', $_SERVER["PHP_SELF"])) {
                    dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                    $supplier_id = GETPOST('id', 'int');

                    if ($supplier_id > 0 && synergiestech_has_shipping_equipment_to_serialize($this->db, $supplier_id)) {
                        $this->_print_block_page('Veuillez terminer');
                    }
                } elseif (!preg_match('/\/equipement\/tabs\/expedition\.php/i', $_SERVER["PHP_SELF"]) &&
                    preg_match('/\/equipement\/tabs\/expedition\.php/i', $_SERVER["HTTP_REFERER"])
                ) {
                    dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                    $supplier_id = GETPOST('id', 'int');

                    if ($supplier_id > 0 && synergiestech_has_shipping_equipment_to_validate($this->db, $supplier_id)) {
                        $this->_print_block_page('Veuillez terminer');
                    }
                }
            } elseif (!empty($conf->global->SYNERGIESTECH_FORCE_SET_EQUIPMENTS_AFTER_ORDER_SUPPLIER_DISPATCH)) {
                if (preg_match('/\/equipement\/tabs\/supplier_order\.php$/i', $_SERVER["PHP_SELF"])) {
                    dol_include_once('/synergiestech/lib/synergiestech.lib.php');
                    $supplier_order_id = GETPOST('id', 'int');

                    if ($supplier_order_id > 0 && synergiestech_has_dispatching_equipment_to_serialize($this->db, $supplier_order_id)) {
                        $this->_print_block_page('Veuillez terminer');
                    }
                }
            }
        }

        return 0; // or return 1 to replace standard code
    }*/

    /**
     * Print js for blocking the page
     *
     * @param   string  $text   Message show when blocked
     * @return  void
     */
    /*private function _print_block_page($text) {
        $this->block_page_set = true;
        $text = str_replace('"', '\\"', $text);

        print <<<SCRIPT
<script type="text/javascript">
    $(document).ready(function() {
        window.onbeforeunload = function (event) {
            event.returnValue = "$text";
        }
    });
</script>
SCRIPT;
    }*/
}
