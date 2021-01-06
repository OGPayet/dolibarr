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
 * \file        class/surveyblocquestion.class.php
 * \ingroup     interventionsurvey
 * \brief       This file is a CRUD class file for surveyBlocQuestion (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/interventionsurvey/lib/interventionsurvey.lib.php');
dol_include_once('/interventionsurvey/class/surveyblocstatus.class.php');
dol_include_once('/interventionsurvey/class/surveyquestion.class.php');

//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for surveyBlocQuestion
 */
class SurveyBlocQuestion extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'surveyblocquestion';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'interventionsurvey_surveyblocquestion';

    /**
     * @var int  Does surveyblocquestion support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for surveyblocquestion. Must be the part after the 'object_' into object_surveyblocquestion.png
     */
    public $picto = 'surveyblocquestion@interventionsurvey';


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
        'label' => array('type' => 'text', 'label' => 'Title of this bloc of question', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 3,),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2,),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2,),
        'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
        'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2,),
        'mandatory_status' => array('type' => 'boolean', 'label' => 'A status must be set', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => -1,),
        'justification_text' => array('type' => 'text', 'label' => 'Justification regarding status', 'enabled' => 1, 'position' => 35, 'notnull' => 0, 'visible' => 3,),
        'description' => array('type' => 'text', 'label' => 'Description of this bloc', 'enabled' => 1, 'position' => 32, 'notnull' => 0, 'visible' => 3,),
        'attached_files' => array('type' => 'array', 'label' => 'List of attached files on this bloc', 'enabled' => 1, 'position' => 32, 'notnull' => 0, 'visible' => 3,),
        'extrafields' => array('type' => 'array', 'label' => 'List of extrafields for this bloc', 'enabled' => 1, 'position' => 33, 'notnull' => 0, 'visible' => 3,),
        'position' => array('type' => 'integer', 'label' => 'order', 'enabled' => 1, 'position' => 10, 'notnull' => 0, 'visible' => 3,),
        'fk_surveypart' => array('type' => 'integer:SurveyPart:interventionsurvey/class/surveypart.class.php', 'label' => 'Link the the current survey part', 'enabled' => 1, 'position' => 15, 'notnull' => 1, 'visible' => -1,),
        'fk_c_survey_bloc_question' => array('type' => 'integer:SurveyBlocQuestionDictionary:interventionsurvey/core/dictionaries/surveyblocquestion.dictionary.php', 'label' => 'Link to the corresponding dictionnary item', 'enabled' => 1, 'position' => 20, 'notnull' => 0, 'visible' => -1,),
        'fk_chosen_status' => array('type' => 'integer:SurveyBlocStatus:interventionsurvey/class/surveyblocstatus.class.php', 'label' => 'Link to the choosen status', 'enabled' => 1, 'position' => 34, 'notnull' => 0, 'visible' => 3,),
        'fk_chosen_status_predefined_text' => array('type'=>'array', 'label'=>'Stringify array (split by comma) of predefined used text id', 'enabled'=>1, 'position'=>40, 'notnull'=>0, 'visible'=>-1,),
        'label_editable' => array('type' => 'boolean', 'label' => 'Label can be edited', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => -1,),
        'description_editable' => array('type' => 'boolean', 'label' => 'Description can be edited', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => -1,),
        'deletable' => array('type' => 'boolean', 'label' => 'Bloc can be deleted', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => -1,),
        'private' => array('type' => 'boolean', 'label' => 'Bloc is private', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => -1,),

    );
    public $rowid;
    public $array_options;
    public $label;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $mandatory_status;
    public $justification_text;
    public $description;
    public $attached_files;
    public $extrafields;
    public $position;
    public $fk_surveypart;
    public $fk_c_survey_bloc_question;
    public $fk_chosen_status;
    public $chosen_status;
    public $questions;
    public $status;
    public $label_editable;
    public $description_editable;
    public $deletable;
    public $private;
    public $fk_chosen_status_predefined_text;
    // END MODULEBUILDER PROPERTIES

    public static $extrafields_cache;
    public static $extrafields_label_cache;
    public $surveyPart;

    // If this object has a subtable with lines

    /**
     * @var int    Name of subtable line
     */
    //public $table_element_line = 'interventionsurvey_surveyblocquestionline';

    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    //public $fk_element = 'fk_surveyblocquestion';

    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    //public $class_element_line = 'surveyBlocQuestionline';

    /**
     * @var array	List of child tables. To test if we can delete object.
     */
    //protected $childtables=array();

    /**
     * @var array	List of child tables. To know object to delete on cascade.
     */
    //protected $childtablesoncascade=array('interventionsurvey_surveyblocquestiondet');

    /**
     * @var surveyBlocQuestionLine[]     Array of subtable lines
     */
    //public $lines = array();



    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

        // Example to show how to set values of fields definition dynamically
        /*if ($user->rights->interventionsurvey->surveyblocquestion->read) {
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
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false)
    {
        return $this->createCommon($user, $notrigger);
    }

    /**
     * Clone an object into another one
     *
     * @param  	User 	$user      	User that creates
     * @param  	int 	$fromid     Id of object to clone
     * @return 	mixed 				New object created, <0 if KO
     */
    public function createFromClone(User $user, $fromid)
    {
        global $langs, $extrafields;
        $error = 0;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $object = new self($this->db);

        $this->db->begin();

        // Load source object
        $result = $object->fetchCommon($fromid);
        if ($result > 0 && !empty($object->table_element_line)) $object->fetchLines();

        // get lines so they will be clone
        //foreach($this->lines as $line)
        //	$line->fetch_optionals();

        // Reset some properties
        unset($object->id);
        unset($object->fk_user_creat);
        unset($object->import_key);


        // Clear fields
        $object->ref = empty($this->fields['ref']['default']) ? "copy_of_" . $object->ref : $this->fields['ref']['default'];
        $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
        $object->status = self::STATUS_DRAFT;
        // ...
        // Clear extrafields that are unique
        if (is_array($object->array_options) && count($object->array_options) > 0) {
            $extrafields->fetch_name_optionals_label($this->table_element);
            foreach ($object->array_options as $key => $option) {
                $shortkey = preg_replace('/options_/', '', $key);
                if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
                    //var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
                    unset($object->array_options[$key]);
                }
            }
        }

        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $result = $object->createCommon($user);
        if ($result < 0) {
            $error++;
            $this->error = $object->error;
            $this->errors = $object->errors;
        }

        if (!$error) {
            // copy internal contacts
            if ($this->copy_linked_contact($object, 'internal') < 0) {
                $error++;
            }
        }

        if (!$error) {
            // copy external contacts if same company
            if (property_exists($this, 'socid') && $this->socid == $object->socid) {
                if ($this->copy_linked_contact($object, 'external') < 0)
                    $error++;
            }
        }

        unset($object->context['createfromclone']);

        // End
        if (!$error) {
            $this->db->commit();
            return $object;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id   Id object
     * @param string $ref  Ref
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null, $parent = null)
    {
        $result = $this->fetchCommon($id, $ref);
        if(isset($parent)){
            $this->surveyPart = $parent;
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
    public function fetchLines($parent = null)
    {
        $this->status = array();
        $this->chosen_status = null;
        $this->questions = array();
        if(isset($parent)){
            $this->surveyPart = $parent;
        }

        $this->interventionSurveyFetchLinesCommon(" ORDER BY position ASC", "SurveyQuestion", $this->questions);
        foreach($this->questions as $question){
            $question->fetch_optionals();
        }
        $this->interventionSurveyFetchLinesCommon(" ORDER BY position ASC", "SurveyBlocStatus", $this->status);
        if (isset($this->fk_chosen_status)) {
            $this->chosen_status = $this->status[$this->fk_chosen_status];
        }
        return 1;
    }

    /**
     * Fetch Parent object
     */

     public function fetchParent(){
        $this->fetchParentCommon("SurveyPart", $this->fk_surveypart, $this->surveyPart);
     }

     /**
      * Check if we are not in readonly mode
      *
      */
      public function is_survey_read_only(){
          $this->fetchParent();
          $temp = $this->surveyPart;
          return $temp ->is_survey_read_only();
      }

    /**
     *
     * Load survey in memory from the given array of survey parts
     *
     */

    public function setVarsFromFetchObj(&$obj, $parent = null)
    {
        $this->status = array();
        $this->chosen_status = null;
        $this->questions = array();
        $this->extrafields = array();
        parent::setVarsFromFetchObj($obj);
        if(isset($parent)){
            $this->surveyPart = $parent;
        }
        $tmp = is_array($obj) ? $obj["extrafields"] : $obj->extrafields;
        if(is_array($tmp)) {
            $this->extrafields = $tmp;
        }
        $dictionaryRowId = is_array($obj) ? $obj["c_rowid"] : $obj->c_rowid;
        $this->fk_c_survey_bloc_question = $dictionaryRowId;
        $objectValues = is_array($obj) ? $obj["questions"] : $obj->questions;
        if(isset($objectValues)){
            foreach ($objectValues as $questionObj) {
                $question = new SurveyQuestion($this->db);
                $question->setVarsFromFetchObj($questionObj,$this);
                $question->fk_surveyblocquestion = $this->id;
                $this->questions[] = $question;
            }
        }
        $objectValues = is_array($obj) ? $obj["status"] : $obj->status;
        if(isset($objectValues)){
            foreach ($objectValues as $statusObj) {
                $status = new SurveyBlocStatus($this->db);
                $status->setVarsFromFetchObj($statusObj,$this);
                $status->fk_surveyblocquestion = $this->id;
                $this->status[] = $status;
            }
        }
        $objectValues = is_array($obj) ? $obj["chosen_status"] : $obj->chosen_status;
        if (isset($objectValues)) {
            $chosen_status = new SurveyBlocStatus($this->db);
            $chosen_status->setVarsFromFetchObj($objectValues);
            $this->chosen_status = $chosen_status;
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
    public function update(User $user, $notrigger = false)
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        $this->db->begin();
        $this->deleteCommon($user, $notrigger);
        $errors = array();
        $errors = array_merge($errors, $this->errors);
        if(empty($errors)){
            foreach($this->questions as $question){
                $question->delete($user, $notrigger);
                $errors = array_merge($errors, $question->errors ?? array());
            }
            foreach($this->status as $status){
                $status->delete($user, $notrigger);
                $errors = array_merge($errors, $status->errors ?? array());
            }
        }
        if(empty($errors)){
            $this->db->commit();
            return 1;
        }
        else{
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
     *	Validate object
     *
     *	@param		User	$user     		User making status change
     *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
     *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
     */
    public function validate($user, $notrigger = 0)
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $error = 0;

        // Protection
        if ($this->status == self::STATUS_VALIDATED) {
            dol_syslog(get_class($this) . "::validate action abandonned: already validated", LOG_WARNING);
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->surveyblocquestion->create))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->surveyblocquestion->surveyblocquestion_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

        $now = dol_now();

        $this->db->begin();

        // Define new ref
        if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) // empty should not happened, but when it occurs, the test save life
        {
            $num = $this->getNextNumRef();
        } else {
            $num = $this->ref;
        }
        $this->newref = $num;

        // Validate
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " SET ref = '" . $this->db->escape($num) . "',";
        $sql .= " status = " . self::STATUS_VALIDATED . ",";
        $sql .= " date_validation = '" . $this->db->idate($now) . "',";
        $sql .= " fk_user_valid = " . $user->id;
        $sql .= " WHERE rowid = " . $this->id;

        dol_syslog(get_class($this) . "::validate()", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_print_error($this->db);
            $this->error = $this->db->lasterror();
            $error++;
        }

        if (!$error && !$notrigger) {
            // Call trigger
            $result = $this->call_trigger('SURVEYBLOCQUESTION_VALIDATE', $user);
            if ($result < 0) $error++;
            // End call triggers
        }

        if (!$error) {
            $this->oldref = $this->ref;

            // Rename directory if dir was a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref)) {
                // Now we rename also files into index
                $sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'surveyblocquestion/" . $this->db->escape($this->newref) . "'";
                $sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'surveyblocquestion/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++;
                    $this->error = $this->db->lasterror();
                }

                // We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
                $oldref = dol_sanitizeFileName($this->ref);
                $newref = dol_sanitizeFileName($num);
                $dirsource = $conf->interventionsurvey->dir_output . '/surveyblocquestion/' . $oldref;
                $dirdest = $conf->interventionsurvey->dir_output . '/surveyblocquestion/' . $newref;
                if (!$error && file_exists($dirsource)) {
                    dol_syslog(get_class($this) . "::validate() rename dir " . $dirsource . " into " . $dirdest);

                    if (@rename($dirsource, $dirdest)) {
                        dol_syslog("Rename ok");
                        // Rename docs starting with $oldref with $newref
                        $listoffiles = dol_dir_list($conf->interventionsurvey->dir_output . '/surveyblocquestion/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
                        foreach ($listoffiles as $fileentry) {
                            $dirsource = $fileentry['name'];
                            $dirdest = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
                            $dirsource = $fileentry['path'] . '/' . $dirsource;
                            $dirdest = $fileentry['path'] . '/' . $dirdest;
                            @rename($dirsource, $dirdest);
                        }
                    }
                }
            }
        }

        // Set new ref and current status
        if (!$error) {
            $this->ref = $num;
            $this->status = self::STATUS_VALIDATED;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Set draft status
     *
     *	@param	User	$user			Object user that modify
     *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     *	@return	int						<0 if KO, >0 if OK
     */
    public function setDraft($user, $notrigger = 0)
    {
        // Protection
        if ($this->status <= self::STATUS_DRAFT) {
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->interventionsurvey->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->interventionsurvey->interventionsurvey_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

        return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'SURVEYBLOCQUESTION_UNVALIDATE');
    }

    /**
     *	Set cancel status
     *
     *	@param	User	$user			Object user that modify
     *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
     */
    public function cancel($user, $notrigger = 0)
    {
        // Protection
        if ($this->status != self::STATUS_VALIDATED) {
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->interventionsurvey->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->interventionsurvey->interventionsurvey_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

        return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'SURVEYBLOCQUESTION_CLOSE');
    }

    /**
     *	Set back to validated status
     *
     *	@param	User	$user			Object user that modify
     *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
     */
    public function reopen($user, $notrigger = 0)
    {
        // Protection
        if ($this->status != self::STATUS_CANCELED) {
            return 0;
        }

        /*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->interventionsurvey->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->interventionsurvey->interventionsurvey_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

        return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'SURVEYBLOCQUESTION_REOPEN');
    }

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                     On what the link point to ('nolink', ...)
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  string  $morecss                    Add more css on link
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                              String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs, $hookmanager;

        if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

        $result = '';

        $label = '<u>' . $langs->trans("surveyBlocQuestion") . '</u>';
        $label .= '<br>';
        $label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (isset($this->status)) {
            $label .= '<br><b>' . $langs->trans("Status") . ":</b> " . $this->getLibStatut(5);
        }

        $url = dol_buildpath('/interventionsurvey/surveyblocquestion_card.php', 1) . '?id=' . $this->id;

        if ($option != 'nolink') {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
            if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
        }

        $linkclose = '';
        if (empty($notooltip)) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("ShowsurveyBlocQuestion");
                $linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
            }
            $linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
            $linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
        } else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

        $linkstart = '<a href="' . $url . '"';
        $linkstart .= $linkclose . '>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        if ($withpicto != 2) $result .= $this->ref;
        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action, $hookmanager;
        $hookmanager->initHooks(array('surveyblocquestiondao'));
        $parameters = array('id' => $this->id, 'getnomurl' => $result);
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) $result = $hookmanager->resPrint;
        else $result .= $hookmanager->resPrint;

        return $result;
    }

    /**
     *  Return label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return the status
     *
     *  @param	int		$status        Id status
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return string 			       Label of status
     */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            //$langs->load("interventionsurvey");
            $this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
            $this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
            $this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
            $this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
        }

        $statusType = 'status' . $status;
        //if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
        if ($status == self::STATUS_CANCELED) $statusType = 'status6';

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     *	Load the info information in the object
     *
     *	@param  int		$id       Id of object
     *	@return	void
     */
    public function info($id)
    {
        $sql = 'SELECT rowid, date_creation as datec, tms as datem,';
        $sql .= ' fk_user_creat, fk_user_modif';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE t.rowid = ' . $id;
        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation = $cuser;
                }

                if ($obj->fk_user_valid) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation = $vuser;
                }

                if ($obj->fk_user_cloture) {
                    $cluser = new User($this->db);
                    $cluser->fetch($obj->fk_user_cloture);
                    $this->user_cloture = $cluser;
                }

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
                $this->date_validation   = $this->db->jdate($obj->datev);
            }

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
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
     * 	Create an array of lines
     *
     * 	@return array|int		array of lines if OK, <0 if KO
     */
    public function getLinesArray()
    {
        $this->lines = array();

        $objectline = new surveyBlocQuestionLine($this->db);
        $result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql' => 'fk_surveyblocquestion = ' . $this->id));

        if (is_numeric($result)) {
            $this->error = $this->error;
            $this->errors = $this->errors;
            return $result;
        } else {
            $this->lines = $result;
            return $this->lines;
        }
    }

    /**
     *  Returns the reference to the following non used object depending on the active numbering module.
     *
     *  @return string      		Object free reference
     */
    public function getNextNumRef()
    {
        global $langs, $conf;
        $langs->load("interventionsurvey@surveyblocquestion");

        if (empty($conf->global->INTERVENTIONSURVEY_SURVEYBLOCQUESTION_ADDON)) {
            $conf->global->INTERVENTIONSURVEY_SURVEYBLOCQUESTION_ADDON = 'mod_mymobject_standard';
        }

        if (!empty($conf->global->INTERVENTIONSURVEY_SURVEYBLOCQUESTION_ADDON)) {
            $mybool = false;

            $file = $conf->global->INTERVENTIONSURVEY_SURVEYBLOCQUESTION_ADDON . ".php";
            $classname = $conf->global->INTERVENTIONSURVEY_SURVEYBLOCQUESTION_ADDON;

            // Include file with class
            $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {
                $dir = dol_buildpath($reldir . "core/modules/interventionsurvey/");

                // Load file with numbering class (if found)
                $mybool |= @include_once $dir . $file;
            }

            if ($mybool === false) {
                dol_print_error('', "Failed to include file " . $file);
                return '';
            }

            $obj = new $classname();
            $numref = $obj->getNextValue($this);

            if ($numref != "") {
                return $numref;
            } else {
                $this->error = $obj->error;
                //dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
                return "";
            }
        } else {
            print $langs->trans("Error") . " " . $langs->trans("Error_INTERVENTIONSURVEY_SURVEYBLOCQUESTION_ADDON_NotDefined");
            return "";
        }
    }

    /**
     *  Create a document onto disk according to template module.
     *
     *  @param	    string		$modele			Force template to use ('' to not force)
     *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
     *  @param      int			$hidedetails    Hide details of lines
     *  @param      int			$hidedesc       Hide description
     *  @param      int			$hideref        Hide ref
     *  @param      null|array  $moreparams     Array to provide more information
     *  @return     int         				0 if KO, 1 if OK
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $langs;

        $langs->load("interventionsurvey@interventionsurvey");

        if (!dol_strlen($modele)) {
            $modele = 'standard';

            if ($this->modelpdf) {
                $modele = $this->modelpdf;
            } elseif (!empty($conf->global->SURVEYBLOCQUESTION_ADDON_PDF)) {
                $modele = $conf->global->SURVEYBLOCQUESTION_ADDON_PDF;
            }
        }

        $modelpath = "core/modules/interventionsurvey/doc/";

        return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
    }

    /**
     * Action executed by scheduler
     * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
     *
     * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
     */
    //public function doScheduledJob($param1, $param2, ...)
    public function doScheduledJob()
    {
        global $conf, $langs;

        //$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

        $error = 0;
        $this->output = '';
        $this->error = '';

        dol_syslog(__METHOD__, LOG_DEBUG);

        $now = dol_now();

        $this->db->begin();

        // ...

        $this->db->commit();

        return $error;
    }

    /**
     * Fetch parent object common
     */

    public function fetchParentCommon($classname, $id, &$field){
        if(!isset($field)){
            $parent = new $classname($this->db);
            if($parent->fetch($id) > 0 ){
                $field = $parent;
            }
        }
        if(method_exists($field, "fetchParent")){
            $field->fetchParent();
        }
    }


    /**
     * Load object in memory from the database
     *
     * @param	string	$morewhere		More SQL filters (' AND ...')
     * @return 	int         			<0 if KO, 0 if not found, >0 if OK
     */
    public function interventionSurveyFetchLinesCommon($morewhere = '', $objectlineclassname = null, &$resultValue)
    {

        if (!class_exists($objectlineclassname)) {
            $this->error = 'Error, class ' . $objectlineclassname . ' not found during call of fetchLinesCommon';
            $this->errors[] = $this->error;
            return -1;
        }

        $objectline = new $objectlineclassname($this->db);

        $sql = 'SELECT ' . $objectline->getFieldList();
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $objectline->table_element;
        $sql .= ' WHERE fk_' . $this->element . ' = ' . $this->id;
        if ($morewhere)   $sql .= $morewhere;

        $resql = $this->db->query($sql);
        if ($resql) {
            $num_rows = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_rows) {
                $obj = $this->db->fetch_object($resql);
                if ($obj) {
                    $newline = new $objectlineclassname($this->db);
                    $newline->setVarsFromFetchObj($obj);
                    if (method_exists($newline, "fetchLines")) {
                        $newline->fetchLines($this);
                    }
                    $resultValue[] = $newline;
                    $this->errors = array_merge($this->errors, $newline->errors);
                }
                $i++;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
            return -1;
        }
        return empty($this->errors) ? 1 : -1;
    }
    /**
     *
     * Save
     *
     *
     */

    public function save($user, $fk_surveypart=NULL)
    {
        global $langs;

        $this->db->begin();
        if (isset($fk_surveypart)) {
            $this->fk_surveypart = $fk_surveypart;
        }
        $errors = array();
        if($this->is_survey_read_only()){
            $errors[] = $langs->trans('InterventionSurveyReadOnlyMode');
            $this->db->rollback();
            $this->errors = $errors;
            return -1;
        }

        if ($this->id) {
            $this->update($user);
        } else {
            $this->create($user);
        }
        if (empty($errors)) {
            foreach ($this->questions as $position => $question) {
                $question->position = $position;
                $question->save($user, $this->id);
                $errors = array_merge($errors, $question->errors);
            }
            foreach ($this->status as $position => $status) {
                $status->position = $position;
                $status->save($user, $this->id);
                $errors = array_merge($errors, $status->errors);
            }
        }
        if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
            $this->insertExtraFields();
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
     * Get chosen status or an empty object
     */

     public function getChosenStatus(){
        $result = new stdClass();
        if(is_array($this->status) && $this->fk_chosen_status > 0) {
            foreach($this->status as $status){
                if($status->id == $this->fk_chosen_status){
                $result = $status;
                break;
                }
            }
        }
         $this->chosen_status = $result;
         return $result;
     }

     /**
     * Is answer properly chosen ?
     */

    public function IsStatusChosen(){
        return !!(!$this->mandatory_status || $this->fk_chosen_status > 0);
     }

     /**
     * Is justification text for answer properly set ?
     */

    public function IsJustificationTextProperlySet(){
        $chosenStatus = $this->getChosenStatus();
        return !!(!$chosenStatus->mandatory_justification || $this->justification_text);
     }

     /**
     * Is label of this bloc properly set ?
     */

    public function IsBlocLabelProperlySet(){
        return !!$this->label;
     }


     /**
      *
      * Check that extrafields are properly set
      */

     public function checkExtrafieldProperlySet(){
          global $langs;
          $errors = array();
          foreach (self::$extrafields_cache->attributes[$this->table_element]['required'] as $key => $val) {
            if (!empty($val) && !in_array(substr($key,8), $this->extrafields) && empty($this->array_options['options_' . $key])) {
                    $errors[] = $langs->trans('InterventionSurveyBlocMissingExtrafield',
                    self::$extrafields_cache->attributes[$this->table_element]['label'][$key],
                    $this->label,
                    $this->id);
            }
        }
        $this->errors = array_merge($this->errors,$errors);
        return empty($errors);
      }


      /**
      * Are some information missing ?
      */

    public function areDataValid(){
        global $langs;
        $errors = array();
        if($this->isBlocDesactivated()){
            return true;
        }
        if(!$this->IsStatusChosen()){
            $errors[] = $langs->trans('InterventionSurveyMissingStatus', $this->label, $this->id);
        }
        if(!$this->IsJustificationTextProperlySet()){
            $errors[] = $langs->trans('InterventionSurveyMissingJustificationStatus', $this->label, $this->id);
        }
        if(!$this->IsBlocLabelProperlySet()){
            $errors[] = $langs->trans('InterventionSurveyMissingLabel', $this->id);
        }
        if(!$this->errors){
            $this->errors = array();
        }
        $this->errors = array_merge($this->errors, $errors);
        return $this->checkExtrafieldProperlySet() && empty($errors);
    }

    /**
     * Is Bloc desactivated ?
     */

     public function isBlocDesactivated(){
        return $this->getChosenStatus()->deactivate_bloc ? true:false;
     }

     /**
     *	{@inheritdoc}
     */
    function fetch_optionals($rowid = null, $optionsArray = null)
    {
        $result = parent::fetch_optionals($rowid, $optionsArray);
        if ($result > 0) {
            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key,8), $this->extrafields)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        return $result;
    }

    /**
     *
     * Function to fetch and update $extrafields_cache property to save all extrafields for this object
     * There might be a more elegant way of doing it
     *
     */

    public function fetchExtraFieldsInfo() {
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
        // Clean extra fields
        if (count($this->extrafields) == 0) {
            $this->array_options = array();
        } elseif (is_array($this->array_options)) {
            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key,8), $this->extrafields)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        // Manage require fields but not selected
        $this->fetchExtraFieldsInfo();
        foreach (self::$extrafields_cache->attributes[$this->table_element]['required'] as $key => $val) {
            if (!empty($val) && empty($this->array_options["options_" . $key]) && ( !in_array(substr($key,8), $this->extrafields) || $this->isBlocDesactivated() ) ) {
                $this->array_options["options_" . $key] = '0';
            }
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

        $extrafields_question_bloc = clone $extrafields;
        $tmp = array();
        foreach ($extrafields_question_bloc->attribute_label as $key => $val) {
            if (!in_array($key, $this->extrafields)) {
                // Old usage
                unset($extrafields_question_bloc->attribute_type[$key]);
                unset($extrafields_question_bloc->attribute_size[$key]);
                unset($extrafields_question_bloc->attribute_elementtype[$key]);
                unset($extrafields_question_bloc->attribute_default[$key]);
                unset($extrafields_question_bloc->attribute_computed[$key]);
                unset($extrafields_question_bloc->attribute_unique[$key]);
                unset($extrafields_question_bloc->attribute_required[$key]);
                unset($extrafields_question_bloc->attribute_param[$key]);
                unset($extrafields_question_bloc->attribute_pos[$key]);
                unset($extrafields_question_bloc->attribute_alwayseditable[$key]);
                unset($extrafields_question_bloc->attribute_perms[$key]);
                unset($extrafields_question_bloc->attribute_list[$key]);
                unset($extrafields_question_bloc->attribute_hidden[$key]);

                // New usage
                unset($extrafields_question_bloc->attributes[$this->table_element]['type'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['label'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['size'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['elementtype'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['default'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['computed'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['unique'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['required'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['param'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['pos'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['alwayseditable'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['perms'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['list'][$key]);
                unset($extrafields_question_bloc->attributes[$this->table_element]['ishidden'][$key]);
            } else {
                $tmp[$key] = $val;
            }
        }
        $extrafields_question_bloc->attribute_label = $tmp;

        return parent::showOptionals($extrafields_question_bloc, $mode, $params, $keysuffix, $keyprefix, $onetrtd);
    }
}
