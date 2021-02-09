<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019   Alexis LAURIER     <alexis@alexislaurier.fr>
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

//require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

/**
 * API class for users
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user}
 */
class SynergiesTechApi extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
		'login'
	);

	/**
	 * @var User $user {@type User}
	 */
	public $useraccount;

	/**
	 * Constructor
	 */
	function __construct() {
		global $db, $conf;
		$this->db = $db;
		$this->useraccount = new User($this->db);
	}


	/**
	 * List Users
	 *
	 * Get a list of Users
	 *
	 * @url	GET users/
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array               Array of User objects
	 */
	function getUsers($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $sqlfilters = '') {
	    global $db, $conf;

	    $obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->synergiestech->user->lirerestreint) {
	       throw new RestException(401, "You are not allowed to read restricted list of users");
	    }

		// If the internal user must only see his customers, force searching by him
		if ($socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as t";
		$sql.= ' WHERE (t.entity IN ('.getEntity('user').')';
		$sql.= ")";
		$sql.= " AND (t.fk_soc IS NULL)";
		$sql.= " GROUP BY rowid";

	    // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
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
	    $result = $db->query($sql);

	    if ($result)
	    {
	        $num = $db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
	        while ($i < $min)
	        {
	            $obj = $db->fetch_object($result);
	            $user_static = new User($db);
	            if($user_static->fetch($obj->rowid)) {

					$finalItem = $this->_cleanObjectDatas($user_static);

						$resultObj=array();
						$temp=array("id","firstname","lastname","statut");
						foreach($temp as $tempkey){$resultObj[$tempkey]=$finalItem->$tempkey;}
                    $obj_ret[] = $resultObj;
	            }
	            $i++;
	        }
	    }
	    else {
	        throw new RestException(503, 'Error when retrieve User list : '.$db->lasterror());
	    }
	    if( ! count($obj_ret)) {
	        return [];
	    }
	    return $obj_ret;
	}

	/**
	 * Clean sensible object datas
	 *
	 * @param   object  $object    Object to clean
	 * @return    array    Array of cleaned object properties
	 */
	function _cleanObjectDatas($object) {
		global $conf;

	    $object = parent::_cleanObjectDatas($object);

	    unset($object->default_values);
	    unset($object->lastsearch_values);
	    unset($object->lastsearch_values_tmp);

	    unset($object->total_ht);
	    unset($object->total_tva);
	    unset($object->total_localtax1);
	    unset($object->total_localtax2);
	    unset($object->total_ttc);
	    unset($object->libelle_incoterms);
	    unset($object->location_incoterms);

	    unset($object->fk_delivery_address);
	    unset($object->fk_incoterms);
	    unset($object->all_permissions_are_loaded);
	    unset($object->shipping_method_id);
	    unset($object->nb_rights);
	    unset($object->search_sid);
	    unset($object->ldap_sid);

	    // List of properties never returned by API, whatever are permissions
	    unset($object->pass);
	    unset($object->pass_indatabase);
	    unset($object->pass_indatabase_crypted);
	    unset($object->pass_temp);
	    unset($object->api_key);
	    unset($object->clicktodial_password);
	    unset($object->openid);


	    $canreadsalary = ((! empty($conf->salaries->enabled) && ! empty(DolibarrApiAccess::$user->rights->salaries->read))
		|| (! empty($conf->hrm->enabled) && ! empty(DolibarrApiAccess::$user->rights->hrm->employee->read)));

		if (! $canreadsalary)
		{
			unset($object->salary);
			unset($object->salaryextra);
			unset($object->thm);
			unset($object->tjm);
		}

	    return $object;
	}
}
