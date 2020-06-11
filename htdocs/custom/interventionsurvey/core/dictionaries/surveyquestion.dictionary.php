<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 * \file        core/dictionaries/surveyquestion.dictionary.php
 * \ingroup     interventionsurvey
 * \brief       Class of the dictionary Question
 */

dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for SurveyQuestionDictionary
 */
class SurveyQuestionDictionary extends Dictionary
{
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
    public $familyPosition = 4;

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
    public $nameLabel = 'InterventionSurveyQuestionDictionaryName';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_intervention_survey_question';

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
            'label'      => 'InterventionSurveyQuestionTextOrderFieldLabel',
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
            'label'      => 'InterventionSurveyQuestionIdentifierFieldLabel',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => false,
            'help'       => 'InterventionSurveyQuestionIdentifierFieldHelp'
        ),
        'label' => array(
            'name'       => 'label',
            'label'      => 'InterventionSurveyQuestionLabelFieldLabel',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => true,
        ),
        'mandatory_answer' => array(
            'name'       => 'mandatory_answer',
            'label'      => 'InterventionSurveyQuestionMandatoryAnswerDictionaryField',
            'type'       => 'boolean',
            'td_input' => array(
                'positionLine' => 0,
            ),
        ),
        'answers' => array(),
        'extrafields' => array(),
        'bloc_question' => array(),
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
     * @var bool    Is multi entity (false = partaged, true = by entity)
     */
    public $is_multi_entity = true;

    /**
	 * Initialize the dictionary
	 *
     * @return  void
	 */
	protected function initialize()
    {
        $this->fields['answers'] = array(
            'name' => 'answers',
            'label' => 'InterventionSurveyAnswerDictionaryLabel',
            'type' => 'chkbxlst',
            'options' => 'c_intervention_survey_answer:identifier|label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'label_separator' => ' - ',
            'td_input' => array(
                'moreAttributes' => 'width="100%"',
                'positionLine' => 1,
            ),
            'is_require' => false,
        );

        $element_type = 'interventionsurvey_surveyquestion';
        $extrafields = new ExtraFields($this->db);
        $extralabels = $extrafields->fetch_name_optionals_label($element_type);

        $this->fields['extrafields'] = array(
            'name' => 'extrafields',
            'label' => 'InterventionSurveyQuestionExtrafieldDictionary',
            'type' => 'checkbox',
            'options' => $extrafields->attributes[$element_type]['label'],
            'td_input' => array(
                'moreAttributes' => 'width="100%"',
                'positionLine' => 2,
            ),
        );

        $this->fields['bloc_question'] = array(
            'name' => 'bloc_question',
            'label' => 'InterventionSurveyQuestionIsUsedInTheseBlocQuestionDictionary',
            'type' => 'chkbxlst',
            'options' => 'c_intervention_survey_bloc_question:identifier|label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'association_table' => 'c_intervention_survey_bloc_question_cbl_questions:fk_target:fk_line',
            'td_input' => array(
                'moreAttributes' => 'width="100%"',
                'positionLine' => 3,
            ),
            'td_output' => array(
                'moreAttributes' => 'width="40%"'
            )
        );
    }
}
