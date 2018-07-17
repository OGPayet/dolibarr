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
 *	\file       requestmanager/core/class/html.formrequestmanager.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class with all html predefined components for request manager
 */

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
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerrequesttype', $selected, $htmlname, 'rowid', '{{label}}', is_array($usergroups) ? array('user_group'=>$usergroups) : array(), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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

        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagercategory', $selected, $htmlname, 'rowid', '{{label}}', array('rowid' => $categories_id), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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
    function select_source($selected = '', $htmlname = 'type', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagersource', $selected, $htmlname, 'rowid', '{{label}}', array(), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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
    function select_urgency($selected = '', $htmlname = 'type', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerurgency', $selected, $htmlname, 'rowid', '{{label}}', array(), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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
    function select_impact($selected = '', $htmlname = 'type', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerimpact', $selected, $htmlname, 'rowid', '{{label}}', array(), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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
    function select_priority($selected = '', $htmlname = 'type', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerpriority', $selected, $htmlname, 'rowid', '{{label}}', array(), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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
    function select_status($request_type, $selected = '', $htmlname = 'type', $showempty = '', $forcecombo = 0, $events = array(), $usesearchtoselect=0, $limit = 0, $morecss = 'minwidth100', $moreparam = '', $selected_input_value = '', $hidelabel = 1, $selectlabel = '', $autofocus=0, $ajaxoptions = array(), $options_only=false)
    {
        dol_include_once('/requestmanager/class/requestmanager.class.php');
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerstatus', $selected, $htmlname, 'rowid', '{{label}}', array('request_type'=>array($request_type), 'type'=>array(RequestManager::STATUS_TYPE_IN_PROGRESS)), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
    }
}
