<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/relationstierscontacts/class/html.formcompanyrelationships.class.php
 *  \ingroup    relationstierscontacts
 *	\brief      File of class with all html predefined components for company relationships
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

/**
 *	Class to manage generation of HTML components
 *	Only common components for company relationships must be here.
 *
 */
class FormCompanyRelationships
{
    public $db;
    public $error;
    public $num;

    /**
     * @var Form  Instance of the form
     */
    public $form;

    /**
     * @var FormDictionary  Instance of the form form dictionaries
     */
    public $formdictionary;



    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->form = new Form($this->db);
        $this->formdictionary = new FormDictionary($this->db);
    }


/**
 *  Move to the top the options of a select
 *
 * @param string  select_htmlname   ID of the select
 * @param array   values_list       List of values of options to move to the top
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

}
