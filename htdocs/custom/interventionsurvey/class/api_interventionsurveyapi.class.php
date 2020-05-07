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
            "rang" => '', "product_type" => '', "array_options" => '',
        ),
        'societe' => array(
            "entity" => '', "nom" => '', "name_alias" => '', "zip" => '', "town" => '', "status" => '',
            "state_code" => '', "state" => '', "pays" => '',
            "phone" => '', "fax" => '', "email" => '', "skype" => '', "url" => '', "code_client" => '',
            "price_level" => '', "outstanding_limit" => '', "parent" => '', "default_lang" => '', "ref" => '', "ref_ext" => '',
            "logo" => '', "array_options" => '', "id" => '', 'address' => '',"name" => '',
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
     * @var InterventionSurvey $interventionSurvey {@type InterventionSurvey}
     */
    public $interventionSurvey;

    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    public function __construct()
    {
        global $conf, $db, $langs, $user;
        $langs->load("interventionsurvey@interventionsurvey");
        $this->interventionSurvey = new InterventionSurvey($db);
    }

    /**
     * Get properties of a interventionSurvey object
     *
     * Return an array with InterventionSurvey informations
     *
     * @param 	int 	$id ID of surveyanswer
     * @return 	object data without useless information
     *
     * @url	GET {id_intervention}
     * @throws 	RestException
     */
    function get($id)
    {
        if (! DolibarrApiAccess::$user->rights->interventionsurvey->survey->readApi) {
            throw new RestException(401);
        }

        $result = $this->interventionSurvey->fetch($id);
        if (!($result > 0)) {
            throw new RestException(404, 'Intervention not found');
        }

        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id='.$this->interventionSurvey->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $this->interventionSurvey->fetchObjectLinked();
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
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter as t";

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



        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $fichinter_static = new InterventionSurvey($db);
                if($fichinter_static->fetch($obj->rowid)) {
                    $fichinter_static->fetchObjectLinked();
                    $obj_ret[] = $this->_cleanObjectData($fichinter_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve fichinter list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return $obj_ret;
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
        global $db;

        if (!$request_data) {
            throw new RestException(400, "You must provide data");
        }

        if (! DolibarrApiAccess::$user->rights->interventionsurvey->survey->writeApi) {
            throw new RestException(401);
        }

        $request_data = json_decode(json_encode($request_data));
        $id = $request_data->id;
        if (!$id) {
            throw new RestException(400, "You must provide id field of the intervention to update");
        }

        $result = $this->interventionSurvey->fetch($id);
        if (!($result > 0)) {
            throw new RestException(404, 'Intervention not found');
        }


        if (!$this->interventionSurvey->checkUserAccess(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'Access to instance id='.$this->interventionSurvey->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }



        if($this->interventionSurvey->is_survey_read_only()) {
            throw new RestException(401, 'Intervention survey with id='.$this->interventionSurvey->id.'is in readonly mode');
        }

        $request = clone $this->interventionSurvey;
        $request->setSurveyFromFetchObj($request_data->survey, true);

        $result = $this->interventionSurvey->mergeWithFollowingData(DolibarrApiAccess::$user,$request, true);
        $this->interventionSurvey->fetchObjectLinked();

        if ($result > 0)
        {
            return $this->_cleanObjectData($this->interventionSurvey);
        }
        else
        {
            throw new RestException(422, "Error when saving the survey", [ 'id_intervention' => $this->interventionSurvey->id, 'details' => $this->_getErrors($this->interventionSurvey) ]);
        }
    }

    /******************************************** */
    //                    TOOLS                   //
    /******************************************** */
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
    function _cleanString($string, $pagecodeto='UTF-8')
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

        return $errors;
    }
}
