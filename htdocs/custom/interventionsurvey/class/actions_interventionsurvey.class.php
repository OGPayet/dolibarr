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
include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
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
     *  @param      DoliDB      $db      Database handler
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

        $contexts = explode(':', $parameters['context']);


        if ($action == "addlink" || $action == "dellink") {
            $errors = array(); // Error array result

            // Goal : do a soft regeneration when linked item to a fichinter is added/removed

            // To do that two case :
            // - we are removing a link ($action == 'dellink') --> so we fetch link before deleting to get intervention id if concerned
            // - we are add a link ($action == 'addlink') --> we look after $object->elementtype,
            //   GETPOST('addlink', 'alpha') and GETPOST('idtolinkto', 'int') to have source and destination element type and id

            //According to these fetched data, if we have to do something, we execute database operations in order to have proper data for soft regeneration
            //Then we delete $_POST["action"] and $_GET["action"] in order to avoid any further action from DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'

            $interventionId = array();
            $otherLinkedElementType = null; //if one item is fichinter, this is the type of the other item
            if ($action == "addlink") {
                $sourceElementType = $object->element;
                $destinationElementType = GETPOST('addlink', 'alpha');
                if ($sourceElementType == "fichinter") {
                    $interventionId = array($object->id);
                    $otherLinkedElementType = $destinationElementType;
                } elseif ($destinationElementType == "fichinter") {
                    $interventionId = GETPOST('idtolinkto', 'array');
                    if (empty($interventionId)) {
                        $interventionId = array(GETPOST('idtolinkto', 'int'));
                    }
                    $otherLinkedElementType = $sourceElementType;
                }
                $id = $object->id; //used for /core/actions_dellink.inc.php
            } elseif ($action == "dellink") {
                //here $action == "dellink"

                $sql = "SELECT fk_source, sourcetype, targettype, fk_target FROM " . MAIN_DB_PREFIX . "element_element WHERE rowid = " . GETPOST('dellinkid', 'int');

                $resql = $this->db->query($sql);
                if ($resql) {
                    if ($obj = $this->db->fetch_object($resql)) {
                        if ($obj->sourcetype == "fichinter") {
                            $interventionId = array($obj->fk_source);
                            $otherLinkedElementType = $obj->targettype;
                        } elseif ($obj->targettype == "fichinter") {
                            $interventionId = array($obj->fk_target);
                            $otherLinkedElementType = $obj->sourcetype;
                        }
                    }
                } else {
                    $errors[] = $this->db->lasterror();
                }
            }

            $interventionId = array_filter($interventionId);

            //If action was relating to an intervention, we have $interventionId which is now not null
            if (!empty($interventionId) && empty($errors) && $otherLinkedElementType == "equipement") {
                //We add value needed for the include, as we don't fetch it from hookmanager
                if ($object->element == "fichinter") {
                    $permissiondellink = $user->rights->ficheinter->creer; //used for /core/actions_dellink.inc.php
                } elseif ($object->element == "equipement") {
                    $permissiondellink = $user->rights->equipement->creer; //used for /core/actions_dellink.inc.php
                }
                //We do actions in order to have database updated
                include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';
                 //As we have already launched the dellink or addlink actions, we change action value
                $action = null;
            }

            foreach ($interventionId as $interId) {
                if ($interId > 0 && empty($errors) && $otherLinkedElementType == "equipement") {
                    //We launch update of the intervention
                    dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
                    $interventionSurvey = new InterventionSurvey($this->db);
                    if ($interventionSurvey->fetch($interId, null, true, true) > 0
                        && (!$interventionSurvey->is_survey_read_only() || $interventionSurvey->statut == InterventionSurvey::STATUS_DRAFT)
                    ) {
                        $interventionSurvey->softUpdateOfSurveyFromDictionary($user);
                        $errors = array_merge($errors, $interventionSurvey->errors);
                    }
                }
            }

            if (empty($errors)) {
                $this->results = array('Intervention survey' => 999);
                $this->resprints = 'Actions from Intervention survey ended';
                return 0; // or return 1 to replace standard code
            } else {
                $this->errors = array_merge($errors, $interventionSurvey->errors);
                return -1;
            }
        } elseif ($action == 'classifyDoneWithoutDataCheck') {
            if ($user->rights->interventionsurvey->survey->noCheck) {
                $object->noSurveyDataCheck = true;
            }
            $action = 'classifydone';
        }

        //Now we manage update of filename into bloc in case we are renaming or removing them
        if (($action == 'confirm_deletefile' && $parameters["confirm"] == 'yes') || ($action == 'renamefile' && GETPOST('renamefilesave'))) {
            global $conf, $user;
            if ($action == 'confirm_deletefile') {
                $urlfile = GETPOST('urlfile', 'alpha', 0, null, null, 1);
                $filename = basename($urlfile);
            } elseif ($action == 'renamefile') {
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
        } elseif (in_array('interventioncard', $contexts) && $action == "addline" && $user->rights->ficheinter->creer) {
            $mesg = array();
            if (empty($conf->global->FICHINTER_WITHOUT_DURATION) && !GETPOST('durationhour', 'int') && !GETPOST('durationmin', 'int')) {
                $mesg[] = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Duration")) . '</div>';
                $error++;
            }
            if (empty($conf->global->FICHINTER_WITHOUT_DURATION) && GETPOST('durationhour', 'int') >= 24 && GETPOST('durationmin', 'int') > 0) {
                $mesg[] = '<div class="error">' . $langs->trans("ErrorValueTooHigh") . '</div>';
                $error++;
            }
            if (!$error) {
                $this->db->begin();

                $desc = GETPOST('np_desc');
                $date_intervention = dol_mktime(GETPOST('dihour', 'int'), GETPOST('dimin', 'int'), 0, GETPOST('dimonth', 'int'), GETPOST('diday', 'int'), GETPOST('diyear', 'int'));
                $duration = empty($conf->global->FICHINTER_WITHOUT_DURATION) ? convertTime2Seconds(GETPOST('durationhour', 'int'), GETPOST('durationmin', 'int')) : 0;


                // Extrafields
                $extrafieldsline = new ExtraFields($this->db);
                $extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
                $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);

                $result = $object->addline(
                    $userd,
                    $object->id,
                    $desc,
                    $date_intervention,
                    $duration,
                    $array_options
                );

                // Define output language
                $outputlangs = $langs;
                $newlang = '';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
                    $newlang = GETPOST('lang_id', 'aZ09');
                }
                if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                    $newlang = $object->thirdparty->default_lang;
                }
                if (!empty($newlang)) {
                    $outputlangs = new Translate("", $conf);
                    $outputlangs->setDefaultLang($newlang);
                }

                if ($result >= 0) {
                    $this->db->commit();
                    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                        fichinter_create($this->db, $object, $object->modelpdf, $outputlangs);
                    }
                    header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
                    exit;
                } else {
                    $mesg[] = $object->error;
                    $this->db->rollback();
                }
            }
            if (!empty($mesg)) {
                setEventMessages('', $mesg, 'errors');
            }
            $action = null;
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

    public function addMoreActionsButtons($parameters = array(), &$object, &$action = '', $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array("interventioncard", $contexts)) {
            if ($user->societe_id == 0) {
                if ($action != 'editdescription' && ($action != 'presend')) {
                    //Validate fichinter without survey check
                    if ($user->rights->interventionsurvey->survey->noCheck && empty($conf->global->FICHINTER_CLASSIFY_BILLED) && $object->statut > 0 && $object->statut < 3 && !empty($conf->global->INTERVENTIONSURVEY_STRICT_DATA_CHECK_ON_CLOTURED)) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=classifyDoneWithoutDataCheck">' . $langs->trans("InterventionSurveyClassifyDoneWithoutDataCheck") . '</a></div>';
                    }

                    // Validate
                    if ($object->statut == Fichinter::STATUS_DRAFT && count($object->lines) == 0 && empty($conf->global->FICHINTER_DISABLE_DETAILS)) {
                        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->ficheinter->creer) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->ficheinter->ficheinter_advance->validate)) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id=' . $object->id . '&action=validate"';
                            print '>' . $langs->trans("Validate") . '</a></div>';
                        }
                    }
                                // ReOpen
                    if ($object->statut == 2 /* invoiced */ || $object->statut == 3 /* done */) {
                        $langs->load('synergiestech@synergiestech');
                        if ($user->rights->synergiestech->fichinter->reopen) {
                            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=reopen' . (empty($conf->global->MAIN_JUMP_TAG) ? '' : '#reopen') . '">' .
                                $langs->trans("ReOpen") . '</a></div>';
                        } else {
                            print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans("ReOpen") . '</a></div>';
                        }
                        //We hide classify done button
            $buttonContent = dol_string_nohtmltag($langs->trans("Reopen"));
            print <<<SCRIPT
<script type="text/javascript">
    $(document).ready(function() {
        $("a:contains('$buttonContent')").hide();
    });
</script>
SCRIPT;
                    }
                }
            }
            return 0;
        }
    }

    /**
     * Overloading the addMoreToEmail function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
    */
    public function addMoreToEmail($parameters = array(), &$object, &$action = '', $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);
        $errors = array();
        $emailList = $parameters['emailList'];
        $this->results = array();
        if (in_array("interventionmail", $contexts)) {
            // Check if customer is absent
            if (!empty($object->array_options['options_customer_signature']) && $conf->global->INTERVENTIONSURVEY_SEND_MAIL_TO_SIGNATORY_CUSTOMER) {
                $customer_signature = json_decode($object->array_options['options_customer_signature'], true);
                $isCustomerAbsent = $customer_signature['isCustomerAbsent'];
                // Add signatory customer to the emailList
                if (!$isCustomerAbsent) {
                    if (!empty($customer_signature['people'])) {
                        foreach ($customer_signature['people'] as $signatoryCustomer) {
                            $contact = new Contact($this->db);
                            $result = $contact->fetch($signatoryCustomer['identifier']);
                            $errors = array_merge($errors, $contact->errors);
                            if ($result > 0 && !empty($contact->email)) {
                                array_push($emailList, $contact->email);
                            }
                        }
                    }
                }
            }
            // Add signatory user to the emailList
            if (!empty($object->array_options['options_stakeholder_signature']) && $conf->global->INTERVENTIONSURVEY_SEND_MAIL_TO_SIGNATORY_STAKEHOLDER) {
                $stakeholder_signature = json_decode($object->array_options['options_stakeholder_signature'], true);
                if (!empty($stakeholder_signature['people'])) {
                    foreach ($stakeholder_signature['people'] as $signatoryUser) {
                        $user = new User($this->db);
                        $result = $user->fetch($signatoryUser['identifier']);
                        $errors = array_merge($errors, $user->errors);
                        if ($result > 0 && !empty($user->email)) {
                            array_push($emailList, $user->email);
                        }
                    }
                }
            }
            $this->results = $emailList;
            $this->errors = array_merge($this->errors, $errors);
            if (!empty($errors)) {
                return -1;
            } else {
                return 0;
            }
        }
    }
}
