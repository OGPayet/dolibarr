<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
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
 * \file        core/dictionaries/requestmanagernotifytemplate.dictionary.php
 * \ingroup     requestmanager
 * \brief       Class of the dictionary Status
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');
dol_include_once('/requestmanager/class/requestmanagernotify.class.php');

/**
 * Class for RequestManagerStatusDictionary
 */
class RequestManagerNotifyTemplateDictionary extends Dictionary
{
    /**
     * @var int         Version of this dictionary
     */
    public $version = 1;

    /**
     * @var array       List of languages to load
     */
    public $langs = array('requestmanager@requestmanager');

    /**
     * @var string      Family name of which this dictionary belongs
     */
    public $family = 'requestmanager';

    /**
     * @var string      Family label for show in the list, translated if key found
     */
    public $familyLabel = 'Module163018Name';

    /**
     * @var int         Position of the dictionary into the family
     */
    public $familyPosition = 8;

    /**
     * @var string      Module name of which this dictionary belongs
     */
    public $module = 'requestmanager';

    /**
     * @var string      Module name for show in the list, translated if key found
     */
    public $moduleLabel = 'Module163018Name';

    /**
     * @var string      Name of this dictionary for show in the list, translated if key found
     */
    public $nameLabel = 'RequestManagerNotifyTemplateDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_requestmanager_notify_template';

    /**
     * @var array  Fields of the dictionary table
     * 'name' => array(
     *   'name'       => string,         // Name of the field
     *   'label'      => string,         // Label of the field, translated if key found
     *   'type'       => string,         // Type of the field (varchar, text, int, double, date, datetime, boolean, price, phone, mail, url,
     *                                                         password, select, sellist, radio, checkbox, chkbxlst, link, custom)
     *   'database' => array(            // Description of the field in the database always rewrite default value if set
     *     'type'      => string,        // Data type
     *     'length'    => string,        // Length of the data type (require)
     *     'default'   => string,        // Default value in the database
     *   ),
     *   'is_require' => bool,           // Set at true if this field is required
     *   'options'    => array()|string, // Parameters same as extrafields (ex: 'table:label:rowid::active=1' or array(1=>'value1', 2=>'value2') )
     *                                      string: sellist, chkbxlst, link | array: select, radio, checkbox
     *                                      The key of the value must be not contains the character ',' and for chkbxlst it's a rowid
     *   'is_fixed_value'    => bool,    // Set at true if this field is a set automatically
     *   'is_not_show'       => bool,    // Set at true if this field is not show must be set at true if you want to search or edit
     *   'td_title'          => array (
     *      'moreClasses'    => string,  // Add more classes in the title balise td
     *      'moreAttributes' => string,  // Add more attributes in the title balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'td_output'         => array (
     *      'moreClasses'    => string,  // Add more classes in the output balise td
     *      'moreAttributes' => string,  // Add more attributes in the output balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_output'       => array (
     *      'moreAttributes' => string,  // Add more attributes in when show output field
     *   ),
     *   'is_not_searchable' => bool,    // Set at true if this field is not searchable
     *   'td_search'         => array (
     *      'moreClasses'    => string,  // Add more classes in the search input balise td
     *      'moreAttributes' => string,  // Add more attributes in the search input balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_search_input' => array (
     *      'size'           => int,     // Size attribute of the search input field (input text)
     *      'moreClasses'    => string,  // Add more classes in the search input field
     *      'moreAttributes' => string,  // Add more attributes in the search input field
     *   ),
     *   'is_not_addable'    => bool,    // Set at true if this field is not addable
     *   'is_not_editable'   => bool,    // Set at true if this field is not editable
     *   'td_input'         => array (
     *      'moreClasses'    => string,  // Add more classes in the input balise td
     *      'moreAttributes' => string,  // Add more attributes in the input balise td
     *      'align'          => string,  // Overwrirte the align by default
     *   ),
     *   'show_input'        => array (
     *      'moreClasses'    => string,  // Add more classes in the input field
     *      'moreAttributes' => string,  // Add more attributes in the input field
     *   ),
     *   'help'              => '',      // Help text for this field or url, translated if key found
     *   'is_not_sortable'   => bool,    // Set at true if this field is not sortable
     *   'min'               => int,     // Value minimum (include) if type is int, double or price
     *   'max'               => int,     // Value maximum (include) if type is int, double or price
     * )
     */
    public $fields = array(
        'type' => array(),
        'request_type' => array(),
        'notify_to' => array(),
        'subject' => array(),
        'body' => array(),
        'signature' => array(),
    );

    /**
     * @var array  List of index for the database
     * array(
     *   'fields'    => array( ... ), // List of field name who constitute this index
     *   'is_unique' => bool,         // Set at true if this index is unique
     * )
     */
    public $indexes = array();

    /**
     * @var array  List of fields/indexes added, updated or deleted for a version
     * array(
     *   'version' => array(
     *     'fields' => array('field_name'=>'a', 'field_name'=>'u', ...), // List of field name who is added(a) or updated(u) for a version
     *     'deleted_fields' => array('field_name'=> array('name', 'type', other_custom_data_required_for_delete), ...), // List of field name who is deleted for a version
     *     'indexes' => array('idx_number'=>'u', 'idx_number'=>'d', ...), // List of indexes number who is updated(u) or deleted(d) for a version
     *   ),
     * )
     */
    public $updates = array(
        1 => array(
            'fields' => array(
                'signature' => 'a',
            ),
        ),
    );

    /**
     * @var bool    Is multi entity (false = partaged, true = by entity)
     */
    public $is_multi_entity = true;

    /**
     * @var bool    Edit in the add form
     */
    public $edit_in_add_form = true;

    /**
	 * Initialize the dictionary
	 *
     * @return  void
	 */
	protected function initialize()
    {
        global $langs, $hookmanager;

        $langs->load('requestmanager@requestmanager');

        // Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('requestmanagerdao'));

        // Set list of template type
        $templateTypeList = array(
            RequestManagerNotify::TYPE_REQUEST_CREATED => $langs->trans('RequestManagerNotifyTemplateTypeRequestCreated'),
            RequestManagerNotify::TYPE_ASSIGNED_MODIFIED => $langs->trans('RequestManagerNotifyTemplateTypeAssignedUsersModified'),
            RequestManagerNotify::TYPE_ASSIGNED_MODIFIED => $langs->trans('RequestManagerNotifyTemplateTypeAssignedUsersModified'),
            RequestManagerNotify::TYPE_STATUS_MODIFIED => $langs->trans('RequestManagerNotifyTemplateTypeStatusModified'),
            RequestManagerNotify::TYPE_MESSAGE_ADDED => $langs->trans('RequestManagerNotifyTemplateTypeMessageAdded'),
        );

        $parameters = array('templateTypeList' => $templateTypeList);
        $reshook = $hookmanager->executeHooks('addNotifyTemplateTypeList', $parameters);    // Note that $action and $object may have been modified by some hooks
        if ($reshook >= 0) {
            foreach ($hookmanager->resArray as $item => $value) {
                $templateTypeList[$item] = $value;
            }
        }

        $this->fields['type'] = array(
            'name' => 'type',
            'label' => 'RequestManagerNotifyTemplateType',
            'type' => 'select',
            'options' => $templateTypeList,
            'is_require' => true,
        );

        $this->fields['request_type'] = array(
            'name' => 'request_type',
            'label' => 'RequestManagerRequestType',
            'type' => 'chkbxlst',
            'options' => 'c_requestmanager_request_type:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="30%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="30%"',
            ),
            'is_require' => true,
            'help' => 'RequestManagerTemplateForRequestType',
        );

        // Set list of notify to
        $notifyToList = array(
            RequestManagerNotify::NOTIFY_TO_ASSIGNED => $langs->trans('RequestManagerAssigned'),
            RequestManagerNotify::NOTIFY_TO_REQUESTERS => $langs->trans('RequestManagerRequesterContacts'),
            RequestManagerNotify::NOTIFY_TO_WATCHERS => $langs->trans('RequestManagerWatcherContacts'),
        );

        $parameters = array('notifyToList' => $notifyToList);
        $reshook = $hookmanager->executeHooks('addNotifyToList', $parameters);    // Note that $action and $object may have been modified by some hooks
        if ($reshook >= 0) {
            foreach ($hookmanager->resArray as $item => $value) {
                $notifyToList[$item] = $value;
            }
        }

        $this->fields['notify_to'] = array(
            'name' => 'notify_to',
            'label' => 'RequestManagerNotifyTo',
            'type' => 'checkbox',
            'options' => $notifyToList,
            'td_output' => array(
                'moreAttributes' => 'width="30%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="30%"',
            ),
            'is_require' => true,
            'help' => 'RequestManagerNotifyToHelp',
        );

        // List of help for fields
        dol_include_once('/requestmanager/class/requestmanagersubstitutes.class.php');
        $subsitutesKeys = RequestManagerSubstitutes::getAvailableSubstitutesKeyFromRequest($this->db);
        $subsitutesMessageKeys = RequestManagerSubstitutes::getAvailableSubstitutesKeyFromRequestMessage($this->db);
        $subsitutesKeys = array_merge($subsitutesKeys, $subsitutesMessageKeys);
        $helpSubstitution = $langs->trans("AvailableVariables") . ':<br>';
        $helpSubstitution .= "<div style='display: block; overflow: auto; height: 700px;'><table class='nobordernopadding'>";
        foreach ($subsitutesKeys as $key => $label) {
            $helpSubstitution .= "<tr><td><span style='margin-right: 10px;'>" . $key . ' :</span></td><td>' . $label . '</td></tr>';
        }
        $helpSubstitution .= '</table></div>';

        $this->fields['subject'] = array(
            'name' => 'subject',
            'label' => 'Topic',
            'type' => 'varchar',
            'database' => array(
                'length' => 255,
            ),
            'td_output' => array(
                'moreAttributes' => 'width="100%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="100%"',
            ),
            'show_input' => array(
                'moreAttributes' => ' style="width:100%;"',
            ),
            'is_require' => true,
            'help_button' => $helpSubstitution,
        );

        $this->fields['body'] = array(
            'name' => 'body',
            'label' => 'Content',
            'type' => 'text',
            'is_require' => true,
            'is_not_show' => true,
            'help_button' => $helpSubstitution,
            'td_input' => array(
                'positionLine' => 1,
            ),
        );

        $this->fields['signature'] = array(
            'name' => 'signature',
            'label' => 'Signature',
            'type' => 'text',
            'is_not_show' => true,
            'help_button' => $helpSubstitution,
            'td_input' => array(
                'positionLine' => 2,
            ),
        );
    }
}

class RequestManagerNotifyTemplateDictionaryLine extends DictionaryLine
{
    public function checkFieldsValues($fieldsValue)
    {
        global $langs;

        $result = parent::checkFieldsValues($fieldsValue);
        if ($result < 0) {
            return $result;
        }

        $result = $this->dictionary->fetch_lines(-1, array('type' => array($fieldsValue['type']), 'request_type' => array($fieldsValue['request_type']), 'notify_to' => array($fieldsValue['notify_to'])));
        if ($result < 0) {
            return $result;
        }

        $nbLines = count($this->dictionary->lines);
        if ($nbLines > 0 && ($nbLines > 1 || !isset($this->dictionary->lines[$this->id]))) {
            $this->errors[] = $langs->trans('RequestManagerErrorOnlyOneNotifyTemplateTypeForEachRequestTypeAndNotifyTo');
            return -1;
        }

        return 1;
    }
}
