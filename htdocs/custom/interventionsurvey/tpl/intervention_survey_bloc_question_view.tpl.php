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

<!-- BEGIN PHP TEMPLATE intervention_survey_bloc_question_view.tpl -->
<div id="interventionsurvey_anchor_surveyblocquestion_<?php print $bloc->id ?>"></div>
<?php
if ($idx % 2 == 0) {
    print '<div class="fichehalfright"><div class="ficheaddleft">';
} else {
    print '<div class="fichehalfleft">';
}
?>
<div class="underbanner clearboth"></div>
<div id="interventionsurvey_surveyblocquestion_<?php print $bloc->id ?>">
    <?php
    // Print question title and status
    print load_fiche_titre(
        $bloc->label,
        $bloc->getChosenStatus()->label . (!empty($bloc->justification_text)
            ?  ' ' . $form->textwithpicto('', '<b>' . $langs->trans('InterventionSurveyJustificationStatusText') . ' :</b><br>' . $bloc->justification_text, 1, 'object_tip.png@interventionsurvey', '', 0, 2) :
            ''),
        ''
    );
    if (!empty($bloc->description)) {
    ?>
        <tr>
            <td><?php print $langs->trans('InterventionSurveyBlocQuestionDescriptionTitle') ?></td>
            <td><?php print $bloc->description ?></td>
        </tr>
    <?php
    }
    ?>
    <table class="border" width="100%">
        <?php
        // Print question
        foreach ($bloc->questions as $question) {
        ?>
            <tr>
                <td><?php print $question->label ?></td>
                <td width="50%"><?php print $question->getChosenAnswer()->label . (!empty($question->justification_text) ? ' ' . $form->textwithpicto('', '<b>' . $langs->trans('InterventionSurveyAnswerJustificationText') . ' :</b><br>' . $question->justification_text, 1, 'object_tip.png@extendedintervention', '', 0, 2) : '') ?></td>
            </tr>
            <?php
            // Other attributes of the question
            $parameters = array();
            $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $line, $action);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook) && !empty($question::$extrafields_cache->attribute_label)) {
                print $question->showOptionals($question::$extrafields_cache, 'view', array());
            }
        }
        // Other attributes of the question bloc
        $parameters = array();
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $bloc, $action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook) && !empty($bloc::$extrafields_cache->attribute_label)) {
            print $bloc->showOptionals($bloc::$extrafields_cache, 'view', array());
        }

        // Attached files of the question bloc
            $attached_files = array();
            foreach ($bloc->attached_files as $filename) {
                $attached_files[] = isset($object->attached_files[$filename]) ? $object->attached_files[$filename] : $langs->trans('InterventionSurveyErrorFileNotFound', $filename);
            }
            ?>
            <tr>
                <td><?php print $langs->trans('Documents') ?></td>
                <td><?php print implode(' , ', $attached_files) ?></td>
            </tr>
    </table>
    <br>
    <div class="right">
        <?php
        if ($user->rights->interventionsurvey->survey->write && !$readOnlySurvey) {
        ?>
            <div class="inline-block divButAction">
                <a class="butAction" href="<?php print $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&survey_bloc_question_id=' . $bloc->id . '&action=edit_question_bloc#interventionsurvey_anchor_surveyblocquestion_' . $bloc->id ?>"> <?php print $langs->trans("Modify") ?>
                </a>
            </div>
        <?php
        } else {
        ?>
            <div class="inline-block divButAction"><a class="butActionRefused" href="#" title="<?php print $langs->trans("NotAllowed") ?>"><?php print $langs->trans("Modify") ?></a></div>
        <?php
        }
        ?>
    </div>
</div>
<br>
<?php
if ($idx % 2 == 0) {
    print '</div></div>';
} else {
    print '</div>';
}
?>
<!-- END PHP TEMPLATE intervention_survey_bloc_question_view.tpl.php -->
