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
     * @var array       $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();

    /**
     * @var array       $MESSAGE_FIELDS     Mandatory fields, checked when create and update message object
     */
    static $MESSAGE_FIELDS = array();

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

        $requestmanager->fetch_optionals();
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
     * @param   int         $only_assigned	    1=Restrict list to the request assigned to this user or his user groups
     * @param   string      $sql_filters        Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     *
     * @return  array                           Array of request objects
     *
     * @throws  400         RestException       Error when validating parameter 'sqlfilters'
     * @throws  401         RestException       Insufficient rights
     * @throws  500         RestException       Error when retrieve request list
     */
    function index($sort_field="t.rowid", $sort_order='ASC', $limit=100, $page=0, $only_assigned=0, $sql_filters='')
    {
        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
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
                    $requestmanager->fetch_optionals();
                    $requestmanager->fetchObjectLinked();
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

        // todo remplir auto le socid_origin, socid et socid_benefactor si un utilisateur externe ?
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
     * @throws  403     RestException       Access unauthorized
     * @throws  404     RestException       Request not found
     * @throws  500     RestException       Error when retrieve request
	 */
	function getLines($id)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

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
            throw new RestException(500, "Error when retrieve request message", $this->_getErrors($requestmanager_message));
        } elseif ($requestmanager_message->id > 0) {
            if ($requestmanager_message->message_type != -1) {
                $requestmanager_message->fetch_knowledge_base();
                $requestmanager_message->fetch_optionals();
            } else {
                throw new RestException(404, "Request not found");
            }
        } else {
            throw new RestException(403, "Access unauthorized");
        }

        $requestmanager_message = $this->_cleanEventObjectDatas($requestmanager_message);
        return $this->_cleanMessageObjectDatas($requestmanager_message);
    }

    /**
     *  Create a request message
     *
     * @url	POST {id}/message
     *
     * @param   int     $id                         ID of the request
     * @param   array   $request_message_data       Request message data
     *
     * @return  int                                 ID of the request message created
     *
     * @throws  400     RestException               Field missing
     * @throws  403     RestException               Access unauthorized
     * @throws  401     RestException               Insufficient rights
     * @throws  500     RestException               Error while creating the request message
     */
    function postMessage($id, $request_message_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        // Check mandatory fields
        $this->_validateMessage($request_message_data);

        $requestmanager_message = new RequestManagerMessage(self::$db);
        foreach ($request_message_data as $field => $value) {
            $requestmanager_message->$field = $value;
        }
        $requestmanager_message->requestmanager = $requestmanager;

        if ($requestmanager_message->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while creating the request message", $this->_getErrors($requestmanager_message));
        }

        return $requestmanager_message->id;
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
     * @throws  500         RestException                               Error when retrieve request list
     */
    function indexEvents($id, $sort_field="t.datec", $sort_order='DESC', $limit=100, $page=0, $sql_filters='', $only_message=0, $only_linked_to_request=1, $include_events_other_request=0, $include_linked_events_children_request=1)
    {
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
                if ($obj->code == 'AC_RM_PRIV' || $obj->code == 'AC_RM_IN' || $obj->code == 'AC_RM_OUT') {
                    $requestmanager_message = new RequestManagerMessage(self::$db);
                    if ($requestmanager_message->fetch($obj->id) > 0 && $requestmanager_message->id > 0) {
                        $requestmanager_message->fetch_knowledge_base();
                        $requestmanager_message->fetch_optionals();
                        $requestmanager_message = $this->_cleanEventObjectDatas($requestmanager_message);
                        $obj_ret[] = $this->_cleanMessageObjectDatas($requestmanager_message);
                    }
                } else {
                    $event = new ActionComm(self::$db);
                    if ($event->fetch($obj->id) > 0 && $event->id > 0) {
                        $event->fetch_optionals();
                        $obj_ret[] = $this->_cleanEventObjectDatas($event);
                    }
                }
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve request events list", self::$db->lasterror());
        }

        return $obj_ret;
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
     * @throws  403     RestException   Access unauthorized
     * @throws  404     RestException   Request not found
     * @throws  500     RestException   Error when retrieve request
     * @throws  500     RestException   Error while setting the request status
     */
    function setStatus($id, $status_id=0, $status_type=-1, $no_trigger=0, $force_reload=0)
    {
        if (!DolibarrApiAccess::$user->rights->requestmanager->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get request object
        $requestmanager = $this->_getRequestManagerObject($id);

        if ($requestmanager->set_status($status_id, $status_type, DolibarrApiAccess::$user, $no_trigger, $force_reload) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while setting the request status", $this->_getErrors($requestmanager));
        }
    }

    /**
     *  Create a calling event
     *
     * @url	POST /call/{id}/begin
     *
     * @param   int         $id                 Id interne à l'IPBX de l'appel
     * @param   string      $caller_number      Caller's number
     * @param   string      $called_number      Called number
     * @param   string      $type               Sens de l'appel : entrant, sortant, transfert interne
     * @param   string      $hour               Heure de début de l'appel (renseigné par l'IPBX, si non renseigné fait par dolibarr)
     * @param   float       $poste              Poste ayant décroché/émis l'appel
     * @param   string      $name_post          Nom du compte associé au poste
     * @param   string      $groupe             Groupe de réponse à l'origine de l'appel
     * @param   string      $source             Appel source : id interne IPBX
     *
     * @return  int                             ID of the calling event
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Company not found with phones
     * @throws  404         RestException       Internal user not found with phones
     * @throws  500         RestException       Error when retrieve company / contact
     * @throws  500         RestException       Error when retrieve company information
     * @throws  500         RestException       Error when retrieve internal user
     * @throws  500         RestException       Error while creating the calling event
     */
    function CallBegin($id, $caller_number, $called_number, $type, $hour='', $poste=NULL, $name_post='', $groupe='', $source=NULL)
    {
        global $langs;

        if (!DolibarrApiAccess::$user->rights->agenda->myactions->create) {
            throw new RestException(401, "Insufficient rights");
        }

        $now = dol_now();

        // Clean parameters
        $from_num = preg_replace("/\D/", "", $caller_number);
        $target_num = preg_replace("/\D/", "", $called_number);

        // Search contact / company
        //---------------------------------------
        $socid = 0;
        $contactid = 0;

        $sql = "SELECT socid, contactid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "requestmanager_soc_contact_phone_book";
        $sql .= " WHERE entity IN (".getEntity('societe').")";
        $sql .= " AND (";
        $sql .= " soc_phone = '" . $from_num . "'";
        $sql .= " OR contact_phone = '" . $from_num . "'";
        $sql .= " OR contact_phone_perso = '" . $from_num . "'";
        $sql .= " OR contact_phone_mobile = '" . $from_num . "'";
        $sql .= " OR soc_phone = '" . $target_num . "'";
        $sql .= " OR contact_phone = '" . $target_num . "'";
        $sql .= " OR contact_phone_perso = '" . $target_num . "'";
        $sql .= " OR contact_phone_mobile = '" . $target_num . "'";
        $sql .= " )";

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                $socid = $obj->socid;
                $contactid = $obj->contactid;
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve company / contact", self::$db->lasterror());
        }

        if ($socid == 0) {
            throw new RestException(404, "Company not found with phones: ".$from_num.", ".$target_num);
        }

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        $societe = new Societe(self::$db);
        $result = $societe->fetch($socid);
        if ($result < 0) {
            throw new RestException(500, "Error when retrieve company information", $this->_getErrors($requestmanager));
        }

        // Search internal user
        //---------------------------------------
        $userid = 0;
        //$userassigned = array();

        $sql = "SELECT rowid AS userid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "requestmanager_internal_user_phone_book";
        $sql .= " WHERE entity IN (".getEntity('user').")";
        $sql .= " AND (";
        $sql .= " office_phone = '" . $from_num . "'";
        $sql .= " OR user_mobile = '" . $from_num . "'";
        $sql .= " OR office_phone = '" . $target_num . "'";
        $sql .= " OR user_mobile = '" . $target_num . "'";
        $sql .= " )";

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                $userid = $obj->userid;
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve internal user", self::$db->lasterror());
        }

        if ($userid == 0) {
            throw new RestException(404, "Internal user not found with phones: ".$from_num.", ".$target_num);
        }

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm(self::$db);

        // Create event
        //--------------------------------------------------
        $langs->load('commercial');
        $langs->load('requestmanager@requestmanager');
        $actioncomm->type_code = "AC_TEL";
        $actioncomm->label = $langs->trans('ActionAC_TEL') . ': ' . $type . ' - ' . $societe->getFullName($langs) . ' - ' . $langs->trans('ActionRunningShort');
        // Todo calculer la date exacte
        $actioncomm->location = (!empty($name_post) ? $name_post : '') . (!empty($poste) ? ' ( ' . $poste . ' )' : '');
        $actioncomm->datep = $now;
        $actioncomm->percentage = 50;
        //$actioncomm->userassigned = $userassigned;
        $actioncomm->socid = $socid;
        $actioncomm->contactid = $contactid;
        $actioncomm->userownerid = $userid;
        $actioncomm->array_options = array("options_rm_ipbx" => $id);

        // Message
        //--------------------------------------------------
        $message = $langs->trans('RequestManagerIPBXID', $id) . '<br/>';
        if (!empty($poste)) $message .= $langs->trans('RequestManagerCallPost', $poste) . '<br/>';
        if (!empty($name_post)) $message .= $langs->trans('RequestManagerCallPostName', $name_post) . '<br/>';
        $message .= $langs->trans('RequestManagerCallerNumber', $caller_number) . '<br/>';
        $message .= $langs->trans('RequestManagerCalledNumber', $called_number) . '<br/>';
        $message .= $langs->trans('RequestManagerCallDirection', $type) . '<br/>';
        if (!empty($groupe)) $message .= $langs->trans('RequestManagerAnswerGroupAtOriginCall', $groupe) . '<br/>';
        if (!empty($source)) $message .= $langs->trans('RequestManagerCallOrigin', $source) . '<br/>';
        $message .= $langs->trans('RequestManagerStartTimeCall', !empty($hour) ? $hour : dol_print_date($now, 'hour')) . '<br/>';
        $actioncomm->note = $message;

        if ($actioncomm->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while creating the calling event", $this->_getErrors($actioncomm));
        }

        return $actioncomm->id;
    }

    /**
     *  Close a calling event
     *
     * @url	PUT /call/{id}/ending
     *
     * @param   int         $id                 Id interne à l'IPBX de l'appel
     * @param   string      $state              État de l'appel : Décroché, non décroché, messagerie
     * @param   string      $hour               Heure de fin de l'appel  (renseigné par l'IPBX, si non renseigné fait par dolibarr)
     * @param   string      $during             Durée de la communication
     * @param   int         $messagerie         Si messagerie, id interne à l'IPBX du message
     *
     * @return  int                             ID of the calling event
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Calling event not found with IPBX ID
     * @throws  500         RestException       Error when retrieve calling event
     * @throws  500         RestException       Error while closing the calling event
     */
    function CallEnding($id, $state, $hour='', $during='', $messagerie=NULL)
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
        $sql .= " WHERE acef.rm_ipbx = " . $id;

        $resql = self::$db->query($sql);
        if ($resql) {
            if ($obj = self::$db->fetch_object($resql)) {
                $actioncommid = $obj->id;
            }

            self::$db->free($resql);
        }

        if ($actioncommid == 0) {
            throw new RestException(404, "Calling event not found with IPBX ID: " . $id);
        }

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm(self::$db);
        if ($actioncomm->fetch($actioncommid) < 0) {
            throw new RestException(500, "Error when retrieve calling event", $this->_getErrors($actioncomm));
        }

        // Update event
        //--------------------------------------------------
        $langs->load('commercial');
        $langs->load('requestmanager@requestmanager');
        $actioncomm->label = str_replace(' - ' . $langs->trans('ActionRunningShort'), '' , $actioncomm->label) . ' ' . $langs->trans('RequestManagerHourFromTo', dol_print_date($actioncomm->datep, 'hour'), dol_print_date($now, 'hour'));
        $actioncomm->percentage = 100;
        // Todo calculer la date exacte et la durée exacte
        $actioncomm->datef = $now;
        $actioncomm->durationp = $now - $actioncomm->datep;

        // Added ending message
        //--------------------------------------------------
        $message = '<br/>' . $langs->trans('RequestManagerCallStatus', $state) . '<br/>';
        $message .= $langs->trans('RequestManagerEndTimeCall', !empty($hour) ? $hour : dol_print_date($now, 'hour')) . '<br/>';
        $message .= $langs->trans('RequestManagerCallDuration', $during) . '<br/>';
        if (!empty($messagerie)) $message .= $langs->trans('RequestManagerCallMessageID', $messagerie) . '<br/>';
        $actioncomm->note = dol_concatdesc($actioncomm->note, $message);

        if ($actioncomm->update(DolibarrApiAccess::$user) > 0) {
            return $actioncomm->id;
        } else {
            throw new RestException(500, "Error while closing the calling event", $this->_getErrors($actioncomm));
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
            throw new RestException(500, "Error when retrieve request", $this->_getErrors($requestmanager));
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

        unset($object->icalname);
        unset($object->icalcolor);
        unset($object->actions);
        unset($object->email_msgid);
        unset($object->email_from);
        unset($object->email_sender);
        unset($object->email_to);
        unset($object->email_tocc);
        unset($object->email_tobcc);
        unset($object->email_subject);
        unset($object->errors_to);
        unset($object->table_rowid);
        unset($object->libelle);
        unset($object->linkedObjectsIds);
        unset($object->canvas);
        unset($object->thirdparty);
        unset($object->user);
        unset($object->origin);
        unset($object->origin_id);
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
        unset($object->modelpdf);
        unset($object->fk_account);
        unset($object->note_public);
        unset($object->note_private);
        unset($object->total_ht);
        unset($object->total_tva);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);
        unset($object->lines);
        unset($object->fk_incoterms);
        unset($object->libelle_incoterms);
        unset($object->location_incoterms);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->civility_id);
        unset($object->contact_id);
        unset($object->contact);
        unset($object->societe);
        unset($object->usermod);
        unset($object->import_key);
        unset($object->userdone);
        unset($object->usertodo);
        unset($object->elementtype);

        return $object;
    }

    /**
     *  Clean sensible event object data
     *
     * @param   object          $object         Object to clean
     *
     * @return  object|array                    Array of cleaned object properties
     */
    function _cleanMessageObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->requestmanager);
        unset($object->attached_files);
        unset($object->knowledge_base_list);
        unset($object->durationp);
        unset($object->fulldayevent);
        unset($object->punctual);
        unset($object->percentage);
        unset($object->location);
        unset($object->transparency);
        unset($object->priority);
        unset($object->ref_ext);
        unset($object->fk_project);

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
