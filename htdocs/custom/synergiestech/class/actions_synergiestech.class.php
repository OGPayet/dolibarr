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
            }
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
            } elseif ($action == 'confirm_synergiestech_addline' && $confirm == 'yes' && $user->rights->commande->creer) {
                $object->context['synergiestech_addline_not_into_formula'] = $_SESSION['synergiestech_addline_formulas'];
                unset($_SESSION['synergiestech_addline_formulas']);
                $_GET = $_SESSION['synergiestech_addline_get'];
                unset($_SESSION['synergiestech_addline_get']);
                $_POST = $_SESSION['synergiestech_addline_post'];
                unset($_SESSION['synergiestech_addline_post']);
                $action = 'addline';
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
        $origin=GETPOST('origin','alpha');
        $originid=GETPOST('originid','int');

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
                    'sql' => "SELECT s.rowid as socid, s.nom as name, s.client, e.rowid, e.ref, p.ref AS ref_client FROM " . MAIN_DB_PREFIX . "societe as s" .
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
