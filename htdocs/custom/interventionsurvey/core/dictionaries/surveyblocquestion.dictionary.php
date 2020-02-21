<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
/* Copyright (C) 2019-2020   Alexis LAURIER      <alexis@alexislaurier.fr>
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
 * \file        core/dictionaries/surveyblocquestion.dictionary.php
 * \ingroup     interventionsurvey
 * \brief       Class of the dictionary Question Block
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for SurveyBlocQuestionDictionary
 */
class SurveyBlocQuestionDictionary extends Dictionary
{
    /**
     * @var int         Version of this dictionary
     */
    public $version = 1;

    /**
     * @var array       List of languages to load
     */
    public $langs = array('interventionsurvey@interventionsurvey');

    /**
     * @var string      Family name of which this dictionary belongs
     */
    public $family = 'interventionSurvey';

    /**
     * @var string      Family label for show in the list, translated if key found
     */
    public $familyLabel = 'ModuleInterventionSurveyName';

    /**
     * @var int         Position of the dictionary into the family
     */
    public $familyPosition = 1;

    /**
     * @var string      Module name of which this dictionary belongs
     */
    public $module = 'interventionsurvey';

    /**
     * @var string      Module name for show in the list, translated if key found
     */
    public $moduleLabel = 'ModuleInterventionSurveyName';

    /**
     * @var string      Name of this dictionary for show in the list, translated if key found
     */
    public $nameLabel = 'InterventionSurveyBlocDictionaryName';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_intervention_survey_bloc_question';

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
        'position' => array(
            'name'       => 'position',
            'label'      => 'InterventionSurveyBlocQuestionOrderDictionaryField',
            'type'       => 'int',
            'database'   => array(
              'length'   => 10,
            ),
            'td_title'  => array (
                'align'  => 'left',
            ),
            'td_output'  => array (
                'align'  => 'left',
            ),
            'td_search'  => array (
                'align'  => 'left',
            ),
            'td_input'  => array (
                'align'  => 'left',
            ),
        ),
        'identifier' => array(
            'name'       => 'identifier',
            'label'      => 'InterventionSurveyBlocQuestionIdentifierFieldLabel',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => false,
            'help'       => 'InterventionSurveyBlocQuestionIdentifierFieldHelp'
        ),
        'label' => array(
            'name'       => 'label',
            'label'      => 'InterventionSurveyBlocQuestionLabelDictionaryField',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => true,
        ),
        'description' => array(
            'name'       => 'description',
            'label'      => 'InterventionSurveyBlocQuestionDescriptionDictionaryField',
            'type'       => 'text',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => false,
            'td_input' => array(
                'positionLine' => 2,
            ),
        ),
        'icon' => array(
            'name'       => 'icon',
            'label'      => 'InterventionSurveyBlocQuestionIconDictionaryField',
            'help'       => 'InterventionSurveyBlocQuestionIconDictionaryHelp',
            'type'       => 'varchar',
            'database'   => array(
                'length'   => 255,
            ),
            'is_require' => true,
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'mandatory_status' => array(
            'name'       => 'mandatory_status',
            'label'      => 'InterventionSurveyBlocQuestionMandatoryStatusDictionaryField',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'label_editable' => array(
            'name'       => 'label_editable',
            'label'      => 'InterventionSurveyBlocQuestionLabelEditableDictionaryField',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'description_editable' => array(
            'name'       => 'description_editable',
            'label'      => 'InterventionSurveyBlocQuestionDescriptionEditableDictionaryField',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'deletable' => array(
            'name'       => 'deletable',
            'label'      => 'InterventionSurveyBlocQuestionDeletableBlocDictionaryField',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'private' => array(
            'name'       => 'private',
            'label'      => 'InterventionSurveyBlocQuestionPrivateBlocDictionaryField',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'bloc_in_general_part' => array(
            'name'       => 'bloc_in_general_part',
            'label'      => "InterventionSurveyQuestionBlocBlocInGeneralPartDictionary",
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 3,
            ),
        ),
        'categories' => array(),
        'status' => array(),
        'questions' => array(),
        'extrafields' => array(),
    );

    /**
     * @var array  List of index for the database
     * array(
     *   'fields'    => array( ... ), // List of field name who constitute this index
     *   'is_unique' => bool,         // Set at true if this index is unique
     * )
     */
    public $indexes = array(
    );

    /**
     * @var array  List of fields/indexes added, updated or deleted for a version
     * array(
     *   'version' => array(
     *     'fields' => array('field_name'=>'a', 'field_name'=>'u', 'field_name'=>'d', ...), // List of field name who is added(a) or updated(u) or deleted(d) for a version
     *     'indexes' => array('idx_number'=>'a', 'idx_number'=>'u', 'idx_number'=>'d', ...), // List of indexes number who is added(a) or updated(u) or deleted(d) for a version
     *   ),
     * )
     */
    public $updates = array(
    );

    /**
     * @var bool    Is multi entity (false = partaged, true = by entity)
     */
    public $is_multi_entity = true;

    /**
     * @var array    Cache of the list of the categories for categorie show output
     */
    public $categories_cache = null;
    /**
     * @var FormInterventionSurvey    Instance of the FormInterventionSurvey
     */
    public $form_intervention_survey = null;

    /**
	 * Load the cache of the list of the categories for categories show output
	 *
     * @return  void
	 */
    public function load_categories()
    {
        if (!isset($this->categories_cache)) {
            $this->load_form_intervention_survey();
            $this->categories_cache = $this->form_intervention_survey->get_categories_array();
        }
    }

    /**
	 * Load the FormInterventionSurvey object for categories show input
	 *
     * @return  void
	 */
    public function load_form_intervention_survey()
    {
        if (!isset($this->form_intervention_survey)) {
            dol_include_once('/interventionsurvey/class/html.forminterventionsurvey.class.php');
            $this->form_intervention_survey = new FormInterventionSurvey($this->db);
        }
    }

    /**
	 * Initialize the dictionary
	 *
     * @return  void
	 */
        protected function initialize()
        {
            $this->fields['types_intervention'] = array(
                'name' => 'types_intervention',
                'label' => 'InterventionSurveyBlocQuestionInterventionTypeField',
                'type' => 'chkbxlst',
                'options' => 'c_extendedintervention_type:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
                'td_output' => array(
                    'moreAttributes' => 'width="50%"',
                ),
                'td_input' => array(
                    'moreAttributes' => 'width="50%"',
                    'positionLine' => 4,
                    'colspan' => 3,
                ),
                'is_require' => false,
            );

            $this->fields['categories'] = array(
                'name' => 'categories',
                'label' => 'InterventionSurveyTagCategoriesInQuestionBlocDictionnary',
                'type' => 'chkbxlst',
                'options' => 'categorie:label:rowid::type=0 and entity IN (' . getEntity( 'category', 1 ) . ')',
                'td_output' => array(
                    'moreAttributes' => 'width="50%"'
                ),
                'td_input' => array(
                    'moreAttributes' => 'width="50%"',
                    'positionLine' => 3,
                ),
            );

            $this->fields['status'] = array(
                'name' => 'status',
                'label' => 'InterventionSurveyBlocStatusInQuestionBlocDictionnary',
                'type' => 'chkbxlst',
                'options' => 'c_intervention_survey_bloc_status:identifier|label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
                'label_separator' => ' - ',
                'td_output' => array(
                    'moreAttributes' => 'width="100%"',
                ),
                'td_input' => array(
                    'moreAttributes' => 'width="100%"',
                    'positionLine' => 4,
                ),
                'is_require' => false,
            );

            $this->fields['questions'] = array(
                'name' => 'questions',
                'label' => 'InterventionSurveyQuestionsInQuestionBlocDictionnary',
                'type' => 'chkbxlst',
                'options' => 'c_intervention_survey_question:identifier|label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
                'label_separator' => ' - ',
                'td_output' => array(
                    'moreAttributes' => 'width="100%"',
                ),
                'td_input' => array(
                    'moreAttributes' => 'width="100%"',
                    'positionLine' => 5,
                ),
                'is_require' => true,
            );

            $table_element = 'interventionsurvey_surveyblocquestion';
            $extrafields = new ExtraFields($this->db);
            $extralabels = $extrafields->fetch_name_optionals_label($table_element);

            $this->fields['extrafields'] = array(
                'name' => 'extrafields',
                'label' => 'InterventionSurveyBlocQuestionExtrafieldDictionary',
                'type' => 'checkbox',
                'options' => $extrafields->attributes[$table_element]['label'],
                'td_output' => array(
                    'moreAttributes' => 'width="100%"',
                ),
                'td_input' => array(
                    'moreAttributes' => 'width="100%"',
                    'positionLine' => 6,
                ),
            );
        }
    }


class SurveyBlocQuestionDictionaryLine extends DictionaryLine
{
    public function checkFieldsValues($fieldsValue)
    {
        global $langs;

        $result = parent::checkFieldsValues($fieldsValue);
        if ($result < 0) {
            return $result;
        }

        return $result;
    }

    /**
     * Return HTML string to put an output field into a page
     *
     * @param   string	$fieldName      Name of the field
     * @param   string	$value          Value to show
     * @return	string					Formatted value
     */
    function showOutputField($fieldName, $value = null)
    {
        if ($fieldName == 'categories') {
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
	function showInputField($fieldName, $value=null, $keyprefix='', $keysuffix='', $objectid=0)
    {
        if ($fieldName == 'categories') {
            $field = $this->dictionary->fields[$fieldName];

            if ($value === null) $value = $this->fields[$fieldName];

            $fieldHtmlName = $keyprefix . $fieldName . $keysuffix;

            $moreClasses = trim($field['show_input']['moreClasses']);
            if (empty($moreClasses)) {
                $moreClasses = ' minwidth100';
            } else {
                $moreClasses = ' ' . $moreClasses;
            }

            $moreAttributes = trim($field['show_input']['moreAttributes']);
            $moreAttributes = !empty($moreAttributes) ? ' ' . $moreAttributes : '';

            if (is_array($value)) {
                $value_arr = $value;
            } else {
                $value_arr = array_filter(explode(',', (string)$value), 'strlen');
            }

            $this->dictionary->load_form_intervention_survey();
            return $this->dictionary->form_intervention_survey->multiselect_categories($fieldHtmlName, $value_arr, '', 0, $moreClasses, 0, '100%', $moreAttributes);
        }

        return parent::showInputField($fieldName, $value, $keyprefix, $keysuffix, $objectid);
    }
}
