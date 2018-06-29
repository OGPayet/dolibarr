<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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

        if (in_array('advancedticketcard', $contexts)) {
            if (!empty($conf->propal->enabled) && $user->rights->propal->creer) { // && $object->status == 1) {
                $langs->load("propal");
                print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/propal/card.php?originid=' . $object->id . '&origin=' . $object->element . '&socid=' . $object->socid . '&action=create">' . $langs->trans("AddProp") . '</a></div>';
            }

            if (!empty($conf->commande->enabled) && $user->rights->commande->creer) { // && $object->status == 1) {
                $langs->load("orders");
                print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/commande/card.php?originid=' . $object->id . '&origin=' . $object->element . '&socid=' . $object->socid . '&action=create">' . $langs->trans("AddOrder") . '</a></div>';
            }

            /*if ($user->rights->contrat->creer) { // && $object->status == 1) {
                $langs->load("contracts");
                print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?originid=' . $object->id . '&origin=' . $object->element . '&socid=' . $object->socid . '&action=create">' . $langs->trans("AddContract") . '</a></div>';
            }*/

            if (!empty($conf->ficheinter->enabled) && $user->rights->ficheinter->creer) { // && $object->status == 1) {
                $langs->load("fichinter");
                print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fichinter/card.php?originid=' . $object->id . '&origin=' . $object->element . '&socid=' . $object->socid . '&action=create">' . $langs->trans("AddIntervention") . '</a></div>';
            }

            // Add invoice
		if ($user->societe_id == 0)
		{
			if (! empty($conf->deplacement->enabled) && $object->status==1)
			{
				$langs->load("trips");
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/deplacement/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddTrip").'</a></div>';
			}

			if (! empty($conf->facture->enabled) && $object->status==1)
			{
				if (empty($user->rights->facture->creer))
				{
				    print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
				}
				else
				{
					$langs->load("bills");
					$langs->load("orders");

					if (! empty($conf->commande->enabled))
					{
					    if ($object->client != 0 && $object->client != 2)
					    {
						   if (! empty($orders2invoice) && $orders2invoice > 0) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$object->id.'">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
						   else print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("NoOrdersToInvoice")).'" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
					    }
					    else print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
					}

					if ($object->client != 0 && $object->client != 2) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a></div>';
					else print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a></div>';

				}
			}
		}
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

        $contexts = explode(':',$parameters['context']);

        if (in_array('invoicelist', $contexts)) {
            $langs->load('synergiestech@synergiestech');

            $enabled = (empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->creer)) ||
                (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->invoice_advance->validate));

            $this->resprints = '<option value="synergiestech_valid"' . (!$enabled ? ' disabled="disabled"' : '') . '>' .
                $langs->trans("ValidateBill") . '</option>';
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

        $contexts = explode(':',$parameters['context']);

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

        return 0; // or return 1 to replace standard code
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
