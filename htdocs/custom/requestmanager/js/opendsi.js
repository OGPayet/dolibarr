/*  Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/requestmanager/js/opendsi.js
 * \ingroup requestmanager
 * \brief   General functions.
 */

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

/**
 *  Select the options of a select
 *
 * @param string  select_htmlname   ID of the select
 * @param array   values_list       List of values of options to move to the top
 */
if (typeof set_select_options !== "function") {
  function set_select_options(select_htmlname, values_list) {
    $("#" + select_htmlname).val(values_list).change();
  }
}
