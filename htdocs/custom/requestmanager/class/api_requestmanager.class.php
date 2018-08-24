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
class Requestmanager extends DolibarrApi
{
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
    function __construct()
    {
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


    }

	/**
	 * Add an event following a phone call
	 *
	 * @param string   $from_num     Caller's number
	 * @param string   $target_num   Called number
	 *
	 * @url	GET /call
	 *
	 * @return  RestException
	 */
	function getCall($from_num, $target_num)
	{
		global $db, $conf;

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

		$result = $db->query($sql);

		if ($result) {
			$obj = $db->fetch_object($result);
			$this->user_static->fetch($obj->rowid);

			$this->actioncomm->userassigned = $this->user_static->id;
			$this->actioncomm->userownerid = $this->user_static->id;
		}

		if(empty($this->actioncomm->userownerid)) {
			$this->actioncomm->userownerid = DolibarrApiAccess::$user->id;
		}

		$this->actioncomm->datep = dol_now();
		$this->actioncomm->type_id = 1;
		$this->actioncomm->type_code = "AC_TEL";

		// if ($this->actioncomm->create(DolibarrApiAccess::$user) < 0) {
			// throw new RestException(500, "Error creating event", array_merge(array($this->actioncomm->error), $this->actioncomm->errors));
		// }

		return $this->actioncomm;
	}
}
