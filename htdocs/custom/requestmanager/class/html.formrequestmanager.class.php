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
        return $this->formdictionary->select_dictionary('requestmanager', 'requestmanagerstatus', $selected, $htmlname, 'rowid', '{{label}}', array('request_type'=>array($request_type), 'type'=>array(RequestManager::STATUS_TYPE_IN_PROGRESS)), $showempty, $forcecombo, $events, $usesearchtoselect, $limit, $morecss, $moreparam, $selected_input_value, $hidelabel, $selectlabel, $autofocus, $ajaxoptions, $options_only);
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
                $out .= '<option value="-1" disabled>' . $langs->trans("NoActionComm") . '</option>';
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
     * @param   int         $fkSoc                  Id of company (-1 for all, 0 for none)
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
                $out .= '<option value="-1" disabled>' . $langs->trans("NoEquipement") . '</option>';
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
        }
        $newCompanyId = $requestManager->socid;
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
        $this->form->select_contacts($newCompanyId, '', $contactTypeCodeHtmlName . '_fk_socpeople', 1);
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
            $cate_arbo = $this->form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
            foreach($categories as $c) {
                $arrayselected[] = $c->id;
            }

            $selectMoreAttrib = 'disabled';
            $selectElementType = 'category';
            if ($editMode === TRUE) {
                $selectMoreAttrib = '';
                $selectElementType = '';
            }

            return $this->form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%', $selectMoreAttrib, $selectElementType);
        }

        return 'ErrorBadValueForParameterRenderMode';	// Should not happened
    }


    /**
     * Prepare SQL request for lists to follow
     *
     * @param   DoliDB  $db             Doli DB object
     * @param   string  $filter         Filter condition where in SQL
     * @param   string  $sortfield      List of sort fields, separated by comma. Example: 't1.fielda, t2.fieldb'
     * @param	string  $sortorder      List of sort order seprated by comma ('ASC'|'DESC')
     * @return  string  SQL request
     */
    private static function _listsFollowSqlPrepare(DoliDB $db, $filter='', $sortfield='', $sortorder='')
    {
        $sql  = 'SELECT';
        $sql .= ' rm.rowid, rm.ref, rm.ref_ext,';
        $sql .= ' rm.fk_soc, s.nom as soc_name, s.client as soc_client, s.fournisseur as soc_fournisseur, s.code_client as soc_code_client, s.code_fournisseur as soc_code_fournisseur,';
        $sql .= ' rm.label, rm.description,';
        $sql .= ' rm.fk_type, crmrt.label as type_label,';
        $sql .= ' rm.fk_category, crmc.label as category_label,';
        $sql .= ' rm.fk_source, crms.label as source_label,';
        $sql .= ' rm.fk_urgency, crmu.label as urgency_label,';
        $sql .= ' rm.fk_impact, crmi.label as impact_label,';
        $sql .= ' rm.fk_priority, crmp.label as priority_label,';
        $sql .= ' rm.notify_requester_by_email, rm.notify_watcher_by_email, rm.notify_assigned_by_email,';
        $sql .= ' rm.fk_assigned_user, uas.firstname as userassignedfirstname, uas.lastname as userassignedlastname, uas.email as userassignedemail,';
        $sql .= ' rm.fk_assigned_usergroup, uga.nom as usergroupassignedname,';
        $sql .= ' rm.duration, rm.date_deadline, rm.date_resolved, rm.date_closed,';
        $sql .= ' rm.fk_user_resolved, ur.firstname as userresolvedfirstname, ur.lastname as userresolvedlastname, ur.email as userresolvedemail,';
        $sql .= ' rm.fk_user_closed, uc.firstname as userclosedfirstname, uc.lastname as userclosedlastname, uc.email as userclosedemail,';
        $sql .= ' rm.fk_status,';
        $sql .= ' rm.datec, rm.tms,';
        $sql .= ' rm.fk_user_author, ua.firstname as userauthorfirstname, ua.lastname as userauthorlastname, ua.email as userauthoremail,';
        $sql .= ' rm.fk_user_modif, um.firstname as usermodiffirstname, um.lastname as usermodiflastname, um.email as usermodifemail';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm';
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_request_type as crmrt on (crmrt.rowid = rm.fk_type)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_category as crmc on (crmc.rowid = rm.fk_category)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_source as crms on (crms.rowid = rm.fk_source)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_urgency as crmu on (crmu.rowid = rm.fk_urgency)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_impact as crmi on (crmi.rowid = rm.fk_impact)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_priority as crmp on (crmp.rowid = rm.fk_priority)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_status as crmst on (crmst.rowid = rm.fk_status)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s on (s.rowid = rm.fk_soc)";
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as uas ON uas.rowid = rm.fk_assigned_user';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'usergroup as uga ON uga.rowid = rm.fk_assigned_usergroup';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ur ON ur.rowid = rm.fk_user_resolved';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as uc ON uc.rowid = rm.fk_user_closed';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ua ON ua.rowid = rm.fk_user_author';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as um ON um.rowid = rm.fk_user_modif';
        $sql .= ' WHERE rm.entity IN (' . getEntity('requestmanager') . ')';
        $sql .= $filter;
        $sql .= $db->order($sortfield, $sortorder);

        return $sql;
    }


    /**
     * Print a line for lists to follow
     *
     * @param   DoliDB          $db                     Doli DB object
     * @param   array           $arrayfields            Array of fields to show
     * @param   stdClass        $obj                    Standard object from db
     * @param   RequestManager  $requestmanagerstatic   RequestManager object
     * @param   Societe         $societestatic          Societe object
     * @param   User            $userstatic             User object
     */
    private static function _listsFollowPrintLineFrom(DoliDB $db, $arrayfields, $obj, RequestManager $requestmanagerstatic, Societe $societestatic, User $userstatic)
    {
        global $langs;

        $now = dol_now();

        // societe
        $societestatic->id               = $obj->fk_soc;
        $societestatic->name             = $obj->soc_name;
        $societestatic->client           = $obj->soc_client;
        $societestatic->fournisseur      = $obj->soc_fournisseur;
        $societestatic->code_client      = $obj->soc_code_client;
        $societestatic->code_fournisseur = $obj->soc_code_fournisseur;

        // requestmanager
        $requestmanagerstatic->id            = $obj->rowid;
        $requestmanagerstatic->ref           = $obj->ref;
        $requestmanagerstatic->ref_ext       = $obj->ref_ext;
        $requestmanagerstatic->fk_type       = $obj->fk_type;
        $requestmanagerstatic->label         = $obj->label;
        $requestmanagerstatic->socid         = $obj->fk_soc;
        //$requestmanagerstatic->date_deadline = $obj->date_deadline;
        $requestmanagerstatic->thirdparty    = $societestatic;

        // picto warning for deadline
        $pictoWarning = '';
        if ($obj->date_deadline) {
            $tmsDeadLine = strtotime($obj->date_deadline);
            if ($tmsDeadLine < $now) {
                // alert time is up
                $pictoWarning = img_warning($langs->trans("Late"));
            }
        }

        print '<tr class="oddeven">';

        // Ref
        if (!empty($arrayfields['rm.ref']['checked'])) {
            print '<td class="nowrap">';
            print $requestmanagerstatic->getNomUrl(1) . ' ' . $pictoWarning;
            print '</td>';
        }

        //External Ref
        if (!empty($arrayfields['rm.ref_ext']['checked'])) {
            print '<td class="nowrap">';
            print $obj->ref_ext;
            print '</td>';
        }

        // Type
        if (!empty($arrayfields['rm.fk_type']['checked'])) {
            print '<td class="nowrap">';
            print $obj->type_label;
            print '</td>';
        }

        // Category
        if (!empty($arrayfields['rm.fk_category']['checked'])) {
            print '<td class="nowrap">';
            print $obj->category_label;
            print '</td>';
        }

        // Label
        if (!empty($arrayfields['rm.label']['checked'])) {
            print '<td class="nowrap">';
            print $obj->label;
            print '</td>';
        }

        // Thridparty
        if (!empty($arrayfields['rm.fk_soc']['checked'])) {
            print '<td class="nowrap">';
            print $societestatic->getNomUrl(1);
            print '</td>';
        }

        // Description
        if (!empty($arrayfields['rm.description']['checked'])) {
            print '<td class="nowrap">';
            print $obj->description;
            print '</td>';
        }

        // Source
        if (!empty($arrayfields['rm.fk_source']['checked'])) {
            print '<td class="nowrap">';
            print $obj->source_label;
            print '</td>';
        }

        // Urgency
        if (!empty($arrayfields['rm.fk_urgency']['checked'])) {
            print '<td class="nowrap">';
            print $obj->urgency_label;
            print '</td>';
        }

        // Impact
        if (!empty($arrayfields['rm.fk_impact']['checked'])) {
            print '<td class="nowrap">';
            print $obj->impact_label;
            print '</td>';
        }

        // Priority
        if (!empty($arrayfields['rm.fk_priority']['checked'])) {
            print '<td class="nowrap">';
            print $obj->priority_label;
            print '</td>';
        }

        // Duration
        if (!empty($arrayfields['rm.duration']['checked'])) {
            print '<td class="nowrap">';
            if ($obj->duration > 0) print requestmanager_print_duration($obj->duration);
            print '</td>';
        }

        // Date Deadline
        if (!empty($arrayfields['rm.date_deadline']['checked'])) {
            print '<td class="nowrap" align="center">';
            if ($obj->date_deadline > 0) print dol_print_date($db->jdate($obj->date_deadline), 'dayhour');
            print '</td>';
        }

        // Notification requesters
        if (!empty($arrayfields['rm.notify_requester_by_email']['checked'])) {
            print '<td class="nowrap" align="center">';
            print yn($obj->notify_requester_by_email);
            print '</td>';
        }

        // Notification watchers
        if (!empty($arrayfields['rm.notify_watcher_by_email']['checked'])) {
            print '<td class="nowrap" align="center">';
            print yn($obj->notify_watcher_by_email);
            print '</td>';
        }

        // Assigned user
        if (!empty($arrayfields['rm.fk_assigned_user']['checked'])) {
            print '<td class="nowrap">';
            if ($obj->fk_assigned_user > 0) {
                $userstatic->id = $obj->fk_assigned_user;
                $userstatic->firstname = $obj->userassignedfirstname;
                $userstatic->lastname = $obj->userassignedlastname;
                $userstatic->email = $obj->userassignedemail;
                print $userstatic->getNomUrl(1);
            }
            print '</td>';
        }

        // Assigned usergroup
        if (!empty($arrayfields['rm.fk_assigned_usergroup']['checked'])) {
            print '<td class="nowrap">';
            print $obj->usergroupassignedname;
            print '</td>';
        }

        // Notification assigned
        if (!empty($arrayfields['rm.notify_assigned_by_email']['checked'])) {
            print '<td class="nowrap" align="center">';
            print yn($obj->notify_assigned_by_email);
            print '</td>';
        }

        // User resolved
        if (!empty($arrayfields['rm.fk_user_resolved']['checked'])) {
            print '<td class="nowrap">';
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
            print '<td class="nowrap">';
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
            print '<td class="nowrap" align="center">';
            if ($obj->date_resolved > 0) print dol_print_date($db->jdate($obj->date_resolved), 'dayhour');
            print '</td>';
        }

        // Date closed
        if (!empty($arrayfields['rm.date_cloture']['checked'])) {
            print '<td class="nowrap" align="center">';
            if ($obj->date_closed > 0) print dol_print_date($db->jdate($obj->date_closed), 'dayhour');
            print '</td>';
        }

        // Author
        if (!empty($arrayfields['rm.fk_user_author']['checked'])) {
            print '<td class="nowrap">';
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
            print '<td class="nowrap">';
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
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->datec), 'dayhour');
            print '</td>';
        }

        // Date modification
        if (!empty($arrayfields['rm.tms']['checked'])) {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->tms), 'dayhour');
            print '</td>';
        }

        // Status
        if (!empty($arrayfields['rm.fk_status']['checked'])) {
            print '<td align="right" class="nowrap">' . $requestmanagerstatic->LibStatut($obj->fk_status, 5) . '</td>';
        }

        print '<td></td>';

        print "</tr>\n";
    }


    /**
     * Print a list to follow
     *
     * @param   DoliDB          $db                     Doli DB object
     * @param   array           $arrayfields            Array of fields to show
     * @param   stdClass        $obj                    Standard object from db
     * @param   RequestManager  $requestmanagerstatic   RequestManager object
     * @param   Societe         $societestatic          Societe object
     * @param   User            $userstatic             User object
     * @param   string          $filter                 [=''] Filter condition where in SQL
     * @param   string          $sortfield              [=''] List of sort fields, separated by comma. Example: 't1.fielda, t2.fieldb'
     * @param	string          $sortorder              [=''] List of sort order seprated by comma ('ASC'|'DESC')
     * @param   string          $titleKey               [=''] Traduction key for title of this list
     * @param   int             $nbCol                  [=1] Nb column to show
     */
    public static function listsFollowPrintListFrom(DoliDB $db, $arrayfields, RequestManager $requestmanagerstatic, Societe $societestatic, User $userstatic, $filter='', $sortfield='', $sortorder='', $titleKey='', $nbCol=1)
    {
        global $langs;

        $sql = self::_listsFollowSqlPrepare($db, $filter, $sortfield, $sortorder);

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
                self::_listsFollowPrintLineFrom($db, $arrayfields, $obj, $requestmanagerstatic, $societestatic, $userstatic);

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

