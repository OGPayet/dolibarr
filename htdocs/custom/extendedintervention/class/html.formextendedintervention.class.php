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
 *	\file       extendedintervention/class/html.formextendedintervention.class.php
 *  \ingroup    extendedintervention
 *	\brief      File of class with all html predefined components for extended intervention
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

/**
 *	Class to manage generation of HTML components
 *	Only common components for extended intervention must be here.
 *
 */
class FormExtendedIntervention
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
     *	Return list of product categories
     *
     *	@return	array					List of product categories
     */
    function get_categories_array()
    {
        global $conf;

        include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $cat = new Categorie($this->db);
        $cate_arbo = $cat->get_full_arbo(Categorie::TYPE_PRODUCT);

        $list = array();
        foreach ($cate_arbo as $k => $cat) {
            if (((preg_match('/^'.$conf->global->EXTENDEDINTERVENTION_ROOT_PRODUCT_CATEGORIES.'$/', $cat['fullpath']) ||
                preg_match('/_'.$conf->global->EXTENDEDINTERVENTION_ROOT_PRODUCT_CATEGORIES.'$/', $cat['fullpath'])) && $conf->global->EXTENDEDINTERVENTION_ROOT_PRODUCT_CATEGORY_INCLUDE) ||
                preg_match('/^'.$conf->global->EXTENDEDINTERVENTION_ROOT_PRODUCT_CATEGORIES.'_/', $cat['fullpath']) ||
                preg_match('/_'.$conf->global->EXTENDEDINTERVENTION_ROOT_PRODUCT_CATEGORIES.'_/', $cat['fullpath'])) {
                $list[$cat['id']] = $cat['fulllabel'];
            }
        }

        return $list;
    }

    /**
     *	Return multiselect list of product categories
     *
     *	@param	string	$htmlname		Name of select
     *	@param	array	$selected		Array with key+value preselected
     *	@param	int		$key_in_label   1 pour afficher la key dans la valeur "[key] value"
     *	@param	int		$value_as_key   1 to use value as key
     *	@param  string	$morecss        Add more css style
     *	@param  int		$translate		Translate and encode value
     *  @param	int		$width			Force width of select box. May be used only when using jquery couch. Example: 250, 95%
     *  @param	string	$moreattrib		Add more options on select component. Example: 'disabled'
     *	@return	string					HTML multiselect string
     *  @see selectarray
     */
    function multiselect_categories($htmlname='categories', $selected=array(), $key_in_label=0, $value_as_key=0, $morecss='', $translate=0, $width=0, $moreattrib='')
    {
        $list = $this->get_categories_array();

        $out = $this->form->multiselectarray($htmlname, $list, $selected, $key_in_label, $value_as_key, $morecss, $translate, $width, $moreattrib, 'category');

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
     *     @param	int			$post				Send by form POST.
     *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirm($page, $title, $question, $action, $formquestion=array(), $selectedchoice="", $useajax=0, $height=200, $width=500, $post=0)
    {
        global $langs, $conf, $form;
        global $useglobalvars;

        if (!is_object($form)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            $form = new Form($this->db);
        }

        $more = '';
        $formconfirm = '';
        $inputok = array();
        $inputko = array();

        // Clean parameters
        $newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
        if ($conf->browser->layout == 'phone') $width = '95%';

        if (is_array($formquestion) && !empty($formquestion)) {
            if ($post) {
                $more .= '<form id="form_dialog_confirm" name="form_dialog_confirm" action="'.$page.'" method="POST" enctype="multipart/form-data">';
                $more .= '<input type="hidden" id="confirm" name="confirm" value="yes">' . "\n";
                $more .= '<input type="hidden" id="action" name="action" value="'.$action.'">' . "\n";
            }
            // First add hidden fields and value
            foreach ($formquestion as $key => $input) {
                if (is_array($input) && !empty($input)) {
                    if ($post && ($input['name'] == "confirm" || $input['name'] == "action")) continue;
                    if ($input['type'] == 'hidden') {
                        $more .= '<input type="hidden" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . dol_escape_htmltag($input['value'], 1, 1) . '">' . "\n";
                    }
                }
            }

            // Now add questions
            $more .= '<table class="paddingtopbottomonly" width="100%">' . "\n";
            $more .= '<tr><td colspan="3">' . (!empty($formquestion['text']) ? $formquestion['text'] : '') . '</td></tr>' . "\n";
            foreach ($formquestion as $key => $input) {
                if (is_array($input) && !empty($input)) {
                    $size = (!empty($input['size']) ? ' size="' . $input['size'] . '"' : '');

                    if ($input['type'] == 'text') {
                        $more .= '<tr><td>' . $input['label'] . '</td><td colspan="2" align="left"><input type="text" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
                    } else if ($input['type'] == 'password') {
                        $more .= '<tr><td>' . $input['label'] . '</td><td colspan="2" align="left"><input type="password" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
                    } else if ($input['type'] == 'select') {
                        $more .= '<tr><td>';
                        if (!empty($input['label'])) $more .= $input['label'] . '</td><td valign="top" colspan="2" align="left">';
                        $more .= $form->selectarray($input['name'], $input['values'], $input['default'], 1);
                        $more .= '</td></tr>' . "\n";
                    } else if ($input['type'] == 'checkbox') {
                        $more .= '<tr>';
                        $more .= '<td>' . $input['label'] . ' </td><td align="left">';
                        $more .= '<input type="checkbox" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"';
                        if (!is_bool($input['value']) && $input['value'] != 'false') $more .= ' checked';
                        if (is_bool($input['value']) && $input['value']) $more .= ' checked';
                        if (isset($input['disabled'])) $more .= ' disabled';
                        $more .= ' /></td>';
                        $more .= '<td align="left">&nbsp;</td>';
                        $more .= '</tr>' . "\n";
                    } else if ($input['type'] == 'radio') {
                        $i = 0;
                        foreach ($input['values'] as $selkey => $selval) {
                            $more .= '<tr>';
                            if ($i == 0) $more .= '<td class="tdtop">' . $input['label'] . '</td>';
                            else $more .= '<td>&nbsp;</td>';
                            $more .= '<td width="20"><input type="radio" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . $selkey . '"';
                            if ($input['disabled']) $more .= ' disabled';
                            $more .= ' /></td>';
                            $more .= '<td align="left">';
                            $more .= $selval;
                            $more .= '</td></tr>' . "\n";
                            $i++;
                        }
                    } else if ($input['type'] == 'date') {
                        $more .= '<tr><td>' . $input['label'] . '</td>';
                        $more .= '<td colspan="2" align="left">';
                        $more .= $form->select_date($input['value'], $input['name'], 0, 0, 0, '', 1, 0, 1);
                        $more .= '</td></tr>' . "\n";
                        $formquestion[] = array('name' => $input['name'] . 'day');
                        $formquestion[] = array('name' => $input['name'] . 'month');
                        $formquestion[] = array('name' => $input['name'] . 'year');
                        $formquestion[] = array('name' => $input['name'] . 'hour');
                        $formquestion[] = array('name' => $input['name'] . 'min');
                    } else if ($input['type'] == 'other') {
                        $more .= '<tr><td>';
                        if (!empty($input['label'])) $more .= $input['label'] . '</td><td colspan="2" align="left">';
                        $more .= $input['value'];
                        $more .= '</td></tr>' . "\n";
                    } else if ($input['type'] == 'onecolumn') {
                        $more .= '<tr><td colspan="3" align="left">';
                        $more .= $input['value'];
                        $more .= '</td></tr>' . "\n";
                    }
                }
            }
            $more .= '</table>' . "\n";
            if ($post) $more .= '</form>';
        }

        // JQUI method dialog is broken with jmobile, we use standard HTML.
        // Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
        // See page product/card.php for example
        if (!empty($conf->dol_use_jmobile)) $useajax = 0;
        if (empty($conf->use_javascript_ajax)) $useajax = 0;

        if ($useajax) {
            $autoOpen = true;
            $dialogconfirm = 'dialog-confirm';
            $button = '';
            if (!is_numeric($useajax)) {
                $button = $useajax;
                $useajax = 1;
                $autoOpen = false;
                $dialogconfirm .= '-' . $button;
            }
            $pageyes = $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . $action . '&confirm=yes';
            $pageno = ($useajax == 2 ? $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . $action . '&confirm=no' : '');
            // Add input fields into list of fields to read during submit (inputok and inputko)
            if (is_array($formquestion)) {
                foreach ($formquestion as $key => $input) {
                    //print "xx ".$key." rr ".is_array($input)."<br>\n";
                    if (is_array($input) && isset($input['name'])) {
                        // Modification Open-DSI - Begin
                        if (is_array($input['name'])) $inputok = array_merge($inputok, $input['name']);
                        else array_push($inputok, $input['name']);
                        // Modification Open-DSI - End
                    }
                    if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko, $input['name']);
                }
            }
            // Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
            $formconfirm .= '<div id="' . $dialogconfirm . '" title="' . dol_escape_htmltag($title) . '" style="display: none;">';
            if (!empty($more)) {
                $formconfirm .= '<div class="confirmquestions">' . $more . '</div>';
            }
            $formconfirm .= ($question ? '<div class="confirmmessage">' . img_help('', '') . ' ' . $question . '</div>' : '');
            $formconfirm .= '</div>' . "\n";

            $formconfirm .= "\n<!-- begin ajax form_confirm page=" . $page . " -->\n";
            $formconfirm .= '<script type="text/javascript">' . "\n";
            $formconfirm .= 'jQuery(document).ready(function() {
            $(function() {
		$( "#' . $dialogconfirm . '" ).dialog(
		{
                    autoOpen: ' . ($autoOpen ? "true" : "false") . ',';
            if ($newselectedchoice == 'no') {
                $formconfirm .= '
						open: function() {
					$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
            }
            if ($post) {
                $formconfirm .= '
                    resizable: false,
                    height: "' . $height . '",
                    width: "' . $width . '",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
                            var form_dialog_confirm = $("form#form_dialog_confirm");
                            form_dialog_confirm.find("input#confirm").val("yes");
                            form_dialog_confirm.submit();
                            $(this).dialog("close");
                        },
                        "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
                            if (' . ($useajax == 2 ? '1' : '0') . ' == 1) {
                                var form_dialog_confirm = $("form#form_dialog_confirm");
                                form_dialog_confirm.find("input#confirm").val("no");
                                form_dialog_confirm.submit();
                            }
                            $(this).dialog("close");
                        }
                    }
                }
                );

		var button = "' . $button . '";
		if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#' . $dialogconfirm . '").dialog("open");
				});
                }
            });
            });
            </script>';
            } else {
                $formconfirm .= '
                    resizable: false,
                    height: "' . $height . '",
                    width: "' . $width . '",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
				var options="";
				var inputok = ' . json_encode($inputok) . ';
				var pageyes = "' . dol_escape_js(!empty($pageyes) ? $pageyes : '') . '";
				if (inputok.length>0) {
					$.each(inputok, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
					    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + urlencode(inputvalue);
					});
				}
				var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					if (pageyes.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        },
                        "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
				var options = "";
				var inputko = ' . json_encode($inputko) . ';
				var pageno="' . dol_escape_js(!empty($pageno) ? $pageno : '') . '";
				if (inputko.length>0) {
					$.each(inputko, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + urlencode(inputvalue);
					});
				}
				var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					if (pageno.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        }
                    }
                }
                );

		var button = "' . $button . '";
		if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#' . $dialogconfirm . '").dialog("open");
				});
                }
            });
            });
            </script>';
            }
            $formconfirm .= "<!-- end ajax form_confirm -->\n";
        } else {
            $formconfirm .= "\n<!-- begin form_confirm page=" . $page . " -->\n";

            $formconfirm .= '<form method="POST" action="' . $page . '" class="notoptoleftroright">' . "\n";
            $formconfirm .= '<input type="hidden" name="action" value="' . $action . '">' . "\n";
            $formconfirm .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";

            $formconfirm .= '<table width="100%" class="valid">' . "\n";

            // Line title
            $formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="3">' . img_picto('', 'recent') . ' ' . $title . '</td></tr>' . "\n";

            // Line form fields
            if ($more) {
                $formconfirm .= '<tr class="valid"><td class="valid" colspan="3">' . "\n";
                $formconfirm .= $more;
                $formconfirm .= '</td></tr>' . "\n";
            }

            // Line with question
            $formconfirm .= '<tr class="valid">';
            $formconfirm .= '<td class="valid">' . $question . '</td>';
            $formconfirm .= '<td class="valid">';
            $formconfirm .= $form->selectyesno("confirm", $newselectedchoice);
            $formconfirm .= '</td>';
            $formconfirm .= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="' . $langs->trans("Validate") . '"></td>';
            $formconfirm .= '</tr>' . "\n";

            $formconfirm .= '</table>' . "\n";

            $formconfirm .= "</form>\n";
            $formconfirm .= '<br>';

            $formconfirm .= "<!-- end form_confirm -->\n";
        }

        return $formconfirm;
    }
}
