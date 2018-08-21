<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/requestmanager/core/triggers/interface_99_modRequestManager_RMWorkflow.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class of triggers for workflow in requestmanager module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for workflow in requestmanager module
 */
class InterfaceRMWorkflow extends DolibarrTriggers
{
	public $family = 'requestmanager';
	public $description = "Triggers of this module catch triggers event for the workflow of RequestManager module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'requestmanager@requestmanager';


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (isset($object->element)) {
            // Get request linked to the object
            $sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'element_element';
            $sql .= " WHERE (fk_source = " . $object->id . " AND sourcetype = '" . $object->element . "' AND targettype = 'requestmanager')";
            $sql .= " OR (fk_target = " . $object->id . " AND targettype = '" . $object->element . "' AND sourcetype = 'requestmanager')";

            $resql = $this->db->query($sql);
            if ($resql) {
                dol_include_once('/requestmanager/class/requestmanager.class.php');

                $requests = array();
                while ($obj = $this->db->fetch_object($resql)) {
                    $request_id = $obj->sourcetype == $object->element ? $obj->fk_target : $obj->fk_source;

                    $request = new RequestManager($this->db);
                    $request->fetch($request_id);

                    $requests[$request_id] = $request;
                }

                if (count($requests) > 0) {
                    dol_include_once('/advancedictionaries/class/dictionary.class.php');
                    $requestManagerStatusDictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerstatus');
                    $requestManagerStatusDictionary->fetch_lines(1);

                    foreach ($requests as $requestmanager) {
                        if (isset($requestManagerStatusDictionary->lines[$requestmanager->statut])) {
                            $line = $requestManagerStatusDictionary->lines[$requestmanager->statut];

                            if ($line->fields['next_trigger'] == $action) {
                                $result = $requestmanager->set_status($line->fields['next_status'], -1, $user);
                                if ($result < 0) {
                                    $this->errors = array_merge(array($langs->trans('RequestManagerErrorTriggerNextStatus', $requestmanager->ref)), $requestmanager->errors);
                                    return -1;
                                }
                            }
                        }
                    }

                    dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                }
            }
        }

        return 0;
    }
}