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
// $blocPrefix
// $questionPrefix

// Protection to avoid direct call of template
if (empty($bloc)) {
    print "Error, template page can't be called as URL";
    dol_syslog("Error, template page can't be called as URL : " . $_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"], LOG_ERR);
    exit;
}
?>

<!-- BEGIN PHP TEMPLATE intervention_survey_bloc_question_edit.tpl -->
<div id="<?php print $blocPrefix . $bloc->id ?>_anchor"></div>
<?php
if ($idx % 2 == 0) {
    print '<div class="fichehalfright"><div class="ficheaddleft">';
} else {
    print '<div class="fichehalfleft">';
}
?>
<div class="underbanner clearboth"></div>
<div id="<?php print $blocPrefix . $bloc->id ?>">
    <form name="<?php print $blocPrefix . $bloc->id ?>" action="<?php print $_SERVER['PHP_SELF'].'?id='.$object->id. '#' . $blocPrefix . $bloc->id . '_anchor' ?>" method="POST">
      <input type="hidden" name="token" value="<?php print $_SESSION['newtoken'] ?>">
      <input type="hidden" name="survey_bloc_question_id" value="<?php print $bloc->id ?>">
      <input type="hidden" name="action" value="save_question_bloc">
      <input type="hidden" name="backtopage" value="<?php print dol_string_nohtmltag($backtopage) ?>">

      <?php
      // Print question title and status
      print load_fiche_titre(
        $bloc->label,'',''
    );
    $status_predefined_text = array();
    $status_predefined_text[$status->id]=array();
        $statusList = array();
        foreach ($bloc->status as $status) {
            $statusList[$status->id] = $status->label;
            $status_predefined_text[$status->id]["label"] = $status->label;
            $status_predefined_text[$status->id]["mandatory_justification"] = $status->mandatory_justification;
            $predefined_texts = array();
                    foreach($status->predefined_texts as $predefined_text){
                        $predefined_texts[$predefined_text->id]=$predefined_text->label;
                    }
            $status_predefined_text[$status->id]["predefined_texts"]=$predefined_texts;
        }

    ?>
      <table class="border" width="100%">

      <tr>
    <td class="<?php if($bloc->mandatory_status) {print "fieldrequired";} ?>">
        <?php print $langs->trans('InterventionSurveyBlocStatusLabel'); ?>
    </td>
    <td>
        <?php print $form->selectarray($blocPrefix . $bloc->id . "_fk_chosen_status", $statusList, $bloc->fk_chosen_status, 1, 0, 0, '', 0, 0, 0, '', 'centpercent intervention_survey_status_select');?>
    </td>
</tr>
<tr>
<td id="<?php print $blocPrefix . $bloc->id . "_justification_text_title" ?>" class="interventionsurvey_bloc_justification_title">
                <?php print $langs->trans('InterventionSurveyStatusJustificationTitle') ?>
            </td>
            <td>
              <table class="nobordernopadding" width="100%">
                <tr>
                  <td>
                      <?php
                      print $form->selectarray($blocPrefix . $bloc->id . "_fk_chosen_status_predefined_text", array(), $bloc->fk_chosen_status_predefined_text, 1, 0, 0, '', 0, 0, 0, '', 'centpercent') ?>
                  </td>
                  <td>
                    <input type="button" class="button intervention_survey_status_predefined_texts"
                    element_prefix="<?php print $blocPrefix . $bloc->id ?>"
                           value="<?php print $langs->trans("InterventionSurveyUseButton") ?>">
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                      <?php
                      $doleditor = new DolEditor($blocPrefix . $bloc->id . "_justification_text", $bloc->justification_text,
                          '', 150, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '90%');
                      $doleditor->Create();
                      ?>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
      <tr>
          <td><?php print $langs->trans('InterventionSurveyDescriptionBloc') ?></td>
          <td>
          <?php
          if($bloc->description_editable){
            $doleditor = new DolEditor($blocPrefix . $bloc->id .'_description', $bloc->description, '', 150, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '90%');
            $doleditor->Create();
          }
          else {
              print $bloc->description;
          }

          ?>
          </td>
        </tr>

      <?php
    // Print question and prepare data for jquery
    $answers_predefined_text = array();
      foreach ($bloc->questions as $question) {
            ?>
          <tr>
            <td class="<?php if($question->mandatory_answer) {print "fieldrequired";} ?>"><?php print $question->label; ?></td>
            <td>
                <?php
                $answers = array();
                foreach ($question->answers as $answer) {
                    $answers[$answer->id] = $answer->label;
                    $answers_predefined_text[$answer->id] = array();
                    $predefined_texts = array();
                    foreach($answer->predefined_texts as $predefined_text){
                        $predefined_texts[$predefined_text->id]=$predefined_text->label;
                    }
                    $answers_predefined_text[$answer->id]["label"] = $answer->label;
                    $answers_predefined_text[$answer->id]["mandatory_justification"] = $answer->mandatory_justification;
                    $answers_predefined_text[$answer->id]["predefined_texts"]=$predefined_texts;
                }
                print $form->selectarray($questionPrefix . $question->id . "_fk_chosen_answer", $answers, $question->fk_chosen_answer, 1, 0, 0, '', 0, 0, 0, '', 'centpercent intervention_survey_answer_select');
                ?>
            </td>
          </tr>
          <tr>
            <td id="<?php print $questionPrefix . $question->id . "_justification_text_title" ?>" class="interventionsurvey_question_justification_title">
                <?php print $langs->trans('InterventionSurveyAnswerJustificationTitle') ?>
            </td>
            <td>
              <table class="nobordernopadding" width="100%">
                <tr>
                  <td>
                      <?php
                      print $form->selectarray($questionPrefix . $question->id . "_fk_chosen_answer_predefined_text", array(), $question->fk_chosen_answer_predefined_text, 1, 0, 0, '', 0, 0, 0, '', 'centpercent') ?>
                  </td>
                  <td>
                    <input type="button" class="button intervention_survey_answer_predefined_texts"
                           element_prefix="<?php print $questionPrefix . $question->id ?>"
                           value="<?php print $langs->trans("InterventionSurveyUseButton") ?>">
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                      <?php
                      $doleditor = new DolEditor($questionPrefix . $question->id . "_justification_text", $question->justification_text,
                          '', 150, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '90%');
                      $doleditor->Create();
                      ?>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
            <?php
            // Other attributes of the question
            $reshook = $hookmanager->executeHooks('formObjectOptions', array(), $question, $action);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook) && !empty($question::$extrafields_cache->attribute_label)) {
                print $question->showOptionals($question::$extrafields_cache, 'edit', array(), '_intervention_survey_question_' . $question->id . '_');
        }
}
    // Other attributes of the question bloc
    $reshook = $hookmanager->executeHooks('formObjectOptions', array(), $bloc, $action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook) && ! empty($bloc::$extrafields_cache->attribute_label)) {
        print $bloc->showOptionals($bloc::$extrafields_cache, 'edit',  array(), '_intervention_survey_question_bloc_' . $bloc->id . '_');
    }
        ?>
       <tr>
          <td><?php print $langs->trans('Documents') ?></td>
          <td><?php print $formextendedintervention->multiselect_attached_files($object->ref, $blocPrefix . $bloc->id .'_attached_files',
                  $bloc->attached_files) ?></td>
        </tr>
      </table>

      <br>
      <div class="right">
        <input type="submit" class="button" value="<?php print $langs->trans("Save") ?>">&nbsp;&nbsp;&nbsp;<input type="button" class="button" value="<?php print $langs->trans("Cancel") ?>">
      </div>

    </form>
  </div>
  <br>
  <script type="text/javascript" language="javascript">
     $(document).ready(function() {
        const answer_predefined_texts = <?php print json_encode($answers_predefined_text) ?> ;
        const status_predefined_texts = <?php print json_encode($status_predefined_text) ?>;

        function intervention_survey_update_predefined_texts(select_answer, listOfPredefinedText, chosenAnswerSuffix, predefinedTextSuffix) {
            const temp = select_answer.attr('id').substring(0, select_answer.attr('id').lastIndexOf(chosenAnswerSuffix));
            const select_predefined_texts = $('#' + temp + predefinedTextSuffix);
            const answer_id = select_answer.val();
            select_predefined_texts.empty();
            if (answer_id in listOfPredefinedText) {
                for (let id in listOfPredefinedText[answer_id]['predefined_texts']) {
                    select_predefined_texts.append($('<option>', {
                        value: id,
                        text: listOfPredefinedText[answer_id]['predefined_texts'][id]
                    }));
                }
            }
        }

        function intervention_survey_update_mandatory_justification(select_answer,listOfPredefinedText, chosenAnswerSuffix) {
            const temp = select_answer.attr('id').substring(0, select_answer.attr('id').lastIndexOf(chosenAnswerSuffix));
            const answer_id = select_answer.val();

            if (answer_id && listOfPredefinedText[answer_id] && listOfPredefinedText[answer_id]['mandatory_justification']) {
                $('#' + temp + '_justification_text_title').addClass('fieldrequired');
            } else {
                $('#' + temp + '_justification_text_title').removeClass('fieldrequired');
            }
        }

        function append_predefined_text_to_justification_area(add_to_justification_button,listOfPredefinedText, answerSuffix, predefinedTextSuffix){
            const temp = add_to_justification_button.attr('element_prefix');
            var answer_id = $('#' + temp + answerSuffix).val();
            var predefined_text_id = $('#' + temp + predefinedTextSuffix).val();
            var justificatory_text_area_id = temp + '_justification_text';

            if (!answer_id || !predefined_text_id) {
                return;
            }
            if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && justificatory_text_area_id in CKEDITOR.instances) {
                var justificatory_textarea = CKEDITOR.instances[justificatory_text_area_id];
                var last_text = justificatory_textarea.getData();

                justificatory_textarea.setData((last_text.length > 0 ? last_text + '<br>' : '') + listOfPredefinedText[answer_id]["predefined_texts"][predefined_text_id]);
            } else {
                var justificatory_textarea = $('#' + justificatory_text_area_id);
                var last_text = justificatory_textarea.val();

                justificatory_textarea.val((last_text.length > 0 ? last_text + "\n" : '') + listOfPredefinedText[answer_id]["predefined_texts"][predefined_text_id]);
            }
        }

        $('select.intervention_survey_answer_select').on('change', function(e) {
            intervention_survey_update_mandatory_justification($(this),answer_predefined_texts, "_fk_chosen_answer");
            intervention_survey_update_predefined_texts($(this),answer_predefined_texts, "_fk_chosen_answer", "_fk_chosen_answer_predefined_text");
        });

        $('select.intervention_survey_answer_select').map(function(e) {
            intervention_survey_update_mandatory_justification($(this),answer_predefined_texts, "_fk_chosen_answer");
            intervention_survey_update_predefined_texts($(this),answer_predefined_texts, "_fk_chosen_answer", "_fk_chosen_answer_predefined_text");
        });

        $('input.button.intervention_survey_answer_predefined_texts').on('click', function(e) {
            append_predefined_text_to_justification_area($(this),answer_predefined_texts,"_fk_chosen_answer", "_fk_chosen_answer_predefined_text");
            e.stopPropagation();
            e.preventDefault();
            return false;
        });

        $('select.intervention_survey_status_select').on('change', function(e) {
            intervention_survey_update_mandatory_justification($(this),status_predefined_texts, "_fk_chosen_status");
            intervention_survey_update_predefined_texts($(this),status_predefined_texts, "_fk_chosen_status", "_fk_chosen_status_predefined_text");
        });

        $('select.intervention_survey_status_select').map(function(e) {
            intervention_survey_update_mandatory_justification($(this),status_predefined_texts, "_fk_chosen_status");
            intervention_survey_update_predefined_texts($(this),status_predefined_texts, "_fk_chosen_status", "_fk_chosen_status_predefined_text");
        });

        $('input.button.intervention_survey_status_predefined_texts').on('click', function(e) {
            append_predefined_text_to_justification_area($(this),status_predefined_texts,"_fk_chosen_status", "_fk_chosen_status_predefined_text");
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
