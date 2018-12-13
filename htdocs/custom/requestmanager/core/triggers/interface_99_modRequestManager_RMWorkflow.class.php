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
	public $picto = 'technic';


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
        if (empty($conf->requestmanager->enabled)) return 0;     // Module not active, we do nothing

        /**
         * Propagation of requests links
         */

        if (!empty($object->origin) && $object->origin_id > 0) {
            // Parse element/subelement (ex: project_task)
            $element = $subelement = $object->origin;
            if (preg_match('/^([^_]+)_([^_]+)/i', $object->origin, $regs)) {
                $element = $regs [1];
                $subelement = $regs [2];
            }

            // For compatibility
            if ($element == 'order') {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal') {
                $element = 'comm/propal';
                $subelement = 'propal';
            }
            if ($element == 'contract') {
                $element = $subelement = 'contrat';
            }
            if ($element == 'inter') {
                $element = $subelement = 'ficheinter';
            }
            if ($element == 'shipping') {
                $element = $subelement = 'expedition';
            }

            dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
            $classname = ucfirst($subelement);
            $srcobject = new $classname($this->db);
            if (method_exists($srcobject, 'fetchObjectLinked')) {
                $result = $srcobject->fetch($object->origin_id);
                if ($result > 0) {
                    $srcobject->fetchObjectLinked();
                    $requestmanager_ids = $srcobject->linkedObjectsIds['requestmanager'];
                    if (isset($requestmanager_ids)) {
                        // Add object linked
                        if (is_array($requestmanager_ids)) {       // New behaviour, if linkedObjectsIds can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
                            foreach ($requestmanager_ids as $origin_id) {
                                if ($object->element == 'requestmanager' && $origin_id == $object->id) continue;
                                $ret = $object->add_object_linked('requestmanager', $origin_id);
                                if (!$ret && $this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                                    $this->errors[] = $this->db->lasterror();
                                    return -1;
                                }
                            }
                        } else {                               // Old behaviour, if linkedObjectsIds has only one link per type, so is something like array('contract'=>id1))
                            $origin_id = $requestmanager_ids;
                            if ($object->element != 'requestmanager' || $origin_id != $object->id) {
                                $ret = $object->add_object_linked('requestmanager', $origin_id);
                                if (!$ret && $this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                                    $this->errors[] = $this->db->lasterror();
                                    return -1;
                                }
                            }
                        }
                    }
                }
            }

            dol_syslog("Trigger '" . $this->name . "' for action '$action' [propagation of requests links] launched by " . __FILE__ . ". id=" . $object->id . " origin=" . $object->origin . " originid=" . $object->origin_id);
        }

        /**
         * Auto set next status of the requests
         */
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
                            $next_status = !empty($line->fields['next_status']) ? explode(',', $line->fields['next_status']) : array();

                            // Auto set next status of the requests (by next trigger)
                            if ($line->fields['next_trigger'] == $action) {
                                if (count($next_status) == 1) {
                                    $result = $requestmanager->set_status($line->fields['next_status'], -1, $user);
                                    if ($result < 0) {
                                        $this->errors = array_merge(array($langs->trans('RequestManagerErrorTriggerNextStatus', $requestmanager->ref)), $requestmanager->errors);
                                        return -1;
                                    }
                                } else {
                                    $this->errors = array_merge(array($langs->trans('RequestManagerErrorTriggerNextStatus', $requestmanager->ref)), array($langs->trans('RequestManagerErrorMustBeOneNextStatus')));
                                    return -1;
                                }
                            } else {
                                // Auto set next status of the requests (by current trigger for each next status)
                                if (count($next_status)) {
                                    foreach ($next_status as $next_status_id) {
                                        $next_status_line = $requestManagerStatusDictionary->lines[$next_status_id];
                                        if ($next_status_line->fields['current_trigger'] == $action) {
                                            $result = $requestmanager->set_status($next_status_id, -1, $user);
                                            if ($result < 0) {
                                                $this->errors = array_merge(array($langs->trans('RequestManagerErrorTriggerNextStatus', $requestmanager->ref)), $requestmanager->errors);
                                                return -1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    dol_syslog("Trigger '" . $this->name . "' for action '$action' [auto set next status] launched by " . __FILE__ . ". id=" . $object->id);
                }
            }
        }

        return 0;
    }
}