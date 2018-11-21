/**
 * Open DSI
 * --------
 *
 * Class FactoryDispatcher
 *
 * @Use JQuery
 *
 */


class FactoryDispatcher {

    /**
     * Constructor of dispatcher
     *
     * @param	int		    idDispatcher		    Id of dispacther
     * @param	string	    dispatcherName		    Name of dispatcher
     * @param	string	    dispatcherMode		    [='select'] Mode of dispatcher ('select' to use select for qty)
     * @param   bool        dispatcherUnlockQty     [=false] Unlock qty
     * @param   string      dispatcherElementType   [=''] Element type (ex : 'equipement')
     * @param   string      dispatchElementData     Data object of the element to send (ex : '{"actionname" : "getAllEquipementInWarehouse", "htmlname_middle" : "equipementused_", "copyto_htmlname_middle" : "equipementlost_"}')
     */
    constructor(idDispatcher, dispatcherName, dispatcherMode, dispatcherUnlockQty, dispatcherElementType, dispatchElementData)
    {
        this.id   = idDispatcher;
        this.name = dispatcherName;

        if (dispatcherMode!==undefined) {
            this.mode = dispatcherMode;
        } else {
            this.mode = 'select';
        }

        if (dispatcherUnlockQty!==undefined) {
            this.unlock_qty = dispatcherUnlockQty;
        } else {
            this.unlock_qty = false;
        }

        if (dispatcherElementType!==undefined) {
            this.element_type = dispatcherElementType;
        } else {
            this.element_type = '';
        }

        if (dispatchElementData!==undefined) {
            this.element_data = dispatchElementData;
        } else {
            this.element_data = '';
        }
    }


    /**
     * Add line and dispatching quantities from a dispatcher
     *
     * @param	int		    idDispatcher		    Id of dispatcher
     * @param	string	    dispatcherName		    Name of dispatcher
     * @param	string	    dispatcherMode		    [='select'] Mode of dispatcher ('select' to use select for qty)
     * @param   bool        dispatcherUnlockQty     [=false] Unlock qty
     * @param   string      dispatcherElementType   [=''] Element type (ex : 'equipement')
     * @param   string      dispatchElementData     Data object of the element to send (ex : '{"actionname" : "getAllEquipementInWarehouse", "htmlname_middle" : "equipementused_", "copyto_htmlname_middle" : "equipementlost_"}')
     */
    static addLineFromDispatcher(idDispatcher, dispatcherName, dispatcherMode, dispatcherUnlockQty, dispatcherElementType, dispatchElementData)
    {
        var dispatcher = new FactoryDispatcher(idDispatcher, dispatcherName, dispatcherMode, dispatcherUnlockQty, dispatcherElementType, dispatchElementData);
        dispatcher.addLine();
    }


    /**
     * Add new row from table for dispatching (duplicate line and select the max quantity remaining to dispatch)
     */
    addLine()
    {
        var idDispatcher = this.id;
        var dispatcherName = this.name;
        var dispatchElementData = this.element_data;

        var dispatcherPrefix = '';

        if (dispatcherName) {
            dispatcherPrefix = dispatcherName + '_';
        }

        // nb lines dispatched
        var nbLine = jQuery("tr[name^='"+dispatcherPrefix+idDispatcher+"']").length;

        // dispatcher suffix name
        var dispatcherSuffix = idDispatcher+"_"+nbLine;

        // get the max value from first quantity selector of dispatcher
        var qtyToDispatch = 0;
        if (this.unlock_qty===false) {
            if (this.mode === 'select') {
                qtyToDispatch = jQuery("#"+dispatcherPrefix+"qty_"+idDispatcher+"_0 option:last").val();
            }
        }

        // retrieve the first dispatched line to copy
        var $row = jQuery("tr[name='"+dispatcherPrefix+idDispatcher+'_0'+"']").clone(true);

        // add all quantities already selected
        var qtyDispatched = 0;
        for (var i = 0; i < nbLine; i++)
        {
            qtyDispatched += parseInt(jQuery("#"+dispatcherPrefix+"qty_"+idDispatcher+"_"+i).val());
        }

        if (this.unlock_qty===true || qtyDispatched < qtyToDispatch)
        {
            // replace with new names
            var dispatcherRegex = new RegExp('_'+idDispatcher+'_0', 'g');
            $row.html($row.html().replace(dispatcherRegex, '_'+idDispatcher+'_'+nbLine));

            // remove action to duplicate line
            $row.find("td[name='"+dispatcherPrefix+"action_"+idDispatcher+"_"+nbLine+"']").html('');

            // change name of new row
            $row.attr('name', dispatcherPrefix+idDispatcher+'_'+nbLine);

            // insert new row after last row
            jQuery("tr[name='"+dispatcherPrefix+idDispatcher+"_"+(nbLine-1)+"']:last").after($row);

            jQuery("#"+dispatcherPrefix+"qty_"+dispatcherSuffix).focus();

            jQuery("#"+dispatcherPrefix+"qty_"+dispatcherSuffix).val(qtyToDispatch - qtyDispatched);

            // specific for equipement
            if (this.element_type == 'equipement') {

                // get all equipments in selected warehouse
                FactoryDispatcher.getAllEquipementInSelectedWarehouse(idDispatcher, dispatcherName, nbLine, dispatchElementData);

                // change event on warehouse
                jQuery("#"+dispatcherPrefix+"id_entrepot_"+dispatcherSuffix).change(function(){
                    // get all equipments in selected warehouse
                    FactoryDispatcher.getAllEquipementInSelectedWarehouse(idDispatcher, dispatcherName, nbLine, dispatchElementData);
                });
            }
        }
    }

    /**
     * Get all equipment in selected warehouse of dispatched line
     *
     * @param   int         idDispatcher                Id of dispatcher
     * @param   string      dispatcherName              Name of dispatcher
     * @param   int         numLine                     Num line of dispatcher
     * @param   string      dispatchElementData         Data object of the element to send
     */
    static getAllEquipementInSelectedWarehouse(idDispatcher, dispatcherName, numLine, dispatchElementData)
    {
        var dispatcherPrefix = '';

        if (dispatcherName) {
            dispatcherPrefix = dispatcherName + '_';
        }

        // dispatcher suffix name
        var dispatcherSuffix = idDispatcher+"_"+numLine;

        // default values
        var dispatchElementDataObj;
        if (dispatchElementData===undefined || dispatchElementData.length<=0) {
            dispatchElementDataObj = {
                actionname : "getAllEquipementInWarehouse",
                htmlname_middle : "equipementl_"
            };
        } else {
            // convert to object
            dispatchElementDataObj = JSON.parse(dispatchElementData);
        }

        var urlTo = jQuery("#url_to_get_all_equipement_in_warehouse").val();

        var data = {
            id: idDispatcher,
            action: dispatchElementDataObj.actionname,
            htmlname: dispatcherPrefix+dispatchElementDataObj.htmlname_middle+dispatcherSuffix,
            id_component_product: jQuery("#"+dispatcherPrefix+"id_component_product_"+dispatcherSuffix).val(),
            id_entrepot: jQuery("#"+dispatcherPrefix+"id_entrepot_"+dispatcherSuffix).val()
        };

        var input = jQuery("#"+dispatcherPrefix+dispatchElementDataObj.htmlname_middle+"multiselect_"+dispatcherSuffix);
        var inputCopyTo = '';
        if (dispatchElementDataObj.copyto_htmlname_middle!==undefined) {
            inputCopyTo = jQuery("#"+dispatcherPrefix+dispatchElementDataObj.copyto_htmlname_middle+"multiselect_"+dispatcherSuffix);
        }

        jQuery.getJSON(urlTo, data, function(response){
            input.html(response.value);
            //input.change();
            if (dispatchElementDataObj.copyto_htmlname_middle!==undefined) {
                var dispatcherEquipementRegex = new RegExp(dispatchElementDataObj.htmlname_middle, 'g');
                inputCopyTo.html(response.value.replace(dispatcherEquipementRegex, dispatchElementDataObj.copyto_htmlname_middle));
            }

            if (response.num < 0) {
                console.error(response.error);
            }
        });
    }
}