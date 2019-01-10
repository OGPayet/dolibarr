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
                label: jQuery('#label').val(),
                socid_origin: jQuery('#socid_origin').val(),
                socid: jQuery('#socid').val(),
                socid_benefactor: jQuery('#socid_benefactor').val(),
                socid_watcher: jQuery('#socid_watcher').val(),
                source: jQuery('#source').val(),
                type: jQuery('#type').val(),
                urgency: jQuery('#urgency').val(),
                notify_requester_by_email: jQuery('#notify_requester_by_email').val(),
                zone: idZone
            };

            ajaxData.description   = typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && "description" in CKEDITOR.instances ? CKEDITOR.instances["description"].getData() : jQuery('#description').val();
            ajaxData.contact_ids   = jQuery('#contact_ids').val();
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

/**
 *  Move to the top the options of a select
 *
 * @param string  select_htmlname   ID of the select
 * @param array   values_list       List of values of options to move to the top
 */
if (typeof move_top_select_options !== "function") {
  function move_top_select_options(select_htmlname, values_list) {
    var select = $("#" + select_htmlname);
    var select2 = $('#s2id_' + select_htmlname + ' span.select2-chosen');
    $.map(values_list, function (value, key) {
      var option = select.find("option[value='" + value + "']");
      var text = option.text();
      if (text.search(/\s\*$/) == -1) text += " *";
      option.text(text);
      option.detach().prependTo(select);
      if (select.val() == value && select2.length > 0) select2.text(text);
    });
  }
}
