<?php
/* Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * \file    htdocs/extendedintervention/class/extendedintervention.class.php
 * \ingroup extendedintervention
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';

/**
 * Class InterventionSurvey
 *
 * Put here description of your class
 * @see Fichinter
 */
class InterventionSurvey extends Fichinter
{
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
        "id" => '', "ref" => '', "description" => '', "socid" => '', "statut" => '', "duration" => '', "datec" => '',
        "datee" => '', "dateo" => '', "datet" => '', "datev" => '', "datem" => '', "fk_project" => '', "note_public" => '',
        "trueWidth" => '', "width_units" => '', "trueHeight" => '', "height_units" => '', "trueDepth" => '', "depth_units" => '',
        "fk_contrat" => '', "user_creation" => '', "brouillon" => '', "thirdparty" => '', "array_options" => '',
        "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '', "survey" => '',
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
	 * @var EISurveyBloc[]
	 */
    public $survey = array();

    /**
	 * @var array List of attached files of the intervention
	 */
    public $attached_files = array();

    /**
     * Status
     */
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_INVOICED = 2;
    const STATUS_DONE = 3;

    /**
     * Cache Dictionary data of bloc question
     */
    public $cache_survey_bloc_question_dictionary;

     /**
     * Cache Dictionary data of status
     */
    public $cache_survey_bloc_status_dictionary;

     /**
     * Cache Dictionary data of status_predefined_text
     */
    public $cache_survey_bloc_status_predefined_text_dictionary;

     /**
     * Cache Dictionary data of question
     */
    public $cache_survey_question_dictionary;

     /**
     * Cache Dictionary data of answer
     */
    public $cache_survey_answer_dictionary;

     /**
     * Cache Dictionary data of answer_predefined_text
     */
    public $cache_survey_answer_predefined_text;

    /**
     *  Fill dictionary caches
     *
     */
    public function fillCaches(){
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        if(!isset($this->cache_survey_bloc_question_dictionary)) $this->cache_survey_bloc_question_dictionary = Dictionary::getJSONDictionary($this->db, 'interventionsurvey', 'SurveyBlocQuestion');
        if(!isset($this->cache_survey_bloc_status_dictionary)) $this->cache_survey_bloc_status_dictionary = Dictionary::getJSONDictionary($this->db, 'interventionsurvey', 'SurveyBlocStatus');
        if(!isset($this->cache_survey_bloc_status_predefined_text_dictionary)) $this->cache_survey_bloc_status_predefined_text_dictionary = Dictionary::getJSONDictionary($this->db, 'interventionsurvey', 'SurveyBlocStatusPredefinedText');
        if(!isset($this->cache_survey_question_dictionary)) $this->cache_survey_question_dictionary = Dictionary::getJSONDictionary($this->db, 'interventionsurvey', 'SurveyQuestion');
        if(!isset($this->cache_survey_answer_dictionary)) $this->cache_survey_answer_dictionary = Dictionary::getJSONDictionary($this->db, 'interventionsurvey', 'SurveyAnswer');
        if(!isset($this->cache_survey_answer_predefined_text)) $this->cache_survey_answer_predefined_text = Dictionary::getJSONDictionary($this->db, 'interventionsurvey', 'SurveyAnswerPredefinedText');
        }

     /**
     * Fill object from one array with index value taken from object initial property value
     *
     */

    static function fillDataFromJSONDictionnary($JSONDictionaryLine,$field,$JSONDataDictionary) {
        $listOfIds = explode(",", $JSONDictionaryLine[$field]);
        $JSONDictionaryLine[$field] = array();
        foreach($listOfIds as $id) {
            $obj = $JSONDataDictionary[$id];
            $JSONDictionaryLine[$field][] = $JSONDataDictionary[$id];
        }
        return $JSONDictionaryLine;
    }

    /**
     *  Get blank intervention list of blocs from dictionary according to intervention type and equipment product type
     *
     */

    public function generateBlocsWithFollowingSettings($interventionTypeId, $productCategory) {
        $this->fillCaches();
        $listOfGeneratedBloc = array();
        foreach($this->cache_survey_bloc_question_dictionary as $blocDictionary)
        {
            if(true) {
            $listOfGeneratedBloc[] = $this->fillQuestionBlocData($blocDictionary,$interventionTypeId, $productCategory);
            }
        }
        return $listOfGeneratedBloc;
    }


     /**
     *  Fill bloc question data according to intervention type and equipment product type
     *
     */

    private function fillQuestionBlocData($blocDictionary,$interventionTypeId, $productCategory)
    {
        //We fill status field
        $blocDictionary = InterventionSurvey::fillDataFromJSONDictionnary($blocDictionary, "status", $this->cache_survey_bloc_status_dictionary);
        $statusList = array();
        foreach($blocDictionary["status"] as $index => $status){
            $statusList[$index] = $this->fillStatus($status,$interventionTypeId, $productCategory);
        }
        $blocDictionary["status"] = $statusList;
        //We fill question field
        $blocDictionary = InterventionSurvey::fillDataFromJSONDictionnary($blocDictionary, "questions", $this->cache_survey_question_dictionary);
        $questions = array();
        foreach($blocDictionary["questions"] as $index => $question){
            $questions[$index] = $this->fillAnswer($question,$interventionTypeId, $productCategory);
        }
        $blocDictionary["questions"] = $questions;
        return $blocDictionary;
    }

     /**
     *  Fill answer data according to intervention type and equipment product type
     *
     */
    function fillAnswer($answer, $interventionTypeId, $productCategory){
        return InterventionSurvey::fillDataFromJSONDictionnary($answer, "predefined_texts", $this->cache_survey_answer_predefined_text);
    }

     /**
     *  Fill status data according to intervention type and equipment product type
     *
     */
    function fillStatus($status,$interventionTypeId, $productCategory){
        return InterventionSurvey::fillDataFromJSONDictionnary($status, "predefined_texts", $this->cache_survey_bloc_status_predefined_text_dictionary);
    }

    /**
     *  Get all attached files of the intervention
     *
     * @return  void
     */
    public function fetch_attached_files()
    {
        global $conf, $langs, $formfile;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        if (!is_object($formfile)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
            $formfile = new FormFile($this->db);
        }

        $this->attached_files = array();
        $upload_dir = $conf->ficheinter->dir_output.'/'.dol_sanitizeFileName($this->ref);
        $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$');
        foreach ($filearray as $file) {
            $relativepath = dol_sanitizeFileName($this->ref) . '/' . $file["name"];

            $documenturl = DOL_URL_ROOT . '/document.php';
            if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper

            // Show file name with link to download
            $tmp = $formfile->showPreview($file, 'ficheinter', $relativepath, 0, '');
            $out = ($tmp ? $tmp . ' ' : '');
            $out .= '<a class="documentdownload" href="' . $documenturl . '?modulepart=ficheinter&amp;file=' . urlencode($relativepath) . '"';
            $mime = dol_mimetype($relativepath, '', 0);
            if (preg_match('/text/', $mime)) $out .= ' target="_blank"';
            $out .= ' target="_blank">';
            $out .= img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]) . ' ' . $file["name"];
            $out .= '</a>';

            $this->attached_files[$file["name"]] = $out;
        }
    }

    /**
     *  Update attached filename of all question bloc of the survey
     *
     * @param   string  $old_filename   Old filename
     * @param   string  $new_filename   New filename
     *
     * @return  int                     <0 if KO, >0 if OK
     */
    public function update_attached_filename_in_survey($old_filename, $new_filename)
    {
        $sql = "SELECT eiqb.rowid, eiqb.attached_files";
        $sql .= " FROM " . MAIN_DB_PREFIX . "extendedintervention_question_bloc AS eiqb";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "extendedintervention_survey_bloc AS eisb ON eisb.rowid = eiqb.fk_survey_bloc";
        $sql .= " WHERE eiqb.entity IN (" . getEntity('ei_question_bloc') . ")";
        $sql .= " AND eisb.fk_fichinter = " . $this->id;

        $this->db->begin();

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $attached_files = !empty($obj->attached_files) ? unserialize($obj->attached_files) : array();

                if (in_array($old_filename, $attached_files)) {
                    $attached_files = array_diff($attached_files, array($old_filename));
                    $attached_files[] = $new_filename;
                    $attached_files = array_flip(array_flip($attached_files));

                    // Update into database
                    $sql2 = "UPDATE " . MAIN_DB_PREFIX . "extendedintervention_question_bloc";
                    $sql2 .= " SET attached_files = " . (!empty($attached_files) ? "'" . $this->db->escape(serialize($attached_files)) . "'" : "NULL");
			$sql2 .= " WHERE rowid = " . $obj->rowid;

                    $resql2 = $this->db->query($sql2);
                    if (!$resql2) {
                        $this->errors[] = $this->db->error();
                        $this->db->rollback();
                        dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
                        return -1;
                    }
                }
            }
        } else {
            $this->errors[] = $this->db->error();
            $this->db->rollback();
            dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
            return -1;
        }

        $this->db->commit();
        return 1;
    }
}
