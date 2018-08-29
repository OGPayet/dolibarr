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

/**
 * API class for requestmanager
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Requestmanager extends DolibarrApi {
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();

    /**
     * @var Societe $company {@type Societe}
     */
    public $company;

    /**
     * @var Contact $contact {@type Contact}
     */
    public $contact;

    /**
     * @var User $user_static {@type User}
     */
    public $user_static;

    /**
     * @var ActionComm $actioncomm {@type ActionComm}
     */
    public $actioncomm;

    /**
     * Constructor
     */
    function __construct() {
        global $db, $conf;
        $this->db = $db;

		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $this->company = new Societe($this->db);

		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$this->contact = new Contact($this->db);

		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$this->user_static = new User($this->db);

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$this->actioncomm = new ActionComm($this->db);

		$this->request = new RequestManager($this->db);

    }

	/**
	 * Get properties of a request manager object
	 *
	 * Return an array with request informations
	 *
	 * @param       int         $id         ID of request
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	function get($id) {
		if(! DolibarrApiAccess::$user->rights->requestmanager->lire) {
			throw new RestException(401);
		}

		$result = $this->request->fetch($id);
		if( ! $result ) {
			throw new RestException(200);
		}

		return $this->_cleanObjectDatas($this->request);
	}

	/**
	 * List requests
	 *
	 * Get a list of requests
	 *
	 * @param string	$sortfield	        Sort field
	 * @param string	$sortorder	        Sort order
	 * @param int		$limit		        Limit for list
	 * @param int		$page		        Page number
	 * @param string   	$thirdparty_ids	    Thirdparty ids to filter requests. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
	 * @return  array                       Array of order objects
	 */
	function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '') {
		global $db, $conf;

		$obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->requestmanager->lire) {
			throw new RestException(401);
		}

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

		$sql = "SELECT t.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."requestmanager as t";

		if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

		$sql.= ' WHERE (t.entity IN ('.getEntity('requestmanager').')';
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
		if ($search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";		// Join for the needed table to filter by sale
		// Insert sale filter
		if ($search_sale > 0) {
			$sql .= " AND sc.fk_user = ".$search_sale;
		}
		$sql.= ")";

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
		if ($socids) {
			$sql.= ' OR (t.entity IN ('.getEntity('societe').')';
			$sql.= " AND t.fk_soc IN (".$socids."))";
		}
		$sql.= " GROUP BY rowid";

		// Add sql filters
		if ($sqlfilters) {
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

        dol_syslog("API Rest request");
		$result = $db->query($sql);

		if ($result) {
			$num = $db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $db->fetch_object($result);
				$request_static = new RequestManager($db);
				if($request_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($request_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve request list : '.$db->lasterror());
		}
		if( ! count($obj_ret)) {
			return [];
		}
		return $obj_ret;
	}

	/**
	 * Create request object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of proposal
	 */
	function post($request_data = null) {
		if(! DolibarrApiAccess::$user->rights->requestmanager->creer) {
			throw new RestException(401, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach($request_data as $field => $value) {
			$this->request->$field = $value;
		}

		if ($this->request->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating request", array_merge(array($this->request->error), $this->request->errors));
		}

		return $this->request->id;
	}

	/**
	 * Update request general fields
	 *
	 * @param int   $id             Id of request to update
	 * @param array $request_data   Datas
	 *
	 * @return int
	 */
	function put($id, $request_data = null) {
		if(! DolibarrApiAccess::$user->rights->requestmanager->creer) {
			throw new RestException(401);
		}

		$result = $this->request->fetch($id);
		if( ! $result ) {
			return [];
		}

		foreach($request_data as $field => $value) {
			if ($field == 'id') continue;
			$this->request->$field = $value;
		}

		if ($this->request->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->request->error);
		}
	}

	/**
	 * Delete request
	 *
	 * @param   int     $id         Request ID
	 *
	 * @return  array
	 */
	function delete($id) {
		if(! DolibarrApiAccess::$user->rights->requestmanager->supprimer) {
			throw new RestException(401);
		}
		$result = $this->request->fetch($id);
		if( ! $result ) {
			return [];
		}

		if( ! $this->request->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Request : '.$this->request->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Request deleted'
			)
		);

	}

	/**
	 * Add a line to given request
	 *
	 * @param int   $id             Id of request to update
	 * @param array $request_data   Request line data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int
	 */
	function postLine($id, $request_data = null) {
		if(! DolibarrApiAccess::$user->rights->requestmanager->creer) {
			throw new RestException(401);
		}

		$result = $this->request->fetch($id);
		if (! $result) {
		   return [];
		}

		$request_data = (object) $request_data;

	$updateRes = $this->request->addline(
                        $request_data->desc,
                        $request_data->pu_ht,
                        $request_data->qty,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        $request_data->idprod,
                        $request_data->remise_percent,
                        $request_data->info_bits,
                        0,
                        $request_data->price_base_type,
                        $request_data->pu_ttc,
                        $request_data->date_start,
                        $request_data->date_end,
						-1,
						0,
                        $request_data->fk_parent_line,
                        $request_data->fournprice,
                        $request_data->buyingprice,
                        $request_data->label,
                        $request_data->array_options,
                        $request_data->fk_unit,
                        '',
						0,
                        $request_data->pu_ht_devise
		);

		if ($updateRes > 0) {
			return $updateRes;
		} else {
			throw new RestException(400, $this->request->error);
		}
	}

	/**
	 * Update a line of given request
	 *
	 * @param int   $id             Id of request to update
	 * @param int   $lineid         Id of line to update
	 * @param array $request_data   Request line data
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @return object
	 */
	function putLine($id, $lineid, $request_data = null) {
		if(! DolibarrApiAccess::$user->rights->requestmanager->creer) {
			throw new RestException(401);
		}

		$result = $this->request->fetch($id);
		if($result <= 0) {
			return [];
		}

		$request_data = (object) $request_data;

		$requestline = new RequestManagerLine($this->db);
		$result = $requestline->fetch($lineid);
		if ($result <= 0) {
			return [];
		}

		$updateRes = $this->request->updateline(
						$lineid,
						isset($request_data->desc)?$request_data->desc:$requestline->desc,
						isset($request_data->pu_ht)?$request_data->pu_ht:$requestline->pu_ht,
						isset($request_data->qty)?$request_data->qty:$requestline->qty,
						isset($request_data->remise_percent)?$request_data->remise_percent:$requestline->remise_percent,
						isset($request_data->tva_tx)?$request_data->tva_tx:$requestline->tva_tx,
						isset($request_data->localtax1_tx)?$request_data->localtax1_tx:$requestline->localtax1_tx,
						isset($request_data->localtax2_tx)?$request_data->localtax2_tx:$requestline->localtax2_tx,
						'HT',
						isset($request_data->info_bits)?$request_data->info_bits:$requestline->info_bits,
						isset($request_data->date_start)?$request_data->date_start:$requestline->date_start,
						isset($request_data->date_end)?$request_data->date_end:$requestline->date_end,
						isset($request_data->type)?$request_data->type:$requestline->type,
						isset($request_data->fk_parent_line)?$request_data->fk_parent_line:$requestline->fk_parent_line,
						0,
						isset($request_data->fournprice)?$request_data->fournprice:$requestline->fournprice,
						isset($request_data->buyingprice)?$request_data->buyingprice:$requestline->buyingprice,
						isset($request_data->label)?$request_data->label:$requestline->label,
						isset($request_data->special_code)?$request_data->special_code:$requestline->special_code,
						isset($request_data->array_options)?$request_data->array_options:$requestline->array_options,
						isset($request_data->fk_unit)?$request_data->fk_unit:$requestline->fk_unit,
						isset($request_data->pu_ht_devise)?$request_data->pu_ht_devise:$requestline->pu_ht_devise
		);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}
	  return false;
	}

	/**
	 * Delete a line of given request
	 *
	 *
	 * @param int   $id             Id of request to update
	 * @param int   $lineid         Id of line to delete
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @return int
     * @throws 401
     * @throws 404
	 */
	function deleteLine($id, $lineid) {
		if(! DolibarrApiAccess::$user->rights->requestmanager->creer) {
			throw new RestException(401);
		}

		$result = $this->request->fetch($id);
		if( ! $result ) {
			return [];
		}

		$updateRes = $this->request->deleteline($lineid);
		if ($updateRes > 0) {
			return $this->get($id);
		} else {
			throw new RestException(405, $this->request->error);
		}
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array           $data   Array with data to verify
	 * @return  array
	 * @throws  RestException
	 */
	function _validate($data) {
		$request = array();
		foreach (Requestmanager::$FIELDS as $field) {
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
			$request[$field] = $data[$field];

		}
		return $request;
	}

	/**
	 * Clean sensible object datas
	 *
	 * @param   object  $object    Object to clean
	 * @return    array    Array of cleaned object properties
	 */
	function _cleanObjectDatas($object) {

		$object = parent::_cleanObjectDatas($object);

        unset($object->note);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->address);

		return $object;
	}


	/**
	 * Add an event following a phone call begin
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
	 * @url	POST /call
	 * @url	GET /call
	 *
	 * @return  RestException
	 */
	function CallBegin($from_num, $target_num, $type, $id_IPBX, $hour='', $poste=NULL, $name_post='', $groupe='', $source=NULL) {
		global $db, $conf, $langs;

		$now=dol_now();

		$langs->load('requestmanager@requestmanager');

		$from_num = trim($from_num);
		$from_num = preg_replace("/\s/","",$from_num);
		$from_num = preg_replace("/\./","",$from_num);

		$target_num = trim($target_num);
		$target_num = preg_replace("/\s/","",$target_num);
		$target_num = preg_replace("/\./","",$target_num);

		$target_num_space = substr($target_num,0,2).' '.substr($target_num,2,2).' '.substr($target_num,4,2).' '.substr($target_num,6,2).' '.substr($target_num,8,2);
		$from_num_space = substr($from_num,0,2).' '.substr($from_num,2,2).' '.substr($from_num,4,2).' '.substr($from_num,6,2).' '.substr($from_num,8,2);

		//Search target in contact
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql.= " WHERE phone = '".$target_num_space."' OR phone_perso = '".$target_num_space."' OR phone_mobile = '".$target_num_space."'";
		$sql.= " OR phone = '".$target_num."' OR phone_perso = '".$target_num."' OR phone_mobile = '".$target_num."'";
		$sql.= " OR phone = '".$from_num_space."' OR phone_perso = '".$from_num_space."' OR phone_mobile = '".$from_num_space."'";
		$sql.= " OR phone = '".$from_num."' OR phone_perso = '".$from_num."' OR phone_mobile = '".$from_num."'";

		$result = $db->query($sql);

		if ($result) {
			$obj = $db->fetch_object($result);
			$this->contact->fetch($obj->rowid);

			$this->actioncomm->contact = $this->contact;
			$this->actioncomm->socid=$this->contact->socid;
			$this->actioncomm->fetch_thirdparty();

			$this->actioncomm->societe = $this->actioncomm->thirdparty;
		}

		if(empty($this->actioncomm->socid)) {
			//Search target in thirdparty
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe";
			$sql.= " WHERE phone = '".$target_num_space."' OR phone = '".$target_num."'";
			$sql.= " OR phone = '".$from_num_space."' OR phone = '".$from_num."'";

			$result = $db->query($sql);
			if ($result) {
				$obj = $db->fetch_object($result);
				$this->company->fetch($obj->rowid);

				$this->actioncomm->socid=$this->company->id;
				$this->actioncomm->fetch_thirdparty();

				$this->actioncomm->societe = $this->actioncomm->thirdparty;
			}
		}

		//Search from in user
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE office_phone = '".$from_num_space."' OR user_mobile = '".$from_num_space."'";
		$sql.= " OR office_phone = '".$from_num."' OR user_mobile = '".$from_num."'";
		$sql.= " OR office_phone = '".$target_num_space."' OR user_mobile = '".$target_num_space."'";
		$sql.= " OR office_phone = '".$target_num."' OR user_mobile = '".$target_num."'";

		$result = $db->query($sql);
		$num = $db->num_rows($result);
		$userassigned = array();
		if ($result) {
			$i=0;
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				$this->user_static->fetch($obj->rowid);

				$userassigned[] = $this->user_static->id;

				$i++;
			}
		}
		$this->actioncomm->userassigned = $userassigned;
		$this->actioncomm->userownerid = DolibarrApiAccess::$user->id;

		$this->actioncomm->datep = dol_now();
		$this->actioncomm->type_id = 1;
		$this->actioncomm->type_code = "AC_TEL";
		$this->actioncomm->note = "";
		if(!empty($hour)) {
			$this->actioncomm->note .= $langs->trans('API_hour').$hour."<br/>";
		} else {
			$this->actioncomm->note .= $langs->trans('API_hour').$db->idate($now)."<br/>";
		}

		$this->actioncomm->note .= $langs->trans('API_poste').$poste."<br/>";
		$this->actioncomm->note .= $langs->trans('API_name_post').$name_post."<br/>";
		$this->actioncomm->note .= $langs->trans('API_id_IPBX').$id_IPBX."<br/>";
		$this->actioncomm->note .= $langs->trans('API_groupe').$groupe."<br/>";
		$this->actioncomm->note .= $langs->trans('API_type').$type."<br/>";
		$this->actioncomm->note .= $langs->trans('API_source').$source."<br/>";
		$this->actioncomm->array_options = array("[options_ipbx]" => $id_IPBX);

		// if ($this->request->create_event_api($this->actioncomm) < 0) {
			// throw new RestException(500, "Error creating event", array_merge(array($this->actioncomm->error), $this->actioncomm->errors));
		// }

		return $this->actioncomm;
	}

	/**
	 * Update an event following a phone call end
	 *
	 * @param int      $id_IPBX        Id interne à l'IPBX de l'appel
	 * @param string   $state          État de l'appel : Décroché, non décroché, messagerie
	 * @param string   $hour           Heure de fin de l'appel  (renseigné par l'IPBX, si non renseigné fait par dolibarr)
	 * @param string   $during         Durée de la communication
	 * @param int      $messagerie     Si messagerie, id interne à l'IPBX du message
	 *
	 * @url	POST /call
	 * @url	GET /call
	 *
	 * @return  RestException
	 */
	function CallEnding($id_IPBX, $state, $hour='', $during=0, $messagerie=NULL) {
		global $db, $conf, $langs;

		$now=dol_now();

		$langs->load('requestmanager@requestmanager');

		//Search event
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm AS a";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_extrafields AS ex ON ex.fk_object = a.rowid";
		$sql.= " WHERE ex.ipbx = ".$id_IPBX;

		$result = $db->query($sql);

		if ($result) {
			$obj = $db->fetch_object($result);
			$this->actioncomm->fetch($obj->rowid);

			$this->actioncomm->note .= $langs->trans('API_state').$state."<br/>";
			if(!empty($hour)) {
				$this->actioncomm->note .= $langs->trans('API_hour_end').$hour."<br/>";
			} else {
				$this->actioncomm->note .= $langs->trans('API_hour_end').$db->idate($now)."<br/>";
			}
			$this->actioncomm->note .= $langs->trans('API_during').$during."<br/>";$
			if(!empty($messagerie)) {
				$this->actioncomm->note .= $langs->trans('API_messagerie').$messagerie."<br/>";
			}

			// if ($this->request->update_event_api($this->actioncomm) < 0) {
				// throw new RestException(500, "Error creating event", array_merge(array($this->actioncomm->error), $this->actioncomm->errors));
			// }

			return $this->actioncomm;
		}
	}
}
