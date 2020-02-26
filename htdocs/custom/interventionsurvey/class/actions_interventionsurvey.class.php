<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    interventionsurvey/class/actions_interventionsurvey.class.php
 * \ingroup interventionsurvey
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsInterventionSurvey
 */
class ActionsInterventionSurvey
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;


    /**
     * Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     * Execute action
     *
     * @param	array			$parameters		Array of parameters
     * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param	string			$action      	'add', 'update', 'view'
     * @return	int         					<0 if KO,
     *                           				=0 if OK but we want to process standard actions too,
     *                            				>0 if OK and we want to replace standard actions.
     */
    public function getNomUrl($parameters, &$object, &$action)
    {
        global $db, $langs, $conf, $user;
        $this->resprints = '';
        return 0;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $errors = array(); // Error array result

        // Goal : do a soft regeneration when linked item to a fichinter is added/removed

        // To do that two case :
        // - we are removing a link ($action == 'dellink') --> so we fetch link before deleting to get intervention id if concerned
        // - we are add a link ($action == 'addlink') --> we look after $object->elementtype,
        //   GETPOST('addlink', 'alpha') and GETPOST('idtolinkto', 'int') to have source and destination element type and id

        //According to these fetched data, if we have to do something, we execute database operations in order to have proper data for soft regeneration
        //Then we delete $_POST["action"] and $_GET["action"] in order to avoid any further action from DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'

        $interventionId = null;
        $otherLinkedElementType = null; //if one item is fichinter, this is the type of the other item
        if ($action == "addlink") {
            $sourceElementType = $object->element;
            $destinationElementType = GETPOST('addlink', 'alpha');
            if ($sourceElementType == "fichinter") {
                $interventionId = $object->id;
                $otherLinkedElementType = $destinationElementType;
            } else if ($destinationElementType == "fichinter") {
                $interventionId = GETPOST('idtolinkto', 'int');
                $otherLinkedElementType = $sourceElementType;
            }
        } else if ($action == "dellink") {

            $sql = "SELECT fk_source, sourcetype, targettype, fk_target FROM " . MAIN_DB_PREFIX . "element_element WHERE rowid = " . GETPOST('dellinkid', 'int');

            $resql = $this->db->query($sql);
            if ($resql) {
                if ($obj = $this->db->fetch_object($resql)) {
                    if ($obj->sourcetype == "fichinter") {
                        $interventionId = $obj->fk_source;
                        $otherLinkedElementType = $obj->fk_target;
                    } else if ($obj->targettype == "fichinter") {
                        $interventionId = $obj->fk_target;
                        $otherLinkedElementType = $obj->fk_source;
                    }
                }
            } else {
                $errors[] = $this->db->lasterror();
            }
        }

        //If action was relating to an intervention, we have $interventionId which is now not null
        if ($interventionId && empty($errors) && $otherLinkedElementType == "equipement") {
            //We do actions in order to have database updated
            global $users;
            //We add value needed for the include, as we don't fetch it from hookmanager
            if($object->element == "fichinter"){
                $permissiondellink = $user->rights->ficheinter->creer;
            }
            else if($object->element == "equipement"){
                $permissiondellink = $user->rights->equipement->creer;
            }
            include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';
            //We launch update of the intervention
            dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
            $interventionSurvey = new InterventionSurvey($this->db);
            if (
                $interventionSurvey->fetch($interventionId) > 0
                && $interventionSurvey->fetchSurvey() > 0
                && !$interventionSurvey->is_survey_read_only()
            ) {
                $interventionSurvey->softUpdateOfSurveyFromDictionary($user);
                $errors = array_merge($errors, $interventionSurvey->errors);
            }
            //As we have already launched the dellink or addlink actions, we change action value
            $action = null;
        }

        if (empty($errors)) {
            $this->results = array('Intervention survey' => 999);
            $this->resprints = 'Actions from Intervention survey ended';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors = array_merge($errors, $interventionSurvey->errors);
            return -1;
        }
    }
}
