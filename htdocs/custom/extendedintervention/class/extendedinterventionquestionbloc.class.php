<?php
/* Copyright (C) 2018   Open-DSI            <support@open-dsi.fr>
 * Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
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
 *	\file       htdocs/extendedintervention/class/extendedinterventionquestionbloc.class.php
 *	\brief      File of class to manage question bloc
 */

/**
 *	Sort by position
 */
function ei_sort_question_position($a, $b)
{
    if (isset($a) && isset($b) && $a->position_question != $b->position_question) return ($a->position_question < $b->position_question) ? -1 : 1;
    if (isset($a) && isset($b) && $a->label_question != $b->label_question) return ($a->label_question < $b->label_question) ? -1 : 1;
    return 0;
}
function ei_sort_position($a, $b)
{
    if ($a['position'] != $b['position']) return ($a['position'] < $b['position']) ? -1 : 1;
    if ($a['label'] != $b['label']) return strcmp($a['label'], $b['label']);
    return 0;
}
function ei_sort_predefined_texts_position($a, $b)
{
    if ($a['position'] != $b['position']) return ($a['position'] < $b['position']) ? -1 : 1;
    if ($a['predefined_text'] != $b['predefined_text']) return strcmp($a['predefined_text'], $b['predefined_text']);
    return 0;
}

/**
 *	Class to manage question bloc
 */
class EIQuestionBloc extends CommonObject
{
    public $element='extendedintervention_eiquestionbloc';
    public $table_element='extendedintervention_question_bloc';
    public $table_element_parent='extendedintervention_survey_bloc';
    public $table_element_line='extendedintervention_question_blocdet';
    public $fk_element='fk_question_bloc';
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
        /*"id" => '', */"fk_fichinter" => '', "fk_equipment" => '',
        "fk_c_question_bloc" => '', "position_question_bloc" => '', "code_question_bloc" => '', "label_question_bloc" => '', "color_status" => '', "complementary_question_bloc" => '', "extrafields_question_bloc" => '',
        "fk_c_question_bloc_status" => '', "code_status" => '', "label_status" => '', "mandatory_status" => '', "icone" => '',"title_editable" => '',"bloc_complementary_editable" => '', "deletable" => '',
        "attached_files" => '', "private_bloc" => '', "desactivated_bloc" => '',"desactivate_bloc_status"=>'',
		"justificatory_status" => '', "array_options" => '',
        "status_list" => array(
            '' => array(
                "id" => '', "position" => '', "code" => '', "label" => '', "mandatory" => '',"desactivate_bloc"=>'', "predefined_texts" => array(
                    '' => array(
                        "position" => '', "code" => '', "predefined_text" => ''
                    ),
                ),
            ),
        ),
        "warning_code_question_bloc" => '', "warning_label_question_bloc" => '', "warning_extrafields_question_bloc" => '',
        "warning_code_status" => '', "warning_label_status" => '', "warning_color_status" => '', "warning_mandatory_status" => '',
        "read_only" => '', "entity" => '', "date_creation" => '', "date_modification" => '', "user_creation_id" => '', "user_creation" => '',
        "user_modification_id" => '', "user_modification" => '', "import_key" => '', "lines" => '',
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
	 * Id of the line
	 * @var int
	 */
    public $id;
    /**
     * @var EIQuestionBloc
     */
	public $oldcopy;
    /**
	 * @var EIQuestionBlocLine[]
	 */
    public $lines = array();
    /**
     * @var EIQuestionBlocLine
     */
    public $line;

    /**
     * Survey bloc ID
     * @var int
     */
    public $fk_survey_bloc;
    /**
     * Survey bloc loaded
     * @see fetch_survey_bloc()
     * @var EISurveyBloc
     */
    public $survey_bloc;
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
     * Question bloc ID into the dictionary (Custom if negative)
     * @var int
     */
    public $fk_c_question_bloc;
    /**
     * Question bloc position
     * @var int
     */
    public $position_question_bloc;
    /**
     * Question bloc code
     * @var string
     */
    public $code_question_bloc;
    /**
     * Question bloc label
     * @var string
     */
    public $label_question_bloc;
    /**
     * Question bloc complementary text
     * @var string
     */
    public $complementary_question_bloc;
    /**
     * Question bloc extra fields
     * @var array
     */
    public $extrafields_question_bloc;
    /**
     * Question bloc status ID into the dictionary (Custom if negative)
     * @var int
     */
    public $fk_c_question_bloc_status;
    /**
     * Question bloc status code
     * @var string
     */
    public $code_status;
    /**
     * Question bloc status label
     * @var string
     */
    public $label_status;
    /**
     * Question bloc status color
     * @var string
     */
    public $color_status;
    /**
     * Question bloc status text is mandatory
     * @var int
     */
    public $mandatory_status;
    /**
     * List of filename attached to this question bloc
     * @var array
     */
    public $attached_files;
    /**
     * Question bloc icon classname between material design, fontawesome and others
     * @var string
     */
    public $icone;
	 /**
     * Bloc title editable parameter
     * @var int
     */
    public $title_editable;
	 /**
     * Question bloc complementary Text editable parameter
     * @var int
     */
    public $bloc_complementary_editable;
	 /**
     * Question bloc deletable parameter
     * @var int
     */
    public $deletable;
	 /**
     * Question bloc private parameter
     * @var int
     */
    public $private_bloc;
	 /**
     * Question bloc desactivated statut
     * @var int
     */
    public $desactivated_bloc;
	/**
     * Question bloc status desactivated property
     * @var int
     */
    public $desactivate_bloc_status;
    /**
     * Question bloc status text
     * @var string
     */
    public $justificatory_status;

    /**
     * List of the question bloc status (when fetch question bloc with all data)
     * @var array
     */
    public $status_list;
    /**
     * Warning the code of the question bloc is different with dictionaries (when fetch question bloc with all data)
     * @var int
     */
    public $warning_code_question_bloc;
    /**
     * Warning the label of the question bloc is different with dictionaries (when fetch question bloc with all data)
     * @var int
     */
    public $warning_label_question_bloc;
    /**
     * Warning the extra fields of the question bloc is different with dictionaries (when fetch question bloc with all data)
     * @var int
     */
    public $warning_extrafields_question_bloc;
    /**
     * Warning the code of the status of the question bloc is different with dictionaries (when fetch question bloc with all data)
     * @var int
     */
    public $warning_code_status;
    /**
     * Warning the label of the status of the question bloc is different with dictionaries (when fetch question bloc with all data)
     * @var int
     */
    public $warning_label_status;
    /**
     * Warning the color of the status of the question bloc is different with dictionaries (when fetch question bloc with all data)
     * @var int
     */
    public $warning_color_status;
    /**
     * Warning the mandatory text of the status of the question bloc is different with dictionaries (when fetch question bloc with all data)
     * @var int
     */
    public $warning_mandatory_status;
    /**
     * True if this question bloc is not in the current survey defined into the dictionaries configuration
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
     *  Cache of the extra fields info
     * @var ExtraFields
     */
	static protected $extrafields = null;
    /**
     *  Cache of the list of question bloc information
     * @var DictionaryLine[]
     */
	static protected $question_bloc_cached = array();
    /**
     *  Cache of the list of question bloc status information
     * @var DictionaryLine[]
     */
    static protected $question_bloc_status_cached = array();
    /**
     *  Cache of the list of predefined texts of question bloc status information
     * @var DictionaryLine[]
     */
    static protected $question_bloc_status_predefined_texts_cached = array();

    /**
     *  Constructor
     *
     * @param   DoliDB                  $db             Database handler
     * @param   ExtendedIntervention    $fichinter      Intervention handler
     * @param   EISurveyBloc            $survey_bloc    Survey bloc handler
     */
    function __construct($db, $fichinter=null, $survey_bloc=null)
    {
        global $langs;

        $this->db = $db;
        $this->fichinter = $fichinter;
        $this->survey_bloc = $survey_bloc;

        $langs->load("extendedintervention@extendedintervention");
        $langs->load("errors");
    }

    /**
     *  Fetch extra fields information (cached)
     * @return  void
     */
    protected function fetchExtraFieldsInfo() {
        if (!isset(self::$extrafields)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            self::$extrafields = new ExtraFields($this->db);
            $extrafields_labels = self::$extrafields->fetch_name_optionals_label($this->table_element);
        }
    }

    /**
     *  Fetch all the question bloc information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    protected function fetchQuestionBlocInfo() {
        if (empty(self::$question_bloc_cached)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionquestionbloc');
            if ($dictionary->fetch_lines(1) > 0) {
                self::$question_bloc_cached = $dictionary->lines;
            } else {
                $this->error = $dictionary->error;
                $this->errors = array_merge($this->errors, $dictionary->errors);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Fetch all the question bloc status information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    protected function fetchQuestionBlocStatusInfo() {
        if (empty(self::$question_bloc_status_cached)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionquestionblocstatus');
            if ($dictionary->fetch_lines(1) > 0) {
                self::$question_bloc_status_cached = $dictionary->lines;
            } else {
                $this->error = $dictionary->error;
                $this->errors = array_merge($this->errors, $dictionary->errors);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Fetch all the predefined texts of the question bloc status information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    protected function fetchQuestionBlocStatusPredefinedTextsInfo() {
        if (empty(self::$question_bloc_status_predefined_texts_cached)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionquestionblocstatuspredefinedtext');
            if ($dictionary->fetch_lines(1) > 0) {
                self::$question_bloc_status_predefined_texts_cached = $dictionary->lines;
            } else {
                $this->error = $dictionary->error;
                $this->errors = array_merge($this->errors, $dictionary->errors);
                return -1;
            }
        }

        return 1;
    }

//    /**
//     *  Add a line (question with this answer) into database
//     *
//     * @param   User    $user               User that create
//     * @param   int     $fk_c_question      ID of the question
//     * @param   int     $fk_c_answer        ID of the answer
//     * @param   string  $text_answer        Text of the answer
//     * @param   array   $array_options      Extra fields array
//     * @param   int     $notrigger          1=Does not execute triggers, 0= execute triggers
//     * @return  int                         >0 if OK, <0 if KO
//     */
//	function addLine($user, $fk_c_question, $fk_c_answer, $text_answer='', $array_options=null, $notrigger=0)
//    {
//        // TODO a check and to recode because survey bloc added in the process
//        dol_syslog(__METHOD__ . " user_id=" . $user->id . " fk_c_question=" . $fk_c_question . " fk_c_answer=" . $fk_c_answer . " text_answer=" . $text_answer . " notrigger=" . $notrigger);
//
//        $error = 0;
//        $this->db->begin();
//
//        $this->line = new EIQuestionBlocLine($this->db);
//
//        $this->line->context = $this->context;
//        $this->line->fk_c_question_bloc = $this->id;
//
//        // Clean parameters
//        $this->line->fk_c_question = $fk_c_question > 0 ? $fk_c_question : 0;
//        $this->line->fk_c_answer = $fk_c_answer > 0 ? $fk_c_answer : 0;
//        $this->line->text_answer = !empty($text_answer) ? $text_answer : '';
//        $this->line->array_options = is_array($array_options) && count($array_options) ? $array_options : array();
//
//        // Insert line
//        $result = $this->line->insert($user, $notrigger);
//        if ($result < 0) {
//            $this->error = $this->line->error;
//            $this->errors = array_merge($this->errors, $this->line->errors);
//            $error++;
//        }
//
//        if (!$error) {
//            $this->db->commit();
//            return $this->line->id;
//        } else {
//            $this->db->rollback();
//            dol_syslog(__METHOD__ . " : " . $this->errorsToString(), LOG_ERR);
//            return -1;
//        }
//    }
//
//    /**
//     *  Update line (the answer of a question) into database
//     *
//     * @param   User    $user               User that update
//     * @param   int     $fk_c_question      ID of the question
//     * @param   int     $fk_c_answer        ID of the answer
//     * @param   string  $text_answer        Text of the answer
//     * @param   array   $array_options      Extra fields array
//     * @param   int     $notrigger          1=Does not execute triggers, 0= execute triggers
//     * @return  int                         >0 if OK, <0 if KO
//     */
//	function updateLine($user, $fk_c_question, $fk_c_answer, $text_answer='', $array_options=null, $notrigger=0)
//    {
//        // TODO a check and to recode because survey bloc added in the process
//        dol_syslog(__METHOD__ . " user_id=" . $user->id . " fk_c_question=" . $fk_c_question . " fk_c_answer=" . $fk_c_answer . " text_answer=" . $text_answer . " notrigger=" . $notrigger);
//
//        $error = 0;
//        $this->db->begin();
//
//        //Fetch line from the database and then clone the object and set it in $oldline property
//        $line = new EIQuestionBlocLine($this->db);
//        $result = $line->fetch('', $this->fk_fichinter, $this->fk_c_question_bloc, $fk_c_question);
//        if ($result < 0) {
//            $this->error = $line->error;
//            $this->errors = array_merge($this->errors, $line->errors);
//            $error++;
//        }
//
//        if (!$error) {
//            $line->fetch_optionals(); // Fetch extrafields for oldcopy
//
//            $static_line = clone $line;
//            $line->oldline = $static_line;
//            $this->line = $line;
//            $this->line->context = $this->context;
//
//            // Clean parameters
//            $this->line->fk_c_answer = $fk_c_answer > 0 ? $fk_c_answer : 0;
//            $this->line->text_answer = !empty($text_answer) ? $text_answer : '';
//            $this->line->array_options = is_array($array_options) && count($array_options) ? $array_options : array();
//
//            $result = $this->line->update($user, $notrigger);
//            if ($result < 0) {
//                $this->error = $this->line->error;
//                $this->errors = array_merge($this->errors, $this->line->errors);
//                $error++;
//            }
//        }
//
//        if (!$error) {
//            $this->db->commit();
//            return 1;
//        } else {
//            $this->db->rollback();
//            dol_syslog(__METHOD__ . " : " . $this->errorsToString(), LOG_ERR);
//            return -1;
//        }
//    }
//
//    /**
//     *  Delete a line (question and this answer) into database
//     *
//     * @param   User    $user               User that delete
//     * @param   int     $line_id            ID of the question
//     * @param   int     $notrigger          1=Does not execute triggers, 0= execute triggers
//     * @return  int                         >0 if OK, <0 if KO
//     */
//    function deleteLine($user, $line_id=null, $notrigger=0)
//    {
//        // TODO a check and to recode because survey bloc added in the process
//        dol_syslog(__METHOD__ . " user_id=" . $user->id . " line_id=" . $line_id . " notrigger=" . $notrigger);
//
//        $error = 0;
//        $this->db->begin();
//
//        $line = new EIQuestionBlocLine($this->db);
//
//        $result = $line->delete($user, $line_id, $notrigger);
//        if ($result < 0) {
//            $this->error = $line->error;
//            $this->errors = array_merge($this->errors, $line->errors);
//            $error++;
//        }
//
//        if (!$error) {
//            $this->db->commit();
//            return 1;
//        } else {
//            $this->db->rollback();
//            dol_syslog(__METHOD__ . " : " . $this->errorsToString(), LOG_ERR);
//            return -1;
//        }
//    }

    /**
     *  Create question bloc into database
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
        $this->fk_survey_bloc = $this->fk_survey_bloc > 0 ? $this->fk_survey_bloc : 0;
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;
        $this->position_question_bloc = !empty($this->position_question_bloc) ? $this->position_question_bloc : 0;
        $this->code_question_bloc = !empty($this->code_question_bloc) ? $this->code_question_bloc : '';
        $this->label_question_bloc = !empty($this->label_question_bloc) ? $this->label_question_bloc : '';
        $this->extrafields_question_bloc = is_array($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : (is_string($this->extrafields_question_bloc) ? explode(',', $this->extrafields_question_bloc) : array());
        $this->complementary_question_bloc = !empty($this->complementary_question_bloc) ? $this->complementary_question_bloc : '';
        $this->fk_c_question_bloc_status = isset($this->fk_c_question_bloc_status) ? $this->fk_c_question_bloc_status : 0;
        $this->code_status = !empty($this->code_status) ? $this->code_status : '';
        $this->label_status = !empty($this->label_status) ? $this->label_status : '';
        $this->color_status = !empty($this->color_status) ? $this->color_status : '';
        $this->mandatory_status = isset($this->mandatory_status) ? (!empty($this->mandatory_status) ? 1 : 0) : null;
        $this->justificatory_status = !empty($this->justificatory_status) ? $this->justificatory_status : '';
        $this->attached_files = is_array($this->attached_files) ? $this->attached_files : array();
		$this->icone = !empty($this->icone) ? $this->icone : '';
		$this->title_editable = isset($this->title_editable) ? (!empty($this->title_editable) ? 1 : 0) : null;
		$this->bloc_complementary_editable = isset($this->bloc_complementary_editable) ? (!empty($this->bloc_complementary_editable) ? 1 : 0) : null;
		$this->deletable = isset($this->deletable) ? (!empty($this->deletable) ? 1 : 0) : null;
		$this->private_bloc = isset($this->private_bloc) ? (!empty($this->private_bloc) ? 1 : 0) : null;
		$this->desactivated_bloc = isset($this->desactivated_bloc) ? (!empty($this->desactivated_bloc) ? 1 : 0) : null;
		$this->desactivate_bloc_status = isset($this->desactivate_bloc_status) ? (!empty($this->desactivate_bloc_status) ? 1 : 0) : null;

        // Check parameters
        $error = 0;
        $now = dol_now();

        if (!($this->fk_survey_bloc > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionSurveyBlocId");
            $error++;
        }
        if ($this->fk_c_question_bloc == 0) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId");
            $error++;
        } elseif ($this->fk_c_question_bloc > 0 && (empty($this->position_question_bloc) || empty($this->code_question_bloc) || empty($this->label_question_bloc) || empty($this->extrafields_question_bloc))) {
            if ($this->fetchQuestionBlocInfo() < 0) {
                $error++;
            } elseif (isset(self::$question_bloc_cached[$this->fk_c_question_bloc])) {
                $question_bloc = self::$question_bloc_cached[$this->fk_c_question_bloc];
                $this->position_question_bloc = !empty($this->position_question_bloc) ? $this->position_question_bloc : (!empty($question_bloc->fields['position']) ? $question_bloc->fields['position'] : 0);
                $this->code_question_bloc = !empty($this->code_question_bloc) ? $this->code_question_bloc : $question_bloc->fields['code'];
                $this->label_question_bloc = !empty($this->label_question_bloc) ? $this->label_question_bloc : $question_bloc->fields['label'];
				$this->icone = !empty($this->icone) ? $this->icone : $question_bloc->fields['icone'];
				$this->title_editable = !empty($this->title_editable) ? $this->title_editable : $question_bloc->fields['title_editable'];
				$this->bloc_complementary_editable = !empty($this->bloc_complementary_editable) ? $this->bloc_complementary_editable : $question_bloc->fields['bloc_complementary_editable'];
				$this->deletable = !empty($this->deletable) ? $this->deletable : $question_bloc->fields['deletable'];
				$this->private_bloc = !empty($this->private_bloc) ? $this->private_bloc : $question_bloc->fields['private_bloc'];
                $this->extrafields_question_bloc = !empty($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : array_filter(explode(',', $question_bloc->fields['extra_fields']), 'strlen');
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionBlocDictionaryInfoNotFound", $this->fk_c_question_bloc);
                $error++;
            }
        }
        if ($this->position_question_bloc === '' || !isset($this->position_question_bloc)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocPosition")) . ' (' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId") . ': ' . $this->fk_c_question_bloc . ')';
            $error++;
        }
        if (empty($this->code_question_bloc)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocCode")) . ' (' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId") . ': ' . $this->fk_c_question_bloc . ')';
            $error++;
        }
        if (empty($this->label_question_bloc)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocTitle")) . ' (' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId") . ': ' . $this->fk_c_question_bloc . ')';
            $error++;
        }
        $error_in_questionbloc = $error > 0;
        if ($this->fk_c_question_bloc_status > 0) {
            if (!$this->is_status_in_survey()) {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionBlocStatusNotInSurvey", $this->fk_c_question_bloc);
                $error++;
            } elseif ($this->fetchQuestionBlocStatusInfo() < 0) {
                $error++;
            } elseif (isset(self::$question_bloc_status_cached[$this->fk_c_question_bloc_status])) {
                $question_bloc_status = self::$question_bloc_status_cached[$this->fk_c_question_bloc_status];
                $this->code_status = !empty($this->code_status) ? $this->code_status : $question_bloc_status->fields['code'];
                $this->label_status = !empty($this->label_status) ? $this->label_status : $question_bloc_status->fields['label'];
                $this->color_status = !empty($this->color_status) ? $this->color_status : $question_bloc_status->fields['color'];
                $this->mandatory_status = isset($this->mandatory_status) ? $this->mandatory_status : (!empty($question_bloc_status->fields['mandatory']) ? 1 : 0);
                $this->desactivated_bloc_status = isset($this->desactivated_bloc_status) ? $this->desactivated_bloc_status : (!empty($question_bloc_status->fields['desactivate_bloc']) ? 1 : 0);
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionBlocStatusDictionaryInfoNotFound", $this->fk_c_question_bloc_status);
                $error++;
            }
            if ($this->mandatory_status && empty($this->justificatory_status)) {
                $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocStatusJustificatory")) . ' (' . (empty($this->label_status) ? $langs->trans("ExtendedInterventionQuestionBlocStatusId") . ': ' . $this->fk_c_question_bloc_status : $langs->trans("ExtendedInterventionQuestionBlocStatusLabel") . ': ' . $this->label_status) . ')';
                $error++;
            }
        } elseif ($this->fk_c_question_bloc_status == 0) {
            $this->code_status = "";
            $this->label_status = "";
            $this->color_status = "";
            $this->mandatory_status = 0;
            $this->justificatory_status = "";
			$this->desactivated_bloc_status = 0;
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return $error_in_questionbloc ? -4 : -5;
        }

        $this->db->begin();

        // Insert into database
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= "(fk_survey_bloc";
        $sql .= ", fk_c_question_bloc";
        $sql .= ", position_question_bloc";
        $sql .= ", code_question_bloc";
        $sql .= ", label_question_bloc";
        $sql .= ", complementary_question_bloc";
        $sql .= ", extrafields_question_bloc";
        $sql .= ", fk_c_question_bloc_status";
        $sql .= ", code_status";
        $sql .= ", label_status";
        $sql .= ", color_status";
        $sql .= ", mandatory_status";
        $sql .= ", justificatory_status";
        $sql .= ", attached_files";

		$sql .= ", icone";
		$sql .= ", title_editable";
		$sql .= ", bloc_complementary_editable";
		$sql .= ", deletable";
		$sql .= ", private_bloc";
		$sql .= ", desactivated_bloc";
		$sql .= ", desactivate_bloc_status";

        $sql .= ", fk_user_author";
        $sql .= ", datec";
        $sql .= ", entity";
        $sql .= ") VALUES (";
        $sql .= $this->fk_survey_bloc;
        $sql .= ", " . $this->fk_c_question_bloc;
        $sql .= ", " . $this->position_question_bloc;
        $sql .= ", '" . $this->db->escape($this->code_question_bloc) . "'";
        $sql .= ", '" . $this->db->escape($this->label_question_bloc) . "'";
        $sql .= ", " . (!empty($this->complementary_question_bloc) ? "'" . $this->db->escape($this->complementary_question_bloc) . "'" : 'NULL');
        $sql .= ", " . (count($this->extrafields_question_bloc) ? "'" . $this->db->escape(implode(',', $this->extrafields_question_bloc)) . "'" : 'NULL');
        $sql .= ", " .  ($this->fk_c_question_bloc_status != 0 ? $this->fk_c_question_bloc_status : 'NULL');
        $sql .= ", " .  (!empty($this->code_status) ? "'" . $this->db->escape($this->code_status) . "'" : 'NULL');
        $sql .= ", " .  (!empty($this->label_status) ? "'" . $this->db->escape($this->label_status) . "'" : 'NULL');
        $sql .= ", " .  (!empty($this->color_status) ? "'" . $this->db->escape($this->color_status) . "'" : 'NULL');
        $sql .= ", " .  (!empty($this->mandatory_status) ? $this->mandatory_status : 'NULL');
        $sql .= ", " . (!empty($this->justificatory_status) ? "'" . $this->db->escape($this->justificatory_status) . "'" : 'NULL');
        $sql .= ", " . (count($this->attached_files) ? "'" . $this->db->escape(serialize($this->attached_files)) . "'" : 'NULL');

        $sql .= ", " . (!empty($this->icone) ? "'" . $this->db->escape($this->icone) . "'" : 'NULL');
		$sql .= ", " . (!empty($this->title_editable) ? "'" . $this->db->escape($this->title_editable) . "'" : 'NULL');
		$sql .= ", " . (!empty($this->bloc_complementary_editable) ? "'" . $this->db->escape($this->bloc_complementary_editable) . "'" : 'NULL');
		$sql .= ", " . (!empty($this->deletable) ? "'" . $this->db->escape($this->deletable) . "'" : 'NULL');
		$sql .= ", " . (!empty($this->private_bloc) ? "'" . $this->db->escape($this->private_bloc) . "'" : 'NULL');
		$sql .= ", " . (!empty($this->desactivated_bloc) ? "'" . $this->db->escape($this->desactivated_bloc) . "'" : 'NULL');
		$sql .= ", " . (!empty($this->desactivate_bloc_status) ? "'" . $this->db->escape($this->desactivate_bloc_status) . "'" : 'NULL');

        $sql .= ", " . $user->id;
        $sql .= ", '" . $this->db->idate($now) . "'";
        $sql .= ", " . $conf->entity;
        $sql .= ")";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                }
            }

            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('EI_QUESTION_BLOCK_CREATE', $user);
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
     *  Update question bloc into database
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
        $this->fk_survey_bloc = $this->fk_survey_bloc > 0 ? $this->fk_survey_bloc : 0;
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;
        $this->position_question_bloc = !empty($this->position_question_bloc) ? $this->position_question_bloc : 0;
        $this->code_question_bloc = !empty($this->code_question_bloc) ? $this->code_question_bloc : '';
        $this->label_question_bloc = !empty($this->label_question_bloc) ? $this->label_question_bloc : '';
        $this->complementary_question_bloc = !empty($this->complementary_question_bloc) ? $this->complementary_question_bloc : '';
        $this->extrafields_question_bloc = is_array($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : (is_string($this->extrafields_question_bloc) ? explode(',', $this->extrafields_question_bloc) : array());
        $this->fk_c_question_bloc_status = isset($this->fk_c_question_bloc_status) ? $this->fk_c_question_bloc_status : 0;
        $this->code_status = !empty($this->code_status) ? $this->code_status : '';
        $this->label_status = !empty($this->label_status) ? $this->label_status : '';
        $this->color_status = !empty($this->color_status) ? $this->color_status : '';
        $this->mandatory_status = isset($this->mandatory_status) ? (!empty($this->mandatory_status) ? 1 : 0) : null;
        $this->justificatory_status = !empty($this->justificatory_status) ? $this->justificatory_status : '';
        $this->attached_files = is_array($this->attached_files) ? $this->attached_files : array();
		$this->icone = !empty($this->icone) ? $this->icone : '';
		$this->title_editable = isset($this->title_editable) ? (!empty($this->title_editable) ? 1 : 0) : null;
		$this->bloc_complementary_editable = isset($this->bloc_complementary_editable) ? (!empty($this->bloc_complementary_editable) ? 1 : 0) : null;
		$this->deletable = isset($this->deletable) ? (!empty($this->deletable) ? 1 : 0) : null;
		$this->private_bloc = isset($this->private_bloc) ? (!empty($this->private_bloc) ? 1 : 0) : null;
		$this->desactivated_bloc = isset($this->desactivated_bloc) ? (!empty($this->desactivated_bloc) ? 1 : 0) : null;
		$this->desactivate_bloc_status = isset($this->desactivate_bloc_status) ? (!empty($this->desactivate_bloc_status) ? 1 : 0) : null;

        // Check parameters
        $error = 0;
        $now = dol_now();

        if (!($this->fk_survey_bloc > 0) && !($this->fk_fichinter > 0) && $this->fk_c_question_bloc == 0 && !($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocId");
            $error++;
        } else {
            if (!($this->fk_survey_bloc > 0) && !($this->fk_fichinter > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionSurveyBlocId");
                $error++;
            } elseif (!($this->fk_fichinter > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionInterventionId");
                $error++;
            }
            if ($this->fk_c_question_bloc == 0) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId");
                $error++;
            }
        }
        if ($this->fk_c_question_bloc > 0 && (empty($this->position_question_bloc) || empty($this->code_question_bloc) || empty($this->label_question_bloc) || empty($this->extrafields_question_bloc))) {
            if ($this->fetchQuestionBlocInfo() < 0) {
                $error++;
            } elseif (isset(self::$question_bloc_cached[$this->fk_c_question_bloc])) {
                $question_bloc = self::$question_bloc_cached[$this->fk_c_question_bloc];
                $this->position_question_bloc = !empty($this->position_question_bloc) ? $this->position_question_bloc : (!empty($question_bloc->fields['position']) ? $question_bloc->fields['position'] : 0);
                $this->code_question_bloc = !empty($this->code_question_bloc) ? $this->code_question_bloc : $question_bloc->fields['code'];
                $this->label_question_bloc = !empty($this->label_question_bloc) ? $this->label_question_bloc : $question_bloc->fields['label'];
                $this->extrafields_question_bloc = !empty($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : array_filter(explode(',', $question_bloc->fields['extra_fields']), 'strlen');
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionBlocDictionaryInfoNotFound", $this->fk_c_question_bloc);
                $error++;
            }
        }
        if ($this->position_question_bloc === '' || !isset($this->position_question_bloc)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocPosition")) . ' (' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId") . ': ' . $this->fk_c_question_bloc . ')';
            $error++;
        }
        if (empty($this->code_question_bloc)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocCode")) . ' (' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId") . ': ' . $this->fk_c_question_bloc . ')';
            $error++;
        }
        if (empty($this->label_question_bloc)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocTitle")) . ' (' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId") . ': ' . $this->fk_c_question_bloc . ')';
            $error++;
        }
        $error_in_questionbloc = $error > 0;
        if ($this->fk_c_question_bloc_status > 0) {
            if (!$this->is_status_in_survey()) {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionBlocStatusNotInSurvey", $this->fk_c_question_bloc);
                $error++;
            } elseif ($this->fetchQuestionBlocStatusInfo() < 0) {
                $error++;
            } elseif (isset(self::$question_bloc_status_cached[$this->fk_c_question_bloc_status])) {
                $question_bloc_status = self::$question_bloc_status_cached[$this->fk_c_question_bloc_status];
                $this->code_status = !empty($this->code_status) ? $this->code_status : $question_bloc_status->fields['code'];
                $this->label_status = !empty($this->label_status) ? $this->label_status : $question_bloc_status->fields['label'];
                $this->color_status = !empty($this->color_status) ? $this->color_status : $question_bloc_status->fields['color'];
                $this->mandatory_status = isset($this->mandatory_status) ? $this->mandatory_status : (!empty($question_bloc_status->fields['mandatory']) ? 1 : 0);
				$this->desactivated_bloc_status = isset($this->desactivated_bloc_status) ? $this->desactivated_bloc_status : (!empty($question_bloc_status->fields['desactivate_bloc']) ? 1 : 0);
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionBlocStatusDictionaryInfoNotFound", $this->fk_c_question_bloc_status);
                $error++;
            }
            if ($this->mandatory_status && empty($this->justificatory_status)) {
                $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionBlocStatusJustificatory")) . ' (' . (empty($this->label_status) ? $langs->trans("ExtendedInterventionQuestionBlocStatusId") . ': ' . $this->fk_c_question_bloc_status : $langs->trans("ExtendedInterventionQuestionBlocStatusLabel") . ': ' . $this->label_status) . ')';
                $error++;
            }
        } elseif ($this->fk_c_question_bloc_status == 0) {
            $this->code_status = "";
            $this->label_status = "";
            $this->color_status = "";
            $this->mandatory_status = 0;
            $this->justificatory_status = "";
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return $error_in_questionbloc ? -4 : -5;
        }

        $this->db->begin();

        // Update into database
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " AS t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " as p ON t.fk_survey_bloc = p.rowid";
        $sql .= " SET";
        $sql .= " t.position_question_bloc = " . $this->position_question_bloc;
        $sql .= ", t.code_question_bloc = '" . $this->db->escape($this->code_question_bloc) . "'";
        $sql .= ", t.label_question_bloc = '" . $this->db->escape($this->label_question_bloc) . "'";
        $sql .= ", t.complementary_question_bloc = " . (!empty($this->complementary_question_bloc) ? "'" . $this->db->escape($this->complementary_question_bloc) . "'" : 'NULL');
        $sql .= ", t.extrafields_question_bloc = " . (count($this->extrafields_question_bloc) ? "'" . $this->db->escape(implode(',', $this->extrafields_question_bloc)) . "'" : 'NULL');
        $sql .= ", t.fk_c_question_bloc_status = " . ($this->fk_c_question_bloc_status != 0 ? $this->fk_c_question_bloc_status : 'NULL');
        $sql .= ", t.code_status = " . (!empty($this->code_status) ? "'" . $this->db->escape($this->code_status) . "'" : 'NULL');
        $sql .= ", t.label_status = " . (!empty($this->label_status) ? "'" . $this->db->escape($this->label_status) . "'" : 'NULL');
        $sql .= ", t.color_status = " . (!empty($this->color_status) ? "'" . $this->db->escape($this->color_status) . "'" : 'NULL');
        $sql .= ", t.mandatory_status = " . (!empty($this->mandatory_status) ? $this->mandatory_status : 'NULL');
        $sql .= ", t.justificatory_status = " . (!empty($this->justificatory_status) ? "'" . $this->db->escape($this->justificatory_status) . "'" : "NULL");
        $sql .= ", t.attached_files = " . (!empty($this->attached_files) ? "'" . $this->db->escape(serialize($this->attached_files)) . "'" : "NULL");

        $sql .= ", t.icone = " . (!empty($this->icone) ? "'" . $this->db->escape($this->icone) . "'" : 'NULL');
		$sql .= ", t.title_editable = " . (!empty($this->title_editable) ? "'" . $this->db->escape($this->title_editable) . "'" : 'NULL');
		$sql .= ", t.bloc_complementary_editable = " . (!empty($this->bloc_complementary_editable) ? "'" . $this->db->escape($this->bloc_complementary_editable) . "'" : 'NULL');
		$sql .= ", t.deletable = " . (!empty($this->deletable) ? "'" . $this->db->escape($this->deletable) . "'" : 'NULL');
		$sql .= ", t.private_bloc = " . (!empty($this->private_bloc) ? "'" . $this->db->escape($this->private_bloc) . "'" : 'NULL');
		$sql .= ", t.desactivated_bloc = " . (!empty($this->desactivated_bloc) ? "'" . $this->db->escape($this->desactivated_bloc) . "'" : 'NULL');
		$sql .= ", t.desactivate_bloc_status = " . (!empty($this->desactivate_bloc_status) ? "'" . $this->db->escape($this->desactivate_bloc_status) . "'" : 'NULL');

        $sql .= ", t.fk_user_modif = " . $user->id;
        $sql .= ", t.tms = '" . $this->db->idate($now) . "'";
        $sql .= " WHERE t.entity IN (" . getEntity('ei_question_bloc') . ")";
        if ($this->fk_fichinter > 0 && $this->fk_c_question_bloc != 0)
            $sql .= " AND p.fk_fichinter=" . $this->fk_fichinter . " AND p.fk_equipment=" . $this->fk_equipment . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
        elseif ($this->fk_survey_bloc > 0 && $this->fk_c_question_bloc != 0)
            $sql .= " AND t.fk_survey_bloc=" . $this->fk_survey_bloc . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
        else
            $sql .= " AND t.rowid=" . $this->id;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                }
            }

            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('EI_QUESTION_BLOCK_UPDATE', $user);
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
     *  Delete question bloc into database
     *
     * @param   User    $user           Object user that delete
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  int                     1 if ok, otherwise if error
     */
    function delete($user, $notrigger=0)
    {
        global $conf, $langs;

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->fk_survey_bloc = $this->fk_survey_bloc > 0 ? $this->fk_survey_bloc : 0;
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;

        // Check parameters
        $error = 0;

        if (!($this->fk_survey_bloc > 0) && !($this->fk_fichinter > 0) && $this->fk_c_question_bloc == 0 && !($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocId");
            $error++;
        } else {
            if (!($this->fk_survey_bloc > 0) && !($this->fk_fichinter > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionSurveyBlocId");
                $error++;
            } elseif (!($this->fk_fichinter > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionInterventionId");
                $error++;
            }
            if ($this->fk_c_question_bloc == 0) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId");
                $error++;
            }
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        if (!$notrigger) {
            // Call trigger
            $result = $this->call_trigger('EI_QUESTION_BLOCK_DELETE', $user);
            if ($result < 0) {
                $error++;
            }
            // End call triggers
        }

        // Removed extrafields of the questions
        if (!$error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element_line . "_extrafields FROM " . MAIN_DB_PREFIX . $this->table_element_line . "_extrafields" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_line . " as d ON " . MAIN_DB_PREFIX . $this->table_element_line . "_extrafields.fk_object = d.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON d.fk_question_bloc = t.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " as p ON t.fk_survey_bloc = p.rowid" .
                " WHERE p.entity IN (" . getEntity('ei_question_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND p.fk_fichinter=" . $this->fk_fichinter . " AND p.fk_equipment=" . $this->fk_equipment . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
            elseif ($this->fk_survey_bloc > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND t.fk_survey_bloc=" . $this->fk_survey_bloc . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
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
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element_line . " FROM " . MAIN_DB_PREFIX . $this->table_element_line .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON " . MAIN_DB_PREFIX . $this->table_element_line . ".fk_question_bloc = t.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " as p ON t.fk_survey_bloc = p.rowid" .
                " WHERE p.entity IN (" . getEntity('ei_question_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND p.fk_fichinter=" . $this->fk_fichinter . " AND p.fk_equipment=" . $this->fk_equipment . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
            elseif ($this->fk_survey_bloc > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND t.fk_survey_bloc=" . $this->fk_survey_bloc . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
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
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element . "_extrafields FROM " . MAIN_DB_PREFIX . $this->table_element . "_extrafields" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON " . MAIN_DB_PREFIX . $this->table_element . "_extrafields.fk_object = t.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " as p ON t.fk_survey_bloc = p.rowid" .
                " WHERE p.entity IN (" . getEntity('ei_question_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND p.fk_fichinter=" . $this->fk_fichinter . " AND p.fk_equipment=" . $this->fk_equipment . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
            elseif ($this->fk_survey_bloc > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND t.fk_survey_bloc=" . $this->fk_survey_bloc . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
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
            $sql = "DELETE " . MAIN_DB_PREFIX . $this->table_element . " FROM " . MAIN_DB_PREFIX . $this->table_element .
                " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " as p ON " . MAIN_DB_PREFIX . $this->table_element . ".fk_survey_bloc = p.rowid" .
                " WHERE " . MAIN_DB_PREFIX . $this->table_element . ".entity IN (" . getEntity('ei_question_bloc') . ")";
            if ($this->fk_fichinter > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND p.fk_fichinter=" . $this->fk_fichinter . " AND p.fk_equipment=" . $this->fk_equipment . " AND " . MAIN_DB_PREFIX . $this->table_element . ".fk_c_question_bloc=" . $this->fk_c_question_bloc;
            elseif ($this->fk_survey_bloc > 0 && $this->fk_c_question_bloc != 0)
                $sql .= " AND " . MAIN_DB_PREFIX . $this->table_element . ".fk_survey_bloc=" . $this->fk_survey_bloc . " AND " . MAIN_DB_PREFIX . $this->table_element . ".fk_c_question_bloc=" . $this->fk_c_question_bloc;
            else
                $sql .= " AND " . MAIN_DB_PREFIX . $this->table_element . ".rowid=" . $this->id;
            if (!$this->db->query($sql)) {
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                $this->errors[] = $this->db->lasterror();
                $error++;
            }
        }

        if (!$error) {
            dol_syslog(__METHOD__ . ' rowid=' . $this->id . " fk_survey_bloc=" . $this->fk_survey_bloc . " fk_fichinter=" . $this->fk_fichinter . " fk_equipment=" . $this->fk_equipment . " fk_c_question_bloc=" . $this->fk_c_question_bloc . " by user_id=" . $user->id, LOG_DEBUG);
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Load a question bloc from database and its line array (question and answer)
     *
     * @param   int     $rowid                  ID of object to load
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $fk_survey_bloc         ID of the survey bloc
     * @param   int     $fk_c_question_bloc     ID of the question bloc in the dictionary
     * @param   int     $all_data               1=Load all data of the dictionaries (all status, all answer and all predefined text)
     * @param   int     $test_exist             1=Test if the question bloc exist in the survey of the intervention
     * @return  int                             <0 if KO, >=0 if OK
     */
    function fetch($rowid=0, $fk_fichinter=0, $fk_equipment=0, $fk_survey_bloc=0, $fk_c_question_bloc=0, $all_data=0, $test_exist=1)
    {
        global $langs;

        // Clean parameters
        $rowid = $rowid > 0 ? $rowid : 0;
        $fk_survey_bloc = $fk_survey_bloc > 0 ? $fk_survey_bloc : 0;
        $fk_fichinter = $fk_fichinter > 0 ? $fk_fichinter : 0;
        $fk_equipment = isset($fk_equipment) ? $fk_equipment : 0;
        $fk_c_question_bloc = isset($fk_c_question_bloc) ? $fk_c_question_bloc : 0;

        $sql = "SELECT t.rowid, p.fk_fichinter, p.fk_equipment";
        $sql .= ", t.fk_c_question_bloc, t.position_question_bloc, t.code_question_bloc, t.label_question_bloc, t.complementary_question_bloc, t.extrafields_question_bloc";
        $sql .= ", t.fk_c_question_bloc_status, t.code_status, t.label_status, t.color_status, t.mandatory_status, t.justificatory_status, t.attached_files";
		$sql .= ", t.icone, t.title_editable, t.bloc_complementary_editable, t.deletable, t.private_bloc, t.desactivated_bloc, t.desactivate_bloc_status";
        $sql .= ", t.datec, t.tms, t.fk_user_author, t.fk_user_modif, t.import_key";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " AS t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " AS p ON p.rowid = t.fk_survey_bloc";
        $sql .= " WHERE t.entity IN (" . getEntity('ei_question_bloc') . ")";
        if ($fk_fichinter > 0 && $fk_c_question_bloc != 0)
            $sql .= " AND p.fk_fichinter=" . $fk_fichinter . " AND p.fk_equipment=" . $fk_equipment . " AND t.fk_c_question_bloc=" . $fk_c_question_bloc;
        elseif ($fk_survey_bloc > 0 && $fk_c_question_bloc != 0)
            $sql .= " AND t.fk_survey_bloc=" . $fk_survey_bloc . " AND t.fk_c_question_bloc=" . $fk_c_question_bloc;
        else
            $sql .= " AND t.rowid=" . $rowid;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->fetchQuestionBlocInfo();
            $this->status_list = array();
            $this->warning_code_question_bloc = 0;
            $this->warning_label_question_bloc = 0;
            $this->warning_extrafields_question_bloc = 0;
            $this->warning_code_status = 0;
            $this->warning_label_status = 0;
            $this->warning_color_status = 0;
            $this->warning_mandatory_status = 0;

            $num = $this->db->num_rows($resql);
            if ($num) {
                $obj = $this->db->fetch_object($resql);

                $this->id                           = $obj->rowid;
                $this->fk_fichinter                 = $obj->fk_fichinter;
                $this->fk_equipment                 = $obj->fk_equipment;
                $this->fk_c_question_bloc           = $obj->fk_c_question_bloc;
                $this->position_question_bloc       = $obj->position_question_bloc;
                $this->code_question_bloc           = $obj->code_question_bloc;
                $this->label_question_bloc          = $obj->label_question_bloc;
                $this->complementary_question_bloc  = $obj->complementary_question_bloc;
                $this->extrafields_question_bloc    = !empty($obj->extrafields_question_bloc) ? explode(',', $obj->extrafields_question_bloc) : array();
                $this->fk_c_question_bloc_status    = $obj->fk_c_question_bloc_status;
                $this->code_status                  = $obj->code_status;
                $this->label_status                 = $obj->label_status;
                $this->color_status                 = $obj->color_status;
                $this->mandatory_status             = !empty($obj->mandatory_status) ? 1 : 0;
                $this->justificatory_status         = $obj->justificatory_status;
                $this->attached_files               = !empty($obj->attached_files) ? unserialize($obj->attached_files) : array();

				$this->icone 						= $obj->icone;
				$this->title_editable 				= !empty($obj->title_editable) ? 1 : 0;
				$this->bloc_complementary_editable  = !empty($obj->bloc_complementary_editable) ? 1 : 0;
				$this->deletable					= !empty($obj->deletable) ? 1 : 0;
				$this->private_bloc				    = !empty($obj->private_bloc) ? 1 : 0;
				$this->desactivated_bloc 			= !empty($obj->desactivated_bloc) ? 1 : 0;
				$this->desactivate_bloc_status 		= !empty($obj->desactivate_bloc_status) ? 1 : 0;

                $this->date_creation                = $this->db->jdate($obj->datec);
                $this->date_modification            = $this->db->jdate($obj->tms);
                $this->user_creation_id             = $obj->fk_user_author;
                $this->user_modification_id         = $obj->fk_user_modif;
                $this->import_key                   = $obj->import_key;

                $this->db->free($resql);
            } elseif (($fk_survey_bloc > 0 || $fk_fichinter > 0) && $fk_c_question_bloc != 0 &&
                ($fk_c_question_bloc < 0 || (isset(self::$question_bloc_cached[$fk_c_question_bloc]) && (!$test_exist || $fk_equipment < 0 || $this->is_in_survey($fk_fichinter, $fk_equipment, $fk_c_question_bloc))))) {
                $question_bloc = self::$question_bloc_cached[$fk_c_question_bloc];

                $this->id                           = 0;
                $this->fk_fichinter                 = $fk_fichinter;
                $this->fk_equipment                 = $fk_equipment;
                $this->fk_c_question_bloc           = $fk_c_question_bloc;
                $this->position_question_bloc       = !empty($question_bloc->fields['position']) ? $question_bloc->fields['position'] : 0;
                $this->code_question_bloc           = !empty($question_bloc->fields['code']) ? $question_bloc->fields['code'] : '';
                $this->label_question_bloc          = !empty($question_bloc->fields['label']) ? $question_bloc->fields['label'] : '';
                $this->complementary_question_bloc  = '';
                $this->extrafields_question_bloc    = !empty($question_bloc->fields['extra_fields']) ? explode(',', $question_bloc->fields['extra_fields']) : array();
                $this->fk_c_question_bloc_status    = 0;
                $this->code_status                  = '';
                $this->label_status                 = '';
                $this->color_status                 = '';
                $this->mandatory_status             = 0;
                $this->justificatory_status         = '';
                $this->attached_files               = array();

				$this->icone 						= !empty($question_bloc->fields['icone']) ? $question_bloc->fields['icone'] : '';
				$this->title_editable 				= !empty($question_bloc->fields['title_editable']) ? $question_bloc->fields['title_editable'] : 0;
				$this->bloc_complementary_editable  = !empty($question_bloc->fields['bloc_complementary_editable']) ? $question_bloc->fields['bloc_complementary_editable'] : 0;
				$this->deletable					= !empty($question_bloc->fields['deletable']) ? $question_bloc->fields['deletable'] : 0;
				$this->private_bloc				    = !empty($question_bloc->fields['private_bloc']) ? $question_bloc->fields['private_bloc'] : 0;
				$this->desactivated_bloc 			= !empty($question_bloc->fields['desactivated_bloc']) ? $question_bloc->fields['desactivated_bloc'] : 0;
				$this->desactivate_bloc_status 		= 0;

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
                $question_bloc = self::$question_bloc_cached[$this->fk_c_question_bloc];
                $this->fetchQuestionBlocStatusInfo();
                $this->fetchQuestionBlocStatusPredefinedTextsInfo();
                $status_list = !empty($question_bloc->fields['status']) ? explode(',', $question_bloc->fields['status']) : array();

                // Load status list
                foreach ($status_list as $status_id) {
                    $this->status_list[$status_id] = array_merge(array('id' => $status_id), self::$question_bloc_status_cached[$status_id]->fields);

                    // Load predefined text for the status
                    $predefined_texts_list = !empty($this->status_list[$status_id]['predefined_texts']) ? explode(',', $this->status_list[$status_id]['predefined_texts']) : array();
                    $this->status_list[$status_id]['predefined_texts'] = array();
                    foreach ($predefined_texts_list as $predefined_texts_id) {
                        $this->status_list[$status_id]['predefined_texts'][$predefined_texts_id] = self::$question_bloc_status_predefined_texts_cached[$predefined_texts_id]->fields;
                    }
                }

                // Sort by position
                uasort($this->status_list, 'ei_sort_position');
                foreach ($this->status_list as $k => $v) {
                    $tab = $this->status_list[$k]['predefined_texts'];
                    uasort($tab, 'ei_sort_predefined_texts_position');
                    $this->status_list[$k]['predefined_texts'] = $tab;
                }

                // Check if has value modified with the dictionaries
                if ($this->id > 0 && $this->fk_c_question_bloc > 0) {
                    // Code of the question bloc
                    if ($this->code_question_bloc != $question_bloc->fields['code']) {
                        $this->code_question_bloc = $question_bloc->fields['code'];
                        $this->warning_code_question_bloc = 1;
                    }
                    // Label of the question bloc
                    if ($this->label_question_bloc != $question_bloc->fields['label']) {
                        $this->label_question_bloc = $question_bloc->fields['label'];
                        $this->warning_label_question_bloc = 1;
                    }
                    // Extra fields of the question bloc
                    $new_extra_fields = !empty($question_bloc->fields['extra_fields']) ? explode(',', $question_bloc->fields['extra_fields']) : array();
                    if (count(array_diff($this->extrafields_question_bloc, $new_extra_fields))) {
                        $this->extrafields_question_bloc = $new_extra_fields;
                        $this->warning_extrafields_question_bloc = 1;
                    }
                    // Code of the status of the question bloc
                    if ($this->code_status != self::$question_bloc_status_cached[$this->fk_c_question_bloc_status]->fields['code']) {
                        $this->code_status = self::$question_bloc_status_cached[$this->fk_c_question_bloc_status]->fields['code'];
                        $this->warning_code_status = 1;
                    }
                    // Label of the status of the question bloc
                    if ($this->label_status != self::$question_bloc_status_cached[$this->fk_c_question_bloc_status]->fields['label']) {
                        $this->label_status = self::$question_bloc_status_cached[$this->fk_c_question_bloc_status]->fields['label'];
                        $this->warning_label_status = 1;
                    }
                    // Label of the status of the question bloc
                    if ($this->color_status != self::$question_bloc_status_cached[$this->fk_c_question_bloc_status]->fields['color']) {
                        $this->color_status = self::$question_bloc_status_cached[$this->fk_c_question_bloc_status]->fields['color'];
                        $this->warning_color_status = 1;
                    }
                    // Mandatory text of the status of the question bloc
                    //$new_mandatory_status = !empty(self::$question_bloc_status_cached[$this->fk_c_question_bloc_status]->fields['mandatory']) ? 1 : 0;
                    //if ($this->mandatory_status != $new_mandatory_status) {
                    //    $this->mandatory_status = $new_mandatory_status;
                    //    $this->warning_mandatory_status = 1;
                    //}

					//All fields in this list : mandatory_status, icone, title_editable, bloc_complementary_editable ,deletable, private_bloc, desactivated_bloc, desactivated_bloc_status
					// aren't checked to be able to modify them by the api and to display these forced informations

                    $this->position_question_bloc       = $question_bloc->fields['position'];
                    $this->code_question_bloc           = $question_bloc->fields['code'];
                    $this->label_question_bloc          = $question_bloc->fields['label'];
                    $this->extrafields_question_bloc    = !empty($question_bloc->fields['extra_fields']) ? explode(',', $question_bloc->fields['extra_fields']) : array();
                }
            }

            $this->fetch_optionals();

            $result = $this->fetch_lines($all_data);
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
	 * Load array lines (question and answer)
	 *
     * @param   int     $all_data       1=Load all data of the dictionaries (all status, all answer and all predefined text)
	 * @return  int                     <0 if KO, >0 if OK
	 */
	function fetch_lines($all_data=0)
    {
        global $langs;

        $this->lines = array();

        // Clean parameters
        $this->fk_survey_bloc = $this->fk_survey_bloc > 0 ? $this->fk_survey_bloc : 0;
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;

        // Check parameters
        $error = 0;
        if (!($this->fk_survey_bloc > 0) && !($this->fk_fichinter > 0) && $this->fk_c_question_bloc == 0 && !($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocId");
            $error++;
        } else {
            if (!($this->fk_survey_bloc > 0) && !($this->fk_fichinter > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionSurveyBlocId");
                $error++;
            } elseif (!($this->fk_fichinter > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionInterventionId");
                $error++;
            }
            if ($this->fk_c_question_bloc == 0) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocDictionaryId");
                $error++;
            }
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->fetch_fichinter();
        if ($this->fichinter->statut != ExtendedIntervention::STATUS_VALIDATED) $all_data = 0;

        $sql = "SELECT p.fk_fichinter, p.fk_equipment, t.fk_c_question_bloc, d.fk_question_bloc, d.fk_c_question".
            " FROM " . MAIN_DB_PREFIX . $this->table_element_line . " AS d" .
            " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " as t ON d.fk_question_bloc = t.rowid" .
            " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " as p ON d.fk_question_bloc = t.rowid" .
            " WHERE t.entity IN (" . getEntity('ei_question_bloc') . ")";
        if ($this->fk_fichinter > 0 && $this->fk_c_question_bloc != 0)
            $sql .= " AND p.fk_fichinter=" . $this->fk_fichinter . " AND p.fk_equipment=" . $this->fk_equipment . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
        elseif ($this->fk_survey_bloc > 0 && $this->fk_c_question_bloc != 0)
            $sql .= " AND t.fk_survey_bloc=" . $this->fk_survey_bloc . " AND t.fk_c_question_bloc=" . $this->fk_c_question_bloc;
        else
            $sql .= " AND t.rowid=" . $this->id;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $question = new EIQuestionBlocLine($this->db);
                $question->fetch(0, $obj->fk_fichinter, $obj->fk_equipment, $obj->fk_c_question_bloc, $obj->fk_question_bloc, $obj->fk_c_question, $all_data, 0);
                $question->read_only = 1;
                $this->lines[$obj->fk_c_question] = $question;
            }
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        if ($this->fichinter->statut == ExtendedIntervention::STATUS_VALIDATED) {
            $this->fetchQuestionBlocInfo();

            $question_list = !empty(self::$question_bloc_cached[$this->fk_c_question_bloc]->fields['questions']) ? explode(',', self::$question_bloc_cached[$this->fk_c_question_bloc]->fields['questions']) : array();

            foreach ($question_list as $question_id) {
                if (!isset($this->lines[$question_id])) {
                    $question = new EIQuestionBlocLine($this->db);
                    $question->fetch(0, $this->fk_fichinter, $this->fk_equipment, $this->fk_c_question_bloc, $this->id, $question_id, $all_data, 0);
                    $this->lines[$question_id] = $question;
                }
                if (isset($this->lines[$question_id])) $this->lines[$question_id]->read_only = 0;
            }
        }

        // Sort by position
        uasort($this->lines, 'ei_sort_question_position');

        return 1;
    }

    /**
     *  Retrieve an array lines (question and answer)
	 *
     * @param   int     $all_data       1=Load all data of the dictionaries (all status, all answer and all predefined text)
	 * @return  int                     <0 if KO, >0 if OK
     */
    function getLinesArray($all_data=0)
    {
        return $this->fetch_lines($all_data);
    }

    /**
     *  Is the question bloc read only
     *
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $fk_c_question_bloc     ID of the question bloc in the dictionary
     * @return  int                             =0 if No, >0 if Yes
     */
    function is_read_only($fk_fichinter=null, $fk_equipment=null, $fk_c_question_bloc=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;
        if (isset($fk_c_question_bloc)) $this->fk_c_question_bloc = $fk_c_question_bloc;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;

        $this->fetch_fichinter();
        if (isset($this->fichinter)) {
            if (!is_array($this->fichinter->survey) || count($this->fichinter->survey) == 0) {
                $this->fichinter->fetch_survey(0);
            }

            if (empty($this->fichinter->survey[$this->fk_equipment]->survey[$this->fk_c_question_bloc]->read_only)) {
                $this->read_only = 0;
                return 0;
            }
        }

        $this->read_only = 1;
        return 1;
    }

    /**
     *  Is the question bloc in the survey
     *
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $fk_c_question_bloc     ID of the question bloc in the dictionary
     * @return  int                             =0 if No, >0 if Yes
     */
    function is_in_survey($fk_fichinter=null, $fk_equipment=null, $fk_c_question_bloc=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;
        if (isset($fk_c_question_bloc)) $this->fk_c_question_bloc = $fk_c_question_bloc;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;

        $this->fetch_fichinter();
        if (isset($this->fichinter)) {
            if (!is_array($this->fichinter->survey) || count($this->fichinter->survey) == 0) {
                $this->fichinter->fetch_survey(0);
            }

            if (isset($this->fichinter->survey[$this->fk_equipment]->survey[$this->fk_c_question_bloc])) {
                return 1;
            }
        }

        return 0;
    }

    /**
     *  Is the question bloc status in the survey
     *
     * @param   int     $fk_fichinter                   ID of the intervention
     * @param   int     $fk_equipment                   ID of the equipment
     * @param   int     $fk_c_question_bloc             ID of the question bloc in the dictionary
     * @param   int     $fk_c_question_bloc_status      ID of the question bloc status in the dictionary
     * @return  int                                     =0 if No, >0 if Yes
     */
    function is_status_in_survey($fk_fichinter=null, $fk_equipment=null, $fk_c_question_bloc=null, $fk_c_question_bloc_status=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;
        if (isset($fk_c_question_bloc)) $this->fk_c_question_bloc = $fk_c_question_bloc;
        if (isset($fk_c_question_bloc_status)) $this->fk_c_question_bloc_status = $fk_c_question_bloc_status;

        // Clean parameters
        $this->fk_survey_bloc = $this->fk_survey_bloc > 0 ? $this->fk_survey_bloc : 0;
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;

        $this->fetch_fichinter();
        if (isset($this->fichinter)) {
            if (!is_array($this->fichinter->survey[$this->fk_equipment]->survey[$this->fk_c_question_bloc]->status_list) || count($this->fichinter->survey[$this->fk_equipment]->survey[$this->fk_c_question_bloc]->status_list) == 0) {
                $this->fichinter->fetch_survey(1);
            }

            if (isset($this->fichinter->survey[$this->fk_equipment]->survey[$this->fk_c_question_bloc]->status_list[$this->fk_c_question_bloc_status])) {
                return 1;
            }
        }

        return 0;
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
     *  Load the survey bloc of object, from id $this->fk_survey_bloc, into this->survey_bloc
     *
     * @return  int             <0 if KO, >0 if OK
     */
    function fetch_survey_bloc()
    {
        $result = 0;

        if (empty($this->fk_survey_bloc)) {
            $this->survey_bloc = null;
        } elseif (!isset($this->survey_bloc) || $this->survey_bloc->id != $this->fk_survey_bloc) {
            dol_include_once('/extendedintervention/class/extendedinterventionsurveybloc.class.php');
            $survey_bloc = new EISurveyBloc($this->db);
            $result = $survey_bloc->fetch($this->fk_survey_bloc);
            if ($result > 0) {
                $this->survey_bloc = $survey_bloc;
            } else {
                $this->survey_bloc = null;
            }
        }

        return $result;
    }

    /**
     *	{@inheritdoc}
     */
    function fetch_optionals($rowid = null, $optionsArray = null)
    {
        $result = parent::fetch_optionals($rowid, $optionsArray);
        if ($result > 0) {
            $this->extrafields_question_bloc = is_array($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : (is_string($this->extrafields_question_bloc) ? explode(',', $this->extrafields_question_bloc) : array());

            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key,8), $this->extrafields_question_bloc)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        return $result;
    }

    /**
     *	{@inheritdoc}
     */
    function insertExtraFields()
    {
        $this->extrafields_question_bloc = is_array($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : (is_string($this->extrafields_question_bloc) ? explode(',', $this->extrafields_question_bloc) : array());

        // Clean extra fields
        if (count($this->extrafields_question_bloc) == 0) {
            $this->array_options = array();
        } elseif (is_array($this->array_options)) {
            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key,8), $this->extrafields_question_bloc)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        // Manage require fields but not selected
        $this->fetchExtraFieldsInfo();
        foreach (self::$extrafields->attributes[$this->table_element]['required'] as $key => $val) {
            if (!empty($val) && !in_array($key, $this->extrafields_question_bloc)) {
                $this->array_options[$key] = '0';
            }
        }

        $result = parent::insertExtraFields();

        return $result;
    }

    /**
     *	{@inheritdoc}
     */
    function updateExtraField($key)
    {
        $this->extrafields_question_bloc = is_array($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : (is_string($this->extrafields_question_bloc) ? explode(',', $this->extrafields_question_bloc) : array());

        if (in_array($key, $this->extrafields_question_bloc)) {
            return parent::updateExtraField($key);
        } else {
            return 0;
        }
    }

    /**
     *	{@inheritdoc}
     */
    function showOptionals($extrafields, $mode = 'view', $params = null, $keyprefix = '')
    {
        $this->extrafields_question_bloc = is_array($this->extrafields_question_bloc) ? $this->extrafields_question_bloc : (is_string($this->extrafields_question_bloc) ? explode(',', $this->extrafields_question_bloc) : array());

        $extrafields_question = clone $extrafields;
        $tmp = array();
        foreach ($extrafields_question->attribute_label as $key => $val) {
            if (!in_array($key, $this->extrafields_question_bloc)) {
                // Old usage
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
            } else {
                $tmp[$key] = $val;
            }
        }
        $extrafields_question->attribute_label = $tmp;

        return parent::showOptionals($extrafields_question, $mode, $params, $keyprefix);
    }
}

/**
 *	Class to manage question bloc lines (question + answer)
 */
class EIQuestionBlocLine extends CommonObjectLine
{
    public $element='extendedintervention_eiquestionblocline';
    public $table_element='extendedintervention_question_blocdet';
    public $table_element_parent='extendedintervention_question_bloc';
    public $table_element_parent_bis='extendedintervention_survey_bloc';

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
//        "id" => '', "fk_question_bloc" => '',
        "fk_fichinter" => '', "fk_equipment" => '', "fk_c_question_bloc" => '',
        "fk_c_question" => '', "position_question" => '', "code_question" => '', "label_question" => '', "color_answer" => '', "extrafields_question" => '',
        "fk_c_answer" => '', "code_answer" => '', "label_answer" => '', "mandatory_answer" => '', "text_answer" => '', "array_options" => '',
        "answer_list" => array(
            '' => array(
                "id" => '', "position" => '', "code" => '', "label" => '', "mandatory" => '', "predefined_texts" => array(
                    '' => array(
                        "position" => '', "code" => '', "predefined_text" => ''
                    ),
                ),
            ),
        ),
        "warning_code_question" => '', "warning_label_question" => '', "warning_extrafields_question" => '',
        "warning_code_answer" => '', "warning_label_answer" => '', "warning_color_answer" => '', "warning_mandatory_answer" => '',
        "read_only" => '', "date_creation" => '', "date_modification" => '', "user_creation_id" => '', "user_creation" => '',
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
	 * Id of the line
	 * @var int
	 */
    public $id;
    /**
     * Old line when update
     * @var EIQuestionBlocLine
     */
    public $oldline;

    /**
     * Intervention ID
     * @var int
     */
    public $fk_fichinter;
    /**
     * Equipment ID
     * @var int
     */
    public $fk_equipment;
    /**
     * Question bloc ID into the dictionary
     * @var int
     */
    public $fk_c_question_bloc;
    /**
     * Question bloc ID
     * @var int
     */
    public $fk_question_bloc;
    /**
     * Question bloc loaded
     * @see fetch_question_bloc()
     * @var EIQuestionBloc
     */
    public $question_bloc;
    /**
     * Question ID into the dictionary
     * @var int
     */
    public $fk_c_question;
    /**
     * Question position
     * @var int
     */
    public $position_question;
    /**
     * Question code
     * @var string
     */
    public $code_question;
    /**
     * Question label
     * @var string
     */
    public $label_question;
    /**
     * Question extra fields
     * @var array
     */
    public $extrafields_question;
    /**
     * Answer ID into the dictionary
     * @var int
     */
    public $fk_c_answer;
    /**
     * Answer code
     * @var string
     */
    public $code_answer;
    /**
     * Answer label
     * @var string
     */
    public $label_answer;
    /**
     * Answer color
     * @var string
     */
    public $color_answer;
    /**
     * Answer text is mandatory
     * @var int
     */
    public $mandatory_answer;
    /**
     * Answer text
     * @var string
     */
    public $text_answer;

    /**
     * List of the answer of the question (when fetch question with all data)
     * @var array
     */
    public $answer_list;
    /**
     * Warning the code of the question is different with dictionaries (when fetch question with all data)
     * @var int
     */
    public $warning_code_question;
    /**
     * Warning the label of the question is different with dictionaries (when fetch question with all data)
     * @var int
     */
    public $warning_label_question;
    /**
     * Warning the extra fields of the question is different with dictionaries (when fetch question with all data)
     * @var int
     */
    public $warning_extrafields_question;
    /**
     * Warning the code of the answer of the question is different with dictionaries (when fetch question with all data)
     * @var int
     */
    public $warning_code_answer;
    /**
     * Warning the label of the answer of the question is different with dictionaries (when fetch question with all data)
     * @var int
     */
    public $warning_label_answer;
    /**
     * Warning the color of the answer of the question is different with dictionaries (when fetch question with all data)
     * @var int
     */
    public $warning_color_answer;
    /**
     * Warning the mandatory text of the answer of the question is different with dictionaries (when fetch question with all data)
     * @var int
     */
    public $warning_mandatory_answer;
    /**
     * True if this question is not in the current survey defined into the dictionaries configuration
     * @var boolean
     */
    public $read_only;

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
     *  Cache of the extra fields info
     * @var ExtraFields
     */
	static protected $extrafields = null;
    /**
     *  Cache of the list of question information
     * @var DictionaryLine[]
     */
	static protected $question_cached = array();
    /**
     *  Cache of the list of answer information
     * @var DictionaryLine[]
     */
    static protected $answer_cached = array();
    /**
     *  Cache of the list of predefined texts of answers information
     * @var DictionaryLine[]
     */
    static protected $answer_predefined_texts_cached = array();

    /**
     * 	Constructor
     *
     * 	@param	DoliDB	$db	Database handler
     */
    function __construct($db)
    {
        global $langs;

        $this->db= $db;

        $langs->load('extendedintervention@extendedintervention');
        $langs->load('errors');
    }

    /**
     *  Fetch extra fields information (cached)
     * @return  void
     */
    protected function fetchExtraFieldsInfo() {
        if (!isset(self::$extrafields)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            self::$extrafields = new ExtraFields($this->db);
            $extrafields_labels = self::$extrafields->fetch_name_optionals_label($this->table_element);
        }
    }

    /**
     *  Fetch all the question information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    protected function fetchQuestionInfo() {
        if (empty(self::$question_cached)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionquestion');
            if ($dictionary->fetch_lines(1) > 0) {
                self::$question_cached = $dictionary->lines;
            } else {
                $this->error = $dictionary->error;
                $this->errors = array_merge($this->errors, $dictionary->errors);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Fetch all the answer information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    protected function fetchAnswerInfo() {
        if (empty(self::$answer_cached)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionanswer');
            if ($dictionary->fetch_lines(1) > 0) {
                self::$answer_cached = $dictionary->lines;
            } else {
                $this->error = $dictionary->error;
                $this->errors = array_merge($this->errors, $dictionary->errors);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Fetch all the predefined texts of the answer information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    protected function fetchAnswerPredefinedTextsInfo() {
        if (empty(self::$answer_predefined_texts_cached)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionanswerpredefinedtext');
            if ($dictionary->fetch_lines(1) > 0) {
                self::$answer_predefined_texts_cached = $dictionary->lines;
            } else {
                $this->error = $dictionary->error;
                $this->errors = array_merge($this->errors, $dictionary->errors);
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Insert line (question and answer) in database
     *
     * @param   User    $user           User that insert
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  int                     <0 if KO, >0 if OK
     */
    function insert($user, $notrigger=0)
    {
        dol_syslog(__METHOD__ . " user_id=" . $user->id . " notrigger=" . $notrigger);

        global $conf, $langs;

        // Clean parameters
        $this->fk_question_bloc = $this->fk_question_bloc > 0 ? $this->fk_question_bloc : 0;
        $this->fk_c_question = isset($this->fk_c_question) ? $this->fk_c_question : 0;
        $this->position_question = !empty($this->position_question) ? $this->position_question : 0;
        $this->code_question = !empty($this->code_question) ? $this->code_question : '';
        $this->label_question = !empty($this->label_question) ? $this->label_question : '';
        $this->extrafields_question = is_array($this->extrafields_question) ? $this->extrafields_question : (is_string($this->extrafields_question) ? explode(',', $this->extrafields_question) : array());
        $this->fk_c_answer = isset($this->fk_c_answer) ? $this->fk_c_answer : 0;
        $this->code_answer = !empty($this->code_answer) ? $this->code_answer : '';
        $this->label_answer = !empty($this->label_answer) ? $this->label_answer : '';
        $this->color_answer = !empty($this->color_answer) ? $this->color_answer : '';
        $this->mandatory_answer = isset($this->mandatory_answer) ? (!empty($this->mandatory_answer) ? 1 : 0) : null;
        $this->text_answer = !empty($this->text_answer) ? $this->text_answer : '';

        // Check parameters
        $error = 0;
        $now = dol_now();

        if (!($this->fk_question_bloc > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocId");
            $error++;
        }
        if ($this->fk_c_question == 0) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionDictionaryId");
            $error++;
        } elseif ($this->fk_c_question > 0 && (empty($this->position_question) || empty($this->code_question) || empty($this->label_question) || empty($this->extrafields_question))) {
            if ($this->fetchQuestionInfo() < 0) {
                $error++;
            } elseif (isset(self::$question_cached[$this->fk_c_question])) {
                $question = self::$question_cached[$this->fk_c_question];
                $this->position_question = !empty($this->position_question) ? $this->position_question : (!empty($question->fields['position']) ? $question->fields['position'] : 0);
                $this->code_question = !empty($this->code_question) ? $this->code_question : $question->fields['code'];
                $this->label_question = !empty($this->label_question) ? $this->label_question : $question->fields['label'];
                $this->extrafields_question = !empty($this->extrafields_question) ? $this->extrafields_question : array_filter(explode(',', $question->fields['extra_fields']), 'strlen');
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionDictionaryInfoNotFound", $this->fk_c_question);
                $error++;
            }
        }
        if ($this->position_question === '' || !isset($this->position_question)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionPosition")) . ' (' . $langs->trans("ExtendedInterventionQuestionDictionaryId") . ': ' . $this->fk_c_question . ')';
            $error++;
        }
        if (empty($this->code_question)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionCode")) . ' (' . $langs->trans("ExtendedInterventionQuestionDictionaryId") . ': ' . $this->fk_c_question . ')';
            $error++;
        }
        if (empty($this->label_question)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionLabel")) . ' (' . $langs->trans("ExtendedInterventionQuestionDictionaryId") . ': ' . $this->fk_c_question . ')';
            $error++;
        }
        if ($this->fk_c_answer > 0) {
            if (!$this->is_answer_in_survey()) {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionAnswerNotInSurvey", $this->fk_c_question);
                $error++;
            } elseif ($this->fetchAnswerInfo() < 0) {
                $error++;
            } elseif (isset(self::$answer_cached[$this->fk_c_answer])) {
                $answer = self::$answer_cached[$this->fk_c_answer];
                $this->code_answer = !empty($this->code_answer) ? $this->code_answer : $answer->fields['code'];
                $this->label_answer = !empty($this->label_answer) ? $this->label_answer : $answer->fields['label'];
                $this->color_answer = !empty($this->color_answer) ? $this->color_answer : $answer->fields['color'];
                $this->mandatory_answer = isset($this->mandatory_answer) ? $this->mandatory_answer : (!empty($answer->fields['mandatory']) ? 1 : 0);
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorAnswerDictionaryInfoNotFound", $this->fk_c_answer);
                $error++;
            }
            if ($this->mandatory_answer && empty($this->text_answer)) {
                $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionAnswerJustificatory")) . ' (' . (empty($this->label_answer) ? $langs->trans("ExtendedInterventionAnswerId") . ': ' . $this->fk_c_answer : $langs->trans("ExtendedInterventionAnswerLabel") . ': ' . $this->label_answer) . ')';
                $error++;
            }
        } elseif ($this->fk_c_answer == 0) {
            $this->code_answer = "";
            $this->label_answer = "";
            $this->color_answer = "";
            $this->mandatory_answer = 0;
            $this->text_answer = "";
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        // Insert line (question and answer) into database
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element;
        $sql .= ' (fk_question_bloc,';
        $sql .= ' fk_c_question, position_question, code_question, label_question, extrafields_question,';
        $sql .= ' fk_c_answer, code_answer, label_answer, color_answer, mandatory_answer, text_answer,';
        $sql .= ' fk_user_author, datec)';
        $sql .= " VALUES (" . $this->fk_question_bloc;
        $sql .= ", " . $this->fk_c_question;
        $sql .= ", " . $this->position_question;
        $sql .= ", '" . $this->db->escape($this->code_question) . "'";
        $sql .= ", '" . $this->db->escape($this->label_question) . "'";
        $sql .= ", " . (count($this->extrafields_question) ? "'" . $this->db->escape(implode(',', $this->extrafields_question)) . "'" : 'NULL');
        $sql .= ", " . ($this->fk_c_answer != 0 ? $this->fk_c_answer : "NULL");
        $sql .= ", " . (!empty($this->code_answer) ? "'" . $this->db->escape($this->code_answer) . "'" : "NULL");
        $sql .= ", " . (!empty($this->label_answer) ? "'" . $this->db->escape($this->label_answer) . "'" : "NULL");
        $sql .= ", " . (!empty($this->color_answer) ? "'" . $this->db->escape($this->color_answer) . "'" : "NULL");
        $sql .= ", " . (!empty($this->mandatory_answer) ? $this->mandatory_answer : "NULL");
        $sql .= ", " . (!empty($this->text_answer) ? "'" . $this->db->escape($this->text_answer) . "'" : "NULL");
        $sql .= ", " . $user->id;
        $sql .= ", '" . $this->db->idate($now) . "'";
        $sql .= ')';

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                    dol_syslog(__METHOD__ . " Errors extra fields: " . $this->errorsToString(), LOG_ERR);
                }
            }

            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINE_EI_QUESTION_BLOC_INSERT', $user);
                if ($result < 0) {
                    $error++;
                    dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
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
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Update line (question and answer) in database
     *
     * @param   User    $user           User that insert
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  int                     <0 if KO, >0 if OK
     */
    function update($user, $notrigger=0)
    {
        dol_syslog(__METHOD__ . " user_id=" . $user->id . " notrigger=" . $notrigger);

        global $conf, $langs;

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->fk_question_bloc = $this->fk_question_bloc > 0 ? $this->fk_question_bloc : 0;
        $this->fk_c_question = isset($this->fk_c_question) ? $this->fk_c_question : 0;
        $this->position_question = !empty($this->position_question) ? $this->position_question : 0;
        $this->code_question = !empty($this->code_question) ? $this->code_question : '';
        $this->label_question = !empty($this->label_question) ? $this->label_question : '';
        $this->extrafields_question = is_array($this->extrafields_question) ? $this->extrafields_question : (is_string($this->extrafields_question) ? explode(',', $this->extrafields_question) : array());
        $this->fk_c_answer = isset($this->fk_c_answer) ? $this->fk_c_answer : 0;
        $this->code_answer = !empty($this->code_answer) ? $this->code_answer : '';
        $this->label_answer = !empty($this->label_answer) ? $this->label_answer : '';
        $this->color_answer = !empty($this->color_answer) ? $this->color_answer : '';
        $this->mandatory_answer = isset($this->mandatory_answer) ? (!empty($this->mandatory_answer) ? 1 : 0) : null;
        $this->text_answer = !empty($this->text_answer) ? $this->text_answer : '';

        // Check parameters
        $error = 0;
        $now = dol_now();

        if (!($this->fk_question_bloc > 0) && $this->fk_c_question == 0 && !($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionId");
            $error++;
        } else {
            if (!($this->fk_question_bloc > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocId");
                $error++;
            }
            if ($this->fk_c_question == 0) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionDictionaryId");
                $error++;
            }
        }
        if ($this->fk_c_question > 0 && (empty($this->position_question) || empty($this->code_question) || empty($this->label_question) || empty($this->extrafields_question))) {
            if ($this->fetchQuestionInfo() < 0) {
                $error++;
            } elseif (isset(self::$question_cached[$this->fk_c_question])) {
                $question = self::$question_cached[$this->fk_c_question];
                $this->position_question = !empty($this->position_question) ? $this->position_question : (!empty($question->fields['position']) ? $question->fields['position'] : 0);
                $this->code_question = !empty($this->code_question) ? $this->code_question : $question->fields['code'];
                $this->label_question = !empty($this->label_question) ? $this->label_question : $question->fields['label'];
                $this->extrafields_question = !empty($this->extrafields_question) ? $this->extrafields_question : array_filter(explode(',', $question->fields['extra_fields']), 'strlen');
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionDictionaryInfoNotFound", $this->fk_c_question);
                $error++;
            }
        }
        if ($this->position_question === '' || !isset($this->position_question)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionPosition")) . ' (' . $langs->trans("ExtendedInterventionQuestionDictionaryId") . ': ' . $this->fk_c_question . ')';
            $error++;
        }
        if (empty($this->code_question)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionCode")) . ' (' . $langs->trans("ExtendedInterventionQuestionDictionaryId") . ': ' . $this->fk_c_question . ')';
            $error++;
        }
        if (empty($this->label_question)) {
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionQuestionLabel")) . ' (' . $langs->trans("ExtendedInterventionQuestionDictionaryId") . ': ' . $this->fk_c_question . ')';
            $error++;
        }
        if ($this->fk_c_answer > 0) {
            if (!$this->is_answer_in_survey()) {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorQuestionAnswerNotInSurvey", $this->fk_c_question);
                $error++;
            } elseif ($this->fetchAnswerInfo() < 0) {
                $error++;
            } elseif (isset(self::$answer_cached[$this->fk_c_answer])) {
                $answer = self::$answer_cached[$this->fk_c_answer];
                $this->code_answer = !empty($this->code_answer) ? $this->code_answer : $answer->fields['code'];
                $this->label_answer = !empty($this->label_answer) ? $this->label_answer : $answer->fields['label'];
                $this->color_answer = !empty($this->color_answer) ? $this->color_answer : $answer->fields['color'];
                $this->mandatory_answer = isset($this->mandatory_answer) ? $this->mandatory_answer : (!empty($answer->fields['mandatory']) ? 1 : 0);
            } else {
                $this->errors[] = $langs->trans("ExtendedInterventionErrorAnswerDictionaryInfoNotFound", $this->fk_c_answer);
                $error++;
            }
            if ($this->mandatory_answer && empty($this->text_answer)) {
                $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExtendedInterventionAnswerJustificatory")) . ' (' . (empty($this->label_answer) ? $langs->trans("ExtendedInterventionAnswerId") . ': ' . $this->fk_c_answer : $langs->trans("ExtendedInterventionAnswerLabel") . ': ' . $this->label_answer) . ')';
                $error++;
            }
        } elseif ($this->fk_c_answer == 0) {
            $this->code_answer = "";
            $this->label_answer = "";
            $this->color_answer = "";
            $this->mandatory_answer = 0;
            $this->text_answer = "";
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        // Update line (question and answer) into database
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
        $sql .= " position_question = " . $this->position_question;
        $sql .= ", code_question = '" . $this->db->escape($this->code_question) . "'";
        $sql .= ", label_question = '" . $this->db->escape($this->label_question) . "'";
        $sql .= ", extrafields_question = " . (count($this->extrafields_question) ? "'" . $this->db->escape(implode(',', $this->extrafields_question)) . "'" : 'NULL');
        $sql .= ", fk_c_answer = " . ($this->fk_c_answer != 0 ? $this->fk_c_answer : "NULL");
        $sql .= ", code_answer = " . (!empty($this->code_answer) ? "'" . $this->db->escape($this->code_answer) . "'" : "NULL");
        $sql .= ", label_answer = " . (!empty($this->label_answer) ? "'" . $this->db->escape($this->label_answer) . "'" : "NULL");
        $sql .= ", color_answer = " . (!empty($this->color_answer) ? "'" . $this->db->escape($this->color_answer) . "'" : "NULL");
        $sql .= ", mandatory_answer = " . (!empty($this->mandatory_answer) ? $this->mandatory_answer : "NULL");
        $sql .= ", text_answer = " . (!empty($this->text_answer) ? "'" . $this->db->escape($this->text_answer) . "'" : "NULL");
        $sql .= ", fk_user_modif = " . $user->id;
        $sql .= ", tms = '" . $this->db->idate($now) . "'";
        $sql .= " WHERE";
        if ($this->fk_question_bloc > 0 && $this->fk_c_question != 0)
            $sql .= " fk_question_bloc=" . $this->fk_question_bloc . " AND fk_c_question=" . $this->fk_c_question;
        else
            $sql .= " rowid=" . $this->id;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                    dol_syslog(__METHOD__ . " Errors extra fields: " . $this->errorsToString(), LOG_ERR);
                }
            }

            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINE_EI_QUESTION_BLOC_UPDATE', $user);
                if ($result < 0) {
                    $error++;
                    dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
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
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *  Delete line (question and answer) in database
     *
     * @param   User    $user           User that insert
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  int                     <0 if KO, >0 if OK
     */
    function delete($user, $notrigger=0)
    {
        dol_syslog(__METHOD__ . " user_id=" . $user->id . " line_id=" . $this->id . " fk_question_bloc=" . $this->fk_question_bloc . " fk_c_question=" . $this->fk_c_question . " notrigger=" . $notrigger);
        global $conf, $langs;

        // Clean parameters
        $this->id = $this->id > 0 ? $this->id : 0;
        $this->fk_question_bloc = $this->fk_question_bloc > 0 ? $this->fk_question_bloc : 0;
        $this->fk_c_question = isset($this->fk_c_question) ? $this->fk_c_question : 0;

        // Check parameters
        $error = 0;

        if (!($this->fk_question_bloc > 0) && $this->fk_c_question == 0 && !($this->id > 0)) {
            $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionId");
            $error++;
        } else {
            if (!($this->fk_question_bloc > 0)) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionBlocId");
                $error++;
            }
            if ($this->fk_c_question == 0) {
                $this->errors[] = $langs->trans("ErrorBadParameters") . ': ' . $langs->trans("ExtendedInterventionQuestionDictionaryId");
                $error++;
            }
        }
        if ($error) {
            dol_syslog(__METHOD__ . " Errors: " . $this->errorsToString(), LOG_ERR);
            return -4;
        }

        $this->db->begin();

        if (!$notrigger) {
            // Call trigger
            $result = $this->call_trigger('LINE_EI_QUESTION_BLOC_DELETE', $user);
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors call trigger: " . $this->errorsToString(), LOG_ERR);
            }
            // End call triggers
        }

        // Removed extrafields
        if (!$error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
            $result = $this->deleteExtraFields();
            if ($result < 0) {
                $error++;
                dol_syslog(__METHOD__ . " Errors delete extra fields: " . $this->errorsToString(), LOG_ERR);
            }
        }

        // Remove line (question and answer) into database
        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
            if ($this->fk_question_bloc > 0 && $this->fk_c_question != 0)
                $sql .= " WHERE fk_question_bloc=" . $this->fk_question_bloc . " AND fk_c_question=" . $this->fk_c_question;
            else
                $sql .= " WHERE rowid=" . $this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            }
        }

        if (!$error) {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);

            return 1;
        } else {
            $this->db->rollback();

            return -1;
        }
    }

    /**
     *  Retrieve the line (question and answer) in database
     *
     * @param   int     $rowid                  ID of object to load
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $fk_c_question_bloc     ID of the question bloc in the dictionary
     * @param   int     $fk_question_bloc       ID of the question bloc
     * @param   int     $fk_c_question          ID of the question in the dictionary
     * @param   int     $all_data               1=Load all data of the dictionaries (all status, all answer and all predefined text)
     * @param   int     $test_exist             1=Test if the question exist in the survey of the intervention
     * @return  int                             <0 if KO, >=0 if OK
     */
    function fetch($rowid, $fk_fichinter=0, $fk_equipment=0, $fk_c_question_bloc=0, $fk_question_bloc=0, $fk_c_question=0, $all_data=0, $test_exist=1)
    {
        global $langs;

        // Clean parameters
        $rowid = $rowid > 0 ? $rowid : 0;
        $fk_question_bloc = $fk_question_bloc > 0 ? $fk_question_bloc : 0;
        $fk_fichinter = $fk_fichinter > 0 ? $fk_fichinter : 0;
        $fk_equipment = isset($fk_equipment) ? $fk_equipment : 0;
        $fk_c_question_bloc = isset($fk_c_question_bloc) ? $fk_c_question_bloc : 0;
        $fk_c_question = isset($fk_c_question) ? $fk_c_question : 0;

        $sql = "SELECT t.rowid, pb.fk_fichinter, pb.fk_equipment, p.fk_c_question_bloc, t.fk_question_bloc";
        $sql .= ", t.fk_c_question, t.position_question, t.code_question, t.label_question, t.extrafields_question";
        $sql .= ", t.fk_c_answer, t.code_answer, t.label_answer, t.color_answer, t.mandatory_answer, t.text_answer";
        $sql .= ", t.datec, t.tms, t.fk_user_author, t.fk_user_modif, t.import_key";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " AS t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent . " AS p ON p.rowid = t.fk_question_bloc";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element_parent_bis . " AS pb ON pb.rowid = p.fk_survey_bloc";
        $sql .= " WHERE p.entity IN (" . getEntity('ei_question_bloc') . ")";
        if ($fk_fichinter > 0 && $fk_c_question_bloc != 0 && $fk_c_question != 0)
            $sql .= " AND pb.fk_fichinter=" . $fk_fichinter . " AND pb.fk_equipment=" . $fk_equipment . " AND p.fk_c_question_bloc=" . $fk_c_question_bloc . " AND t.fk_c_question=" . $fk_c_question;
        elseif ($fk_question_bloc > 0 && $fk_c_question != 0)
            $sql .= " AND t.fk_question_bloc=" . $fk_question_bloc . " AND t.fk_c_question=" . $fk_c_question;
        else
            $sql .= " AND t.rowid=" . $rowid;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->fetchQuestionInfo();
            $this->answer_list = array();
            $this->warning_code_question = 0;
            $this->warning_label_question = 0;
            $this->warning_extrafields_question = 0;
            $this->warning_code_answer = 0;
            $this->warning_label_answer = 0;
            $this->warning_color_answer = 0;
            $this->warning_mandatory_answer = 0;

            $num = $this->db->num_rows($resql);
            if ($num) {
                $obj = $this->db->fetch_object($resql);

                $this->id                   = $obj->rowid;
                $this->fk_fichinter         = $obj->fk_fichinter;
                $this->fk_equipment         = $obj->fk_equipment;
                $this->fk_c_question_bloc   = $obj->fk_c_question_bloc;
                $this->fk_question_bloc     = $obj->fk_question_bloc;
                $this->fk_c_question        = $obj->fk_c_question;
                $this->position_question    = $obj->position_question;
                $this->code_question        = $obj->code_question;
                $this->label_question       = $obj->label_question;
                $this->extrafields_question = !empty($obj->extrafields_question) ? explode(',', $obj->extrafields_question) : array();
                $this->fk_c_answer          = $obj->fk_c_answer;
                $this->code_answer          = $obj->code_answer;
                $this->label_answer         = $obj->label_answer;
                $this->color_answer         = $obj->color_answer;
                $this->mandatory_answer     = !empty($obj->mandatory_answer) ? 1 : 0;
                $this->text_answer          = $obj->text_answer;
                $this->date_creation        = $this->db->jdate($obj->datec);
                $this->date_modification    = $this->db->jdate($obj->tms);
                $this->user_creation_id     = $obj->fk_user_author;
                $this->user_modification_id = $obj->fk_user_modif;
                $this->import_key           = $obj->import_key;

                $this->db->free($resql);
            } elseif ((($fk_fichinter > 0 && $fk_c_question_bloc != 0) || $fk_question_bloc > 0) && $fk_c_question != 0 &&
                ($fk_c_question < 0 || (isset(self::$question_cached[$fk_c_question]) && (!$test_exist || $fk_equipment < 0 || $fk_c_question_bloc < 0 || $this->is_in_survey($fk_fichinter, $fk_equipment, $fk_c_question_bloc, $fk_question_bloc, $fk_c_question))))) {
                $question = self::$question_cached[$fk_c_question];
                $this->id                           = 0;
                $this->fk_fichinter                 = $fk_fichinter > 0 && $fk_c_question_bloc != 0 ? $fk_fichinter : 0;
                $this->fk_equipment                 = $fk_fichinter > 0 && $fk_c_question_bloc != 0 ? $fk_equipment : 0;
                $this->fk_c_question_bloc           = $fk_fichinter > 0 && $fk_c_question_bloc != 0 ? $fk_c_question_bloc : 0;
                $this->fk_question_bloc             = !($fk_fichinter > 0 && $fk_c_question_bloc != 0) ? $fk_question_bloc : 0;
                $this->fk_c_question                = $fk_c_question;
                $this->position_question            = !empty($question->fields['position']) ? $question->fields['position'] : 0;
                $this->code_question                = !empty($question->fields['code']) ? $question->fields['code'] : '';
                $this->label_question               = !empty($question->fields['label']) ? $question->fields['label'] : '';
                $this->extrafields_question         = !empty($question->fields['extra_fields']) ? explode(',', $question->fields['extra_fields']) : array();
                $this->fk_c_answer                  = 0;
                $this->code_answer                  = '';
                $this->label_answer                 = '';
                $this->color_answer                 = '';
                $this->mandatory_answer             = 0;
                $this->text_answer                  = '';
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
                $question = self::$question_cached[$fk_c_question];
                $this->fetchAnswerInfo();
                $this->fetchAnswerPredefinedTextsInfo();
                $answer_list = !empty($question->fields['answers']) ? explode(',', $question->fields['answers']) : array();

                // Load answer list
                foreach ($answer_list as $answer_id) {
                    $this->answer_list[$answer_id] = array_merge(array('id' => $answer_id), self::$answer_cached[$answer_id]->fields);

                    // Load predefined text for the answer
                    $predefined_texts_list = !empty($this->answer_list[$answer_id]['predefined_texts']) ? explode(',', $this->answer_list[$answer_id]['predefined_texts']) : array();
                    $this->answer_list[$answer_id]['predefined_texts'] = array();
                    foreach ($predefined_texts_list as $predefined_texts_id) {
                        $this->answer_list[$answer_id]['predefined_texts'][$predefined_texts_id] = self::$answer_predefined_texts_cached[$predefined_texts_id]->fields;
                    }
                }

                // Sort by position
                uasort($this->answer_list, 'ei_sort_position');
                foreach ($this->answer_list as $k => $v) {
                    $tab = $this->answer_list[$k]['predefined_texts'];
                    uasort($tab, 'ei_sort_predefined_texts_position');
                    $this->answer_list[$k]['predefined_texts'] = $tab;
                }

                // Check if has value modified with the dictionaries
                if ($this->id > 0) {
                    // Code of the question
                    if ($this->code_question != $question->fields['code']) {
                        $this->code_question = $question->fields['code'];
                        $this->warning_code_question = 1;
                    }
                    // Label of the question
                    if ($this->label_question != $question->fields['label']) {
                        $this->label_question = $question->fields['label'];
                        $this->warning_label_question = 1;
                    }
                    // Extra fields of the question
                    $new_extra_fields = !empty($question->fields['extra_fields']) ? explode(',', $question->fields['extra_fields']) : array();
                    if (count(array_diff($this->extrafields_question, $new_extra_fields))) {
                        $this->extrafields_question = $new_extra_fields;
                        $this->warning_extrafields_question = 1;
                    }
                    // Code of the answer of the question
                    if ($this->code_answer != self::$answer_cached[$this->fk_c_answer]->fields['code']) {
                        $this->code_answer = self::$answer_cached[$this->fk_c_answer]->fields['code'];
                        $this->warning_code_answer = 1;
                    }
                    // Label of the answer of the question
                    if ($this->label_answer != self::$answer_cached[$this->fk_c_answer]->fields['label']) {
                        $this->label_answer = self::$answer_cached[$this->fk_c_answer]->fields['label'];
                        $this->warning_label_answer = 1;
                    }
                    // Color of the answer of the question
                    if ($this->color_answer != self::$answer_cached[$this->fk_c_answer]->fields['color']) {
                        $this->color_answer = self::$answer_cached[$this->fk_c_answer]->fields['color'];
                        $this->warning_color_answer = 1;
                    }
                    // Mandatory text of the answer of the question
                    $new_mandatory_answer = !empty(self::$answer_cached[$this->fk_c_answer]->fields['mandatory']) ? 1 : 0;
                    if ($this->mandatory_answer != $new_mandatory_answer) {
                        $this->mandatory_answer = $new_mandatory_answer;
                        $this->warning_mandatory_answer = 1;
                    }
                    $this->position_question            = $question->fields['position'];
                    $this->code_question                = $question->fields['code'];
                    $this->label_question               = $question->fields['label'];
                    $this->extrafields_question         = !empty($question->fields['extra_fields']) ? explode(',', $question->fields['extra_fields']) : array();
                }
            }

            $this->fetch_optionals();

            return 1;
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
            return -1;
        }
    }

    /**
     *  Is the question read only
     *
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $fk_c_question_bloc     ID of the question bloc in the dictionary
     * @param   int     $fk_question_bloc       ID of the question bloc
     * @param   int     $fk_c_question          ID of the question in the dictionary
     * @return  int                             =0 if No, >0 if Yes
     */
    function is_read_only($fk_fichinter=null, $fk_equipment=null, $fk_c_question_bloc=null, $fk_question_bloc=null, $fk_c_question=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;
        if (isset($fk_c_question_bloc)) $this->fk_c_question_bloc = $fk_c_question_bloc;
        if (isset($fk_question_bloc)) $this->fk_question_bloc = $fk_question_bloc;
        if (isset($fk_c_question)) $this->fk_c_question = $fk_c_question;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;
        $this->fk_question_bloc = $this->fk_question_bloc > 0 ? $this->fk_question_bloc : 0;
        $this->fk_c_question = isset($this->fk_c_question) ? $this->fk_c_question : 0;

        $this->fetch_question_bloc();
        if (isset($this->question_bloc)) {
            if (!is_array($this->question_bloc->lines) || count($this->question_bloc->lines) == 0) {
                $this->question_bloc->fetch_lines(0);
            }

            if (empty($this->question_bloc->lines[$this->fk_c_question]->read_only)) {
                $this->read_only = 0;
                return 0;
            }
        }

        $this->read_only = 1;
        return 1;
    }

    /**
     *  Is the question in the survey
     *
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $fk_c_question_bloc     ID of the question bloc in the dictionary
     * @param   int     $fk_question_bloc       ID of the question bloc
     * @param   int     $fk_c_question          ID of the question in the dictionary
     * @return  int                             =0 if No, >0 if Yes
     */
    function is_in_survey($fk_fichinter=null, $fk_equipment=null, $fk_c_question_bloc=null, $fk_question_bloc=null, $fk_c_question=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;
        if (isset($fk_c_question_bloc)) $this->fk_c_question_bloc = $fk_c_question_bloc;
        if (isset($fk_question_bloc)) $this->fk_question_bloc = $fk_question_bloc;
        if (isset($fk_c_question)) $this->fk_c_question = $fk_c_question;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;
        $this->fk_question_bloc = $this->fk_question_bloc > 0 ? $this->fk_question_bloc : 0;
        $this->fk_c_question = isset($this->fk_c_question) ? $this->fk_c_question : 0;

        $this->fetch_question_bloc();
        if (isset($this->question_bloc)) {
            if (!is_array($this->question_bloc->lines) || count($this->question_bloc->lines) == 0) {
                $this->question_bloc->fetch_lines(0);
            }

            if (isset($this->question_bloc->lines[$this->fk_c_question])) {
                return 1;
            }
        }

        return 0;
    }

    /**
     *  Is the question answer in the survey
     *
     * @param   int     $fk_fichinter           ID of the intervention
     * @param   int     $fk_equipment           ID of the equipment
     * @param   int     $fk_c_question_bloc     ID of the question bloc in the dictionary
     * @param   int     $fk_question_bloc       ID of the question bloc
     * @param   int     $fk_c_question          ID of the question in the dictionary
     * @param   int     $fk_c_answer            ID of the question bloc status in the dictionary
     * @return  int                             =0 if No, >0 if Yes
     */
    function is_answer_in_survey($fk_fichinter=null, $fk_equipment=null, $fk_c_question_bloc=null, $fk_question_bloc=null, $fk_c_question=null, $fk_c_answer=null)
    {
        if (isset($fk_fichinter)) $this->fk_fichinter = $fk_fichinter;
        if (isset($fk_equipment)) $this->fk_equipment = $fk_equipment;
        if (isset($fk_c_question_bloc)) $this->fk_c_question_bloc = $fk_c_question_bloc;
        if (isset($fk_question_bloc)) $this->fk_question_bloc = $fk_question_bloc;
        if (isset($fk_c_question)) $this->fk_c_question = $fk_c_question;
        if (isset($fk_c_answer)) $this->fk_c_answer = $fk_c_answer;

        // Clean parameters
        $this->fk_fichinter = $this->fk_fichinter > 0 ? $this->fk_fichinter : 0;
        $this->fk_equipment = isset($this->fk_equipment) ? $this->fk_equipment : 0;
        $this->fk_c_question_bloc = isset($this->fk_c_question_bloc) ? $this->fk_c_question_bloc : 0;
        $this->fk_question_bloc = $this->fk_question_bloc > 0 ? $this->fk_question_bloc : 0;
        $this->fk_c_question = isset($this->fk_c_question) ? $this->fk_c_question : 0;
        $this->fk_c_answer = isset($this->fk_c_answer) ? $this->fk_c_answer : 0;

        $this->fetch_question_bloc();
        if (isset($this->question_bloc)) {
            if (!is_array($this->question_bloc->lines[$this->fk_c_question]->answer_list) || count($this->question_bloc->lines[$this->fk_c_question]->answer_list) == 0) {
                $this->question_bloc->fetch_lines(1);
            }

            if (isset($this->question_bloc->lines[$this->fk_c_question]->answer_list[$this->fk_c_answer])) {
                return 1;
            }
        }

        return 0;
    }

    /**
     *  Load the question bloc of object, from id $this->fk_question_bloc, into this->question_bloc
     *
     * @return  int             <0 if KO, >0 if OK
     */
    function fetch_question_bloc()
    {
        $result = 0;

        if (!($this->fk_fichinter > 0) && $this->fk_c_question_bloc == 0 && !($this->fk_question_bloc > 0)) {
            $this->fk_question_bloc = null;
        } elseif (!isset($this->question_bloc) || (($this->fk_fichinter > 0 && $this->fk_c_question_bloc != 0 && $this->question_bloc->fk_fichinter != $this->fk_fichinter && $this->question_bloc->fk_equipment != $this->fk_equipment && $this->question_bloc->fk_c_question_bloc != $this->fk_c_question_bloc) || $this->question_bloc->id != $this->fk_question_bloc)) {
            $questionbloc = new EIQuestionBloc($this->db);
            $result = $questionbloc->fetch($this->fk_question_bloc, $this->fk_fichinter, $this->fk_equipment, 0, $this->fk_c_question_bloc);
            if ($result > 0) {
                $this->question_bloc = $questionbloc;
            } else {
                $this->question_bloc = null;
            }
        }

        return $result;
    }

    /**
     *	{@inheritdoc}
     */
    function fetch_optionals($rowid = null, $optionsArray = null)
    {
        $result = parent::fetch_optionals($rowid, $optionsArray);
        if ($result > 0) {
            $this->extrafields_question = is_array($this->extrafields_question) ? $this->extrafields_question : (is_string($this->extrafields_question) ? explode(',', $this->extrafields_question) : array());

            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key,8), $this->extrafields_question)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        return $result;
    }

    /**
     *	{@inheritdoc}
     */
    function insertExtraFields()
    {
        $this->extrafields_question = is_array($this->extrafields_question) ? $this->extrafields_question : (is_string($this->extrafields_question) ? explode(',', $this->extrafields_question) : array());

        // Clean extra fields
        if (count($this->extrafields_question) == 0) {
            $this->array_options = array();
        } elseif (is_array($this->array_options)) {
            $tmp = array();
            foreach ($this->array_options as $key => $val) {
                if (in_array(substr($key,8), $this->extrafields_question)) {
                    $tmp[$key] = $val;
                }
            }
            $this->array_options = $tmp;
        }

        // Manage require fields but not selected
        $this->fetchExtraFieldsInfo();
        foreach (self::$extrafields->attributes[$this->table_element]['required'] as $key => $val) {
            if (!empty($val) && !in_array($key, $this->extrafields_question)) {
                $this->array_options[$key] = '0';
            }
        }

        $result = parent::insertExtraFields();

        return $result;
    }

    /**
     *	{@inheritdoc}
     */
    function updateExtraField($key)
    {
        $this->extrafields_question = is_array($this->extrafields_question) ? $this->extrafields_question : (is_string($this->extrafields_question) ? explode(',', $this->extrafields_question) : array());

        if (in_array($key, $this->extrafields_question)) {
            return parent::updateExtraField($key);
        } else {
            return 0;
        }
    }

    /**
     *	{@inheritdoc}
     */
    function showOptionals($extrafields, $mode = 'view', $params = null, $keyprefix = '')
    {
        $this->extrafields_question = is_array($this->extrafields_question) ? $this->extrafields_question : (is_string($this->extrafields_question) ? explode(',', $this->extrafields_question) : array());

        $extrafields_question_bloc = clone $extrafields;
        $tmp = array();
        foreach ($extrafields_question_bloc->attribute_label as $key => $val) {
            if (!in_array($key, $this->extrafields_question)) {
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

        return parent::showOptionals($extrafields_question_bloc, $mode, $params, $keyprefix);
    }
}
