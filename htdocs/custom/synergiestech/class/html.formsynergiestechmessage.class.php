<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       synergiestech/core/class/html.formsynergiestechmessage.class.php
 *  \ingroup    synergiestech
 *	\brief      File of class with all html for request manager message specific synergies tech
 */

dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');

/**
 *	Class to manage generation of HTML components for request manager message specific synergies tech
 *
 */
class FormSynergiesTechMessage extends FormRequestManagerMessage
{
    /**
     *  Output html form to send a message
     *
     * @param   string      $addfileaction      Name of action when posting file attachments
     * @param   string      $removefileaction   Name of action when removing file attachments
     * @return  string                          HTML string with form to send a message
     */
    function get_message_form($addfileaction='addfile', $removefileaction='removefile')
    {
        global $conf, $langs, $user, $hookmanager, $form, $formrequestmanager;

        dol_include_once('/requestmanager/class/requestmanagermessage.class.php');

        if (!is_object($form)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            $form = new Form($this->db);
        }

        $langs->loadLangs(array("other", "requestmanager@requestmanager", "requestmanager@synergiestechsynergiestech"));

        $hookmanager->initHooks(array('requestmanagerformmessage'));

        $parameters = array(
            'addfileaction' => &$addfileaction,
            'removefileaction' => &$removefileaction,
        );
        $reshook = $hookmanager->executeHooks('getRequestManagerMessageForm', $parameters, $this);
        if (!empty($reshook)) {
            return $hookmanager->resPrint;
        }

        if (!is_object($formrequestmanager)) {
            dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');
            $formrequestmanager = new FormRequestManager($this->db);
        }

        // Make
        $out = '';

        // Define list of attached files
        $listofpaths = array();
        $listofnames = array();
        $listofmimes = array();
        if (!empty($_SESSION[$this->key_list_of_paths])) $listofpaths = explode(';', $_SESSION[$this->key_list_of_paths]);
        if (!empty($_SESSION[$this->key_list_of_names])) $listofnames = explode(';', $_SESSION[$this->key_list_of_names]);
        if (!empty($_SESSION[$this->key_list_of_mimes])) $listofmimes = explode(';', $_SESSION[$this->key_list_of_mimes]);

        // Define output language
        $outputlangs = $langs;
        $newlang = '';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $this->param['langsmodels'];
        if (!empty($newlang)) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($newlang);
            $outputlangs->load('other');
        }

        $out .= "\n" . '<!-- Begin form message --><div id="requestmanagermessageformdiv"></div>' . "\n";
        if ($this->withform == 1) {
            $out .= '<form method="POST" name="requestmanagermessageform" id="requestmanagermessageform" enctype="multipart/form-data" action="' . $this->param["returnurl"] . '#formmessagebeforetitle">' . "\n";
            $out .= '<input style="display:none" type="submit" id="addmessage" name="addmessage">';
            $out .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
            $out .= '<a id="formrequestmanagermessage" name="formrequestmanagermessage"></a>';
        }

        // Hidden parameters
        //--------------------------
        foreach ($this->param as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $arrKey => $arrValue) {
                    $out .= '<input type="hidden" id="' . $arrKey . '" name="' . $arrKey . '[]" value="' . $arrValue . '" />' . "\n";
                }
            } else {
                $out .= '<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $value . '" />' . "\n";
            }
        }

        $out .= '<table class="border" width="100%">' . "\n";

        // Message type
        //-----------------
        $message_type_out = RequestManagerMessage::MESSAGE_TYPE_OUT;
        $message_type_private = RequestManagerMessage::MESSAGE_TYPE_PRIVATE;
        $message_type = GETPOST('message_type', 'alpha', 2);
        $out .= '<tr>';
        $out .= '<td class="fieldrequired" width="180">' . $langs->trans("RequestManagerMessageType") . '</td>';
        $out .= '<td>';
        $out .= '<input type="radio" id="message_type_out" name="message_type" value="' . RequestManagerMessage::MESSAGE_TYPE_OUT . '"' . ($message_type != RequestManagerMessage::MESSAGE_TYPE_PRIVATE && $message_type != RequestManagerMessage::MESSAGE_TYPE_IN ? ' checked="checked"' : '') . '/>';
        $out .= '&nbsp;<label for="message_type_out">' . $langs->trans("RequestManagerMessageTypeOut") . '&nbsp;' . img_help(0, $langs->trans("RequestManagerMessageTypeOutHelp")) . '</label>';
        $out .= ' &nbsp; ';
        $out .= '<input type="radio" id="message_type_private" name="message_type" value="' . RequestManagerMessage::MESSAGE_TYPE_PRIVATE . '"' . ($message_type == RequestManagerMessage::MESSAGE_TYPE_PRIVATE ? ' checked="checked"' : '') . '/>';
        $out .= '&nbsp;<label for="message_type_private">' . $langs->trans("RequestManagerMessageTypePrivate") . '&nbsp;' . img_help(0, $langs->trans("RequestManagerMessageTypePrivateHelp")) . '</label>';
        $out .= "</td></tr>\n";
        $out .= <<<SCRIPT
             <script type="text/javascript" language="javascript">
                 jQuery(document).ready(function () {
                     rm_update_message_type_options($('input[type=radio][name="message_type"]:checked').val());

                     // Change message type
                     $('input[type="radio"][name="message_type"]').on("change", function () {
                         rm_update_message_type_options(this.value);
                     });

                     // Update html element for a message type
                     function rm_update_message_type_options(value) {
                         value = parseInt(value);
                         switch (value) {
                             case $message_type_out:
                                 $('input#notify_requesters').prop('disabled', false);
                                 $('input#notify_watchers').prop('disabled', false);
                                 $('#subject_label').addClass('fieldrequired');
                                 $('input#subject').prop('disabled', false);
                                 break;
                             case $message_type_private:
                                 $('input#notify_requesters').prop('disabled', true);
                                 $('input#notify_watchers').prop('disabled', true);
                                 $('#subject_label').removeClass('fieldrequired');
                                 $('input#subject').prop('disabled', true);
                                 break;
                         }
                     }
                 });
             </script>
SCRIPT;

        // Notify
        //-----------------
        $notify_assigned = GETPOST('notify_assigned', 'int', 2);
        $notify_requester = GETPOST('notify_requester', 'int', 2);
        $notify_watcher = GETPOST('notify_watcher', 'int', 2);
        if ($notify_assigned === '') $notify_assigned = $this->requestmanager->notify_assigned_by_email;
        if ($notify_requester === '') $notify_requester = $this->requestmanager->notify_requester_by_email;
        if ($notify_watcher === '') $notify_watcher = $this->requestmanager->notify_watcher_by_email;
        $out .= '<tr>';
        $out .= '<td width="180">' . $langs->trans("RequestManagerMessageNotify") . '</td>';
        $out .= '<td>';
        if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL)) {
            $out .= '<input type="checkbox" id="notify_assigned" name="notify_assigned" value="1"' . (!empty($notify_assigned) ? ' checked="checked"' : '') . ' />';
            $out .= '&nbsp;<label for="notify_assigned">' . $langs->trans("RequestManagerAssigned") . '</label>';
            $out .= ' &nbsp; ';
        }
        $out .= '<input type="checkbox" id="notify_requesters" name="notify_requesters" value="1"' . (!empty($notify_requester) ? ' checked="checked"' : '') . ' />';
        $out .= '&nbsp;<label for="notify_requester">' . $langs->trans("RequestManagerRequesterContacts") . '</label>';
        $out .= ' &nbsp; ';
        $out .= '<input type="checkbox" id="notify_watchers" name="notify_watchers" value="1"' . (!empty($notify_watcher) ? ' checked="checked"' : '') . ' />';
        $out .= '&nbsp;<label for="notify_watcher">' . $langs->trans("RequestManagerWatcherContacts") . '</label>';
        $out .= "</td></tr>\n";

        // Other attributes
        //----------------------
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        $requestmanagermessage = new RequestManagerMessage($this->db);
        $extralabels = $extrafields->fetch_name_optionals_label($requestmanagermessage->table_element);
        $ret = $extrafields->setOptionalsFromPost($extralabels, $requestmanagermessage);
        $parameters = array(
            'messageform' => &$this,
            'addfileaction' => &$addfileaction,
            'removefileaction' => &$removefileaction,
        );
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $requestmanagermessage); // Note that $action and $this may have been modified by hook
        $out .= $hookmanager->resPrint;
        if (empty($reshook) && !empty($extrafields->attribute_label)) {
            $out .= $requestmanagermessage->showOptionals($extrafields, 'edit');
        }

        // Event confidentiality
        //--------------------------------------
        if ($conf->eventconfidentiality->enabled && $user->rights->eventconfidentiality->manage) {
            $langs->load('eventconfidentiality@eventconfidentiality');

            // Get all tags
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $dictionary = Dictionary::getDictionary($this->db, 'eventconfidentiality', 'eventconfidentialitytag');
            $result = $dictionary->fetch_lines(1, array(), array('label' => 'ASC'));
            if ($result < 0) {
                $this->error = $dictionary->error;
                $this->errors = $dictionary->errors;
                return -1;
            }

            // Get tags set or default tags
            dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
            $eventconfidentiality = new EventConfidentiality($this->db);
            $default_tags = $eventconfidentiality->getDefaultTags($this->elementtype);
            if (!is_array($default_tags)) {
                $this->error = $eventconfidentiality->error;
                $this->errors = $eventconfidentiality->errors;
                return -1;
            }

            // Format out tags lines
            $internal_tags = '';
            $external_tags = '';
            $initialize_tags = true;
            foreach ($dictionary->lines as $line) {
                if (isset($_POST['ec_mode_' . $line->id])) $initialize_tags = false;
                $mode = isset($_POST['ec_mode_' . $line->id]) ? GETPOST('ec_mode_' . $line->id, 'int') : EventConfidentiality::MODE_HIDDEN;
                if (empty($line->fields['external']) && !$user->rights->eventconfidentiality->internal->lire) {
                    $tmp = '<input type="hidden" name="ec_mode_' . $line->id . '" value="' . ($mode == EventConfidentiality::MODE_VISIBLE ? 0 : ($mode == EventConfidentiality::MODE_BLURRED ? 1 : 2)) . '">';
                } else {
                    $tmp = '<tr id="' . $line->id . '">';
                    $tmp .= '<td>' . $line->fields['label'] . '</td>';
                    $tmp .= '<td>';
                    $tmp .= '<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_VISIBLE . '" name="ec_mode_' . $line->id . '" value="0"' . ($mode == EventConfidentiality::MODE_VISIBLE ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_VISIBLE . '">' . $langs->trans('EventConfidentialityModeVisible') . '</label>';
                    $tmp .= '&nbsp;<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_BLURRED . '" name="ec_mode_' . $line->id . '" value="1"' . ($mode == EventConfidentiality::MODE_BLURRED ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_BLURRED . '">' . $langs->trans('EventConfidentialityModeBlurred') . '</label>';
                    $tmp .= '&nbsp;<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_HIDDEN . '" name="ec_mode_' . $line->id . '" value="2"' . ($mode == EventConfidentiality::MODE_HIDDEN ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_HIDDEN . '">' . $langs->trans('EventConfidentialityModeHidden') . '</label>';
                    $tmp .= '</td>';
                    $tmp .= '</tr>';
                }
                if (empty($line->fields['external'])) {
                    $internal_tags .= $tmp;
                } else {
                    $external_tags .= $tmp;
                }
            }
            if ($user->rights->eventconfidentiality->internal->lire) {
                // Internal tags
                $out .= '<tr>';
                $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagInterneLabel") . '</td>';
                $out .= '<td colspan="3"><table class="noborder margintable centpercent">';
                $out .= '<tr><th class="liste_titre" width="40%">Tags</th><th class="liste_titre">Mode</th></tr>';
                $out .= $internal_tags;
                $out .= '</table></td>';
                $out .= '</tr>';
            } else {
                $out .= $internal_tags;
            }
            // External tags
            $out .= '<tr>';
            $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagExterneLabel") . '</td>';
            $out .= '<td colspan="3"><table class="noborder margintable centpercent">';
            $out .= '<tr><th class="liste_titre" width="40%">Tags</th><th class="liste_titre">Mode</th></tr>';
            $out .= $external_tags;
            $out .= '</table></td>';
            $out .= '</tr>';

            $default_tags = json_encode($default_tags);
            $message_type_list = json_encode(array(
                RequestManagerMessage::MESSAGE_TYPE_OUT => 'AC_RM_OUT',
                RequestManagerMessage::MESSAGE_TYPE_PRIVATE => 'AC_RM_PRIV',
            ));
            $hidden_mode = EventConfidentiality::MODE_HIDDEN;
            $out .= <<<SCRIPT
     <script type="text/javascript" language="javascript">
         $(document).ready(function () {
             var rm_default_tags = $default_tags;
             var rm_message_type_list = $message_type_list;

             if ($initialize_tags) rm_update_tags();
             $('input[name="message_type"]').on('change', function() {
                 rm_update_tags();
             });

             function rm_update_tags() {
                 var message_type = $('input[name="message_type"]:checked').val();
                 var action_code = rm_message_type_list[message_type];

                 if (action_code in rm_default_tags) {
                     $.map(rm_default_tags[action_code], function(val, idx) {
                         $('#ec_mode_' + idx + '_' + val['mode']).prop('checked', true);
                         $('input[type="hidden"][name="ec_mode_' + idx + '"]').val(val['mode']);
                     })
                 } else {
                     $.map($('[id^="ec_mode_"][id$="_$hidden_mode"]'), function(val, idx) {
                         $(val).prop('checked', true);
                     });
                     $.map($('input[type="hidden"][name^="ec_mode_"]'), function(val, idx) {
                         $(val).val($hidden_mode);
                     });
                 }
             }
         });
     </script>
SCRIPT;
        }

        // Substitution help
        //-----------------------
        // List of help for fields
        dol_include_once('/requestmanager/class/requestmanagersubstitutes.class.php');
        $subsituteKeys = RequestManagerSubstitutes::getAvailableSubstitutesKeyFromRequest($this->db, 1, $this->requestmanager);
        $this->substit = RequestManagerSubstitutes::setSubstitutesFromRequest($this->db, $this->requestmanager);
        $helpSubstitution = $langs->trans("AvailableVariables") . ':<br>';
        $helpSubstitution .= '<div style="display: block; overflow: auto; height: 700px;"><table class="nobordernopadding">';
        foreach ($subsituteKeys as $key => $label) {
            $helpSubstitution .= "<tr><td><span style='margin-right: 10px;'>" . $key . ' :</span></td><td>' . $label . '</td></tr>';
        }
        $helpSubstitution .= '</table></div>';

        // Get template list
        //--------------------------
        $result = $this->fetchAllMessageTemplate($this->requestmanager->fk_type);
        if ($result < 0) {
            setEventMessages($this->error, null, 'errors');
        }
        $modelmessage_array = array();
        foreach ($this->message_templates_list as $line) {
            $modelmessage_array[$line->id] = $line->fields['label'];
        }

        // Get default message template
        //----------------------------------
        $model_id = !empty($this->param["models_id"]) ? $this->param["models_id"] : 0;
        $default_message = $this->message_templates_list[$model_id]->fields;

        $out .= '<tr><td valign="top" colspan="2"><table class="nobordernopadding" width="100%"><tr>' . "\n";

        // Select template
        //--------------------------
        $out .= '<td style="padding-right: 15px;" width="25%">' . "\n";
        if (count($modelmessage_array) > 0) {
            $out .= $langs->trans('RequestManagerSelectTemplate') . ': ' . $form->selectarray('stmodelmessageselected', $modelmessage_array, 0, 1);
        } else {
            // Do not put disabled on option, it is already on select and it makes chrome crazy.
            $out .= $langs->trans('RequestManagerSelectTemplate') . ': <select name="stmodelmessageselected" disabled="disabled"><option value="none">' . $langs->trans("RequestManagerNoMessageTemplateDefined") . '</option></select>';
        }
        if ($user->admin) $out .= '&nbsp;' . info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv('Setup') . ' - ' . $langs->transnoentitiesnoconv('Module163018Name')), 1);
        $out .= ' &nbsp; ';
        $out .= '<input class="button" type="submit" value="' . $langs->trans('Apply') . '" name="modelselected" id="modelselected"' . (count($modelmessage_array) > 0 ? '' : ' disabled="disabled"') . '>';
        $out .= '</td>';

        // Subject
        //-----------------
        $default_subject = !empty($default_message['subject']) ? $default_message['subject'] : '';
        $subject = !empty($default_subject) ? $default_subject : GETPOST('subject', 'alpha', 2);
        $subject = make_substitutions($subject, $this->substit);

        $out .= '<td id="subject_label" width="150px">' . $langs->trans("RequestManagerSubject");
        $out .= '&nbsp;' . $form->textwithpicto('', $helpSubstitution, 1, 'help', '', 0, 2, 'substitution');
        $out .= '</td><td>' . "\n";
        $out .= '<input type="text" id="subject" name="subject" style="width: 95%;" max="255" value="' . dol_escape_htmltag($subject) . '">';
        $out .= "</td>\n";

        $out .= '</tr></table></td></tr>' . "\n";

        // Get knowledge base list
        //--------------------------
        $this->requestmanager->fetch_tags();
        $categories_selected = isset($_POST['tags_categories']) ? GETPOST('tags_categories', 'array') : $this->requestmanager->tag_ids;
        $result = $this->fetchAllKnowledgeBase($this->requestmanager->fk_type, $categories_selected);
        if ($result < 0) {
            setEventMessages($this->error, null, 'errors');
        }
        $modelknowledgebase_array = array();
        $modelknowledgebase_texts = array();
        foreach ($this->knowledge_base_list as $line) {
            $modelknowledgebase_array[$line->id] = $line->fields['title'];
            $modelknowledgebase_texts[$line->id] = $line->fields['description'];
        }

        $out .= '<tr><td valign="top" colspan="2"><table class="nobordernopadding" width="100%"><tr>' . "\n";

        // Select product tag
        //--------------------------
        $out .= '<td>' . "\n";
        $out .= $langs->trans('RequestManagerTags');
        $out .= '</td><td style="padding-right: 15px;" width="40%">' . "\n";
        $out .= $formrequestmanager->showCategories($this->requestmanager->id, CategorieRequestManager::TYPE_REQUESTMANAGER, 0, TRUE, 'tags_categories', $categories_selected);
        $out .= '</td>';

        // Select knowledge base
        //------------------------
        $out .= '<td>' . $langs->trans("RequestManagerMessageKnowledgeBase") . '</td>';
        $out .= '<td id="knowledgebase">';
        if (count($modelknowledgebase_array) > 0) {
            $out .= $form->selectarray('knowledgebaseselected', $modelknowledgebase_array, '', 1, 0, 0, '', 0, 0, 0, '', ' minwidth300');
        } else {
            // Do not put disabled on option, it is already on select and it makes chrome crazy.
            $out .= '<select id="knowledgebaseselected" name="knowledgebaseselected" disabled="disabled"><option value="none">' . $langs->trans("RequestManagerNoKnowledgeBaseDefined") . '</option></select>';
        }
        if ($user->admin) $out .= '&nbsp;' . info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv('Setup') . ' - ' . $langs->transnoentitiesnoconv('Module163018Name')), 1);
        $out .= ' &nbsp; ';
        $out .= '<input class="button" type="button" value="' . $langs->trans('RequestManagerAddKnowledgeBaseDescriptions') . '" id="addknowledgebasedescription" disabled="disabled">';
        $out .= "</td></tr>\n";

        $out .= '</tr></table>' . "\n";

        $knowledgebase_ajax_url = dol_buildpath('/synergiestech/ajax/knowledgebase_td.php', 1);
        $modelknowledgebase_texts_json = json_encode($modelknowledgebase_texts);
        $out .= <<<SCRIPT
            <script type="text/javascript" language="javascript">
                $(document).ready(function () {
                    var knowledgebaseselected = $("select#knowledgebaseselected");
                    var knowledgebase_texts = $modelknowledgebase_texts_json;

                    $("#tags_categories").on('change', function() {
                        update_td_knowledgebase($(this).val());
                    });

                    function update_td_knowledgebase(selected_tags_categories) {
                        $.ajax({
                            method: "POST",
                            url: "$knowledgebase_ajax_url",
                            data: { request_type: {$this->requestmanager->fk_type}, selected_tags_categories: selected_tags_categories },
                            dataType: "json"
                        }).done(function(data) {
                            knowledgebaseselected.empty();
                            knowledgebaseselected.append(data.values);
                            knowledgebase_texts = data.texts;
                            knowledgebaseselected.prop('disabled', data.error);
                            knowledgebaseselected.change();
                        }).fail(function(jqXHR, textStatus) {
                            knowledgebaseselected.empty();
                            knowledgebaseselected.append('<option value="none">Request failed: ' + textStatus + '</option>');
                            knowledgebase_texts = [];
                            knowledgebaseselected.prop('disabled', true);
                            knowledgebaseselected.change();
                        });
                    }

                    // Disable add knowledge button
                    knowledgebaseselected.on('change', function() {
                        var disabled = knowledgebaseselected.length == 0 || knowledgebaseselected.is(':disabled') || !(knowledgebaseselected.val() > 0);
                        $("#addknowledgebasedescription").prop('disabled', disabled);
                    });

                    // Add knowledge text in description and tag in list when click on add button
                    $('#addknowledgebasedescription').click(function() {
                        var knowledgebaseselected_id = knowledgebaseselected.val();
                        var knowledgebaseselected_label = $("select#knowledgebaseselected option:selected").text();
                        var knowledgebaseselected_text = knowledgebase_texts[knowledgebaseselected_id];
                        var knowledgebaselist = $("#knowledgebaselist");
                        var knowledgebaselist_values = knowledgebaselist.val();

                        $("#knowledgebaselisttext").append('<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'+knowledgebaseselected_label+'</li>');
                        knowledgebaselist.val(knowledgebaselist_values + (knowledgebaselist_values.length > 0 ? ',' : '') + knowledgebaseselected_id);
                        if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && CKEDITOR.instances['message'] != "undefined") {
                            var text = CKEDITOR.instances['message'].getData();
                            CKEDITOR.instances['message'].setData((text.length > 0 ? text + "<br>" : "") + knowledgebaseselected_label + " :<br>" +  knowledgebaseselected_text);
                        } else {
                            var text = $("#message").text();
                            $("#message").append((text.length > 0 ? "\\n" : "") + knowledgebaseselected_label + " :\\n" +  knowledgebaseselected_text);
                        }
                        knowledgebaseselected.val(null).change();
                    });
                });
            </script>
SCRIPT;
        $out .= '</td></tr>' . "\n";

        // Selected knowledge base list
        //------------------------
        $knowledgebaselist = !empty($this->param["knowledgebaselist"]) ? explode(',', $this->param["knowledgebaselist"]) : array();
        $knowledgebaselisttext = array();
        foreach ($knowledgebaselist as $knowledge_id) {
            $knowledgebaselisttext[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$this->knowledge_base_list[$knowledge_id]->fields['title'].'</li>';
        }
        $out .= '<tr>';
        $out .= '<td>' . $langs->trans("SynergiesTechMessageKnowledgeBaseSelected") . '</td>';
        $out .= '<td>';
        $out .= '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr" id="knowledgebaselisttext">' . implode(' ', $knowledgebaselisttext) . '</ul></div>';
        $out .= "</td></tr>\n";

        // Message
        //-----------------
        $default_body = !empty($default_message['message']) ? $default_message['message'] : '';
        $message = !empty($default_body) ? $default_body : GETPOST('message', '', 2);
        $message = make_substitutions($message, $this->substit);
        // Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
        //$message = preg_replace("/^(<br>)+/", "", $message);
        //$message = preg_replace("/^\n+/", "", $message);

        $out .= '<tr>';
        $out .= '<td class="fieldrequired" width="180" valign="top">' . $langs->trans("RequestManagerMessage");
        $out .= '&nbsp;' . $form->textwithpicto('', $helpSubstitution, 1, 'help', '', 0, 2, 'substitution');
        $out .= '</td>';
        $out .= '<td>';
        // Editor wysiwyg
        require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        $doleditor = new DolEditor('message', $message, '', 280, 'dolibarr_notes', 'In', true, true, 1, 8, '95%');
        $out .= $doleditor->Create(1);
        $out .= "</td></tr>\n";

        // Attached files
        //-----------------
        $out .= '<tr>';
        $out .= '<td width="180">' . $langs->trans("RequestManagerMessageFile") . '</td>';
        $out .= '<td>';
        // TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
        $out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">' . "\n";
        $out .= '<script type="text/javascript" language="javascript">';
        $out .= 'jQuery(document).ready(function () {';
        $out .= '    jQuery(".removedfile").click(function() {';
        $out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
        $out .= '    });';
        $out .= '})';
        $out .= '</script>' . "\n";
        if (count($listofpaths)) {
            foreach ($listofpaths as $key => $val) {
                $out .= '<div id="attachfile_' . $key . '">';
                $out .= img_mime($listofnames[$key]) . ' ' . $listofnames[$key];
                $out .= ' <input type="image" style="border: 0px;" src="' . img_picto('', 'delete.png', '', false, 1) . '" value="' . ($key + 1) . '" class="removedfile" id="' . $removefileaction . '_' . $key . '" name="' . $removefileaction . '_' . $key . '" />';
                $out .= '<br></div>';
            }
        } else {
            $out .= $langs->trans("NoAttachedFiles") . '<br>';
        }
        $out .= '<input type="file" class="flat" id="addedfile" name="addedfile[]" value="' . $langs->trans("Upload") . '" multiple />';
        $out .= ' ';
        $out .= '<input class="button" type="submit" id="addfile' . $addfileaction . '" name="' . $addfileaction . '" value="' . $langs->trans("MailingAddFile") . '" />';
        $out .= "</td></tr>\n";

        $out .= '</table>' . "\n";

        if ($this->withform == 1 || $this->withform == -1) {
            $out .= '<br><div class="center">';
            $out .= '<input class="button" type="submit" id="addmessage" name="addmessage" value="' . $langs->trans("RequestManagerAddMessage") . '"';
            // Add a javascript test to avoid to forget to submit file before sending email
            if ($conf->use_javascript_ajax) {
                $out .= ' onClick="if (document.requestmanagermessageform.addedfile.value != \'\') { alert(\'' . dol_escape_js($langs->trans("FileWasNotUploaded")) . '\'); return false; } else { return true; }"';
            }
            $out .= ' />';
            if ($this->withcancel) {
                $out .= ' &nbsp; &nbsp; ';
                $out .= '<input class="button" type="submit" id="cancel" name="cancel" value="' . $langs->trans("Cancel") . '" />';
            }
            $out .= '</div>' . "\n";
        }

        if ($this->withform == 1) $out .= '</form>' . "\n";

        $out .= <<<SCRIPT
             <script type="text/javascript" language="javascript">
                 jQuery(document).ready(function () {
                     // Disabled return keypress
                     $(document).on("keypress", '#requestmanagermessageform', function (e) {
                         var code = e.keyCode || e.which;
                         if (code == 13) {
                             e.preventDefault();
                             return false;
                         }
                     });

                     // Resize tooltip box
                     $(".classfortooltiponclick").click(function () {
                         if ($(this).attr('dolid'))
                         {
                             jQuery(".classfortooltiponclicktext").dialog({ width: 'auto', autoOpen: false });
                             obj=$("#idfortooltiponclick_"+$(this).attr('dolid'));
                             obj.dialog("open");
                         }
                     });
                 });
             </script>
SCRIPT;

        $out .= "<!-- End form message -->\n";

        return $out;
    }
}
