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
 * \file        class/surveypart.class.php
 * \ingroup     interventionsurvey
 * \brief       This file is a CRUD class file for surveyPart (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
dol_include_once('/interventionsurvey/class/surveyblocquestion.class.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey.helper.php');

/**
 * Class for surveyPart
 */
class SurveyPart extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'surveypart';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'interventionsurvey_surveypart';

    /**
     * @var int  Does surveypart support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for surveypart. Must be the part after the 'object_' into object_surveypart.png
     */
    public $picto = 'surveypart@interventionsurvey';


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
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2,),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2,),
        'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
        'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2,),
        'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'position' => 1000, 'notnull' => -1, 'visible' => -2,),
        'fk_fichinter' => array('type' => 'integer:Fichinter:fichinter/class/fichinter.class.php', 'label' => 'FichInterLinked', 'enabled' => 1, 'position' => 50, 'notnull' => 1, 'visible' => -1, 'index' => 1,),
        'fk_identifier_type' => array('type' => 'varchar(50)', 'label' => 'PolymorphicIdentifierType', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => -2, 'index' => 1,),
        'fk_identifier_value' => array('type' => 'integer', 'label' => 'PolymorphicIdentifierId', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => -2, 'index' => 1,),
        'label' => array('type' => 'text', 'label' => 'Part title', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => 1, 'searchall' => 1,),
        'position' => array('type' => 'integer', 'label' => 'Order inside survey', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => -2,),
    );
    public $rowid;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;
    public $fk_fichinter;
    public $fk_identifier_type;
    public $fk_identifier_value;
    public $label;
    public $position;
    public $blocs;
    // END MODULEBUILDER PROPERTIES

    /**
     * @var object  parent interventionsurvey object
     */

    public $fichinter;


    // If this object has a subtable with lines

    /**
     * @var int    Name of subtable line
     */
    public $table_element_line = 'interventionsurvey_surveyblocquestion';

    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    public $fk_element = 'fk_survey_part';

    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    public $class_element_line = 'SurveyBlocQuestion';

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
        'id'=>'','label'=>'','fk_identifier_type'=>'',"fk_identifier_value"=>'','blocs'=>''
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
        /*if ($user->rights->interventionsurvey->surveypart->read) {
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
    public function fetch($id, $ref = null, &$parent = null)
    {
        if (isset($parent)) {
            $this->fichinter = $parent;
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
    public function fetchLines(&$parent = null)
    {
        if (isset($parent)) {
            $this->fichinter = $parent;
        }
        unset($this->blocs);
        $this->blocs = array();
        $result = interventionSurveyFetchLinesCommon(" ORDER BY position ASC", "SurveyBlocQuestion", $this->blocs, $this);
        foreach ($this->blocs as $bloc) {
            $bloc->fetch_optionals();
        }
        return $result;
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
    public function delete(User &$user, $notrigger = false, bool $disableDeletableBlocCheck = false)
    {
        $this->db->begin();
        $atLeastOneBlocHasNotBeenDeleted = false;
        $errors = array();
        $errors = array_merge($errors, $this->errors);
        if (empty($errors)) {
            foreach ($this->blocs as $bloc) {
                if($bloc->delete($user, $notrigger, $disableDeletableBlocCheck) <= 0){
                    $atLeastOneBlocHasNotBeenDeleted = true;
                };
                $errors = array_merge($errors, $bloc->errors ?? array());
            }
        }
        if(!$atLeastOneBlocHasNotBeenDeleted){
            $this->deleteCommon($user, $notrigger);
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
     * Load survey in memory from the given array of survey parts
     *
     */

    public function setVarsFromFetchObj(&$obj, &$parent = null, bool $forceId = false)
    {
        if(!is_object($obj)){
            $obj = json_decode(json_encode($obj));
        }
        parent::setVarsFromFetchObj($obj);
        if (isset($parent)) {
            $this->fichinter = $parent;
        }

        if(!$this->fk_fichinter && $this->fichinter){
            $this->fk_fichinter = $this->fichinter->id;
        }

        if($forceId && $obj->id){
            $this->id = $obj->id;
        }

        $this->blocs = array();
        if (isset($obj->blocs)) {
            foreach ($obj->blocs as $blocObj) {
                $bloc = new SurveyBlocQuestion($this->db);
                $bloc->setVarsFromFetchObj($blocObj, $this, $forceId);
                $bloc->fk_surveypart = $this->id;
                $this->blocs[] = $bloc;
            }
        }
    }

    /**
     * Fetch Parent object
     */

    public function fetchParent()
    {
        fetchParentCommon("InterventionSurvey", $this->fk_fichinter, $this->fichinter, $this->db);
    }

    /**
     *
     * Save
     *
     *
     */

    public function save(&$user, $fk_fichinter = NULL, $noSurveyReadOnlyCheck = false)
    {
        global $langs;
        $this->db->begin();

        if (isset($fk_fichinter)) {
            $this->fk_fichinter = $fk_fichinter;
        }

        if ($this->is_survey_read_only() && !$noSurveyReadOnlyCheck) {
            $this->errors[] = $langs->trans('InterventionSurveyReadOnlyMode');
            $this->db->rollback();
            return -1;
        }
        if ($this->id && $this->id > 0) {
            $this->update($user);
        } else {
            $this->create($user);
        }

        if (empty($this->errors)) {
            foreach ($this->blocs as $position => $bloc) {
                $bloc->position = $position;
                $bloc->save($user, $this->id, $noSurveyReadOnlyCheck);
                $this->errors = array_merge($this->errors, $bloc->errors);
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
     * Check if we are not in readonly mode
     *
     */
    public function is_survey_read_only()
    {
        $this->fetchParent();
        return $this->fichinter->is_survey_read_only();
    }

    /**
     *
     * Method to check if there are missing data on the survey
     *
     */

    public function areDataValid()
    {
        $result = true;
        foreach ($this->blocs as $bloc) {
            if (!$bloc->areDataValid()) {
                $result = false;
                break;
            }
        }
        return $result;
    }

     /**
     *
     * Merge current InterventionSurvey with a given InterventionSurvey
     *
     */

    public function mergeWithFollowingData(User &$user, self &$newSurveyPart, bool $saveWholeObjectToBdd = false, int $position = null, $noTrigger = false){

        $this->db->begin();
        //We update property for this object
        //BEGIN
        $this->position = $position;
        //END

        //We begin property update for subobject

        $parameters = array(
            "blocs"=>array(
                "identifierPropertiesName" => array("id"),
                "mergeSubItemNameMethod" => "mergeWithFollowingData"),
        );

        $errors = mergeSubItemFromObject($user, $this, $newSurveyPart, $parameters, false, $noTrigger);
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
}
