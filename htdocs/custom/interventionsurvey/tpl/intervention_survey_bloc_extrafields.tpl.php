<?php
/* Copyright (C) 2018   Open-DSI            <support@open-dsi.fr>
/* Copyright (C) 2020   Alexis LAURIER      <contact@alexislaurier.fr>
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

// Need to have following variables defined:
// $user
// $langs
// $hookmanager
// $action
// $form
// $bloc
// $object - must be an interventionsurvey object, which extends fichinter
// $extrafields_interventionsurvey_surveyblocquestion
// $extrafields_interventionsurvey_surveyquestion

// Protection to avoid direct call of template
if (empty($bloc)) {
    print "Error, template page can't be called as URL";
    dol_syslog("Error, template page can't be called as URL : " . $_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"], LOG_ERR);
    exit;
}
?>

<?php
if($readonly){
    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $bloc, $action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook) && !empty($extrafields_interventionsurvey_surveyblocquestion->attribute_label)) {
        print $bloc->showOptionals($extrafields_interventionsurvey_surveyblocquestion, 'view', array());
    }
}
else
{
    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $bloc, $action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook) && ! empty($extrafields_interventionsurvey_surveyblocquestion->attribute_label)) {
        $bloc->array_options = $extrafields_interventionsurvey_surveyblocquestion->getOptionalsFromPost($extralabels_interventionsurvey_surveyblocquestion, '_intervention_survey_question_bloc_' . $bloc->id);
        print $bloc->showOptionals($extrafields_interventionsurvey_surveyblocquestion, 'edit',  array(), '_intervention_survey_question_bloc_' . $bloc->id);
    }
}

?>
