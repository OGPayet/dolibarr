<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2020      Alexis LAURIER       <contact@alexislaurier.fr>
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
 *	\file       htdocs/extendedintervention/core/triggers/interface_99_modExtendedIntervention_ExtendedIntervention.class.php
 *  \ingroup    extendedintervention
 *	\brief      File of class of triggers for Extended Intervention module
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Extended Intervention module
 */
class InterfaceExtendedIntervention extends DolibarrTriggers
{
	public $family = 'extendedintervention';
	public $description = "Triggers of this module catch triggers event for Extended Intervention module.";
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
        if (empty($conf->extendedintervention->enabled)) return 0;     // Module not active, we do nothing

        switch ($action) {
            // Interventions
            case 'FICHINTER_CREATE':
                if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE) && !empty($object->ei_created_out_of_quota)) {
                    dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                    $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                    $reason = GETPOST('ei_reason', "alpha");
                    $result = $extendedinterventionquota->addActionForcedCreatedOutOfQuota($object, $user, $reason);
                    if ($result < 0) {
                        $this->error = $extendedinterventionquota->error;
                        $this->errors = $extendedinterventionquota->errors;
                        return -1;
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
            case 'FICHINTER_CLASSIFY_DONE':
            case 'FICHINTER_CLASSIFY_BILLED':
            case 'FICHINTER_CLASSIFY_UNBILLED':
            case 'FICHINTER_DELETE':
            // Contracts
            case 'CONTRACT_CREATE':
                if (!empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
                    $error = 0;
                    $langs->load('extendedintervention@extendedintervention');

                    $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();

                    dol_include_once('/advancedictionaries/class/dictionary.class.php');
                    $inter_type_dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventiontype');
                    $inter_type_dictionary->fetch_lines(1, array('count' => 1), array('label' => 'ASC'));

                    dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                    $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                    if ($conf->requestmanager->enabled && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
                        $info = $extendedinterventionquota->getInfoInterventionOfContract($object->id);
                    }

                    $values = array();
                    foreach ($inter_type_dictionary->lines as $line) {
                        $htmlname = 'ei_count_type_' . $line->fields['code'];
                        if (isset($_POST[$htmlname]) || isset($_GET[$htmlname])) {
                            $values[$line->id] = GETPOST($htmlname, 'int') ? GETPOST($htmlname, 'int') : 0;
                        }

                        if ($conf->requestmanager->enabled && !empty($conf->global->REQUESTMANAGER_PLANNING_ACTIVATE)) {
                            $count = isset($values[$line->id]['count']) ? $values[$line->id]['count'] : (isset($info[$line->id]['count']) ? $info[$line->id]['count'] : '');

                            $found = false;
                            $planning_count = 0;
                            $htmlname = 'ei_planning_times_type_' . $line->fields['code'];

                            foreach ($request_types_planned as $request_type) {
                                $sub_htmlname = $htmlname . '_' . $request_type;

                                if (isset($_POST[$sub_htmlname]) || isset($_GET[$sub_htmlname])) {
                                    $values[$line->id]['planning_times'][$request_type] = GETPOST($sub_htmlname, 'array') ? GETPOST($sub_htmlname, 'array') : array();
                                    $planning_count += count($values[$line->id]['planning_times'][$request_type]);
                                    $found = true;
                                }
                            }

                            if ($found && $planning_count != $count) {
                                $action = 'edit' . $htmlname;
                                if (isset($_POST['action'])) $_POST['action'] = $action;
                                if (isset($_GET['action'])) $_GET['action'] = $action;
                                setEventMessages($langs->trans("ExtendedInterventionErrorPlanningTimesPlannedMismatchedWithQuota", $planning_count, $line->fields['label'], $count), null, 'errors');
                                $error++;
                                break;
                            }
                        }
                    }

                    if (!$error && count($values) > 0) {
                        $res = $extendedinterventionquota->setInfoInterventionOfContract($object->id, $values);
                        if ($res < 0) {
                            setEventMessages($extendedinterventionquota->error, $extendedinterventionquota->errors, 'errors');
                        }
                    }
                }

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
            case 'CONTRACT_DELETE':
                dol_include_once('/extendedintervention/class/extendedinterventionquota.class.php');
                $extendedinterventionquota = new ExtendedInterventionQuota($this->db);

                $extendedinterventionquota->delAllCountInterventionOfContract($object->id);

                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }

        return 0;
    }
}