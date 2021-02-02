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

require_once DOL_DOCUMENT_ROOT.'/custom/eventconfidentiality/class/eventconfidentiality.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

/**
 * API class for EventConfidentiality
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class EventConfidentialityApi extends DolibarrApi {
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
     *  Constructor
     */
    function __construct()
    {
        global $db, $user;

        $user = DolibarrApiAccess::$user;
        $this->db = $db;
    }

    /**
     *  Get default tags information for an origin and an action type
     *
     * @url	GET default_tags
     *
     * @param   string      $element_type           Element type linked to the event (if empty is a event)
     * @param   int         $action_type_id         Id of the event type (search all if = 0)
     *
     * @return  array                               List of default tags information for each action type
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  500             RestException       Error when retrieve default tags information
     */
    function getDefaultTags($element_type = '', $action_type_id = 0)
    {
        if (!DolibarrApiAccess::$user->rights->eventconfidentiality->manage) {
            throw new RestException(401, "Insufficient rights");
        }

        dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
        $eventconfidentiality = new EventConfidentiality($this->db);
        $default_tags = $eventconfidentiality->getDefaultTags($element_type, $action_type_id);
        if (!is_array($default_tags)) {
            throw new RestException(500, "Error when retrieve default tags information", [ 'details' => $this->_getErrors($eventconfidentiality) ]);
        }

        return $default_tags;
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
