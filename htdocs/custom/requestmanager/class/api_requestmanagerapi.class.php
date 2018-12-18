<?php
/* Copyright (C) 2018	Julien Vercruysse	<julien.vercruysse@elonet.fr>
 * Copyright (C) 2018	Open-DSI	        <support@open-dsi.fr>
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

dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/requestmanagermessage.class.php');

/**
 * API class for Request Manager
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class RequestManagerApi extends DolibarrApi {
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
     * @var array       $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();
    /**
     * @var array       $MESSAGE_FIELDS     Mandatory fields, checked when create and update message object
     */
    static $MESSAGE_FIELDS = array();

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
        'action' => array(
            "id" => '', "ref" => '', "type_id" => '', "type_code" => '', "type" => '', "type_color" => '', "code" => '',
            "label" => '', "datec" => '', "datem" => '', "authorid" => '', "usermodid" => '', "datep" => '', "datef" => '',
            "durationp" => '', "fulldayevent" => '', "punctual" => '', "percentage" => '', "location" => '', "transparency" => '',
            "priority" => '', "userassigned" => array(''=>array('id'=>'')), "userownerid" => '', "userdoneid" => '', "socid" => '', "contactid" => '',
            "contact" => '', "array_options" => '', "fk_project" => '', "ref_ext" => '', "note" => '', "type_picto" => '',
            "user_mod" => '', "user_done" => '', "user_owner" => '', "thirdparty" => '', "entity" => '', "fk_element " => '', "elementtype" => '',
        ),
        'contact' => array(
            "id" => '', "ref" => '', "ref_ext" => '', "civility_id" => '', "civility_code" => '', "lastname" => '', "firstname" => '',
            "address" => '', "zip" => '', "town" => '', "state_id" => '', "state_code" => '', "state" => '', "country_id" => '',
            "country_code" => '', "country" => '', "socid" => '', "socname" => '', "poste" => '', "statut" => '', "phone_pro" => '',
            "fax" => '', "phone_perso" => '', "phone_mobile" => '', "email" => '', "jabberid" => '', "skype" => '',
            "photo" => '', "priv" => '', "mail" => '', "birthday" => '', "note_public" => '', "gender" => '', "user_id" => '',
            "default_lang" => '',
        ),
        'user' => array(
            "id" => '', "employee" => '', "gender" => '', "email" => '', "skype" => '', "job" => '', "signature" => '',
            "address" => '', "zip" => '', "town" => '', "state_id" => '', "state_code" => '', "state" => '', "office_phone" => '',
            "office_fax" => '', "user_mobile" => '', "entity" => '', "datec" => '', "datem" => '', "socid" => '', "contactid" => '',
            "fk_member" => '', "fk_user" => '', "datelastlogin" => '', "datepreviouslogin" => '', "statut" => '', "photo" => '',
            "lang" => '', "users" => '', "parentof" => '', "thm" => '', "tjm" => '', "salary" => '', "salaryextra" => '',
            "weeklyhours" => '', "color" => '', "dateemployment" => '', "array_options" => '', "ref" => '', "ref_ext" => '',
            "country_id" => '', "country_code" => '', "lastname" => '', "firstname" => '', "thirdparty" => '',
        ),
        'usergroup' => array(
            "id" => '', "ref" => '', "name" => '', "array_options" => '', "entity" => '', "datec" => '', "datem" => '',
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
            "logo" => '', "array_options" => '', "id" => '', "linkedObjectsIds" => '',
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
     *  Constructor
     */
    function __construct()
    {
        global $conf, $db, $langs;
        self::$db = $db;
        $langs->load('requestmanager@requestmanager');

        // Specific case for the whitelist of the user object
        $canreadsalary = ((!empty($conf->salaries->enabled) && !empty(DolibarrApiAccess::$user->rights->salaries->read))
            || (!empty($conf->hrm->enabled) && !empty(DolibarrApiAccess::$user->rights->hrm->employee->read)));
        if (!$canreadsalary) {
            unset(self::$WHITELIST_OF_PROPERTIES['user']['salary']);
            unset(self::$WHITELIST_OF_PROPERTIES['user']['salaryextra']);
            unset(self::$WHITELIST_OF_PROPERTIES['user']['thm']);
            unset(self::$WHITELIST_OF_PROPERTIES['user']['tjm']);
        }
    }

    /**
     *  Get the request
     *
     * @param   int             $id                 ID of the request
     *
     * @return  object|array                        Request data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  403             RestException       Access unauthorized
     * @throws  404             RestException       Request not found
     * @throws  500             RestException       Error when retrieve request
     */
    function get($id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        $requestmanager->fetch_assigned(1);
        $requestmanager->fetch_requesters(1);
        $requestmanager->fetch_watchers(1);
        $requestmanager->fetch_optionals();
        $requestmanager->fetch_thirdparty_origin();
        $requestmanager->fetch_thirdparty();
        $requestmanager->fetch_thirdparty_benefactor();
        $requestmanager->fetchObjectLinked();
        return $this->_cleanObjectData($requestmanager);
    }

    /**
     *  Get the list of requests
     *
     * @param   string	    $sort_field         Sort field
     * @param   string	    $sort_order         Sort order
     * @param   int		    $limit		        Limit for list
     * @param   int		    $page		        Page number
     * @param   int         $only_assigned	    1=Restrict list to the request assigned to this user or his user groups
     * @param   string      $sql_filters        Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     * @param   string      $linked_filters     Filter for linked object 'element:id,id;element:id,id'
     *                                          ex: 'commande' only request linked with order, 'commande:1,10' only request linked with order id 1 and 10
     *
     * @return  array                           Array of request objects
     *
     * @throws  400         RestException       Error when validating parameter 'sqlfilters'
     * @throws  401         RestException       Insufficient rights
     * @throws  500         RestException       Error when retrieve request list
     */
    function index($sort_field="t.rowid", $sort_order='ASC', $limit=100, $page=0, $only_assigned=0, $sql_filters='', $linked_filters='')
    {
        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // clean params
        $linked_filters_t = array();
        if (!empty($linked_filters)) {
            $bloc_tmp = explode(';', $linked_filters);
            foreach ($bloc_tmp as $bloc) {
                $element_tmp = explode(':', $bloc);
                $element = trim($element_tmp[0]);
                if (!empty($element)) {
                    $element_ids = array();
                    if (isset($element_tmp[1]) && trim($element_tmp[1]) !== '') {
                        $ids_tmp = explode(',', $element_tmp[1]);
                        foreach ($ids_tmp as $id) {
                            $id = trim($id);
                            if ($id !== '') {
                                $element_ids[] = $id;
                            }
                        }
                    }
                    $linked_filters_t[$element] = $element_ids;
                }
            }
        }

        $assignedSQLJoin = '';
        if (!(DolibarrApiAccess::$user->socid > 0) && $only_assigned) {
            $sqlFilter = ' AND rmau.fk_user = ' . DolibarrApiAccess::$user->id;

            require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
            $usergroup_static = new UserGroup(self::$db);
            $groupslist = $usergroup_static->listGroupsForUser(DolibarrApiAccess::$user->id);
            if (!empty($groupslist)) {
                $myGroups = implode(',', array_keys($groupslist));
                $sqlFilter = ' AND (rmau.fk_user = ' . DolibarrApiAccess::$user->id . ' OR rmaug.fk_usergroup IN (' . $myGroups . '))';
            }

            $assignedSQLJoin = ' INNER JOIN (' .
                ' SELECT DISTINCT rm.rowid' .
                ' FROM ' . MAIN_DB_PREFIX . 'requestmanager as rm' .
                ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_user as rmau ON rmau.fk_requestmanager = rm.rowid' .
                ' LEFT JOIN ' . MAIN_DB_PREFIX . 'requestmanager_assigned_usergroup as rmaug ON rmaug.fk_requestmanager = rm.rowid' .
                ' WHERE rm.entity IN (' . getEntity('requestmanager') . ')' .
                $sqlFilter .
                ' ) as assigned ON assigned.rowid = rm.rowid';
        }

        $sql = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "requestmanager as t" . $assignedSQLJoin;
        $sql .= ' WHERE t.entity IN (' . getEntity('requestmanager') . ')';
        // Restrict to the company of the user
        if (DolibarrApiAccess::$user->socid > 0) {
            $sql .= " AND (t.fk_soc = " . DolibarrApiAccess::$user->socid . " OR t.fk_soc_benefactor = " . DolibarrApiAccess::$user->socid . ")";
        }
        // Add sql filters
        if ($sql_filters) {
            if (!DolibarrApi::_checkFilters($sql_filters)) {
                throw new RestException(400, 'Error when validating parameter \'sql_filters\': ' . $sql_filters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sql_filters) . ")";
        }
        $sql .= " GROUP BY t.rowid";

        // Set Order and Limit
        $sql .= self::$db->order($sort_field, $sort_order);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= self::$db->plimit($limit, $offset);
        }

        $resql = self::$db->query($sql);
        if ($resql) {
            while ($obj = self::$db->fetch_object($resql)) {
                $requestmanager = new RequestManager(self::$db);
                if ($requestmanager->fetch($obj->rowid) > 0) {
                    $requestmanager->fetch_assigned(1);
                    $requestmanager->fetch_requesters(1);
                    $requestmanager->fetch_watchers(1);
                    $requestmanager->fetch_optionals();
                    $requestmanager->fetch_thirdparty_origin();
                    $requestmanager->fetch_thirdparty();
                    $requestmanager->fetch_thirdparty_benefactor();
                    $requestmanager->fetchObjectLinked();
                    $add_request = !count($linked_filters_t);
                    foreach ($linked_filters_t as $element_type => $element_type_ids) {
                        $linked_object_ids = $requestmanager->linkedObjectsIds[$element_type];
                        if (is_array($linked_object_ids)) {
                            if (count($element_type_ids) == 0) {
                                $add_request = true;
                                break;
                            } else {
                                foreach ($element_type_ids as $element_id) {
                                    if (in_array($element_id, $linked_object_ids)) {
                                        $add_request = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    if ($add_request) {
                        $obj_ret[] = $this->_cleanObjectData($requestmanager);
                    }
                }
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve request list", [ 'details' => [ self::$db->lasterror() ]]);
        }

        return $obj_ret;
    }

    /**
     *  Create a request
     *
     * @param   array   $request_data       Request data
     *
     * @return  int                         ID of the request created
     *
     * @throws  400     RestException       Field missing
     * @throws  401     RestException       Insufficient rights
     * @throws  500     RestException       Error while creating the request
     */
    function post($request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Check mandatory fields
        $this->_validate($request_data);

        // todo remplir auto le socid_origin, socid et socid_benefactor si un utilisateur externe ?
        $requestmanager = new RequestManager(self::$db);
        foreach ($request_data as $field => $value) {
            $requestmanager->$field = $value;
        }

        if ($requestmanager->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while creating the request", [ 'details' => $this->_getErrors($requestmanager) ]);
        }

        return $requestmanager->id;
    }

    /**
     *  Update a request
     *
     * @param   int         $id             ID of the request
     * @param   array       $request_data   Request data
     *
     * @return  object                      Request data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  403         RestException   Access unauthorized
     * @throws  404         RestException   Request not found
     * @throws  500         RestException   Error when retrieve request
     * @throws  500         RestException   Error while updating the request
     */
    function put($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        $requestmanager->oldcopy = clone $requestmanager;
        $exclude_fields = array('id', 'socid_origin', 'socid', 'socid_benefactor');
        foreach ($request_data as $field => $value) {
            if (in_array($field, $exclude_fields)) continue;
            $requestmanager->$field = $value;
        }

        if ($requestmanager->update(DolibarrApiAccess::$user) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the request", [ 'details' => $this->_getErrors($requestmanager) ]);
        }
    }

    /**
     *  Delete request
     *
     * @param   int     $id             ID of the request
     *
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  403     RestException   Access unauthorized
     * @throws  404     RestException   Request not found
     * @throws  500     RestException   Error when retrieve request
     * @throws  500     RestException   Error while deleting the request
     */
    function delete($id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        if ($requestmanager->delete(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while deleting the request", [ 'details' => $this->_getErrors($requestmanager) ]);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Request deleted'
            )
        );
    }

    /**
	 *  Get lines of the given request
	 *
     * @url	GET {id}/lines
     *
     * @param   int     $id                 Id of the request
	 *
	 * @return  array                       List of request line
     *
     * @throws  401     RestException       Insufficient rights
     * @throws  403     RestException       Access unauthorized
     * @throws  404     RestException       Request not found
     * @throws  500     RestException       Error when retrieve request
     * @throws  500     RestException       Error when retrieve the request lines
	 */
	function getLines($id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        if ($requestmanager->getLinesArray() < 0) {
            throw new RestException(500, "Error when retrieve the request lines", [ 'details' => $this->_getErrors($requestmanager) ]);
        }

        $result = array();
        foreach ($requestmanager->lines as $line) {
            array_push($result, $this->_cleanObjectData($line));
        }

        return $result;
    }

    /**
     *  Add a line to the given request
     *
     * @url	POST {id}/lines
     *
     * @param   int     $id                 Id of the request
     * @param   array   $request_data       Request line data
     *
     * @return  int                         ID of the request line created
     *
     * @throws  401     RestException       Insufficient rights
     * @throws  403     RestException       Access unauthorized
     * @throws  404     RestException       Request not found
     * @throws  500     RestException       Error when retrieve request
     * @throws  500     RestException       Error while creating the request line
     */
    function postLine($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        $request_data = (object)$request_data;

        $createRes = $requestmanager->addline(
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->fk_product,
            $request_data->remise_percent,
            $request_data->info_bits,
            $request_data->fk_remise_except,
            'HT',
            0,
            $request_data->date_start,
            $request_data->date_end,
            $request_data->product_type,
            $request_data->rang,
            $request_data->special_code,
            $request_data->fk_parent_line,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->label,
            $request_data->array_options,
            $request_data->fk_unit,
            $request_data->origin,
            $request_data->origin_id,
            $request_data->multicurrency_subprice
        );

        if ($createRes > 0) {
            return $createRes;
        } else {
            throw new RestException(500, "Error while creating the request line", [ 'details' => $this->_getErrors($requestmanager) ]);
        }
    }

    /**
     *  Update a given request line
     *
     * @url	PUT {id}/lines/{line_id}
     *
     * @param   int     $id                 Id of request to update
     * @param   int     $line_id            Id of line to update
     * @param   array   $request_data       Request line data
     *
     * @return  object                      Request data with line updated
     *
     * @throws  401     RestException       Insufficient rights
     * @throws  403     RestException       Access unauthorized
     * @throws  404     RestException       Request not found
     * @throws  404     RestException       Request line not found
     * @throws  500     RestException       Error when retrieve request
     * @throws  500     RestException       Error when retrieve request line
     * @throws  500     RestException       Error while updating the request line
     */
    function putLine($id, $line_id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        $requestline = new RequestManagerLine(self::$db);
        $result = $requestline->fetch($line_id);
        if ($result == 0 || ($result > 0 && $requestline->fk_requestmanager != $id)) {
            throw new RestException(404, "Request line not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request line", [ 'details' => $this->_getErrors($requestline) ]);
        }

        $request_data = (object)$request_data;

        $updateRes = $requestmanager->updateline(
            $line_id,
            isset($request_data->desc) ? $request_data->desc : $requestline->desc,
            isset($request_data->subprice) ? $request_data->subprice : $requestline->subprice,
            isset($request_data->qty) ? $request_data->qty : $requestline->qty,
            isset($request_data->remise_percent) ? $request_data->remise_percent : $requestline->remise_percent,
            isset($request_data->tva_tx) ? $request_data->tva_tx : $requestline->tva_tx,
            isset($request_data->localtax1_tx) ? $request_data->localtax1_tx : $requestline->localtax1_tx,
            isset($request_data->localtax2_tx) ? $request_data->localtax2_tx : $requestline->localtax2_tx,
            'HT',
            isset($request_data->info_bits) ? $request_data->info_bits : $requestline->info_bits,
            isset($request_data->date_start) ? $request_data->date_start : $requestline->date_start,
            isset($request_data->date_end) ? $request_data->date_end : $requestline->date_end,
            isset($request_data->product_type) ? $request_data->product_type : $requestline->product_type,
            isset($request_data->fk_parent_line) ? $request_data->fk_parent_line : $requestline->fk_parent_line,
            0,
            isset($request_data->fk_fournprice) ? $request_data->fk_fournprice : $requestline->fk_fournprice,
            isset($request_data->pa_ht) ? $request_data->pa_ht : $requestline->pa_ht,
            isset($request_data->label) ? $request_data->label : $requestline->label,
            isset($request_data->special_code) ? $request_data->special_code : $requestline->special_code,
            isset($request_data->array_options) ? $request_data->array_options : $requestline->array_options,
            isset($request_data->fk_unit) ? $request_data->fk_unit : $requestline->fk_unit,
            isset($request_data->multicurrency_subprice) ? $request_data->multicurrency_subprice : $requestline->subprice
        );

        if ($updateRes > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the request line", [ 'details' => $this->_getErrors($requestmanager) ]);
        }
    }

    /**
     *  Delete a given request line
     *
     * @url	DELETE {id}/lines/{line_id}
     *
     * @param   int     $id                 Id of request to delete
     * @param   int     $line_id            Id of line to delete
     *
     * @return  object                      Request data with line deleted
     *
     * @throws  401     RestException       Insufficient rights
     * @throws  403     RestException       Access unauthorized
     * @throws  404     RestException       Request not found
     * @throws  404     RestException       Request line not found
     * @throws  500     RestException       Error when retrieve request
     * @throws  500     RestException       Error when retrieve request line
     * @throws  500     RestException       Error while deleting the request line
     */
    function deleteLine($id, $line_id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        $requestline = new RequestManagerLine(self::$db);
        $result = $requestline->fetch($line_id);
        if ($result == 0 || ($result > 0 && $requestline->fk_requestmanager != $id)) {
            throw new RestException(404, "Request line not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request line", [ 'details' => $this->_getErrors($requestmanager) ]);
        }

        if ($requestmanager->deleteline($line_id) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while deleting the request line", [ 'details' => $this->_getErrors($requestmanager) ]);
        }
    }

    /**
     *  Get the request message
     *
     * @url	GET {id}/message/{message_id}
     *
     * @param   int             $id                 ID of the request
     * @param   int             $message_id         ID of the request message (event)
     *
     * @return  object|array                        Request message data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  403             RestException       Access unauthorized
     * @throws  404             RestException       Request not found
     * @throws  404             RestException       Request message not found
     * @throws  500             RestException       Error when retrieve request
     * @throws  500             RestException       Error when retrieve request message
     */
    function getMessage($id, $message_id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        $requestmanager_message = new RequestManagerMessage(self::$db);
        $result = $requestmanager_message->fetch($message_id);
        if ($result == 0 || ($result > 0 && ($requestmanager_message->elementtype != $requestmanager->element || $requestmanager_message->fk_element != $requestmanager->id))) {
            throw new RestException(404, "Request message not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request message", [ 'details' => $this->_getErrors($requestmanager_message) ]);
        } elseif ($requestmanager_message->id > 0) {
            if ($requestmanager_message->message_type != -1) {
                $requestmanager_message->fetch_knowledge_base();
                $requestmanager_message->fetch_optionals();
                $requestmanager_message->fetch_contact();
                $requestmanager_message->fetch_thirdparty();
                $this->_fetch_event_users($requestmanager_message);
            } else {
                throw new RestException(404, "Request not found");
            }
        } else {
            throw new RestException(403, "Access unauthorized");
        }

        return $this->_cleanObjectData($requestmanager_message);
    }

    /**
     *  Create a request message
     *
     * @url	POST {id}/message
     *
     * @param   int         $id                         ID of the request
     * @param   array       $request_message_data       Request message data
     *
     * @return  array                                   ID of the request message created and errors of the notification by email
     *
     * @throws  400         RestException               Field missing
     * @throws  403         RestException               Access unauthorized
     * @throws  401         RestException               Insufficient rights
     * @throws  500         RestException               Error while adding attached files
     * @throws  500         RestException               Error while creating the request message
     */
    function postMessage($id, $request_message_data = null)
    {
        global $user;

        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        // Check mandatory fields
        $this->_validateMessage($request_message_data);

        dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
        $formrequestmanagermessage = new FormRequestManagerMessage(self::$db, $requestmanager);

        // Add attached files
        if (isset($request_message_data['attached_files'])) {
            foreach ($request_message_data['attached_files'] as $file) {
                if ($this->_addAttachedFile($requestmanager, $formrequestmanagermessage, $file) < 0) {
                    $formrequestmanagermessage->remove_all_attached_files();
                    throw new RestException(500, "Error while adding attached files", [ 'details' => $this->_getErrors($this) ]);
                }
            }

            $request_message_data['attached_files'] = $formrequestmanagermessage->get_attached_files();
        }

        $requestmanager_message = new RequestManagerMessage(self::$db);
        foreach ($request_message_data as $field => $value) {
            $requestmanager_message->$field = $value;
        }
        $requestmanager_message->requestmanager = $requestmanager;

        $save_user = $user;
        $user = DolibarrApiAccess::$user;
        if ($requestmanager_message->create(DolibarrApiAccess::$user) < 0) {
            $user = $save_user;
            $formrequestmanagermessage->remove_all_attached_files();
            throw new RestException(500, "Error while creating the request message", [ 'details' => $this->_getErrors($requestmanager_message) ]);
        }
        $user = $save_user;

        $formrequestmanagermessage->remove_all_attached_files();
        $errors = array();
        if (!empty($requestmanager_message->context['send_notify_errors'])) {
            $errors = $requestmanager_message->context['send_notify_errors'];

            function convert($item)
            {
                return dol_html_entity_decode($item, ENT_QUOTES);
            }

            $errors = array_map('convert', $errors);
        }

        return array('id' => $requestmanager_message->id, "notify_errors" => $errors);
    }

    /**
     *  Get the list of the events of the request
     *
     * @url	GET {id}/events
     *
     * @param   int         $id                                         ID of the request
     * @param   string	    $sort_field                                 Sort field
     * @param   string	    $sort_order                                 Sort order
     * @param   int		    $limit		                                Limit for list
     * @param   int		    $page		                                Page number
     * @param   string      $sql_filters                                Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     * @param   int		    $only_message		                        1=Return only request message of the request
     * @param   int		    $only_linked_to_request		                1=Return only linked events to the request
     * @param   int		    $include_events_other_request		        1=Return also events of other request (taken into account if only_linked_to_request = 0)
     * @param   int		    $include_linked_events_children_request	    1=Return also events of children request
     *
     * @return  array                                                   Array of order objects
     *
     * @throws  400         RestException                               Error when validating parameter 'sqlfilters'
     * @throws  401         RestException                               Insufficient rights
     * @throws  403         RestException                               Access unauthorized
     * @throws  404         RestException                               Request not found
     * @throws  500         RestException                               Error when retrieve request
     * @throws  500         RestException                               Error when retrieve tags list of the user
     * @throws  500         RestException                               Error when retrieve request list
     */
    function indexEvents($id, $sort_field="t.datec", $sort_order='DESC', $limit=100, $page=0, $sql_filters='', $only_message=0, $only_linked_to_request=1, $include_events_other_request=0, $include_linked_events_children_request=1)
    {
        global $conf;

        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        // Get request ids (parent + children)
        $request_children_ids = $requestmanager->getAllChildrenRequest();
        if ($include_linked_events_children_request) {
            $request_ids = array_merge($request_children_ids, array($requestmanager->id));
            $request_ids = implode(',', $request_ids);
        } else {
            $request_ids = $requestmanager->id;
        }
        $request_children_ids = implode(',', $request_children_ids);

        $sql = "SELECT t.id, t.code";
        // Event confidentiality support
        if ($conf->eventconfidentiality->enabled) {
            dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
            $sql .= ", MIN(IFNULL(ecm.mode, " . EventConfidentiality::MODE_HIDDEN . ")) as ec_mode";
        }
        $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as t";
        if (!$only_message) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee";
            $element_correspondance = "(" . // Todo a completer si il y a d'autres correspondances
                "IF(t.elementtype = 'contract', 'contrat', " .
                " IF(t.elementtype = 'invoice', 'facture', " .
                "  IF(t.elementtype = 'order', 'commande', " .
                "   t.elementtype)))" .
                ")";
            $sql .= " ON (ee.sourcetype = " . $element_correspondance . " AND ee.fk_source = t.fk_element) OR (ee.targettype = " . $element_correspondance . " AND ee.fk_target = t.fk_element)";
        }
        // Event confidentiality support
        if ($conf->eventconfidentiality->enabled) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "eventconfidentiality_mode as ecm ON ecm.fk_actioncomm = t.id";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_eventconfidentiality_tag AS cect ON ecm.fk_c_eventconfidentiality_tag = cect.rowid";
        }
        $sql .= ' WHERE t.entity IN (' . getEntity('agenda') . ')';
        if (!$only_message) {
            $soc_ids = array_merge(array($requestmanager->socid_origin), array($requestmanager->socid), array($requestmanager->socid_benefactor));
            $sql .= ' AND t.fk_soc IN (' . implode(',', $soc_ids) . ')';
            if ($only_linked_to_request) {
                $sql .= " AND IF(t.elementtype='requestmanager', t.fk_element, IF(ee.targettype='requestmanager', ee.fk_target, IF(ee.sourcetype='requestmanager', ee.fk_source, NULL))) IN(" . (!empty($request_ids) ? $request_ids : '-1') . ")";
            } else {
                if (!$include_events_other_request) {
                    $sql .= " AND (t.elementtype != 'requestmanager' OR t.fk_element IN (" . (!empty($request_ids) ? $request_ids : '-1') . "))";
                }
                if (!$include_linked_events_children_request) {
                    $sql .= " AND IF(t.elementtype='requestmanager', t.fk_element, IF(ee.targettype='requestmanager', ee.fk_target, IF(ee.sourcetype='requestmanager', ee.fk_source, NULL))) NOT IN (" . (!empty($request_children_ids) ? $request_children_ids : '-1') . ")";
                }
            }
        } else {
            $sql .= " AND t.elementtype = 'requestmanager' AND t.fk_element IN (" . $request_ids . ")";
            $sql .= " AND (t.code = 'AC_RM_PRIV' OR t.code = 'AC_RM_IN' OR t.code = 'AC_RM_OUT')";
        }
        if (DolibarrApiAccess::$user->socid > 0) {
            $sql .= " AND t.code != 'AC_RM_PRIV'";
        }
        // Event confidentiality support
        if ($conf->eventconfidentiality->enabled) {
            $eventconfidentiality = new EventConfidentiality(self::$db);
            $tags_list = $eventconfidentiality->getConfidentialTagsOfUser(DolibarrApiAccess::$user);
            if (!is_array($tags_list)) {
                throw new RestException(500, 'Error when retrieve tags list of the user', [ 'details' => $this->_getErrors($eventconfidentiality) ]);
            }
            if (DolibarrApiAccess::$user->socid > 0) {
                $sql .= ' AND cect.external = 1';
            } else {
                $sql .= ' AND (cect.external IS NULL OR cect.external = 0)';
            }
            $sql .= ' AND (ecm.fk_c_eventconfidentiality_tag IN (' . (count($tags_list) > 0 ? implode(',', $tags_list) : -1) . ')';
            if (DolibarrApiAccess::$user->socid == 0) {
                $sql .= ' OR ecm.rowid IS NULL';
            }
            $sql .= ')';
        }
        // Add sql filters
        if ($sql_filters) {
            if (!DolibarrApi::_checkFilters($sql_filters)) {
                throw new RestException(400, 'Error when validating parameter \'sql_filters\': ' . $sql_filters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sql_filters) . ")";
        }

        $sql .= " GROUP BY t.id";
        // Event confidentiality support
        if ($conf->eventconfidentiality->enabled && DolibarrApiAccess::$user->socid > 0) {
            $sql .= ' HAVING ec_mode != ' . EventConfidentiality::MODE_HIDDEN;
        }

        // Set Order and Limit
        $sql .= self::$db->order($sort_field.', t.id', $sort_order.','.$sort_order);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= self::$db->plimit($limit, $offset);
        }

        $resql = self::$db->query($sql);
        if ($resql) {
            require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
            while ($obj = self::$db->fetch_object($resql)) {
                if ($obj->code == 'AC_RM_PRIV' || $obj->code == 'AC_RM_IN' || $obj->code == 'AC_RM_OUT') {
                    $requestmanager_message = new RequestManagerMessage(self::$db);
                    if ($requestmanager_message->fetch($obj->id) > 0 && $requestmanager_message->id > 0) {
                        $requestmanager_message->fetch_knowledge_base();
                        $requestmanager_message->fetch_optionals();
                        $requestmanager_message->fetch_contact();
                        $requestmanager_message->fetch_thirdparty();
                        $this->_fetch_event_users($requestmanager_message);
                        $obj_ret[] = $this->_cleanObjectData($requestmanager_message);
                    }
                } else {
                    $event = new ActionComm(self::$db);
                    if ($event->fetch($obj->id) > 0 && $event->id > 0) {
                        $event->fetch_optionals();
                        $event->fetch_contact();
                        $event->fetch_thirdparty();
                        $this->_fetch_event_users($event);
                        $obj_ret[] = $this->_cleanObjectData($event);
                    }
                }
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve request events list", [ 'details' => [ self::$db->lasterror() ]]);
        }

        return $obj_ret;
    }

    /**
     *  Set request status
     *
     * @url	POST {id}/set_status
     *
     * @param   int     $id                         ID of the request
     * @param   int     $status_id                  New status (ID of a status defined into the dictionary)
     * @param   int     $status_type                New status type (0=Initial, 1=In progress, 2=Resolved, 3=Closed)
     * @param   int     $reason_resolution_id       Id of the reason for resolution (when set STATUS_RESOLVED)
     * @param   string  $reason_resolution_details  Details of the reason for resolution (when set STATUS_RESOLVED)
     * @param   int     $no_trigger                 1=Does not execute triggers, 0= execute triggers
     * @param   int     $force_reload               1=Force reload the cache of the list of status
     *
     * @return  object                              Request data updated
     *
     * @throws  401     RestException               Insufficient rights
     * @throws  403     RestException               Access unauthorized
     * @throws  404     RestException               Request not found
     * @throws  500     RestException               Error when retrieve request
     * @throws  500     RestException               Error while setting the request status
     */
    function setStatus($id, $status_id=0, $status_type=-1, $reason_resolution_id=0, $reason_resolution_details='', $no_trigger=0, $force_reload=0)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        if ($requestmanager->set_status($status_id, $status_type, DolibarrApiAccess::$user, $reason_resolution_id, $reason_resolution_details, $no_trigger, $force_reload) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while setting the request status", [ 'details' => $this->_getErrors($requestmanager) ]);
        }
    }

    /**
     *  Create a calling event
     *
     * @url	POST /call/{unique_id}/begin
     *
     * @param   string      $unique_id          Unique ID of the call
     * @param   string      $caller_id_num      Caller's number
     * @param   string      $called_num         Called number
     * @param   string      $direction          Direction of the call
     * @param   string      $channel            Channel of the call
     * @param   string      $caller_id_name     Caller's name
     * @param   string      $context            Context of the call
     * @param   string      $extension          Extension of the call
     * @param   string      $begin_ask_hour     Begin ask hour of the call
     * @param   string      $transfer_suffix    Transfer suffix of the call
     *
     * @return  int                             ID of the calling event
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  500         RestException       Error when retrieve company / contact
     * @throws  500         RestException       Error when retrieve company information
     * @throws  500         RestException       Error when retrieve internal user
     * @throws  500         RestException       Error while creating the calling event
     */
    function CallBegin($unique_id, $caller_id_num, $called_num, $direction, $channel='', $caller_id_name='', $context='', $extension='', $begin_ask_hour='', $transfer_suffix='')
    {
        global $conf, $langs;

        if (!DolibarrApiAccess::$user->rights->agenda->myactions->create) {
            throw new RestException(401, "Insufficient rights");
        }

        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

        $nb_number = !empty($conf->global->REQUESTMANAGER_NB_NUMBER_FOR_COMPARE_PHONE) ? $conf->global->REQUESTMANAGER_NB_NUMBER_FOR_COMPARE_PHONE : 9;
        $now = dol_now();

        // Clean parameters
        $from_num = preg_replace("/\D/", "", $caller_id_num);
        $from_num = substr($from_num, -$nb_number);
        $target_num = preg_replace("/\D/", "", $called_num);
        $target_num = substr($target_num, -$nb_number);

        // Search contact / company
        //---------------------------------------
        $socid = 0;
        $contactid = 0;

        // Set filters for phones
        $phones = array();
        // s.phone
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(s.phone, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(s.phone, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
        // s.fax
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(s.fax, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(s.fax, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
        // sc.phone
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.phone, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.phone, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
        // sc.phone_perso
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.phone_perso, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.phone_perso, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
        // sc.phone_mobile
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.phone_mobile, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.phone_mobile, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
        // sc.fax
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.fax, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(sc.fax, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";

        // Set filters for phones into extra fields
        $extrafields = new ExtraFields(self::$db);
        $extralabels = $extrafields->fetch_name_optionals_label('societe');
        foreach ($extrafields->attributes['societe']['type'] as $key => $type) {
            if ($type == '') {
                $phones[] = "RIGHT(RM_GLOBAL_TRIM(sef.$key, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
                $phones[] = "RIGHT(RM_GLOBAL_TRIM(sef.$key, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
            }
        }
        $extrafields = new ExtraFields(self::$db);
        $extralabels = $extrafields->fetch_name_optionals_label('socpeople');
        foreach ($extrafields->attributes['socpeople']['type'] as $key => $type) {
            if ($type == '') {
                $phones[] = "RIGHT(RM_GLOBAL_TRIM(spef.$key, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
                $phones[] = "RIGHT(RM_GLOBAL_TRIM(spef.$key, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
            }
        }

        $sql = "SELECT DISTINCT s.rowid AS socid, sc.rowid AS contactid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe AS s";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields AS sef ON sef.fk_object = s.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople AS sc ON sc.fk_soc = s.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople_extrafields AS scef ON scef.fk_object = sc.rowid";
        $sql .= " WHERE s.entity IN (" . getEntity('societe') . ")";
        $sql .= " AND (" . implode(' OR ', $phones) . ")";

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                $socid = $obj->socid;
                $contactid = $obj->contactid;
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve company / contact", ['details' => [self::$db->lasterror()]]);
        }

        if ($socid > 0) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $societe = new Societe(self::$db);
            $result = $societe->fetch($socid);
            if ($result < 0) {
                throw new RestException(500, "Error when retrieve company information", ['details' => $this->_getErrors($requestmanager)]);
            }
        }

        // Search internal user
        //---------------------------------------
        $userid = DolibarrApiAccess::$user->id;
        //$userassigned = array();

        // Set filters for phones
        $phones = array();
        // u.office_phone
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(u.office_phone, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(u.office_phone, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
        // u.office_fax
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(u.office_fax, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(u.office_fax, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
        // u.user_mobile
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(u.user_mobile, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
        $phones[] = "RIGHT(RM_GLOBAL_TRIM(u.user_mobile, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";

        // Set filters for phones into extra fields
        $extrafields = new ExtraFields(self::$db);
        $extralabels = $extrafields->fetch_name_optionals_label('user');
        foreach ($extrafields->attributes['user']['type'] as $key => $type) {
            if ($type == '') {
                $phones[] = "RIGHT(RM_GLOBAL_TRIM(uef.$key, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$from_num'";
                $phones[] = "RIGHT(RM_GLOBAL_TRIM(uef.$key, '0123456789'), $nb_number) COLLATE utf8_general_ci = '$target_num'";
            }
        }

        $sql = "SELECT u.rowid AS userid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "user AS u";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user_extrafields AS uef ON uef.fk_object = u.rowid";
        $sql .= " WHERE u.entity IN (" . getEntity('user') . ")";
        $sql .= " AND (u.fk_soc = 0 OR u.fk_soc IS NULL)";
        $sql .= " AND (" . implode(' OR ', $phones) . ")";

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                $userid = $obj->userid;
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve internal user", ['details' => [self::$db->lasterror()]]);
        }

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm(self::$db);

        // Begin date
        //--------------------------------------------------
        $begin_date = new DateTime($begin_ask_hour);
        if (isset($begin_date)) {
            $begin_date = $begin_date->getTimestamp();
        } else {
            $begin_date = $now;
        }

        // Create event
        //--------------------------------------------------
        $langs->load('commercial');
        $langs->load('requestmanager@requestmanager');
        $actioncomm->type_code = "AC_TEL";
        $actioncomm->location = $channel;
        $actioncomm->datep = $begin_date;
        $actioncomm->percentage = 50;
        //$actioncomm->userassigned = $userassigned;
        $actioncomm->socid = $socid;
        $actioncomm->contactid = $contactid;
        $actioncomm->userownerid = $userid;
        $actioncomm->array_options = array("options_rm_ipbx_id" => $unique_id);

        // Title
        //--------------------------------------------------
        $actioncomm->label = $langs->trans('ActionAC_TEL') . ': ' . $direction . ' - ' . ($socid > 0 ? $societe->getFullName($langs) : $caller_id_num . ' ' . $langs->trans('toward') . ' ' . $called_num) .
            ' - ' . $langs->trans('ActionRunningShort');

        // Message
        //--------------------------------------------------
        $message = $langs->trans('RequestManagerIPBXUniqueID', $unique_id) . '<br>';
        if (!empty($channel)) $message .= $langs->trans('RequestManagerIPBXChannel', $channel) . '<br>';
        $message .= $langs->trans('RequestManagerIPBXCallerIDNum', $caller_id_num) . '<br>';
        if (!empty($caller_id_name)) $message .= $langs->trans('RequestManagerIPBXCallerIDName', $caller_id_name) . '<br>';
        $message .= $langs->trans('RequestManagerIPBXCalledNum', $called_num) . '<br>';
        $message .= $langs->trans('RequestManagerIPBXDirection', $direction) . '<br>';
        if (!empty($context)) $message .= $langs->trans('RequestManagerIPBXContext', $context) . '<br>';
        if (!empty($extension)) $message .= $langs->trans('RequestManagerIPBXExtension', $extension) . '<br>';
        if (!empty($begin_ask_hour)) $message .= $langs->trans('RequestManagerIPBXBeginAskHour', $begin_ask_hour) . '<br>';
        if (!empty($transfer_suffix)) $message .= $langs->trans('RequestManagerIPBXTransferSuffix', $transfer_suffix) . '<br>';
        $actioncomm->note = $message;

        if ($actioncomm->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while creating the calling event", ['details' => $this->_getErrors($actioncomm)]);
        }

        return $actioncomm->id;
    }

    /**
     *  Close a calling event
     *
     * @url	PUT /call/{unique_id}/ending
     *
     * @param   string      $unique_id              Unique ID of the call
     * @param   string      $caller_id_num          Caller's number
     * @param   string      $called_num             Called number
     * @param   string      $direction              Direction of the call
     * @param   string      $channel                Channel of the call
     * @param   string      $caller_id_name         Caller's name
     * @param   string      $context                Context of the call
     * @param   string      $extension              Extension of the call
     * @param   string      $begin_ask_hour         Begin ask hour of the call
     * @param   string      $transfer_suffix        Transfer suffix of the call
     * @param   string      $end_hour               End hour of the call
     * @param   boolean     $answered               Has the call been answered ?
     * @param   string      $privilege              Privilege
     * @param   string      $connected_line_num     Connected line num
     * @param   string      $connected_line_name    Connected line name
     * @param   boolean     $account_code           Does the call have an account code ?
     * @param   string      $channel_state          Channel state
     * @param   string      $channel_state_desc     Channel state description
     * @param   string      $priority               Priority
     * @param   string      $seconds                Seconds
     * @param   string      $id                     ID
     * @param   string      $from                   From ID
     * @param   string      $from_channel           From channel
     * @param   string      $to                     To ID
     * @param   string      $to_channel             To channel
     * @param   string      $type                   Type
     * @param   string      $class                  Class
     * @param   string      $dest_type              Destination type
     * @param   string      $direction_id           Direction ID
     * @param   string      $date_call              Date
     * @param   string      $duration               Duration
     * @param   string      $bill_sec               Bill sec
     * @param   string      $cost                   Cost
     * @param   string      $tags                   Tags
     * @param   string      $pbx                    PBX
     * @param   string       $user_field             User field
     * @param   string      $fax_data               Fax data
     *
     * @return  int                                 ID of the calling event
     *
     * @throws  401         RestException           Insufficient rights
     * @throws  403         RestException           Access unauthorized
     * @throws  404         RestException           Calling event not found with IPBX ID
     * @throws  500         RestException           Error when retrieve calling event
     * @throws  500         RestException           Error when retrieve company information
     * @throws  500         RestException           Error while closing the calling event
     */
    function CallEnding($unique_id, $caller_id_num, $called_num, $direction, $channel='', $caller_id_name='', $context='', $extension='', $begin_ask_hour='', $transfer_suffix='',
                        $end_hour='', $answered = false, $privilege='', $connected_line_num='', $connected_line_name='', $account_code=false, $channel_state='',
                        $channel_state_desc='', $priority='', $seconds='', $id='', $from='', $from_channel='', $to='', $to_channel='', $type='', $class='', $dest_type='',
                        $direction_id='', $date_call='', $duration='', $bill_sec='', $cost='', $tags='', $pbx='', $user_field='', $fax_data='')
    {
        global $langs;

        if (!DolibarrApiAccess::$user->rights->agenda->myactions->create) {
            throw new RestException(401, "Insufficient rights");
        }

        $now = dol_now();

        // Search calling event
        //--------------------------------------------------
        $actioncommid = 0;

        $sql = "SELECT ac.id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm AS ac";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields AS acef ON acef.fk_object = ac.id";
        $sql .= " WHERE acef.rm_ipbx_id = '" . self::$db->escape($unique_id) . "'";

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                $actioncommid = $obj->id;
            }

            self::$db->free($resql);
        }

        if ($actioncommid == 0) {
            throw new RestException(404, "Calling event not found with IPBX Unique ID: " . $unique_id);
        }

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm(self::$db);
        $result = $actioncomm->fetch($actioncommid);
        if ($result < 0) {
            throw new RestException(500, "Error when retrieve calling event", [ 'details' => $this->_getErrors($actioncomm) ]);
        } elseif (!($actioncomm->id > 0)) {
            throw new RestException(403, "Access unauthorized");
        }

        // Get company
        //--------------------------------------------------
        if ($actioncomm->socid > 0) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $societe = new Societe(self::$db);
            $result = $societe->fetch($actioncomm->socid);
            if ($result < 0) {
                throw new RestException(500, "Error when retrieve company information", [ 'details' => $this->_getErrors($requestmanager) ]);
            }
        }

        // Begin date
        //--------------------------------------------------
        $begin_date = !empty($begin_ask_hour) ? new DateTime($begin_ask_hour) : null;
        if (isset($begin_date)) {
            $begin_date = $begin_date->getTimestamp();
        } else {
            $begin_date = $actioncomm->datep;
        }

        // End date
        //--------------------------------------------------
        $end_date = !empty($end_hour) ? new DateTime($end_hour) : null;
        if (isset($end_date)) {
            $end_date = $end_date->getTimestamp();
        } elseif (!empty($duration)) {
            $end_date = $begin_date + intval($duration);
        } else {
            $end_date = $now;
        }

        // Update event
        //--------------------------------------------------
        $langs->load('commercial');
        $langs->load('requestmanager@requestmanager');
        $actioncomm->percentage = 100;
        $actioncomm->datep = $begin_date;
        $actioncomm->datef = $end_date;
        $actioncomm->durationp = $end_date - $begin_date;

        // Title
        //--------------------------------------------------
        $actioncomm->label = $langs->trans('ActionAC_TEL') . ': ' . $direction . ' - ' . ($actioncomm->socid > 0 ? $societe->getFullName($langs) : $caller_id_num . ' ' . $langs->trans('toward') . ' ' . $called_num) .
            ' - ' . $langs->trans('RequestManagerHourFromTo', dol_print_date($actioncomm->datep, 'hour'), dol_print_date($actioncomm->datef, 'hour'));

        // Message
        //--------------------------------------------------
        $message = $langs->trans('RequestManagerIPBXUniqueID', $unique_id) . '<br>';
        if (!empty($channel)) $message.= $langs->trans('RequestManagerIPBXChannel', $channel) . '<br>';
        $message.= $langs->trans('RequestManagerIPBXCallerIDNum', $caller_id_num) . '<br>';
        if (!empty($caller_id_name)) $message.= $langs->trans('RequestManagerIPBXCallerIDName', $caller_id_name) . '<br>';
        $message.= $langs->trans('RequestManagerIPBXCalledNum', $called_num) . '<br>';
        $message.= $langs->trans('RequestManagerIPBXDirection', $direction) . '<br>';
        if (!empty($context)) $message.= $langs->trans('RequestManagerIPBXContext', $context) . '<br>';
        if (!empty($extension)) $message.= $langs->trans('RequestManagerIPBXExtension', $extension) . '<br>';
        if (!empty($begin_ask_hour)) $message.= $langs->trans('RequestManagerIPBXBeginAskHour', $begin_ask_hour) . '<br>';
        if (!empty($transfer_suffix)) $message.= $langs->trans('RequestManagerIPBXTransferSuffix', $transfer_suffix) . '<br>';
        if (!empty($end_hour)) $message.= $langs->trans('RequestManagerIPBXEndHour', $end_hour) . '<br>';
        if (isset($answered)) $message.= $langs->trans('RequestManagerIPBXAnswered', yn($answered)) . '<br>';
        if (!empty($privilege)) $message.= $langs->trans('RequestManagerIPBXPrivilege', $privilege) . '<br>';
        if (!empty($connected_line_num)) $message.= $langs->trans('RequestManagerIPBXConnectedLineNum', $connected_line_num) . '<br>';
        if (!empty($connected_line_name)) $message.= $langs->trans('RequestManagerIPBXConnectedLineName', $connected_line_name) . '<br>';
        if (!empty($account_code)) $message.= $langs->trans('RequestManagerIPBXAccountCode', yn($account_code)) . '<br>';
        if (!empty($channel_state)) $message.= $langs->trans('RequestManagerIPBXChannelState', $channel_state) . '<br>';
        if (!empty($channel_state_desc)) $message.= $langs->trans('RequestManagerIPBXChannelStateDesc', $channel_state_desc) . '<br>';
        if (!empty($priority)) $message.= $langs->trans('RequestManagerIPBXPriority', $priority) . '<br>';
        if (!empty($seconds)) $message.= $langs->trans('RequestManagerIPBXSeconds', $seconds) . '<br>';
        if (!empty($id)) $message.= $langs->trans('RequestManagerIPBXId', $id) . '<br>';
        if (!empty($from)) $message.= $langs->trans('RequestManagerIPBXFrom', $from) . '<br>';
        if (!empty($from_channel)) $message.= $langs->trans('RequestManagerIPBXFromChannel', $from_channel) . '<br>';
        if (!empty($to)) $message.= $langs->trans('RequestManagerIPBXTo', $to) . '<br>';
        if (!empty($to_channel)) $message.= $langs->trans('RequestManagerIPBXToChannel', $to_channel) . '<br>';
        if (!empty($type)) $message.= $langs->trans('RequestManagerIPBXType', $type) . '<br>';
        if (!empty($class)) $message.= $langs->trans('RequestManagerIPBXClass', $class) . '<br>';
        if (!empty($dest_type)) $message.= $langs->trans('RequestManagerIPBXDestinationType', $dest_type) . '<br>';
        if (isset($direction_id)) $message.= $langs->trans('RequestManagerIPBXDirectionId', $direction_id) . '<br>';
        if (isset($date_call)) $message.= $langs->trans('RequestManagerIPBXDate', $date_call) . '<br>';
        if (isset($duration)) $message.= $langs->trans('RequestManagerIPBXDuration', $duration) . '<br>';
        if (isset($bill_sec)) $message.= $langs->trans('RequestManagerIPBXBillSec', $bill_sec) . '<br>';
        if (!empty($cost)) $message.= $langs->trans('RequestManagerIPBXCost', $cost) . '<br>';
        if (!empty($tags)) $message.= $langs->trans('RequestManagerIPBXTags', $tags) . '<br>';
        if (!empty($pbx)) $message.= $langs->trans('RequestManagerIPBXPbx', $pbx) . '<br>';
        if (!empty($user_field)) $message.= $langs->trans('RequestManagerIPBXUserField') . '<br>' . $user_field . '';
        if (!empty($fax_data)) $message.= $langs->trans('RequestManagerIPBXFaxData') . '<br>' . $fax_data . '';
        $actioncomm->note = $message;

        if ($actioncomm->update(DolibarrApiAccess::$user) > 0) {
            return $actioncomm->id;
        } else {
            throw new RestException(500, "Error while closing the calling event", [ 'details' => $this->_getErrors($actioncomm) ]);
        }
    }

    /**
     *  Get request object with authorization
     *
     * @param   int             $request_id         Id of the request
     *
     * @return  RequestManager
     *
     * @throws  403             RestException       Access unauthorized
     * @throws  404             RestException       Request not found
     * @throws  500             RestException       Error when retrieve request
     */
    function _getRequestManagerObject($request_id)
    {
        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($request_id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", [ 'details' => $this->_getErrors($requestmanager) ]);
        }

        $socid = DolibarrApiAccess::$user->socid;
        if ($socid > 0 && $socid != $requestmanager->socid && $socid != $requestmanager->socid_benefactor) {
            throw new RestException(403, "Access unauthorized");
        }

        return $requestmanager;
    }

    /**
     *  Validate fields before create or update object
     *
     * @param   array   $data               Array with data to verify
     *
     * @return  void
     *
     * @throws  400     RestException       Field missing
     */
    function _validate($data)
    {
        foreach (self::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "Field missing: $field");
        }
    }

    /**
     *  Validate fields before create or update message object
     *
     * @param   array   $data               Array with data to verify
     *
     * @return  void
     *
     * @throws  400     RestException       Field missing
     */
    function _validateMessage($data)
    {
        foreach (self::$MESSAGE_FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "Field missing: $field");
        }
    }

    /**
     *  Fetch all users of the event
     *
     * @param   object          $object         Object to fetch
     *
     * @return  void
     **/
    function _fetch_event_users(&$object)
    {
        $object->fetch_userassigned();

        $object->user_mod = $this->_fetch_user($object->usermodid);
        $object->user_done = $this->_fetch_user($object->userdoneid);
        $object->user_owner = $this->_fetch_user($object->userownerid);

        foreach ($object->userassigned as $key => $user_infos) {
            $user = $this->_fetch_user($user_infos['id']);
            $object->userassigned[$key]['user'] = $user;
        }
    }

    /**
     *  Fetch user object
     *
     * @param   int          $id         ID of the user
     *
     * @return  object
     **/
    function _fetch_user($id)
    {
        $result = null;

        if ($id > 0) {
            require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
            $user = new User(self::$db);
            if ($user->fetch($id) > 0) {
                $result = $user;
            }
        }

        return $result;
    }

    /**
     * Add attached file for message
     *
     * @param  RequestManager               $requestmanager                 Handler RequestManager
     * @param  FormRequestManagerMessage    $formrequestmanagermessage      Handler FormRequestManagerMessage
     * @param  array                        $file_info                      Information of the file
     *
     * @return int                                                          >0 if OK, <0 if not OK
     *
     * @throws
     */
	function _addAttachedFile(&$requestmanager, &$formrequestmanagermessage, $file_info)
    {
        global $conf, $langs;

        $filename = $file_info['name'];
        $filecontent = $file_info['content'];
        $fileencoding = $file_info['encoding'];

        $newfilecontent = '';
        if (empty($fileencoding)) $newfilecontent = $filecontent;
        if ($fileencoding == 'base64') $newfilecontent = base64_decode($filecontent);
        $original_file = dol_sanitizeFileName($filename);

        // Set tmp user directory
        $vardir = $conf->user->dir_output . "/" . DolibarrApiAccess::$user->id;
        $upload_dir_tmp = $vardir . '/temp/rm-' . $requestmanager->id . '/'; // TODO Add $keytoavoidconflict in upload_dir path
        $upload_file_tmp = $upload_dir_tmp . '/' . $original_file;

        // Security:
        // Disallow file with some extensions. We rename them.
        // Because if we put the documents directory into a directory inside web root (very bad), this allows to execute on demand arbitrary code.
        if (preg_match('/\.htm|\.html|\.php|\.pl|\.cgi$/i', $upload_file_tmp) && empty($conf->global->MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED)) {
            $upload_file_tmp .= '.noexe';
            $original_file .= '.noexe';
        }

        // Security:
        // We refuse cache files/dirs, upload using .. and pipes into filenames.
        if (preg_match('/^\./', $original_file) || preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
            dol_syslog("Refused to deliver file " . $filename, LOG_WARNING);
            $this->errors[] = 'Refused to deliver file "' . $filename . '".';
            return -1;
        }

        if (dol_mkdir($upload_dir_tmp) < 0) {
            $this->errors[] = "Error when create temporary directory.";
            return -2;
        }

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        if (!dol_is_dir($upload_dir_tmp)) {
            $this->errors[] = 'Directory not exists : "' . $upload_dir_tmp . '".';
            return -3;
        }

        if (dol_is_file($upload_file_tmp)) {
            $this->errors[] = "File with name '" . $original_file . "' already exists.";
            return -4;
        }

        $fhandle = @fopen($upload_file_tmp, 'w');
        if ($fhandle) {
            $nbofbyteswrote = fwrite($fhandle, $newfilecontent);
            fclose($fhandle);
            @chmod($upload_file_tmp, octdec($conf->global->MAIN_UMASK));
        } else {
            $this->errors[] = 'Failed to open file "' . $upload_file_tmp . '" for write.';
            return -5;
        }

        // If we need to make a virus scan
        if (file_exists($upload_file_tmp)) {
            $checkvirusarray = dolCheckVirus($upload_file_tmp);
            if (count($checkvirusarray)) {
                $langs->load("errors");
                dol_syslog(__METHOD__ . ' File "' . $upload_file_tmp . '" KO with antivirus: errors=' . join(',', $checkvirusarray), LOG_WARNING);
                $this->errors[] = $langs->trans('ErrorFileIsInfectedWithAVirus') . ' : ' . join(',', $checkvirusarray);
                return -6;
            }
        }

        // Generate thumbs. useful ?
//        if (image_format_supported($upload_file_tmp) == 1) {
//            global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;
//
//            include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
//
//            // Create thumbs
//            // We can't use $object->addThumbs here because there is no $object known
//
//            // Used on logon for example
//            $imgThumbSmall = vignette($upload_file_tmp, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
//            // Create mini thumbs for image (Ratio is near 16/9)
//            // Used on menu or for setup page for example
//            $imgThumbMini = vignette($upload_file_tmp, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
//        }

        // Update session
        $formrequestmanagermessage->add_attached_files($upload_file_tmp, $original_file, dol_mimetype($original_file));

        return 1;
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
        if (!is_array($blacklist_of_properties)) $blacklist_of_properties = array();
        $has_blacklist = count($blacklist_of_properties) > 0 && !isset($blacklist_of_properties['']);
        foreach ($object as $k => $v) {
            if (($has_whitelist && !isset($whitelist_of_properties[$k])) || ($has_blacklist && isset($blacklist_of_properties[$k]) && !is_array($blacklist_of_properties[$k]))) {
                if (is_array($object))
                    unset($object[$k]);
                else
                    unset($object->$k);
            } else {
                if (is_object($v) || is_array($v)) {
                    if (is_array($object))
                        $this->_cleanSubObjectData($object[$k], $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
                    else
                        $this->_cleanSubObjectData($object->$k, $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
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
        if (!is_array($blacklist_of_properties)) $blacklist_of_properties = array();
        $has_blacklist = count($blacklist_of_properties) > 0 && !isset($blacklist_of_properties['']);
        foreach ($object as $k => $v) {
            if (($has_whitelist && !isset($whitelist_of_properties[$k])) || ($has_blacklist && isset($blacklist_of_properties[$k]) && !is_array($blacklist_of_properties[$k]))) {
                if (is_array($object))
                    unset($object[$k]);
                else
                    unset($object->$k);
            } else {
                if (is_object($v) || is_array($v)) {
                    if (is_array($object))
                        $this->_cleanSubObjectData($object[$k], $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
                    else
                        $this->_cleanSubObjectData($object->$k, $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
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
            return dol_htmlentitiesbr_decode($item);
        }

        $errors = array_map('convert', $errors);

        return $errors;
    }
}
