<?php

/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */



namespace CORE\FRAMEWORK;

dol_include_once('/core/class/html.form.class.php');


use \CORE\FRAMEWORK\Form as Form ;


class Form
	extends \Form{


	public $OSE_loaded_version = DOL_VERSION;

	public $OSE_loaded_path = '5.0';



    /**
     *	Return HTML to show the search and clear seach button
     *
     *  @param  string  $cssclass                  CSS class
     *  @param  int     $calljsfunction            0=default. 1=call function initCheckForSelect() after changing status of checkboxes
     *  @return	string
     */
    function showCheckAddButtons($cssclass='checkforaction', $calljsfunction=0)
    {
        global $conf, $langs;

        $out='';
        if (! empty($conf->use_javascript_ajax)) $out.='<div class="inline-block checkallactions"><input type="checkbox" id="checkallactions" name="checkallactions" class="checkallactions"></div>';
        $out.='<script type="text/javascript">
            $(document).ready(function() {
		$("#checkallactions").click(function() {
                    if($(this).is(\':checked\')){
                        console.log("We check all");
				$(".'.$cssclass.'").prop(\'checked\', true);
                    }
                    else
                    {
                        console.log("We uncheck all");
				$(".'.$cssclass.'").prop(\'checked\', false);
                    }'."\n";
        if ($calljsfunction) $out.='if (typeof initCheckForSelect == \'function\') { initCheckForSelect(); } else { console.log("No function initCheckForSelect found. Call won\'t be done."); }';
        $out.='         });
                });
            </script>';

        return $out;
    }


    /**
     *	Return HTML to show the search and clear seach button
     *
     *  @return	string
     */
    function showFilterButtons()
    {
        global $conf, $langs;

        $out='<div class="nowrap">';
        $out.='<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        $out.='<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
        $out.='</div>';

        return $out;
    }

}
