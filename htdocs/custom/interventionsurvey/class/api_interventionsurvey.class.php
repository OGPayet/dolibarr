<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2020 Alexis LAURIER
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

use Luracast\Restler\RestException;

dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
dol_include_once('/fichinter/class/fichinter.class.php');


/**
 * \file    interventionsurvey/class/api_interventionsurvey.class.php
 * \ingroup interventionsurvey
 * \brief   File for API management of surveyanswer.
 */

/**
 * API class for interventionsurvey
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class InterventionSurveyApi extends DolibarrApi
{

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
            "rang" => '', "product_type" => '', "array_options" => '', "fk_fichinter" => ''
        ),
        'societe' => array(
            "entity" => '', "nom" => '', "name_alias" => '', "zip" => '', "town" => '', "status" => '',
            "state_code" => '', "state" => '', "pays" => '',
            "phone" => '', "fax" => '', "email" => '', "skype" => '', "url" => '', "code_client" => '',
            "price_level" => '', "outstanding_limit" => '', "parent" => '', "default_lang" => '', "ref" => '', "ref_ext" => '',
            "logo" => '', "array_options" => '', "id" => '', 'address' => '', "name" => '',
        )
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
    static protected $WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array();

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
     * @var InterventionSurvey $interventionSurvey {@type InterventionSurvey}
     */
    public $interventionSurvey;

    /**
     * @var InterventionSurveyLine $interventionLine {@type FichinterLigne}
     */
    public $interventionLine;

    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    public function __construct()
    {
        global $db, $langs, $conf;
        $this->db = $db;
        $langs = new Translate("", $conf);
        $langs->setDefaultLang(!empty(DolibarrApiAccess::$user->lang) ? DolibarrApiAccess::$user->lang : 'fr_FR');
        $langs->load("interventionsurvey@interventionsurvey");
        $this->interventionSurvey = new InterventionSurvey($this->db);
        $this->interventionLine = new InterventionSurveyLine($this->db);
    }
    /**
     * Get dictionary of a intervention type
     *
     * Return an array with Intervention type
     *
     * @param   string	    $sort_field         Sort field (field name)
     * @param   string	    $sort_order         Sort order (ASC or DESC)
     * @param   int		    $limit		        Limit for list
     * @param   int		    $page		        Page number
     * @return 	array
     * @url	GET /dictionaryType
     * @throws 	RestException
     */
    function getDictionaryType($sort_field = "", $sort_order = "ASC", $limit = 100, $page = 0)
    {
        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->readApi) {
            throw new RestException(401);
        }
        global $user;
        dol_include_once("/advancedictionaries/class/api_advancedictionaries.class.php");
        $dictionaryApi = new AdvanceDictionariesApi();
        if(empty($user->rights->advancedictionaries)) {
            $user->rights->advancedictionaries = new stdClass();
        }
        $oldRight = $user->rights->advancedictionaries->read;
        $user->rights->advancedictionaries->read = 1;
        $result = $dictionaryApi->index('extendedintervention', 'extendedinterventiontype', '', $sort_field, $sort_order, $limit, $page);
        $user->rights->advancedictionaries->read = $oldRight;
        return $result;
    }


    /**
     * Get properties of a interventionSurvey object
     *
     * Return an array with InterventionSurvey informations
     *
     * @param 	int 	$id ID of surveyanswer
     * @return 	object data without useless information
     *
     * @url	GET {id}
     * @throws 	RestException
     */
    function get($id)
    {
        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->readApi) {
            throw new RestException(401);
        }

        if (!($id > 0)) {
            throw new RestException(400, 'Bad Request : you must provide a valid Intervention Id');
        }

        $result = $this->interventionSurvey->fetch($id, '', true, true);
        if (!($result > 0)) {
            throw new RestException(404, 'Intervention not found');
        }

        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id=' . $this->interventionSurvey->id . ' of object not allowed for login ' . DolibarrApiAccess::$user->login);
        }
        $this->interventionSurvey->fetchObjectLinkedIdsWithCache();
        $result = $this->_cleanObjectData($this->interventionSurvey);
        return $result;
    }


    /**
     * List of interventions
     *
     * Return a list of interventions
     *
     * @url GET /
     *
     * @param   string          $sortfield          Sort field
     * @param   string          $sortorder          Sort order
     * @param   int             $limit		        Limit for list
     * @param   int             $page		        Page number
     * @param   string          $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  object[]           Array of order objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve intervention list
     */
    function indexIntervention($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
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
        }
        // internal
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
            $socids = DolibarrApiAccess::$user->socid;
            if ($socids) {
                $sql .= ' OR (t.entity IN (' . getEntity('intervention') . ')';
                $sql .= " AND t.fk_soc IN (" . $socids . "))";
            }
            $sql .= " GROUP BY rowid";
        }



        $sql .= $this->db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $this->db->query($sql);
        if ($result) {
            $arrayOfInterventionIds = array();
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min) {
                $obj = $this->db->fetch_object($result);
                $arrayOfInterventionIds[] = $obj->rowid;
                $i++;
            }
            InterventionSurvey::fillSurveyCacheForParentObjectIds($arrayOfInterventionIds);
            foreach ($arrayOfInterventionIds as $id) {
                $fichinter_static = new InterventionSurvey($this->db);
                if ($fichinter_static->fetch($id, null, true)) {
                    $fichinter_static->fetchObjectLinkedIdsWithCache();
                    $obj_ret[] = $this->_cleanObjectData($fichinter_static);
                }
            }
        } else {
            throw new RestException(503, 'Error when retrieve fichinter list : ' . $this->db->lasterror());
        }

        return $obj_ret;
    }


    /**
     * Update interventionsurvey based on id field of request data
     *
     * @return object
     *
     * @url PUT /
     */
    function put($request_data = null)
    {

        if (!$request_data) {
            throw new RestException(400, "You must provide data");
        }

        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->writeApi) {
            throw new RestException(401, "Insufficient rights");
        }

        $request_data = json_decode(json_encode($request_data));
        $id = $request_data->id;

        if (!($id > 0)) {
            throw new RestException(400, 'Bad Request : you must provide a valid Intervention Id');
        }
        $result = $this->interventionSurvey->fetch($id, '', true, true);
        if (!($result > 0)) {
            throw new RestException(404, 'Intervention not found');
        }

        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id=' . $this->interventionSurvey->id . ' of object not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->interventionSurvey->is_survey_read_only()) {
            throw new RestException(401, 'Intervention survey with id = ' . $this->interventionSurvey->id . ' is in readonly mode');
        }

        $request = clone $this->interventionSurvey;
        $request->setSurveyFromFetchObj($request_data->survey, true);

        //We update general field on intervention
        $fields = array();
        foreach ($fields as $field) {
            if (property_exists($request_data, $field)) {
                $this->interventionSurvey->{$field} = $request_data->{$field} ?? null;
            }
        }
        //We update too signature contained into array_options
        $fields = array('options_customer_signature', 'options_stakeholder_signature', 
            'options_contacts_to_send_fichinter_to', 'options_users_to_send_fichinter_to');
        if (!$this->interventionSurvey->array_options) {
            $this->interventionSurvey->array_options = array();
        }
        if (!is_array($request_data->array_options)) {
            $request_data->array_options = (array) $request_data->array_options;
        }
        foreach ($fields as $field) {
            if (array_key_exists($field, $request_data->array_options)) {
                $this->interventionSurvey->array_options[$field] = $request_data->array_options[$field];
            }
        }

        $this->db->begin();
        //We update too other field on intervention
        $result = $this->interventionSurvey->update(DolibarrApiAccess::$user);

        //Finally we update survey
        if ($result > 0) {
            $result = $this->interventionSurvey->mergeWithFollowingData(DolibarrApiAccess::$user, $request, true);
        }

        //Add linked equipment to the intervention and update intervention survey
        $this->interventionSurvey->fetchObjectLinked();
        $requestDataLinkEquipementIds = array();
        if($request_data->linkedObjectsIds)
        {
            if(is_array($request_data->linkedObjectsIds))
            {
                $requestDataLinkEquipementIds = $request_data->linkedObjectsIds['equipement'];
            }
            elseif(is_object($request_data->linkedObjectsIds)) {
                $requestDataLinkEquipementIds = $request_data->linkedObjectsIds->equipement;
            }
            $requestDataLinkEquipementIds = array_values((array) $requestDataLinkEquipementIds);
        }
        $alreadyLinkedEquipmentsIds = [];
        if ($this->interventionSurvey->linkedObjectsIds && $this->interventionSurvey->linkedObjectsIds['equipement']) {
            $alreadyLinkedEquipmentsIds = array_values((array) $this->interventionSurvey->linkedObjectsIds['equipement']);
        }

        $newLinkedEquipmentsIds = [];
		foreach ((array) $requestDataLinkEquipementIds as $requestDataLinkedEquipementId) {
			if (!in_array($requestDataLinkedEquipementId, $alreadyLinkedEquipmentsIds)) {
				$newLinkedEquipmentsIds[] = $requestDataLinkedEquipementId;
			}
		}

		foreach ($newLinkedEquipmentsIds as $newLinkedEquipementId) {
			if ((int) $newLinkedEquipementId > 0) {
				$this->interventionSurvey->add_object_linked('equipement', (int) $newLinkedEquipementId);
			}
		}

            //If we want to unlinked some equipement, links should be deleted here
            //We update survey with data from dictionnary as some equipment may have been removed/deleted
		if (!empty($newLinkedEquipmentsIds)) {
			$this->interventionSurvey->fetchObjectLinkedIdsWithCache(true, true);
			$this->interventionSurvey->softUpdateOfSurveyFromDictionary(DolibarrApiAccess::$user);
		}

        if ($result > 0) {
            $this->db->commit();
            $this->updatePdfFileIfNeeded();
            return $this->_cleanObjectData($this->interventionSurvey);
        } else {
            $this->db->rollback();
            throw new RestException(422, "Error when saving the survey", ['id_intervention' => $this->interventionSurvey->id, 'details' => $this->_getErrors($this->interventionSurvey)]);
        }
    }

    /**
     * Update or create intervention line based on provided id
     *
     * @return object
     *
     * @url PUT /line/
     */
    function putLine($request_data = null)
    {

        if (!$request_data) {
            throw new RestException(400, "You must provide data");
        }

        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->writeApi) {
            throw new RestException(401, "Insufficient rights");
        }

        //We prepare request data object
        $request_data = json_decode(json_encode($request_data));

        if (property_exists($request_data, 'array_options')) {
            $request_data->array_options = (array) $request_data->array_options;
        }

        //We do some check

        $id = $request_data->id;
        if ($id && $id > 0 && $this->interventionLine->fetch($id) < 0) {
            $this->interventionLine->id = 0;
        }

        $fichInterId = $request_data->fk_fichinter ?? $this->interventionLine->fk_fichinter;

        if (!$fichInterId || $fichInterId < 0) {
            throw new RestException(400, "Bad request, you must provide a valid fk_fichinter value");
        }

        $this->interventionLine->fk_fichinter = $fichInterId;

        if ($this->interventionSurvey->fetch($fichInterId, '', true, true) < 0) {
            throw new RestException(422, "Error when fetching the intervention", ['id_intervention' => $request_data->fk_fichinter, 'details' => $this->_getErrors($this->interventionSurvey)]);
        }

        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id=' . $this->interventionSurvey->id . ' of object not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->interventionSurvey->is_survey_read_only()) {
            throw new RestException(401, 'Intervention survey with id = ' . $this->interventionSurvey->id . ' is in readonly mode');
        }


        $fields = array('datei', 'desc', 'duration', 'rang', 'array_options');
        foreach ($fields as $field) {
            if (property_exists($request_data, $field)) {
                $this->interventionLine->{$field} = $request_data->{$field} ?? null;
            }
        }

        if ($this->interventionLine->id > 0) {
            $result = $this->interventionLine->update(DolibarrApiAccess::$user);
        } else {
            $result = $this->interventionLine->insert(DolibarrApiAccess::$user);
        }

        if ($result >= 0 && $this->interventionLine->fetch_optionals() >= 0) {
            $this->updatePdfFileIfNeeded();
            return $this->_cleanObjectData($this->interventionLine);
        } else {
            throw new RestException(422, "Error when saving the intervention Line", ['id_intervention' => $this->interventionSurvey->id, 'details' => $this->_getErrors($this->interventionLine)]);
        }
    }

    /**
     * Delete a line of given intervention
     *
     * @url	DELETE /line/{lineId}
     *
     * @param   int   $lineId         Id of line to delete
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while deleting the intervention line
     */
    function deleteLine($lineId)
    {
        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->writeApi) {
            throw new RestException(401, "Insufficient rights");
        }

        if (!$lineId) {
            throw new RestException(400, "Bad Request");
        }

        if ($this->interventionLine->fetch($lineId) < 0 || $this->interventionLine->rowid == null) {
            //Intervention line has already been deleted
            return true;
        }

        if ($this->interventionLine->fk_fichinter > 0 && $this->interventionSurvey->fetch($this->interventionLine->fk_fichinter, '', true, true) < 0) {
            //Intervention has already been deleted
            return true;
        }

        $hasPerm = $this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user);
        if (!$hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->interventionSurvey->is_survey_read_only()) {
            throw new RestException(403, 'Intervention survey with id = ' . $this->interventionSurvey->id . ' is in readonly mode');
        }

        if ($this->interventionLine->deleteline(DolibarrApiAccess::$user) >= 0) {
            $this->updatePdfFileIfNeeded();
            return true;
        } else {
            throw new RestException(422, "Error when deleting the intervention line", ['id_intervention' => $this->interventionSurvey->id, 'id_line' => $this->interventionLine->id, 'details' => $this->_getErrors($this->interventionLine)]);
        }
    }

    /**
     * Close an intervention
     *
     * @url POST /{interventionId}/close
     *
     * @param   int 	$interventionId             Intervention ID
     * @return  object
     *
     */
    function closeIntervention($id)
    {
        global $user;
        $user = DolibarrApiAccess::$user;
        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->writeApi) {
            throw new RestException(401, "Insufficient rights");
        }

        if (!($id > 0)) {
            throw new RestException(400, 'Bad Request : you must provide a valid Intervention Id');
        }

        $result = $this->interventionSurvey->fetch($id, '', true, true);
        if (!($result > 0)) {
            throw new RestException(404, 'Intervention not found');
        }

        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id = ' . $this->interventionSurvey->id . ' of object not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->interventionSurvey->is_survey_read_only() && $this->interventionSurvey->statut != $this->interventionSurvey::STATUS_DONE) {
            throw new RestException(401, 'Intervention survey with id = ' . $this->interventionSurvey->id . ' is in readonly mode but not closed');
        }
        if ($this->interventionSurvey->statut != $this->interventionSurvey::STATUS_DONE) {
            $this->interventionSurvey->context['closedFromApi'] = true;
            $result = $this->interventionSurvey->setStatut(3);
        } else {
            $result = 1; //intervention is already closed
        }
        if ($result < 0) {
            throw new RestException(403, 'Error when closing Intervention with id=' . $this->interventionSurvey->id . ' : ' .  $this->_getErrors($this->interventionSurvey));
        }

        $this->interventionSurvey->fetchObjectLinked();
        return $this->_cleanObjectData($this->interventionSurvey);
    }

    /**
     * Tag the intervention as validated (opened)
     *
     * Function used when intervention is reopened after being closed.
     *
     * @url POST /{interventionId}/reopen
     *
     * @param  int   $interventionId       Id of the intervention
     * @return object
     *
     */
    function reopenIntervention($id)
    {
        global $conf, $langs;

        // module not active
        if (empty($conf->synergiestech->enabled)) {
            throw new RestException(500, 'Error when re-opening Intervention : Module SynergiesTech disabled');
        }

        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->reopenApi) {
            throw new RestException(401, "Insufficient rights");
        }

        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->writeApi) {
            throw new RestException(401, "Insufficient rights");
        }

        if (!($id > 0)) {
            throw new RestException(400, 'Bad Request : you must provide a valid Intervention Id');
        }

        $result = $this->interventionSurvey->fetch($id, '', true, true);
        if (!($result > 0)) {
            throw new RestException(404, 'Intervention not found');
        }

        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id = ' . $this->interventionSurvey->id . ' of object not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        dol_include_once('/synergiestech/lib/synergiestech.lib.php');
        $langs->load('synergiestech@synergiestech');

        $msg_error = '';
        $result = synergiestech_reopen_intervention($this->db, $this->interventionSurvey, DolibarrApiAccess::$user, $msg_error);

        if ($result < 0) {
            throw new RestException(403, 'Error while reopen Intervention with id=' . $this->interventionSurvey->id . ' : ' . $this->_getErrors($this->interventionSurvey));
        }
        $this->updatePdfFileIfNeeded();
        return $this->_cleanObjectData($this->interventionSurvey);
    }

    /**
     * Get fichinter PDF file of an intervention
     *
     * Return the PDF file in base64 string format
     *
     * @param 	int 	$id intervention ID
     * @return 	string
     * @url	GET /fichinterPdf/{id}
     * @throws 	RestException
     */
    function getFichinterPdf($id)
    {
        global $conf;
        
        if (!DolibarrApiAccess::$user->rights->interventionsurvey->survey->readApi) {
            throw new RestException(401);
        }

        if (!($id > 0)) {
            throw new RestException(400, 'Bad Request : you must provide a valid Intervention Id');
        }

        $result = $this->interventionSurvey->fetch($id, '', true, true);
        if (!($result > 0)) {
            throw new RestException(404, 'Intervention not found');
        }

        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id=' . $this->interventionSurvey->id . ' of object not allowed for login ' . DolibarrApiAccess::$user->login);
        }
        
        $this->updatePdfFileIfNeeded(true);

        $ref = dol_sanitizeFileName($this->interventionSurvey->ref);
        $file = $conf->ficheinter->multidir_output[$this->interventionSurvey->entity] . '/' . $ref . '/' . $ref . '.pdf';
        $file_osencoded = dol_osencode($file); // New file encoded in OS encoding charset
        $filename = basename($file);

        if (!file_exists($file_osencoded)) {
			throw new RestException(404, 'Error fichinter PDF not found for Intervention with id=' . $this->interventionSurvey->id . ' : ' . $this->_getErrors($this->interventionSurvey));
		}

        $pdf_content = file_get_contents($file_osencoded);

        return array('filename' => $filename, 'content' => 'data:application/pdf;base64,' . base64_encode($pdf_content), 'encoding' => 'MIME base64 (base64_encode php function, http://php.net/manual/en/function.base64-encode.php)', 'Content-Type' => mime_content_type($file_osencoded));
    }



    /******************************************** */
    //                    TOOLS                   //
    /******************************************** */

    private function updatePdfFileIfNeeded($forceUpdate = false)
    {
        global $conf;
        global $langs;
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE) || $forceUpdate) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/fichinter/modules_fichinter.php';
            fichinter_create($this->db, $this->interventionSurvey, $this->interventionSurvey->modelpdf, $langs);
        }
    }




    /**
     *  Prepare SQL request for element list (propal, commande, invoice, fichinter, contract) for external user
     * @see in CompanyRelationshipsApi class
     *
     * @param       int     $userSocId      Id of user company (external user)
     * @param       int     $search_sale    Id of commercial user
     * @return      string  SQL request
     */
    private static function _sqlElementListForExternalUser($userSocId, $search_sale = 0)
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
    function _cleanObjectData(&$object, $whitelist_of_properties = array(), $blacklist_of_properties = array())
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
    function _cleanSubObjectData(&$object, $whitelist_of_properties = array(), $blacklist_of_properties = array())
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
     *                                                  array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *                                                  if property is a object and this properties_name value is equal '' then get whitelist of his object element
     *                                                  if property is a object and this properties_name value is a array then get whitelist set in the array
     *                                                  if property is a array and this properties_name value is equal '' then get all values
     *                                                  if property is a array and this properties_name value is a array then get whitelist set in the array
     * @param   array       $blacklist_of_properties    Array of blacklist of properties keys for this object
     *                                                  array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *                                                  if property is a object and this properties_name value is equal '' then get blacklist of his object element
     *                                                  if property is a object and this properties_name value is a array then get blacklist set in the array
     *                                                  if property is a array and this properties_name value is equal '' then get all values
     *                                                  if property is a array and this properties_name value is a array then get blacklist set in the array
     *
     * @return void
     *
     * @throws  500         RestException       Error while retrieve the custom whitelist of properties for the object type
     */
    function _getBlackWhitelistOfProperties($object, &$whitelist_of_properties, &$blacklist_of_properties, $linked_object = false)
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
                // $hookmanager->initHooks(array('companyrelationshipsapi', 'globalapi'));
                // $parameters = array('whitelist_of_properties' => &$whitelist_of_properties, 'whitelist_of_properties_linked_object' => &$whitelist_of_properties_linked_object,
                //     'blacklist_of_properties' => &$blacklist_of_properties, 'blacklist_of_properties_linked_object' => &$blacklist_of_properties_linked_object);
                // $reshook = $hookmanager->executeHooks('getBlackWhitelistOfProperties', $parameters, $object); // Note that $action and $object may have been
                // if ($reshook < 0) {
                //     throw new RestException(500, "Error while retrieve the custom blacklist and whitelist of properties for the object type: " . $object->element, ['details' => $this->_getErrors($hookmanager)]);
                // }

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
    function _cleanString($string, $pagecodeto = 'UTF-8')
    {
        return dol_htmlentitiesbr_decode($string);
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
        return implode(", ", $errors);
    }
}
