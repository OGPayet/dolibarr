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

<!-- BEGIN PHP TEMPLATE intervention_survey_bloc_question_edit.tpl -->
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
    <form name="ei_survey" action="<?php print $_SERVER['PHP_SELF'].'?id='.$object->id.'#interventionsurvey_anchor_surveyblocquestion_'.$bloc->id ?>" method="POST">
      <input type="hidden" name="token" value="<?php print $_SESSION['newtoken'] ?>">
      <input type="hidden" name="question_bloc_id" value="<?php print $bloc->id ?>">
      <input type="hidden" name="action" value="save_question_bloc">
      <input type="hidden" name="backtopage" value="<?php print dol_string_nohtmltag($backtopage) ?>">

      <?php
      // Print question title and status
      print load_fiche_titre(
        $bloc->label,
        $bloc->getChosenStatus()->label . (!empty($bloc->justification_text)
            ?  ' ' . $form->textwithpicto('', '<b>' . $langs->trans('InterventionSurveyJustificationStatusText') . ' :</b><br>' . $bloc->justification_text, 1, 'object_tip.png@interventionsurvey', '', 0, 2) :
            ''),
        ''
    );
    ?>
      <table class="border" width="100%">

      <tr>
          <td><?php print $langs->trans('InterventionSurveyDescriptionBloc') ?></td>
          <td>
          <?php
          $doleditor = new DolEditor('ei_qb_complementary', isset($_POST['ei_qb_complementary']) ? GETPOST('ei_qb_complementary') : $question_bloc->complementary_question_bloc,
              '', 150, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '100%');
          print $doleditor->Create(1);
          ?>
          </td>
        </tr>
        <tr>
          <td><?php print $langs->trans('Documents') ?></td>
          <td><?php print $formextendedintervention->multiselect_attached_files($object->ref, 'ei_qb_attached_files',
                  isset($_POST['ei_qb_attached_files']) ? GETPOST('ei_qb_attached_files') : $question_bloc->attached_files) ?></td>
        </tr>

      <?php
    // Print question
      foreach ($bloc->questions as $question) {
            ?>
          <tr>
            <td><?php print $question->label; ?></td>
            <td>
                <?php
                $answers = array();
                foreach ($question->answers as $answer) {
                    $answers[$answer->id] = $answer->label;
                }
                $already_chosen_answer = GETPOST('interventionsurvey_chosen_answer_for_' . $question->id, "int") ?? $question->fk_chosen_answer;
                print $form->selectarray('interventionsurvey_chosen_answer_for_' . $question->id, $answers, $already_chosen_answer, 1, 0, 0, '', 0, 0, 0, '', 'centpercent interventionsurvey_answer');
                ?>
            </td>
          </tr>
          <tr>
            <td class="interventionsurvey_question_justification_title">
                <?php print $langs->trans('InterventionSurveyAnswerJustificationTitle') ?>
            </td>
            <td>
              <table class="nobordernopadding" width="100%">
                <tr>
                  <td>
                      <?php
                      $already_used_predefined_text = GETPOST('interventionsurvey_used_predefined_text_for_' . $question->id, "array") ?? $question->fk_chosen_answer;
                      print $form->selectarray('interventionsurvey_used_predefined_text_for_' . $question->id, array(), $already_used_predefined_text, 1, 0, 0, '', 0, 0, 0, '', 'centpercent') ?>
                  </td>
                  <td>
                    <input type="button" class="button ei_predefined_texts"
                           question_id="<?php print $question->id ?>"
                           value="<?php print $langs->trans("InterventionSurveyUseButton") ?>">
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                      <?php
                    $already_written_justification_text = GETPOST('interventionsurvey_justification_text_for_' . $question->id, "text") ?? $question->justification_text;

                      $doleditor = new DolEditor('interventionsurvey_justification_text_for_' . $question->id, $already_written_justification_text,
                          '', 150, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '90%');
                      print $doleditor->Create(1);
                      ?>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
            <?php
            // Other attributes of the question
            $parameters = array();
            $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $question, $action);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook) && !empty($extrafields_interventionsurvey_surveyquestion->attribute_label)) {
                $question->array_options = $extrafields_interventionsurvey_surveyquestion->getOptionalsFromPost($extralabels_question, '_intervention_survey_question' . $line->fk_c_question);
                print $question->showOptionals($extrafields_interventionsurvey_surveyquestion, 'edit', array(), '_intervention_survey_question' . $question->id);
        }
}
    // Other attributes of the question bloc
    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $bloc, $action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook) && ! empty($extrafields_interventionsurvey_surveyblocquestion->attribute_label)) {
        $bloc->array_options = $extrafields_interventionsurvey_surveyblocquestion->getOptionalsFromPost($extralabels_interventionsurvey_surveyblocquestion, '_intervention_survey_question_bloc');
        print $bloc->showOptionals($extrafields_interventionsurvey_surveyblocquestion, 'edit',  array(), '_intervention_survey_question_bloc');
    }
        ?>

      </table>

      <br>
      <div class="right">
        <input type="submit" class="button" value="<?php print $langs->trans("Save") ?>">&nbsp;&nbsp;&nbsp;<input type="button" class="button" value="<?php print $langs->trans("Cancel") ?>">
      </div>

    </form>
  </div>
  <br>
  <script type="text/javascript" language="javascript">
    $(document).ready(function () {
      var predefined_texts = <?php print json_encode($predefined_texts) ?>;

      function ei_update_predefined_texts(select_answer) {
        var id = select_answer.attr('id');
        var question_id = id.substr(5, id.indexOf('_answer') - 5);
        var select_predefined_texts = $('#ei_q_' + question_id + '_predefined_texts');
        var answer_id = select_answer.val();

        select_predefined_texts.empty();
        if (question_id in predefined_texts && answer_id in predefined_texts[question_id]) {
          $.map(predefined_texts[question_id][answer_id].predefined_texts, function (val, i) {
            select_predefined_texts.append($('<option>', {value: i, text: val.option}));
          });
        }
      }

      function ei_update_mandatory(select_answer) {
        var id = select_answer.attr('id');
        var question_id = id.substr(5, id.indexOf('_answer') - 5);
        var answer_id = select_answer.val();

        if (question_id in predefined_texts && answer_id in predefined_texts[question_id] && predefined_texts[question_id][answer_id].mandatory) {
          $('#ei_q_'+question_id+'_justificatory_label').addClass('fieldrequired');
        } else {
          $('#ei_q_'+question_id+'_justificatory_label').removeClass('fieldrequired');
        }
      }

      $('select.ei_q_answer').on('change', function(e) {
        ei_update_mandatory($(this));
        ei_update_predefined_texts($(this));
      });

      $('select.ei_q_answer').map(function(e) {
        ei_update_mandatory($(this));
        ei_update_predefined_texts($(this));
      });

      $('input.button.ei_predefined_texts').on('click', function (e) {
        var _this = $(this);
        var question_id = _this.attr('question_id');
        var answer_id = $('#ei_q_'+question_id+'_answer').val();
        var predefined_text_id = $('#ei_q_'+question_id+'_predefined_texts').val();

        if (question_id in predefined_texts && answer_id in predefined_texts[question_id] && predefined_text_id in predefined_texts[question_id][answer_id].predefined_texts) {
          var justificatory_id = 'ei_q_' + question_id + '_justificatory';

          if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && justificatory_id in CKEDITOR.instances) {
            var justificatory_textarea = CKEDITOR.instances[justificatory_id];
            var last_text = justificatory_textarea.getData();

            justificatory_textarea.setData((last_text.length > 0 ? last_text + '<br>' : '') + predefined_texts[question_id][answer_id].predefined_texts[predefined_text_id].text);
          } else {
            var justificatory_textarea = $('#'+justificatory_id);
            var last_text = justificatory_textarea.val();

            justificatory_textarea.val((last_text.length > 0 ? last_text + "\n" : '') + predefined_texts[question_id][answer_id].predefined_texts[predefined_text_id].text);
          }
        }

        e.stopPropagation();
        e.preventDefault();
        return false;
      });
    });
  </script>
<?php
   if ($idx % 2 == 0) {
       print '</div></div>';
   } else {
       print '</div>';
   }
?>
<!-- END PHP TEMPLATE ei_survey_edit.tpl.php -->
