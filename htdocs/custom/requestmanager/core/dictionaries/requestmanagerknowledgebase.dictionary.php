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
 * \file        requestmanager/core/dictionaries/requestmanagerknowledgebase.dictionary.php
 * \ingroup     requestmanager
 * \brief       Class of the dictionary knowledge base
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for RequestManagerKnowledgeBaseDictionary
 */
class RequestManagerKnowledgeBaseDictionary extends Dictionary
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
    public $familyPosition = 9;

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
    public $nameLabel = 'RequestManagerKnowledgeBaseDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_requestmanager_knowledge_base';

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
        'code' => array(
            'name'       => 'code',
            'label'      => 'Code',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 16
            ),
            'is_require' => true
        ),
        'title' => array(
            'name'       => 'title',
            'label'      => 'Title',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255
            ),
            'is_require' => true
        ),
        'request_type' => array(),
        'categorie' => array(),
        'position' => array(
            'name'       => 'position',
            'label'      => 'Position',
            'type'       => 'int',
            'database'   => array(
              'length'   => 11
            ),
            'td_title'  => array (
                'align'  => 'left'
            ),
            'td_output'  => array (
                'align'  => 'left'
            ),
            'td_search'  => array (
                'align'  => 'left'
            ),
            'td_input'  => array (
                'align'  => 'left'
            ),
        ),
        'description' => array()
    );

    /**
     * @var array  List of index for the database
     * array(
     *   'fields'    => array( ... ), // List of field name who constitute this index
     *   'is_unique' => bool,         // Set at true if this index is unique
     * )
     */
    public $indexes = array(
        0 => array(
            'fields'    => array('code'),
            'is_unique' => true
        )
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
            'fields' => array(
                'position'      => 'u',
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
     * @var array    Cache of the list of the categories for categorie show output
     */
    public $categories_cache = null;
    /**
     * @var FormRequestManager    FormRequestManager object for categorie show input
     */
    public $formrequestmanager = null;

    /**
	 * Load the cache of the list of the categories for categorie show output
	 *
     * @return  void
	 */
    public function load_categories()
    {
        global $conf;

        if (!isset($this->categories_cache)) {
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

            $this->categories_cache = $list;
        }
    }

    /**
	 * Load the FormRequestManager object for categories show input
	 *
     * @return  void
	 */
    public function load_formrequestmanager()
    {
        if (!isset($this->formrequestmanager)) {
            dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
            $this->formrequestmanager = new FormRequestManager($this->db);
        }
    }

    /**
	 * Initialize the dictionary
	 *
     * @return  void
	 */
	protected function initialize()
    {
        global $langs;

        $langs->load('requestmanager@requestmanager');

        dol_include_once('/requestmanager/class/requestmanager.class.php');
        $this->fields['categorie'] = array(
            'name' => 'categorie',
            'label' => 'Categories',
            'type' => 'chkbxlst',
            'options' => 'categorie:label:rowid::type=0 and entity IN (' . getEntity( 'category', 1 ) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="40%"'
            ),
            'td_input' => array(
                'moreAttributes' => 'width="40%"'
            ),
            'show_input' => array(
                'moreClasses' => 'centpercent'
            ),
            'is_require' => false
        );

        $this->fields['request_type'] = array(
            'name' => 'request_type',
            'label' => 'RequestManagerRequestType',
            'type' => 'chkbxlst',
            'options' => 'c_requestmanager_request_type:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="30%"'
            ),
            'td_input' => array(
                'moreAttributes' => 'width="30%"'
            ),
            'is_require' => true
        );

        // List of help for fields
        dol_include_once('/requestmanager/class/requestmanagersubstitutes.class.php');
        $subsitutesKeys = RequestManagerSubstitutes::getAvailableSubstitutesKeyFromRequest($this->db);
        $helpSubstitution = $langs->trans("AvailableVariables") . ':<br>';
        $helpSubstitution .= "<div style='display: block; overflow: auto; height: 700px;'><table class='nobordernopadding'>";
        foreach ($subsitutesKeys as $key => $label) {
            $helpSubstitution .= "<tr><td><span style='margin-right: 10px;'>" . $key . ' :</span></td><td>' . $label . '</td></tr>';
        }
        $helpSubstitution .= '</table></div>';

        $this->fields['description'] = array(
            'name' => 'description',
            'label' => 'Description',
            'type' => 'text',
            'is_require' => true,
            'is_not_show' => true,
            'help_button' => $helpSubstitution,
            'td_input' => array(
                'positionLine' => 1
            )
        );
    }
}

class RequestManagerKnowledgeBaseDictionaryLine extends DictionaryLine
{
    /**
     * Return HTML string to put an output field into a page
     *
     * @param   string	$fieldName      Name of the field
     * @param   string	$value          Value to show
     * @return	string					Formatted value
     */
    function showOutputFieldAD($fieldName, $value = null)
    {
        if ($fieldName == 'categorie') {
            if (isset($this->dictionary->fields[$fieldName])) {
                if ($value === null) $value = $this->fields[$fieldName];
                if (is_array($value)) {
                    $value_arr = $value;
                } else {
                    $value_arr = array_filter(explode(',', (string)$value), 'strlen');
                }

                $toprint = array();
                if (is_array($value_arr) && count($value_arr)) {
                    $this->dictionary->load_categories();
                    foreach ($value_arr as $id) {
                        $toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">' . $this->dictionary->categories_cache[$id] . '</li>';
                    }
                }

                return '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
            }
        }

        return parent::showOutputFieldAD($fieldName, $value);
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
	function showInputFieldAD($fieldName, $value = NULL, $keyprefix = '', $keysuffix = '', $objectid = 0, $options_only = 0)
    {
        if ($fieldName == 'categorie') {
            if (isset($this->dictionary->fields[$fieldName])) {
                $field = $this->dictionary->fields[$fieldName];

                if ($value === null) $value = $this->fields[$fieldName];
                if (is_array($value)) {
                    $value_arr = $value;
                } else {
                    $value_arr = array_filter(explode(',', (string)$value), 'strlen');
                }

                $type = $field['type'];

                $fieldHtmlName = $keyprefix . $fieldName . $keysuffix;

                $moreClasses = trim($field['show_input']['moreClasses']);
                if (empty($moreClasses)) {
                    if ($type == 'date') {
                        $moreClasses = ' minwidth100imp';
                    } elseif ($type == 'datetime') {
                        $moreClasses = ' minwidth200imp';
                    } elseif (in_array($type, array('int', 'double', 'price'))) {
                        $moreClasses = ' maxwidth75';
                    } elseif (in_array($type, array('varchar', 'phone', 'mail', 'url', 'password', 'select', 'sellist', 'radio', 'checkbox', 'link', 'chkbxlst'))) {
                        $moreClasses = ' minwidth200';
                    } elseif ($type == 'boolean') {
                        $moreClasses = '';
                    } else {
                        $moreClasses = ' minwidth100';
                    }
                } else {
                    $moreClasses = ' ' . $moreClasses;
                }

                $moreAttributes = trim($field['show_input']['moreAttributes']);
                $moreAttributes = !empty($moreAttributes) ? ' ' . $moreAttributes : '';

                $this->dictionary->load_formrequestmanager();
                return $this->dictionary->formrequestmanager->multiselect_categories($value_arr, $fieldHtmlName,  0, 0, $moreClasses, 0, '', $moreAttributes);
            }
        }

        return parent::showInputFieldAD($fieldName, $value, $keyprefix, $keysuffix, $objectid);
    }
}
