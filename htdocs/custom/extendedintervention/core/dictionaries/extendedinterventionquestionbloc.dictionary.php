<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
/* Copyright (C) 2019   Alexis LAURIER      <alexis@alexislaurier.fr>
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
 * \file        core/dictionaries/extendedinterventionquestionbloc.dictionary.php
 * \ingroup     extendedintervention
 * \brief       Class of the dictionary Question Block
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once('/advancedictionaries/class/dictionary.class.php');

/**
 * Class for ExtendedInterventionQuestionBlocDictionary
 */
class ExtendedInterventionQuestionBlocDictionary extends Dictionary
{
    /**
     * @var array       List of languages to load
     */
    public $langs = array('extendedintervention@extendedintervention');

    /**
     * @var string      Family name of which this dictionary belongs
     */
    public $family = 'extendedintervention';

    /**
     * @var string      Family label for show in the list, translated if key found
     */
    public $familyLabel = 'Module163023Name';

    /**
     * @var int         Position of the dictionary into the family
     */
    public $familyPosition = 1;

    /**
     * @var string      Module name of which this dictionary belongs
     */
    public $module = 'extendedintervention';

    /**
     * @var string      Module name for show in the list, translated if key found
     */
    public $moduleLabel = 'Module163023Name';

    /**
     * @var string      Name of this dictionary for show in the list, translated if key found
     */
    public $nameLabel = 'ExtendedInterventionQuestionBlocDictionaryLabel';

    /**
     * @var string      Name of the dictionary table without prefix (ex: c_country)
     */
    public $table_name = 'c_extendedintervention_question_bloc';

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
            'label'      => 'Position',
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
        'code' => array(
            'name'       => 'code',
            'label'      => 'Code',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 16,
            ),
            'is_require' => true,
        ),
        'label' => array(
            'name'       => 'label',
            'label'      => 'Label',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => true,
        ),
        'types_intervention' => array(),
        'categories' => array(),
        'status' => array(),
        'questions' => array(),
        'extra_fields' => array(),
		'icone' => array(
            'name'       => 'icone',
            'label'      => 'Icone',
            'type'       => 'varchar',
            'database'   => array(
              'length'   => 255,
            ),
            'is_require' => true,
        ),
		'title_editable' => array(
            'name'       => 'title_editable',
            'label'      => 'Libellé du Bloc Editable ?',
            'type'       => 'boolean',
        ),
		'bloc_complementary_editable' => array(
            'name'       => 'bloc_complementary_editable',
            'label'      => 'Texte complémentaire du Libellé du bloc Editable ?',
            'type'       => 'boolean',
        ),
		'deletable' => array(
            'name'       => 'deletable',
            'label'      => 'Bloc supprimable ?',
            'type'       => 'boolean',
        ),
		'private_bloc' => array(
            'name'       => 'private_bloc',
            'label'      => 'Bloc privé par défaut ?',
            'type'       => 'boolean',
        ),
		'unique_bloc' => array(
            'name'       => 'unique_bloc',
            'label'      => "Ne générer ce bloc q'une fois ce bloc (non rattaché à un équipement)",
            'type'       => 'boolean',
        ),



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
            'is_unique' => true,
        ),
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
     * @var FormExtendedIntervention    Instance of the FormExtendedIntervention
     */
    public $form_extended_intervention = null;

    /**
	 * Load the cache of the list of the categories for categories show output
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
	 * Load the FormExtendedIntervention object for categories show input
	 *
     * @return  void
	 */
    public function load_form_extended_intervention()
    {
        if (!isset($this->formrequestmanager)) {
            dol_include_once('/extendedintervention/class/html.formextendedintervention.class.php');
            $this->form_extended_intervention = new FormExtendedIntervention($this->db);
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
            'label' => 'ExtendedInterventionQuestionBlocDictionaryType',
            'type' => 'chkbxlst',
            'options' => 'c_extendedintervention_type:label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="50%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="50%"',
                'positionLine' => 1,
                'colspan' => 2,
            ),
            'is_require' => true,
        );

        $this->fields['categories'] = array(
            'name' => 'categories',
            'label' => 'Categories',
            'type' => 'chkbxlst',
            'options' => 'categorie:label:rowid::type=0 and entity IN (' . getEntity( 'category', 1 ) . ')',
            'td_output' => array(
                'moreAttributes' => 'width="50%"'
            ),
            'td_input' => array(
                'moreAttributes' => 'width="50%"',
                'positionLine' => 1,
            ),
        );

        $this->fields['status'] = array(
            'name' => 'status',
            'label' => 'ExtendedInterventionQuestionBlocDictionaryStatus',
            'type' => 'chkbxlst',
            'options' => 'c_extendedintervention_status_qb:code|label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'label_separator' => ' - ',
            'td_output' => array(
                'moreAttributes' => 'width="100%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="100%"',
                'positionLine' => 2,
            ),
            'is_require' => true,
        );

        $this->fields['questions'] = array(
            'name' => 'questions',
            'label' => 'ExtendedInterventionQuestionBlocDictionaryQuestions',
            'type' => 'chkbxlst',
            'options' => 'c_extendedintervention_question:code|label:rowid::active=1 and entity IN (' . getEntity('dictionary', 1) . ')',
            'label_separator' => ' - ',
            'td_output' => array(
                'moreAttributes' => 'width="100%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="100%"',
                'positionLine' => 3,
            ),
            'is_require' => true,
        );

        $element_type = 'extendedintervention_question_bloc';
        $extrafields = new ExtraFields($this->db);
        $extralabels = $extrafields->fetch_name_optionals_label($element_type);

        $this->fields['extra_fields'] = array(
            'name' => 'extra_fields',
            'label' => 'ExtendedInterventionQuestionBlocDictionaryExtraFields',
            'type' => 'checkbox',
            'options' => $extrafields->attributes[$element_type]['label'],
            'td_output' => array(
                'moreAttributes' => 'width="100%"',
            ),
            'td_input' => array(
                'moreAttributes' => 'width="100%"',
                'positionLine' => 4,
            ),
        );
    }
}


class ExtendedInterventionQuestionBlocDictionaryLine extends DictionaryLine
{
    public function checkFieldsValues($fieldsValue)
    {
        global $langs;

        $result = parent::checkFieldsValues($fieldsValue);
        if ($result < 0) {
            return $result;
        }

        /*$res = $this->dictionary->getCodeFromFilter('{{rowid}}', array(
            'types_intervention' => explode(',', $fieldsValue['types_intervention']),
            'categories' => (!empty($fieldsValue['categories']) ? explode(',', $fieldsValue['categories']) : array())
        ));
        if (($res > 0 && $res != $this->id) || $res == -1) {
            $this->errors[] = $langs->trans('ExtendedInterventionErrorTypeWithCategoryDuplicated');
            return -1;
        } elseif ($res == -2) {
            $this->errors[] = $this->dictionary->error;
            return $res;
        }*/

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

            $this->dictionary->load_form_extended_intervention();
            return $this->dictionary->form_extended_intervention->multiselect_categories($fieldHtmlName, $value_arr, '', 0, $moreClasses, 0, '100%', $moreAttributes);
        }

        return parent::showInputField($fieldName, $value, $keyprefix, $keysuffix, $objectid);
    }
}
