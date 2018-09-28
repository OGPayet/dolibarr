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
     * Return list of labels (translated)
     *
     * @param	string	$htmlname	Name of html select field ('myid' or '.myclass')
     * @param	array	$events		Event options. Example: array(array('action'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'done_action'=>array('disabled' => array('add-customer-contact'))))
     *                                  'url':          string,  Url of the Ajax script
     *                                  'action':       string,  Action name for the Ajax script
     *                                  'params':       array(), Others parameters send for Ajax script (exclude name: id, action, htmlname), if value = '{{selector}}' get value of the 'selector' input
     *                                  'htmlname':     string,  Id of the select updated with new options from Ajax script
     *                                  'done_action':  array(), List of action done when new options get successfully
     *                                      'empty_select': array(), List of html ID of select to empty
     *                                      'disabled'    : array(), List of html ID to disable if no options
     * @return  string
     */
    function add_select_events($htmlname, $events)
    {
        global $conf;

        $out = '';
        if (!empty($conf->use_javascript_ajax)) {
            $out .= '<script type="text/javascript">
            $(document).ready(function () {
                jQuery("select#'.$htmlname.'").change(function () {
                    var obj = '.json_encode($events).';
                    $.each(obj, function(key,values) {
                        if (values.action.length) {
                            runJsCodeForEvent'.$htmlname.'(values);
                        }
                    });
                });

                function runJsCodeForEvent'.$htmlname.'(obj) {
                    console.log("Run runJsCodeForEvent'.$htmlname.'");
                    var id = $("#'.$htmlname.'").val();
                    var action = obj.action;
                    var url = obj.url;
                    var urlsrc = obj.urlsrc;
                    var htmlname = obj.htmlname;
                    var datas = {
                        action: action,
                        id: id,
                        htmlname: htmlname,
                        urlsrc: urlsrc
                    };
                    var selector_regex = new RegExp("^\\{\\{(.*)\\}\\}$", "i");
                    $.each(obj.params, function(key, value) {
                        var match = null;
                        if ($.type(value) === "string") match = value.match(selector_regex);
                        if (match) {
                            datas[key] = $(match[1]).val();
                        } else {
                            datas[key] = value;
                        }
                    });
                    var input = $("#" + htmlname);
                    var inputautocomplete = $("#inputautocomplete"+htmlname);
                    $.getJSON(url, datas,
                        function(response) {
                            input.html(response.value);
                            if (response.num) {
                                var selecthtml_dom = $.parseHTML(response.value);
                                inputautocomplete.val(selecthtml_dom.innerHTML);
                            } else {
                                inputautocomplete.val("");
                            }

                            var num = response.num;
                            $.each(obj.done_action, function(key, action) {
                                switch (key) {
                                    case "empty_select":
                                        $.each(action, function(id) {
                                            $("select#" + id).html("");
                                        });
                                        break;
                                    case "disabled":
                                        $.each(action, function(id) {
                                            if (num > 0) {
                                                $("#" + id).removeAttr("disabled");
                                            } else {
                                                $("#" + id).attr("disabled", "disabled");
                                            }
                                        });
                                        break;
                                }
                            });

                            input.change();	/* Trigger event change */

                            if (response.num < 0) {
                                console.error(response.error);
                            }
                        }
                    );
                }
            });
            </script>';
        }

        return $out;
    }
}
