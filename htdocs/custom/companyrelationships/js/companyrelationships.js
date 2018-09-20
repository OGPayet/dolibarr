/**
 * Open DSI
 * --------
 *
 * @Use JQuery
 *
 */

/*
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
*/