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
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
dol_include_once('/interventionsurvey/class/html.forminterventionsurvey.class.php');

$langs->load("interventions");
$langs->load("interventionsurvey@interventionsurvey");

$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$survey_bloc_question_id = GETPOST('survey_bloc_question_id','int');
$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');
$backtopage = GETPOST('backtopage','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

if(empty($user->rights->interventionsurvey->survey->read)) accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('interventionsurvey'));

$object = new InterventionSurvey($db);

// Optionals attributes and labels for question bloc
$extrafields_interventionsurvey_surveyblocquestion = new ExtraFields($db);
$extralabels_interventionsurvey_surveyblocquestion = $extrafields_interventionsurvey_surveyblocquestion->fetch_name_optionals_label('interventionsurvey_surveyblocquestion');
// Optionals attributes and labels for question
$extrafields_interventionsurvey_surveyquestion = new ExtraFields($db);
$extralabels_interventionsurvey_surveyquestion = $extrafields_interventionsurvey_surveyquestion->fetch_name_optionals_label('interventionsurvey_surveyquestion');

//Readonly survey mode
$readOnlySurvey = true;


// Load object
if ($id > 0 || !empty($ref)) {
    $ret = $object->fetch($id, $ref);
    $object->fetch_thirdparty();
    $object->fetchSurvey();
    $object->fetch_thirdparty();
    if (!empty($object->errors)) {
        setEventMessages("", $object->errors, 'errors');
        $ret = -1;
    }
    if ($ret == 0) {
        print $langs->trans('NoRecordFound');
        exit();
    }
}

$readOnlySurvey = $object->is_survey_read_only();

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook) && !$readOnlySurvey && $user->rights->interventionsurvey->survey->write && $object->id > 0) {
    if ($action == 'confirm_save_question_bloc' ) {
        $survey_bloc_question = new InterventionSurveyBloc($db);
        if ($confirm == "yes" && $survey_bloc_question->fetch($survey_bloc_question_id) > 0) {
            $survey_bloc_question->complementary_question_bloc = GETPOST('interventionsurvey_question_bloc_description');
            $survey_bloc_question->fk_c_question_bloc_status = GETPOST('interventionsurvey_question_bloc_status', 'int');
            $survey_bloc_question->justification_text = GETPOST('interventionsurvey_question_bloc_justification_text');
            $survey_bloc_question->description = GETPOST('interventionsurvey_question_bloc_description');
            $survey_bloc_question->array_options = $extrafields_interventionsurvey_surveyblocquestion->getOptionalsFromPost($extralabels_question_bloc, '_intervention_survey_question_bloc');
            $survey_bloc_question->attached_files = GETPOST('interventionsurvey_question_bloc_attached_files', 'array');
            foreach ($survey_bloc_question->questions as $question) {
                $line->fk_c_answer = GETPOST('ei_q_' . $line->fk_c_question . '_answer', 'int');
                $line->text_answer = GETPOST('ei_q_' . $line->fk_c_question . '_justificatory');
                $line->array_options = $extrafields_question->getOptionalsFromPost($extralabels_question, '_ei_q_' . $line->fk_c_question);
            }
            $result = $survey_bloc_question->save();
            if ($result < 0) {
                setEventMessages("",$survey_bloc_question->errors, 'errors');
                $action = "save_question_bloc";
            }
        }
    }
}


/*
 * View
 */

$form = new Form($db);
$formextendedintervention = new FormInterventionSurvey($db);
$formproject=new FormProjets($db);

llxHeader('',$langs->trans("Intervention"));

// Mode vue et edition
if ($object->id > 0) {
    $head = fichinter_prepare_head($object);
    dol_fiche_head($head, 'survey', $langs->trans("InterventionCard"), -1, 'intervention');

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

    if ($object->statut == InterventionSurvey::STATUS_DRAFT) {
        print $langs->trans('InterventionSurveyMustBeValidated');
    }
    if ($readOnlySurvey) {
        print $langs->trans('InterventionSurveyReadOnlyMode');
    }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
        elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

	// Print form confirm
          print $formconfirm;
        //Prepare needed data for following form
          $object->fetch_attached_files();
        // Print left question bloc of the survey
        if (!empty($object->survey)) {

                print '<style>td.is_extrafield_question { padding-left: 15px !important; };</style>';
                print '<script type="text/javascript" language="javascript">' . "\n" . '$(document).ready(function () {';
                foreach ($extrafields_question->attribute_label as $key => $val) {
                    print '$.map($(".extendedintervention_eiquestionblocline_extras_' . $key . '"), function(item) { $(item).closest("tr").children("td").first().addClass("ei_ef_question"); });';
                }
                print '});' . "\n" . '</script>';

                foreach ($object->survey as $survey_part) {
                    print load_fiche_titre('<b>'. $survey_part->label .'</b>', '', '');
                    $idx = 1;
                    foreach ($survey_part->blocs as $bloc) {
                        if ($idx % 2 == 1) {
                            print '<div class="fichecenter">';
                        }
                        if ($user->rights->interventionsurvey->survey->write && $action == 'edit_question_bloc' && $bloc->id == $survey_bloc_question_id && !$readOnlySurvey) {
                            //@include dol_buildpath($reldir . '/ei_survey_edit.tpl.php');
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
}

llxFooter();
$db->close();
