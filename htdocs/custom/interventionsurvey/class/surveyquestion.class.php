<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/surveyquestion.class.php
 * \ingroup     interventionsurvey
 * \brief       This file is a CRUD class file for surveyQuestion (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/interventionsurvey/class/surveyanswer.class.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey.helper.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey.cache.lib.php');

/**
 * Class for surveyQuestion
 */
class SurveyQuestion extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'surveyquestion';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'interventionsurvey_surveyquestion';

    /**
     * @var int  Does surveyquestion support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for surveyquestion. Must be the part after the 'object_' into object_surveyquestion.png
     */
    public $picto = 'surveyquestion@interventionsurvey';


    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CANCELED = 9;

        /**
     * Array of cache data for massive api call
     * @var array
     * array('surveyQuestionId'=>objectOfSqlResult))
     */

    static public $DB_CACHE = array();

    /**
     * Array of cache data for extrafield values for massive api call
     * @var array
     * array('surveyQuestionId'=>cacheArrayOptions))
     */

    static public $DB_CACHE_EXTRAFIELDS = array();

    /**
     * Array of cache data for massive api call
     * @var array
     * array('surveyBlocQuestionId'=>array('surveyQuestionId'=>true)))
     */

    static public $DB_CACHE_FROM_SURVEYBLOCQUESTION = array();

    /**
     *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
     *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     *  'label' the translation key.
     *  'enabled' is a condition when the field must be managed.
     *  'position' is the sort order of field.
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
     *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
     *  'css' is the CSS style to use on field. For example: 'maxwidth200'
     *  'help' is a string visible as a tooltip on field
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */

    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => -1, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
        'label' => array('type' => 'text', 'label' => 'Lable', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 3,),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2,),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2,),
        'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
        'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2,),
        'fk_surveyblocquestion' => array('type' => 'integer:SurveyQuestion:interventionsurvey/class/surveyblocquestion.class.php', 'label' => 'Id of the parent bloc Question', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => -1,),
        'fk_c_survey_question' => array('type' => 'integer:SurveyQuestionDictionary:interventionsurvey/core/dictionaries/surveyquestion.dictionary.php', 'label' => 'Link to the corresponding question inside dictionnary', 'enabled' => 1, 'position' => 15, 'notnull' => 0, 'visible' => -1,),
        'extrafields' => array('type' => 'array', 'label' => 'List of extrafields for this bloc', 'enabled' => 1, 'position' => 33, 'notnull' => 0, 'visible' => 3,),
        'position' => array('type' => 'integer', 'label' => 'order', 'enabled' => 1, 'position' => 20, 'notnull' => 0, 'visible' => -1,),
        'fk_chosen_answer' => array('type' => 'integer:SurveyAnswer:interventionsurvey/class/surveyanswer.class.php', 'label' => 'Chosen answer for this question', 'enabled' => 1, 'position' => 35, 'notnull' => 0, 'visible' => 3,),
        'mandatory_answer' => array('type' => 'boolean', 'label' => 'Is an answer mandatory', 'enabled' => 1, 'position' => 31, 'notnull' => 0, 'visible' => 3,),
        'fk_chosen_answer_predefined_text' => array('type' => 'array', 'label' => 'Stringify array (split by comma) of predefined used text id', 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => -1,),
        'justification_text' => array('type' => 'text', 'label' => 'Justification text', 'enabled' => 1, 'position' => 38, 'notnull' => 0, 'visible' => 3,),
        'helper_text' => array('type' => 'text', 'label' => 'Helper text', 'enabled' => 1, 'position' => 39, 'notnull' => 0, 'visible' => 3,),
    );
    public $rowid;
    public $label;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $fk_surveyblocquestion;
    public $fk_c_survey_question;
    public $extrafields;
    public $position;
    public $array_options;
    public $fk_chosen_answer;
    public $mandatory_answer;
    public $fk_chosen_answer_predefined_text;
    public $justification_text;
    public $chosen_answer;
    public $helper_text;
    public $answers;
    // END MODULEBUILDER PROPERTIES

    public static $extrafields_cache;
    public static $extrafields_label_cache;
    public $surveyBlocQuestion;

    // If this object has a subtable with lines

    /**
     * @var int    Name of subtable line
     */
    //public $table_element_line = 'interventionsurvey_surveyquestionline';

    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    //public $fk_element = 'fk_surveyquestion';

    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    //public $class_element_line = 'surveyQuestionline';

    /**
     * @var array	List of child tables. To test if we can delete object.
     */
    //protected $childtables=array();

    /**
     * @var array	List of child tables. To know object to delete on cascade.
     */
    //protected $childtablesoncascade=array('interventionsurvey_surveyquestiondet');

    /**
     * @var surveyQuestionLine[]     Array of subtable lines
     */
    //public $lines = array();
/**
     * Array of whitelist of properties keys for this object used for the API
     * @var  array
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES = array(
        'id'=>'','label'=>'','fk_chosen_answer'=>'','justification_text'=>'','array_options'=>'','answers'=>'',
        'fk_chosen_answer_predefined_text'=>'','chosen_answer'=>'','mandatory_answer'=>'', 'extrafields'=>'', 'helper_text'=> ''
    );

    /**
     * Array of whitelist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      if empty array then equal at $api_whitelist_of_properties
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array();

    /**
     * Array of blacklist of properties keys for this object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES = array();

    /**
     * Array of blacklist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT = array();



    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB &$db = null)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

        // Example to show how to set values of fields definition dynamically
        /*if ($user->rights->interventionsurvey->surveyquestion->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
        $this->fetchExtraFieldsInfo();
        $this->errors = array();
    }

    /**
     *
     * Override getFieldList to change method accessibility
     *
     */
    public function getFieldList(){
        return parent::getFieldList();
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create(User &$user, $notrigger = false)
    {
        return $this->createCommon($user, $notrigger);
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id   Id object
     * @param string $ref  Ref
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null, &$parent = null)
    {
        $result = $this->fetchCommon($id, $ref);
        if ($parent) {
            $this->bloc = $parent;
        }
        if ($result > 0) {
            $this->fetch_optionals();
            $this->fetchLines();
        }
        return $result;
    }
    /**
     * Load object lines in memory from the database
     *
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetchLines($forceDataFromCache = false)
    {
        $this->answers = array();
        $this->chosen_answer = null;

        $result = interventionSurveyFetchCommonLineWithCache(" ORDER BY position ASC", "SurveyAnswer", $this->answers, $this, SurveyAnswer::$DB_CACHE_FROM_SURVEYQUESTION, SurveyAnswer::$DB_CACHE, $forceDataFromCache);

        if ($this->fk_chosen_answer) {
            $this->getChosenAnswer();
        }
        return $result;
    }

    /**
     *
     * Load survey in memory from the given array of survey parts
     *
     */

    public function setVarsFromFetchObj(&$obj, &$parent = null, bool $forceId = false)
    {
         //To get a php stdClass obj
        if(!is_object($obj)){
            $obj = json_decode(json_encode($obj));
        }
        $this->answers = array();
        if ($parent) {
            $this->surveyBlocQuestion = $parent;
        }

        if(!$this->fk_surveyblocquestion && $this->surveyBlocQuestion){
            $this->fk_surveyblocquestion = $this->surveyBlocQuestion->id;
        }

        parent::setVarsFromFetchObj($obj);

        if(is_array($obj->extrafields)){
            $this->extrafields = $obj->extrafields;
        }

        if($obj->array_options){
            $this->array_options = $obj->array_options;
        }

        if($forceId && $obj->id){
            $this->id = $obj->id;
        }

        if($obj->c_rowid){
            $this->fk_c_survey_question = $obj->c_rowid;
        }

        if (isset($obj->answers)) {
            foreach ($obj->answers as $answerObj) {
                $answer = new SurveyAnswer($this->db);
                $answer->setVarsFromFetchObj($answerObj, $this, $forceId);
                $answer->fk_surveyquestion = $this->id;
                $this->answers[] = $answer;
            }
        }
    }

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder    Sort Order
     * @param  string      $sortfield    Sort field
     * @param  int         $limit        limit
     * @param  int         $offset       Offset
     * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
     * @param  string      $filtermode   Filter mode (AND or OR)
     * @return array|int                 int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = 'SELECT ';
        $sql .= $this->getFieldList();
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
        else $sql .= ' WHERE 1 = 1';
        // Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key . '=' . $value;
                } elseif (strpos($key, 'date') !== false) {
                    $sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } else {
                    $sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
                }
            }
        }
        if (count($sqlwhere) > 0) {
            $sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= ' ' . $this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < min($limit, $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new self($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function update(User &$user, $notrigger = false)
    {
        $fieldsToRemove = array('date_creation', 'fk_user_creat');
        $saveFields = $this->fields;
        foreach($fieldsToRemove as $field){
            unset($this->fields[$field]);
        }
        $result = $this->updateCommon($user, $notrigger);
        $this->fields = $saveFields;
        return $result;
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User &$user, $notrigger = true)
    {
        $this->db->begin();
        $this->deleteCommon($user, $notrigger);
        $this->deleteExtraFields($user);
        $errors = array();
        $errors = array_merge($errors, $this->errors);
        if (empty($errors)) {
            foreach ($this->answers as $answer) {
                $answer->delete($user, $notrigger);
                $errors = array_merge($errors, $answer->errors ?? array());
            }
        }
        if (empty($errors)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            $this->errors = $errors;
            return -1;
        }
    }

    /**
     *  Delete a line of object in database
     *
     *	@param  User	$user       User that delete
     *  @param	int		$idline		Id of line to delete
     *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
     *  @return int         		>0 if OK, <0 if KO
     */
    public function deleteLine(User $user, $idline, $notrigger = false)
    {
        if ($this->status < 0) {
            $this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
            return -2;
        }

        return $this->deleteLineCommon($user, $idline, $notrigger);
    }


    /**
     * Initialise object with example values
     * Id must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        $this->initAsSpecimenCommon();
    }

    /**
     * Fetch Parent object
     */

    public function fetchParent()
    {
        fetchParentCommon("SurveyBlocQuestion", $this->fk_surveyblocquestion, $this->surveyBlocQuestion, $this->db);
    }

    /**
     *
     * Save
     *
     *
     */

    public function save(&$user, $fk_surveyblocquestion = NULL, $noSurveyReadOnlyCheck = false, $notrigger = true)
    {
        global $langs, $conf;
        $this->db->begin();

        if (isset($fk_surveyblocquestion)) {
            $this->fk_surveyblocquestion = $fk_surveyblocquestion;
        }

        if($this->fk_chosen_answer){
            $this->getChosenAnswer();
            if(!$this->chosen_answer){
                $this->fk_chosen_answer = null;
            }
        }

        if (!$noSurveyReadOnlyCheck && $this->is_survey_read_only()) {
            $this->errors[] = $langs->trans('InterventionSurveyReadOnlyMode');
            $this->db->rollback();
            return -1;
        }

        if ($this->id && $this->id>0) {
            $this->update($user, $notrigger);
        } else {
            $this->create($user, $notrigger);
        }
        if (empty($this->errors)) {
            foreach ($this->answers as $position => $answer) {
                $answer->position = $position;
                $answer->save($user, $this->id, $noSurveyReadOnlyCheck, $notrigger);
                $this->errors = array_merge($this->errors, $answer->errors);
            }
        }

        if($this->chosen_answer){
            if($this->fk_chosen_answer !=$this->chosen_answer->id){
                $this->fk_chosen_answer = $this->chosen_answer->id;
                $this->update($user, $notrigger);
            }
        }

        if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
            $this->insertExtraFields();
        }
        if (empty($this->errors)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
    /**
     * Get chosen answer or an empty object
     */

    public function getChosenAnswer()
    {
        if ($this->chosen_answer && $this->chosen_answer->id == $this->fk_chosen_answer) {
            return $this->chosen_answer;
        }
        $this->chosen_answer = getItemFromThisArray($this->answers, array('id' => $this->fk_chosen_answer));
        return $this->chosen_answer;
    }


    /**
     * Is answer properly chosen ?
     */

    public function IsAnswerChosen()
    {
        $this->getChosenAnswer();
        return !!(!$this->mandatory_answer || ($this->chosen_answer && $this->chosen_answer->id));
    }

    /**
     * Is justification text for answer properly set ?
     */

    public function IsJustificationTextProperlySet()
    {
        $answer = $this->getChosenAnswer();
        return !!(!$answer->mandatory_justification || $this->justification_text);
    }

    /**
     *
     * Check that extrafields are properly set
     */

    public function checkExtrafieldProperlySet()
    {
        global $langs;
        $errors = array();
        foreach (self::$extrafields_cache->attributes[$this->table_element]['required'] as $key => $val) {
            if (!empty($val) && in_array($key, $this->extrafields) && empty($this->array_options['options_' . $key])) {
                $errors[] = $langs->trans(
                    'InterventionSurveyQuestionMissingExtrafield',
                    self::$extrafields_cache->attributes[$this->table_element]['label'][$key],
                    $this->label,
                    $this->id
                );
            }
        }
        $this->errors = array_merge($this->errors, $errors);
        return empty($errors);
    }


    /**
     *
     * Is Bloc desactivated ?
     */

    public function isBlocDesactivated()
    {
        return isset($this->bloc) ? $this->bloc->isBlocDesactivated() : false;
    }


    /**
     * Are some information missing ?
     */

    public function areDataValid()
    {
        global $langs;
        $errors = array();
        if ($this->isBlocDesactivated()) {
            return true;
        }
        if (!$this->IsAnswerChosen()) {
            $errors[] = $langs->trans('InterventionSurveyMissingAnswer', $this->label, $this->id);
        }
        if (!$this->IsJustificationTextProperlySet()) {
            $errors[] = $langs->trans('InterventionSurveyMissingJustificationAnswer', $this->label, $this->id);
        }
        if (!$this->errors) {
            $this->errors = array();
        }
        $this->errors = array_merge($this->errors, $errors);
        return $this->checkExtrafieldProperlySet() && empty($errors);
    }

    /**
     *
     * Function to fetch and update $extrafields_cache property to save all extrafields for this object
     * There might be a more elegant way of doing it
     *
     */

    public function fetchExtraFieldsInfo()
    {
        if (!isset(self::$extrafields_cache)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            self::$extrafields_cache = new ExtraFields($this->db);
            self::$extrafields_label_cache = self::$extrafields_cache->fetch_name_optionals_label($this->table_element);
        }
    }

    /**
     *	{@inheritdoc}
     */
    function insertExtraFields($trigger = '', $userused = NULL)
    {
        if(is_object($this->array_options)){
            $this->array_options = json_decode(json_encode($this->array_options), true);
        }

        // Clean extra fields
        if (count($this->extrafields) == 0) {
            $this->array_options = array();
        } elseif (is_array($this->array_options)) {
            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key, 8), $this->extrafields)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        // Manage require fields but not selected
        $this->fetchExtraFieldsInfo();
        foreach (self::$extrafields_cache->attributes[$this->table_element]['required'] as $key => $val) {
            if (empty($val) || !empty($this->array_options["options_" . $key])) {
                continue;
            }

            $this->array_options["options_" . $key] = '0'; //We set not required set value
                    }

        $result = parent::insertExtraFields($trigger, $userused);

        return $result;
    }

    /**
     *	{@inheritdoc}
     * Taken from extended intervention module, may be outdated way of implement informations
     */
    function updateExtraField($key)
    {
        if (in_array($key, $this->extrafields)) {
            return parent::updateExtraField($key);
        } else {
            return 0;
        }
    }

    /**
     *	{@inheritdoc}
     * Taken from extended intervention module, may be outdated way of implement informations
     */
    function showOptionals($extrafields, $mode = 'view', $params = NULL, $keysuffix = '', $keyprefix = '', $onetrtd = 0)
    {

        $extrafields_question = clone $extrafields;
        $tmp = array();
        foreach ($extrafields_question->attribute_label as $key => $val) {
            if (!in_array($key, $this->extrafields)) {
                // Old extrafields_question
                unset($extrafields_question->attribute_type[$key]);
                unset($extrafields_question->attribute_size[$key]);
                unset($extrafields_question->attribute_elementtype[$key]);
                unset($extrafields_question->attribute_default[$key]);
                unset($extrafields_question->attribute_computed[$key]);
                unset($extrafields_question->attribute_unique[$key]);
                unset($extrafields_question->attribute_required[$key]);
                unset($extrafields_question->attribute_param[$key]);
                unset($extrafields_question->attribute_pos[$key]);
                unset($extrafields_question->attribute_alwayseditable[$key]);
                unset($extrafields_question->attribute_perms[$key]);
                unset($extrafields_question->attribute_list[$key]);
                unset($extrafields_question->attribute_hidden[$key]);
                unset($extrafields_question->attribute_langfile[$key]);
                unset($extrafields_question->attribute_entityid[$key]);
                unset($extrafields_question->attribute_entitylabel[$key]);

                // New usage
                unset($extrafields_question->attributes[$this->table_element]['type'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['label'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['size'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['elementtype'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['default'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['computed'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['unique'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['required'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['param'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['pos'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['alwayseditable'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['perms'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['list'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['ishidden'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['entityid'][$key]);
                unset($extrafields_question->attributes[$this->table_element]['entitylabel'][$key]);
            } else {
                $tmp[$key] = $val;
            }
        }
        $extrafields_question->attribute_label = $tmp;

        return parent::showOptionals($extrafields_question, $mode, $params, $keysuffix, $keyprefix, $onetrtd);
    }

    /**
     * Check if we are not in readonly mode
     *
     */
    public function is_survey_read_only()
    {
        $this->fetchParent();
        return $this->surveyBlocQuestion->is_survey_read_only();
    }

    /**
     * Check if we this bloc is empty, with no new data provided
     *
     */
    public function is_empty()
    {
        $isEmpty = true;
        $listOfPropertyWhereWeMayFindData = array(
            "justification_text",
            "fk_chosen_answer",
            "fk_chosen_answer_predefined_text"
        );
        foreach ($listOfPropertyWhereWeMayFindData as $property) {
            if (!empty($this->$property)) {
                $isEmpty = false;
                break;
            }
        }

        //Now we check extrafields
        if ($isEmpty) {
            foreach ($this->array_options as $value) {
                if (!empty($value)) {
                    $isEmpty = false;
                    break;
                }
            }
        }
        return $isEmpty;
    }

    /**
     *	{@inheritdoc}
     */
    public function fetch_optionals($rowid = null, $optionsArray = null, $getDataFromCache = false)
    {
        if($getDataFromCache) {
            $this->array_options = is_array(self::$DB_CACHE_EXTRAFIELDS[$this->id]) ? self::$DB_CACHE_EXTRAFIELDS[$this->id] : array();
            $result = 1;
        }
        else {
            $result = parent::fetch_optionals($rowid, $optionsArray);
        }
        if ($result > 0) {
            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key, 8), $this->extrafields)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        return $result;
    }

    /**
     *
     * Merge current InterventionSurvey with a given InterventionSurvey
     *
     */

    public function mergeWithFollowingData(User &$user, self &$newSurveyQuestion, int $position = null, bool $saveWholeObjectToBdd = false, $noTrigger = false){

        $this->db->begin();
        //We update property for this object
        //BEGIN
        $this->array_options = $newSurveyQuestion->array_options;
        $this->fk_chosen_answer = $newSurveyQuestion->fk_chosen_answer;
        $this->fk_chosen_answer_predefined_text = $newSurveyQuestion->fk_chosen_answer_predefined_text;
        $this->justification_text = $newSurveyQuestion->justification_text;
        //END

        //We begin property update for subobject

        $parameters = array(
            "answers"=>array(
                "identifierPropertiesName" => array("id"),
                "mergeSubItemNameMethod" => "mergeWithFollowingData"),
        );

        $errors = mergeSubItemFromObject($user, $this, $newSurveyQuestion, $parameters, false);
        $this->errors = array_merge($this->errors, $errors);

        if($saveWholeObjectToBdd === true) {
            $this->save($user);
        }

         if (empty($this->errors)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Autocomplete survey - we fill data according to user permissions
     */
    function autoComplete()
    {
        $this->errors = array();
        $this->db->begin();
        global $user;
        if(!$user->rights->interventionsurvey->survey->autocomplete){
            return 0;
        }
        if($this->fk_chosen_answer == null || $this->fk_chosen_answer == 0){
            $autocompleteAnswer = &getItemFromThisArray($this->answers, array('consider_as_positive' => 1));
            if($autocompleteAnswer){
                $this->fk_chosen_answer = $autocompleteAnswer->id;
                $this->chosen_answer = $autocompleteAnswer;
            }
        }
        $this->errors = array_merge($this->errors, $this->errors);
        if (empty($this->errors)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public static function fillCacheFromParentObjectIds($arrayOfSurveyBlocQuestionIds) {
        global $db;
        $object = new self($db);
        commonLoadCacheForItemWithFollowingSqlFilter($object, $db, self::$DB_CACHE, ' WHERE fk_surveyblocquestion IN ( ' . implode(",", $arrayOfSurveyBlocQuestionIds) . ')');
        commonLoadCacheIdForLinkedObject(self::$DB_CACHE_FROM_SURVEYBLOCQUESTION, 'fk_surveyblocquestion', self::$DB_CACHE, $arrayOfSurveyBlocQuestionIds);
        $surveyQuestionIds = getCachedElementIds(self::$DB_CACHE);
        commonLoadExtrafieldCacheForItemWithIds($object, $db, self::$DB_CACHE_EXTRAFIELDS, $surveyQuestionIds);
        SurveyAnswer::fillCacheFromParentObjectIds($surveyQuestionIds);
    }
}
