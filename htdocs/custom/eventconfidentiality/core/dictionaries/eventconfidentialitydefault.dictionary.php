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
    public $version = 0;

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
            'is_require' => true,
        ),
        'element_origin' => array(),
        'tags' => array(),
        'external' => array(
            'name'       => 'external',
            'label'      => 'EventConfidentialityExternal',
            'type'       => 'boolean',
            'td_input'  => array (
                'positionLine' => 1,
            ),
        ),
        'mode' => array(),
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

        $langs->loadLangs(array('eventconfidentiality@eventconfidentiality', 'propal', 'orders', 'bills', 'interventions', 'commercial'));

        // Todo à vérifier car c'est mis à titre d'exemple (c'est la liste des objets standard)
        $element_origin_list = array(
            'propal' => $langs->trans('Propal'),
            'order' => $langs->trans('Order'),
            'invoice' => $langs->trans('Invoice'),
            'inter' => $langs->trans('Inter'),
            'event' => $langs->trans('Action'),
        );

        // Add custom object
        $hookmanager->initHooks(array('eventconfidentialitydao'));
        $parameters = array();
        $reshook = $hookmanager->executeHooks('addEventConfidentialityElementOrigin', $parameters); // Note that $action and $object may have been
        if ($reshook) $element_origin_list = array_merge($element_origin_list, $hookmanager->resArray);

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
                'colspan' => 2,
            ),
            'is_require' => true,
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

        $this->fields['mode'] = array(
            'name' => 'mode',
            'label' => 'EventConfidentialityMode',
            'type' => 'select',
            'options' => array(
                0 => $langs->trans('EventConfidentialityModeVisible'),
                1 => $langs->trans('EventConfidentialityModeBlurred'),
                2 => $langs->trans('EventConfidentialityModeHidden'),
            ),
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
                'external' => $fieldsValue['external'],
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
}