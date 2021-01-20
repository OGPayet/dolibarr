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
 * \file        core/dictionaries/eventconfidentialitydefault.dictionary.php
 * \ingroup     eventconfidentiality
 * \brief       Class of the dictionary Default event confidentialities
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for EventConfidentialityDefaultDictionary
 */
class EventConfidentialityDefaultDictionary extends Dictionary
{
    /**
     * @var int         Version of this dictionary
     */
    public $version = 1;

    /**
     * @var array       List of languages to load
     */
    public $langs = array('eventconfidentiality@eventconfidentiality');

    /**
     * @var string      Family name of which this dictionary belongs
     */
    public $family = 'eventconfidentiality';

    /**
     * @var string      Family label for show in the list, translated if key found
     */
    public $familyLabel = 'Module163022Name';

    /**
     * @var int         Position of the dictionary into the family
     */
    public $familyPosition = 2;

    /**
     * @var string      Module name of which this dictionary belongs
     */
    public $module = 'eventconfidentiality';

    /**
     * @var string      Module name for show in the list, translated if key found
     */
    public $moduleLabel = 'Module163022Name';

    /**
     * @var string      Name of this dictionary for show in the list, translated if key found
     */
    public $nameLabel = 'EventConfidentialityDefaultDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_eventconfidentiality_default';

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
     *   'help' => '',                   // Help text for this field or url, translated if key found
     *   'is_not_sortable'   => bool,    // Set at true if this field is not sortable
     *   'min'               => int,     // Value minimum (include) if type is int, double or price
     *   'max'               => int,     // Value maximum (include) if type is int, double or price
     * )
     */
    public $fields = array(
        'action_type' => array(
            'name' => 'action_type',
            'label' => 'EventConfidentialityActionType',
            'type' => 'chkbxlst',
            'options' => 'c_actioncomm:libelle:id::active=1',
            'td_output' => array(
                'moreAttributes' => 'width="50%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="50%"',
            ),
        ),
        'element_origin' => array(),
        'tags' => array(),
        'mode' => array(),
    );

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
            'delete_fields' => array(
                'external' => array(
                    'name' => 'external',
                    'type' => 'boolean',
                ),
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
     * @var array    Cache of the list of the action type for action_type show output
     */
    public $cactioncomm_cache = null;

    /**
	 * Load the cache of the list of the action type for action_type show output
	 *
     * @return  void
	 */
    public function load_action_type()
    {
        global $conf;

        if (!isset($this->cactioncomm_cache)) {
            require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
            $cactioncomm = new CActionComm($this->db);

            // Suggest a list with manual events or all auto events
            $this->cactioncomm_cache = $cactioncomm->liste_array(1, 'id', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE)?1:-1));

            unset($this->cactioncomm_cache[-99]);
        }
    }

    /**
	 * Initialize the dictionary
	 *
     * @return  void
	 */
	protected function initialize()
    {
        global $langs, $hookmanager;

        $langs->loadLangs(array('eventconfidentiality@eventconfidentiality', 'companies', 'projects', 'products', 'propal', 'orders', 'bills', 'trips', 'sendings', 'interventions', 'commercial', 'supplier_proposal'));

        // TODO A compléter si manquant
        $element_origin_list = array(
            'contract' => $langs->trans('Contract'),
            'societe' => $langs->trans('ThirdParty'),
            'expensereport' => $langs->trans('ExpenseReport'),
            'product' => $langs->trans('ProductOrService'),
            'invoice' => $langs->trans('Invoice'),
            'propal' => $langs->trans('Proposal'),
            'supplier_proposal' => $langs->trans('SupplierProposal'),
            'order' => $langs->trans('Order'),
            'order_supplier' => $langs->trans('SupplierOrder'),
            'shipping' => $langs->trans('Shipment'),
            'invoice_supplier' => $langs->trans('SupplierInvoice'),
            'project' => $langs->trans('Project'),
            'fichinter' => $langs->trans('Intervention'),
            'ec_event' => $langs->trans('Event'),
        );

        // Add custom object
        $hookmanager->initHooks(array('eventconfidentialitydao'));
        $parameters = array();
        $reshook = $hookmanager->executeHooks('addEventConfidentialityElementOrigin', $parameters); // Note that $action and $object may have been
        if ($reshook) $element_origin_list = array_merge($element_origin_list, $hookmanager->resArray);

        asort($element_origin_list);

        $this->fields['element_origin'] = array(
            'name'       => 'element_origin',
            'label'      => 'EventConfidentialityElementOrigin',
            'type'       => 'checkbox',
            'options' => $element_origin_list,
            'td_output' => array(
                'moreAttributes' => 'width="50%"',
            ),
            'td_input'  => array (
                'moreAttributes' => 'width="50%"',
            ),
        );

        $this->fields['tags'] = array(
            'name' => 'tags',
            'label' => 'EventConfidentialityTags',
            'type' => 'chkbxlst',
            'options' => 'c_eventconfidentiality_tag:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="50%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="50%"',
                'positionLine' => 1,
            ),
            'is_require' => true,
        );

        dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
        $eventconfidentiality = new EventConfidentiality($this->db);
        $this->fields['mode'] = array(
            'name' => 'mode',
            'label' => 'EventConfidentialityMode',
            'type' => 'select',
            'options' => $eventconfidentiality->mode_labels,
            'td_input'  => array (
                'positionLine' => 1,
            ),
            'is_require' => true,
        );
    }
}

class EventConfidentialityDefaultDictionaryLine extends DictionaryLine
{
    /**
     *  Check values fields
     *
     * @param   array   $fieldsValue    Values of the fields array(name => value, ...)
     * @return  int                     <0 if not ok, >0 if ok
     */
    public function checkFieldsValues($fieldsValue)
    {
        global $langs;

        $result = parent::checkFieldsValues($fieldsValue);
        if ($result < 0) {
            return $result;
        }

        // Unique key not found
        $result = $this->dictionary->fetch_lines(1,
            array(
                'action_type' => explode(',', $fieldsValue['action_type']),
                'element_origin' => explode(',', $fieldsValue['element_origin']),
                'tags' => explode(',', $fieldsValue['tags']),
                'mode' => array($fieldsValue['mode']),
            ),
            array(), 0, 0, true
        );

        if ($result > 0) {
            $this->errors[] = $langs->trans('EventConfidentialityErrorUniqueKeyAlreadyExist');
            return -1;
        }

        return 1;
    }


    /**
     * Return HTML string to put an output field into a page
     *
     * @param   string	$fieldName      Name of the field
     * @param   string	$value          Value to show
     * @return	string					Formatted value
     */
    function showOutputFieldAD($fieldName, $value = null)
    {
        if ($fieldName == 'action_type') {
            if (isset($this->dictionary->fields[$fieldName])) {
                // Load action type infos into cache
                $this->dictionary->load_action_type();

                if ($value === null) $value = $this->fields[$fieldName];

                if (is_array($value)) {
                    $value_arr = $value;
                } else {
                    $value_arr = array_filter(explode(',', (string)$value), 'strlen');
                }

                $toprint = array();
                foreach ($value_arr as $action_type_id) {
                    $toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">' . $this->dictionary->cactioncomm_cache[$action_type_id] . '</li>';
                }
                $value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';

                return $value;
            }
        }

        return parent::showOutputField($fieldName, $value);
    }

    /**
	 * Return HTML string to put an input field into a page
	 *
	 * @param  string  $fieldName      Name of the field
	 * @param  string  $value          Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $keyprefix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keysuffix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  int     $objectid       Current object id
	 * @return string
	 */
	function showInputFieldAD($fieldName, $value = null, $keyprefix = '', $keysuffix = '', $objectid = 0, $options_only = 0)
    {
        if ($fieldName == 'action_type') {
            // Load action type infos into cache
            $this->dictionary->load_action_type();

            require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            $form = new Form($this->db);

            return $form->multiselectarray($keyprefix . 'action_type' . $keysuffix, $this->dictionary->cactioncomm_cache, $value, 0, 0, 'centpercent');
        }

        return parent::showInputField($fieldName, $value, $keyprefix, $keysuffix, $objectid);
    }
}
