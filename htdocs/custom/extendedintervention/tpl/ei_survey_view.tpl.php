<?php
/* Copyright (C) 2018   Open-DSI            <support@open-dsi.fr>
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
// $survey_bloc
// $question_bloc
// $extrafields_question_bloc
// $extrafields_question

// Protection to avoid direct call of template
if (empty($question_bloc) || !is_object($question_bloc)) {
    print "Error, template page can't be called as URL";
    dol_syslog("Error, template page can't be called as URL : " . $_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"], LOG_ERR);
    exit;
}
?>

<!-- BEGIN PHP TEMPLATE ei_survey_view.tpl.php -->
<div id="ei_anchor_sb_<?php print $survey_bloc->fk_equipment ?>_qb_<?php print $question_bloc->fk_c_question_bloc ?>"></div>
<?php
   if ($idx % 2 == 0) {
       print '<div class="fichehalfright"><div class="ficheaddleft">';
   } else {
       print '<div class="fichehalfleft">';
   }
?>
  <div class="underbanner clearboth"></div>
  <div id="ei_question_bloc_<?php print $question_bloc->fk_c_question_bloc ?>">
    <?php
    // Print question title and status
    print load_fiche_titre($question_bloc->label_question_bloc, $question_bloc->label_status . (!empty($question_bloc->justificatory_status) ? ' ' . $form->textwithpicto('', '<b>' . $langs->trans('ExtendedInterventionJustificatory') . ' :</b><br>' . $question_bloc->justificatory_status, 1, 'object_tip.png@extendedintervention', '', 0, 2) : ''), '');
    ?>
    <table class="border" width="100%">
      <?php
      // Print question
      if (is_array($question_bloc->lines) && count($question_bloc->lines)) {
        foreach ($question_bloc->lines as $line_id => $line) {
      ?>
      <tr>
        <td><?php print $line->label_question ?></td>
        <td width="50%"><?php print $line->label_answer . (!empty($line->text_answer) ? ' ' . $form->textwithpicto('', '<b>' . $langs->trans('ExtendedInterventionJustificatory') . ' :</b><br>' . $line->text_answer, 1, 'object_tip.png@extendedintervention', '', 0, 2) : '') ?></td>
      </tr>
      <?php
          // Other attributes of the question
          $parameters = array();
          $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $line, $action);    // Note that $action and $object may have been modified by hook
          print $hookmanager->resPrint;
          if (empty($reshook) && ! empty($extrafields_question->attribute_label)) {
              print $line->showOptionals($extrafields_question, 'view', array());
          }
        }
      }
      // Other attributes of the question bloc
      $parameters = array();
      $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $question_bloc, $action);    // Note that $action and $object may have been modified by hook
      print $hookmanager->resPrint;
      if (empty($reshook) && ! empty($extrafields_question_bloc->attribute_label)) {
          print $question_bloc->showOptionals($extrafields_question_bloc, 'view', array());
      }
      // Complementary text of the question bloc
      if (!empty($question_bloc->complementary_question_bloc)) {
      ?>
      <tr>
        <td><?php print $langs->trans('ExtendedInterventionSurveyComplementaryText') ?></td>
        <td><?php print $question_bloc->complementary_question_bloc ?></td>
      </tr>
      <?php
      }
      ?>
    </table>

    <?php
    if ($object->statut < ExtendedIntervention::STATUS_DONE && !$survey_bloc->read_only && !$question_bloc->read_only) {
    ?>
    <br>
    <div class="right">
      <?php
      if ($user->rights->ficheinter->creer) {
          //if ($question_bloc->fk_c_question_bloc_status > 0) {
      ?>
      <div class="inline-block divButAction"><a class="butAction" href="<?php print $_SERVER["PHP_SELF"].'?id='.$object->id.'&equipment_id='.$survey_bloc->fk_equipment.'&question_bloc_id='.$question_bloc->fk_c_question_bloc.'&action=edit_question_bloc#ei_anchor_sb_'.$survey_bloc->fk_equipment.'_qb_'.$question_bloc->fk_c_question_bloc ?>"><?php print $langs->trans("Modify") ?></a></div>
      <?php
          /*} else {
      ?>
      <div class="inline-block divButAction"><a class="butActionRefused" href="#" title="<?php print $langs->trans("ExtendedInterventionSurveyInterventionMustBeValidated") ?>"><?php print $langs->trans("Modify") ?></a></div>
      <?php
          }*/
      } else {
      ?>
      <div class="inline-block divButAction"><a class="butActionRefused" href="#" title="<?php print $langs->trans("NotAllowed") ?>"><?php print $langs->trans("Modify") ?></a></div>
      <?php
      }
      ?>
    </div>
    <?php
    }
    ?>
  </div>
  <br>
<?php
   if ($idx % 2 == 0) {
       print '</div></div>';
   } else {
       print '</div>';
   }
?>
<!-- END PHP TEMPLATE ei_survey_view.tpl.php -->
