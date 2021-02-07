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
 *	\file       quicklist/class/html.formquicklist.class.php
 *  \ingroup    quicklist
 *	\brief      File of class with all html predefined components for request manager
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

/**
 *	Class to manage generation of HTML components
 *	Only common components for request manager must be here.
 *
 */
class FormQuickList
{
    public $db;
    public $error;
    public $num;

    /**
     * @var Form  Instance of the form
     */
    public $form;

    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->form = new Form($this->db);
    }

    /**
     *	Return multiselect list of groups
     *
     *  @param	array	$selected       List of ID group preselected
     *  @param  string	$htmlname       Field name in form
     *  @param  string	$exclude        Array list of groups id to exclude
     * 	@param	int		$disabled		If select list must be disabled
     *  @param  string	$include        Array list of groups id to include
     * 	@param	int		$enableonly		Array list of groups id to be enabled. All other must be disabled
     * 	@param	int		$force_entity	0 or Id of environment to force
     *  @return	string
     *  @see select_dolusers
     */
    function multiselect_dolgroups($selected=array(), $htmlname='groupid', $exclude='', $disabled=0, $include='', $enableonly=0, $force_entity=0)
    {
        global $conf;

        $out = '';

        $out .= $this->multiselect_javascript_code($selected, $htmlname);

        $save_conf = $conf->use_javascript_ajax;
        $conf->use_javascript_ajax = 0;
        $out .= $this->form->select_dolgroups('', $htmlname, 0, $exclude, $disabled, $include, $enableonly, $force_entity);
        $conf->use_javascript_ajax = $save_conf;

        return $out;
    }

    /**
     *	Return multiselect javascript code
     *
     *  @param	array	$selected       Preselected values
     *  @param  string	$htmlname       Field name in form
     *  @param	string	$elemtype		Type of element we show ('category', ...)
     *  @return	string
     */
    function multiselect_javascript_code($selected, $htmlname, $elemtype='')
    {
        global $conf;

        $out = '';

        // Add code for jquery to use multiselect
       	if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT'))
       	{
       		$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
      			$out.='<!-- JS CODE TO ENABLE '.$tmpplugin.' for id '.$htmlname.' -->
       			<script type="text/javascript">
   	    			function formatResult(record) {'."\n";
   						if ($elemtype == 'category')
   						{
   							$out.='	//return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> <a href="'.DOL_URL_ROOT.'/categories/viewcat.php?type=0&id=\'+record.id+\'">\'+record.text+\'</a></span>\';
   								  	return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> \'+record.text+\'</span>\';';
   						}
   						else
   						{
   							$out.='return record.text;';
   						}
   			$out.= '	};
       				function formatSelection(record) {'."\n";
   						if ($elemtype == 'category')
   						{
   							$out.='	//return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> <a href="'.DOL_URL_ROOT.'/categories/viewcat.php?type=0&id=\'+record.id+\'">\'+record.text+\'</a></span>\';
   								  	return \'<span><img src="'.DOL_URL_ROOT.'/theme/eldy/img/object_category.png'.'"> \'+record.text+\'</span>\';';
   						}
   						else
   						{
   							$out.='return record.text;';
   						}
   			$out.= '	};
   	    			$(document).ready(function () {
   	    			    $(\'#'.$htmlname.'\').attr("name", "'.$htmlname.'[]");
   	    			    $(\'#'.$htmlname.'\').attr("multiple", "multiple");
   	    			    //$.map('.json_encode($selected).', function(val, i) {
   	    			        $(\'#'.$htmlname.'\').val('.json_encode($selected).');
   	    			    //});
   	    			
       					$(\'#'.$htmlname.'\').'.$tmpplugin.'({
       						dir: \'ltr\',
   							// Specify format function for dropdown item
   							formatResult: formatResult,
       					 	templateResult: formatResult,		/* For 4.0 */
   							// Specify format function for selected item
   							formatSelection: formatSelection,
       					 	templateResult: formatSelection		/* For 4.0 */
       					});
       				});
       			</script>';
       	}

       	return $out;
    }
}

