<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
 * Copyright (C) 2018  Alexis LAURIER <contact@alexislaurier.fr>
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
 * \file       htdocs/extendedintervention/survey.php
 * \ingroup    extendedintervention
 * \brief      Tab for the management of the survey of a intervention
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include '../main.inc.php';                    // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
dol_include_once('/interventionsurvey/class/html.forminterventionsurvey.class.php');

$langs->load("interventions");
$langs->load("interventionsurvey@interventionsurvey");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$survey_bloc_question_id = GETPOST('survey_bloc_question_id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check
if ($user->societe_id) $socid = $user->societe_id;

if (empty($user->rights->interventionsurvey->survey->read) || !$id) accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('interventionsurvey'));

$object = new InterventionSurvey($db);

//Readonly survey mode
$readOnlySurvey = true;


// Load object
if ($id > 0 || !empty($ref)) {
    $ret = $object->fetch($id, $ref, true, true);
    $object->fetch_thirdparty();
    if (!$object->checkUserAccess($user)) {
        accessforbidden();
    }
    if ($ret == -1 && $object->id == 0) {
        setEventMessages($langs->trans('NoRecordFound'), array(), 'errors');
    } else if ($ret < 0) {
        setEventMessages("", $object->errors, 'errors');
    }
}

$readOnlySurvey = $object->statut == $object::STATUS_DONE;
$form = new Form($db);
$formextendedintervention = new FormInterventionSurvey($db);
$formproject = new FormProjets($db);

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

//$survey_bloc_question is the object used to perform actions on bloc and display some informations in confirm action

if ($survey_bloc_question_id) {
    $survey_bloc_question = new SurveyBlocQuestion($db);
    $survey_bloc_question->errors = array();
    $result = $survey_bloc_question->fetch($survey_bloc_question_id) > 0;
}

if (empty($reshook) && $object->id > 0 && $action && !$readOnlySurvey) {

    if ($user->rights->interventionsurvey->survey->write && $action == 'save_question_bloc') {
        if ($result > 0) {
            $survey_bloc_question = $formextendedintervention->updateBlocObjectFromPOST($survey_bloc_question);
            $survey_bloc_question->attached_files = $formextendedintervention->updateFieldFromGETPOST($survey_bloc_question, "attached_files", $formextendedintervention::BLOC_FORM_PREFIX, array());
            $survey_bloc_question->private = $formextendedintervention->updateFieldFromGETPOST($survey_bloc_question, "private", $formextendedintervention::BLOC_FORM_PREFIX, 0);
            //We set extrafields
            $survey_bloc_question->array_options = $survey_bloc_question::$extrafields_cache->getOptionalsFromPost($survey_bloc_question::$extrafields_label_cache, '_intervention_survey_question_bloc_' . $survey_bloc_question->id . '_');
            foreach ($survey_bloc_question->questions as $question) {
                $question->array_options = $question::$extrafields_cache->getOptionalsFromPost($question::$extrafields_label_cache, '_intervention_survey_question_' . $question->id . '_');
            }
            $result = $survey_bloc_question->save($user);
            if ($result < 0 || $survey_bloc_question->errors) {
                setEventMessages("", $survey_bloc_question->errors, 'errors');
                $action = "edit_question_bloc";
            }
        }
    } else if ($user->rights->interventionsurvey->survey->write && ($action == 'confirm_delete_bloc' && $confirm == 'yes')) {
        if ($result > 0 && $survey_bloc_question->deletable) {
            $result = $survey_bloc_question->delete($user);
            if ($result > 0) {
                $object->cleanSurvey($user);
            }
        }
        if ($result < 0 || $survey_bloc_question->errors) {
            $survey_bloc_question->errors[] = $langs->trans('InterventionSurveyCantDeleteBloc', $survey_bloc_question_id);
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_soft_regeneration' && $confirm == 'yes') {
        $result = $object->softUpdateOfSurveyFromDictionary($user);
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_add_missing_part_only' && $confirm == 'yes') {
        $result = $object->mergeCurrentSurveyWithDictionaryData($user, false, false, true, false, false);
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_add_missing_bloc_in_general_part' && $confirm == 'yes') {
        $result = $object->mergeCurrentSurveyWithDictionaryData($user, false, false, false, true, false);
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_add_missing_bloc_in_other_part' && $confirm == 'yes') {
        $result = $object->mergeCurrentSurveyWithDictionaryData($user, false, false, false, false, true);
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_add_missing_bloc' && $confirm == 'yes') {
        $result = $object->mergeCurrentSurveyWithDictionaryData($user, false, false, false, true, true);
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_hard_regeneration' && $confirm == 'yes') {
        $result = $object->mergeCurrentSurveyWithDictionaryData($user, true, true, true, true, true);
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_reset_survey' && $confirm == 'yes') {
        $result = $object->deleteSurvey($user);
        if ($result > 0) {
            $object->survey = array();
            $result = $object->mergeCurrentSurveyWithDictionaryData($user, true, true, true, true, true);
        }
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $survey_bloc_question->errors, 'errors');
        }
    } else if ($action == 'confirm_autocomplete_survey' && $confirm == 'yes') {
        $result = $object->autoComplete();
        if ($result < 0 || $survey_bloc_question->errors) {
            setEventMessages("", $object->errors, 'errors');
        }
    }
}


/*
 * View
 */

llxHeader('', $langs->trans("Intervention"));

// Confirmation to delete bloc
if ($action == 'delete_bloc') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formquestion[] = array('type' => 'hidden', 'name' => 'survey_bloc_question_id', 'value' => $survey_bloc_question_id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmDeleteBlocTitle'), $langs->trans('InterventionSurveyConfirmDeleteBlocDescription', $survey_bloc_question->label), 'confirm_delete_bloc', $formquestion, 'yes', 1);
}
// Confirmation to soft regeneration
else if ($action == 'soft_regeneration') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmSoftRegenerationTitle'), $langs->trans('InterventionSurveyConfirmSoftRegenerationDescription'), 'confirm_soft_regeneration', $formquestion, 'yes', 1);
}

// Confirmation to hard regeneration
else if ($action == 'hard_regeneration') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmHardRegenerationTitle'), $langs->trans('InterventionSurveyConfirmHardRegenerationDescription'), 'confirm_hard_regeneration', $formquestion, 'yes', 1);
}

// Confirmation to reset survey
else if ($action == 'reset_survey') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmResetTitle'), $langs->trans('InterventionSurveyConfirmResetDescription'), 'confirm_reset_survey', $formquestion, 'yes', 1);
}

// Confirmation add only missing part
else if ($action == 'add_missing_part_only') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmAddMissingPartTitle'), $langs->trans('InterventionSurveyConfirmAddMissingPartDescription'), 'confirm_add_missing_part_only', $formquestion, 'yes', 1);
}

// Confirmation add missing bloc only in general part
else if ($action == 'add_missing_bloc_in_general_part') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmAddMissingBlocInGeneralPartTitle'), $langs->trans('InterventionSurveyConfirmAddMissingBlocInGeneralPartDescription'), 'confirm_add_missing_bloc_in_general_part', $formquestion, 'yes', 1);
}

// Confirmation add missing bloc in all parts excepting general part
else if ($action == 'add_missing_bloc_in_other_part') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmAddMissingBlocInOtherPartTitle'), $langs->trans('InterventionSurveyConfirmAddMissingBlocInOtherPartDescription'), 'confirm_add_missing_bloc_in_other_part', $formquestion, 'yes', 1);
}

// Confirmation add missing bloc in all parts
else if ($action == 'add_missing_bloc') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmAddMissingBlocTitle'), $langs->trans('InterventionSurveyConfirmAddMissingBlocDescription'), 'confirm_add_missing_bloc', $formquestion, 'yes', 1);
}

// Confirmation autocomplete
else if ($action == 'autocomplete_survey') {
    $formquestion = array();
    $formquestion[] = array('type' => 'hidden', 'name' => 'id', 'value' => $object->id);
    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('InterventionSurveyConfirmAutoCompleteTitle'), $langs->trans('InterventionSurveyConfirmAutocompleteDescription'), 'confirm_autocomplete_survey', $formquestion, 'yes', 1);
}

// Call Hook formConfirm
$parameters = array('formConfirm' => $formconfirm);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

// Print form confirm
print $formconfirm;

// Mode vue et edition
if ($object->id > 0) {
    $head = fichinter_prepare_head($object);
    dol_fiche_head($head, 'interventionsurvey', $langs->trans("InterventionCard"), -1, 'intervention');

    // Intervention card
    $linkback = '<a href="' . DOL_URL_ROOT . '/fichinter/list.php' . (!empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    // Ref customer
    // Thirdparty
    $morehtmlref .= $langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
    // Project
    if (!empty($conf->projet->enabled)) {
        $langs->load("projects");
        $morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
        if ($user->rights->interventionsurvey->survey->write) {
            if ($action != 'classify')
                $morehtmlref .= ' : ';
            if ($action == 'classify') {
                $morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="' . $langs->trans("Modify") . '">';
                $morehtmlref .= '</form>';
            } else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        } else {
            if (!empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref .= '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
                $morehtmlref .= $proj->ref;
                $morehtmlref .= '</a>';
            } else {
                $morehtmlref .= '';
            }
        }
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

    print '<br>';


    //Prepare needed data for following form
    $object->fetch_attached_files();
    $object->fetch($id, $ref, true, true);

    if (!empty($object->errors)) {
        setEventMessages("", $object->errors, 'errors');
        $ret = -1;
    }

    //    if ($object->statut == InterventionSurvey::STATUS_DRAFT) {
    //         print $langs->trans('InterventionSurveyMustBeValidated');
    //         print '<br>';
    //     }

    if ($readOnlySurvey) {
        print $langs->trans('InterventionSurveyReadOnlyMode');
        print '<br>';
    }

    if (empty($object->survey)) {
        print $langs->trans('InterventionSurveyEmptySurvey');
        print '<br>';
    }
    // Print left question bloc of the survey
    else {
        foreach ($object->survey as $survey_part) {
            print load_fiche_titre('<b>' . $survey_part->label . '</b>', '', '');
            $idx = 1;
            foreach ($survey_part->blocs as $bloc) {
                if ($idx % 2 == 1) {
                    print '<div class="fichecenter border">';
                }
                $blocPrefix = $formextendedintervention::BLOC_FORM_PREFIX;
                $questionPrefix = $formextendedintervention::QUESTION_FORM_PREFIX;
                if ($user->rights->interventionsurvey->survey->write && $action == 'edit_question_bloc' && $bloc->id == $survey_bloc_question_id && !$readOnlySurvey) {
                    $bloc = $formextendedintervention->updateBlocObjectFromPOST($bloc);
                    @include dol_buildpath('interventionsurvey/tpl/intervention_survey_bloc_question_edit.tpl.php');
                } else {
                    @include dol_buildpath('interventionsurvey/tpl/intervention_survey_bloc_question_view.tpl.php');
                }
                if ($idx % 2 == 0) {
                    print '</div>';
                }
                $idx++;
            }
            if ($idx % 2 != 1) {
                print '</div>';
            }
        }
        dol_fiche_end();
    }


    /*
	 * Actions buttons
	 */

    print '<div class="tabsAction">';

    $parameters = array();
    $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
    // modified by hook
    if (empty($reshook) &&  ($user->rights->interventionsurvey->survey->manage || $user->rights->interventionsurvey->survey->manage_more) && !$readOnlySurvey) {
        print '<div class="inline-block divButAction">';
        if ($user->rights->interventionsurvey->survey->autocomplete) {
            print '<div class="inline-block divButAction">';
            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=autocomplete_survey">'
                . $langs->trans("InterventionSurveyAutoCompleteButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmAutocompleteDescription'), 1, 'info', '', 0, 2) .
                '</a>';
            print '</div>';
        }

        if ($user->rights->interventionsurvey->survey->manage) {
            print '<div class="inline-block divButAction">';

            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=soft_regeneration">'
                . $langs->trans("InterventionSurveyConfirmSoftRegenerationButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmSoftRegenerationDescription'), 1, 'info', '', 0, 2) .
                '</a>';
                print '</div>';

                print '<div class="inline-block divButAction">';

            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=add_missing_part_only">'
                . $langs->trans("InterventionSurveyConfirmAddMissingPartButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmAddMissingPartDescription'), 1, 'info', '', 0, 2) .
                '</a>';
                print '</div>';

                print '<div class="inline-block divButAction">';

                print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=add_missing_bloc_in_general_part">'
                . $langs->trans("InterventionSurveyConfirmAddMissingBlocInGeneralPartButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmAddMissingBlocInGeneralPartDescription'), 1, 'info', '', 0, 2) .
                '</a>';
                print '</div>';

                print '<div class="inline-block divButAction">';

                print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=add_missing_bloc_in_other_part">'
                . $langs->trans("InterventionSurveyConfirmAddMissingBlocInOtherPartButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmAddMissingBlocInOtherPartDescription'), 1, 'info', '', 0, 2) .
                '</a>';
                print '</div>';

                print '<div class="inline-block divButAction">';

                print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=add_missing_bloc">'
                . $langs->trans("InterventionSurveyConfirmAddMissingBlocButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmAddMissingBlocDescription'), 1, 'info', '', 0, 2) .
                '</a>';
                print '</div>';

        }

        if ($user->rights->interventionsurvey->survey->manageMore) {
            print '<div class="inline-block divButAction">';

            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=hard_regeneration">'
                . $langs->trans("InterventionSurveyConfirmHardRegenerationButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmHardRegenerationDescription'), 1, 'info', '', 0, 2) .
                '</a>';
                print '</div>';

                print '<div class="inline-block divButAction">';

            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=reset_survey">'
                . $langs->trans("InterventionSurveyConfirmResetButton") . ' ' .
                $form->textwithpicto('', $langs->trans('InterventionSurveyConfirmResetDescription'), 1, 'info', '', 0, 2) .
                '</a>';
                print '</div>';

        }




        print '</div>';
    }

    print '</div>';
}

llxFooter();
$db->close();
