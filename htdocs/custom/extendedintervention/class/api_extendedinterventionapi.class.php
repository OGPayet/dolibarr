<?php
/* Copyright (C) 2019	Open-DSI	        <support@open-dsi.fr>
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

use Luracast\Restler\RestException;

dol_include_once('/extendedintervention/class/extendedintervention.class.php');
dol_include_once('/extendedintervention/class/extendedinterventionsurveybloc.class.php');
dol_include_once('/extendedintervention/class/extendedinterventionquestionbloc.class.php');

/**
 * API class for Extended Intervention
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class ExtendedInterventionApi extends DolibarrApi {
    /**
     * @var DoliDb      $db         Database object
     */
    static protected $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * Array of whitelist of properties keys to overwrite the white list of each element object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static protected $WHITELIST_OF_PROPERTIES = array(
        'fichinterdet' => array(
            "id" => '', "desc" => '', "duration" => '', "qty" => '', "date" => '', "datei" => '',
            "rang" => '', "product_type" => '', "array_options" => '',
        ),
        'societe' => array(
            "entity" => '', "nom" => '', "name_alias" => '', "particulier" => '', "zip" => '', "town" => '', "status" => '',
            "state_id" => '', "state_code" => '', "state" => '', "departement_code" => '', "departement" => '', "pays" => '',
            "phone" => '', "fax" => '', "email" => '', "skype" => '', "url" => '', "barcode" => '', "idprof1" => '', "idprof2" => '',
            "idprof3" => '', "idprof4" => '', "idprof5" => '', "idprof6" => '', "prefix_comm" => '', "tva_assuj" => '', "tva_intra" => '',
            "localtax1_assuj" => '', "localtax1_value" => '', "localtax2_assuj" => '', "localtax2_value" => '', "capital" => '',
            "typent_id" => '', "typent_code" => '', "effectif" => '', "effectif_id" => '', "forme_juridique_code" => '', "forme_juridique" => '',
            "remise_percent" => '', "mode_reglement_supplier_id" => '', "cond_reglement_supplier_id" => '', "fk_prospectlevel" => '',
            "date_modification" => '', "date_creation" => '', "client" => '', "prospect" => '', "fournisseur" => '', "code_client" => '',
            "code_fournisseur" => '', "code_compta" => '', "code_compta_fournisseur" => '', "stcomm_id" => '', "statut_commercial" => '',
            "price_level" => '', "outstanding_limit" => '', "parent" => '', "default_lang" => '', "ref" => '', "ref_ext" => '',
            "logo" => '', "array_options" => '', "id" => '', "linkedObjectsIds" => '','address' => '',"name" => '',
        ),
        'product' => array(
            "label" => '', "description" => '', "type" => '', "price" => '', "price_ttc" => '', "price_min" => '',
            "price_min_ttc" => '', "price_base_type" => '', "multiprices" => '', "multiprices_ttc" => '', "multiprices_base_type" => '',
            "multiprices_min" => '', "multiprices_min_ttc" => '', "multiprices_tva_tx" => '', "multiprices_recuperableonly" => '',
            "price_by_qty" => '', "prices_by_qty" => '', "prices_by_qty_id" => '', "prices_by_qty_list" => '', "default_vat_code" => '',
            "tva_tx" => '', "tva_npr" => '', "localtax1_tx" => '', "localtax2_tx" => '', "localtax1_type" => '', "localtax2_type" => '',
            "stock_reel" => '', "cost_price" => '', "pmp" => '', "seuil_stock_alerte" => '', "desiredstock" => '', "duration_value" => '',
            "duration_unit" => '', "status" => '', "status_buy" => '', "finished" => '', "status_batch" => '', "customcode" => '',
            "url" => '', "weight" => '', "weight_units" => '', "length" => '', "length_units" => '', "surface" => '', "surface_units" => '',
            "volume" => '', "volume_units" => '', "accountancy_code_buy" => '', "accountancy_code_sell" => '', "barcode" => '',
            "multilangs" => '', "date_creation" => '', "date_modification" => '', "fk_price_expression" => '', "fk_unit" => '',
            "price_autogen" => '', "id" => '', "array_options" => '', "linkedObjectsIds" => '', "ref" => '', "ref_ext" => '',
            "barcode_type" => '', "barcode_type_code" => '', "recuperableonly" => '', "duration" => '', "width" => '', "width_units" => '',
            "height" => '', "height_units" => '', "entity" => '',
        ),
    );

    /**
     * Array of whitelist of properties keys to overwrite the white list of each element object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static protected $WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
     * Array of blacklist of properties keys to overwrite the blacklist of each element object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $BLACKLIST_OF_PROPERTIES = array();

    /**
     * Array of blacklist of properties keys to overwrite the blacklist of each element object when is a linked object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $BLACKLIST_OF_PROPERTIES_LINKED_OBJECT = array();

    /**
     * @var array   $BLACKWHITELIST_OF_PROPERTIES_LOADED      List of element type who is loaded
     */
    static protected $BLACKWHITELIST_OF_PROPERTIES_LOADED = array();

    /**
     *  Constructor
     */
    function __construct()
    {
        global $conf, $db, $langs, $user;

        $user = DolibarrApiAccess::$user;
        self::$db = $db;
        $langs->load('extendedintervention@extendedintervention');
        $langs->load('errors');
    }

    /**
     *  Get the survey
     *
     * @url	GET {id_intervention}/survey
     *
     * @param   int             $id_intervention    ID of the intervention
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  object|array                        Survey data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  403             RestException       Access not allowed for login
     * @throws  404             RestException       Intervention not found
     * @throws  500             RestException       Error when retrieve the survey
     */
    function getSurvey($id_intervention, $all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get survey
        if ($extendedintervention->fetch_survey($all_data) < 0) {
            throw new RestException(500, "Error when retrieve the survey", [ 'id_intervention' => $id_intervention, 'details' => [ $this->_getErrors($extendedintervention) ]]);
        }

        return $this->_cleanObjectData($extendedintervention->survey);
    }

    /**
     *  Save a survey
     *
     * @url	PUT {id_intervention}/survey
     *
     * @param   int     $id_intervention        ID of the intervention
     * @param   array   $survey                 Survey answers for this survey
     *
     * @return  object|array                    Survey data without useless information
     *
     * @throws  401     RestException           Insufficient rights
     * @throws  403     RestException           Access not allowed for login
     * @throws  404     RestException           Intervention not found
     * @throws  405     RestException           Read only
     * @throws  500     RestException           Error when retrieve intervention
     * @throws  500     RestException           Error while saving the survey
     */
    function saveSurvey($id_intervention, $survey=null)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Save answers
        if (is_array($survey) && count($survey) > 0) {
            foreach ($survey as $survey_bloc) {
                $this->saveSurveyBloc($id_intervention, $survey_bloc['fk_equipment'], $survey_bloc['survey']);
            }
        }

        // Get survey
        if ($extendedintervention->fetch_survey() < 0) {
            throw new RestException(500, "Error when retrieve the survey", [ 'id_intervention' => $id_intervention, 'details' => [ $this->_getErrors($extendedintervention) ]]);
        }

        return $this->_cleanObjectData($extendedintervention->survey);
    }

//    /**
//     *  Delete a survey
//     *
//     * @url	DELETE {id_intervention}/survey
//     *
//     * @param   int     $id_intervention    ID of the intervention
//     *
//     * @return  array
//     *
//     * @throws  401     RestException       Insufficient rights
//     * @throws  403     RestException       Access not allowed for login
//     * @throws  404     RestException       Intervention not found
//     * @throws  405     RestException       Read only
//     * @throws  500     RestException       Error when retrieve intervention
//     * @throws  500     RestException       Error while deleting the survey
//     */
//    function deleteSurvey($id_intervention)
//    {
//        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
//            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention ]);
//        }
//
//        // Get Extended Intervention
//        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);
//
//        // Get survey
//        if ($extendedintervention->fetch_survey(0) < 0) {
//            throw new RestException(500, "Error when retrieve the survey", [ 'id_intervention' => $id_intervention, 'details' => [ $this->_getErrors($extendedintervention) ]]);
//        }
//
//        // Delete survey
//        if (is_array($extendedintervention->survey) && count($extendedintervention->survey) > 0) {
//            foreach ($extendedintervention->survey as $equipment_id => $survey_bloc) {
//                $this->deleteSurveyBloc($id_intervention, $equipment_id);
//            }
//        }
//
//        return array(
//            'success' => array(
//                'code' => 200,
//                'message' => 'Survey deleted'
//            )
//        );
//    }

    /**
     *  Get a survey bloc
     *
     * @url	GET {id_intervention}/survey/{id_equipment}
     *
     * @param   int             $id_intervention    ID of the intervention
     * @param   int             $id_equipment       ID of the equipment
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  object|array                        Survey bloc data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  403             RestException       Access not allowed for login
     * @throws  404             RestException       Intervention not found
     * @throws  404             RestException       Survey bloc not found
     * @throws  500             RestException       Error when retrieve intervention
     * @throws  500             RestException       Error when retrieve survey bloc
     */
    function getSurveyBloc($id_intervention, $id_equipment, $all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get survey bloc
        $surveybloc = $this->_getSurveyBlocObject($id_intervention, $id_equipment, $all_data);
        $surveybloc->is_read_only();

        return $this->_cleanObjectData($surveybloc);
    }

    /**
     *  Save a survey bloc
     *
     * @url	PUT {id_intervention}/survey/{id_equipment}
     *
     * @param   int     $id_intervention        ID of the intervention
     * @param   int     $id_equipment           ID of the equipment
     * @param   array   $survey                 Survey answers for this survey bloc
     *
     * @return  object|array                    Survey bloc data without useless information
     *
     * @throws  401     RestException           Insufficient rights
     * @throws  403     RestException           Access not allowed for login
     * @throws  404     RestException           Intervention not found
     * @throws  404     RestException           Survey bloc not found
     * @throws  405     RestException           Read only
     * @throws  500     RestException           Error when retrieve intervention
     * @throws  500     RestException           Error when retrieve survey bloc
     * @throws  500     RestException           Error while saving the survey bloc
     */
    function saveSurveyBloc($id_intervention, $id_equipment, $survey=null)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get survey bloc
        $surveybloc = $this->_getSurveyBlocObject($id_intervention, $id_equipment);
        if ($surveybloc->is_read_only()) {
            throw new RestException(405, "Read only", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment ]);
        }

        // Save survey bloc
//        $surveybloc->oldcopy = clone $surveybloc; // Error 500
        $surveybloc->fk_fichinter = $id_intervention;
        $surveybloc->fk_equipment = $id_equipment;
        $surveybloc->fk_product = null;
        $surveybloc->equipment_ref = null;
        $surveybloc->product_ref = null;
        $surveybloc->product_label = null;

        if ($surveybloc->id > 0) {
            // Update
            $result = $surveybloc->update(DolibarrApiAccess::$user);
        } else {
            // Create
            $result = $surveybloc->create(DolibarrApiAccess::$user);
        }

        // Save answers
        if (is_array($survey) && count($survey) > 0) {
            foreach ($survey as $question_bloc) {
                $this->saveQuestionBloc($id_intervention, $id_equipment, $question_bloc['fk_c_question_bloc'], $question_bloc['complementary_question_bloc'],
                    $question_bloc['fk_c_question_bloc_status'], $question_bloc['justificatory_status'], $question_bloc['array_options'], $question_bloc['lines']);
            }
        }

        if ($result < 0) {
            throw new RestException(500, "Error while saving the survey bloc", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'details' => $this->_getErrors($surveybloc) ]);
        }

        return $this->getSurveyBloc($id_intervention, $id_equipment);
    }

//    /**
//     *  Delete a survey bloc
//     *
//     * @url	DELETE {id_intervention}/survey/{id_equipment}
//     *
//     * @param   int     $id_intervention    ID of the intervention
//     * @param   int     $id_equipment       ID of the equipment
//     *
//     * @return  array
//     *
//     * @throws  401     RestException       Insufficient rights
//     * @throws  403     RestException       Access not allowed for login
//     * @throws  404     RestException       Intervention not found
//     * @throws  404     RestException       Survey bloc not found
//     * @throws  405     RestException       Read only
//     * @throws  500     RestException       Error when retrieve intervention
//     * @throws  500     RestException       Error when retrieve survey bloc
//     * @throws  500     RestException       Error while deleting the survey bloc
//     */
//    function deleteSurveyBloc($id_intervention, $id_equipment)
//    {
//        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
//            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment ]);
//        }
//
//        // Get Extended Intervention
//        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);
//
//        // Get survey bloc
//        $surveybloc = $this->_getSurveyBlocObject($id_intervention, $id_equipment);
//
//        if ($surveybloc->is_read_only()) {
//            throw new RestException(405, "Read only", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment ]);
//        }
//
//        if ($surveybloc->delete(DolibarrApiAccess::$user) < 0) {
//            throw new RestException(500, "Error while deleting the survey bloc", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'details' => $this->_getErrors($surveybloc) ]);
//        }
//
//        return array(
//            'success' => array(
//                'code' => 200,
//                'message' => 'Survey bloc deleted'
//            )
//        );
//    }

    /**
	 *  Get the question blocs of the given survey bloc
	 *
     * @url	GET {id_intervention}/survey/{id_equipment}/questionblocs
     *
     * @param   int     $id_intervention    ID of the intervention
     * @param   int     $id_equipment       ID of the equipment
     * @param   int     $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
	 *
	 * @return  array                       List of question blocs
     *
     * @throws  401     RestException       Insufficient rights
     * @throws  403     RestException       Access not allowed for login
     * @throws  404     RestException       Intervention not found
     * @throws  404     RestException       Survey bloc not found
     * @throws  500     RestException       Error when retrieve intervention
     * @throws  500     RestException       Error when retrieve survey bloc
     * @throws  500     RestException       Error when retrieve question bloc
	 */
	function getQuestionBlocs($id_intervention, $id_equipment, $all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get survey bloc
        $surveybloc = $this->_getSurveyBlocObject($id_intervention, $id_equipment);

        if ($surveybloc->fetch_survey($all_data) < 0) {
            throw new RestException(500, "Error when retrieve the question blocs", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'details' => $this->_getErrors($surveybloc) ]);
        }

        $result = array();
        foreach ($surveybloc->survey as $questionbloc) {
            array_push($result, $this->_cleanObjectData($questionbloc));
        }

        return $result;
    }

    /**
     *  Get a question bloc
     *
     * @url	GET {id_intervention}/survey/{id_equipment}/questionblocs/{id_c_question_bloc}
     *
     * @param   int             $id_intervention    ID of the intervention
     * @param   int             $id_equipment       ID of the equipment
     * @param   int             $id_c_question_bloc ID of the question bloc (in dictionary)
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  object|array                        Question bloc data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  403             RestException       Access not allowed for login
     * @throws  404             RestException       Intervention not found
     * @throws  404             RestException       Question bloc not found
     * @throws  500             RestException       Error when retrieve intervention
     * @throws  500             RestException       Error when retrieve question bloc
     */
    function getQuestionBloc($id_intervention, $id_equipment, $id_c_question_bloc, $all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get question bloc
        $questionbloc = $this->_getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc, $all_data);
        $questionbloc->is_read_only();

        return $this->_cleanObjectData($questionbloc);
    }

    /**
     *  Save a question bloc
     *
     * @url	PUT {id_intervention}/survey/{id_equipment}/questionblocs/{id_c_question_bloc}
     *
     * @param   int     $id_intervention                ID of the intervention
     * @param   int     $id_equipment                   ID of the equipment
     * @param   int     $id_c_question_bloc             ID of the question bloc (in dictionary)
     * @param   string  $complementary_question_bloc    Complementary text
     * @param   int     $fk_c_question_bloc_status      ID of the status (in dictionary)
     * @param   string  $justificatory_status           Justificatory of the status
     * @param   array   $array_options                  Extra fields data
     * @param   array   $lines                          Question answers for this question bloc
     *
     * @return  object|array                            Question bloc data without useless information
     *
     * @throws  401     RestException                   Insufficient rights
     * @throws  403     RestException                   Access not allowed for login
     * @throws  404     RestException                   Intervention not found
     * @throws  404     RestException                   Question bloc not found
     * @throws  405     RestException                   Read only
     * @throws  500     RestException                   Error when retrieve intervention
     * @throws  500     RestException                   Error when retrieve question bloc
     * @throws  500     RestException                   Error while saving the question bloc
     */
    function saveQuestionBloc($id_intervention, $id_equipment, $id_c_question_bloc, $complementary_question_bloc = null, $fk_c_question_bloc_status = null, $justificatory_status = null, $array_options = null, $lines=null)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get survey bloc
        $surveybloc = $this->_getSurveyBlocObject($id_intervention, $id_equipment);

        // Get question bloc
        $questionbloc = $this->_getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc);
        if ($questionbloc->is_read_only()) {
            throw new RestException(405, "Read only", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc ]);
        }

        // Save question bloc
//        $questionbloc->oldcopy = clone $questionbloc; // Error 500
        $questionbloc->fk_survey_bloc = $surveybloc->id;
        $questionbloc->fk_c_question_bloc = $id_c_question_bloc;
        if (isset($complementary_question_bloc)) $questionbloc->complementary_question_bloc = $complementary_question_bloc;
        if (isset($fk_c_question_bloc_status)) $questionbloc->fk_c_question_bloc_status = $fk_c_question_bloc_status;
        if (isset($justificatory_status)) $questionbloc->justificatory_status = $justificatory_status;
        if (isset($array_options)) $questionbloc->array_options = $array_options;
        $questionbloc->position_question_bloc = null;
        $questionbloc->code_question_bloc = null;
        $questionbloc->label_question_bloc = null;
        $questionbloc->extrafields_question_bloc = null;
        $questionbloc->code_status = null;
        $questionbloc->label_status = null;
        $questionbloc->mandatory_status = null;

        if ($questionbloc->id > 0) {
            // Update
            $result = $questionbloc->update(DolibarrApiAccess::$user);
        } else {
            // Create
            $result = $questionbloc->create(DolibarrApiAccess::$user);
        }

        // Save answers
        if (is_array($lines) && count($lines) > 0) {
            foreach ($lines as $question) {
                $this->saveQuestion($id_intervention, $id_equipment, $id_c_question_bloc, $question['fk_c_question'],
                    $question['fk_c_answer'], $question['justificatory_answer'], $question['array_options']);
            }
        }

        if ($result < 0) {
            throw new RestException(500, "Error while saving the question bloc", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'details' => $this->_getErrors($questionbloc) ]);
        }

        return $this->getQuestionBloc($id_intervention, $id_equipment, $id_c_question_bloc);
    }

//    /**
//     *  Delete a question bloc
//     *
//     * @url	DELETE {id_intervention}/survey/{id_equipment}/questionblocs/{id_c_question_bloc}
//     *
//     * @param   int     $id_intervention    ID of the intervention
//     * @param   int     $id_equipment       ID of the equipment
//     * @param   int     $id_c_question_bloc ID of the question bloc (in dictionary)
//     *
//     * @return  array
//     *
//     * @throws  401     RestException       Insufficient rights
//     * @throws  403     RestException       Access not allowed for login
//     * @throws  404     RestException       Intervention not found
//     * @throws  404     RestException       Question bloc not found
//     * @throws  405     RestException       Read only
//     * @throws  500     RestException       Error when retrieve intervention
//     * @throws  500     RestException       Error when retrieve question bloc
//     * @throws  500     RestException       Error while deleting the question bloc
//     */
//    function deleteQuestionBloc($id_intervention, $id_equipment, $id_c_question_bloc)
//    {
//        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
//            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc ]);
//        }
//
//        // Get Extended Intervention
//        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);
//
//        // Get question bloc
//        $questionbloc = $this->_getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc);
//        if ($questionbloc->is_read_only()) {
//            throw new RestException(405, "Read only", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc ]);
//        }
//
//        if ($questionbloc->delete(DolibarrApiAccess::$user) < 0) {
//            throw new RestException(500, "Error while deleting the question bloc", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'details' => $this->_getErrors($questionbloc) ]);
//        }
//
//        return array(
//            'success' => array(
//                'code' => 200,
//                'message' => 'Question bloc deleted'
//            )
//        );
//    }

    /**
	 *  Get the questions of the given question bloc
	 *
     * @url	GET {id_intervention}/survey/{id_equipment}/questionblocs/{id_c_question_bloc}/questions
     *
     * @param   int     $id_intervention    ID of the intervention
     * @param   int     $id_equipment       ID of the equipment
     * @param   int     $id_c_question_bloc ID of the question bloc (in dictionary)
     * @param   int     $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
	 *
	 * @return  array                       List of questions
     *
     * @throws  401     RestException       Insufficient rights
     * @throws  403     RestException       Access not allowed for login
     * @throws  404     RestException       Intervention not found
     * @throws  404     RestException       Question bloc not found
     * @throws  500     RestException       Error when retrieve intervention
     * @throws  500     RestException       Error when retrieve question bloc
     * @throws  500     RestException       Error when retrieve the questions
	 */
	function getQuestions($id_intervention, $id_equipment, $id_c_question_bloc, $all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get question bloc
        $questionbloc = $this->_getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc);

        if ($questionbloc->getLinesArray($all_data) < 0) {
            throw new RestException(500, "Error when retrieve the questions", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'details' => $this->_getErrors($questionbloc) ]);
        }

        $result = array();
        foreach ($questionbloc->lines as $line) {
            array_push($result, $this->_cleanObjectData($line));
        }

        return $result;
    }

    /**
     *  Get a question
     *
     * @url	GET {id_intervention}/survey/{id_equipment}/questionblocs/{id_c_question_bloc}/questions/{id_c_question}
     *
     * @param   int             $id_intervention    ID of the intervention
     * @param   int             $id_equipment       ID of the equipment
     * @param   int             $id_c_question_bloc ID of the question bloc (in dictionary)
     * @param   int             $id_c_question      ID of the question (in dictionary)
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  object|array                        Request data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  403             RestException       Access unauthorized
     * @throws  404             RestException       Request not found
     * @throws  500             RestException       Error when retrieve request
     */
    function getQuestion($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question, $all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question ]);
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get Question bloc
        $questionbloc = $this->_getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc);

        // Get question
        $question = $this->_getQuestionObject($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question, $all_data);
        $question->is_read_only();

        return $this->_cleanObjectData($question);
    }

    /**
     *  Save a question
     *
     * @url	PUT {id_intervention}/survey/{id_equipment}/questionblocs/{id_c_question_bloc}/questions/{id_c_question}
     *
     * @param   int     $id_intervention        ID of the intervention
     * @param   int     $id_equipment           ID of the equipment
     * @param   int     $id_c_question_bloc     ID of the question bloc (in dictionary)
     * @param   int     $id_c_question          ID of the question (in dictionary)
     * @param   int     $fk_c_answer            ID of the answer (in dictionary)
     * @param   string  $justificatory_answer   Justificatory of the answer
     * @param   array   $array_options          Extra fields data
     *
     * @return  object|array                    Question data without useless information
     *
     * @throws  401     RestException           Insufficient rights
     * @throws  403     RestException           Access not allowed for login
     * @throws  404     RestException           Intervention not found
     * @throws  404     RestException           Question not found
     * @throws  405     RestException           Read only
     * @throws  500     RestException           Error when retrieve intervention
     * @throws  500     RestException           Error when retrieve question
     * @throws  500     RestException           Error while saving the question
     */
    function saveQuestion($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question, $fk_c_answer = null, $justificatory_answer = null, $array_options = null)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question ]);
        }

        // Save question bloc (create if not exist or do nothing)
        $this->saveQuestionBloc($id_intervention, $id_equipment, $id_c_question_bloc);

        // Get question bloc
        $questionbloc = $this->_getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc);

        // Get question
        $question = $this->_getQuestionObject($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question);
        if ($question->is_read_only()) {
            throw new RestException(405, "Read only", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question ]);
        }

        // Save question
//        $question->oldline = clone $question; // Error 500
        $question->fk_question_bloc = $questionbloc->id;
        $question->fk_c_question = $id_c_question;
        if (isset($fk_c_answer)) $question->fk_c_answer = $fk_c_answer;
        if (isset($justificatory_answer)) $question->text_answer = $justificatory_answer;
        if (isset($array_options)) $question->array_options = $array_options;
        $question->position_question = null;
        $question->code_question = null;
        $question->label_question = null;
        $question->extrafields_question = null;
        $question->code_answer = null;
        $question->label_answer = null;
        $question->mandatory_answer = null;

        if ($question->id > 0) {
            // Update
            $result = $question->update(DolibarrApiAccess::$user);
        } else {
            // Create
            $result = $question->insert(DolibarrApiAccess::$user);
        }

        if ($result < 0) {
            throw new RestException(500, "Error while saving the question", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question, 'details' => $this->_getErrors($question) ]);
        }

        self::$db->commit();

        return $this->getQuestion($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question);
    }

//    /**
//     *  Delete a question
//     *
//     * @url	DELETE {id_intervention}/survey/{id_equipment}/questionblocs/{id_c_question_bloc}/questions/{id_c_question}
//     *
//     * @param   int     $id_intervention    ID of the intervention
//     * @param   int     $id_equipment       ID of the equipment
//     * @param   int     $id_c_question_bloc ID of the question bloc (in dictionary)
//     * @param   int     $id_c_question      ID of the question (in dictionary)
//     *
//     * @return  array
//     *
//     * @throws  401     RestException       Insufficient rights
//     * @throws  403     RestException       Access not allowed for login
//     * @throws  404     RestException       Intervention not found
//     * @throws  404     RestException       Question bloc not found
//     * @throws  404     RestException       Question not found
//     * @throws  405     RestException       Read only
//     * @throws  500     RestException       Error when retrieve intervention
//     * @throws  500     RestException       Error when retrieve question bloc
//     * @throws  500     RestException       Error when retrieve question
//     * @throws  500     RestException       Error while deleting the question
//     */
//    function deleteQuestion($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question)
//    {
//        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
//            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question ]);
//        }
//
//        // Get Extended Intervention
//        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);
//
//        // Get Question bloc
//        $questionbloc = $this->_getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc);
//
//        // Get question
//        $question = $this->_getQuestionObject($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question);
//        if ($question->is_read_only()) {
//            throw new RestException(405, "Read only", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question ]);
//        }
//
//        if ($question->delete(DolibarrApiAccess::$user) < 0) {
//            throw new RestException(500, "Error while deleting the question", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question, 'details' => $this->_getErrors($question) ]);
//        }
//
//        return array(
//            'success' => array(
//                'code' => 200,
//                'message' => 'Question deleted'
//            )
//        );
//    }

    /**
     *  Get Extended Intervention object with authorization
     *
     * @param   int             $id_intervention    Id of the intervention
     *
     * @return  ExtendedIntervention
     *
     * @throws  403             RestException       Access not allowed for login
     * @throws  404             RestException       Intervention not found
     * @throws  500             RestException       Error when retrieve intervention
     */
    function _getExtendedInterventionObject($id_intervention)
    {
        global $conf;

        if (!DolibarrApi::_checkAccessToResource('fichinter', $id_intervention, 'fichinter')) {
            throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login, [ 'id_intervention' => $id_intervention ]);
        }

        $extendedintervention = new ExtendedIntervention(self::$db);
        $result = $extendedintervention->fetch($id_intervention);
        if ($result == 0) {
            throw new RestException(404, "Intervention not found", [ 'id_intervention' => $id_intervention ]);
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve intervention", [ 'id_intervention' => $id_intervention, 'details' => $this->_getErrors($extendedintervention) ]);
        }

        if ($conf->companyrelationships->enabled) {
            $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($extendedintervention);
            if (!$hasPerm) {
                throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login, [ 'id_intervention' => $id_intervention ]);
            }
        }

        return $extendedintervention;
    }

    /**
     *  Get Survey Bloc object with authorization
     *
     * @param   int             $id_intervention    Id of the intervention
     * @param   int             $id_equipment       Id of the equipment
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  EISurveyBloc
     *
     * @throws  404             RestException       Survey bloc not found
     * @throws  500             RestException       Error when retrieve survey bloc
     */
    function _getSurveyBlocObject($id_intervention, $id_equipment, $all_data=0)
    {
        $surveybloc = new EISurveyBloc(self::$db);
        $result = $surveybloc->fetch(0, $id_intervention, $id_equipment, $all_data);
        if ($result == 0) {
            throw new RestException(404, "Survey bloc not found", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment ]);
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve survey bloc", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'details' => $this->_getErrors($surveybloc) ]);
        }

//        $surveybloc->fetch_fichinter();
//        $surveybloc->fetch_equipment();
//        $surveybloc->fetch_product();

        return $surveybloc;
    }

    /**
     *  Get Question Bloc object with authorization
     *
     * @param   int             $id_intervention    ID of the intervention
     * @param   int             $id_equipment       Id of the equipment
     * @param   int             $id_c_question_bloc ID of the question bloc (in dictionary)
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  EIQuestionBloc
     *
     * @throws  404             RestException       Question bloc not found
     * @throws  500             RestException       Error when retrieve question bloc
     */
    function _getQuestionBlocObject($id_intervention, $id_equipment, $id_c_question_bloc, $all_data=0)
    {
        $questionbloc = new EIQuestionBloc(self::$db);
        $result = $questionbloc->fetch(0, $id_intervention, $id_equipment, null, $id_c_question_bloc, $all_data);
        if ($result == 0) {
            throw new RestException(404, "Question bloc not found", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc ]);
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve question bloc", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'details' => $this->_getErrors($questionbloc) ]);
        }

        return $questionbloc;
    }

    /**
     *  Get Question object with authorization
     *
     * @param   int             $id_intervention    ID of the intervention
     * @param   int             $id_equipment       Id of the equipment
     * @param   int             $id_c_question_bloc ID of the question bloc (in dictionary)
     * @param   int             $id_c_question      ID of the question (in dictionary)
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  EIQuestionBlocLine
     *
     * @throws  404             RestException       Question not found
     * @throws  500             RestException       Error when retrieve question
     */
    function _getQuestionObject($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question, $all_data=0)
    {
        $question = new EIQuestionBlocLine(self::$db);
        $result = $question->fetch(0, $id_intervention, $id_equipment, $id_c_question_bloc, null, $id_c_question, $all_data);
        if ($result == 0) {
            throw new RestException(404, "Question not found", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question ]);
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve question", [ 'id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question, 'details' => $this->_getErrors($question) ]);
        }

        return $question;
    }

    /**
     * Check perms for user with public space availability
     * Copied from '/companyrelationships/class/api_companyrelationshipsapi.class.php'
     *
     * @param   Object      $object         Object (propal, commande, invoice, fichinter)
     * @return  bool        FALSE to deny user access, TRUE to authorize
     * @throws  Exception
     */
    private function _checkUserPublicSpaceAvailabilityPermOnObject($object)
    {
        global $conf;

        $hasPerm = FALSE;

        // get API user
        $user = DolibarrApiAccess::$user;
        $userSocId = $user->societe_id;

        // If external user: Check permission for external users
        if ($userSocId > 0) {
            // search customers of this external user
            $search_sale = 0;
            if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

            $sql  = "SELECT t.rowid";
            $sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as t";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef ON ef.fk_object = t.rowid";

            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scp ON scp.fk_soc = t.fk_soc AND scp.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scb ON scb.fk_soc = ef.companyrelationships_fk_soc_benefactor AND scb.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scw ON scw.fk_soc = ef.companyrelationships_fk_soc_watcher AND scw.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale

            // search principal company
            $sqlPrincipal  = "(";
            $sqlPrincipal .= "(t.fk_soc = " . $userSocId;
            if ($search_sale > 0) {
                $sqlPrincipal .= " OR scp.fk_user = " . $search_sale;
            }
            $sqlPrincipal .= ")";
            $sqlPrincipal .= " AND ef.companyrelationships_availability_principal = 1";
            $sqlPrincipal .= ")";

            // search benefactor company
            $sqlBenefactor  = "(";
            $sqlBenefactor .= "(ef.companyrelationships_fk_soc_benefactor = " . $userSocId;
            if ($search_sale > 0) {
                $sqlBenefactor .= " OR scb.fk_user = " . $search_sale;
            }
            $sqlBenefactor .= ")";
            $sqlBenefactor .= " AND ef.companyrelationships_availability_benefactor = 1";
            $sqlBenefactor .= ")";

            // search watcher company
            $sqlWatcher = "(";
            $sqlWatcher .= "(ef.companyrelationships_fk_soc_watcher = " . $userSocId;
            if ($search_sale > 0) {
                $sqlWatcher .= " OR scw.fk_user = " . $search_sale;
            }
            $sqlWatcher .= ")";
            $sqlWatcher .= " AND ef.companyrelationships_availability_watcher = 1";
            $sqlWatcher .= ")";

            $sql .= " WHERE t.rowid = " . $object->id;
            $sql .= " AND t.entity IN (" . getEntity($object->table_element) . ")";
            $sql .= " AND (". $sqlPrincipal . " OR " . $sqlBenefactor . " OR " . $sqlWatcher . ")";

            $resql = self::$db->query($sql);
            if ($resql) {
                $nbResult = self::$db->num_rows($resql);
                if ($nbResult > 0) {
                    $hasPerm = TRUE;
                }
            }
        }
        // If internal user: Check permission for internal users that are restricted on their objects
        else if (! empty($conf->societe->enabled) && ($user->rights->societe->lire && ! $user->rights->societe->client->voir)) {
            $hasPerm = TRUE;

            $sql  = "SELECT COUNT(sc.fk_soc) as nb";
            $sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as dbt";
            $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
            $sql .= " WHERE dbt.rowid = " . $object->id;
            $sql .= " AND sc.fk_soc = dbt.fk_soc";
            $sql .= " AND dbt.fk_soc = s.rowid";
            $sql .= " AND dbt.entity IN (" . getEntity($object->table_element, 1) . ")";
            $sql .= " AND sc.fk_user = " . $user->id;

            $resql = self::$db->query($sql);
            if ($resql) {
                $obj = self::$db->fetch_object($resql);
                if (! $obj || $obj->nb < count(explode(',', $object->id))) $hasPerm = FALSE;
            } else {
                $hasPerm = FALSE;
            }
        }
        // If multicompany and internal users with all permissions, check user is in correct entity
        else if (! empty($conf->multicompany->enabled)) {
            $hasPerm = TRUE;

            $sql = "SELECT COUNT(dbt.fk_soc) as nb";
            $sql.= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as dbt";
            $sql.= " WHERE dbt.rowid = " . $object->id;
            $sql.= " AND dbt.entity IN (". getEntity($object->table_element, 1) . ")";

            $resql = self::$db->query($sql);
            if ($resql) {
                $obj = self::$db->fetch_object($resql);
                if (! $obj || $obj->nb < count(explode(',', $object->id))) $hasPerm = FALSE;
            } else {
                $hasPerm = FALSE;
            }
        }

        return $hasPerm;
    }

    /*******************************************************************************************************************
     * Tools functions
     ******************************************************************************************************************/

    /**
     *  Clean sensible object data
     *
     * @param   object|array    $object                     Object to clean
     * @param   array           $whitelist_of_properties    Whitelist of properties
     * @param   array           $blacklist_of_properties    Blacklist of properties
     *
     * @return  object|array                                Array of cleaned object properties
     *
     * @throws  500             RestException               Error while retrieve the custom whitelist of properties for the object type
     */
	function _cleanObjectData(&$object, $whitelist_of_properties=array(), $blacklist_of_properties=array())
    {
        if (!empty($object->element)) {
            $this->_getBlackWhitelistOfProperties($object, $whitelist_of_properties, $blacklist_of_properties);
        }

        if (!is_array($whitelist_of_properties)) $whitelist_of_properties = array();
        $has_whitelist = count($whitelist_of_properties) > 0 && !isset($whitelist_of_properties['']);
        $has_sub_whitelist = count($whitelist_of_properties) == 1 && isset($whitelist_of_properties['']);
        if (!is_array($blacklist_of_properties)) $blacklist_of_properties = array();
        $has_blacklist = count($blacklist_of_properties) > 0 && !isset($blacklist_of_properties['']);
        $has_sub_blacklist = count($blacklist_of_properties) == 1 && isset($blacklist_of_properties['']);
        foreach ($object as $k => $v) {
            if (($has_whitelist && !isset($whitelist_of_properties[$k])) || ($has_blacklist && isset($blacklist_of_properties[$k]) && !is_array($blacklist_of_properties[$k]))) {
                if (is_array($object))
                    unset($object[$k]);
                else
                    unset($object->$k);
            } else {
                if (is_object($v) || is_array($v)) {
                    if (is_array($object))
                        $this->_cleanSubObjectData($object[$k], $has_sub_whitelist ? $whitelist_of_properties[''] : $whitelist_of_properties[$k], $has_sub_blacklist ? $blacklist_of_properties[''] : $blacklist_of_properties[$k]);
                    else
                        $this->_cleanSubObjectData($object->$k, $has_sub_whitelist ? $whitelist_of_properties[''] : $whitelist_of_properties[$k], $has_sub_blacklist ? $blacklist_of_properties[''] : $blacklist_of_properties[$k]);
                }
            }
        }

        return $object;
    }

    /**
     *  Clean sensible linked object data
     *
     * @param   object|array    $object                     Object to clean
     * @param   array           $whitelist_of_properties    Whitelist of properties
     * @param   array           $blacklist_of_properties    Blacklist of properties
     *
     * @return  object|array                                Array of cleaned object properties
     *
     * @throws  500             RestException               Error while retrieve the custom whitelist of properties for the object type
     */
	function _cleanSubObjectData(&$object, $whitelist_of_properties=array(), $blacklist_of_properties=array())
    {
        if (!empty($object->element)) {
            $this->_getBlackWhitelistOfProperties($object, $whitelist_of_properties, $blacklist_of_properties, true);
        }

        if (!is_array($whitelist_of_properties)) $whitelist_of_properties = array();
        $has_whitelist = count($whitelist_of_properties) > 0 && !isset($whitelist_of_properties['']);
        $has_sub_whitelist = count($whitelist_of_properties) == 1 && isset($whitelist_of_properties['']);
        if (!is_array($blacklist_of_properties)) $blacklist_of_properties = array();
        $has_blacklist = count($blacklist_of_properties) > 0 && !isset($blacklist_of_properties['']);
        $has_sub_blacklist = count($blacklist_of_properties) == 1 && isset($blacklist_of_properties['']);
        foreach ($object as $k => $v) {
            if (($has_whitelist && !isset($whitelist_of_properties[$k])) || ($has_blacklist && isset($blacklist_of_properties[$k]) && !is_array($blacklist_of_properties[$k]))) {
                if (is_array($object))
                    unset($object[$k]);
                else
                    unset($object->$k);
            } else {
                if (is_object($v) || is_array($v)) {
                    if (is_array($object))
                        $this->_cleanSubObjectData($object[$k], $has_sub_whitelist ? $whitelist_of_properties[''] : $whitelist_of_properties[$k], $has_sub_blacklist ? $blacklist_of_properties[''] : $blacklist_of_properties[$k]);
                    else
                        $this->_cleanSubObjectData($object->$k, $has_sub_whitelist ? $whitelist_of_properties[''] : $whitelist_of_properties[$k], $has_sub_blacklist ? $blacklist_of_properties[''] : $blacklist_of_properties[$k]);
                }
            }
        }

        return $object;
    }

    /**
     *  Get a array of whitelist of properties keys for this object or linked object
     *
     * @param   object      $object                     Object to clean
     * @param   boolean     $linked_object              This object is a linked object
     * @param   array       $whitelist_of_properties    Array of whitelist of properties keys for this object
     *                                                      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *                                                      if property is a object and this properties_name value is equal '' then get whitelist of his object element
     *                                                      if property is a object and this properties_name value is a array then get whitelist set in the array
     *                                                      if property is a array and this properties_name value is equal '' then get all values
     *                                                      if property is a array and this properties_name value is a array then get whitelist set in the array
     * @param   array       $blacklist_of_properties    Array of blacklist of properties keys for this object
     *                                                      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *                                                      if property is a object and this properties_name value is equal '' then get blacklist of his object element
     *                                                      if property is a object and this properties_name value is a array then get blacklist set in the array
     *                                                      if property is a array and this properties_name value is equal '' then get all values
     *                                                      if property is a array and this properties_name value is a array then get blacklist set in the array
     *
     * @return void
     *
     * @throws  500         RestException       Error while retrieve the custom whitelist of properties for the object type
     */
	function _getBlackWhitelistOfProperties($object, &$whitelist_of_properties, &$blacklist_of_properties, $linked_object=false)
    {
        global $hookmanager;

        $whitelist_of_properties = array();
        $whitelist_of_properties_linked_object = array();
        $blacklist_of_properties = array();
        $blacklist_of_properties_linked_object = array();

        if (!empty($object->element)) {
            // Load white list for clean sensitive properties of the objects
            if (!isset(self::$BLACKWHITELIST_OF_PROPERTIES_LOADED[$object->element])) {
                $object_class = get_class($object);

                // Whitelist
                if (!empty(self::$WHITELIST_OF_PROPERTIES[$object->element]))
                    $whitelist_of_properties = self::$WHITELIST_OF_PROPERTIES[$object->element];
                elseif (!empty($object_class::$API_WHITELIST_OF_PROPERTIES))
                    $whitelist_of_properties = $object_class::$API_WHITELIST_OF_PROPERTIES;

                if (!empty(self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element]))
                    $whitelist_of_properties_linked_object = self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                elseif (!empty($object_class::$API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT))
                    $whitelist_of_properties_linked_object = $object_class::$API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT;

                // Blacklist
                if (!empty(self::$BLACKLIST_OF_PROPERTIES[$object->element]))
                    $blacklist_of_properties = self::$BLACKLIST_OF_PROPERTIES[$object->element];
                elseif (!empty($object_class::$API_BLACKLIST_OF_PROPERTIES))
                    $blacklist_of_properties = $object_class::$API_BLACKLIST_OF_PROPERTIES;

                if (!empty(self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element]))
                    $blacklist_of_properties_linked_object = self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                elseif (!empty($object_class::$API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT))
                    $blacklist_of_properties_linked_object = $object_class::$API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT;

                // Modification by hook
                $hookmanager->initHooks(array('companyrelationshipsapi', 'globalapi'));
                $parameters = array('whitelist_of_properties' => &$whitelist_of_properties, 'whitelist_of_properties_linked_object' => &$whitelist_of_properties_linked_object,
                    'blacklist_of_properties' => &$blacklist_of_properties, 'blacklist_of_properties_linked_object' => &$blacklist_of_properties_linked_object);
                $reshook = $hookmanager->executeHooks('getBlackWhitelistOfProperties', $parameters, $object); // Note that $action and $object may have been
                if ($reshook < 0) {
                    throw new RestException(500, "Error while retrieve the custom blacklist and whitelist of properties for the object type: " . $object->element, ['details' => $this->_getErrors($hookmanager)]);
                }

                if (empty($whitelist_of_properties_linked_object)) $whitelist_of_properties_linked_object = $whitelist_of_properties;
                if (empty($blacklist_of_properties_linked_object)) $blacklist_of_properties_linked_object = $blacklist_of_properties;

                self::$WHITELIST_OF_PROPERTIES[$object->element] = $whitelist_of_properties;
                self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element] = $whitelist_of_properties_linked_object;
                self::$BLACKLIST_OF_PROPERTIES[$object->element] = $blacklist_of_properties;
                self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element] = $blacklist_of_properties_linked_object;

                self::$BLACKWHITELIST_OF_PROPERTIES_LOADED[$object->element] = true;
            }
            // Get white list
            elseif (isset(self::$WHITELIST_OF_PROPERTIES[$object->element])) {
                $whitelist_of_properties = self::$WHITELIST_OF_PROPERTIES[$object->element];
                $whitelist_of_properties_linked_object = self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                if (empty($whitelist_of_properties_linked_object)) $whitelist_of_properties_linked_object = $whitelist_of_properties;

                $blacklist_of_properties = self::$BLACKLIST_OF_PROPERTIES[$object->element];
                $blacklist_of_properties_linked_object = self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                if (empty($blacklist_of_properties_linked_object)) $blacklist_of_properties_linked_object = $blacklist_of_properties;
            }
        }

        $whitelist_of_properties = $linked_object ? $whitelist_of_properties_linked_object : $whitelist_of_properties;
        $blacklist_of_properties = $linked_object ? $blacklist_of_properties_linked_object : $blacklist_of_properties;
    }

    /**
     * Get all errors
     *
     * @param  object   $object     Object
     *
     * @return array                Array of errors
     */
	function _getErrors(&$object)
    {
        $errors = is_array($object->errors) ? $object->errors : array();
        $errors = array_merge($errors, (!empty($object->error) ? array($object->error) : array()));

        function convert($item)
        {
            $item = dol_htmlentitiesbr_decode($item);
            return dol_html_entity_decode($item, ENT_QUOTES);
        }

        $errors = array_map('convert', $errors);

        return $errors;
    }
}
