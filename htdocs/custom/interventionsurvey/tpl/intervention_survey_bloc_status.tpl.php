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
// $bloc
// $form

// Protection to avoid direct call of template
if (empty($bloc)) {
    print "Error, template page can't be called as URL";
    dol_syslog("Error, template page can't be called as URL : " . $_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"], LOG_ERR);
    exit;
}
?>

<?php

if(isset($readonly)){
        // We print nothing as chosen status et justification text are displayed into title part

}
else
{
    //We prepare list of status for html
    $statusList = array();
                foreach ($bloc->status as $status) {
                    $statusList[$status->id] = $status->label;
                }
    $selectStatus = $form->selectarray('bloc[' . $bloc->id .']["fk_chosen_status"]', $statusList, $bloc->fk_chosen_status, 1, 0, 0, '', 0, 0, 0, '', 'centpercent');
    //We prepare list of predefined text for html
    $selectPredefinedText = $form->selectarray('bloc[' . $bloc->id .']["lastChosenPredefinedText"]', array(), $bloc->fk_c_survey_bloc_question, 1, 0, 0, '', 0, 0, 0, '', 'centpercent');

    //We prepare a list of object with status and predefined text informations for jQuery interactions

    $listOfPredefinedTextAndStatus = array();
    foreach ($bloc->status as $status) {
        $listOfPredefinedTextAndStatus[$status->id] = array();
        $listOfPredefinedTextAndStatus[$status->id]["mandatory_justification"] = $status->mandatory_justification;
        $listOfPredefinedTextAndStatus[$status->id]["predefined_text"]=array();
        foreach($status->predefined_texts as $predefined_text)
        {
            $listOfPredefinedTextAndStatus[$status->id]["predefined_text"][$predefined_text->id]=$predefined_text->label;
        }
    }
    $listOfPredefinedTextAndStatus = json_encode($listOfPredefinedTextAndStatus);

    //We prepare editor for justification status text
    $doleditor = new DolEditor('bloc[' . $bloc->id .']["justification_text"]',
                $bloc->justification_text, '', 150, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '90%');


//We display status select
?>

<tr>
    <td>
        <label id="<?php print 'bloc[' . $bloc->id .']["justification_title"]"'; ?>></label>
        <?php $langs->trans('InterventionSurveyBlocStatusLabel'); ?>
    </td>
    <td>
        <?php print $selectStatus;?>
    </td>
</tr>

<?php
//We display justification text area
?>

<tr>
    <td>
    <?php $langs->trans('InterventionSurveyJustificationText'); ?>
    </td>
    <td>
    <table class="noborderpadding" width="100%">
    <tr>
                <td>
                  <input type="hidden" id="<?php print 'bloc[' . $bloc->id .']["fk_chosen_answer_predefined_text"]'; ?>" name="<?php print 'bloc[' . $bloc->id .']["fk_chosen_answer_predefined_text"]'; ?>" value="<?php print $bloc[' . $bloc->id .']['fk_chosen_answer_predefined_text']; ?>"></input>
                  <?php print $selectPredefinedText; ?>
                </td>
                <td>
                  <input type="button" class="button status_predefined_text_<?php print $bloc->id?>"><?php $langs->trans('InterventionSurveyPredefinedTextUseButton');?></input>
                </td>
              </tr>
              <tr>
                <td colspan="2">' . $doleditor_static->Create(1) . '</td>
            </tr>
    </table>
    </td>
</tr>


<?php
            $javascript = <<<SCRIPT
            <script>
            $(document).ready(function () {
              var predefined_texts = $listOfPredefinedTextAndStatus;

              function ei_update_predefined_texts(select_status) {
                var select_predefined_texts = $('bloc[' . $bloc->id .']["lastChosenPredefinedText"]');
                var status_id = select_status.val();

                select_predefined_texts.empty();
                if (status_id in predefined_texts) {
                  $.map(predefined_texts[status_id].predefined_texts, function (val, i) {
                    select_predefined_texts.append($('<option>', {value: i, text: val.option}));
                  });
                }
              }

              function ei_update_mandatory(select_status) {
                var status_id = select_status.val();

                if (status_id in predefined_texts && predefined_texts[status_id].mandatory_justification) {
                  $('#bloc[' . $bloc->id .']["justification_title"]').addClass('fieldrequired');
                } else {
                  $('#bloc[' . $bloc->id .']["justification_title"]').removeClass('fieldrequired');
                }
              }


                $('select.bloc[' . $bloc->id .']["fk_chosen_status"]').on('change', function(e) {
                  ei_update_mandatory($(this));
                  ei_update_predefined_texts($(this));
                });

                $('select.bloc[' . $bloc->id .']["fk_chosen_status"]').map(function(e) {
                  ei_update_mandatory($(this));
                  ei_update_predefined_texts($(this));
                });

                $('input.button.status_predefined_text_$bloc->id').on('click', function (e) {
                  var status_id = $('select.bloc[' . $bloc->id .']["fk_chosen_status"]').val();
                  var predefined_text_id = $('#bloc[' . $bloc->id .']["lastChosenPredefinedText"]').val();

                  if (status_id in predefined_texts && predefined_text_id in predefined_texts[status_id].predefined_texts) {
                    var justificatory_id = 'bloc[' . $bloc->id .']["justification_text"]';

                    if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && justificatory_id in CKEDITOR.instances) {
                      var justificatory_textarea = CKEDITOR.instances[justificatory_id];
                      var last_text = justificatory_textarea.getData();

                      justificatory_textarea.setData((last_text.length > 0 ? last_text + '<br>' : '') + predefined_texts[status_id].predefined_texts[predefined_text_id].text);
                    } else {
                      var justificatory_textarea = $('#'+justificatory_id);
                      var last_text = justificatory_textarea.val();

                      justificatory_textarea.val((last_text.length > 0 ? last_text + "\\n" : '') + predefined_texts[status_id].predefined_texts[predefined_text_id].text);
                    }
                  }

                  e.stopPropagation();
                  e.preventDefault();
                  return false;
                });
            });
        </script>
SCRIPT;
    print $javascript;
}
?>
