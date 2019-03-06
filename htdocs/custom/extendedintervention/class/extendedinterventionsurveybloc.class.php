<?php
/* Copyright (C) 2019   Open-DSI            <support@open-dsi.fr>
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
 *	\file       htdocs/extendedintervention/class/extendedinterventionsurveybloc.class.php
 *	\brief      File of class to manage survey bloc
 */

/**
 *	Sort by position
 */
function ei_sort_question_bloc_position($a, $b)
{
    if (isset($a) && isset($b) && $a->position_question_bloc != $b->position_question_bloc) return ($a->position_question_bloc < $b->position_question_bloc) ? -1 : 1;
    if (isset($a) && isset($b) && $a->label_question_bloc != $b->label_question_bloc) return ($a->label_question_bloc < $b->label_question_bloc) ? -1 : 1;
    return 0;
}

/**
 *	Class to manage survey bloc
 */
class EISurveyBloc extends CommonObject
{
    public $element='extendedintervention_eisurveybloc';
    public $table_element='extendedintervention_survey_bloc';
    public $table_element_child='extendedintervention_question_bloc';
    public $table_element_child_line='extendedintervention_question_blocdet';
    //protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    //public $picto='generic';

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
        /*"id" => '', */"fk_fichinter" => ''/*, "fichinter" => ''*/, "fk_equipment" => ''/*, "equipment" => ''*/,
        "fk_product" => ''/*, "product" => ''*/, "equipment_ref" => '', "product_ref" => '', "product_label" => '', "survey" => '',
        "read_only" => '', "entity" => '', "date_creation" => '', "date_modification" => '', "user_creation_id" => '', "user_creation" => '',
        "user_modification_id" => '', "user_modification" => '', "import_key" => '',
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
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
     * Array of blacklist of properties keys for this object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES = array(
    );

    /**
     * Array of blacklist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
	 * Id of the survey bloc
	 * @var int
	 */
    public $id;
    /**
     * @var EISurveyBloc
     */
	public $oldcopy;

    /**
     * Intervention ID
     * @var int
     */
    public $fk_fichinter;
    /**
     * Intervention loaded
     * @see fetch_fichinter()
     * @var ExtendedIntervention
     */
    public $fichinter;
    /**
     * Equipment ID (Custom if negative)
     * @var int
     */
    public $fk_equipment;
    /**
     * Equipment loaded
     * @see fetch_equipment()
     * @var Equipement
     */
    public $equipment;
    /**
     * Product ID
     * @var int
     */
    public $fk_product;
    /**
     * Product loaded
     * @see fetch_product()
     * @var Product
     */
    public $product;
    /**
     * Equipment ref
     * @var string
     */
    public $equipment_ref;
    /**
     * Product ref
     * @var string
     */
    public $product_ref;
    /**
     * Product label
     * @var string
     */
    public $product_label;

    /**
     * Warning the id of the product is different with that of the equipment (when fetch survey bloc with all data)
     * @var int
     */
    public $warning_fk_product;
    /**
     * Warning the ref of the equipment is different with that of the equipment (when fetch survey bloc with all data)
     * @var int
     */
    public $warning_equipment_ref;
    /**
     * Warning the ref of the product is different with that of the equipment (when fetch survey bloc with all data)
     * @var int
     */
    public $warning_product_ref;
    /**
     * Warning the label of the product is different with that of the equipment (when fetch survey bloc with all data)
     * @var int
     */
    public $warning_product_label;
    /**
     * True if this survey bloc (equipment) is not in the current linked object of the intervention
     * @var boolean
     */
    public $read_only;

    /**
     * Entity of the request
     * @var int
     */
    public $entity;
    /**
     * Date created of the request
     * @var int
     */
    public $date_creation;
    /**
     * Date modified of the request
     * @var int
     */
    public $date_modification;
    /**
     * Id of the user who created the request
     * @var int
     */
    public $user_creation_id;
    /**
     * User who created the request
     * @var User
     */
    public $user_creation;
    /**
     * Id of the user who modified the request
     * @var int
     */
    public $user_modification_id;
    /**
     * User who modified the request
     * @var User
     */
    public $user_modification;
    /**
     * Key value used to track if data is coming from import wizard
     * @var string
     */
    public $import_key;

    /**
	 * @var EIQuestionBloc[]
	 */
    public $survey = array();

    /**
     *  Cache of the list of question bloc information
     * @var DictionaryLine[]
     */
	static public $question_bloc_cached = array();

    /**
     *  Cache of the list of parent categories IDs of a category ID
     * @var string[]
     */
	static public $parent_categories_cached = array();

    /**
     *  Constructor
     *
     * @param   DoliDB                  $db             Database handler
     * @param   ExtendedIntervention    $fichinter      Intervention handler
     */
    function __construct($db, $fichinter=null)
    {
        global $langs;

        $this->db = $db;
        $this->fichinter = $fichinter;

        $langs->load("extendedintervention@extendedintervention");
        $langs->load("errors");
    }

    /**
     *  Create survey bloc into database
     *
     * @param   User    $user           User that create
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  int                     <0 if KO, >=0 if OK
     */
    function create($user, $notrigger=0)
    {
        dol_syslog(__METHOD__ . " user_id=" . $user->id . " notrigger=" . $notrigger);

        global $conf, $langs;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_product = $this->fk_product > 0 ? $this->fk_product : 0;
        $this->equipment_ref = !empty($this->equipment_ref) ? $this->equipment_ref : '';
        $this->product_ref = !empty($this->product_ref) ? $this->product_ref : '';
        $this->product_label = !empty($this->product_label) ? $this->product_label : '';

        // Check parameters
        $error = 0;
        $now = dol_now();

        if (!($this->fk_fichinter > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionInterventionId");
            $error++;
        }
        if ($this->fk_equipment > 0) {
            if (empty($this->equipment_ref) || empty($this->fk_product)) {
                $this->fetch_equipment();
                if (isset($this->equipment)) {
                    $this->equipment_ref = $this->equipment->ref;
                    $this->fk_product = $this->equipment->fk_product;

                    if ($this->fk_product > 0) {
                        if (empty($this->product_ref) || empty($this->product_label)) {
                            $this->fetch_product();
                            if (isset($this->product)) {
                                $this->product_ref = $this->product->ref;
                                $this->product_label = $this->product->label;
                            } else {
                                $this->errors[] = $langs->trans("ExtendedInterventionProductNotFound");
                                $error++;
                            }
                        }
                    } else {
                        $this->product_ref = '';
                        $this->product_label = '';
                    }
                } else {
                    $this->errors[] = $langs->trans("ExtendedInterventionEquipmentNotFound");
                    $error++;
                }
            }
        } elseif ($this->fk_equipment == 0) {
            $this->fk_product = 0;
            $this->equipment_ref = '';
            $this->product_ref = '';
            $this->product_label = '';
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        // Insert into database
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= "(fk_fichinter";
        $sql .= ", fk_equipment";
        $sql .= ", fk_product";
        $sql .= ", equipment_ref";
        $sql .= ", product_ref";
        $sql .= ", product_label";
        $sql .= ", fk_user_author";
        $sql .= ", datec";
        $sql .= ", entity";
        $sql .= ") VALUES (";
        $sql .= $this->fk_fichinter;
        $sql .= ", " . $this->fk_equipment;
        $sql .= ", " . ($this->fk_product > 0 ? $this->fk_product : 'NULL');
        $sql .= ", " . (!empty($this->equipment_ref) ? "'" . $this->db->escape($this->equipment_ref) . "'" : 'NULL');
        $sql .= ", " . (!empty($this->product_ref) ? "'" . $this->db->escape($this->product_ref) . "'" : 'NULL');
        $sql .= ", " . (!empty($this->product_label) ? "'" . $this->db->escape($this->product_label) . "'" : 'NULL');
        $sql .= ", " . $user->id;
        $sql .= ", '" . $this->db->idate($now) . "'";
        $sql .= ", " . $conf->entity;
        $sql .= ")";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('EI_SURVEY_BLOCK_CREATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return $this->id;
            } else {
                $this->db->rollback();
                dol_syslog(__METHOD__ . ' Errors: ' . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
            return -1;
        }
    }

    /**
     *  Update survey bloc into database
     *
     * @param   User    $user           User that create
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  int                     <0 if KO, >=0 if OK
     */
    function update($user, $notrigger=0)
    {
        dol_syslog(__METHOD__ . " user_id=" . $user->id . " notrigger=" . $notrigger);

        global $conf, $langs;

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_product = $this->fk_product > 0 ? $this->fk_product : 0;
        $this->equipment_ref = !empty($this->equipment_ref) ? $this->equipment_ref : '';
        $this->product_ref = !empty($this->product_ref) ? $this->product_ref : '';
        $this->product_label = !empty($this->product_label) ? $this->product_label : '';

        // Check parameters
        $error = 0;
        $now = dol_now();

        if (!($this->fk_fichinter > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionInterventionId");
            $error++;
        }
        if ($this->fk_equipment > 0) {
            if (empty($this->equipment_ref) || empty($this->fk_product)) {
                $this->fetch_equipment();
                if (isset($this->equipment)) {
                    $this->equipment_ref = $this->equipment->ref;
                    $this->fk_product = $this->equipment->fk_product;

                    if ($this->fk_product > 0) {
                        if (empty($this->product_ref) || empty($this->product_label)) {
                            $this->fetch_product();
                            if (isset($this->product)) {
                                $this->product_ref = $this->product->ref;
                                $this->product_label = $this->product->label;
                            } else {
                                $this->errors[] = $langs->trans("ExtendedInterventionProductNotFound");
                                $error++;
                            }
                        }
                    } else {
                        $this->product_ref = '';
                        $this->product_label = '';
                    }
                } else {
                    $this->errors[] = $langs->trans("ExtendedInterventionEquipmentNotFound");
                    $error++;
                }
            }
        } elseif ($this->fk_equipment == 0) {
            $this->fk_product = 0;
            $this->equipment_ref = '';
            $this->product_ref = '';
            $this->product_label = '';
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        // Update into database
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
        $sql .= " fk_product = " . ($this->fk_product > 0 ? $this->fk_product : 'NULL');
        $sql .= ", equipment_ref = " . (!empty($this->equipment_ref) ? "'" . $this->db->escape($this->equipment_ref) . "'" : 'NULL');
        $sql .= ", product_ref = " . (!empty($this->product_ref) ? "'" . $this->db->escape($this->product_ref) . "'" : 'NULL');
        $sql .= ", product_label = " . (!empty($this->product_label) ? "'" . $this->db->escape($this->product_label) . "'" : 'NULL');
        $sql .= ", fk_user_modif = " . $user->id;
        $sql .= ", tms = '" . $this->db->idate($now) . "'";
        $sql .= " WHERE entity IN (" . getEntity('ei_survey_bloc') . ")";
        if ($this->fk_fichinter > 0 && $this->fk_equipment != 0)
            $sql .= " AND fk_fichinter=" . $this->fk_fichinter . " AND fk_equipment=" . $this->fk_equipment;
        else
            $sql .= " AND rowid=" . $this->id;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('EI_SURVEY_BLOCK_UPDATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return $this->id;
            } else {
                $this->db->rollback();
                dol_syslog(__METHOD__ . ' Errors: ' . $this->errorsToString(), LOG_ERR);
                return -1;
            }
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
            return -1;
        }
    }

    /**
     *  Delete survey bloc into database
     *
     * @param   User    $user           Object user that delete
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  int                     1 if ok, otherwise if error
     */
    function delete($user, $notrigger=0)
    {
        dol_syslog(__METHOD__ . ' rowid=' . $this->id . " fk_fichinter=" . $this->fk_fichinter . " fk_equipment=" . $this->fk_equipment . " by user_id=" . $user->id, LOG_DEBUG);

        global $conf, $langs;

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;

        // Check parameters
        $error = 0;

        if (!($this->fk_fichinter > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionInterventionId");
            $error++;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        if (!$notrigger) {
            // Call trigger
            $result = $this->call_trigger('EI_SURVEY_BLOCK_DELETE', $user);
            if ($result < 0) {
                $error++;
            }
            // End call triggers
        }

        // Removed extrafields of the questions
        if (!$error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element_child_line . "_extrafields FROM " . MAIN_DB_PREFIX . $this->table_element_child_line . "_extrafields" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_child_line . " as cd ON " . MAIN_DB_PREFIX . $this->table_element_child_line . "_extrafields.fk_object = cd.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_child . " as c ON cd.fk_question_bloc = c.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON c.fk_survey_bloc = t.rowid" .
                " WHERE t.entity IN (" . getEntity('ei_survey_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_equipment != 0)
                $sql .= " AND t.fk_fichinter=" . $this->fk_fichinter . " AND t.fk_equipment=" . $this->fk_equipment;
            else
                $sql .= " AND t.rowid=" . $this->id;
            if (!$this->db->query($sql)) {
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $this->errors[] = $this->db->lasterror();
                $error++;
            }
        }

        // Removed the questions
        if (!$error) {
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element_child_line . " FROM " . MAIN_DB_PREFIX . $this->table_element_child_line .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_child . " as c ON " . MAIN_DB_PREFIX . $this->table_element_child_line . ".fk_question_bloc = c.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON c.fk_survey_bloc = t.rowid" .
                " WHERE t.entity IN (" . getEntity('ei_survey_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_equipment != 0)
                $sql .= " AND t.fk_fichinter=" . $this->fk_fichinter . " AND t.fk_equipment=" . $this->fk_equipment;
            else
                $sql .= " AND t.rowid=" . $this->id;
            if (!$this->db->query($sql)) {
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $this->errors[] = $this->db->lasterror();
                $error++;
            }
        }

        // Removed extrafields of the question blocs
        if (!$error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element_child . "_extrafields FROM " . MAIN_DB_PREFIX . $this->table_element_child . "_extrafields" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_child . " as c ON " . MAIN_DB_PREFIX . $this->table_element_child . "_extrafields.fk_object = c.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON c.fk_survey_bloc = t.rowid" .
                " WHERE t.entity IN (" . getEntity('ei_survey_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_equipment != 0)
                $sql .= " AND t.fk_fichinter=" . $this->fk_fichinter . " AND t.fk_equipment=" . $this->fk_equipment;
            else
                $sql .= " AND t.rowid=" . $this->id;
            if (!$this->db->query($sql)) {
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $this->errors[] = $this->db->lasterror();
                $error++;
            }
        }

        // Removed the question blocs
        if (!$error) {
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element_child . " FROM " . MAIN_DB_PREFIX . $this->table_element_child .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON " . MAIN_DB_PREFIX . $this->table_element_child . ".fk_survey_bloc = t.rowid" .
                " WHERE t.entity IN (" . getEntity('ei_survey_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_equipment != 0)
                $sql .= " AND t.fk_fichinter=" . $this->fk_fichinter . " AND t.fk_equipment=" . $this->fk_equipment;
            else
                $sql .= " AND t.rowid=" . $this->id;
            if (!$this->db->query($sql)) {
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $this->errors[] = $this->db->lasterror();
                $error++;
            }
        }

        // Removed the survey blocs
        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element .
                " WHERE entity IN (" . getEntity('ei_survey_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_equipment != 0)
                $sql .= " AND fk_fichinter=" . $this->fk_fichinter . " AND fk_equipment=" . $this->fk_equipment;
            else
                $sql .= " AND rowid=" . $this->id;
            if (!$this->db->query($sql)) {
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $this->errors[] = $this->db->lasterror();
                $error++;
            }
        }

        if (!$error) {
            dol_syslog(__METHOD__ . " Success", LOG_DEBUG);
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Load a survey bloc from database and its survey
     *
     * @param   int     $rowid                  ID of object to load
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $all_data               1=Load all data of the dictionaries (all status, all answer and all predefined text)
     * @param   int     $test_exist             1=Test if the survey bloc exist in the survey of the intervention
     * @return  int                             <0 if KO, >=0 if OK
     */
    function fetch($rowid=0, $fk_fichinter=0, $fk_equipment=0, $all_data=0, $test_exist=1)
    {
        global $langs;

        // Clean parameters
        $rowid = $rowid > 0 ? $rowid : 0;
        $fk_fichinter = $fk_fichinter > 0 ? $fk_fichinter : 0;
        $fk_equipment = isset($fk_equipment) ? $fk_equipment : 0;

        $sql = "SELECT t.rowid, t.fk_fichinter, t.fk_equipment";
        $sql .= ", t.fk_product, t.equipment_ref, t.product_ref, t.product_label";
        $sql .= ", t.datec, t.tms, t.fk_user_author, t.fk_user_modif, t.import_key";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " AS t";
        $sql .= " WHERE t.entity IN (" . getEntity('ei_survey_bloc') . ")";
        if ($fk_fichinter > 0 && $fk_equipment != 0)
            $sql .= " AND t.fk_fichinter=" . $fk_fichinter . " AND t.fk_equipment=" . $fk_equipment;
        else
            $sql .= " AND t.rowid=" . $rowid;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->warning_fk_product = 0;
            $this->warning_equipment_ref = 0;
            $this->warning_product_ref = 0;
            $this->warning_product_label = 0;

            $num = $this->db->num_rows($resql);
            if ($num) {
                $obj = $this->db->fetch_object($resql);

                $this->id                           = $obj->rowid;
                $this->fk_fichinter                 = $obj->fk_fichinter;
                $this->fk_equipment                 = !empty($obj->fk_equipment) ? $obj->fk_equipment : 0;
                $this->fk_product                   = !empty($obj->fk_product) ? $obj->fk_product : 0;
                $this->equipment_ref                = !empty($obj->equipment_ref) ? $obj->equipment_ref : '';
                $this->product_ref                  = !empty($obj->product_ref) ? $obj->product_ref : '';
                $this->product_label                = !empty($obj->product_label) ? $obj->product_label : '';
                $this->date_creation                = $this->db->jdate($obj->datec);
                $this->date_modification            = $this->db->jdate($obj->tms);
                $this->user_creation_id             = $obj->fk_user_author;
                $this->user_modification_id         = $obj->fk_user_modif;
                $this->import_key                   = $obj->import_key;

                $this->db->free($resql);
            } elseif ($fk_fichinter > 0 && $fk_equipment != 0 && (!$test_exist || $fk_equipment < 0 || $this->is_in_survey($fk_fichinter, $fk_equipment))) {
                $this->id                           = 0;
                $this->fk_fichinter                 = $fk_fichinter;
                $this->fk_equipment                 = $fk_equipment;
                $this->fetch_equipment();
                if (isset($this->equipment)) $this->equipment->fetch_product();
                $this->fk_product                   = isset($this->equipment) ? $this->equipment->fk_product : 0;
                $this->equipment_ref                = isset($this->equipment) ? $this->equipment->ref : '';
                $this->product_ref                  = isset($this->equipment->product) ? $this->equipment->product->ref : '';
                $this->product_label                = isset($this->equipment->product) ? $this->equipment->product->label : '';
                $this->date_creation                = 0;
                $this->date_modification            = 0;
                $this->user_creation_id             = 0;
                $this->user_modification_id         = 0;
                $this->import_key                   = '';
            } else {
                $this->error = $langs->trans("ErrorRecordNotFound");
                return 0;
            }

            if ($all_data) {
                $this->load_warning();
            }

            $result = $this->fetch_survey($all_data);
            if ($result < 0) {
                return -3;
            }

            return 1;
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
            return -1;
        }
    }

    /**
     *  Load warning
     *
     * @return  void
     */
    function load_warning()
    {
        $this->warning_fk_product = 0;
        $this->warning_equipment_ref = 0;
        $this->warning_product_ref = 0;
        $this->warning_product_label = 0;

        // Check if has value modified with the dictionaries
        if ($this->id > 0 && $this->fk_equipment > 0) {
            $this->fetch_equipment();
            if (isset($this->equipment)) $this->equipment->fetch_product();

            // Id of the product
            $new_fk_product = isset($this->equipment) ? $this->equipment->fk_product : 0;
            if ($this->fk_product != $new_fk_product) {
                $this->fk_product = $new_fk_product;
                $this->warning_fk_product = 1;
            }
            // Ref of the equipment
            $new_equipment_ref = isset($this->equipment) ? $this->equipment->ref : '';
            if ($this->equipment_ref != $new_equipment_ref) {
                $this->equipment_ref = $new_equipment_ref;
                $this->warning_equipment_ref = 1;
            }
            // Ref of the product
            $new_product_ref = isset($this->equipment->product) ? $this->equipment->product->ref : 0;
            if ($this->product_ref != $new_product_ref) {
                $this->product_ref = $new_product_ref;
                $this->warning_product_ref = 1;
            }
            // Label of the product
            $new_product_label = isset($this->equipment->product) ? $this->equipment->product->label : 0;
            if ($this->product_label != $new_product_label) {
                $this->product_label = $new_product_label;
                $this->warning_product_label = 1;
            }
        }
    }

    /**
     *  Is the survey bloc read only
     *
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @return  int                             =0 if No, >0 if Yes
     */
    function is_read_only($fk_fichinter=null, $fk_equipment=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;

        $this->fetch_fichinter();
        if (isset($this->fichinter)) {
            if (!is_array($this->fichinter->survey) || count($this->fichinter->survey) == 0) {
                $this->fichinter->fetch_survey(0);
            }

            if (empty($this->fichinter->survey[$this->fk_equipment]->read_only)) {
                $this->read_only = 0;
                return 0;
            }
        }

        $this->read_only = 1;
        return 1;
    }

    /**
     *  Is the survey bloc in the survey
     *
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @return  int                             =0 if No, >0 if Yes
     */
    function is_in_survey($fk_fichinter=null, $fk_equipment=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;

        $this->fetch_fichinter();
        if (isset($this->fichinter)) {
            $this->fichinter->fetch_optionals();

            if (!is_array($this->fichinter->survey) || count($this->fichinter->survey) == 0) {
                $this->fichinter->fetch_survey(0);
            }

            if (isset($this->fichinter->survey[$this->fk_equipment])) {
                return 1;
            }
        }

        return 0;
    }

    /**
     *  Fetch all the question bloc information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    public function fetchQuestionBlocInfo()
    {
        $this->fetch_fichinter();

        if (isset($this->fichinter)) {
            if (empty($this->fichinter->array_options['options_ei_type'])) {
                $this->fichinter->fetch_optionals();
            }

            if (empty(self::$question_bloc_cached[$this->fichinter->array_options['ei_type']])) {
                if (!empty($this->fichinter->array_options['options_ei_type'])) {
                    dol_include_once('/advancedictionaries/class/dictionary.class.php');
                    $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionquestionbloc');
                    if ($dictionary->fetch_lines(1, array('types_intervention' => array($this->fichinter->array_options['options_ei_type']))) > 0) {
                        self::$question_bloc_cached[$this->fichinter->array_options['options_ei_type']] = $dictionary->lines;
                    } else {
                        $this->error = $dictionary->error;
                        $this->errors = array_merge($this->errors, $dictionary->errors);
                        return -1;
                    }
                }
            }
        }

        return 1;
    }

    /**
     *  Fetch all the parent categories IDs of a category ID (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    public function fetchParentCategoriesInfo()
    {
        if (empty(self::$parent_categories_cached)) {
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
            $category_static = new Categorie($this->db);
            $full_categories = $category_static->get_full_arbo('product');

            self::$parent_categories_cached = array();
            foreach ($full_categories as $category) {
                self::$parent_categories_cached[$category['id']] = array_filter(explode('_', $category['fullpath']), "strlen");
            }
        }

        return 1;
    }

    /**
     *  Load the survey of this survey bloc
     *
     * @param   int     $all_data   1=Load all data of the dictionaries (all status, all answer and all predefined text)
     * @return  int                 <0 if KO, >0 if OK
     */
    public function fetch_survey($all_data=0)
    {
        $this->survey = array();
        $this->fetch_fichinter();

        if (isset($this->fichinter) && $this->fk_fichinter > 0 && ($this->id > 0 || $this->fk_equipment != 0) && $this->fichinter->statut != ExtendedIntervention::STATUS_DRAFT) {
            dol_include_once('/extendedintervention/class/extendedinterventionquestionbloc.class.php');
            if ($this->fichinter->statut != ExtendedIntervention::STATUS_VALIDATED) $all_data = 0;

            $sql = "SELECT t.fk_fichinter, t.fk_equipment, c.fk_survey_bloc, c.fk_c_question_bloc";
            $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element_child . " AS c" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON c.fk_survey_bloc = t.rowid" .
                " WHERE t.entity IN (" . getEntity('ei_survey_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_equipment != 0)
                $sql .= " AND t.fk_fichinter=" . $this->fk_fichinter . " AND t.fk_equipment=" . $this->fk_equipment;
            else
                $sql .= " AND t.rowid=" . $this->id;

            dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $bloc = new EIQuestionBloc($this->db, $this);
                    if ($bloc->fetch(0, $obj->fk_fichinter, $obj->fk_equipment, $obj->fk_survey_bloc, $obj->fk_c_question_bloc, $all_data, 0) < 0) {
                        $this->error = $bloc->error;
                        $this->errors = $bloc->errors;
                        return -1;
                    }
                    $bloc->read_only = 1;
                    $this->survey[$obj->fk_c_question_bloc] = $bloc;
                }
            } else {
                $this->error = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
                return -1;
            }

            if ($this->fk_equipment != 0 && $this->fichinter->statut == ExtendedIntervention::STATUS_VALIDATED) {
                if (empty($this->fichinter->array_options['options_ei_type'])) {
                    $this->fichinter->fetch_optionals();
                }

                if ($this->fetchQuestionBlocInfo() < 0)
                    return -1;

                $this->fetchParentCategoriesInfo();
                dol_include_once('/equipement/class/equipement.class.php');
                require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
                $category_static = new Categorie($this->db);

                $equipement_categories = array();
                $this->fichinter->fetchObjectLinked();
                $equipment_ids = isset($this->fichinter->linkedObjectsIds['equipement']) ? $this->fichinter->linkedObjectsIds['equipement'] : array();

                // Get list of categories (and parents) of the equipment
                foreach ($equipment_ids as $equipment_id) {
                    $equipment_static = new Equipement($this->db);
                    if ($equipment_static->fetch($equipment_id) > 0) {
                        if ($equipment_static->fk_product > 0) {
                            $categories = $category_static->containing($equipment_static->fk_product, 'product', 'id');
                            foreach ($categories as $category_id) {
                                if (isset(self::$parent_categories_cached[$category_id])) {
                                    $equipement_categories = array_merge($equipement_categories, self::$parent_categories_cached[$category_id]);
                                }
                            }
                        }
                    }
                }
                $equipement_categories = array_flip(array_flip($equipement_categories));
                $equipement_nb_categories = count($equipement_categories);

                // Filter question bloc for this intervention type and categories of the linked equipments
                if (is_array(self::$question_bloc_cached[$this->fichinter->array_options['options_ei_type']])) {
                    foreach (self::$question_bloc_cached[$this->fichinter->array_options['options_ei_type']] as $question_bloc_id => $question_bloc) {
                        if ((empty($question_bloc->fields['unique_bloc']) || empty($this->fk_equipment)) &&
                            (empty($question_bloc->fields['categories']) || count(array_diff($equipement_categories, explode(',', $question_bloc->fields['categories']))) != $equipement_nb_categories)
                        ) {
                            if (!isset($this->survey[$question_bloc->id])) {
                                $bloc = new EIQuestionBloc($this->db, $this);
                                if ($bloc->fetch(0, $this->fk_fichinter, $this->fk_equipment, 0, $question_bloc->id, $all_data, 0) < 0) {
                                    $this->error = $bloc->error;
                                    $this->errors = $bloc->errors;
                                    return -1;
                                }
                                $this->survey[$question_bloc->id] = $bloc;
                            }
                            if (isset($this->survey[$question_bloc->id])) $this->survey[$question_bloc->id]->read_only = 0;
                        }
                    }
                }
            }

            // Sort by position
            uasort($this->survey, 'ei_sort_question_bloc_position');
        }

        return 1;
    }

    /**
     *  Load the intervention of object, from id $this->fk_fichinter, into this->fichinter
     *
     * @return  int             <0 if KO, >0 if OK
     */
    function fetch_fichinter()
    {
        $result = 0;

        if (empty($this->fk_fichinter)) {
            $this->fichinter = null;
        } elseif (!isset($this->fichinter) || $this->fichinter->id != $this->fk_fichinter) {
            dol_include_once('/extendedintervention/class/extendedintervention.class.php');
            $intervention = new ExtendedIntervention($this->db);
            $result = $intervention->fetch($this->fk_fichinter);
            if ($result > 0) {
                $this->fichinter = $intervention;
            } else {
                $this->fichinter = null;
            }
        }

        return $result;
    }

    /**
     *  Load the equipment of object, from id $this->fk_equipment, into this->equipment
     *
     * @return  int             <0 if KO, >0 if OK
     */
    function fetch_equipment()
    {
        $result = 0;

        if (empty($this->fk_equipment)) {
            $this->equipment = null;
        } elseif (!isset($this->equipment) || $this->equipment->id != $this->fk_equipment) {
            dol_include_once('/equipement/class/equipement.class.php');
            $equipment = new Equipement($this->db);
            $result = $equipment->fetch($this->fk_equipment);
            if ($result > 0) {
                $this->equipment = $equipment;
            } else {
                $this->equipment = null;
            }
        }

        return $result;
    }

    /**
     *  Load the product of object, from id $this->fk_product, into this->product
     *
     * @return  int             <0 if KO, >0 if OK
     */
    function fetch_product()
    {
        $result = 0;

        if (empty($this->fk_product)) {
            $this->product = null;
        } elseif (!isset($this->product) || $this->product->id != $this->fk_product) {
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
            $product = new Product($this->db);
            $result = $product->fetch($this->fk_product);
            if ($result > 0) {
                $this->product = $product;
            } else {
                $this->product = null;
            }
        }

        return $result;
    }
}
