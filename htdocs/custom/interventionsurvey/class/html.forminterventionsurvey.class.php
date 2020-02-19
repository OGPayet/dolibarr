<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 *	\file       interventionsurvey/class/html.interventionsurvey.class.php
 *  \ingroup    interventionsurvey
 *	\brief      File of class with all html predefined components for intervention survey
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');
dol_include_once('/interventionsurvey/lib/interventionsurvey.lib.php');

/**
 *	Class to manage generation of HTML components
 *	Only common components for intervention survey must be here.
 *
 */
class FormInterventionSurvey
{
    public $db;
    public $error;
    public $num;

    /**
     * @var Form  Instance of the form
     */
    public $form;

    /**
     * @var FormDictionary  Instance of the form form dictionaries
     */
    public $formdictionary;
    /**
     * @var array  List of request type
     */
    public $request_types_array;
    /**
     * @var array  List of intervention type
     */
    public $intervention_types_list;

    /**
     * @var Array of blocs from post data, with index equal to bloc id
     */
    public $blocs_post;

    /**
     * @var Array of questions from post data, with index equal to question id
     */
    public $questions_post;

    const BLOC_FORM_PREFIX = "intervention_survey_bloc_";
    const QUESTION_FORM_PREFIX = "intervention_survey_question_";

    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->form = new Form($this->db);
        $this->formdictionary = new FormDictionary($this->db);
        $this->blocs_post = GETPOST("blocs");
        $this->questions_post = GETPOST("questions");
    }

    /**
	 * Load the list of intervention type
	 *
     * @return  void
	 */
    public function load_intervention_type()
    {
        if (!isset($this->intervention_types_list)) {
            // Get intervention types list
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $extendedinterventiontype = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventiontype');
            $intervention_types = $extendedinterventiontype->fetch_lines(1, array(), array(), 0, 0, false, true);
            $this->intervention_types_list = array();
            foreach ($intervention_types as $intervention_type) {
                $this->intervention_types_list[$intervention_type->id] = $intervention_type->fields;
            }
        }
    }

    /**
     *	Return list of product categories
     *
     *	@return	array					List of product categories
     */
    function get_categories_array()
    {
        global $conf;

        include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $cat = new Categorie($this->db);
        $cate_arbo = $cat->get_full_arbo(Categorie::TYPE_PRODUCT);

        $list = array();
        foreach ($cate_arbo as $k => $cat) {
            if (((preg_match('/^'.$conf->global->INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES.'$/', $cat['fullpath']) ||
                preg_match('/_'.$conf->global->INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES.'$/', $cat['fullpath'])) && $conf->global->INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORY_INCLUDE) ||
                preg_match('/^'.$conf->global->INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES.'_/', $cat['fullpath']) ||
                preg_match('/_'.$conf->global->INTERVENTIONSURVEY_ROOT_PRODUCT_CATEGORIES.'_/', $cat['fullpath'])) {
                $list[$cat['id']] = $cat['fulllabel'];
            }
        }

        return $list;
    }

    /**
     *	Return multiselect list of product categories
     *
     *	@param	string	$htmlname		Name of select
     *	@param	array	$selected		Array with key+value preselected
     *	@param	int		$key_in_label   1 pour afficher la key dans la valeur "[key] value"
     *	@param	int		$value_as_key   1 to use value as key
     *	@param  string	$morecss        Add more css style
     *	@param  int		$translate		Translate and encode value
     *  @param	int		$width			Force width of select box. May be used only when using jquery couch. Example: 250, 95%
     *  @param	string	$moreattrib		Add more options on select component. Example: 'disabled'
     *	@return	string					HTML multiselect string
     *  @see selectarray
     */
    function multiselect_categories($htmlname='categories', $selected=array(), $key_in_label=0, $value_as_key=0, $morecss='', $translate=0, $width=0, $moreattrib='')
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $list = $this->get_categories_array();

        $out = $this->form->multiselectarray($htmlname, $list, $selected, $key_in_label, $value_as_key, $morecss, $translate, $width, $moreattrib, 'category');

        return $out;
    }

    /**
     *	Return multiselect list of attached files of a intervention
     *
     *	@param	int		$ref_intervention   Ref of the intervention
     *	@param	string	$htmlname		    Name of select
     *	@param	array	$selected		    Array with key+value preselected
     *	@param	int		$key_in_label       1 pour afficher la key dans la valeur "[key] value"
     *	@param	int		$value_as_key       1 to use value as key
     *	@param  string	$morecss            Add more css style
     *	@param  int		$translate		    Translate and encode value
     *  @param	int		$width			    Force width of select box. May be used only when using jquery couch. Example: 250, 95%
     *  @param	string	$moreattrib		    Add more options on select component. Example: 'disabled'
     *	@return	string					    HTML multiselect string
     *  @see selectarray
     */
    function multiselect_attached_files($ref_intervention, $htmlname='attached_files', $selected=array(), $key_in_label=0, $value_as_key=0, $morecss='minwidth300', $translate=0, $width=0, $moreattrib='')
    {
        global $conf, $formfile;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        if (!is_object($formfile)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
            $formfile = new FormFile($this->db);
        }

        $attached_files = array();
        $upload_dir = $conf->ficheinter->dir_output.'/'.dol_sanitizeFileName($ref_intervention);
        $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$');
        foreach ($filearray as $file) {
            $attached_files[$file["name"]] = $file["name"];
        }

        $out = $this->form->multiselectarray($htmlname, $attached_files, $selected, $key_in_label, $value_as_key, $morecss, $translate, "100%", $moreattrib);

        return $out;
    }

    /**
     *
     * Update blocQuestionSurvey object from post data
     * @param bloc SurveyBlocQuestion
     * @return bloc SurveyBlocQuestion
     */

     function updateBlocObjectFromPOST($bloc){
         if($bloc){

             if($bloc->label_editable){
                 $bloc->label = GETPOST(self::BLOC_FORM_PREFIX . $bloc->id . "_label") ?? $bloc->label;
             }

             if($bloc->description_editable){
                $bloc->description = GETPOST(self::BLOC_FORM_PREFIX . $bloc->id . "_description") ?? $bloc->description;
             }

            $bloc->fk_chosen_status = GETPOST(self::BLOC_FORM_PREFIX . $bloc->id . "_fk_chosen_status") ?? $bloc->fk_chosen_status;
            $bloc->description = GETPOST(self::BLOC_FORM_PREFIX . $bloc->id . "_description") ?? $bloc->description;
            $bloc->attached_files = GETPOST(self::BLOC_FORM_PREFIX . $bloc->id . "_attached_files") ?? $bloc->attached_files;
            $bloc->fk_chosen_answer_predefined_text = GETPOST(self::BLOC_FORM_PREFIX . $bloc->id . "_fk_chosen_answer_predefined_text") ?? $bloc->fk_chosen_answer_predefined_text;
            $bloc->private = GETPOST(self::BLOC_FORM_PREFIX . $bloc->id . "_private") ?? $bloc->private;
            if(isset($bloc->questions)){
                foreach($bloc->questions as $question){
                    $question->fk_chosen_answer = GETPOST(self::QUESTION_FORM_PREFIX . $question->id . "_fk_chosen_answer") ?? $question->fk_chosen_answer;
                    $question->justification_text = GETPOST(self::QUESTION_FORM_PREFIX . $question->id . "_justification_text") ?? $question->justification_text;
                    $question->fk_chosen_answer_predefined_text = GETPOST(self::QUESTION_FORM_PREFIX . $question->id . "_fk_chosen_answer_predefined_text") ?? $question->fk_chosen_answer_predefined_text;
                }
            }
         }
         return $bloc;
     }




    /**
     *	Return html to display bloc status and manage justification text
     *
     *	@param	boolean		$readonly   Should we be in readonly mode
     *	@param	SurveyBlocQuestion	$bloc		    Bloc object question
     */

     function displayStatus($readonly, $bloc, $form){
        global $conf,$langs, $hookmanager;
        $bloc = $readonly ? $bloc : $this->mergeNotNullDataFromFirstIntoSecondParameter($this->blocs_post[$bloc->id],$bloc);
        @include dol_buildpath('interventionsurvey/tpl/intervention_survey_bloc_status.tpl.php');
     }

     /**
     *	Return html to display bloc description
     *
     *	@param	boolean		$readonly   Should we be in readonly mode
     *	@param	SurveyBlocQuestion	$bloc		    Bloc object question
     */

    function displayBlocDescription($readonly, $bloc){
        global $conf,$langs, $hookmanager;
        $bloc = $readonly ? $bloc : $this->mergeNotNullDataFromFirstIntoSecondParameter($this->blocs_post[$bloc->id],$bloc);
        @include dol_buildpath('interventionsurvey/tpl/intervention_survey_bloc_description.tpl.php');
     }

     /**
     *	Return html to display question, manage answer and justification text with predefined text
     *
     *	@param	boolean		$readonly   Should we be in readonly mode
     *	@param	SurveyQuestion	$question		    question object
     */

    function displayQuestion($readonly, $question){
        global $conf,$langs, $hookmanager;
        $question = $readonly ? $question : $this->mergeNotNullDataFromFirstIntoSecondParameter($this->questions_post[$question->id],$question);
        @include dol_buildpath('interventionsurvey/tpl/intervention_survey_question.tpl.php');
    }

    /**
     *	Return html to display bloc files
     *
     *	@param	boolean		$readonly   Should status be in readonly mode
     *	@param	SurveyBlocQuestion	$bloc		    question object
     */

    function displayBlocFiles($readonly, $bloc, $intervention_ref, $listOfAvailableFiles = array()){
        global $conf,$langs, $hookmanager;
        $bloc = $readonly ? $bloc : $this->mergeNotNullDataFromFirstIntoSecondParameter($this->blocs_post[$bloc->id],$bloc);
        @include dol_buildpath('interventionsurvey/tpl/intervention_survey_bloc_files.tpl.php');
    }
    /**
     *	Return html to display Extrafields
     *
     *	@param	boolean		$readonly   Should status be in readonly mode
     *	@param	SurveyBlocQuestion	$bloc		    question object
     */

    function displayExtrafields($readonly,$extrafield_object,$extrafield_label, $object, $prefix) {
        global $conf,$langs, $hookmanager;
        @include dol_buildpath('interventionsurvey/tpl/intervention_survey_extrafields.tpl.php');
    }

    /**
     *	Return html to display bloc title
     *
     *	@param	boolean		$readonly   Should status be in readonly mode
     *	@param	SurveyBlocQuestion	$bloc		    question object
     */

    function displayBlocTitle($readonly, $bloc) {
        global $conf,$langs, $hookmanager;
        @include dol_buildpath('interventionsurvey/tpl/intervention_survey_bloc_title.tpl.php');
    }

    /**
     *	Return html to display bloc action button
     *
     *	@param	boolean		$readonly   Should status be in readonly mode
     *	@param	SurveyBlocQuestion	$bloc		    question object
     */

    function displayBlocActionButton($readonly, $bloc) {
        global $conf,$langs, $hookmanager, $user;
        @include dol_buildpath('interventionsurvey/tpl/intervention_survey_action_button.tpl.php');
    }

    /**
     *
     * Return merge data from second parameters into first if data is not null in second parameters
     * @param array $slave
     * @param object $master
     *
     */
    private function mergeNotNullDataFromFirstIntoSecondParameter($slave,$master) {
        if(is_array($slave)){
            foreach($slave as $field=>$value){
                $master->$field = $value;
            }
        }
        return $master;
        }
}

