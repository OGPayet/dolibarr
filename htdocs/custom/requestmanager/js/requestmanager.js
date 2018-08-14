/**
 * Open DSI
 * --------
 *
 * Class RequestManagerLoader
 *
 * @Use JQuery
 *
 */


class RequestManagerLoader {

    /**
     * Constructor of RequestManagerLoader
     *
     */
    constructor(createdFirstZone, zoneName, ajaxUrl, ajaxData)
    {
        this.ajaxData         = ajaxData;
        this.ajaxMethod       = 'POST';
        this.ajaxUrl          = ajaxUrl;
        this.createdFirstZone = createdFirstZone;
        this.zoneName         = zoneName;
    }


    /**
     * Create zone for create fast request
     *
     * @param   int         idZone          Id of zone
     * @param   string      actionJs        Javascript action
     * @returns object      Ajax data
     */
    ajaxDataForCreateFastZone(idZone, actionJs)
    {
        var me = this;

        var ajaxData = {};

        if (me.createdFirstZone == 0) {
            ajaxData = me.ajaxData;
        } else if (me.createdFirstZone == 1) {
            ajaxData = {
                action_js: actionJs,
                actioncomm_id: jQuery('#actioncomm_id').val(),
                contactid: jQuery('#contactid').val(),
                description: jQuery('#description').val(),
                label: jQuery('#label').val(),
                socid: jQuery('#socid').val(),
                source: jQuery('#source').val(),
                type: jQuery('#type').val(),
                urgency: jQuery('#urgency').val(),
                zone: idZone
            };
            ajaxData.categories    = jQuery('#categories').val();
            ajaxData.equipement_id = jQuery('#equipement_id').val();
        }

        return ajaxData;
    }


    /**
     * Load automatically others zones after loading first zone
     *
     * @param   int         idZone      Id of zone
     * @param   string      actionJs    Javascript action
     */
    loadAutoForCreateFast(idZone, actionJs)
    {
        var me = this;
        me.createdFirstZone = 1;

        if (actionJs != '') {
            if (idZone == 1) {
                me.loadZone(2, actionJs);
            } else if (idZone == 2) {
                me.loadZone(3, actionJs);
            }
        }
    }


    /**
     * Load a specific zone
     *
     * @param   int         idZone      Id of zone
     * @param   string      actionJs    Javascript action
     */
    loadZone(idZone, actionJs)
    {
        var me = this;

        if (me.zoneName == 'create_fast_zone') {
            me.ajaxData = me.ajaxDataForCreateFastZone(idZone, actionJs);
        }

        jQuery.ajax({
            data: me.ajaxData,
            method: me.ajaxMethod,
            url: me.ajaxUrl,
            success: function(data) {
                jQuery('#' + me.zoneName + idZone).html(data);

                if (me.zoneName == 'create_fast_zone') {
                    me.loadAutoForCreateFast(idZone, actionJs);
                }
            },
            error: function() {
                jQuery('#' + me.zoneName + idZone).html('Error');
            }
        });
    }
}