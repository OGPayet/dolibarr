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
dol_include_once('/interventionsurvey/lib/interventionsurvey.helper.php');
dol_include_once('/societe/class/societe.helper.php');



/**
 *
 * Function to sort intervention survey element on fetch and generation
 */

function intervention_survey_cmp($a, $b)
{
    if (!isset($a) || !isset($a["position"])) {
        return 1;
    }
    if (!isset($b) || !isset($b["position"])) {
        return -1;
    }
    if ($a["position"] == $b["position"]) {
        return 0;
    }
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
        "fk_contrat" => '', "thirdparty" => '', "array_options" => '', "survey" => '',
        'lines'=>'','thirdparty'=>'','benefactor'=>'','watcher'=>'', 'linkedObjectsIds'=>''
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
     * @var SurveyPart[]
     */
    public $survey = array();

    /**
     * @var array List of attached files of the intervention
     */
    public $attached_files = array();

    /**
     * @var object Benefactor of this intervention
     */
    public $benefactor;

    /**
     * @var object Watcher of this intervention
     */
    public $watcher;

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
    public static $cache_survey_bloc_question_dictionary;

    /**
     * Cache Dictionary data of status
     */
    public static $cache_survey_bloc_status_dictionary;

    /**
     * Cache Dictionary data of status_predefined_text
     */
    public static $cache_survey_bloc_status_predefined_text_dictionary;

    /**
     * Cache Dictionary data of question
     */
    public static $cache_survey_question_dictionary;

    /**
     * Cache Dictionary data of answer
     */
    public static $cache_survey_answer_dictionary;

    /**
     * Cache Dictionary data of answer_predefined_text
     */
    public static $cache_survey_answer_predefined_text;

    /**
     * Cache Available Product Category array
     */
    public static $cache_product_categories;

    /**
     * Dictionary survey - survey if only data from dictionary were used
     */
    public $survey_taken_from_dictionary;


    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB &$db)
    {
        parent::__construct($db);
        global $langs;
        $langs->load("interventionsurvey@interventionsurvey");
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
     *  Fill dictionary caches
     *
     */
    public function fillCaches()
    {
        if (!self::$cache_survey_bloc_question_dictionary) {
            self::$cache_survey_bloc_question_dictionary = self::fetchProperDataFromDictionary(
                $this->db,
                'interventionsurvey',
                'SurveyBlocQuestion',
                array(
                    "position", "identifier", "label", "icon",  "label_editable", "description_editable", "deletable",
                    "private", "bloc_in_general_part", "categories", "status", "questions", "extrafields", "types_intervention", "mandatory_status"
                ),
                array("categories", "extrafields", "types_intervention")
            );
        }

        if (!self::$cache_survey_bloc_status_dictionary) {
            self::$cache_survey_bloc_status_dictionary = self::fetchProperDataFromDictionary(
                $this->db,
                'interventionsurvey',
                'SurveyBlocStatus',
                array("position", "identifier", "label", "icon",  "color", "mandatory_justification", "deactivate_bloc", "predefined_texts")
            );
        }

        if (!self::$cache_survey_bloc_status_predefined_text_dictionary) {
            self::$cache_survey_bloc_status_predefined_text_dictionary = self::fetchProperDataFromDictionary(
                $this->db,
                'interventionsurvey',
                'SurveyBlocStatusPredefinedText',
                array("position", "identifier", "label", "blkLim",  "catLim"),
                array("blkLim", "catLim")
            );
        }
        if (!self::$cache_survey_question_dictionary) {
            self::$cache_survey_question_dictionary = self::fetchProperDataFromDictionary(
                $this->db,
                'interventionsurvey',
                'SurveyQuestion',
                array("position", "identifier", "mandatory_answer", "label", "answers",  "extrafields"),
                array("extrafields")
            );
        }
        if (!self::$cache_survey_answer_dictionary) {
            self::$cache_survey_answer_dictionary = self::fetchProperDataFromDictionary(
                $this->db,
                'interventionsurvey',
                'SurveyAnswer',
                array("position", "identifier", "label", "color",  "mandatory_justification", "predefined_texts", "bloc_filter"),
                array("bloc_filter")
            );
        }
        if (!self::$cache_survey_answer_predefined_text) {
            self::$cache_survey_answer_predefined_text = self::fetchProperDataFromDictionary(
                $this->db,
                'interventionsurvey',
                'SurveyAnswerPredefinedText',
                array("position", "identifier", "label", "bloc_filter",  "cat_filter", "quest_filt"),
                array("bloc_filter", "cat_filter", "quest_filt")
            );
        }
        if (!self::$cache_product_categories) {
            dol_include_once('/interventionsurvey/class/html.forminterventionsurvey.class.php');
            $formextendedintervention = new FormInterventionSurvey($this->db);
            self::$cache_product_categories = $formextendedintervention->get_categories_array();
        }
    }

    /**
     *
     * Prepare data fetched from dictionary in order to have simple and proper object
     *
     */

    public static function fetchProperDataFromDictionary($db, $moduleName, $dictionaryName, $fieldToKeep = array(), $fieldsToTransformInArray = array())
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $data = Dictionary::getJSONDictionary($db, $moduleName, $dictionaryName);
        $result = array();
        foreach ($data as $position => $value) {
            $temp = array();
            $temp["c_rowid"] = $value["rowid"];
            foreach ($fieldsToTransformInArray as $field) {
                $value[$field] = array_filter(explode(",", $value[$field]));
            }
            foreach ($fieldToKeep as $field) {
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

    static function fillDataFromJSONDictionary($JSONDictionaryLine, $field, $JSONDataDictionary)
    {
        $listOfIds = array_filter(explode(",", $JSONDictionaryLine[$field]));
        $JSONDictionaryLine[$field] = array();
        foreach ($listOfIds as $id) {
            $JSONDictionaryLine[$field][] = $JSONDataDictionary[$id];
        }
        return $JSONDictionaryLine;
    }

    /**
     *	Sort by position field value an array
     */
    static function sortArrayOfObjectByPositionObjectProperty($array)
    {
        usort($array, 'intervention_survey_cmp');
        return $array;
    }

    /**
     *  Get list of blocs from dictionary according to intervention type and equipment product category
     *
     */

    public function generateBlocsWithFollowingSettings($interventionTypeId, $productCategory)
    {
        $this->fillCaches();
        $listOfGeneratedBloc = array();
        foreach (self::$cache_survey_bloc_question_dictionary as $blocDictionary) {
            if (self::shouldThisBlocOfQuestionBeIntoThisSurvey($blocDictionary, $interventionTypeId, $productCategory)) {
                $listOfGeneratedBloc[] = $this->fillStatusAndQuestionInQuestionBloc($blocDictionary, $productCategory);
            }
        }
        return $listOfGeneratedBloc;
    }

    /**
     *
     * Generate Survey from dictionary according to this intervention data
     *
     */

    public function generateSurveyFromDictionary()
    {
        if ($this->fetchObjectLinked() < 0) {
            return -1;
        }
        if (empty($this->array_options)) {
            $this->fetch_optionals();
        }
        $this->survey_taken_from_dictionary = $this->generateInterventionSurveyPartsWithFollowingSettings($this->array_options['options_ei_type'], $this->linkedObjectsIds);
        return 1;
    }

    /**
     *  Get intervention parts according to intervention type and list of equipement product categories
     *
     */

    public function generateInterventionSurveyPartsWithFollowingSettings($interventionTypeId, $arrayOfModuleNameAndItemId)
    {
        $this->fillCaches();
        $surveyParts = array();
        $generalSurveyBlocParts = array();
        foreach ($arrayOfModuleNameAndItemId as $moduleName => $listOfId) {
            switch ($moduleName) {
                case "equipement":
                    foreach ($listOfId as $equipementId) {
                        $listOfGeneratedBlocsForThisEquipement = array();
                        $categoriesOfProductForThisEquipement = $this->getArrayOfProductCategoriesAssociatedToEquipementId($equipementId);
                        $crudeListOfBlocsForThisEquipementInThisSurvey = array();
                        //We create list of blocs needed to be added to this survey
                        foreach ($categoriesOfProductForThisEquipement as $categoryId) {
                            $crudeListOfBlocsForThisEquipementInThisSurvey = array_merge(
                                $crudeListOfBlocsForThisEquipementInThisSurvey,
                                $this->generateBlocsWithFollowingSettings($interventionTypeId, $categoryId)
                            );
                        }
                        //We put these blocs into survey parts
                        foreach ($crudeListOfBlocsForThisEquipementInThisSurvey as $bloc) {
                            $bloc_id = $bloc["c_rowid"];
                            if (empty($bloc["bloc_in_general_part"])) {
                                $listOfGeneratedBlocsForThisEquipement[$bloc_id] = $bloc;
                            } else {
                                $generalSurveyBlocParts[$bloc_id] = $bloc;
                            }
                        }
                        $listOfGeneratedBlocsForThisEquipement = self::sortArrayOfObjectByPositionObjectProperty($listOfGeneratedBlocsForThisEquipement);
                        $surveyParts[] = $this->generateEquipementSurveyPart($listOfGeneratedBlocsForThisEquipement, $equipementId, count($surveyParts) + 1);
                    }
                    break;

                    //Put here code to generate survey parts according to another classes
            }
        }

        $generalSurveyBlocParts = self::sortArrayOfObjectByPositionObjectProperty($generalSurveyBlocParts);
        $surveyParts[] = $this->generateGeneralSurveyPart($generalSurveyBlocParts);
        $data = self::sortArrayOfObjectByPositionObjectProperty($surveyParts);
        //We have final data, we unset useless data
        foreach ($data as $index => $part) {
            unset($data[$index]["position"]);
        }
        return $data;
    }

    /**
     *
     * Generate information for general Survey Parts
     *
     */

    function generateGeneralSurveyPart($listOfGeneralBlocs)
    {
        global $langs;
        $part = array(
            'label' => dol_htmlentitiesbr_decode($langs->trans('InterventionSurveyGeneralPartsLabel')),
            'position' => null,
            'fk_identifier_type' => null,
            'fk_identifier_value' => null,
            'blocs' => $listOfGeneralBlocs,
        );
        return $part;
    }

    /**
     *
     * Generate information for each specific parts linked to equipement
     *
     */

    function generateEquipementSurveyPart($listOfEquipementBlocs, $equipementId, $position = null)
    {
        dol_include_once('/equipement/class/equipement.class.php');
        $equipment_static = new Equipement($this->db);
        $equipment_static->fetch($equipementId);
        $equipment_static->fetch_product();

        $part = array(
            'label' => $equipment_static->ref . " - " . $equipment_static->product->label,
            'position' => $position,
            'fk_identifier_type' => "equipement",
            'fk_identifier_value' => $equipementId,
            'blocs' => $listOfEquipementBlocs,
        );
        return $part;
    }




    /**
     *  Get product categories associated to an equipment id, according only to accessible categories
     *
     */

    public function getArrayOfProductCategoriesAssociatedToEquipementId($equipementId)
    {
        dol_include_once('/equipement/class/equipement.class.php');
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $category_static = new Categorie($this->db);
        $equipment_static = new Equipement($this->db);
        $categories = array();
        if ($equipment_static->fetch($equipementId) > 0 && $equipment_static->fk_product > 0) {
            $categories = $category_static->containing($equipment_static->fk_product, 'product', 'id');
        }
        foreach ($categories as $index => $categoryId) {
            if (!isset(self::$cache_product_categories[$categoryId])) {
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
    function fillQuestionInQuestionBloc($questionBloc, $productCategory)
    {
        $data = self::fillDataFromJSONDictionary($questionBloc, "questions", self::$cache_survey_question_dictionary);
        $questionList = array();
        foreach ($data["questions"] as $index => $answer) {
            $questionList[$index] = $this->fillAnswerInQuestion($answer, $questionBloc["c_rowid"], $productCategory);
        }
        $questionList = self::sortArrayOfObjectByPositionObjectProperty($questionList);
        $data["questions"] = $questionList;
        return $data;
    }
    /**
     *  Fill answer in question according to intervention type and equipment product type
     *
     */
    function fillAnswerInQuestion($question, $blocDictionaryId, $productCategory)
    {
        $data = self::fillDataFromJSONDictionary($question, "answers", self::$cache_survey_answer_dictionary);
        $answerList = array();
        foreach ($data["answers"] as $index => $answer) {
            if(self::shouldThisAnswerBeIntoThisBlocOfQuestion($answer,$blocDictionaryId)){
                $answerList[$index] = $this->fillAnswerPredefinedTextInAnswer($answer, $blocDictionaryId, $productCategory, $question["c_rowid"]);
            }
        }
        $answerList = self::sortArrayOfObjectByPositionObjectProperty($answerList);
        foreach ($answerList as &$answer) {
            unset($answer["bloc_filter"]);
        }
        $data["answers"] = $answerList;
        return $data;
    }

    /**
     *  Fill answer predefined text in answer according to intervention type and equipment product type
     *
     */
    function fillAnswerPredefinedTextInAnswer($answer, $blocDictionaryId, $productCategory, $questionId)
    {
        $data = self::fillDataFromJSONDictionary($answer, "predefined_texts", self::$cache_survey_answer_predefined_text);
        $data["predefined_texts"] = array_filter($data["predefined_texts"], function ($value) use ($blocDictionaryId, $productCategory, $questionId) {
            return self::shouldThisAnswerPredefinedTextBeIntoThisAnswer($value, $blocDictionaryId, $productCategory, $questionId);
        });
        $data["predefined_texts"] = self::sortArrayOfObjectByPositionObjectProperty($data["predefined_texts"]);
        foreach ($data["predefined_texts"] as &$predefined_text) {
            unset($predefined_text["bloc_filter"]);
            unset($predefined_text["cat_filter"]);
            unset($predefined_text["quest_filt"]);
        }
        return $data;
    }

    /**
     *  Fill status in question bloc data according to intervention type and equipment product type
     *
     */
    function fillStatusInQuestionBloc($questionBloc, $productCategory)
    {
        $data = self::fillDataFromJSONDictionary($questionBloc, "status", self::$cache_survey_bloc_status_dictionary);
        $statusList = array();
        $statusList = self::sortArrayOfObjectByPositionObjectProperty($statusList);
        foreach ($data["status"] as $index => $status) {
            $statusList[$index] = $this->fillStatusPredefinedTextInQuestionBlocStatus($status, $questionBloc["c_rowid"], $productCategory);
            unset($statusList[$index]["position"]);
        }
        $data["status"] = $statusList;
        return $data;
    }

    /**
     *  Fill status predefined text in status bloc data according to intervention type and equipment product type
     *
     */
    function fillStatusPredefinedTextInQuestionBlocStatus($status, $blocDictionaryId, $productCategory)
    {
        $data = self::fillDataFromJSONDictionary($status, "predefined_texts", self::$cache_survey_bloc_status_predefined_text_dictionary);
        $data["predefined_texts"] = array_filter($data["predefined_texts"], function ($value) use ($blocDictionaryId, $productCategory) {
            return self::shouldThisStatusPredefinedTextBeIntoThisStatus($value, $blocDictionaryId, $productCategory);
        });
        $data["predefined_texts"] = self::sortArrayOfObjectByPositionObjectProperty($data["predefined_texts"]);
        foreach ($data["predefined_texts"] as &$predefined_text) {
            unset($predefined_text["blkLim"]);
            unset($predefined_text["catLim"]);
            unset($predefined_text["position"]);
        }
        return $data;
    }
    /**
     * Generic method to check if an item must be include in parts of this survey
     */

    public static function shouldThisItemBeIntoThisSurvey($item, $listOfSearchedValue)
    {
        if (!isset($item)) {
            return false;
        }
        $result = true;
        foreach ($listOfSearchedValue as $field => $searchValue) {
            if (!isset($item[$field]) || empty($item[$field])) {
                continue;
            }
            if (!isset($searchValue) || array_search($searchValue, $item[$field]) === false) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Method to check if a bloc should be into this survey according to dictionary data
     */

    public static function shouldThisBlocOfQuestionBeIntoThisSurvey($blocOfQuestion, $interventionTypeId, $productCategory)
    {
        return self::shouldThisItemBeIntoThisSurvey($blocOfQuestion, array("categories" => $productCategory, "types_intervention" => $interventionTypeId));
    }

    /**
     * Method to check if a status predefined Text should be into this survey according to dictionary data
     */

    public static function shouldThisStatusPredefinedTextBeIntoThisStatus($statusPredefinedText, $blocDictionaryId, $productCategory)
    {
        return self::shouldThisItemBeIntoThisSurvey($statusPredefinedText, array("catLim" => $productCategory, "blkLim" => $blocDictionaryId));
    }

    /**
     * Method to check if an answer should be into this question according to dictionary data
     */

    public static function shouldThisAnswerBeIntoThisBlocOfQuestion($answer, $questionBlocId)
    {
        return self::shouldThisItemBeIntoThisSurvey($answer, array("bloc_filter" => $questionBlocId));
    }

    /**
     * Method to check if an answer predefined Text should be into this survey according to dictionary data
     */

    public static function shouldThisAnswerPredefinedTextBeIntoThisAnswer($answerPredefinedText, $blocDictionaryId, $productCategory, $questionId)
    {
        return self::shouldThisItemBeIntoThisSurvey($answerPredefinedText, array("cat_filter" => $productCategory, "bloc_filter" => $blocDictionaryId, "quest_filt"=>$questionId));
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
        $data = interventionSurveyFetchLinesCommon(" ORDER BY position ASC", "SurveyPart", $this->survey, $this);
        return $data;
    }

    /**
     *
     * Save Survey
     *
     *
     */

    public function saveSurvey(&$user, $noSurveyReadOnlyCheck = false)
    {
        global $langs;
        $this->db->begin();
        if (!$noSurveyReadOnlyCheck && $this->is_survey_read_only()) {
            $this->errors[] = $langs->trans('InterventionSurveyReadOnlyMode');
            $this->db->rollback();
            return -1;
        }
        foreach ($this->survey as $position => $surveyPart) {
            $surveyPart->position = $position;
            $surveyPart->save($user, $this->id, $noSurveyReadOnlyCheck);
            $this->errors = array_merge($this->errors, $surveyPart->errors);
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
     *
     * Delete Survey
     *
     *
     */

    public function deleteSurvey(&$user, $notrigger = true)
    {
        $this->db->begin();
        $errors = array();

        foreach ($this->survey as $surveyPart) {
            $surveyPart->delete($user, $notrigger, true);
            $errors = array_merge($errors, $surveyPart->errors ?? array());
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
     * Override fichinter fetch in order to fetch survey in the same time
     */
    public function fetch($rowid,$ref=''){
        $result = parent::fetch($rowid,$ref);
        if($result > 0 ) {
            $result = $this->fetch_optionals();
        }
        if($result > 0 ) {
            $result = $this->fetchSurvey();
        }
        if($result > 0 ) {
            if($this->lines){
                foreach($this->lines as $line){
                    $result = $line->fetch_optionals();
                    $this->errors = array_merge($this->errors, $line->errors);
                }
            }
        }
        return $result;
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
        $upload_dir = $conf->ficheinter->dir_output . '/' . dol_sanitizeFileName($this->ref);
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

    public function setSurveyFromFetchObj(&$arrayOfSurveyParts, bool $forceId = false)
    {
        $arrayOfSurveyParts = json_decode(json_encode($arrayOfSurveyParts));
        $this->survey = array();
        if ($arrayOfSurveyParts) {
            foreach ($arrayOfSurveyParts as $surveyPartObj) {
                $surveyPart = new SurveyPart($this->db);
                $surveyPart->setVarsFromFetchObj($surveyPartObj, $this, $forceId);
                $surveyPart->fk_fichinter = $this->id;
                $this->survey[] = $surveyPart;
            }
        }
    }

    /**
     *  Is the survey read only
     *
     * @return  int                 =0 if No, >0 if Yes
     */
    function is_survey_read_only()
    {
        return $this->id > 0 && $this->statut == self::STATUS_DONE;
    }

    /**
     * Clean survey - we remove empty survey Part into bdd
     */
    function cleanSurvey($user, $forceUpdateFromBdd = false)
    {
        $errors = array();
        $this->db->begin();
        if($forceUpdateFromBdd){
            $this->fetchSurvey();
        }
            foreach ($this->survey as $surveyPart) {
                if (empty($surveyPart->blocs)) {
                    $surveyPart->delete($user,true,true);
                    $errors = array_merge($errors, $surveyPart->errors);
                }
            }
        $this->fetchSurvey();
        $errors = array_merge($errors, $this->errors);
        if (empty($errors)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *
     * Merge current InterventionSurvey with a given InterventionSurvey
     *
     */

     public function mergeWithFollowingData(User &$user, self &$newInterventionSurvey, bool $saveWholeObjectToBdd = false, $noTrigger = false){

        $this->db->begin();
        //We update property for this object
        //BEGIN
        //END

        //We begin property update for subobject
        $parameters = array(
            "survey"=>array(
                "identifierPropertiesName" => array("id"),
                "mergeSubItemNameMethod" => "mergeWithFollowingData"),//We may add here fichinter lines
        );

        $errors = mergeSubItemFromObject($user, $this, $newInterventionSurvey, $parameters, false, $noTrigger);
        $this->errors = array_merge($this->errors, $errors);

        if($saveWholeObjectToBdd === true) {
            //$this->save($user); //We may add here fichinter lines saving
            $this->saveSurvey($user);
        }
         //finally we clean the survey
         $this->cleanSurvey($user);

         if (empty($this->errors)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *
     * This is a simple merge function with dictionary data
     * It only remove all survey part and blocs set from dictionary that are no more existing
     * Deletion is made according two mode : soft and hard
     * In soft mode we only delete empty element (no user information have been set)
     * In hard mode we delete everything set from dictionary that are not anymore needed
     * Not the best way of doing, we do not respect Single Responsibility principle from SOLID but it think to simple way of maintain it
     *
     */
    public function mergeCurrentSurveyWithDictionaryData($user, $deleteEmptyStaledBloc = false, $deleteStaledBloc = false, $addMissingPart = true, $addMissingBlocIntoGeneralPart = true, $addMissingBlocInOtherPart = false)
    {

        $this->db->begin();
        $blocToDelete = array();
        $partToAdd = array();
        $blocToAdd = array();

        $oldData = $this->survey;

        $interventionSurveyFromDictionary = clone $this;
        $this->generateSurveyFromDictionary();
        $interventionSurveyFromDictionary->setSurveyFromFetchObj($this->survey_taken_from_dictionary);
        $dataFromDictionary = $interventionSurveyFromDictionary->survey;

        //we try to find bloc to delete
        foreach ($oldData as $index => $oldSurveyPart) {
            $surveyPartIntoNewData =
                getItemFromThisArray($dataFromDictionary, array('fk_identifier_type' => $oldSurveyPart->fk_identifier_type, 'fk_identifier_value' => $oldSurveyPart->fk_identifier_value));

            foreach ($oldSurveyPart->blocs as $oldBloc) {
                if (empty($surveyPartIntoNewData) || empty(getItemFromThisArray($surveyPartIntoNewData->blocs, array('fk_c_survey_bloc_question' => $oldBloc->fk_c_survey_bloc_question)))) {
                    if ($deleteStaledBloc || ($deleteEmptyStaledBloc && $oldBloc->is_empty())) {
                        $blocToDelete[] = $oldBloc;
                    }
                }
            }
        }

        //Now we have a copy of items to delete into $partToDelete and blocToDelete and these items have been unset from oldData
        //Now we add new surveyPart to oldData
        foreach ($dataFromDictionary as $newSurveyPart) {
            $itemInOldData = getItemFromThisArray($oldData, array('fk_identifier_type' => $newSurveyPart->fk_identifier_type, 'fk_identifier_value' => $newSurveyPart->fk_identifier_value));
            if (!$itemInOldData) {
                //it is a new part
                if ($addMissingPart) {
                    $partToAdd[] = $newSurveyPart;
                }
            } else {

                //we look for new blocs
                foreach ($newSurveyPart->blocs as $newBloc) {
                    $oldBloc = getItemFromThisArray($itemInOldData->blocs, array('fk_c_survey_bloc_question' => $newBloc->fk_c_survey_bloc_question));
                    if (!$oldBloc) {
                        //It is a new bloc
                        $newBloc->fk_surveypart = $itemInOldData->id;
                        $isCurrentPartGeneralPart = $newSurveyPart->fk_identifier_type == null && $newSurveyPart->fk_identifier_value == null;
                        if (($addMissingBlocInOtherPart && !$isCurrentPartGeneralPart) || ($isCurrentPartGeneralPart && $addMissingBlocIntoGeneralPart)) {
                            $blocToAdd[] = $newBloc;
                        }
                    }
                }
            }
        }

        foreach ($blocToDelete as $bloc) {
            $bloc->delete($user, true, true);
            $this->errors = array_merge($this->errors, $bloc->errors);
        }
        foreach ($partToAdd as $part) {
            $part->save($user, null, true, true);
            $this->errors = array_merge($this->errors, $part->errors);
        }
        foreach ($blocToAdd as $bloc) {
            $bloc->save($user, null, true, true);
            $this->errors = array_merge($this->errors, $bloc->errors);
        }

        //finally we clean the survey
        $this->cleanSurvey($user, true);
        if (empty($this->errors)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *
     * Soft update of the survey with data from dictionary
     * We add missing survey part
     * We add missing bloc only in General part
     * We delete only empty staled bloc. Thus linked survey part are only deleted if there is no more part inside
     *
     */

    public function softUpdateOfSurveyFromDictionary($user)
    {
        return $this->mergeCurrentSurveyWithDictionaryData($user, true, false, true, true, true);
    }

    /**
     *
     * Method to check if there are missing data on the survey
     *
     */

    public function areDataValid()
    {
        $result = true;
        foreach ($this->survey as $surveyPart) {
            if (!$surveyPart->areDataValid()) {
                $result = false;
                break;
            }
        }
        return $result;
    }
    /**
     * Check perms for user on an interventionSurvey object
     *
     * @param   InterventionSurvey      $object         Object (propal, commande, invoice, fichinter)
     * @return  bool        FALSE to deny user access, TRUE to authorize
     * @throws  Exception
     */
    public function checkUserAccess(User $user)
    {
        global $conf;
        //First we check entity of this object is the same than the current entity
        $sql = "SELECT COUNT(fichinter.rowid) as nb";
        $sql.= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as fichinter";
        $sql.= " WHERE fichinter.rowid = " . $this->id;
        $sql.= " AND fichinter.entity IN (". getEntity($this->table_element, 1) . ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $nbResult = $this->db->fetch_object($resql);
            if(!$nbResult){
                //Item does not exist or is not accessible from this entity
                return false;
            }
        }


        if($user->rights->societe->client->voir){
           return true;
        }
        else {
            // we have to check if $user->socid is the id of the thirdparty of a sales representative of this company
                $listOfSalesRepresentative = array();
                if(!$this->thirdparty && !$this->fk_soc == $user->socid){
                    $this->fetch_thirdparty();
                    $listOfSalesRepresentative = $this->thirdparty->getSalesRepresentatives($user);
                }
                $userLinkedToCompany = $this->fk_soc == $user->socid || in_array($user->id, $listOfSalesRepresentative);
                if (!$conf->companyrelationships->enabled) {
                    return $userLinkedToCompany;
                } else {
                    //Company relationships is activated, we have more check to do
                    $principalAvailability = !!$this->array_options['options_companyrelationships_availability_principal'];
                    $benefactorId = $this->array_options['options_companyrelationships_fk_soc_benefactor'];
                    $benefactorAvailability = !!$this->array_options['options_companyrelationships_availability_principal'];
                    $watcherId = $this->array_options['options_companyrelationships_fk_soc_watcher'];
                    $watcherAvailability = !!$this->array_options['options_companyrelationships_availability_watcher'];

                    if($userLinkedToCompany && $principalAvailability){
                        return true;
                    }
                    //We may check for benefactor now
                    if($benefactorAvailability) {
                        $result = isUserLinkedToThisCompanyDirectlyOrBySalesRepresentative($user, $this->benefactor, $benefactorId);
                        if($result){
                            return $result;
                        }
                    }
                    //We may check for watcher now
                    if($watcherAvailability) {
                        $result = isUserLinkedToThisCompanyDirectlyOrBySalesRepresentative($user, $this->watcher, $watcherId);
                        if($result){
                            return $result;
                        }
                    }
                }
        }
        return false;
    }

    /**
     * Fill the benefactor field with a Society object
     */

    public function fetch_benefactor(bool $forceRefreshFromBdd = null){
        if(!$this->benefactor->id || $forceRefreshFromBdd){
            if(!$this->array_options) {
                $this->fetch_optionals();
            }
            $this->benefactor = fetchACompanyObjectById($this->array_options['options_companyrelationships_fk_soc_benefactor'], $this->db);
        }
    }
    /**
     * Fill the watcher field with a Society object
     */
    public function fetch_watcher(bool $forceRefreshFromBdd = null){
        if(!$this->watcher->id || $forceRefreshFromBdd){
            if(!$this->array_options){
                $this->fetch_optionals();
            }
            $this->watcher = fetchACompanyObjectById($this->array_options['options_companyrelationships_fk_soc_watcher'], $this->db);
        }
    }
}
