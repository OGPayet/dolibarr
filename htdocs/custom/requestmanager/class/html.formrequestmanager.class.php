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
 *	\file       requestmanager/class/html.formrequestmanager.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class with all html predefined components for request manager
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

/**
 *	Class to manage generation of HTML components
 *	Only common components for request manager must be here.
 *
 */
class FormRequestManager
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
     *  Output html form to select a request type for user groups
     *
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for request type.
     */
    function select_type($usergroups, $selected = '', $htmlname = 'type', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        global $langs;

        $out = $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerrequesttype', $selected, $htmlname, $showempty, 'rowid', '{{label}}', is_array($usergroups) ? array('user_group'=>$usergroups) : array(), array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
        if ($out < 0) {
            return $langs->trans('RequestManagerDontHaveRequestTypeForYoursGroup');
        }

        return $out;
    }

    /**
     *  Output html form to select a category for a request type
     *
     * @param   int         $request_type           Id of the request type
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for category.
     */
    function select_category($request_type, $selected = '', $htmlname = 'category', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        $categories_id = array();
        if ($request_type > 0) {
            // Get request type
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagerrequesttype');
            $dictionaryLine->fetch($request_type);
            $categories_id = explode(',', $dictionaryLine->fields['category']);
            unset($dictionaryLine);
        }

        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagercategory', $selected, $htmlname, $showempty, 'rowid', '{{label}}', array('rowid' => $categories_id), array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
    }

    /**
     *  Output html form to select a source
     *
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for source.
     */
    function select_source($selected = '', $htmlname = 'source', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagersource', $selected, $htmlname, $showempty, 'rowid', '{{label}}', array(), array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
    }

    /**
     *  Output html form to select a urgency
     *
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for urgency.
     */
    function select_urgency($selected = '', $htmlname = 'urgency', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerurgency', $selected, $htmlname, $showempty, 'rowid', '{{label}}', array(), array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
    }

    /**
     *  Output html form to select a impact
     *
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for impact.
     */
    function select_impact($selected = '', $htmlname = 'impact', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerimpact', $selected, $htmlname, $showempty, 'rowid', '{{label}}', array(), array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
    }

    /**
     *  Output html form to select a priority
     *
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for priority.
     */
    function select_priority($selected = '', $htmlname = 'priority', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerpriority', $selected, $htmlname, $showempty, 'rowid', '{{label}}', array(), array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
    }

    /**
     *  Output html form to select a status
     *
     * @param   int         $request_type           Id of the request type
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for status.
     */
    function select_status($request_type, $selected = '', $htmlname = 'status', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerstatus', $selected, $htmlname, $showempty, 'rowid', '{{label}}', array('request_type'=>array($request_type), 'type'=>array(RequestManager::STATUS_TYPE_IN_PROGRESS)), array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
    }

    /**
     *  Output html form to select a reason for resolution
     *
     * @param   int         $request_type           Id of the request type
     * @param   string      $selected               Preselected type
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   int         $limit                  Maximum number of elements
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   string      $selected_input_value   Value of preselected input text (for use with ajax)
     * @param   int         $hidelabel              Hide label (0=no, 1=yes, 2=show search icon (before) and placeholder, 3 search icon after)
     * @param   string      $selectlabel            Text of the label (can be translated)
     * @param   int         $autofocus              Autofocus the field in form (1 auto focus, 0 not)
     * @param   array       $ajaxoptions            Options for ajax_autocompleter
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for status.
     */
    function select_reason_resolution($request_type, $selected = '', $htmlname = 'reason_resolution', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        $reasons_resolutions = '';
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $statusDictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerstatus');
        $statusDictionary->fetch_lines(1, array('request_type'=>array($request_type), 'type'=>array(RequestManager::STATUS_TYPE_RESOLVED)));
        if (count($statusDictionary->lines) == 1) {
            $lines = array_values($statusDictionary->lines);
            $reasons_resolutions = $lines[0]->fields['reason_resolution'];
        }
        $reasons_resolutions = isset($reasons_resolutions) ? $reasons_resolutions : '';
        $reasons_resolutions = array_filter(array_map('trim', explode(',', $reasons_resolutions)), 'strlen');

        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerreasonresolution', $selected, $htmlname, $showempty, 'rowid', '{{label}}', count($reasons_resolutions) ? array('rowid' => $reasons_resolutions) : null, array('label'=>'ASC'), $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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
     *  @param	string	$elemtype		Type of element we show ('category', ...)
     *	@return	string					HTML multiselect string
     *  @see selectarray
     */
    function multiselect_categories($selected=array(), $htmlname='categories', $key_in_label=0, $value_as_key=0, $morecss='', $translate=0, $width=0, $moreattrib='',$elemtype='')
    {
        global $conf;

        include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $cat = new Categorie($this->db);
        $cate_arbo = $cat->get_full_arbo(Categorie::TYPE_PRODUCT);

        $list = array();
        foreach ($cate_arbo as $k => $cat) {
            if (((preg_match('/^'.$conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES.'$/', $cat['fullpath']) ||
                preg_match('/_'.$conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES.'$/', $cat['fullpath'])) && $conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORY_INCLUDE) ||
                preg_match('/^'.$conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES.'_/', $cat['fullpath']) ||
                preg_match('/_'.$conf->global->REQUESTMANAGER_ROOT_PRODUCT_CATEGORIES.'_/', $cat['fullpath'])) {
                $list[$cat['id']] = $cat['fulllabel'];
            }
        }

        $out = '';

        $out .= $this->form->multiselectarray($htmlname, $list, $selected, $key_in_label, $value_as_key, $morecss, $translate, $width, $moreattrib, $elemtype);

        return $out;
    }

    /**
     *	Return multiselect list of all contacts (for a third party or all)
     *
     *	@param	int		$socid      	Id ot third party or 0 for all
     *	@param  array	$selected   	List of ID contact pre-selectionne
     *	@param  string	$htmlname  	    Name of HTML field ('none' for a not editable field)
     *	@param  string	$exclude        List of contacts id to exclude
     *	@param	string	$limitto		Disable answers that are not id in this array list
     *	@param	integer	$showfunction   Add function into label
     *	@param	string	$moreclass		Add more class to class style
     *	@param	bool	$options_only	Return options only (for ajax treatment)
     *	@param	integer	$showsoc	    Add company into label
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *	@return	 int					<0 if KO, Nb of contact in list if OK
     */
    function multiselect_contacts($socid, $selected=array(), $htmlname='contactid', $exclude='', $limitto='', $showfunction=0, $moreclass='', $options_only=false, $showsoc=0, $forcecombo=0, $events=array())
    {
        global $conf;

        $out = '';

        $out .= $this->multiselect_javascript_code($selected, $htmlname);

        $save_conf = $conf->use_javascript_ajax;
        $conf->use_javascript_ajax = 0;
        $out .= $this->form->selectcontacts($socid,'',$htmlname,0,$exclude,$limitto,$showfunction, $moreclass, $options_only, $showsoc, $forcecombo, $events);
        $conf->use_javascript_ajax = $save_conf;

        return $out;
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
     *	Return multiselect list of users
     *
     *  @param	array|int	    $selected       List of user id or user object of user preselected. If -1, we use id of current user.
     *  @param  string	        $htmlname       Field name in form
     *  @param  array	        $exclude        Array list of users id to exclude
     * 	@param	int		        $disabled		If select list must be disabled
     *  @param  array|string	$include        Array list of users id to include or 'hierarchy' to have only supervised users or 'hierarchyme' to have supervised + me
     * 	@param	array	        $enableonly		Array list of users id to be enabled. All other must be disabled
     *  @param	int		        $force_entity	0 or Id of environment to force
     *  @param	int		        $maxlength		Maximum length of string into list (0=no limit)
     *  @param	int		        $showstatus		0=show user status only if status is disabled, 1=always show user status into label, -1=never show user status
     *  @param	string	        $morefilter		Add more filters into sql request
     *  @param	integer	        $show_every		0=default list, 1=add also a value "Everybody" at beginning of list
     *  @param	string	        $enableonlytext	If option $enableonlytext is set, we use this text to explain into label why record is disabled. Not used if enableonly is empty.
     *  @param	string	        $morecss		More css
     *  @param  int             $noactive       Show only active users (this will also happened whatever is this option if USER_HIDE_INACTIVE_IN_COMBOBOX is on).
     *  @param  int             $showexternal   Show external active users
     * 	@return	string					        HTML select string
     *  @see select_dolgroups
     */
    function multiselect_dolusers($selected=array(), $htmlname='userid', $exclude=null, $disabled=0, $include='', $enableonly=array(), $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $showexternal=1)
    {
        global $conf, $user;

        $out = '';

        $selected_values = array();
        if (is_array($selected)) {
            foreach ($selected as $u) {
                $selected_values[] = is_object($u) ? $u->id : $u;
            }
        } elseif ($selected == -1) {
            $selected_values[] = $user->id;
        }

        $out .= $this->multiselect_javascript_code($selected_values, $htmlname);

        $save_conf = $conf->use_javascript_ajax;
        $conf->use_javascript_ajax = 0;
        $out .= $this->form->select_dolusers('', $htmlname, 0, $exclude, $disabled, $include, $enableonly, $force_entity, $maxlength, $showstatus, $morefilter, $show_every, $enableonlytext, $morecss, $noactive, $showexternal);
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

    /**
     *  Show children request block.
     *
     *  @param	RequestManager	$object		      Object we want to show children request to
     *  @param  string          $morehtmlright    More html to show on right of title
     *  @return	int							      Nb children found
     */
    function showChildrenRequestBlock($object, $morehtmlright='')
    {
        global $conf, $langs, $hookmanager;
        global $bc;

        $object->fetch_children_request(null, 1);

        // Bypass the default method
        $hookmanager->initHooks(array('requestmanagerdao'));
        $parameters = array();
        $reshook = $hookmanager->executeHooks('showChildrenRequestBlock', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook

        if (empty($reshook)) {
            $nbchildren = count($object->children_request_ids);

            print '<br><!-- showChildrenRequestBlock -->';
            print load_fiche_titre($langs->trans('RequestManagerChildrenRequest'), $morehtmlright, '');

            print '<div class="div-table-responsive-no-min">';
            print '<table class="noborder allwidth">';

            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Ref") . '</td>';
            print '<td>' . $langs->trans("RequestManagerExternalReference") . '</td>';
            print '<td align="center">' . $langs->trans("Date") . '</td>';
            print '<td align="right">' . $langs->trans("Status") . '</td>';
            print '</tr>';

            global $childrenRequestBlock;
            global $stopParentID;
            $childrenRequestBlock = $object->children_request_list;
            $stopParentID = $object->id;

            // Output template part (modules that overwrite templates must declare this into descriptor)
            $dirtpls = array_merge($conf->modules_parts['tpl'], array('/requestmanager/tpl'));
            foreach ($dirtpls as $reldir) {
                $res = @include dol_buildpath($reldir . '/childrenrequestblock.tpl.php');
                if ($res) {
                    break;
                }
            }

            if (!$nbchildren) {
                print '<tr><td class="impair opacitymedium" colspan="7">' . $langs->trans("None") . '</td></tr>';
            }

            print '</table>';
            print '</div>';

            return $nbchildren;
        }

        return $reshook;
    }

    /**
     *  Output html form to select a actioncomm
     *
     * @param   int         $idActionComm           Id of the actioncomm
     * @param   array       $actionCommCodeList     [=array] List of actioncomm code
     * @param   string      $selected               Preselected actioncomm
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for status.
     */
    function select_actioncomm($idActionComm, $actionCommCodeList=array(), $selected='', $htmlname='actioncomm_id', $showempty=0, $forcecombo=0, $events=array(), $usesearchtoselect=0, $morecss='minwidth100', $moreparam='', $options_only=false)
    {
        global $conf, $langs, $user;

        $out = '';

        // search actioncomm
        $sql  = "SELECT";
        $sql .= " ac.id";
        $sql .= ", ac.label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as ac";
        $sql .= " WHERE ac.entity IN (" . getEntity('agenda') . ")";
        if (count($actionCommCodeList) > 0) {
            $sql .= " AND ac.code IN (";
            $sqlCodeIn = '';
            $i = 0;
            foreach($actionCommCodeList as $actionCommCode) {
                if ($i > 0) {
                    $sqlCodeIn .= ", ";
                }
                $sqlCodeIn .= "'" . $this->db->escape($actionCommCode) . "'";

                $i++;
            }
            $sql .= $sqlCodeIn;
            $sql .= ")";
        }
        $sql .= " AND ac.elementtype IS NULL";
        if ($idActionComm > 0) {
            $sql .= " AND ac.id = " . $idActionComm;
        }
        $sql .= " ORDER BY ac.fk_user_action = " . $user->id . ", ac.datep DESC";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only)
            {
                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                $comboenhancement = ajax_combobox($htmlname, $events, $usesearchtoselect);
                $out .= $comboenhancement;
            }

            if (!$options_only) $out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . '">';

            $textifempty = '';
            // Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
            //if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
            if (!empty($usesearchtoselect)) {
                if ($showempty && !is_numeric($showempty)) $textifempty = $langs->trans($showempty);
                else $textifempty .= $langs->trans("All");
            }
            if ($showempty) $out .= '<option value="-1">' . $textifempty . '</option>' . "\n";

            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    $out.= '<option value="' . $obj->id . '"';
                    if ($selected && $selected == $obj->id) $out .= ' selected';
                    $out .= '>';
                    $out .= $obj->label;
                    $out .= '</option>';
                    $i++;
                }
            }
            else
            {
                $out .= '<option value="-1" disabled>' . $langs->trans("RequestManagerNoActionComm") . '</option>';
            }

            if (!$options_only)
            {
                $out .= '</select>';
            }

            $this->num = $num;
            return $out;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Output html form to select an equipement
     *
     * @param   int         $fkSoc                  Id of company
     * @param   string      $selected               Preselected equipement
     * @param   string      $htmlname               Name of field in form
     * @param   string      $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for status.
     */
    function select_equipement($fkSoc, $selected='', $htmlname='equipement_id', $showempty=0, $forcecombo=0, $events=array(), $usesearchtoselect=0, $morecss='minwidth100', $moreparam='', $options_only=false)
    {
        global $conf, $langs;

        $out = '';

        dol_syslog(__METHOD__ . " fkSoc=" . $fkSoc,  LOG_DEBUG);

        // search equipement
        $requestManager = new RequestManager($this->db);
        $resql = $requestManager->findAllEquipemenByFkSoc($fkSoc);
        if ($resql)
        {
            if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only)
            {
                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                $comboenhancement = ajax_combobox($htmlname, $events, $usesearchtoselect);
                $out .= $comboenhancement;
            }

            if (!$options_only) $out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . '">';

            $textifempty = '';
            // Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
            //if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
            if (!empty($usesearchtoselect)) {
                if ($showempty && !is_numeric($showempty)) $textifempty = $langs->trans($showempty);
                else $textifempty .= $langs->trans("All");
            }
            if ($showempty) $out .= '<option value="-1">' . $textifempty . '</option>' . "\n";

            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    $out.= '<option value="' . $obj->rowid . '"';
                    if ($selected && $selected == $obj->rowid) $out .= ' selected';
                    $out .= '>';
                    $out .= $obj->ref;
                    $out .= '</option>';
                    $i++;
                }
            }
            else
            {
                $out .= '<option value="-1" disabled>' . $langs->trans("RequestManagerNoEquipement") . '</option>';
            }

            if ($options_only)
            {
                $out .= '</select>';
            }

            $this->num = $num;
            return $out;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Output html form to select an equipement
     *
     * @param   int         $fkSocPrincipal         Id of principal company
     * @param   int         $fkSocBenefactor        Id of benefactor company
     * @param   string      $selected               Preselected equipement
     * @param   string      $htmlname               Name of field in form
     * @param   int|string  $showempty              Add an empty field (Can be '1' or text key to use on empty line like 'SelectThirdParty')
     * @param   int         $forcecombo             Force to use combo box
     * @param   array       $events                 Ajax event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  	int		    $usesearchtoselect	    Minimum length of input string to start autocomplete
     * @param   string      $morecss                Add more css styles to the SELECT component
     * @param   string      $moreparam              Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
     * @param   bool        $options_only           Return options only (for ajax treatment)
     * @return  string                              HTML string with select box for status.
     */
    function select_benefactor_equipement($fkSocPrincipal, $fkSocBenefactor, $selected='', $htmlname='equipement_id', $showempty=0, $forcecombo=0, $events=array(), $usesearchtoselect=0, $morecss='minwidth100', $moreparam='', $options_only=false)
    {
        global $conf, $langs;

        $out = '';

        dol_syslog(__METHOD__ . " fkSocPrincipal=" . $fkSocPrincipal . ", fkSocBenefactor=" . $fkSocBenefactor, LOG_DEBUG);

        // search equipement
        $requestManager = new RequestManager($this->db);
        $resql = $requestManager->findAllBenefactorEquipments($fkSocPrincipal, $fkSocBenefactor);
        if ($resql) {
            if ($conf->use_javascript_ajax && !$forcecombo && !$options_only) {
                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                $comboenhancement = ajax_combobox($htmlname, $events, $usesearchtoselect);
                $out .= $comboenhancement;
            }

            if (!$options_only) $out .= '<select id="' . $htmlname . '" class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($moreparam ? ' ' . $moreparam : '') . ' name="' . $htmlname . '">';

            $textifempty = '';
            // Do not use textifempty = ' ' or '&nbsp;' here, or search on key will search on ' key'.
            //if (! empty($conf->use_javascript_ajax) || $forcecombo) $textifempty='';
            if (!empty($usesearchtoselect)) {
                if ($showempty && !is_numeric($showempty)) $textifempty = $langs->trans($showempty);
                else $textifempty .= $langs->trans("All");
            }
            if ($showempty) $out .= '<option value="-1">' . $textifempty . '</option>' . "\n";

            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num) {
                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);

                    $out .= '<option value="' . $obj->rowid . '"';
                    if ($selected && $selected == $obj->rowid) $out .= ' selected';
                    $out .= '>';
                    $out .= $obj->ref;
                    $out .= '</option>';
                    $i++;
                }
            } else {
                $out .= '<option value="-1" disabled>' . $langs->trans("RequestManagerNoEquipement") . '</option>';
            }

            if ($options_only) {
                $out .= '</select>';
            }

            $this->db->free($resql);

            $this->num = $num;
            return $out;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     * Print contact add form
     *
     * @param   RequestManager  $requestManager     Request manager object
     * @param   int             $idContactType      Id of contact type
     * @return  void            Print contact add form
     */
    function form_add_contact(RequestManager $requestManager, $idContactType)
    {
        global $conf, $langs, $user;

        $formCompany = NULL;
        if ($idContactType === RequestManager::CONTACT_TYPE_ID_WATCHER) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
            $formCompany = new FormCompany($this->db);
            $excludes_contact = $requestManager->watcher_ids;
        } else {
            $excludes_contact = $requestManager->requester_ids;
        }
        $newCompanyId = $requestManager->socid_origin;
        $contactTypeCodeHtmlName = RequestManager::getContactTypeCodeHtmlNameById($idContactType);

        // form to add requester contact
        print '<form name="form_add_contact_' . $contactTypeCodeHtmlName . '" action="' . $_SERVER["PHP_SELF"] . '" method="post">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="action" value="add_contact">';
        print '<input type="hidden" name="id" value="' . $requestManager->id . '">';
        print '<input type="hidden" name="add_contact_type_id" value="' . $idContactType . '">';
        print '<table class="nobordernopadding" width="100%">';
        print '<tr>';
        print '<td>';
        if ($formCompany !== NULL) {
            $selectCompaniesHtmlName = $contactTypeCodeHtmlName . '_newcompany';
            $newCompanyId = intval(GETPOST($selectCompaniesHtmlName, 'int')?GETPOST($selectCompaniesHtmlName, 'int'):$requestManager->socid);
            $formCompany->selectCompaniesForNewContact($requestManager,'id', $newCompanyId, $selectCompaniesHtmlName);
        }
        $this->form->select_contacts($newCompanyId, '', $contactTypeCodeHtmlName . '_fk_socpeople', 1, $excludes_contact);
        print '&nbsp;<input type="submit" class="button" value="' . $langs->trans('Add') . '">';

        // button create contact (only for requesters)
        if ($idContactType === RequestManager::CONTACT_TYPE_ID_REQUEST && $user->rights->societe->contact->creer)
        {
            $backToPage = $_SERVER["PHP_SELF"] . '?id=' . $requestManager->id;
            $btnCreateContactLabel = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
            $btnCreateContact = '<a class="addnewrecord" href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$requestManager->socid.'&amp;action=create&amp;backtopage=' . urlencode($backToPage) . '">' . $btnCreateContactLabel;
            if (empty($conf->dol_optimize_smallscreen)) $btnCreateContact .= ' ' . img_picto($btnCreateContactLabel, 'filenew');
            $btnCreateContact .= '</a>'."\n";
            print '&nbsp;&nbsp;' . $btnCreateContact;
        }

        print '</td>';
        print '</tr>';
        print '</table>';
        print '</form>';
    }


    /**
     * 	Render list of categories linked to object with id $id and type $type
     *
     * 	@param		int		$id				Id of object
     * 	@param		string	$type			Type of category ('member', 'customer', 'supplier', 'product', 'contact', 'requestmanager')
     *  @param		int		$rendermode		0=Default, use multiselect. 1=Emulate multiselect (recommended)
     *  @param		int		$editMode		[=FALSE] for view mode, TRUE for edit mode (with rendermode=0 only)
     * 	@return		string					String with categories
     */
    function showCategories($id, $type, $rendermode=0, $editMode=FALSE)
    {
        global $db;

        dol_include_once('/requestmanager/class/categorierequestmanager.class.php');

        $cat = new CategorieRequestManager($db);
        $categories = $cat->containing($id, $type);

        if ($rendermode == 1)
        {
            $toprint = array();
            foreach($categories as $c)
            {
                $ways = $c->print_all_ways();       // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
                foreach($ways as $way)
                {
                    $toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.($c->color?' style="background: #'.$c->color.';"':' style="background: #aaa"').'>'.img_object('','category').' '.$way.'</li>';
                }
            }
            return '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
        }

        if ($rendermode == 0)
        {
            $arrayselected = array();
            foreach($categories as $c) {
                $arrayselected[] = $c->id;
            }

            $selectMoreAttrib = 'disabled';
            $selectElementType = 'category';
            if ($editMode === TRUE) {
                $selectMoreAttrib = '';
                $selectElementType = '';
            }

            return $this->multiselect_categories($arrayselected,'categories', '', 0, '', 0, '100%', $selectMoreAttrib, $selectElementType);
        }

        return 'ErrorBadValueForParameterRenderMode';	// Should not happened
    }


    /**
     * Prepare SQL request for lists to follow
     *
     * @param   DoliDB  $db             Doli DB object
     * @param   string  $join           [=''] Join condition in SQL
     * @param   string  $filter         [=''] Filter condition where in SQL
     * @param   string  $sortfield      [=''] List of sort fields, separated by comma. Example: 't1.fielda, t2.fieldb'
     * @param	string  $sortorder      [=''] List of sort order seprated by comma ('ASC'|'DESC')
     * @return  string  SQL request
     */
    private static function _listsFollowSqlPrepare(DoliDB $db, $join='', $filter='', $sortfield='', $sortorder='')
    {
        $sql = 'SELECT DISTINCT';
        $sql .= ' rm.rowid, rm.ref, rm.ref_ext, rm.fk_parent,';
        $sql .= ' rm.fk_soc_origin, so.nom as soc_name_origin, so.client as soc_client_origin, so.fournisseur as soc_fournisseur_origin, so.code_client as soc_code_client_origin, so.code_fournisseur as soc_code_fournisseur_origin,';
        $sql .= ' rm.fk_soc, s.nom as soc_name, s.client as soc_client, s.fournisseur as soc_fournisseur, s.code_client as soc_code_client, s.code_fournisseur as soc_code_fournisseur,';
        $sql .= ' rm.fk_soc_benefactor, sb.nom as soc_name_benefactor, sb.client as soc_client_benefactor, sb.fournisseur as soc_fournisseur_benefactor, sb.code_client as soc_code_client_benefactor, sb.code_fournisseur as soc_code_fournisseur_benefactor,';
        $sql .= ' rm.label, rm.description,';
        $sql .= ' rm.fk_type, crmrt.label as type_label,';
        $sql .= ' rm.fk_category, crmc.label as category_label,';
        $sql .= ' rm.fk_source, crms.label as source_label,';
        $sql .= ' rm.fk_urgency, crmu.label as urgency_label,';
        $sql .= ' rm.fk_impact, crmi.label as impact_label,';
        $sql .= ' rm.fk_priority, crmp.label as priority_label,';
        $sql .= ' rm.notify_requester_by_email, rm.notify_watcher_by_email, rm.notify_assigned_by_email,';
        $sql .= ' GROUP_CONCAT(DISTINCT rmau.fk_user SEPARATOR \',\') as assigned_users,';
        $sql .= ' GROUP_CONCAT(DISTINCT rmaug.fk_usergroup SEPARATOR \',\') as assigned_usergroups,';
        $sql .= ' rm.duration, rm.date_operation, rm.date_deadline, rm.date_resolved, rm.date_closed,';
        $sql .= ' rm.fk_user_resolved, ur.firstname as userresolvedfirstname, ur.lastname as userresolvedlastname, ur.email as userresolvedemail,';
        $sql .= ' rm.fk_user_closed, uc.firstname as userclosedfirstname, uc.lastname as userclosedlastname, uc.email as userclosedemail,';
        $sql .= ' rm.fk_status,';
        $sql .= ' rm.datec, rm.tms,';
        $sql .= ' rm.fk_user_author, ua.firstname as userauthorfirstname, ua.lastname as userauthorlastname, ua.email as userauthoremail,';
        $sql .= ' rm.fk_user_modif, um.firstname as usermodiffirstname, um.lastname as usermodiflastname, um.email as usermodifemail';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_request_type as crmrt on (crmrt.rowid = rm.fk_type)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_category as crmc on (crmc.rowid = rm.fk_category)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_source as crms on (crms.rowid = rm.fk_source)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_urgency as crmu on (crmu.rowid = rm.fk_urgency)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_impact as crmi on (crmi.rowid = rm.fk_impact)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_priority as crmp on (crmp.rowid = rm.fk_priority)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_requestmanager_status as crmst on (crmst.rowid = rm.fk_status)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as so on (so.rowid = rm.fk_soc_origin)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s on (s.rowid = rm.fk_soc)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as sb on (sb.rowid = rm.fk_soc_benefactor)';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ur ON ur.rowid = rm.fk_user_resolved';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as uc ON uc.rowid = rm.fk_user_closed';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ua ON ua.rowid = rm.fk_user_author';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as um ON um.rowid = rm.fk_user_modif';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid';
        $sql .= $join;
        $sql .= ' WHERE rm.entity IN (' . getEntity('requestmanager') . ')';
        $sql .= $filter;
        $sql .= ' GROUP BY rm.rowid';
        $sql .= $db->order($sortfield, $sortorder);

        return $sql;
    }


    /**
     * Print a line for lists to follow
     *
     * @param   DoliDB          $db                         Doli DB object
     * @param   array           $arrayfields                Array of fields to show
     * @param   stdClass        $obj                        Standard object from db
     * @param   RequestManager  $requestmanagerstatic       RequestManager object
     * @param   Societe         $societestatic_origin       Societe origin object
     * @param   Societe         $societestatic              Societe bill object
     * @param   Societe         $societestatic_benefactor   Societe benefactor object
     * @param   User            $userstatic                 User object
     * @param   int             $lists_follow_last_date     Last date when follow list is viewed
     */
    private static function _listsFollowPrintLineFrom(DoliDB $db, $arrayfields, $obj, RequestManager $requestmanagerstatic, Societe $societestatic_origin, Societe $societestatic, Societe $societestatic_benefactor, User $userstatic, $lists_follow_last_date=0)
    {
        global $langs, $conf, $form;

        require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
        $users_cache = array();
        $usergroups_cache = array();

        $now = dol_now();

        // societe origin
        $societestatic_origin->id = $obj->fk_soc_origin;
        $societestatic_origin->name = $obj->soc_name_origin;
        $societestatic_origin->client = $obj->soc_client_origin;
        $societestatic_origin->fournisseur = $obj->soc_fournisseur_origin;
        $societestatic_origin->code_client = $obj->soc_code_client_origin;
        $societestatic_origin->code_fournisseur = $obj->soc_code_fournisseur_origin;

        // societe bill
        $societestatic->id               = $obj->fk_soc;
        $societestatic->name             = $obj->soc_name;
        $societestatic->client           = $obj->soc_client;
        $societestatic->fournisseur      = $obj->soc_fournisseur;
        $societestatic->code_client      = $obj->soc_code_client;
        $societestatic->code_fournisseur = $obj->soc_code_fournisseur;

        // societe benefactor
        $societestatic_benefactor->id = $obj->fk_soc_benefactor;
        $societestatic_benefactor->name = $obj->soc_name_benefactor;
        $societestatic_benefactor->client = $obj->soc_client_benefactor;
        $societestatic_benefactor->fournisseur = $obj->soc_fournisseur_benefactor;
        $societestatic_benefactor->code_client = $obj->soc_code_client_benefactor;
        $societestatic_benefactor->code_fournisseur = $obj->soc_code_fournisseur_benefactor;

        // requestmanager
        $requestmanagerstatic->id = $obj->rowid;
        $requestmanagerstatic->ref = $obj->ref;
        $requestmanagerstatic->ref_ext = $obj->ref_ext;
        $requestmanagerstatic->fk_parent = $obj->fk_parent;
        $requestmanagerstatic->fk_type = $obj->fk_type;
        $requestmanagerstatic->label = $obj->label;
        $requestmanagerstatic->socid = $obj->fk_soc;
        $requestmanagerstatic->thirdparty_origin = $societestatic_origin;
        $requestmanagerstatic->thirdparty = $societestatic;
        $requestmanagerstatic->thirdparty_benefactor = $societestatic_benefactor;

        // picto warning for deadline
        $pictoWarning = '';
        if ($obj->date_deadline) {
            $tmsDeadLine = strtotime($obj->date_deadline);
            if ($tmsDeadLine < $now) {
                // alert time is up
                $pictoWarning = img_warning($langs->trans("Late"));
            }
        }

        $updatedLineClass = "";
        if ($lists_follow_last_date > 0 && $lists_follow_last_date <= $db->jdate($obj->tms)) {
            $updatedLineClass = " rm_my_request_updated_line_color";
        }

        print '<tr class="oddeven">';

        // Ref
        if (!empty($arrayfields['rm.ref']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $requestmanagerstatic->getNomUrl(1, 'parent_path', 24) . ' ' . $pictoWarning;
            print '</td>';
        }

        //External Ref
        if (!empty($arrayfields['rm.ref_ext']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $obj->ref_ext;
            print '</td>';
        }

        // Type
        if (!empty($arrayfields['rm.fk_type']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $obj->type_label;
            print '</td>';
        }

        // Category
        if (!empty($arrayfields['rm.fk_category']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $obj->category_label;
            print '</td>';
        }

        // Label
        if (!empty($arrayfields['rm.label']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            $toprint = dol_trunc($obj->label, 24);
            if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                $toprint = $form->textwithtooltip($toprint, $obj->label);
            }
            print $toprint;
            print '</td>';
        }

        // Thridparty
        if (!empty($arrayfields['rm.fk_soc_origin']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $societestatic_origin->getNomUrl(1, '', 24);
            print '</td>';
        }

        // Thridparty
        if (!empty($arrayfields['rm.fk_soc']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $societestatic->getNomUrl(1, '', 24);
            print '</td>';
        }

        // Thridparty
        if (!empty($arrayfields['rm.fk_soc_benefactor']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $societestatic_benefactor->getNomUrl(1, '', 24);
            print '</td>';
        }

        // Description
        if (!empty($arrayfields['rm.description']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            $toprint = dol_trunc($obj->description, 24);
            if (empty($conf->global->MAIN_DISABLE_TRUNC)) {
                $toprint = $form->textwithtooltip($toprint, $obj->description);
            }
            print $toprint;
            print '</td>';
        }

        // Source
        if (!empty($arrayfields['rm.fk_source']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $obj->source_label;
            print '</td>';
        }

        // Urgency
        if (!empty($arrayfields['rm.fk_urgency']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $obj->urgency_label;
            print '</td>';
        }

        // Impact
        if (!empty($arrayfields['rm.fk_impact']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $obj->impact_label;
            print '</td>';
        }

        // Priority
        if (!empty($arrayfields['rm.fk_priority']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            print $obj->priority_label;
            print '</td>';
        }

        // Duration
        if (!empty($arrayfields['rm.duration']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            if ($obj->duration > 0) print requestmanager_print_duration($obj->duration);
            print '</td>';
        }

        // Date Operation
        if (!empty($arrayfields['rm.date_operation']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'" align="center">';
            if ($obj->date_operation > 0) print dol_print_date($db->jdate($obj->date_operation), 'dayhour');
            print '</td>';
        }

        // Date Deadline
        if (!empty($arrayfields['rm.date_deadline']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'" align="center">';
            if ($obj->date_deadline > 0) print dol_print_date($db->jdate($obj->date_deadline), 'dayhour');
            print '</td>';
        }

        // Notification requesters
        if (!empty($arrayfields['rm.notify_requester_by_email']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'" align="center">';
            print yn($obj->notify_requester_by_email);
            print '</td>';
        }

        // Notification watchers
        if (!empty($arrayfields['rm.notify_watcher_by_email']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'" align="center">';
            print yn($obj->notify_watcher_by_email);
            print '</td>';
        }

        // Assigned user
        if (!empty($arrayfields['assigned_users']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            $assigned_users = explode(',', $obj->assigned_users);
            if (is_array($assigned_users) && count($assigned_users) > 0) {
                $toprint = array();
                foreach ($assigned_users as $user_id) {
                    if ($user_id > 0) {
                        if (!isset($users_cache[$user_id])) {
                            $assigned_user = new User($db);
                            $assigned_user->fetch($user_id);
                            $users_cache[$user_id] = $assigned_user;
                        }
                        $toprint[] = $users_cache[$user_id]->getNomUrl(1);
                    }
                }
                print implode(', ', $toprint);
            }
            print '</td>';
        }

        // Assigned usergroup
        if (!empty($arrayfields['assigned_usergroups']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            $assigned_usergroups = explode(',', $obj->assigned_usergroups);
            if (is_array($assigned_usergroups) && count($assigned_usergroups) > 0) {
                $toprint = array();
                foreach ($assigned_usergroups as $usergroup_id) {
                    if (!isset($usergroups_cache[$usergroup_id])) {
                        $assigned_usergroup = new UserGroup($db);
                        $assigned_usergroup->fetch($usergroup_id);
                        $usergroups_cache[$usergroup_id] = $assigned_usergroup;
                    }
                    $toprint[] = $usergroups_cache[$usergroup_id]->getFullName($langs);
                }
                print implode(', ', $toprint);
            }
            print '</td>';
        }

        // Notification assigned
        if (!empty($arrayfields['rm.notify_assigned_by_email']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'" align="center">';
            print yn($obj->notify_assigned_by_email);
            print '</td>';
        }

        // User resolved
        if (!empty($arrayfields['rm.fk_user_resolved']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            if ($obj->fk_user_resolved > 0) {
                $userstatic->id = $obj->fk_user_resolved;
                $userstatic->firstname = $obj->userresolvedfirstname;
                $userstatic->lastname = $obj->userresolvedlastname;
                $userstatic->email = $obj->userresolvedemail;
                print $userstatic->getNomUrl(1);
            }
            print '</td>';
        }

        // User closed
        if (!empty($arrayfields['rm.fk_user_closed']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            if ($obj->fk_user_closed > 0) {
                $userstatic->id = $obj->fk_user_closed;
                $userstatic->firstname = $obj->userclosedfirstname;
                $userstatic->lastname = $obj->userclosedlastname;
                $userstatic->email = $obj->userclosedemail;
                print $userstatic->getNomUrl(1);
            }
            print '</td>';
        }

        // Date resolved
        if (!empty($arrayfields['rm.date_resolved']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'" align="center">';
            if ($obj->date_resolved > 0) print dol_print_date($db->jdate($obj->date_resolved), 'dayhour');
            print '</td>';
        }

        // Date closed
        if (!empty($arrayfields['rm.date_cloture']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'" align="center">';
            if ($obj->date_closed > 0) print dol_print_date($db->jdate($obj->date_closed), 'dayhour');
            print '</td>';
        }

        // Author
        if (!empty($arrayfields['rm.fk_user_author']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            if ($obj->fk_user_author > 0) {
                $userstatic->id = $obj->fk_user_author;
                $userstatic->firstname = $obj->userauthorfirstname;
                $userstatic->lastname = $obj->userauthorlastname;
                $userstatic->email = $obj->userauthoremail;
                print $userstatic->getNomUrl(1);
            }
            print '</td>';
        }

        // Modified by
        if (!empty($arrayfields['rm.fk_user_modif']['checked'])) {
            print '<td class="nowrap'.$updatedLineClass.'">';
            if ($obj->fk_user_modif > 0) {
                $userstatic->id = $obj->fk_user_modif;
                $userstatic->firstname = $obj->usermodiffirstname;
                $userstatic->lastname = $obj->usermodiflastname;
                $userstatic->email = $obj->usermodifemail;
                print $userstatic->getNomUrl(1);
            }
            print '</td>';
        }

        // Date creation
        if (!empty($arrayfields['rm.datec']['checked'])) {
            print '<td align="center" class="nowrap'.$updatedLineClass.'">';
            print dol_print_date($db->jdate($obj->datec), 'dayhour');
            print '</td>';
        }

        // Date modification
        if (!empty($arrayfields['rm.tms']['checked'])) {
            print '<td align="center" class="nowrap'.$updatedLineClass.'">';
            print dol_print_date($db->jdate($obj->tms), 'dayhour');
            print '</td>';
        }

        // Status
        if (!empty($arrayfields['rm.fk_status']['checked'])) {
            print '<td align="right" class="nowrap'.$updatedLineClass.'">' . $requestmanagerstatic->LibStatut($obj->fk_status, 5) . '</td>';
        }

        print '<td class="nowrap'.$updatedLineClass.'"></td>';

        print "</tr>\n";
    }


    /**
     * Print a list to follow
     *
     * @param   DoliDB          $db                         Doli DB object
     * @param   array           $arrayfields                Array of fields to show
     * @param   RequestManager  $requestmanagerstatic       RequestManager object
     * @param   Societe         $societestatic_origin       Societe origin object
     * @param   Societe         $societestatic              Societe bill object
     * @param   Societe         $societestatic_benefactor   Societe benefactor object
     * @param   User            $userstatic                 User object
     * @param   string          $join                       [=''] Join condition where in SQL
     * @param   string          $filter                     [=''] Filter condition where in SQL
     * @param   string          $sortfield                  [=''] List of sort fields, separated by comma. Example: 't1.fielda, t2.fieldb'
     * @param	string          $sortorder                  [=''] List of sort order seprated by comma ('ASC'|'DESC')
     * @param   string          $titleKey                   [=''] Traduction key for title of this list
     * @param   int             $nbCol                      [=1] Nb column to show
     * @param   int             $lists_follow_last_date     Last date when follow list is viewed
     */
    public static function listsFollowPrintListFrom(DoliDB $db, $arrayfields, RequestManager $requestmanagerstatic, Societe $societestatic_origin, Societe $societestatic, Societe $societestatic_benefactor, User $userstatic, $join='', $filter='', $sortfield='', $sortorder='', $titleKey='', $nbCol=1, $lists_follow_last_date=0)
    {
        global $langs;

        $sql = self::_listsFollowSqlPrepare($db, $join, $filter, $sortfield, $sortorder);

        $resql = $db->query($sql);
        if ($resql) {
            print '<tr class="liste_titre">';
            print '<td colspan="' . $nbCol . '">' . $langs->trans($titleKey) . '</td>';
            print '</tr>';

            $i = 0;
            $num = $db->num_rows($resql);
            while ($i < $num) {
                $obj = $db->fetch_object($resql);

                // print a line
                self::_listsFollowPrintLineFrom($db, $arrayfields, $obj, $requestmanagerstatic, $societestatic_origin, $societestatic, $societestatic_benefactor, $userstatic, $lists_follow_last_date);

                $i++;
            }

            print '<tr>';
            print '<td colspan="' . $nbCol . '" align="center">';
            if ($i <= 0) {
                print $langs->trans('NoRecordFound');
            }
            print '</td>';
            print '</tr>';

            $db->free($resql);
        } else {
            dol_print_error($db);
        }
    }
}

