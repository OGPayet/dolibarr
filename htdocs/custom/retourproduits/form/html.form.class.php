<?php
/* Copyright (c) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2006       Marc Barilley/Ocebo     <marc@ocebo.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Herve Prot              <herve.prot@symeos.com>
 * Copyright (C) 2012-2014  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
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
 *	\file       htdocs/core/class/html.form.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 *
 *  TODO Merge all function load_cache_* and loadCache* (except load_cache_vatrates) into one generic function loadCacheTable
 */
class FormRetourProduits extends Form
{
    var $db;
    var $error;


    /**
     * Constructor
     *
     * @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     *  \brief    Return list of services in contract
     *  \param      id_contrat
     *  \param      rowiddefault : Select a row
     */
    function select_return_products($id_order)
    {
        global $langs;

        $sql = "SELECT c.rowid, c.fk_soc, cd.fk_product, cd.rowid as cd_line, cd.qty as cd_qty,";
        $sql.= " exp.fk_entrepot, exp.qty as exp_qty, ";
        $sql.= " p.ref , p.label" ;
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as c, ";
        $sql.= MAIN_DB_PREFIX."product as p, ";
        $sql.= MAIN_DB_PREFIX."commandedet as cd, ";
        $sql.= MAIN_DB_PREFIX."expeditiondet as exp";
        $sql.= " WHERE c.rowid = cd.fk_commande";
        $sql.= " AND p.rowid = cd.fk_product";
        $sql.= " AND cd.rowid = exp.fk_origin_line";
        $sql.= " AND c.rowid = ".$id_order ;
        $result=$this->db->query($sql);
        $num = $this->db->num_rows($result);
        if($num){
            for($i=0;$i<$num;$i++){
                $resultat = $this->db->fetch_object($result);
                $out[]= array('product' => $resultat->ref . ' - ' . $resultat->label,
                               'cd_qty' => $resultat->cd_qty ,
                               'entrepot' => $resultat->fk_entrepot,
                               'expedition' => $resultat->fk_expedition ,
                               'produit_id' => $resultat->fk_product ,
                               'origin_line_id' => $resultat->cd_line ,
                               'fk_soc' => $resultat->fk_soc );
            }
        }
        else{
            $out= false;
        }

        // compléter le tableau avec numéro de série s'il y a
        if ($out) {
            $liste = array() ;
            foreach ($out as $key => $value) {
                $sql = "SELECT eq.rowid, eq.ref , eq.fk_entrepot , eq.quantity";
                $sql .=" FROM ".MAIN_DB_PREFIX."equipement as eq";
                $sql .=" WHERE eq.fk_soc_client = " .$value['fk_soc'] ;
                $sql.= " AND eq.fk_product = ".$value['produit_id'];
                $result=$this->db->query($sql);
                $num = $this->db->num_rows($result);
                if($num){
                    for($i=0;$i<$num;$i++){
                        $resultat = $this->db->fetch_object($result);
                        $liste[] = array('product' => $out[$key]['product'] .  ' / ' .$resultat->ref,
                                    'produit_id' =>$out[$key]['produit_id'] ,
                                    'origin_line_id'=> $out[$key]['origin_line_id'] ,
                                    'eq_id'=> $resultat->rowid ,
                                    'qty'=> $resultat->quantity ,
                                    'entrepot'=> $resultat->fk_entrepot );
                    }
                } else {
                    $liste[] = array('product' => $out[$key]['product'] ,
                                'eq_id'=> false ,
                                'qty'=> $out[$key]['cd_qty'],
                                'entrepot'=> $out[$key]['entrepot'],
                                'produit_id'=> $out[$key]['produit_id'],
                                'origin_line_id'=> $out[$key]['origin_line_id']);
                }
            }
        }
        return $liste;
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
     *     @param   string      $page               Url of page to call if confirmation is OK
     *     @param   string      $title              Title
     *     @param   string      $question           Question
     *     @param   string      $action             Action
     *     @param   array       $formquestion       An array with the liste of product/serial number/qty and wharehouse
     *     @param   string      $selectedchoice     "" or "no" or "yes"
     *     @param   int         $useajax            0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
     *     @param   int         $height             Force height of box
     *     @param   int         $width              Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
     *     @return  string                          HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=200, $width=500)
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

        $warehouse = new Entrepot($this->db);
        $warehouse_array = $warehouse->list_array();
        if (is_array($formquestion) && ! empty($formquestion))
        {
            // Now add questions
            $more.='<table class="paddingtopbottomonly" width="100%">'."\n";
            $more.='<tr><td colspan="3">'.(! empty($formquestion['text'])?$formquestion['text']:'').'</td></tr>'."\n";
            foreach ($formquestion as $key => $input)
            {
                $more.='<tr><td>';
                $more.='<input type="checkbox" class="flat" id="'.$key.'" name="'.$input['origin_line_id'].'" value="'.$input['origin_line_id'].'" /></td>';
                $more.='<td>'.$input['product'].' </td>';
                $more.='<td><input id="qty-'.$key.'" type="number" class="flat" value="'.$input['qty'].'" min="1" max="'.$input['qty'].'"/></td>';
                $more.='<td valign="top" colspan="2" align="left"><select id="wh-'.$key.'">';
                foreach ($warehouse_array as $k => $ware) {
                    $more.='<option ';
                    $more.= ($k==$input['entrepot'])?'selected ':'';
                    $more.='value="'.$k.'">'.$ware.'</option>';
                }
                $more.='</select></td>';
                $more.='<input type="hidden" id="pd-'.$key.'" value="'.$input['produit_id'].'"/>';
                $more.='<input type="hidden" id="line-'.$key.'" value="'.$input['origin_line_id'].'"/>';
                $more.='<input type="hidden" id="eq-'.$key.'" value="'.$input['eq_id'].'"/>';
                $more.='</tr>'."\n";
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
                    if (is_array($input) && isset($input['product'])) array_push($inputok,$input['product']);
                    if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko,$input['product']);
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
                                    //console.log("each "+i + " == " +inputname);
                                    var more = "";
                                    if ($("#" + i).is(":checked")) {
                                        var serievalue = $("#eq-" + i).val();
                                        options += "&serie[]=" + serievalue;
                                        var qtyvalue = $("#qty-"+i).val();
                                        options +="&qty[]=" + qtyvalue;
                                        var whvalue = $("#wh-"+i+" option:selected").val();
                                        options +="&wh[]="+whvalue;
                                        var pdvalue = $("#pd-"+i).val();
                                        options +="&pd[]="+pdvalue;
                                        var origin = $("#line-"+i).val();
                                        options +="&line[]="+origin;
                                    }
                                });
                            }
                            var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
                            //alert(options);
                            if (pageyes.length > 0) { location.href = urljump; }
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
                            var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
                            //alert(urljump);
                            if (pageno.length > 0) { location.href = urljump; }
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
