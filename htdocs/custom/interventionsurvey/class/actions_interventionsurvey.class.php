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
        //Be careful with hook context declared in this module (core/modules/modInterventionSurvey.class.php) - this file is not always executed
        global $user, $langs, $conf;

        if ($action == "addlink" || $action == "dellink") {
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
                $id = $object->id; //used for /core/actions_dellink.inc.php
            } else {
                //here $action == "dellink"

                $sql = "SELECT fk_source, sourcetype, targettype, fk_target FROM " . MAIN_DB_PREFIX . "element_element WHERE rowid = " . GETPOST('dellinkid', 'int');

                $resql = $this->db->query($sql);
                if ($resql) {
                    if ($obj = $this->db->fetch_object($resql)) {
                        if ($obj->sourcetype == "fichinter") {
                            $interventionId = $obj->fk_source;
                            $otherLinkedElementType = $obj->targettype;
                        } else if ($obj->targettype == "fichinter") {
                            $interventionId = $obj->fk_target;
                            $otherLinkedElementType = $obj->sourcetype;
                        }
                    }
                } else {
                    $errors[] = $this->db->lasterror();
                }
            }

            //If action was relating to an intervention, we have $interventionId which is now not null
            if ($interventionId && empty($errors) && $otherLinkedElementType == "equipement") {
                //We add value needed for the include, as we don't fetch it from hookmanager
                if ($object->element == "fichinter") {
                    $permissiondellink = $user->rights->ficheinter->creer; //used for /core/actions_dellink.inc.php
                } else if ($object->element == "equipement") {
                    $permissiondellink = $user->rights->equipement->creer; //used for /core/actions_dellink.inc.php
                }
                //We do actions in order to have database updated
                include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';
                //We launch update of the intervention
                dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
                $interventionSurvey = new InterventionSurvey($this->db);
                if (
                    $interventionSurvey->fetch($interventionId) > 0
                    && $interventionSurvey->fetchSurvey() > 0
                    && (!$interventionSurvey->is_survey_read_only() || $interventionSurvey->statut == InterventionSurvey::STATUS_DRAFT)
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
        } else if ($action == 'classifyDoneWithoutDataCheck') {
            if ($user->rights->interventionsurvey->survey->noCheck) {
                $object->noSurveyDataCheck = true;
            }
            $action = 'classifydone';
        }


        if ($action == 'classifydone' && !$object->noSurveyDataCheck && !empty($conf->global->INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED)) {
            //We emit an error in order to avoid fichinter to be classify done if some required data are missing
            dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
            $interventionSurvey = new InterventionSurvey($this->db);
            if (
                $interventionSurvey->fetch($object->id) > 0
                && $interventionSurvey->fetchSurvey() > 0
            ) {
                if (!$interventionSurvey->areDataValid()) {
                    $hookmanager->errors[] = $langs->trans('InterventionSurveyMissingRequiredFieldInSurvey');
                    $hookmanager->errors = array_merge($hookmanager->errors, $interventionSurvey->errors);
                    return -1;
                }
            }
        }

        //Now we manage update of filename into bloc in case we are renaming or removing them
        if (($action == 'confirm_deletefile' && $parameters["confirm"] == 'yes') || ($action == 'renamefile' && GETPOST('renamefilesave'))) {
            global $conf, $user;
            if ($action == 'confirm_deletefile') {
                $urlfile = GETPOST('urlfile', 'alpha', 0, null, null, 1);
                $filename = basename($urlfile);
            } else if ($action == 'renamefile') {
                $filename = dol_sanitizeFileName(GETPOST('renamefilefrom', 'alpha'));
                $newfilename = dol_sanitizeFileName(GETPOST('renamefileto', 'alpha'));

                // Security:
                // Disallow file with some extensions. We rename them.
                // Because if we put the documents directory into a directory inside web root (very bad), this allows to execute on demand arbitrary code.
                if (preg_match('/\.htm|\.html|\.php|\.pl|\.cgi$/i', $newfilename) && empty($conf->global->MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED)) {
                    $newfilename .= '.noexe';
                }
            }

            //Now we have the filename of the file on which we work in $filename
            //File is renamed to $newfilename or deleted if this value is null

            foreach ($object->survey as $surveyPart) {
                foreach ($surveyPart->blocs as $bloc) {
                    foreach ($bloc->attached_files as $index => $file) {
                        if ($file == $filename) {
                            if ($newfilename) {
                                $bloc->attached_files[$index] = $newfilename;
                            } else {
                                unset($bloc->attached_files[$index]);
                            }
                        }
                    }
                }
            }
            $object->saveSurvey($user, true);
        }



        return 0;
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */

    function addMoreActionsButtons($parameters = array(), &$object, &$action = '', $hookmanager)
    {
        global $conf, $user, $langs;

        if ($user->rights->interventionsurvey->survey->noCheck && empty($conf->global->FICHINTER_CLASSIFY_BILLED) && $object->statut > 0 && $object->statut < 3 && !empty($conf->global->INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED)) {
            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=classifyDoneWithoutDataCheck">' . $langs->trans("InterventionSurveyClassifyDoneWithoutDataCheck") . '</a></div>';
        }
        return 0;
    }
}
