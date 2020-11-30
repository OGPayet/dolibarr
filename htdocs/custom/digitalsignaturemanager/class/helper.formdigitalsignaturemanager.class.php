<?php
/* Copyright (c) 2020  Alexis LAURIER    <contact@alexislaurier.fr>
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

/**
 *	\file       digitalsignaturemanager/class/html.formdigitalsignaturemanager.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 */

dol_include_once('/comm/action/class/actioncomm.class.php');

class FormHelperDigitalSignatureManager
{
	/**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
	public $db;

    /**
     * @var array
     */
	public static $errors = array();

    /**
     * Constructor
     *
     * @param   DoliDB $db Database handler
     */
	public function __construct(DoliDb $db)
	{
		$this->db = $db;
	}

	/**
     *   Load all objects with filters
     * 	 @param		DigitalSignatureRequest	$digitalSignatureRequest Object on which we must return actioncomm
	 *   @param		int		$socid			Filter by thirdparty
     *   @param		string	$filter			Other filter
     *   @param		string	$sortfield		Sort on this field
     *   @param		string	$sortorder		ASC or DESC
     *   @param		string	$limit			Limit number of answers
     *   @return	ActionComm[]|int		Error string if KO, array with actions if OK
     */
	public function getActions($digitalSignatureRequest, $socid = 0, $filter = '', $sortfield = 'datep', $sortorder = 'DESC', $limit = 0)
	{

		$arrayOfPeopleSignatureIds = array();
		foreach($digitalSignatureRequest->people as $people){
			$arrayOfPeopleSignatureIds[] = $people->id;
		}

		$filterForDigitalSignatureRequest = array();


        $sql = "SELECT a.id";
        $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
        $sql.= " WHERE a.entity IN (".getEntity('agenda').")";
        if (!empty($socid)) {
			$sql.= " AND a.fk_soc = " . $socid;
		}
		if($digitalSignatureRequest->id) {
			$filterForDigitalSignatureRequest[] = "( a.fk_element = " . (int) $digitalSignatureRequest->id . " AND a.elementtype = 'digitalsignaturemanager_digitalsignaturerequest' )";
		}

		if(!empty($arrayOfPeopleSignatureIds)) {
			$filterForDigitalSignatureRequest[] = "( a.fk_element IN (" . implode($arrayOfPeopleSignatureIds) .") AND a.elementtype = 'digitalsignaturemanager_digitalsignaturepeople' )";
		}

		if(!empty($filterForDigitalSignatureRequest)) {
			$sql .= " AND (" . implode(' OR ', $filterForDigitalSignatureRequest) . ") ";
		}

		if (!empty($filter))
		{
			$sql.= $filter;
		}
		if ($sortorder && $sortfield) {
			$sql.=$this->db->order($sortfield, $sortorder);
		}
		if ($limit) {
			$sql.=$this->db->plimit($limit);
		}

        dol_syslog(get_class()."::getActions", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);

            if ($num)
            {
                for($i=0;$i<$num;$i++)
                {
                    $obj = $this->db->fetch_object($resql);
                    $actioncommstatic = new ActionComm($this->db);
                    $actioncommstatic->fetch($obj->id);
                    $resarray[$i] = $actioncommstatic;
                }
            }
            $this->db->free($resql);
            return $resarray;
		}
        else
		   {
            return $this->db->lasterror();
		}
	}
}
