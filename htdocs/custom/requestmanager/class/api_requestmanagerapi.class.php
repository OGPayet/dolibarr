<?php
/* Copyright (C) 2018	Julien Vercruysse	<julien.vercruysse@elonet.fr>
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
     * @var array       $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();

    /**
     *  Constructor
     */
    function __construct()
    {
        global $db;
        self::$db = $db;
    }

    /**
     *  Get the request
     *
     * @param   int             $id                 ID of the request
     *
     * @return  object|array                        Request data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  404             RestException       Request not found
     * @throws  500             RestException       Error when retrieve request
     */
    function get($id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        $requestmanager->fetchObjectLinked();
        return $this->_cleanObjectDatas($requestmanager);
    }

    /**
     *  Get the list of requests
     *
     * @param   string	    $sort_field         Sort field
     * @param   string	    $sort_order         Sort order
     * @param   int		    $limit		        Limit for list
     * @param   int		    $page		        Page number
     * @param   string      $benefactor_ids	    Force search of benefactor companies ids to filter requests. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   int         $only_assigned	    1=Restrict list to the request assigned to this user or his user groups
     * @param   string      $sql_filters        Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     *
     * @return  array                           Array of request objects
     *
     * @throws  400         RestException       Error when validating parameter 'sqlfilters'
     * @throws  401         RestException       Insufficient rights
     * @throws  500         RestException       Error when retrieve request list
     */
    function index($sort_field="t.rowid", $sort_order='ASC', $limit=100, $page=0, $benefactor_ids='', $only_assigned=0, $sql_filters='')
    {
        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get benefactor companies
        if (empty($benefactor_ids)) {
            $benefactor_companies_ids = array();
            if (DolibarrApiAccess::$user->socid > 0) {
                dol_include_once('/companyrelationships/class/companyrelationships.class.php');
                $companyrelationships = new CompanyRelationships(self::$db);
                $benefactor_companies_ids = $companyrelationships->getRelationships(DolibarrApiAccess::$user->socid, 1);
                $benefactor_companies_ids = is_array($benefactor_companies_ids) ? array_merge($benefactor_companies_ids, array(DolibarrApiAccess::$user->socid)) : array();
            }
        } else {
            $benefactor_companies_ids = explode(',', $benefactor_ids);
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
        // Restrict to the benefactor companies provided
        if (count($benefactor_companies_ids) > 0) {
            $sql .= " AND t.fk_soc_benefactor IN (" . implode(',', $benefactor_companies_ids) . ")";
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
                    $obj_ret[] = $this->_cleanObjectDatas($requestmanager);
                }
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve request list", self::$db->lasterror());
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

        $requestmanager = new RequestManager(self::$db);
        foreach ($request_data as $field => $value) {
            $requestmanager->$field = $value;
        }

        if ($requestmanager->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while creating the request", $this->_getErrors($requestmanager));
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
     * @throws  404         RestException   Request not found
     * @throws  500         RestException   Error when retrieve request
     * @throws  500         RestException   Error while updating the request
     */
    function put($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        $requestmanager->oldcopy = clone $requestmanager;
        foreach ($request_data as $field => $value) {
            if ($field == 'id') continue;
            $requestmanager->$field = $value;
        }

        if ($requestmanager->update(DolibarrApiAccess::$user) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the request", $this->_getErrors($requestmanager));
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
     * @throws  404     RestException   Request not found
     * @throws  500     RestException   Error when retrieve request
     * @throws  500     RestException   Error while deleting the request
     */
    function delete($id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        if ($requestmanager->delete(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while deleting the request", $this->_getErrors($requestmanager));
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
     * @throws  404     RestException       Request not found
     * @throws  500     RestException       Error when retrieve request
	 */
	function getLines($id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        $requestmanager->getLinesArray();
        $result = array();
        foreach ($requestmanager->lines as $line) {
            array_push($result, $this->_cleanLineObjectDatas($line));
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
     * @throws  404     RestException       Request not found
     * @throws  500     RestException       Error when retrieve request
     * @throws  500     RestException       Error while creating the request line
     */
    function postLine($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        $request_data = (object)$request_data;

        $updateRes = $requestmanager->addline(
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

        if ($updateRes > 0) {
            return $updateRes;
        } else {
            throw new RestException(500, "Error while creating the request line", $this->_getErrors($requestmanager));
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

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        $requestline = new RequestManagerLine(self::$db);
        $result = $requestline->fetch($line_id);
        if ($result == 0 || ($result > 0 && $requestline->fk_requestmanager != $id)) {
            throw new RestException(404, "Request line not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request line", $this->_getErrors($requestmanager));
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
            throw new RestException(500, "Error while updating the request line", $this->_getErrors($requestmanager));
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

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        $requestline = new RequestManagerLine(self::$db);
        $result = $requestline->fetch($line_id);
        if ($result == 0 || ($result > 0 && $requestline->fk_requestmanager != $id)) {
            throw new RestException(404, "Request line not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request line", $this->_getErrors($requestmanager));
        }

        if ($requestmanager->deleteline($line_id) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while deleting the request line", $this->_getErrors($requestmanager));
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

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $requestmanager_message = new ActionComm(self::$db);
        $result = $requestmanager_message->fetch($message_id);
        if ($result == 0 || ($result > 0 && ($requestmanager_message->elementtype != $requestmanager->element || $requestmanager_message->fk_element != $requestmanager->id))) {
            throw new RestException(404, "Request message not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request message", $this->_getErrors($requestmanager_message));
        }

        return $this->_cleanEventObjectDatas($requestmanager_message);
    }

    /**
     *  Create a request message
     *
     * @url	POST {id}/message
     *
     * @param   int     $id                 ID of the request
     * @param   array   $request_data       Request message data
     *
     * @return  int                         ID of the request message created
     *
     * @throws  400     RestException       Field missing
     * @throws  401     RestException       Insufficient rights
     * @throws  500     RestException       Error while creating the request message
     */
    function postMessage($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        // Todo a faire

        return 0;

        // Check mandatory fields
        /*$this->_validate($request_data);

        $requestmanager = new RequestManager(self::$db);
        foreach ($request_data as $field => $value) {
            $requestmanager->$field = $value;
        }

        if ($requestmanager->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while creating the request message", $this->_getErrors($requestmanager));
        }

        return $requestmanager->id;*/
    }

    /**
     *  Get the list of the events of the request
     *
     * @url	GET {id}/events
     *
     * @param   int         $id                 ID of the request
     * @param   string	    $sort_field         Sort field
     * @param   string	    $sort_order         Sort order
     * @param   int		    $limit		        Limit for list
     * @param   int		    $page		        Page number
     * @param   string      $sql_filters        Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     *
     * @return  array                           Array of order objects
     *
     * @throws  400         RestException       Error when validating parameter 'sqlfilters'
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Request not found
     * @throws  500         RestException       Error when retrieve request
     * @throws  500         RestException       Error when retrieve request list
     */
    function indexEvents($id, $sort_field="t.rowid", $sort_order='ASC', $limit=100, $page=0, $sql_filters='')
    {
        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        return $obj_ret;

        // Get benefactor companies
        /*if (empty($benefactor_ids)) {
            $benefactor_companies_ids = array();
            if (DolibarrApiAccess::$user->socid > 0) {
                dol_include_once('/companyrelationships/class/companyrelationships.class.php');
                $companyrelationships = new CompanyRelationships(self::$db);
                $benefactor_companies_ids = $companyrelationships->getRelationships(DolibarrApiAccess::$user->socid, 1);
                $benefactor_companies_ids = is_array($benefactor_companies_ids) ? array_merge($benefactor_companies_ids, array(DolibarrApiAccess::$user->socid)) : array();
            }
        } else {
            $benefactor_companies_ids = explode(',', $benefactor_ids);
        }

        $sql = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "requestmanager as t";
        $sql .= ' WHERE t.entity IN (' . getEntity('requestmanager') . ')';
        // Restrict to the benefactor companies provided
        if (count($benefactor_companies_ids) > 0) {
            $sql .= " AND t.fk_soc_benefactor IN (" . implode(',', $benefactor_companies_ids) . ")";
        }
        if (!(DolibarrApiAccess::$user->socid > 0)) {
            // Todo filter assigned (user/group) if not a external user ?
        }
        // Add sql filters
        if ($sql_filters) {
            if (!DolibarrApi::_checkFilters($sql_filters)) {
                throw new RestException(400, 'Error when validating parameter \'sql_filters\': ' . $sql_filters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sql_filters) . ")";
        }

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
            require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
            while ($obj = self::$db->fetch_object($resql)) {
                $event = new ActionComm(self::$db);
                if ($event->fetch($obj->rowid) > 0) {
                    $obj_ret[] = $this->_cleanEventObjectDatas($event);
                }
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve request events list", self::$db->lasterror());
        }

        return $obj_ret;*/
    }

    /**
     *  Set request status
     *
     * @url	POST {id}/set_status
     *
     * @param   int     $id             ID of the request
     * @param   int     $status_id      New status (ID of a status defined into the dictionary)
     * @param   int     $status_type    New status type (0=Initial, 1=In progress, 2=Resolved, 3=Closed)
     * @param   int     $no_trigger     1=Does not execute triggers, 0= execute triggers
     * @param   int     $force_reload   1=Force reload the cache of the list of status
     *
     * @return  object                  Request data updated
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  404     RestException   Request not found
     * @throws  500     RestException   Error when retrieve request
     * @throws  500     RestException   Error while setting the request status
     */
    function setStatus($id, $status_id=0, $status_type=-1, $no_trigger=0, $force_reload=0)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $requestmanager = new RequestManager(self::$db);
        $result = $requestmanager->fetch($id);
        if ($result == 0) {
            throw new RestException(404, "Request not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
        }

        if ($requestmanager->set_status($status_id, $status_type, DolibarrApiAccess::$user, $no_trigger, $force_reload) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while setting the request status", $this->_getErrors($requestmanager));
        }
    }
    /**
     *  Add an event following a phone call begin
     *
     * @param string   $from_num     Caller's number
     * @param string   $target_num   Called number
     * @param string   $type         Sens de l'appel : entrant, sortant, transfert interne
     * @param integer  $id_IPBX      Id interne à l'IPBX de l'appel
     * @param string   $hour         Heure de début de l'appel (renseigné par l'IPBX, si non renseigné fait par dolibarr)
     * @param float    $poste        Poste ayant décroché/émis l'appel
     * @param string   $name_post    Nom du compte associé au poste
     * @param string   $groupe       Groupe de réponse à l'origine de l'appel
     * @param string   $source       Appel source : id interne IPBX
     *
     * @url	GET /call/begin
     *
     * @return  object
     */
    function CallBegin($from_num, $target_num, $type, $id_IPBX, $hour='', $poste=NULL, $name_post='', $groupe='', $source=NULL)
    {
        global $langs;

        $now = dol_now();

        $langs->load('requestmanager@requestmanager');

        $from_num = trim($from_num);
        $from_num = preg_replace("/\s/", "", $from_num);
        $from_num = preg_replace("/\./", "", $from_num);

        $target_num = trim($target_num);
        $target_num = preg_replace("/\s/", "", $target_num);
        $target_num = preg_replace("/\./", "", $target_num);

        $target_num_space = substr($target_num, 0, 2) . ' ' . substr($target_num, 2, 2) . ' ' . substr($target_num, 4, 2) . ' ' . substr($target_num, 6, 2) . ' ' . substr($target_num, 8, 2);
        $from_num_space = substr($from_num, 0, 2) . ' ' . substr($from_num, 2, 2) . ' ' . substr($from_num, 4, 2) . ' ' . substr($from_num, 6, 2) . ' ' . substr($from_num, 8, 2);

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm(self::$db);

        //Search target in contact
        $sql = "SELECT rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "socpeople";
        $sql .= " WHERE phone = '" . $target_num_space . "' OR phone_perso = '" . $target_num_space . "' OR phone_mobile = '" . $target_num_space . "'";
        $sql .= " OR phone = '" . $target_num . "' OR phone_perso = '" . $target_num . "' OR phone_mobile = '" . $target_num . "'";
        $sql .= " OR phone = '" . $from_num_space . "' OR phone_perso = '" . $from_num_space . "' OR phone_mobile = '" . $from_num_space . "'";
        $sql .= " OR phone = '" . $from_num . "' OR phone_perso = '" . $from_num . "' OR phone_mobile = '" . $from_num . "'";

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
                $contact = new Contact(self::$db);
                $contact->fetch($obj->rowid);

                $actioncomm->contact = $contact;
                $actioncomm->socid = $contact->socid;
                $actioncomm->fetch_thirdparty();

                $actioncomm->societe = $actioncomm->thirdparty;
            }

            self::$db->free($resql);
        }

        if (empty($actioncomm->socid)) {
            //Search target in thirdparty
            $sql = "SELECT rowid";
            $sql .= " FROM " . MAIN_DB_PREFIX . "societe";
            $sql .= " WHERE phone = '" . $target_num_space . "' OR phone = '" . $target_num . "'";
            $sql .= " OR phone = '" . $from_num_space . "' OR phone = '" . $from_num . "'";

            $resql = self::$db->query($sql);
            if ($resql) {
                if ($obj = self::$db->fetch_object($resql)) {
                    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                    $company = new Societe(self::$db);
                    $company->fetch($obj->rowid);

                    $actioncomm->socid = $company->id;
                    $actioncomm->fetch_thirdparty();

                    $actioncomm->societe = $actioncomm->thirdparty;
                }
            }

            self::$db->free($resql);
        }

        //Search from in user
        $sql = "SELECT rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "user";
        $sql .= " WHERE office_phone = '" . $from_num_space . "' OR user_mobile = '" . $from_num_space . "'";
        $sql .= " OR office_phone = '" . $from_num . "' OR user_mobile = '" . $from_num . "'";
        $sql .= " OR office_phone = '" . $target_num_space . "' OR user_mobile = '" . $target_num_space . "'";
        $sql .= " OR office_phone = '" . $target_num . "' OR user_mobile = '" . $target_num . "'";

        $resql = self::$db->query($sql);
        $userassigned = array();
        if ($resql) {
            while ($obj = self::$db->fetch_object($resql)) {
                $userassigned[] = $obj->rowid;
            }

            self::$db->free($resql);
        }
        $actioncomm->userassigned = $userassigned;
        $actioncomm->userownerid = DolibarrApiAccess::$user->id;

        $actioncomm->datep = dol_now();
        $actioncomm->type_id = 1;
        $actioncomm->type_code = "AC_TEL";
        $actioncomm->note = "";
        if (!empty($hour)) {
            $actioncomm->note .= $langs->trans('API_hour') . $hour . "<br/>";
        } else {
            $actioncomm->note .= $langs->trans('API_hour') . self::$db->idate($now) . "<br/>";
        }

        $actioncomm->note .= $langs->trans('API_poste') . $poste . "<br/>";
        $actioncomm->note .= $langs->trans('API_name_post') . $name_post . "<br/>";
        $actioncomm->note .= $langs->trans('API_id_IPBX') . $id_IPBX . "<br/>";
        $actioncomm->note .= $langs->trans('API_groupe') . $groupe . "<br/>";
        $actioncomm->note .= $langs->trans('API_type') . $type . "<br/>";
        $actioncomm->note .= $langs->trans('API_source') . $source . "<br/>";
        $actioncomm->array_options = array("[options_ipbx]" => $id_IPBX);

        // if ($this->requestmanager->create_event_api($actioncomm) < 0) {
        //   throw new RestException(500, "Error creating event", array_merge(array($actioncomm->error), $actioncomm->errors));
        // }

        return $actioncomm;
    }

    /**
     *  Update an event following a phone call end
     *
     * @param int      $id_IPBX        Id interne à l'IPBX de l'appel
     * @param string   $state          État de l'appel : Décroché, non décroché, messagerie
     * @param string   $hour           Heure de fin de l'appel  (renseigné par l'IPBX, si non renseigné fait par dolibarr)
     * @param string   $during         Durée de la communication
     * @param int      $messagerie     Si messagerie, id interne à l'IPBX du message
     *
     * @url	GET /call/ending
     *
     * @return  object
     */
    function CallEnding($id_IPBX, $state, $hour='', $during='', $messagerie=NULL)
    {
        global $langs;

        $now = dol_now();

        $langs->load('requestmanager@requestmanager');

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm(self::$db);

        //Search event
        $sql = "SELECT rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm AS a";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields AS ex ON ex.fk_object = a.rowid";
        $sql .= " WHERE ex.ipbx = " . $id_IPBX;

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                $actioncomm->fetch($obj->rowid);

                $actioncomm->note .= $langs->trans('API_state') . $state . "<br/>";
                if (!empty($hour)) {
                    $actioncomm->note .= $langs->trans('API_hour_end') . $hour . "<br/>";
                } else {
                    $actioncomm->note .= $langs->trans('API_hour_end') . self::$db->idate($now) . "<br/>";
                }
                $actioncomm->note .= $langs->trans('API_during') . $during . "<br/>";
                if (!empty($messagerie)) {
                    $actioncomm->note .= $langs->trans('API_messagerie') . $messagerie . "<br/>";
                }

                // if ($this->requestmanager->update_event_api($actioncomm) < 0) {
                // throw new RestException(500, "Error creating event", array_merge(array($actioncomm->error), $actioncomm->errors));
                // }

                return $actioncomm;
            }
        }

        return [];
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
     *  Clean sensible object data
     *
     * @param   object          $object         Object to clean
     *
     * @return  object|array                    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->thirdparty_origin);
        unset($object->thirdparty);
        unset($object->thirdparty_benefactor);
        unset($object->requester_list);
        unset($object->watcher_list);
        unset($object->assigned_user_list);
        unset($object->assigned_usergroup_list);
        unset($object->new_assigned_user_ids);
        unset($object->new_assigned_usergroup_ids);
        unset($object->assigned_user_added_ids);
        unset($object->assigned_usergroup_added_ids);
        unset($object->assigned_user_deleted_ids);
        unset($object->assigned_usergroup_deleted_ids);
        unset($object->new_statut);
        unset($object->save_status);
        unset($object->user_resolved);
        unset($object->user_cloture);
        unset($object->user_creation);
        unset($object->user_modification);
        unset($object->fk_multicurrency);
        unset($object->multicurrency_code);
        unset($object->multicurrency_tx);
        unset($object->multicurrency_total_ht);
        unset($object->multicurrency_total_tva);
        unset($object->multicurrency_total_ttc);
        unset($object->canvas);
        unset($object->contact);
        unset($object->contact_id);
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);
        unset($object->mode_reglement_id);
        unset($object->cond_reglement_id);
        unset($object->cond_reglement);
        unset($object->fk_delivery_address);
        unset($object->shipping_method_id);
        unset($object->modelpdf);
        unset($object->fk_account);
        unset($object->note_public);
        unset($object->note_private);
        unset($object->total_ht);
        unset($object->total_tva);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);
        unset($object->fk_incoterms);
        unset($object->libelle_incoterms);
        unset($object->location_incoterms);
        unset($object->user);
        unset($object->import_key);
        unset($object->fk_project);
        unset($object->note);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->civility_id);
        unset($object->address);

        // If object has lines, remove $db property
        if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
            $nboflines = count($object->lines);
            for ($i = 0; $i < $nboflines; $i++) {
                $object->lines[$i] = $this->_cleanLineObjectDatas($object->lines[$i]);
            }
        }

        return $object;
    }

    /**
     *  Clean sensible line object data
     *
     * @param   object          $object         Object to clean
     *
     * @return  object|array                    Array of cleaned object properties
     */
    function _cleanLineObjectDatas($object)
    {
        unset($object->contact);
        unset($object->contact_id);
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);
        unset($object->mode_reglement_id);
        unset($object->mode_reglement_code);
        unset($object->mode_reglement);
        unset($object->cond_reglement_id);
        unset($object->cond_reglement_code);
        unset($object->cond_reglement);
        unset($object->fk_delivery_address);
        unset($object->fk_projet);
        unset($object->thirdparty);
        unset($object->user);
        unset($object->model_pdf);
        unset($object->modelpdf);
        unset($object->note_public);
        unset($object->note_private);
        unset($object->fk_incoterms);
        unset($object->libelle_incoterms);
        unset($object->location_incoterms);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->civility_id);
        unset($object->fk_multicurrency);
        unset($object->multicurrency_code);
        unset($object->shipping_method_id);

        return $object;
    }

    /**
     *  Clean sensible event object data
     *
     * @param   object          $object         Object to clean
     *
     * @return  object|array                    Array of cleaned object properties
     */
    function _cleanEventObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->usermod);
	unset($object->libelle);
	unset($object->array_options);
	unset($object->context);
	unset($object->canvas);
	unset($object->contact);
	unset($object->contact_id);
	unset($object->thirdparty);
	unset($object->user);
	unset($object->origin);
	unset($object->origin_id);
	unset($object->ref_ext);
	unset($object->statut);
	unset($object->country);
	unset($object->country_id);
	unset($object->country_code);
	unset($object->barcode_type);
	unset($object->barcode_type_code);
	unset($object->barcode_type_label);
	unset($object->barcode_type_coder);
	unset($object->mode_reglement_id);
	unset($object->cond_reglement_id);
	unset($object->cond_reglement);
	unset($object->fk_delivery_address);
	unset($object->shipping_method_id);
	unset($object->fk_account);
	unset($object->total_ht);
	unset($object->total_tva);
	unset($object->total_localtax1);
	unset($object->total_localtax2);
	unset($object->total_ttc);
	unset($object->fk_incoterms);
	unset($object->libelle_incoterms);
	unset($object->location_incoterms);
	unset($object->name);
	unset($object->lastname);
	unset($object->firstname);
	unset($object->civility_id);
	unset($object->contact);
	unset($object->societe);

        return $object;
    }

    /**
     * Get all errors
     *
     * @param  object   $object     Object
     * @return array                Array of errors
     */
	function _getErrors(&$object) {
	    $errors = is_array($object->errors) ? $object->errors : array();
	    $errors = array_merge($errors, (!empty($object->error) ? array($object->error) : array()));

	    return $errors;
    }

}
