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
 *	\file       htdocs/eventconfidentiality/core/triggers/interface_99_modEventConfidentiality_ECWorkflow.class.php
 *  \ingroup    eventconfidentiality
 *	\brief      File of class of triggers for workflow in eventconfidentiality module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for workflow in eventconfidentiality module
 */
class InterfaceECWorkflow1 extends DolibarrTriggers
{
	public $family = 'eventconfidentiality';
	public $description = "Triggers of this module catch triggers event for the workflow of EventConfidentiality module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'eventconfidentiality@eventconfidentiality';

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
        if (empty($conf->eventconfidentiality->enabled)) return 0;     // Module not active, we do nothing

        switch ($action) {
            // Action
            case 'ACTION_CREATE':
                // Get all tags
                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary = Dictionary::getDictionary($this->db, 'eventconfidentiality', 'eventconfidentialitytag');
                $result = $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
                if ($result < 0) {
                    $this->error = $dictionary->error;
                    $this->errors = $dictionary->errors;
                    return -1;
                }

                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);

                // Get default tags
                $default_tags = $eventconfidentiality->getDefaultTags($object->elementtype, $object->type_id);
                if (!is_array($default_tags)) {
                    $this->error = $eventconfidentiality->error;
                    $this->errors = $eventconfidentiality->errors;
                    return -1;
                }
                $default_tags = $default_tags[$object->type_code];

                foreach ($dictionary->lines as $line) {
                    $mode = isset($_POST['ec_mode_' . $line->id]) ? GETPOST('ec_mode_' . $line->id, 'int') :
                        (isset($object->ec_mode_tags[$line->id]) ? $object->ec_mode_tags[$line->id] :
                            (isset($default_tags[$line->id]['mode']) ? $default_tags[$line->id]['mode'] : EventConfidentiality::MODE_HIDDEN));

                    if ($mode != EventConfidentiality::MODE_HIDDEN) {
                        $eventconfidentiality->fk_actioncomm = $object->id;
                        $eventconfidentiality->fk_c_eventconfidentiality_tag = $line->id;
                        $eventconfidentiality->mode = $mode;
                        $result = $eventconfidentiality->create($user);
                        if ($result < 0) {
                            $this->error = $eventconfidentiality->error;
                            $this->errors = $eventconfidentiality->errors;
                            return -1;
                        }
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;

            case 'ACTION_MODIFY':
                // Get all tags
                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary = Dictionary::getDictionary($this->db, 'eventconfidentiality', 'eventconfidentialitytag');
                $result = $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
                if ($result < 0) {
                    $this->error = $dictionary->error;
                    $this->errors = $dictionary->errors;
                    return -1;
                }

                // Get tags set
                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);
                $tags_set = $eventconfidentiality->fetchAllTagsOfEvent($object->id);
                if (!is_array($tags_set)) {
                    $this->error = $eventconfidentiality->error;
                    $this->errors = $eventconfidentiality->errors;
                    return -1;
                }

                foreach ($dictionary->lines as $line) {
                    $mode = isset($_POST['ec_mode_' . $line->id]) ? GETPOST('ec_mode_' . $line->id, 'int') :
                        (isset($object->ec_mode_tags[$line->id]) ? $object->ec_mode_tags[$line->id] :
                            (isset($tags_set[$line->id]['mode']) ? $tags_set[$line->id]['mode'] : EventConfidentiality::MODE_HIDDEN));
                    $eventconfidentiality->fk_actioncomm = $object->id;
                    $eventconfidentiality->fk_c_eventconfidentiality_tag = $line->id;

                    if ($mode != EventConfidentiality::MODE_HIDDEN) {
                        $eventconfidentiality->mode = $mode;
                        $result = 0;
                        if ($tags_set[$line->id]['mode'] == EventConfidentiality::MODE_HIDDEN) {
                            $result = $eventconfidentiality->create($user);
                        } else if ($tags_set[$line->id]['mode'] != $mode) {
                            $result = $eventconfidentiality->update($user);
                        }
                        if ($result < 0) {
                            $this->error = $eventconfidentiality->error;
                            $this->errors = $eventconfidentiality->errors;
                            return -1;
                        }
                    } elseif ($tags_set[$line->id]['mode'] != EventConfidentiality::MODE_HIDDEN) {
                        $result = $eventconfidentiality->delete();
                        if ($result < 0) {
                            $this->error = $eventconfidentiality->error;
                            $this->errors = $eventconfidentiality->errors;
                            return -1;
                        }
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;

            case 'ACTION_DELETE':
                // Delete all tags
                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);
                $eventconfidentiality->fk_actioncomm = $object->id;
                $result = $eventconfidentiality->delete();
                if ($result < 0) {
                    $this->error = $eventconfidentiality->error;
                    $this->errors = $eventconfidentiality->errors;
                    return -1;
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }

        return 0;
    }
}