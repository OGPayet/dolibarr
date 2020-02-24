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
dol_include_once('/interventionsurvey/class/surveypart.class.php');

/**
 *
 * Function to sort intervention survey element on fetch and generation
 */

function intervention_survey_cmp($a, $b) {
    if (!isset($a) || !isset($a["position"])) {return 1;}
    if (!isset($b) || !isset($b["position"])) {return -1;}
    if ($a["position"] == $b["position"]) {return 0;}
    return $a["position"] < $b["position"] ? -1 : 1;
}
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
	 * @var SurveyPart[]
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
     * Errors
     */
    public $errors = array();


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
     * Cache Available Product Category array
     */
    public $cache_product_categories;

    /**
     * Dictionary survey - survey if only data from dictionary were used
     */
    public $survey_taken_from_dictionary = array();

    /**
     *  Fill dictionary caches
     *
     */
    public function fillCaches(){
        if(!isset($this->cache_survey_bloc_question_dictionary)) {
            $this->cache_survey_bloc_question_dictionary = self::fetchProperDataFromDictionary(
                $this->db, 'interventionsurvey', 'SurveyBlocQuestion',
            array("position","identifier", "label", "icon",  "label_editable", "description_editable", "deletable",
            "private", "bloc_in_general_part", "categories", "status", "questions", "extrafields", "types_intervention","mandatory_status"),
        array("categories","extrafields","types_intervention"));
        }

        if(!isset($this->cache_survey_bloc_status_dictionary)) {
            $this->cache_survey_bloc_status_dictionary = self::fetchProperDataFromDictionary(
                $this->db, 'interventionsurvey', 'SurveyBlocStatus',
            array("position","identifier", "label", "icon",  "color", "mandatory_justification", "deactivate_bloc", "predefined_texts"));
        }

        if(!isset($this->cache_survey_bloc_status_predefined_text_dictionary)) {
            $this->cache_survey_bloc_status_predefined_text_dictionary = self::fetchProperDataFromDictionary(
                $this->db, 'interventionsurvey', 'SurveyBlocStatusPredefinedText',
            array("position","identifier", "label", "blkLim",  "catLim"),
            array("blkLim","catLim"));
        }
        if(!isset($this->cache_survey_question_dictionary)) {
            $this->cache_survey_question_dictionary = self::fetchProperDataFromDictionary(
                $this->db, 'interventionsurvey', 'SurveyQuestion',
            array("position","identifier", "mandatory_answer", "label", "answers",  "extrafields"),
            array("extrafields"));
        }
        if(!isset($this->cache_survey_answer_dictionary)) {
            $this->cache_survey_answer_dictionary = self::fetchProperDataFromDictionary(
                $this->db, 'interventionsurvey', 'SurveyAnswer',
            array("position","identifier", "label", "color",  "mandatory_justification", "predefined_texts"));
        }
        if(!isset($this->cache_survey_answer_predefined_text)) {
            $this->cache_survey_answer_predefined_text = self::fetchProperDataFromDictionary(
                $this->db, 'interventionsurvey', 'SurveyAnswerPredefinedText',
            array("position","identifier", "label", "bloc_filter",  "cat_filter"),
            array("bloc_filter","cat_filter"));
        }
        if(!isset($this->cache_product_categories)){
            dol_include_once('/interventionsurvey/class/html.forminterventionsurvey.class.php');
            $formextendedintervention = new FormInterventionSurvey($this->db);
            $this->cache_product_categories = $formextendedintervention->get_categories_array();
        }

        }

/**
 *
 * Prepare data fetched from dictionary in order to have simple and proper object
 *
 */

 public static function fetchProperDataFromDictionary($db, $moduleName, $dictionaryName, $fieldToKeep = array(), $fieldsToTransformInArray = array()) {
    dol_include_once('/advancedictionaries/class/dictionary.class.php');
    $data = Dictionary::getJSONDictionary($db, $moduleName, $dictionaryName);
    $result = array();
    foreach($data as $position=>$value){
        $temp = array();
        $temp["c_rowid"] = $value["rowid"];
        foreach($fieldsToTransformInArray as $field)
        {
            $value[$field] = array_filter(explode(",",$value[$field]));
        }
        foreach($fieldToKeep as $field)
        {
            $temp[$field] = $value[$field];
        }
        $result[$position] = $temp;
    }
    return $result;
 }

     /**
     * Fill object from one array with index value taken from object initial property value
     *
     */

    static function fillDataFromJSONDictionary($JSONDictionaryLine,$field,$JSONDataDictionary) {
        $listOfIds = array_filter(explode(",", $JSONDictionaryLine[$field]));
        $JSONDictionaryLine[$field] = array();
        foreach($listOfIds as $id) {
            $JSONDictionaryLine[$field][] = $JSONDataDictionary[$id];
        }
        return $JSONDictionaryLine;
    }

    /**
   *	Sort by position field value an array
      */
    static function sortArrayOfObjectByPositionObjectProperty($array) {
        usort($array, 'intervention_survey_cmp');
        return $array;
    }

    /**
     *  Get list of blocs from dictionary according to intervention type and equipment product category
     *
     */

    public function generateBlocsWithFollowingSettings($interventionTypeId, $productCategory) {
        $this->fillCaches();
        $listOfGeneratedBloc = array();
        foreach($this->cache_survey_bloc_question_dictionary as $blocDictionary)
        {
            if(self::shouldThisBlocOfQuestionBeIntoThisSurvey($blocDictionary, $interventionTypeId, $productCategory)) {
            $listOfGeneratedBloc[] = $this->fillStatusAndQuestionInQuestionBloc($blocDictionary, $productCategory);
            }
        }
        return $listOfGeneratedBloc;
    }

    /**
     *
     * Synchronise survey according to dictionary data
     *
     */



    /**
     *
     * Generate Survey from dictionary according to this intervention data
     *
     */

     public function generateSurveyFromDictionary() {
        if ($this->fetchObjectLinked() < 0){
            return -1;
        }
        if (empty($this->array_options)) {
            $this->fetch_optionals();
        }
        $this->survey_taken_from_dictionary = $this->generateInterventionSurveyPartsWithFollowingSettings($this->array_options['options_ei_type'],$this->linkedObjectsIds);
        return 1;
     }

    /**
     *  Get intervention parts according to intervention type and list of equipement product categories
     *
     */

    public function generateInterventionSurveyPartsWithFollowingSettings($interventionTypeId, $arrayOfModuleNameAndItemId) {
        $this->fillCaches();
        $surveyParts = array();
        $generalSurveyBlocParts = array();
        foreach($arrayOfModuleNameAndItemId as $moduleName=>$listOfId) {
            switch($moduleName) {
                case "equipement":
                    foreach($listOfId as $equipementId)
                    {
                        $listOfGeneratedBlocsForThisEquipement = array();
                        $categoriesOfProductForThisEquipement = $this->getArrayOfProductCategoriesAssociatedToEquipementId($equipementId);
                        $crudeListOfBlocsForThisEquipementInThisSurvey = array();
                        //We create list of blocs needed to be added to this survey
                        foreach($categoriesOfProductForThisEquipement as $categoryId){
                            $crudeListOfBlocsForThisEquipementInThisSurvey = array_merge(
                                $crudeListOfBlocsForThisEquipementInThisSurvey,
                                $this->generateBlocsWithFollowingSettings($interventionTypeId, $categoryId));
                        }
                        //We put these blocs into survey parts
                        foreach($crudeListOfBlocsForThisEquipementInThisSurvey as $bloc){
                            $bloc_id= $bloc["c_rowid"];
                            if(empty($bloc["bloc_in_general_part"])){
                                $listOfGeneratedBlocsForThisEquipement[$bloc_id] = $bloc;
                            }
                            else
                            {
                                $generalSurveyBlocParts[$bloc_id] = $bloc;
                            }
                        }
                        $listOfGeneratedBlocsForThisEquipement = self::sortArrayOfObjectByPositionObjectProperty($listOfGeneratedBlocsForThisEquipement);
                        $surveyParts[] = $this->generateEquipementSurveyPart($listOfGeneratedBlocsForThisEquipement,$equipementId, count($surveyParts)+1);
                    }
                break;

            //Put here code to generate survey parts according to another classes
            }
        }

        $generalSurveyBlocParts = self::sortArrayOfObjectByPositionObjectProperty($generalSurveyBlocParts);
        $surveyParts[] = $this->generateGeneralSurveyPart($generalSurveyBlocParts);
        $data = self::sortArrayOfObjectByPositionObjectProperty($surveyParts);
        //We have final data, we unset useless data
        foreach($data as $index=>$part){
            unset($data[$index]["position"]);
        }
        return $data;
    }

/**
 *
 * Generate information for general Survey Parts
 *
 */

function generateGeneralSurveyPart($listOfGeneralBlocs) {
    $part = array(
        'label'=>"General Parts",
        'position'=>null,
        'fk_identifier_type'=>null,
        'fk_identifier_value'=>null,
        'blocs'=>$listOfGeneralBlocs,
    );
    return $part;
}

 /**
 *
 * Generate information for each specific parts linked to equipement
 *
 */

function generateEquipementSurveyPart($listOfEquipementBlocs, $equipementId, $position=null) {
    dol_include_once('/equipement/class/equipement.class.php');
    $equipment_static = new Equipement($this->db);
    $equipment_static->fetch($equipementId);
    $equipment_static->fetch_product();

    $part = array(
        'label'=> $equipment_static->ref . " - " . $equipment_static->product->label,
        'position'=>$position,
        'fk_identifier_type'=>"equipement",
        'fk_identifier_value'=>$equipementId,
        'blocs'=>$listOfEquipementBlocs,
    );
    return $part;
}




    /**
     *  Get product categories associated to an equipment id, according only to accessible categories
     *
     */

     public function getArrayOfProductCategoriesAssociatedToEquipementId($equipementId) {
        dol_include_once('/equipement/class/equipement.class.php');
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $category_static = new Categorie($this->db);
        $equipment_static = new Equipement($this->db);
        $categories = array();
        if ($equipment_static->fetch($equipementId) > 0 && $equipment_static->fk_product > 0) {
                $categories = $category_static->containing($equipment_static->fk_product, 'product', 'id');
        }
        foreach($categories as $index=>$categoryId) {
            if(!isset($this->cache_product_categories[$categoryId])){
                unset($categories[$index]);
            }
        }
        return $categories;
     }


     /**
     *  Fill bloc question data according to intervention type and equipment product type
     *
     */

    private function fillStatusAndQuestionInQuestionBloc($questionBloc, $productCategory)
    {
        //We fill status field
        $questionBloc = $this->fillStatusInQuestionBloc($questionBloc, $productCategory);

        //We fill question field
        $questionBloc = $this->fillQuestionInQuestionBloc($questionBloc, $productCategory);
        return $questionBloc;

    }
     /**
     *  Fill question in question blocs according to intervention type and equipment product type
     *
     */
    function fillQuestionInQuestionBloc($questionBloc, $productCategory){
        $data = self::fillDataFromJSONDictionary($questionBloc, "questions", $this->cache_survey_question_dictionary);
        $questionList = array();
        foreach($data["questions"] as $index=>$answer){
            $questionList[$index] = $this->fillAnswerInQuestion($answer,$questionBloc["c_rowid"], $productCategory);
        }
        $questionList = self::sortArrayOfObjectByPositionObjectProperty($questionList);
        $data["questions"] = $questionList;
        return $data;
    }
    /**
     *  Fill answer in question according to intervention type and equipment product type
     *
     */
    function fillAnswerInQuestion($question, $blocDictionaryId, $productCategory){
        $data = self::fillDataFromJSONDictionary($question, "answers", $this->cache_survey_answer_dictionary);
        $answerList = array();
        foreach($data["answers"] as $index=>$answer){
            $answerList[$index] = $this->fillAnswerPredefinedTextInAnswer($answer,$blocDictionaryId, $productCategory);
        }
        $answerList = self::sortArrayOfObjectByPositionObjectProperty($answerList);
        $data["answers"] = $answerList;
        return $data;
    }

     /**
     *  Fill answer predefined text in answer according to intervention type and equipment product type
     *
     */
    function fillAnswerPredefinedTextInAnswer($answer, $blocDictionaryId, $productCategory){
        $data = self::fillDataFromJSONDictionary($answer, "predefined_texts", $this->cache_survey_answer_predefined_text);
        $data["predefined_texts"] = array_filter($data["predefined_texts"], function($value) use ($blocDictionaryId,$productCategory) {
            return self::shouldThisAnswerPredefinedTextBeIntoThisStatus($value,$blocDictionaryId, $productCategory);
        });
        $data["predefined_texts"] = self::sortArrayOfObjectByPositionObjectProperty($data["predefined_texts"]);
        foreach($data["predefined_texts"] as &$predefined_text){
            unset($predefined_text["bloc_filter"]);
            unset($predefined_text["cat_filter"]);
        }
        return $data;
    }

     /**
     *  Fill status in question bloc data according to intervention type and equipment product type
     *
     */
    function fillStatusInQuestionBloc($questionBloc, $productCategory){
        $data = self::fillDataFromJSONDictionary($questionBloc, "status", $this->cache_survey_bloc_status_dictionary);
        $statusList = array();
        $statusList = self::sortArrayOfObjectByPositionObjectProperty($statusList);
        foreach($data["status"] as $index=>$status) {
            $statusList[$index] = $this->fillStatusPredefinedTextInQuestionBlocStatus($status,$questionBloc["c_rowid"], $productCategory);
            unset($statusList[$index]["position"]);
        }
        $data["status"] = $statusList;
        return $data;
    }

     /**
     *  Fill status predefined text in status bloc data according to intervention type and equipment product type
     *
     */
    function fillStatusPredefinedTextInQuestionBlocStatus($status,$blocDictionaryId, $productCategory){
        $data = self::fillDataFromJSONDictionary($status, "predefined_texts", $this->cache_survey_bloc_status_predefined_text_dictionary);
        $data["predefined_texts"] = array_filter($data["predefined_texts"], function($value) use ($blocDictionaryId,$productCategory) {
            return self::shouldThisStatusPredefinedTextBeIntoThisStatus($value,$blocDictionaryId, $productCategory);
        });
        $data["predefined_texts"] = self::sortArrayOfObjectByPositionObjectProperty($data["predefined_texts"]);
        foreach($data["predefined_texts"] as &$predefined_text){
            unset($predefined_text["blkLim"]);
            unset($predefined_text["catLim"]);
            unset($predefined_text["position"]);
        }
        return $data;
    }
    /**
     * Generic method to check if an item must be include in parts of this survey
     */

     public static function shouldThisItemBeIntoThisSurvey($item,$listOfSearchedValue) {
         if(!isset($item)) {
             return false;
         }
         $result = true;
         foreach($listOfSearchedValue as $field=>$searchValue) {
             if(!isset($item[$field]) || empty($item[$field])) {
                 continue;
             }
             if( !isset($searchValue) || array_search($searchValue,$item[$field]) === false ){
                 $result=false;
             }
         }
         return $result;
     }

    /**
     * Method to check if a bloc should be into this survey according to dictionary data
     */

    public static function shouldThisBlocOfQuestionBeIntoThisSurvey($blocOfQuestion, $interventionTypeId, $productCategory){
        return self::shouldThisItemBeIntoThisSurvey($blocOfQuestion,array("categories"=>$productCategory, "types_intervention"=>$interventionTypeId));
    }

    /**
     * Method to check if a status predefined Text should be into this survey according to dictionary data
     */

    public static function shouldThisStatusPredefinedTextBeIntoThisStatus($statusPredefinedText, $blocDictionaryId, $productCategory){
        return self::shouldThisItemBeIntoThisSurvey($statusPredefinedText,array("catLim"=>$productCategory, "blkLim"=>$blocDictionaryId));
    }

    /**
     * Method to check if an answer predefined Text should be into this survey according to dictionary data
     */

    public static function shouldThisAnswerPredefinedTextBeIntoThisStatus($answerPredefinedText, $blocDictionaryId, $productCategory){
        return self::shouldThisItemBeIntoThisSurvey($answerPredefinedText,array("cat_filter"=>$productCategory, "bloc_filter"=>$blocDictionaryId));
    }

/**
 *
 * Fetch Survey with backported dolibarr v11 functions
 *
 *
 */

public function fetchSurvey()
{
    $this->survey = array();
    $data = $this->interventionSurveyFetchLinesCommon(" ORDER BY position ASC","SurveyPart",$this->survey);
    return $data;
}

/**
 *
 * Save Survey
 *
 *
 */

public function saveSurvey($user)
{
    global $langs;
    $this->db->begin();
    $errors = array();
    if($this->is_survey_read_only()){
        $errors[] = $langs->trans('InterventionSurveyReadOnlyMode');
        $this->db->rollback();
        $this->errors = $errors;
        return -1;
    }
    foreach($this->survey as $position=>$surveyPart){
        $surveyPart->position = $position;
        $surveyPart->save($user, $this->id);
        $errors = array_merge($errors, $surveyPart->errors);
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

/**
 *
 * Load survey in memory from the given array of survey parts
 *
 */

 public function setSurveyFromFetchObj(&$arrayOfSurveyParts){
    $arrayOfSurveyParts = json_decode(json_encode($arrayOfSurveyParts));
     $this->survey = array();
     if(isset($arrayOfSurveyParts)){
        foreach($arrayOfSurveyParts as $surveyPartObj){
            $surveyPart = new SurveyPart($this->db);
            $surveyPart->setVarsFromFetchObj($surveyPartObj, $this);
            $surveyPart->fk_fichinter = $this->id;
            $this->survey[] = $surveyPart;
        }
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

        if (!class_exists($objectlineclassname))
        {
            $this->error = 'Error, class '.$objectlineclassname.' not found during call of fetchLinesCommon';
            $this->errors[] = $this->error;
            return -1;
        }

        $objectline = new $objectlineclassname($this->db);

        $sql = 'SELECT '.$objectline->getFieldList();
        $sql .= ' FROM '.MAIN_DB_PREFIX.$objectline->table_element;
        $sql .= ' WHERE fk_'.$this->element.' = '.$this->id;
        if ($morewhere)   $sql .= $morewhere;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_rows = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_rows)
            {
                $obj = $this->db->fetch_object($resql);
                if ($obj)
                {
                    $newline = new $objectlineclassname($this->db);
                    $newline->setVarsFromFetchObj($obj);
                    if(method_exists($newline, "fetchLines")){
                     $newline->fetchLines($this);
                    }
                    $resultValue[] = $newline;
                    $this->errors = array_merge($this->errors, $newline->errors);
                }
                $i++;
            }
        }
        else
        {
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
            return -1;
        }
        return empty($this->errors) ? 1 : -1;
    }

    /**
     *  Is the survey read only
     *
     * @return  int                 =0 if No, >0 if Yes
     */
    function is_survey_read_only()
    {
        return $this->id > 0 && $this->statut == self::STATUS_VALIDATED ? 0 : 1;
    }

}
