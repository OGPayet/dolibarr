// Copyright (C) 2014 Cedric GROSS		<c.gross@kreiz-it.fr>
// Copyright (C) 2017 Francis Appels	<francis.appels@z-application.com>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// or see http://www.gnu.org/

//
// \file       htdocs/core/js/lib_dispatch.js
// \brief      File that include javascript functions used dispatch.php
//

/**
 * addDispatchLineWarehousechild
 * Adds new table row for dispatching to multiple stock locations
 *
 * @param	index		                int		index of product line. 0 = first product line
 * @param	type		                string	type of dispatch (batch = batch dispatch, dispatch = non batch dispatch)
 * @param	mode		                string	'qtymissing' will create new line with qty missing, 'lessone' will keep 1 in old line and the rest in new one
 * @param	unlock_qty	                bool	[=false] to lock qty, else true
 * @param	synergiestech_to_serialize	int     [=0] not a product to serialize, else 1
 */
function addDispatchLineWarehousechild(index, type, mode, unlock_qty, synergiestech_to_serialize)
{
    mode = mode || 'qtymissing'
    if (unlock_qty===undefined) unlock_qty = false;
    if (synergiestech_to_serialize===undefined) synergiestech_to_serialize = 0;

    console.log("Split line type="+type+" index="+index+" mode="+mode);
    var $row = $("tr[name='"+type+'_0_'+index+"']").clone(true), // clone first batch line to jQuery object
        nbrTrs = $("tr[name^='"+type+"_'][name$='_"+index+"']").length, // position of line for batch
        qtyOrdered = parseFloat($("#qty_ordered_0_"+index).val()), // Qty ordered is same for all rows
        qty = parseFloat($("#qty_"+(nbrTrs - 1)+"_"+index).val()),
        qtyDispatched;

    if (mode === 'lessone')
    {
        qtyDispatched = parseFloat($("#qty_dispatched_0_"+index).val()) + 1;
    }
    else
    {
        qtyDispatched = parseFloat($("#qty_dispatched_0_"+index).val()) + qty;
    }

    if ((unlock_qty===false && qtyDispatched < qtyOrdered) || unlock_qty===true)
    {
        //replace tr suffix nbr
        $row.html($row.html().replace(/_0_/g,"_"+nbrTrs+"_"));
        //create new select2 to avoid duplicate id of cloned one
        $row.find("select[name='"+'entrepot_'+nbrTrs+'_'+index+"']").select2();
        // TODO find solution to copy selected option to new select
        // TODO find solution to keep new tr's after page refresh
        //clear value
        $row.find("input[name^='qty']").val('');
        //change name of new row
        $row.attr('name',type+'_'+nbrTrs+'_'+index);
        //insert new row before last row
        $("tr[name^='"+type+"_'][name$='_"+index+"']:last").after($row);
        //remove cloned select2 with duplicate id.
        $("#s2id_entrepot_"+nbrTrs+'_'+index).detach();
        /*  Suffix of lines are:  _ trs.length _ index  */
        $("#qty_"+nbrTrs+"_"+index).focus();
        $("#qty_dispatched_0_"+index).val(qtyDispatched);

        //hide all buttons then show only the last one
        $("tr[name^='"+type+"_'][name$='_"+index+"'] .splitbutton").hide();
        $("tr[name^='"+type+"_'][name$='_"+index+"']:last .splitbutton").show();

        if (mode === 'lessone')
        {
            qty = 1; // keep 1 in old line
            $("#qty_"+(nbrTrs-1)+"_"+index).val(qty);
        }
        $("#qty_"+nbrTrs+"_"+index).val(qtyOrdered - qtyDispatched);
        // Store arbitrary data for dispatch qty input field change event
        $("#qty_"+(nbrTrs-1)+"_"+index).data('qty', qty);
        $("#qty_"+(nbrTrs-1)+"_"+index).data('type', type);
        $("#qty_"+(nbrTrs-1)+"_"+index).data('index', index);
        $("#qty_"+(nbrTrs-1)+"_"+index).data('mode', mode);

        if (synergiestech_to_serialize === 1) {
            addInputSerialFournWarehousechild(nbrTrs, index);
        }

        // Update dispatched qty when value dispatch qty input field changed
        $("#qty_"+(nbrTrs-1)+"_"+index).change(this.onChangeDispatchLineQtyWarehousechild(unlock_qty));

        if (synergiestech_to_serialize === 1) {
            // add change listener to qty
            $("#qty_" + nbrTrs + "_" + index).change(function() {
                addInputSerialFournWarehousechild(nbrTrs, index);
            });

            // add change listener to warehouse
            $("#entrepot" + nbrTrs + "_" + index).change(function() {
                addInputSerialFournWarehousechild(nbrTrs, index);
            });

            // add change listner to serial method
            $("#serialmethod_" + nbrTrs + "_" + index).change(function(){
                disableInputSerialFournWarehousechild(nbrTrs, index);
            });
        }

        //set focus on lot of new line (if it exists)
        $("#lot_number_"+(nbrTrs)+"_"+index).focus();
    }
}

/**
 * onChangeDispatchLineQty
 *
 * Change event handler for dispatch qty input field,
 * recalculate qty dispatched when qty input has changed.
 * If qty is more then qty ordered reset input qty to max qty to dispatch.
 *
 * element requires arbitrary data qty (value before change), type (type of dispatch) and index (index of product line)
 */

function onChangeDispatchLineQtyWarehousechild(unlock_qty) {
    if (unlock_qty===undefined) unlock_qty = false;

    var	index = $(this).data('index'),
        type = $(this).data('type'),
        qty = parseFloat($(this).data('qty')),
        qtyChanged, nbrTrs, qtyOrdered, qtyDispatched, qtyDispatching;

    console.log("onChangeDispatchLineQtyWarehousechild");

    if (unlock_qty===false && index>=0 && type && qty>=0) {
        nbrTrs = $("tr[name^='"+type+"_'][name$='_"+index+"']").length;
        qtyChanged = parseFloat($(this).val()) - qty; // qty changed
        qtyOrdered = parseFloat($("#qty_ordered_0_"+index).val()); // qty ordered
        qtyDispatched = parseFloat($("#qty_dispatched_0_"+index).val()); // qty already dispatched
        qtyDispatching = parseFloat($("#qty_"+(nbrTrs-1)+"_"+index).val()); // qty currently being dispatched

        console.log("onChangeDispatchLineQtyWarehousechild qtyChanged: " + qtyChanged + " qtyDispatching: " + qtyDispatching + " qtyOrdered: " + qtyOrdered + " qtyDispatched: "+ qtyDispatched);

        if ((qtyChanged) <= (qtyOrdered - (qtyDispatched + qtyDispatching))) {
            $("#qty_dispatched_0_"+index).val(qtyDispatched + qtyChanged);
        } else {
            $(this).val($(this).data('qty'));
        }
        $(this).data('qty', $(this).val());
    }
}


/**
 * Get list of equipments linked to this order supplier dispatch line
 *
 * @param   nbrTrs              int     Nb tours
 * @param	index               int     Index of product line, 0 = first product line
 * @param	idEquipementList    array   List of equipments id
 */
function getSupplierOrderDispatchEquipementWarehousechild(nbrTrs, index, idEquipementList)
{
    var dispatchSuffix = '_' + nbrTrs + '_' + index;
    var urlTo = $("#url_to_get_supplier_order_dispatch_equipement").val();
    var data = {
        action: 'getSupplierOrderDispatchEquipement',
        id: $("#fk_commande_fourn").val(),
        htmlname: 'serialfourn_remove' + dispatchSuffix,
        fk_product: $("#product" + dispatchSuffix).val(),
        fk_entrepot: $("#entrepot" + dispatchSuffix).val(),
        id_equipement_list: idEquipementList
    };
    var tdSerialFourn  = $("#td_serialfourn" + dispatchSuffix);

    $.getJSON(urlTo, data, function(response) {
        tdSerialFourn.html(response.value);
        if (response.num < 0) {
            console.error(response.error);
        }
    });
}


/**
 * Add input for serial numbers of equipements
 *
 * @param   nbrTrs  int     Nb tours
 * @param	index   int     Index of product line, 0 = first product line
 */
function addInputSerialFournWarehousechild(nbrTrs, index)
{
    var dispatchSuffix = '_' + nbrTrs + '_' + index;
    var tdSerialFourn  = $("#td_serialfourn" + dispatchSuffix);

    // remove all serial fourn
    tdSerialFourn.html('');

    // and add input serial fourn
    var qtyToDispatch = $("#qty" + dispatchSuffix).val();

    if (qtyToDispatch < 0) {
        $('#serialmethod' + dispatchSuffix).prop('disabled', true);
        $('#numversion' + dispatchSuffix).prop('disabled', true);

        this.getSupplierOrderDispatchEquipementWarehousechild(nbrTrs, index, []);
    } else if (qtyToDispatch == 0) {
        $('#serialmethod' + dispatchSuffix).prop('disabled', true);
        $('#numversion' + dispatchSuffix).prop('disabled', true);
    } else if (qtyToDispatch > 0) {
        var inputSerialFourn = '<input type="text" class="serialfourn' + dispatchSuffix + '" name="serialfourn' + dispatchSuffix + '[]" value="" /><br />';

        $('#serialmethod' + dispatchSuffix).prop('disabled', false);
        $('#numversion' + dispatchSuffix).prop('disabled', false);

        for (var numSerialFourn = 0; numSerialFourn < qtyToDispatch; numSerialFourn++) {
            tdSerialFourn.append(inputSerialFourn);
        }
    }
}


/**
 * Disable serial numbers if not external method
 *
 * @param   nbrTrs  int     Nb tours
 * @param	index   int     Index of product line, 0 = first product line
 */
function disableInputSerialFournWarehousechild(nbrTrs, index)
{
    var dispatchSuffix    = '_' + nbrTrs + '_' + index;
    var serialMethodValue = parseInt($("#serialmethod" + dispatchSuffix).val());

    if (serialMethodValue !== 2) {
        $(".serialfourn" + dispatchSuffix).prop("disabled", true);
        $(".serialfourn" + dispatchSuffix).val('');
    } else {
        $(".serialfourn" + dispatchSuffix).prop("disabled", false);
    }
}