<?php
/* Copyright (C) 2019	Open-DSI	        <support@open-dsi.fr>
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
     * @var DoliDb      self::$db         Database object
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
     *  Get properties of an intervention object
     *  Return an array with intervention information
     * @see in CompanyRelationshipsApi class
     *
     * @url	GET {id_intervention}
     *
     * @param   int             $id_intervention    ID of the intervention
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     *
     * @return  object|array                        Intervention data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  403             RestException       Access not allowed for login
     * @throws  404             RestException       Intervention not found
     * @throws  500             RestException       Error when retrieve the survey
     */
    function get($id_intervention, $all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Get survey
        if ($extendedintervention->fetch_survey($all_data) < 0) {
            throw new RestException(500, "Error when retrieve the survey", ['id_intervention' => $id_intervention, 'details' => [$this->_getErrors($extendedintervention)]]);
        }

        $extendedintervention->fetchObjectLinked();
        $extendedintervention->fetch_thirdparty();
        foreach ($extendedintervention->lines as $k => $line) {
            $extendedintervention->lines[$k]->fetch_optionals();
        }
        $this->_fetch_benefactor($extendedintervention);

        return $this->_cleanObjectData($extendedintervention);
    }

    /**
     *  List of interventions
     *  Return a list of interventions
     * @see in CompanyRelationshipsApi class
     *
     * @param   string          $sortfield          Sort field
     * @param   string          $sortorder          Sort order
     * @param   int             $limit		        Limit for list
     * @param   int             $page		        Page number
     * @param   string          $thirdparty_ids     Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string          $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @param   int             $all_data           If equal 1 then get all the data for the modification (all answers, status and predefined texts)
     * @return  array           Array of order objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve intervention list
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $all_data=0)
    {
        global $conf;

        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (!DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "fichinter as t";

        // external
        if ($userSocId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "fichinter_extrafields as ef ON ef.fk_object = t.rowid";
            $sql .= self::_sqlElementListForExternalUser($userSocId, $search_sale);
            $sql .= " AND t.entity IN (" . getEntity('fichinter') . ")";
            // Add sql filters
            if ($sqlfilters) {
                if (!DolibarrApi::_checkFilters($sqlfilters)) {
                    throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
                }
                $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
                $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
            }
        } // internal
        else {
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

            $sql .= ' WHERE (t.entity IN (' . getEntity('intervention') . ')';

            // Add sql filters
            if ($sqlfilters) {
                if (!DolibarrApi::_checkFilters($sqlfilters)) {
                    throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
                }
                $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
                $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
            }

            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";
            if ($search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";        // Join for the needed table to filter by sale
            // Insert sale filter
            if ($search_sale > 0) {
                $sql .= " AND sc.fk_user = " . $search_sale;
            }
            $sql .= ")";

            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql .= ' OR (t.entity IN (' . getEntity('intervention') . ')';
                $sql .= " AND t.fk_soc IN (" . $socids . "))";
            }
            $sql .= " GROUP BY rowid";
        }

        $sql .= self::$db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= self::$db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = self::$db->query($sql);

        if ($result) {
            $num = self::$db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min) {
                $obj = self::$db->fetch_object($result);
                $extendedintervention = new ExtendedIntervention(self::$db);
                if ($extendedintervention->fetch($obj->rowid)) {
                    $extendedintervention->fetchObjectLinked();
                    $extendedintervention->fetch_thirdparty();
                    foreach ($extendedintervention->lines as $k => $line) {
                        $extendedintervention->lines[$k]->fetch_optionals();
                    }
                    $this->_fetch_benefactor($extendedintervention);
                    // Get survey
                    if ($extendedintervention->fetch_survey($all_data) < 0) {
                        throw new RestException(500, "Error when retrieve the survey", ['id_intervention' => $obj->rowid, 'details' => [$this->_getErrors($extendedintervention)]]);
                    }
                    $obj_ret[] = $this->_cleanObjectData($extendedintervention);
                }
                $i++;
            }
        } else {
            throw new RestException(503, 'Error when retrieve fichinter list : ' . self::$db->lasterror());
        }
        if (!count($obj_ret)) {
            return $obj_ret;
        }

        return $obj_ret;
    }

    /**
     *  Prepare SQL request for element list (propal, commande, invoice, fichinter, contract) for external user
     * @see in CompanyRelationshipsApi class
     *
     * @param       int     $userSocId      Id of user company (external user)
     * @param       int     $search_sale    Id of commercial user
     * @return      string  SQL request
     */
    private static function _sqlElementListForExternalUser($userSocId, $search_sale=0)
    {
        $sql = '';

        if ($search_sale > 0) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scp ON scp.fk_soc = t.fk_soc AND scp.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
        if ($search_sale > 0) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scb ON scb.fk_soc = ef.companyrelationships_fk_soc_benefactor AND scb.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
        if ($search_sale > 0) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scw ON scw.fk_soc = ef.companyrelationships_fk_soc_watcher AND scw.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale

        // search principal company
        $sqlPrincipal = "(";
        $sqlPrincipal .= "(t.fk_soc = " . $userSocId;
        if ($search_sale > 0) {
            $sqlPrincipal .= " OR scp.fk_user = " . $search_sale;
        }
        $sqlPrincipal .= ")";
        $sqlPrincipal .= " AND ef.companyrelationships_availability_principal = 1";
        $sqlPrincipal .= ")";

        // search benefactor company
        $sqlBenefactor = "(";
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

        $sql .= " WHERE (" . $sqlPrincipal . " OR " . $sqlBenefactor . " OR " . $sqlWatcher . ")";

        return $sql;
    }

    /**
     *  Fetch all benefactor of the object
     * @see in CompanyRelationshipsApi class
     *
     * @param   object          $object         Object to fetch
     *
     * @return  void
     **/
    function _fetch_benefactor(&$object)
    {
        $object->cr_thirdparty_benefactor = null;

        if (!isset($object->array_options['options_companyrelationships_fk_soc_benefactor'])) {
            $object->fetch_optionals();
        }

        if ($object->array_options['options_companyrelationships_fk_soc_benefactor'] > 0) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $societe = new Societe(self::$db);
            if ($societe->fetch($object->array_options['options_companyrelationships_fk_soc_benefactor']) > 0) {
                $object->cr_thirdparty_benefactor = $societe;
            }
        }
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
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
	 * @param	int		$all_data				If equal 1 then get all the data for the modification (all answers, status and predefined texts)
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
    function saveSurvey($id_intervention, $survey=null,$all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->creer) {
            throw new RestException(401, "Insufficient rights", [ 'id_intervention' => $id_intervention ]);
        }

        // Save survey
        $this->_saveSurvey($id_intervention, $survey, true);

        return $this->getSurvey($id_intervention,$all_data);
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
//        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
	 * @param	int		$all_data				If equal 1 then get all the data for the modification (all answers, status and predefined texts)
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
    function saveSurveyBloc($id_intervention, $id_equipment, $survey=null,$all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->creer) {
            throw new RestException(401, "Insufficient rights", ['id_intervention' => $id_intervention, 'id_equipment' => $id_equipment]);
        }

        // Reformat survey array
        $survey = array($id_equipment => $survey);

        // Save survey
        $this->_saveSurvey($id_intervention, $survey);

        return $this->getSurveyBloc($id_intervention, $id_equipment,$all_data);
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
//        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
            $result[]=$this->_cleanObjectData($questionbloc);
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
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
     * @param   array   $attached_files                 List of filename attached for this question bloc
	 * @param	int		$all_data						If equal 1 then get all the data for the modification (all answers, status and predefined texts)
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
    function saveQuestionBloc($id_intervention, $id_equipment, $id_c_question_bloc, $complementary_question_bloc = null, $fk_c_question_bloc_status = null, $justificatory_status = null, $array_options = null, $lines=null, $attached_files=null,$all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->creer) {
            throw new RestException(401, "Insufficient rights", ['id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc]);
        }

        // Reformat survey array
        $survey = array(
            $id_equipment => array(
                'survey' => array(
                    0 => array(
                        'fk_c_question_bloc' => $id_c_question_bloc,
                        'complementary_question_bloc' => $complementary_question_bloc,
                        'fk_c_question_bloc_status' => $fk_c_question_bloc_status,
                        'justificatory_status' => $justificatory_status,
                        'array_options' => $array_options,
                        'lines' => $lines,
                        'attached_files' => $attached_files,
                    )
                )
            )
        );

        // Save survey
        $this->_saveSurvey($id_intervention, $survey);

        return $this->getQuestionBloc($id_intervention, $id_equipment, $id_c_question_bloc,$all_data);
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
//        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
            $result[]=$this->_cleanObjectData($line);
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
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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
     * @param   string  $text_answer            Justificatory of the answer
     * @param   array   $array_options          Extra fields data
	 * @param	int		$all_data				If equal 1 then get all the data for the modification (all answers, status and predefined texts)
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
    function saveQuestion($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question, $fk_c_answer = null, $text_answer = null, $array_options = null,$all_data=0)
    {
        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->creer) {
            throw new RestException(401, "Insufficient rights", ['id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'id_c_question_bloc' => $id_c_question_bloc, 'id_c_question' => $id_c_question]);
        }

        // Reformat survey array
        $survey = array(
            $id_equipment => array(
                $id_c_question_bloc => array(
                    'lines' => array(
                        0 => array(
                            'fk_c_question' => $id_c_question,
                            'fk_c_answer' => $fk_c_answer,
                            'text_answer' => $text_answer,
                            'array_options' => $array_options,
                        )
                    )
                )
            )
        );

        // Save survey
        $this->_saveSurvey($id_intervention, $survey);

        return $this->getQuestion($id_intervention, $id_equipment, $id_c_question_bloc, $id_c_question,$all_data=0);
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
//        if (!DolibarrApiAccess::$user->rights->extendedintervention->questionnaireIntervention->lire) {
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

        $extendedintervention = new ExtendedIntervention(self::$db);
        $result = $extendedintervention->fetch($id_intervention);
        if ($result == 0) {
            throw new RestException(404, "Intervention not found", ['id_intervention' => $id_intervention]);
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve intervention", ['id_intervention' => $id_intervention, 'details' => $this->_getErrors($extendedintervention)]);
        }

        if ($conf->companyrelationships->enabled) {
            $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($extendedintervention);
            if (!$hasPerm) {
                throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login, ['id_intervention' => $id_intervention]);
            }
        } else {
            if (!DolibarrApi::_checkAccessToResource('fichinter', $id_intervention, 'fichinter')) {
                throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login, ['id_intervention' => $id_intervention]);
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
     *  Save the survey into the database
     *
     * @param   int     $id_intervention        ID of the intervention
     * @param   array   $survey                 Survey answers for this survey
     * @param   bool    $delete_question_bloc   Delete question bloc who is deletable and is no longer present
     *
     * @return  void
     *
     * @throws  401     RestException           Insufficient rights
     * @throws  403     RestException           Access not allowed for login
     * @throws  404     RestException           Intervention not found
     * @throws  405     RestException           Read only
     * @throws  500     RestException           Error when retrieve intervention
     * @throws  500     RestException           Error while saving the survey
     */
    function _saveSurvey($id_intervention, $survey=null, $delete_question_bloc=false)
    {
        self::$db->begin();

        // Get Extended Intervention
        $extendedintervention = $this->_getExtendedInterventionObject($id_intervention);

        // Test if is read only
        if ($extendedintervention->is_survey_read_only()) {
            self::$db->rollback();
            throw new RestException(405, "Read only", ['id_intervention' => $id_intervention]);
        }

        // Save survey
        //-------------------------
        if (is_array($survey) && count($survey) > 0) {
            // Get survey
            if ($extendedintervention->fetch_survey(1) < 0) {
                self::$db->rollback();
                throw new RestException(500, "Error when retrieve the survey with all info", [ 'id_intervention' => $id_intervention, 'details' => [ $this->_getErrors($extendedintervention) ]]);
            }
            $current_survey = $extendedintervention->survey;


			//Delete empty survey bloc

			if ($delete_question_bloc) {
                    foreach ($current_survey as $id_equipment => $survey_bloc) {
                        if (!isset($survey[$id_equipment])) {
                            if ($survey_bloc->delete(DolibarrApiAccess::$user) < 0) {
                                self::$db->rollback();
                                throw new RestException(500, "Error while deleting the equipement question bloc", ['id_intervention' => $id_intervention, 'id_equipment' => $id_equipment, 'details' => $this->_getErrors($qb)]);
                            }
                        }
                    }
                }


			// Save survey bloc
            //-------------------------
            foreach ($survey as $id_equipment => $survey_bloc) {
                $current_equipment_id = isset($survey_bloc['fk_equipment']) ? $survey_bloc['fk_equipment'] : $id_equipment;
                // Get handler of the survey bloc (if exist in the current survey) or create it
                $current_survey_bloc = isset($current_survey[$current_equipment_id]) ? $current_survey[$current_equipment_id] : new EISurveyBloc(self::$db, $extendedintervention);

                // Test if is read only
//                if ($current_survey_bloc->read_only) {
//                    self::$db->rollback();
//                    throw new RestException(405, "Read only", ['id_intervention' => $id_intervention, 'id_equipment' => $current_equipment_id]);
//                }

                // Set Values
                $current_survey_bloc->fk_fichinter = $id_intervention;
                $current_survey_bloc->fk_equipment = $current_equipment_id;
                $current_survey_bloc->fk_product = isset($survey_bloc['fk_product']) ? $survey_bloc['fk_product'] : null;
                $current_survey_bloc->equipment_ref = isset($survey_bloc['equipment_ref']) ? $survey_bloc['equipment_ref'] : null;
                $current_survey_bloc->product_ref = isset($survey_bloc['product_ref']) ? $survey_bloc['product_ref'] : null;
                $current_survey_bloc->product_label = isset($survey_bloc['product_label']) ? $survey_bloc['product_label'] : null;

                if ($current_survey_bloc->id > 0) {
                    // Update
                    $result = $current_survey_bloc->update(DolibarrApiAccess::$user);
                } else {
                    // Create
                    $result = $current_survey_bloc->create(DolibarrApiAccess::$user);
                }

                if ($result < 0) {
                    self::$db->rollback();
                    throw new RestException(500, "Error while saving the survey bloc", [ 'id_intervention' => $id_intervention, 'id_equipment' => $current_equipment_id, 'details' => $this->_getErrors($current_survey_bloc) ]);
                }

                $question_bloc_saved = array();

                // Save question bloc
                //-------------------------$id_c_question_bloc
                foreach ($survey_bloc['survey'] as $question_bloc) {
                    $current_id = isset($question_bloc['id']) ? $question_bloc['id'] : 0;
                    // Get handler of the question bloc (if exist in the current survey) or create it
                    $blocInsideLocalBlocIndex = array_search($current_id, array_column($current_survey_bloc->survey, 'fk_c_question_bloc'));
                    $current_question_bloc = $blocInsideLocalBlocIndex !== false ? $current_survey_bloc->survey[$blocInsideLocalBlocIndex] : new EIQuestionBloc(self::$db, $current_survey_bloc);

                    // Set Values
                    $current_question_bloc->fk_survey_bloc = $current_survey_bloc->id;
                    $current_question_bloc->fk_c_question_bloc = $current_c_question_bloc_id;
                    $current_question_bloc->position_question_bloc = isset($question_bloc['position_question_bloc']) ? $question_bloc['position_question_bloc'] : null;
                    $current_question_bloc->code_question_bloc = isset($question_bloc['code_question_bloc']) ? $question_bloc['code_question_bloc'] : null;
                    $current_question_bloc->label_question_bloc = isset($question_bloc['label_question_bloc']) ? $question_bloc['label_question_bloc'] : null;
                    $current_question_bloc->extrafields_question_bloc = isset($question_bloc['extrafields_question_bloc']) ? $question_bloc['extrafields_question_bloc'] : null;
                    $current_question_bloc->array_options = isset($question_bloc['array_options']) ? $question_bloc['array_options'] : $current_question_bloc->array_options;
                    $current_question_bloc->complementary_question_bloc = isset($question_bloc['complementary_question_bloc']) ? $question_bloc['complementary_question_bloc'] : $current_question_bloc->complementary_question_bloc;
                    $current_question_bloc->attached_files = isset($question_bloc['attached_files']) ? $question_bloc['attached_files'] : $current_question_bloc->attached_files;
                    $current_question_bloc->fk_c_question_bloc_status = isset($question_bloc['fk_c_question_bloc_status']) ? $question_bloc['fk_c_question_bloc_status'] : $current_question_bloc->fk_c_question_bloc_status;
                    $current_question_bloc->code_status = isset($question_bloc['code_status']) ? $question_bloc['code_status'] : null;
                    $current_question_bloc->label_status = isset($question_bloc['label_status']) ? $question_bloc['label_status'] : null;
                    $current_question_bloc->color_status = isset($question_bloc['color_status']) ? $question_bloc['color_status'] : null;
                    $current_question_bloc->mandatory_status = isset($question_bloc['mandatory_status']) ? $question_bloc['mandatory_status'] : null;
                    $current_question_bloc->justificatory_status = isset($question_bloc['justificatory_status']) ? $question_bloc['justificatory_status'] : $current_question_bloc->justificatory_status;

                    $current_question_bloc->icone = isset($question_bloc['icone']) ? $question_bloc['icone'] : null;
                    $current_question_bloc->title_editable = isset($question_bloc['title_editable']) ? $question_bloc['title_editable'] : null;
                    $current_question_bloc->bloc_complementary_editable = isset($question_bloc['bloc_complementary_editable']) ? $question_bloc['bloc_complementary_editable'] : null;
                    $current_question_bloc->deletable = isset($question_bloc['deletable']) ? $question_bloc['deletable'] : null;
                    $current_question_bloc->private_bloc = isset($question_bloc['private_bloc']) ? $question_bloc['private_bloc'] : null;
                    $current_question_bloc->desactivated_bloc = isset($question_bloc['desactivated_bloc']) ? $question_bloc['desactivated_bloc'] : null;
                    $current_question_bloc->desactivate_bloc_status = isset($question_bloc['desactivate_bloc_status']) ? $question_bloc['desactivate_bloc_status'] : null;

                    if ($current_question_bloc->id > 0) {
                        // Update
                        $result = $current_question_bloc->update(DolibarrApiAccess::$user);
                        if ($result < 0) {
                            self::$db->rollback();
                            throw new RestException(500, "Error while saving the question bloc", ['id_intervention' => $id_intervention, 'id_equipment' => $current_equipment_id, 'id_c_question_bloc' => $current_question_bloc->fk_c_question_bloc, 'details' => $this->_getErrors($current_question_bloc)]);
                        }
                    } else {
                        // Create
                        $result = $current_question_bloc->create(DolibarrApiAccess::$user);
                        if ($result < 0) {
                            self::$db->rollback();
                            throw new RestException(500, "Error while saving the question bloc", ['id_intervention' => $id_intervention, 'id_equipment' => $current_equipment_id, 'id_c_question_bloc' => $current_question_bloc->fk_c_question_bloc, 'details' => $this->_getErrors($current_question_bloc)]);
                        }
                    }



                    // Save question
                    //-------------------------
                    foreach ($question_bloc['lines'] as $question) {
                        //$current_question_id = isset($question['id']) ? $question['id'] : $id_question;
                        // Get handler of the question (if exist in the current survey) or create it
                        $current_question = isset($current_question_bloc->lines[$question['id']]) ? $current_question_bloc->lines[$question['id']] : new EIQuestionBlocLine(self::$db);


                        // Set Values
                        $current_question->fk_question_bloc = $current_question_bloc->id;
                        $current_question->fk_c_question = $current_c_question_id;
                        $current_question->position_question = isset($question['position_question']) ? $question['position_question'] : null;
                        $current_question->code_question = isset($question['code_question']) ? $question['code_question'] : null;
                        $current_question->label_question = isset($question['label_question']) ? $question['label_question'] : null;
                        $current_question->extrafields_question = isset($question['extrafields_question']) ? $question['extrafields_question'] : null;
                        $current_question->array_options = isset($question['array_options']) ? $question['array_options'] : $current_question->array_options;
                        $current_question->fk_c_answer = isset($question['fk_c_answer']) ? $question['fk_c_answer'] : $current_question->fk_c_answer;
                        $current_question->code_answer = isset($question['code_answer']) ? $question['code_answer'] : null;
                        $current_question->label_answer = isset($question['label_answer']) ? $question['label_answer'] : null;
                        $current_question->color_answer = isset($question['color_answer']) ? $question['color_answer'] : null;
                        $current_question->mandatory_answer = isset($question['mandatory_answer']) ? $question['mandatory_answer'] : null;
                        $current_question->text_answer = isset($question['text_answer']) ? $question['text_answer'] : $current_question->text_answer;

                        if ($current_question->id > 0) {
                            // Update
                            $result = $current_question->update(DolibarrApiAccess::$user);
                        } else {
                            // Create
                            $result = $current_question->insert(DolibarrApiAccess::$user);
                        }

                        if ($result < 0) {
                            self::$db->rollback();
                            throw new RestException(500, "Error while saving the question", ['id_intervention' => $id_intervention, 'id_equipment' => $current_equipment_id, 'id_c_question_bloc' => $current_question_bloc->fk_c_question_bloc, 'id_c_question' => $current_question->fk_c_question,'currentQuestionId' => $current_question->id, 'details' => $this->_getErrors($current_question)]);
                        }
                    }
                    $question_bloc_saved[]=$current_question_bloc;
                }

                // Delete question bloc
                //-------------------------
                if ($delete_question_bloc) {
                    foreach ($current_survey_bloc->survey as $qb) {
                        if (!isset($question_bloc_saved[$qb->fk_c_question_bloc]) && !empty($qb->deletable)) {
                            if ($qb->delete(DolibarrApiAccess::$user) < 0) {
                                self::$db->rollback();
                                throw new RestException(500, "Error while deleting the question bloc", ['id_intervention' => $id_intervention, 'id_equipment' => $current_equipment_id, 'id_c_question_bloc' => $qb->id, 'details' => $this->_getErrors($qb)]);
                            }
                        }
                    }
                }
            }
        }

        self::$db->commit();
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
                } elseif (is_string($v)) {
                    if (is_array($object))
                        $object[$k] = $this->_cleanString($v);
                    else
                        $object->$k = $this->_cleanString($v);
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
                } elseif (is_string($v)) {
                    if (is_array($object))
                        $object[$k] = $this->_cleanString($v);
                    else
                        $object->$k = $this->_cleanString($v);
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
     *	Clean a string (decode a HTML string (it decodes entities and br tags) and convert \n text en return line)
     *
     *	@param	string	$string		        String to decode
     *	@param	string	$pagecodeto			Page code for result
     *	@return	string						String cleaned
     */
    function _cleanString($string, $pagecodeto='UTF-8')
    {
        $ret = dol_html_entity_decode($string, ENT_QUOTES, $pagecodeto);
        $ret = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i', "<br />", $ret);
        $ret = preg_replace('/(\r\n|\r|\n)/i', preg_match('/<br \/>/i', $ret) ? "" : "<br />", $ret);
        return $ret;
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
