<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
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
$langs->load("inteventionsurvey@interventionsurvey");

$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');
$question_bloc_id = GETPOST('question_bloc_id','int');
$backtopage = GETPOST('backtopage','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

if(empty($user->rights->interventionsurvey->survey->read)) accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('interventionsurvey'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


/*
 * View
 */

$form = new Form($db);
dol_include_once('/advancedictionaries/class/dictionary.class.php');

$formextendedintervention = new FormInterventionSurvey($db);
$formproject=new FormProjets($db);

llxHeader('',$langs->trans("Intervention"));


dol_include_once('/interventionsurvey/class/interventionsurvey.class.php');
$interventionsurvey = new InterventionSurvey($db);
$interventionsurvey->fetch($id);
$interventionsurvey->fillCaches();

$cache_survey_bloc_question_dictionary = $interventionsurvey->cache_survey_bloc_question_dictionary;
$cache_survey_bloc_status_dictionary = $interventionsurvey->cache_survey_bloc_status_dictionary;
$cache_survey_bloc_status_predefined_text_dictionary = $interventionsurvey->cache_survey_bloc_status_predefined_text_dictionary;
$cache_survey_question_dictionary = $interventionsurvey->cache_survey_question_dictionary;
$cache_survey_answer_dictionary = $interventionsurvey->cache_survey_answer_dictionary;
$cache_survey_answer_predefined_text = $interventionsurvey->cache_survey_answer_predefined_text;

echo '<h3>cache_survey_bloc_question_dictionary</h3>';
echo json_encode($cache_survey_bloc_question_dictionary);
echo '<br>';

echo '<h3>cache_survey_bloc_status_dictionary</h3>';
echo json_encode($cache_survey_bloc_status_dictionary);
echo '<br>';

echo '<h3>cache_survey_bloc_status_predefined_text_dictionary</h3>';
echo json_encode($cache_survey_bloc_status_predefined_text_dictionary);
echo '<br>';

echo '<h3>cache_survey_question_dictionary</h3>';
echo json_encode($cache_survey_question_dictionary);
echo '<br>';

echo '<h3>cache_survey_answer_dictionary</h3>';
echo json_encode($cache_survey_answer_dictionary);
echo '<br>';

echo '<h3>cache_survey_answer_predefined_text</h3>';
echo json_encode($cache_survey_answer_predefined_text);
echo '<br>';

echo '<h3>crude generated</h3>';
echo "<pre>";
$interventionsurvey->generateSurveyFromDictionary();
echo json_encode($interventionsurvey->survey_taken_from_dictionary, JSON_PRETTY_PRINT);
echo "</pre>";
echo '<br>';

echo '<h3>generated</h3>';
echo "<pre>";
$interventionsurvey->setSurveyFromFetchObj($interventionsurvey->survey_taken_from_dictionary);
echo json_encode($interventionsurvey->survey, JSON_PRETTY_PRINT);
echo "</pre>";
echo '<br>';

echo '<h3>survey</h3>';
echo "<pre>";
$interventionsurvey->fetchSurvey();
echo json_encode($interventionsurvey->survey, JSON_PRETTY_PRINT);
echo "</pre>";
echo '<br>';

dol_fiche_end();

llxFooter();
$db->close();
