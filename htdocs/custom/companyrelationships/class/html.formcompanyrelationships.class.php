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
    function add_select_events_more_data($htmlname, $events)
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
                    var htmlname = obj.htmlname;
                    var datas = {
                        action: action,
                        id: id,
                        htmlname: htmlname
                    };
                    jQuery.each(obj.more_data, function(dataKey, dataValue) {
                        datas[dataKey] = dataValue;
                    });
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


    /**
     *     Show a confirmation HTML form or AJAX popup.
     *     Easiest way to use this is with useajax=1.
     *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
     *     just after calling this method. For example:
     *       print '<script type="text/javascript">'."\n";
     *       print 'jQuery(document).ready(function() {'."\n";
     *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
     *       print '});'."\n";
     *       print '</script>'."\n";
     *
     *     @param  	string		$page        	   	Url of page to call if confirmation is OK
     *     @param	string		$title       	   	Title
     *     @param	string		$question    	   	Question
     *     @param 	string		$action      	   	Action
     *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     * 	   @param  	string		$selectedchoice  	"" or "no" or "yes"
     * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
     *     @param  	int			$height          	Force height of box
     *     @param	int			$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
     *     @param	string      $formName           [=''] Name of form to keep values and to submit
     *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirm_socid($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=200, $width=500, $formName='')
    {
        global $langs,$conf;
        global $useglobalvars;

        $more='';
        $formconfirm='';
        $inputok=array();
        $inputko=array();

        // Clean parameters
        $newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
        if ($conf->browser->layout == 'phone') $width='95%';

        if (is_array($formquestion) && ! empty($formquestion))
        {
            // First add hidden fields and value
            foreach ($formquestion as $key => $input)
            {
                if (is_array($input) && ! empty($input))
                {
                    if ($input['type'] == 'hidden')
                    {
                        $more.='<input type="hidden" id="'.$input['name'].'" name="'.$input['name'].'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
                    }
                }
            }

            // Now add questions
            $more.='<table class="paddingtopbottomonly" width="100%">'."\n";
            $more.='<tr><td colspan="3">'.(! empty($formquestion['text'])?$formquestion['text']:'').'</td></tr>'."\n";
            foreach ($formquestion as $key => $input)
            {
                if (is_array($input) && ! empty($input))
                {
                    $size=(! empty($input['size'])?' size="'.$input['size'].'"':'');

                    if ($input['type'] == 'text')
                    {
                        $more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="text" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'" /></td></tr>'."\n";
                    }
                    else if ($input['type'] == 'password')
                    {
                        $more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="password" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'" /></td></tr>'."\n";
                    }
                    else if ($input['type'] == 'select')
                    {
                        $more.='<tr><td>';
                        if (! empty($input['label'])) $more.=$input['label'].'</td><td valign="top" colspan="2" align="left">';
                        $more.=$this->form->selectarray($input['name'],$input['values'],$input['default'],1);
                        $more.='</td></tr>'."\n";
                    }
                    else if ($input['type'] == 'checkbox')
                    {
                        $more.='<tr>';
                        $more.='<td>'.$input['label'].' </td><td align="left">';
                        $more.='<input type="checkbox" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"';
                        if (! is_bool($input['value']) && $input['value'] != 'false') $more.=' checked';
                        if (is_bool($input['value']) && $input['value']) $more.=' checked';
                        if (isset($input['disabled'])) $more.=' disabled';
                        $more.=' /></td>';
                        $more.='<td align="left">&nbsp;</td>';
                        $more.='</tr>'."\n";
                    }
                    else if ($input['type'] == 'radio')
                    {
                        $i=0;
                        foreach($input['values'] as $selkey => $selval)
                        {
                            $more.='<tr>';
                            if ($i==0) $more.='<td class="tdtop">'.$input['label'].'</td>';
                            else $more.='<td>&nbsp;</td>';
                            $more.='<td width="20"><input type="radio" class="flat" id="'.$input['name'].'" name="'.$input['name'].'" value="'.$selkey.'"';
                            if ($input['disabled']) $more.=' disabled';
                            $more.=' /></td>';
                            $more.='<td align="left">';
                            $more.=$selval;
                            $more.='</td></tr>'."\n";
                            $i++;
                        }
                    }
                    else if ($input['type'] == 'date')
                    {
                        $more.='<tr><td>'.$input['label'].'</td>';
                        $more.='<td colspan="2" align="left">';
                        $more.=$this->form->select_date($input['value'],$input['name'],0,0,0,'',1,0,1);
                        $more.='</td></tr>'."\n";
                        $formquestion[] = array('name'=>$input['name'].'day');
                        $formquestion[] = array('name'=>$input['name'].'month');
                        $formquestion[] = array('name'=>$input['name'].'year');
                        $formquestion[] = array('name'=>$input['name'].'hour');
                        $formquestion[] = array('name'=>$input['name'].'min');
                    }
                    else if ($input['type'] == 'other')
                    {
                        $more.='<tr><td>';
                        if (! empty($input['label'])) $more.=$input['label'].'</td><td colspan="2" align="left">';
                        $more.=$input['value'];
                        $more.='</td></tr>'."\n";
                    }
                    else if ($input['type'] == 'onecolumn')
                    {
                        $more.='<tr><td colspan="3" align="left">';
                        $more.=$input['value'];
                        $more.='</td></tr>'."\n";
                    }
                }
            }
            $more.='</table>'."\n";
        }

        // JQUI method dialog is broken with jmobile, we use standard HTML.
        // Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
        // See page product/card.php for example
        // ALWAYS USE AJAX
        //if (! empty($conf->dol_use_jmobile)) $useajax=0;
        //if (empty($conf->use_javascript_ajax)) $useajax=0;

        if ($useajax)
        {
            $autoOpen=true;
            $dialogconfirm='dialog-confirm-socid';
            $button='';
            if (! is_numeric($useajax))
            {
                $button=$useajax;
                $useajax=1;
                $autoOpen=false;
                $dialogconfirm.='-'.$button;
            }
            $pageyes=$page.(preg_match('/\?/',$page)?'&':'?').'action='.$action.'&confirm=yes';
            $pageno=($useajax == 2 ? $page.(preg_match('/\?/',$page)?'&':'?').'confirm=no':'');
            // Add input fields into list of fields to read during submit (inputok and inputko)
            if (is_array($formquestion))
            {
                foreach ($formquestion as $key => $input)
                {
                    //print "xx ".$key." rr ".is_array($input)."<br>\n";
                    if (is_array($input) && isset($input['name'])) {
                        // Modification Open-DSI - Begin
                        if (is_array($input['name'])) $inputok = array_merge($inputok, $input['name']);
                        else array_push($inputok,$input['name']);
                        // Modification Open-DSI - End
                    }
                    if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko,$input['name']);
                }
            }
            // Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
            $formconfirm.= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
            if (! empty($more)) {
                $formconfirm.= '<div class="confirmquestions">'.$more.'</div>';
            }
            $formconfirm.= ($question ? '<div class="confirmmessage">'.img_help('','').' '.$question . '</div>': '');
            $formconfirm.= '</div>'."\n";

            $formconfirm.= "\n<!-- begin ajax form_confirm page=".$page." -->\n";
            $formconfirm.= '<script type="text/javascript">'."\n";
            $formconfirm.= 'jQuery(document).ready(function() {
            $(function() {
		$( "#'.$dialogconfirm.'" ).dialog(
		{
                    autoOpen: '.($autoOpen ? "true" : "false").',';
            if ($newselectedchoice == 'no')
            {
                $formconfirm.='
						open: function() {
					$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
            }
            $formconfirm.='
                    resizable: false,
                    height: "'.$height.'",
                    width: "'.$width.'",
                    modal: true,
                    closeOnEscape: true,
                    close: function(event, ui) {
                        $("#'.$dialogconfirm.'").dialog("destroy");
                    },
                    buttons: {
                        "'.dol_escape_js($langs->transnoentities("Yes")).'": function() {
				var options="";
				var inputok = '.json_encode($inputok).';
				var pageyes = "'.dol_escape_js(! empty($pageyes)?$pageyes:'').'";
				var form_name = "' .  dol_escape_js($formName) . '";
				//var form = jQuery("form:first");
				var form = jQuery("form[name=\"" +  form_name + "\"]");

				if (inputok.length > 0) {
					$.each(inputok, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
					    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + inputvalue;

						if (form.length > 0) {
						    jQuery("<input type=\"hidden\" name=\"" + inputname + "\" value=\"" + inputvalue + "\" />\"").prependTo(form);
						}
					});
				}
				if (form.length > 0) {
				    jQuery("<input type=\"hidden\" name=\"confirm\" value=\"yes\" />").prependTo(form);
				}

				var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
					if (pageyes.length > 0) {
					    if (form.length > 0) {
                                    if (jQuery("#companyrelationships_socid").val() > 0) {
                                        jQuery("input[name=\"socid\"]").val(jQuery("#companyrelationships_socid").val());
                                        jQuery("#socid").val(jQuery("#companyrelationships_socid").val());
                                    }
                                    jQuery("input[name=\"action\"]").val("'.dol_escape_js($action).'");
                                    jQuery(form).submit();
					    } else {
					        //location.href = urljump;
					    }
					}
                            $(this).dialog("close");
                        },
                        "'.dol_escape_js($langs->transnoentities("No")).'": function() {
				var options = "";
				var inputko = '.json_encode($inputko).';
				var pageno="'.dol_escape_js(! empty($pageno)?$pageno:'').'";
				var form_name = "' .  dol_escape_js($formName) . '";
				//var form = jQuery("form:first");
				var form = jQuery("form[name=\"" +  form_name + "\"]");

				if (inputko.length>0) {
					$.each(inputko, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + inputvalue;

						if (form.length > 0) {
						    jQuery("<input type=\"hidden\" name=\"" + inputname + "\" value=\"" + inputvalue + "\" />\"").prependTo(form);
						}
					});
				}
				if (form.length > 0) {
				    jQuery("<input type=\"hidden\" name=\"confirm\" value=\"no\" />").prependTo(form);
				}

				var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					if (pageno.length > 0) {
					    if (form.length > 0) {
                                    jQuery("input[name=\"action\"]").val("'.dol_escape_js($action).'");
                                    jQuery(form).submit();
					    } else {
					        //location.href = urljump;
					    }
				    }
                            $(this).dialog("close");
                        }
                    }
                }
                );

		var button = "'.$button.'";
		if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#'.$dialogconfirm.'").dialog("open");
				});
                }
            });
            });
            </script>';
            $formconfirm.= "<!-- end ajax form_confirm -->\n";
        }
        else
        {
            $formconfirm.= "\n<!-- begin form_confirm page=".$page." -->\n";

            $formconfirm.= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";
            $formconfirm.= '<input type="hidden" name="action" value="'.$action.'">'."\n";
            $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

            $formconfirm.= '<table width="100%" class="valid">'."\n";

            // Line title
            $formconfirm.= '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>'."\n";

            // Line form fields
            if ($more)
            {
                $formconfirm.='<tr class="valid"><td class="valid" colspan="3">'."\n";
                $formconfirm.=$more;
                $formconfirm.='</td></tr>'."\n";
            }

            // Line with question
            $formconfirm.= '<tr class="valid">';
            $formconfirm.= '<td class="valid">'.$question.'</td>';
            $formconfirm.= '<td class="valid">';
            $formconfirm.= $this->form->selectyesno("confirm",$newselectedchoice);
            $formconfirm.= '</td>';
            $formconfirm.= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="'.$langs->trans("Validate").'"></td>';
            $formconfirm.= '</tr>'."\n";

            $formconfirm.= '</table>'."\n";

            $formconfirm.= "</form>\n";
            $formconfirm.= '<br>';

            $formconfirm.= "<!-- end form_confirm -->\n";
        }

        return $formconfirm;
    }

    /**
     * Generate HTML for search input in Ajax
     *
     * @param   string              $htmlname           Html name of search input to show
     * @param   int                 $socid              Id of main thirdparty
     * @param   int                 $relation_type      Relation type (benefactor, watcher)
     * @param   int                 $relation_socid     Id of thirparty in relation
     * @return  string
     *
     * @throws  Exception
     */
    function relation_select_search_autocompleter($htmlname, $socid, $relation_type, $relation_socid)
    {
        global $conf, $langs;

        dol_include_once('/companyrelationships/class/companyrelationships.class.php');

        $langs->load('companyrelationships@companyrelationships');

        $out = '';

        $select_search_action    = '';
        $select_search_script    = '';
        $select_search_showempty = 0;
        if ($relation_type == CompanyRelationships::RELATION_TYPE_BENEFACTOR) {
            $select_search_action    = 'getBenefactor';
            $select_search_script    = 'benefactor.php';
            $select_search_showempty = 0;
        } else if ($relation_type == CompanyRelationships::RELATION_TYPE_WATCHER) {
            $select_search_showempty = 1;
            $select_search_action    = 'getWatcher';
            $select_search_script    = 'watcher.php';
        }

        if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
            $hidelabel = 1;
            // No immediate load of all database
            $placeholder='';
            if ($relation_socid && empty($selected_input_value)) {
                $companyrelationships = new CompanyRelationships($this->db);
                $relation_ids = $companyrelationships->getRelationshipsThirdparty($socid, $relation_type,1);
                $relation_ids = is_array($relation_ids) ? $relation_ids : array();

                require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                $societetmp = new Societe($this->db);
                $societetmp->fetch($relation_socid);
                $selected_input_value = $societetmp->name;
                if (in_array($relation_socid, $relation_ids)) {
                    $selected_input_value .= ' *';
                }

                unset($societetmp);
            }
            // mode 1
            $urloption='htmlname='.$htmlname.'&outjson=1&socid='.$socid.'&relation_type='.$relation_type;
            $out .= ajax_autocompleter($relation_socid, $htmlname, dol_buildpath('/companyrelationships/ajax/relation2.php', 1), $urloption, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
            $out .= '<style type="text/css">
                            .ui-autocomplete {
                                z-index: 250;
                            }
                            </style>';
            if (empty($hidelabel)) print $langs->trans("RefOrLabel").' : ';
            else if ($hidelabel > 1) {
                if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $placeholder=' placeholder="'.$langs->trans("RefOrLabel").'"';
                else $placeholder=' title="'.$langs->trans("RefOrLabel").'"';
                if ($hidelabel == 2) {
                    $out .= img_picto($langs->trans("Search"), 'search');
                }
            }
            $out.=  '<input type="text" name="search_' . $htmlname . '" id="search_' . $htmlname . '" value="'.$selected_input_value.'"'.$placeholder.' '.(!empty($conf->global->THIRDPARTY_SEARCH_AUTOFOCUS) ? 'autofocus' : '').' />';
            if ($hidelabel == 3) {
                $out.= img_picto($langs->trans("Search"), 'search');
            }
            $out .= '<script type="text/javascript" language="javascript">';
            $out .= 'jQuery(document).ready(function(){';
            $out .= '   var cr_input = $("input#' . $htmlname . '");';
            $out .= '   var cr_input_search = $("input#search_' . $htmlname . '");';
            $out .= '   var cr_select = $("select#' . $htmlname . '");';
            $out .= '   var cr_select_form = cr_select.closest("form");';
            $out .= '   cr_input.detach().prependTo(cr_select_form);';
            $out .= '   cr_input_search.detach().prependTo(cr_select_form);';
            $out .= '   cr_select.remove();';
            $out .= '});';
            $out .= '</script>';
        } else {
            $out .= '<script type="text/javascript" language="javascript">';
            $out .= 'jQuery(document).ready(function(){';
            $out .= '   var data = {';
            $out .= '       action: "' . $select_search_action . '",';
            $out .= '       id: "' . $socid . '",';
            $out .= '       htmlname: "' . $htmlname . '",';
            $out .= '       relation_type: "' . $relation_type . '",';
            $out .= '       relation_socid: "' . $relation_socid . '",';
            $out .= '       showempty: ' . $select_search_showempty . '';
            $out .= '   };';
            $out .= '   jQuery.getJSON("' . dol_buildpath('/companyrelationships/ajax/' . $select_search_script, 1) . '", data,';
            $out .= '       function(response) {';
            $out .= '           jQuery("select#' . $htmlname . '").html(response.value);';
            $out .= '           jQuery("select#' . $htmlname . '").change();';
            $out .= '           if (response.num < 0) {';
            $out .= '               console.error(response.error);';
            $out .= '           }';
            $out .= '       }';
            $out .= '   );';
            $out .= '});';
            $out .= '</script>';

            if ($conf->use_javascript_ajax) {
                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                $comboenhancement = ajax_combobox($htmlname, array(), $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
                $out.= $comboenhancement;
            }
        }

        return $out;
    }
}
