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
 * \file        class/surveyblocstatus.class.php
 * \ingroup     interventionsurvey
 * \brief       This file is a CRUD class file for surveyBlocStatus (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/interventionsurvey/class/surveyblocstatuspredefinedtext.class.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey.helper.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey.cache.lib.php');


/**
 * Class for surveyBlocStatus
 */
class SurveyBlocStatus extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'surveyblocstatus';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'interventionsurvey_surveyblocstatus';

    /**
     * @var int  Does surveyblocstatus support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for surveyblocstatus. Must be the part after the 'object_' into object_surveyblocstatus.png
     */
    public $picto = 'surveyblocstatus@interventionsurvey';


    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CANCELED = 9;


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
        'fk_c_survey_bloc_status' => array('type' => 'integer:SurveyBlocStatusDictionary:interventionsurvey/core/dictionaries/surveyblocstatus.dictionary.php', 'label' => 'Link to the dictionnary', 'enabled' => 1, 'position' => 5, 'notnull' => 0, 'visible' => -1,),
        'fk_surveyblocquestion' => array('type' => 'integer:SurveyBlocQuestion:interventionsurvey/class/surveyblocquestion.class.php', 'label' => 'link to the relative survey bloc question', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 3,),
        'position' => array('type' => 'integer', 'label' => 'order', 'enabled' => 1, 'position' => 15, 'notnull' => 0, 'visible' => 3,),
        'label' => array('type' => 'text', 'label' => 'Texte du status', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 3,),
        'color' => array('type' => 'varchar(10)', 'label' => 'Couleur sur le pdf', 'enabled' => 1, 'position' => 35, 'notnull' => 0, 'visible' => 3,),
        'mandatory_justification' => array('type' => 'boolean', 'label' => 'Indicate if the related bloc must contains a justification', 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => -1,),
        'deactivate_bloc' => array('type' => 'boolean', 'label' => 'Indicate if choosing this status desactives current bloc', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => -1,),
        'consider_as_positive' => array('type' => 'boolean', 'label' => 'Indicate if choosing this status should be considered as positive for autocompleting', 'enabled' => 1, 'position' => 51, 'notnull' => 0, 'visible' => -1,),
    );
    public $rowid;
    public $fk_c_survey_bloc_status;
    public $fk_surveyblocquestion;
    public $position;
    public $label;
    public $color;
    public $mandatory_justification;
    public $deactivate_bloc;
    public $consider_as_positive;
    public $predefined_texts;
    // END MODULEBUILDER PROPERTIES

    /**
     * @var object  parent intervention survey bloc question object
     */
    public $surveyBlocQuestion;

    // If this object has a subtable with lines

    /**
     * @var int    Name of subtable line
     */
    //public $table_element_line = 'interventionsurvey_surveyblocstatusline';

    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    //public $fk_element = 'fk_surveyblocstatus';

    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    //public $class_element_line = 'surveyBlocStatusline';

    /**
     * @var array	List of child tables. To test if we can delete object.
     */
    //protected $childtables=array();

    /**
     * @var array	List of child tables. To know object to delete on cascade.
     */
    //protected $childtablesoncascade=array('interventionsurvey_surveyblocstatusdet');

    /**
     * @var surveyBlocStatusLine[]     Array of subtable lines
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
        'id'=>'','label'=>'','predefined_texts'=>'','color'=>'', 'mandatory_justification'=>'', 'deactivate_bloc'=>'', 'consider_as_positive'=>''
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
     * Array of cache data for massive api call
     * @var array
     * array('surveyBlocStatusId'=>objectOfSqlResult))
     */

    static public $DB_CACHE = array();

    /**
     * Array of cache data for massive api call
     * @var array
     * array('surveyBlocQuestionId'=>array('surveyBlocStatusId'=>true)))
     */

    static public $DB_CACHE_FROM_SURVEYBLOCQUESTION = array();

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
        /*if ($user->rights->interventionsurvey->surveyblocstatus->read) {
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
    public function fetch($id, $ref = null, &$parent)
    {
        if ($parent) {
            $this->bloc = $parent;
        }
        $result = $this->fetchCommon($id, $ref);
        if ($result > 0) {
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
        $this->predefined_texts = array();
        $result = interventionSurveyFetchCommonLineWithCache(" ORDER BY position ASC", "SurveyBlocStatusPredefinedText", $this->predefined_texts, $this, SurveyBlocStatusPredefinedText::$DB_CACHE_FROM_SURVEYBLOCSTATUS, SurveyBlocStatusPredefinedText::$DB_CACHE, $forceDataFromCache);
        foreach ($this->predefined_texts as $predefined_text) {
            $predefined_text->surveyBlocStatus = $this;
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
        if(!is_object($obj)){
            $obj = json_decode(json_encode($obj));
        }

        parent::setVarsFromFetchObj($obj);
        if ($parent) {
            $this->surveyBlocQuestion = $parent;
        }

        if(!$this->fk_surveyblocquestion && $this->surveyBlocQuestion){
            $this->fk_surveyblocquestion = $this->surveyBlocQuestion->id;
        }

        if($forceId && $obj->id){
            $this->id = $obj->id;
        }

        if($obj->c_rowid){
            $this->fk_c_survey_bloc_status = $obj->c_rowid;
        }
        $this->predefined_texts = array();
        if (isset($obj->predefined_texts)) {
            foreach ($obj->predefined_texts as $predefined_textObj) {
                $predefined_text = new SurveyBlocStatusPredefinedText($this->db);
                $predefined_text->setVarsFromFetchObj($predefined_textObj, $this, $forceId);
                $predefined_text->fk_surveyblocstatus = $this->id;
                $this->predefined_texts[] = $predefined_text;
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
        $errors = array();
        $errors = array_merge($errors, $this->errors);
        if (empty($errors)) {
            foreach ($this->predefined_texts as $predefined_text) {
                $predefined_text->delete($user, $notrigger);
                $errors = array_merge($errors, $predefined_text->errors ?? array());
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
     *
     * Save
     *
     *
     */

    public function save(&$user, $fk_surveyblocquestion = NULL, $noSurveyReadOnlyCheck = null, $notrigger = true)
    {
        global $langs;

        $this->db->begin();
        if (isset($fk_surveyblocquestion)) {
            $this->fk_surveyblocquestion = $fk_surveyblocquestion;
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
            foreach ($this->predefined_texts as $position => $predefined_text) {
                $predefined_text->position = $position;
                $predefined_text->save($user, $this->id, $noSurveyReadOnlyCheck, $notrigger);
                $this->errors = array_merge($this->errors, $predefined_text->errors);
            }
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
     * Fetch Parent object
     */

    public function fetchParent()
    {
        fetchParentCommon("SurveyBlocQuestion", $this->fk_surveyblocquestion, $this->surveyBlocQuestion,$this->db);
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
     *
     * Merge current InterventionSurvey with a given InterventionSurvey
     *
     */

    public function mergeWithFollowingData(User &$user, self &$newSurveyBlocStatus, bool $saveWholeObjectToBdd = false, int $position = null, $noTrigger = false){

        $this->db->begin();
        //We update property for this object
        //BEGIN
        //END

        //We begin property update for subobject

        $parameters = array(
            "predefined_texts"=>array(
                "identifierPropertiesName" => array("id"),
                "mergeSubItemNameMethod" => "mergeWithFollowingData"),
        );

        $errors = mergeSubItemFromObject($user, $this, $newSurveyBlocStatus, $parameters, false, $noTrigger);
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

    public static function fillCacheFromParentObjectIds($arrayOfSurveyBlocQuestionIds) {
        global $db;
        $object = new self($db);
        commonLoadCacheForItemWithFollowingSqlFilter($object, $db, self::$DB_CACHE, ' WHERE fk_surveyblocquestion IN ( ' . implode(",", $arrayOfSurveyBlocQuestionIds) . ')');
        commonLoadCacheIdForLinkedObject(self::$DB_CACHE_FROM_SURVEYBLOCQUESTION, 'fk_surveyblocquestion', self::$DB_CACHE, $arrayOfSurveyBlocQuestionIds);
        $surveyBlocStatusIds = getCachedElementIds(self::$DB_CACHE);
        SurveyBlocStatusPredefinedText::fillCacheFromParentObjectIds($surveyBlocStatusIds);
    }
}
