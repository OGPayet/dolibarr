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
dol_include_once('/extendedintervention/class/extendedintervention.class.php');
dol_include_once('/extendedintervention/class/html.formextendedintervention.class.php');

$langs->load("interventions");
$langs->load("extendedintervention@extendedintervention");

$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');
$equipment_id = GETPOST('equipment_id','int');
$question_bloc_id = GETPOST('question_bloc_id','int');
$backtopage = GETPOST('backtopage','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

if(empty($user->rights->extendedintervention->questionnaireIntervention->lire)) accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('interventionsurvey'));

$object = new ExtendedIntervention($db);

// Optionals attributes and labels for question bloc
$extrafields_question_bloc = new ExtraFields($db);
$extralabels_question_bloc = $extrafields_question_bloc->fetch_name_optionals_label('extendedintervention_question_bloc');
// Optionals attributes and labels for question
$extrafields_question = new ExtraFields($db);
$extralabels_question = $extrafields_question->fetch_name_optionals_label('extendedintervention_question_blocdet');

// Load object
if ($id > 0 || !empty($ref)) {
    $ret = $object->fetch($id, $ref);
    if ($ret > 0) $ret = $object->fetch_thirdparty();
    if ($ret < 0) dol_print_error('', $object->errorsToString());
    if ($ret == 0) {
        print $langs->trans('NoRecordFound');
        exit();
    }
}

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    if ($action == 'confirm_save_question_bloc' && $user->rights->extendedintervention->questionnaireIntervention->creer &&
        $object->statut > ExtendedIntervention::STATUS_DRAFT && $object->statut < ExtendedIntervention::STATUS_DONE &&
        $object->id > 0 && $equipment_id > 0 && $question_bloc_id > 0) {
        if ($confirm == "yes") {
            $result = 0;
            $db->begin();

            dol_include_once('/extendedintervention/class/extendedinterventionsurveybloc.class.php');
            $survey_static = new EISurveyBloc($db);
            if (!$survey_static->is_read_only($object->id, $equipment_id) && $survey_static->fetch(0, $object->id, $equipment_id) > 0) {
                $survey_static->fk_fichinter = $object->id;
                $survey_static->fk_equipment = $equipment_id;

                if ($survey_static->id > 0) {
                    // Update
                    $result = $survey_static->update($user);
                } else {
                    // Create
                    $result = $survey_static->create($user);
                }

                if ($result > 0) {
                    dol_include_once('/extendedintervention/class/extendedinterventionquestionbloc.class.php');
                    $question_bloc_static = new EIQuestionBloc($db);
                    if (!$question_bloc_static->is_read_only($object->id, $equipment_id, $question_bloc_id) && $question_bloc_static->fetch(0, $object->id, $equipment_id, 0, $question_bloc_id) > 0) {
                        // Save question bloc
//                $question_bloc_static->oldcopy = clone $question_bloc_static; // Error 500
                        $question_bloc_static->fk_survey_bloc = $survey_static->id;
                        $question_bloc_static->fk_fichinter = $object->id;
                        $question_bloc_static->fk_equipment = $equipment_id;
                        $question_bloc_static->fk_c_question_bloc = $question_bloc_id;
                        $question_bloc_static->complementary_question_bloc = GETPOST('ei_qb_complementary');
                        $question_bloc_static->fk_c_question_bloc_status = GETPOST('ei_qb_status', 'int');
                        $question_bloc_static->justificatory_status = GETPOST('ei_qb_justificatory_status');
                        $question_bloc_static->array_options = $extrafields_question_bloc->getOptionalsFromPost($extralabels_question_bloc, '_ei_qb');
                        $question_bloc_static->position_question_bloc = null;
                        $question_bloc_static->code_question_bloc = null;
                        $question_bloc_static->label_question_bloc = null;
                        $question_bloc_static->extrafields_question_bloc = null;
                        $question_bloc_static->code_status = null;
                        $question_bloc_static->label_status = null;
                        $question_bloc_static->mandatory_status = null;

                        if ($question_bloc_static->id > 0) {
                            // Update
                            $result = $question_bloc_static->update($user);
                        } else {
                            // Create
                            $result = $question_bloc_static->create($user);
                        }

                        if ($result > 0) {
                            // Save questions
                            foreach ($question_bloc_static->lines as $line) {
                                if (!$line->read_only) {
//                            $line->oldline = clone $line; // Error 500
                                    $line->fk_question_bloc = $question_bloc_static->id;
                                    $line->fk_c_answer = GETPOST('ei_q_' . $line->fk_c_question . '_answer', 'int');
                                    $line->text_answer = GETPOST('ei_q_' . $line->fk_c_question . '_justificatory');
                                    $line->array_options = $extrafields_question->getOptionalsFromPost($extralabels_question, '_ei_q_' . $line->fk_c_question);
                                    $line->position_question = null;
                                    $line->code_question = null;
                                    $line->label_question = null;
                                    $line->extrafields_question = null;
                                    $line->code_answer = null;
                                    $line->label_answer = null;
                                    $line->mandatory_answer = null;

                                    if ($line->id > 0) {
                                        // Update
                                        $result = $line->update($user);
                                    } else {
                                        // Create
                                        $result = $line->insert($user);
                                    }
                                    if ($result < 0) {
                                        setEventMessages($line->error, null, 'errors');
                                        setEventMessages($line->error, $line->errors, 'errors');
                                        break;
                                    }
                                }
                            }
                        } else {
                            setEventMessages($question_bloc_static->error, $question_bloc_static->errors, 'errors');
                        }
                    }
                } else {
                    setEventMessages($survey_static->error, $survey_static->errors, 'errors');
                }
            }

            if ($result < 0) {
                $db->rollback();

                if ($result == -5) {
                    $action = "save_question_bloc";
                } else {
                    $action = "edit_question_bloc";
                }
            } else {
                $db->commit();
            }
        } else {
            $action = "edit_question_bloc";
        }
    }
}


/*
 * View
 */

$form = new Form($db);
$formextendedintervention = new FormExtendedIntervention($db);
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
    //$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    //$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref .= $langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
    // Project
    if (!empty($conf->projet->enabled)) {
        $langs->load("projects");
        $morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
        if ($user->rights->extendedintervention->questionnaireIntervention->creer) {
            if ($action != 'classify')
                //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
                $morehtmlref .= ' : ';
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
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

    if ($object->statut == ExtendedIntervention::STATUS_DRAFT) {
        print $langs->trans('ExtendedInterventionMustBeValidate');
    } else {
        $formconfirm = '';

	// Save question bloc confirmation
	if ($action == 'save_question_bloc' && $user->rights->extendedintervention->questionnaireIntervention->creer && $object->statut > ExtendedIntervention::STATUS_DRAFT && $object->statut < ExtendedIntervention::STATUS_DONE) {
            dol_include_once('/extendedintervention/class/extendedinterventionquestionbloc.class.php');
            $question_bloc_static = new EIQuestionBloc($db);
            if ($question_bloc_static->fetch(0, $object->id, $equipment_id, 0, $question_bloc_id, 1) > 0) {
                $formquestion = array();

                // Add form elements
                foreach ($_POST as $k => $v) {
                    if ($k == 'action' || $k == 'token' || $k == 'ei_qb_status' || $k == 'ei_qb_justificatory_status') continue;
                    if (is_array($v)) {
                        foreach ($v as $va) {
                            $formquestion[] = array('type' => 'hidden', 'name' => $k . '[]', 'value' => $va);
                        }
                    } else {
                        $formquestion[] = array('type' => 'hidden', 'name' => $k, 'value' => $v);
                    }
                }

                $status_list = array();
                foreach ($question_bloc_static->status_list as $status_id => $status) {
                    $status_list[$status_id] = $status['label'];
                }
                $predefined_texts = array();
                foreach ($question_bloc_static->status_list as $status_id => $status) {
                    $predefined_texts[$status_id] = array(
                        'predefined_texts' => array(),
                        'mandatory' => !empty($status['mandatory'])
                    );
                    foreach ($status['predefined_texts'] as $predefined_text_id => $predefined_text) {
                        $predefined_texts[$status_id]['predefined_texts'][$predefined_text['position'] . '_' . $predefined_text_id] = [
                            'option' => dol_string_nohtmltag($predefined_text['predefined_text']),
                            'text' => $predefined_text['predefined_text']
                        ];
                    }
                }
                $predefined_texts = json_encode($predefined_texts);
                $doleditor_static = new DolEditor('ei_qb_justificatory_status', isset($_POST['ei_qb_justificatory_status']) ? GETPOST('ei_qb_justificatory_status') : $question_bloc_static->justificatory_status,
                    '', 150, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled), ROWS_5, '90%');

                $warning = '';
                if ($question_bloc_static->warning_code_status) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionBlocStatusCode');
                if ($question_bloc_static->warning_label_status) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionBlocStatusLabel');
                if ($question_bloc_static->warning_mandatory_status) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionQuestionBlocStatusJustificatoryMandatory');
                if (!empty($warning)) $warning = ' ' . $form->textwithpicto('', '<b>' . $langs->trans('ExtendedInterventionSurveyConfigurationChanged') . ' :</b>' . $warning, 1, 'warning', '', 0, 2);

                $formquestion[] = array('type' => 'hidden', 'name' => 'token', 'value' => $_SESSION['newtoken']);
                $formquestion[] = array('type' => 'other', 'name' => 'ei_qb_status', 'label' => /*'<label class="fieldrequired">' .*/ $langs->trans("Status") /*. '</label>'*/ . $warning, 'value' => $form->selectarray('ei_qb_status', $status_list, isset($_POST['ei_qb_status']) ? GETPOST('ei_qb_status', "int") : $question_bloc_static->fk_c_question_bloc_status, 1, 0, 0, '', 0, 0, 0, '', 'centpercent ei_qb_status'));
                $formquestion[] = array('type' => 'other', 'name' => 'ei_qb_justificatory_status', 'label' => '<label id="ei_qb_justificatory_status_label">' . $langs->trans("ExtendedInterventionJustificatory") . '</label>', 'value' => '<table class="nobordernopadding" width="100%">
                  <tr>
                    <td>
                      ' . $form->selectarray('ei_qb_status_predefined_texts', array(), isset($_POST['ei_qb_status_predefined_texts']) ? GETPOST('ei_qb_status_predefined_texts', "alpha") : '', 1, 0, 0, '', 0, 0, 0, '', 'centpercent') . '
                    </td>
                    <td>
                      <input type="button" class="button ei_qb_bt_status_predefined_texts" value="' . $langs->trans("ExtendedInterventionUse") . '">
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">' . $doleditor_static->Create(1) . '</td>
                  </tr>
                </table>');

                $formconfirm = $formextendedintervention->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '#ei_anchor_sb_'.$survey_bloc->fk_equipment.'_qb_' . $question_bloc_id, $langs->trans('ExtendedInterventionSaveQuestionBloc'), $langs->trans('ExtendedInterventionConfirmSaveQuestionBloc'), 'confirm_save_question_bloc', $formquestion, 'yes', 2, 450, 900, 1);
                $formconfirm .= <<<SCRIPT
    <script>
        $(document).ready(function () {
          var predefined_texts = $predefined_texts;

          function ei_update_predefined_texts(select_status) {
            var select_predefined_texts = $('#ei_qb_status_predefined_texts');
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

            if (status_id in predefined_texts && predefined_texts[status_id].mandatory) {
              $('#ei_qb_justificatory_status_label').addClass('fieldrequired');
            } else {
              $('#ei_qb_justificatory_status_label').removeClass('fieldrequired');
            }
          }

          $("#dialog-confirm").on("dialogopen", function() {
            $('select.ei_qb_status').on('change', function(e) {
              ei_update_mandatory($(this));
              ei_update_predefined_texts($(this));
            });

            $('select.ei_qb_status').map(function(e) {
              ei_update_mandatory($(this));
              ei_update_predefined_texts($(this));
            });

            $('input.button.ei_qb_bt_status_predefined_texts').on('click', function (e) {
              var status_id = $('select.ei_qb_status').val();
              var predefined_text_id = $('#ei_qb_status_predefined_texts').val();

              if (status_id in predefined_texts && predefined_text_id in predefined_texts[status_id].predefined_texts) {
                var justificatory_id = 'ei_qb_justificatory_status';

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
        });
    </script>
SCRIPT;
            } else {
                setEventMessages($question_bloc_static->error, $question_bloc_static->errors, 'errors');
            }
        }

        $parameters = array();
        $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
        elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;

        $ret = $object->fetch_survey();
        if ($ret < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            $dirtpls = array_merge($conf->modules_parts['tpl'], array('/extendedintervention/tpl'));

            // Print left question bloc of the survey
            if (count($object->survey)) {
                print '<style>td.ei_ef_question { padding-left: 15px !important; };</style>';
                print '<script type="text/javascript" language="javascript">' . "\n" . '$(document).ready(function () {';
                foreach ($extrafields_question->attribute_label as $key => $val) {
                    print '$.map($(".extendedintervention_eiquestionblocline_extras_' . $key . '"), function(item) { $(item).closest("tr").children("td").first().addClass("ei_ef_question"); });';
                }
                print '});' . "\n" . '</script>';

                foreach ($object->survey as $survey_bloc) {
                    $warning = '';
                    if (!$survey_bloc->read_only && $equipment_id == $survey_bloc->fk_equipment && $action == 'edit_question_bloc' && $user->rights->extendedintervention->questionnaireIntervention->creer && $object->statut < ExtendedIntervention::STATUS_DONE) {
                        $survey_bloc->load_warning();
                        if ($survey_bloc->warning_fk_product) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionProductId');
                        if ($survey_bloc->warning_equipment_ref) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionEquipmentRef');
                        if ($survey_bloc->warning_product_ref) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionProductRef');
                        if ($survey_bloc->warning_product_label) $warning .= '<br> - ' . $langs->trans('ExtendedInterventionProductLabel');
                        if (!empty($warning)) $warning = ' ' . $form->textwithpicto('', '<b>' . $langs->trans('ExtendedInterventionWarningEquipmentInfoChanged') . ' :</b>' . $warning, 1, 'warning', '', 0, 2);
                    }
                    print load_fiche_titre('<b>'.($survey_bloc->fk_equipment > 0 ? $langs->trans('ExtendedInterventionSurveyBlocTitle', $survey_bloc->product_label, $survey_bloc->product_ref, $survey_bloc->equipment_ref) : $langs->trans('ExtendedInterventionSurveyBlocGeneralTitle')).'</b>' . $warning, '', '');
                    $idx = 1;
                    foreach ($survey_bloc->survey as $question_bloc) {
                        if ($idx % 2 == 1) {
                            print '<div class="fichecenter">';
                        }
                        if (!$survey_bloc->read_only && !$question_bloc->read_only && $equipment_id == $survey_bloc->fk_equipment && $question_bloc_id == $question_bloc->fk_c_question_bloc && $action == 'edit_question_bloc' && $user->rights->extendedintervention->questionnaireIntervention->creer && $object->statut < ExtendedIntervention::STATUS_DONE) {
                            if ($question_bloc->fetch(0, $survey_bloc->fk_fichinter, $survey_bloc->fk_equipment, 0, $question_bloc->fk_c_question_bloc, 1, 0) > 0) {
                                // Edit question bloc of the survey
                                foreach ($dirtpls as $reldir) {
                                    $res = @include dol_buildpath($reldir . '/ei_survey_edit.tpl.php');
                                    if ($res) break;
                                }
                            } else {
                                setEventMessages($question_bloc->error, $question_bloc->errors, 'errors');
                                break;
                            }
                        } else {
                            // View question bloc of the survey
                            foreach ($dirtpls as $reldir) {
                                $res = @include dol_buildpath($reldir . '/ei_survey_view.tpl.php');
                                if ($res) break;
                            }
                        }
                        if ($idx % 2 == 0) {
                            print '</div>';
                        }
                        $idx++;
                    }
                    if ($idx % 2 != 1) {
                        print '</div>';
                    }

                    // Print equipment task of the survey
                    //todo
                }
            }
        }
    }

    dol_fiche_end();
}

llxFooter();
$db->close();
