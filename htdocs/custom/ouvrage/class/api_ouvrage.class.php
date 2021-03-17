<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2020 SuperAdmin <nicolas@inovea-conseil.Com>
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

dol_include_once('/ouvrage/class/ouvrage.class.php');



/**
 * \file    ouvrage/class/api_ouvrage.class.php
 * \ingroup ouvrage
 * \brief   File for API management of ouvrage.
 */

/**
 * API class for ouvrage ouvrage
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class OuvrageApi extends DolibarrApi
{
    /**
     * @var Ouvrage $ouvrage {@type Ouvrage}
     */
    public $ouvrage;

    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
        $this->ouvrage = new Ouvrage($this->db);
    }

    /**
     * Get properties of a ouvrage object
     *
     * Return an array with ouvrage informations
     *
     * @param 	int 	$id ID of ouvrage
     * @return 	array|mixed data without useless information
     *
     * @url	GET ouvrages/{id}
     * @throws 	RestException
     */
    public function get($id)
    {
        if (! DolibarrApiAccess::$user->rights->ouvrage->read) {
            throw new RestException(401);
        }

        $result = $this->ouvrage->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Ouvrage not found');
        }

        if (! DolibarrApi::_checkAccessToResource('ouvrage', $this->ouvrage->id, 'ouvrage_ouvrage')) {
            throw new RestException(401, 'Access to instance id='.$this->ouvrage->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->ouvrage);
    }


    /**
     * List ouvrages
     *
     * Get a list of ouvrages
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of order objects
     *
     * @throws RestException
     *
     * @url	GET /ouvrages/
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();
        $tmpobject = new Ouvrage($db);

        if(! DolibarrApiAccess::$user->rights->bbb->read) {
            throw new RestException(401);
        }

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $restrictonsocid = 0;	// Set to 1 if there is a field socid in table of object

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if ($restrictonsocid && ! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";

        if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= " WHERE 1 = 1";

        // Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        if ($tmpobject->ismultientitymanaged) $sql.= ' AND t.entity IN ('.getEntity('ouvrage').')';
        if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($restrictonsocid && $socid) $sql.= " AND t.fk_soc = ".$socid;
        if ($restrictonsocid && $search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($restrictonsocid && $search_sale > 0) {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $ouvrage_static = new Ouvrage($db);
                if($ouvrage_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($ouvrage_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieving ouvrage list: '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No ouvrage found');
        }
        return $obj_ret;
    }

    /**
     * Create ouvrage object
     *
     * @param array $request_data   Request datas
     * @return int  ID of ouvrage
     *
     * @url	POST ouvrages/
     */
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ouvrage->write) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->ouvrage->$field = $value;
        }
        if( ! $this->ouvrage->create(DolibarrApiAccess::$user)) {
            throw new RestException(500, "Error creating Ouvrage", array_merge(array($this->ouvrage->error), $this->ouvrage->errors));
        }
        return $this->ouvrage->id;
    }

    /**
     * Update ouvrage
     *
     * @param int   $id             Id of ouvrage to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT ouvrages/{id}
     */
    public function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ouvrage->write) {
            throw new RestException(401);
        }

        $result = $this->ouvrage->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Ouvrage not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('ouvrage', $this->ouvrage->id, 'ouvrage_ouvrage')) {
            throw new RestException(401, 'Access to instance id='.$this->ouvrage->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->ouvrage->$field = $value;
        }

        if ($this->ouvrage->update($id, DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->ouvrage->error);
        }
    }

    /**
     * Delete ouvrage
     *
     * @param   int     $id   Ouvrage ID
     * @return  array
     *
     * @url	DELETE ouvrages/{id}
     */
    public function delete($id)
    {
        if (! DolibarrApiAccess::$user->rights->ouvrage->delete) {
            throw new RestException(401);
        }
        $result = $this->ouvrage->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Ouvrage not found');
        }

        if (! DolibarrApi::_checkAccessToResource('ouvrage', $this->ouvrage->id, 'ouvrage_ouvrage')) {
            throw new RestException(401, 'Access to instance id='.$this->ouvrage->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $this->ouvrage->delete(DolibarrApiAccess::$user))
        {
            throw new RestException(500, 'Error when deleting Ouvrage : '.$this->ouvrage->error);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'Ouvrage deleted'
            )
        );
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        /*unset($object->note);
        unset($object->address);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);*/

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param	array		$data   Array of data to validate
     * @return	array
     *
     * @throws	RestException
     */
    private function _validate($data)
    {
        $ouvrage = array();
        foreach ($this->ouvrage->fields as $field => $propfield) {
            if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) continue;   // Not a mandatory field
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $ouvrage[$field] = $data[$field];
        }
        return $ouvrage;
    }
}
