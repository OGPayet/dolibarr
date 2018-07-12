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
     * @param	int		idDispatcher		Id of dispacther
     * @param	string	dispatcherName		Name of dispatcher
     */
    constructor(idDispatcher, dispatcherName)
    {
        this.id = idDispatcher;
        this.name = dispatcherName;
    }


    /**
     * Add line and dispatching quantities from a dispatcher
     *
     * @param	int		idDispatcher		Id of dispatcher
     * @param	string	dispatcherName		Name of dispatcher
     */
    static addLineFromDispatcher(idDispatcher, dispatcherName)
    {
        var dispatcher = new FactoryDispatcher(idDispatcher, dispatcherName);
        dispatcher.addLine();
    }


    /**
     * Add new row from table for dispatching (duplicate line and select the max quantity remaining to dispatch)
     */
    addLine()
    {
        var idDispatcher = this.id;
        var dispatcherName = this.name;

        // nb lines dispatched
        var nbLine = jQuery("tr[name^='"+dispatcherName+"_"+idDispatcher+"']").length;

        // get the max value from first quantity selector of dispatcher
        var qtyToDispatch = jQuery("#"+dispatcherName+"_qty_"+idDispatcher+"_0 option:last").val();

        // retrieve the last dispatched line to copy
        var $row = jQuery("tr[name='"+dispatcherName+'_'+idDispatcher+'_'+(nbLine-1)+"']").clone(true);

        // add all quantities already selected
        var qtyDispatched = 0;
        for (var i = 0; i < nbLine; i++)
        {
            qtyDispatched += parseInt(jQuery("#"+dispatcherName+"_qty_"+idDispatcher+"_"+i).val());
        }

        if (qtyDispatched < qtyToDispatch)
        {
            // replace with new names
            var dispatcherRegex = new RegExp('_'+idDispatcher+'_'+(nbLine-1), 'g');
            $row.html($row.html().replace(dispatcherRegex, '_'+idDispatcher+'_'+nbLine));

            // remove action to duplicate line
            $row.find("td[name='"+dispatcherName+"_action_"+idDispatcher+"_"+nbLine+"']").html('');

            // change name of new row
            $row.attr('name', dispatcherName+'_'+idDispatcher+'_'+nbLine);

            // insert new row before last row
            jQuery("tr[name='"+dispatcherName+"_"+idDispatcher+"_"+(nbLine-1)+"']:last").after($row);

            jQuery("#"+dispatcherName+"_qty_"+idDispatcher+"_"+nbLine).focus();

            jQuery("#"+dispatcherName+"_qty_"+idDispatcher+"_"+nbLine).val(qtyToDispatch - qtyDispatched);
        }

    }
}