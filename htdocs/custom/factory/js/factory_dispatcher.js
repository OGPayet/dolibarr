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
     * @param	int		idDispatcher		    Id of dispacther
     * @param	string	dispatcherName		    Name of dispatcher
     * @param	string	dispatcherMode		    [='select'] Mode of dispatcher ('select' to use select for qty)
     * @param   bool    dispatcherUnlockQty     [=false] Unlock qty
     */
    constructor(idDispatcher, dispatcherName, dispatcherMode, dispatcherUnlockQty)
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
    }


    /**
     * Add line and dispatching quantities from a dispatcher
     *
     * @param	int		idDispatcher		Id of dispatcher
     * @param	string	dispatcherName		Name of dispatcher
     * @param   bool    dispatcherUnlockQty     [=false] Unlock qty
     */
    static addLineFromDispatcher(idDispatcher, dispatcherName, dispatcherMode, dispatcherUnlockQty)
    {
        var dispatcher = new FactoryDispatcher(idDispatcher, dispatcherName, dispatcherMode, dispatcherUnlockQty);
        dispatcher.addLine();
    }


    /**
     * Add new row from table for dispatching (duplicate line and select the max quantity remaining to dispatch)
     */
    addLine()
    {
        var idDispatcher = this.id;
        var dispatcherName = this.name;
        var dispatcherPrefix = '';

        if (dispatcherName) {
            dispatcherPrefix = dispatcherName + '_';
        }

        // nb lines dispatched
        var nbLine = jQuery("tr[name^='"+dispatcherPrefix+idDispatcher+"']").length;

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

            // insert new row before last row
            jQuery("tr[name='"+dispatcherPrefix+idDispatcher+"_"+(nbLine-1)+"']:last").after($row);

            jQuery("#"+dispatcherPrefix+"qty_"+idDispatcher+"_"+nbLine).focus();

            jQuery("#"+dispatcherPrefix+"qty_"+idDispatcher+"_"+nbLine).val(qtyToDispatch - qtyDispatched);
        }

    }
}