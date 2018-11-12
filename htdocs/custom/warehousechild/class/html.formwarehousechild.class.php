<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

dol_include_once('/warehousechild/class/html.formproduct.class.php');

class WarehouseschildForm extends Form
{
    public $predefinedSubdivide;

    public function __construct($db)
    {
        parent::__construct($db);

        $this->predefinedSubdivide   = array();
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
    }

    function displayForm()
    {

        print '<form method="POST">';


        print '<table class="border liste">';
        print '<thead><th>';
        print 'Utilisé ?';
        print '</th><th>';
        print 'Nom';
        print '</th><th>';
        print 'Abréviation';
        print '</th><th>';
        print 'Premier';
        print '</th><th>';
        print 'Quantité';
        print '</th><th>';
        print 'Réglages';
        print '</th><th>';
        print 'Séparateur de niveau';
        print '</th><th>';
        print 'Séparateur de nombre';
        print '</th></thead>';
        $i = 0;
        foreach ($this->predefinedSubdivide as $level) {
            print '<tr><td>';
            print '<input type="checkbox" class="center" name="used['.$i.']" value="1" '.((strlen($level['name'])>0)?'checked':'').' />';

            print '</td><td>';
            print '<input type="text" class="center" name="name['.$i.']" value="'.$level['name'].'" placeholder="..." />';

            print '</td><td>';
            print '<input type="text" class="center" name="abb['.$i.']" value="'.substr($level['name'], 0, 3).'" />';

            print '</td><td>';
            print '<input type="number" class="center" name="start['.$i.']" value="1" step="1" min="1" max="256"/>';

            print '</td><td>';
            print '<input type="number" class="center" name="qty['.$i.']" value="2" step="1" min="1" max="256"/>';

            print '</td><td>';
            print ' <label><input type="radio" name="setup['.$i.']" value="digit" checked /> chiffres</label>';
            print ' / <label><input type="radio" name="setup['.$i.']" value="letter" /> lettres</label>';

            print '</td><td align="center">';
            print '<input type="text" class="center" name="separator['.$i.']" value="" size="3" />';

            print '</td><td align="center">';
            print '<input type="text" class="center" name="separator2['.$i.']" value="" size="3" />';

            print '</td></tr>';
            $i++;
        }
        print '</table>';
        print '<br /><label><input type="radio" name="childwh" value="true" /> ';
        print '    Créer un entrepot pour chaque niveau';
        print '</label><br />';
        print 'ou';
        print '<br /><label><input type="radio" name="childwh" value="false"  checked/> ';
        print 'Créer un entrepot uniquement pour le dernier niveau en reprenant les noms des parents';
        print '</label><br/><br />';
        print '<input class="butAction" type="submit" value="Créer les entrepots enfants" />';
        print '</form>';
    }

    function productFavoriteWC($object)
    {
        $sql         = "SELECT fk_target FROM	".MAIN_DB_PREFIX."element_element WHERE	fk_source = $object->id AND sourcetype = 'product' AND targettype = 'stock'";
        //var_dump($sql);
        $resql       = $this->db->query($sql);
        print '<form method="POST">';
        print '<input name="action" type="hidden" value="addFav" />';
        print '<table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;"><tbody><tr><td class="nobordernopadding" valign="middle"><div class="titre">Entrepôts favoris</div></td>';
        $FormProduct = new CORE\WAREHOUSECHILD\FormProduct($this->db);
        print '<td class="nobordernopadding titre_right" align="right" valign="middle"><span style="width: 75%;display: inline-block;max-width: 400px;">';
        print $FormProduct->selectWarehouses('','fav','',0,0,$object->id);
        print '</span> <input type="submit" value="Ajouter" class="butAction">';
        print '</td>';
        print '</tr></tbody></table></form>';
        print '<div class="div-table-responsive-no-min">
    <table class="liste formdoc noborder" summary="listofdocumentstable" width="100%"><tbody>
    <tr class="liste_titre">
    <th colspan="5" class="formdoc liste_titre maxwidthonsmartphone" align="center">&nbsp;</th></tr>';
        if ($resql) {
            require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
            $staticEntrepot = new Entrepot($this->db);
            $num            = $this->db->num_rows($resql);
            $i              = 0;
            while ($i < $num) {
                $obj   = $this->db->fetch_object($resql);
                $fetch = $staticEntrepot->fetch($obj->fk_target);
                if ($fetch == 1) {//pas supprimé
                    print '<tr class="oddeven"><td colspan="2" class="">';
                    print $staticEntrepot->getNomUrl(1, '', 1);
                    print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?action=delFav&id='.$object->id.'&fav='.$obj->fk_target.'">';
                    print img_delete();
                    print    '</a></td></tr>';
                }
                $i++;
            }
        } else {
            print '<tr class="oddeven"><td colspan="3" class="opacitymedium">Aucun</td></tr>';
        }
        print '</tbody></table></div>';
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
     *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirm_submit($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=200, $width=500, $formNameToSubmit='')
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
                        $more.=$this->selectarray($input['name'],$input['values'],$input['default'],1);
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
                        $more.=$this->select_date($input['value'],$input['name'],0,0,0,'',1,0,1);
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
        if (! empty($conf->dol_use_jmobile)) $useajax=0;
        if (empty($conf->use_javascript_ajax)) $useajax=0;

        if ($useajax)
        {
            $autoOpen=true;
            $dialogconfirm='dialog-confirm';
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
                    closeOnEscape: false,
                    buttons: {
                        "'.dol_escape_js($langs->transnoentities("Yes")).'": function() {
				var options="";
				var inputok = '.json_encode($inputok).';
				var pageyes = "'.dol_escape_js(! empty($pageyes)?$pageyes:'').'";
				if (inputok.length>0) {
					$.each(inputok, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
					    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + inputvalue;
					});
				}
				//var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					//if (pageyes.length > 0) { location.href = urljump; }

					$("#' . $formNameToSubmit . '_confirm").val("yes");
					$("form[name=\"' . $formNameToSubmit . '\"]").submit();

                            $(this).dialog("close");
                        },
                        "'.dol_escape_js($langs->transnoentities("No")).'": function() {
				var options = "";
				var inputko = '.json_encode($inputko).';
				var pageno="'.dol_escape_js(! empty($pageno)?$pageno:'').'";
				if (inputko.length>0) {
					$.each(inputko, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + inputvalue;
					});
				}
				//var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					//if (pageno.length > 0) { location.href = urljump; }

					$("#' . $formNameToSubmit . '_confirm").val("no");

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
            $formconfirm.= $this->selectyesno("confirm",$newselectedchoice);
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
}