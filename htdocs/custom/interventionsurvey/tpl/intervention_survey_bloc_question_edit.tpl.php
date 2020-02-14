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
// $conf
// $langs
// $hookmanager
// $action
// $backtopage
// $form
// $object
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

<!-- BEGIN PHP TEMPLATE ei_survey_edit.tpl.php -->
<div id="ei_anchor_sb_<?php print $survey_bloc->fk_equipment ?>_qb_<?php print $question_bloc->fk_c_question_bloc ?>"></div>
<?php
  if ($idx % 2 == 0) {
    print '<div class="fichehalfright"><div class="ficheaddleft">';
  } else {
    print '<div class="fichehalfleft">';
  }
  $predefined_texts = array();
?>
  <div class="underbanner clearboth"></div>
  <div id="ei_question_bloc_<?php print $question_bloc->fk_c_question_bloc ?>">
    <form name="ei_survey" action="<?php print $_SERVER['PHP_SELF'].'?id='.$object->id.'#ei_anchor_sb_'.$survey_bloc->fk_equipment.'_qb_'.$question_bloc->fk_c_question_bloc ?>" method="POST">
      <input type="hidden" name="token" value="<?php print $_SESSION['newtoken'] ?>">
      <input type="hidden" name="equipment_id" value="<?php print $survey_bloc->fk_equipment ?>">
      <input type="hidden" name="question_bloc_id" value="<?php print $question_bloc->fk_c_question_bloc ?>">
      <input type="hidden" name="action" value="save_question_bloc">
      <input type="hidden" name="ei_qb_status" value="<?php print isset($_POST['ei_qb_status']) ? GETPOST('ei_qb_status', "int") : $question_bloc->fk_c_question_bloc_status ?>">
      <input type="hidden" name="ei_qb_justificatory_status" value="<?php print isset($_POST['ei_qb_justificatory_status']) ? GETPOST('ei_qb_justificatory_status', "alpha") : $question_bloc->justificatory_status ?>">
      <input type="hidden" name="backtopage" value="<?php print dol_string_nohtmltag($backtopage) ?>">

      <?php
      $warning = '';
      if ($question_bloc->warning_code_question_bloc) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionBlocCode');
      if ($question_bloc->warning_label_question_bloc) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionBlocTitle');
      if ($question_bloc->warning_extrafields_question_bloc) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionBlocExtraFields');
      if (!empty($warning)) $warning = ' ' . $form->textwithpicto('', '<b>' . $langs->trans('ExtendedInterventionSurveyConfigurationChanged') . ' :</b>' . $warning, 1, 'warning', '', 0, 2);
      // Print question title and status
      print load_fiche_titre($question_bloc->label_question_bloc . $warning, $question_bloc->label_status . (!empty($question_bloc->justificatory_status) ? ' ' . $form->textwithpicto('', '<b>' . $langs->trans('ExtendedInterventionJustificatory') . ' :</b><br>' . $question_bloc->justificatory_status, 1, 'object_tip.png@extendedintervention', '', 0, 2) : ''),'');
      ?>
      <table class="border" width="100%">
      <?php
    // Print question
    if (is_array($question_bloc->lines) && count($question_bloc->lines)) {
      foreach ($question_bloc->lines as $line_id => $line) {
        if (!$line->read_only) {
            foreach ($line->answer_list as $answer_id => $answer) {
                $predefined_texts[$line->fk_c_question][$answer_id] = array(
                    'predefined_texts' => array(),
                    'mandatory' => !empty($answer['mandatory'])
                );
                foreach ($answer['predefined_texts'] as $predefined_text_id => $predefined_text) {
                    $predefined_texts[$line->fk_c_question][$answer_id]['predefined_texts'][$predefined_text['position'] . '_' . $predefined_text_id] = [
                        'option' => dol_string_nohtmltag($predefined_text['predefined_text']),
                        'text' => $predefined_text['predefined_text']
                    ];
                }
            }
            ?>
          <tr>
            <td><?php
                print $line->label_question;
                $warning = '';
                if ($line->warning_code_question) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionCode');
                if ($line->warning_label_question) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionLabel');
                if ($line->warning_extrafields_question) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionExtraFields');
                if ($line->warning_code_answer) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionAnswerCode');
                if ($line->warning_label_answer) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionAnswerLabel');
                if ($line->warning_mandatory_answer) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionAnswerJustificatoryMandatory');
                if (!empty($warning)) print ' ' . $form->textwithpicto('', '<b>' . $langs->trans('ExtendedInterventionSurveyConfigurationChanged') . ' :</b>' . $warning, 1, 'warning', '', 0, 2);
            ?></td>
            <td>
                <?php
                $answers = array();
                foreach ($line->answer_list as $answer_id => $answer) {
                    $answers[$answer_id] = $answer['label'];
                }
                print $form->selectarray('ei_q_' . $line->fk_c_question . '_answer', $answers, isset($_POST['ei_q_' . $line->fk_c_question . '_answer']) ? GETPOST('ei_q_' . $line->fk_c_question . '_answer', "alpha") : $line->fk_c_answer, 1, 0, 0, '', 0, 0, 0, '', 'centpercent ei_q_answer');
                ?>
            </td>
          </tr>
          <tr>
            <td class="ei_ef_question"
                id="ei_q_<?php print $line->fk_c_question ?>_justificatory_label"><?php print $langs->trans('ExtendedInterventionJustificatory') ?></td>
            <td>
              <table class="nobordernopadding" width="100%">
                <tr>
                  <td>
                      <?php print $form->selectarray('ei_q_' . $line->fk_c_question . '_predefined_texts', array(), isset($_POST['ei_q_' . $line->fk_c_question . '_predefined_texts']) ? GETPOST('ei_q_' . $line->fk_c_question . '_predefined_texts', "alpha") : '', 1, 0, 0, '', 0, 0, 0, '', 'centpercent') ?>
                  </td>
                  <td>
                    <input type="button" class="button ei_predefined_texts"
                           question_id="<?php print $line->fk_c_question ?>"
                           value="<?php print $langs->trans("ExtendedInterventionUse") ?>">
                  </td>
                </tr>
                <tr>
                  <td colspan="2">
                      <?php
                      $doleditor = new DolEditor('ei_q_' . $line->fk_c_question . '_justificatory', isset($_POST['ei_q_' . $line->fk_c_question . '_justificatory']) ? GETPOST('ei_q_' . $line->fk_c_question . '_justificatory') : $line->text_answer,
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
            $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $line, $action);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook) && !empty($extrafields_question->attribute_label)) {
                if (isset($_POST['ei_qb_complementary'])) {
                    $line->array_options = $extrafields_question->getOptionalsFromPost($extralabels_question, '_ei_q_' . $line->fk_c_question);
                }
                print $line->showOptionals($extrafields_question, 'edit', array(), '_ei_q_' . $line->fk_c_question);
            }
        } else {
          ?>
          <tr>
            <td><?php print $line->label_question ?></td>
            <td width="50%"><?php print $line->label_answer . (!empty($line->text_answer) ? $form->textwithtooltip($line->text_answer, 'text_answer_' . $line->fk_c_question, 1, 0, 'object_tip.png@extendedintervention') : '') ?></td>
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
    }
    // Other attributes of the question bloc
    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $question_bloc, $action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook) && ! empty($extrafields_question_bloc->attribute_label)) {
        if (isset($_POST['ei_qb_complementary'])) {
            $question_bloc->array_options = $extrafields_question_bloc->getOptionalsFromPost($extralabels_question_bloc, '_ei_qb');
        }
        print $question_bloc->showOptionals($extrafields_question_bloc, 'edit',  array(), '_ei_qb');
    }
        // Complementary text of the question bloc
        ?>
        <tr>
          <td><?php print $langs->trans('ExtendedInterventionSurveyComplementaryText') ?></td>
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
