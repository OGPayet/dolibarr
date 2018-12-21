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
 *	\file       requestmanager/core/class/html.formrequestmanagermessage.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class with all html for request manager message
 */

dol_include_once('/advancedictionaries/class/html.formdictionary.class.php');

/**
 *	Class to manage generation of HTML components for request manager message
 *
 */
class FormRequestManagerMessage
{
    public $db;
    public $error;
    public $withform;       // 1=Include HTML form tag and show submit button, 0=Do not include form tag and submit button, -1=Do not include form tag but include submit button
    public $withcancel;
    public $param=array();
    public $trackid;

    /**
     * @var FormDictionary  Instance of the FormDictionary
     */
    public $formdictionary;

    /**
     * @var RequestManager  Instance of the RequestManager
     */
    public $requestmanager;

    /**
     * @var string  Key of the session for the path list of attached files
     */
    public $key_list_of_paths;
    /**
     * @var string  Key of the session for the name list of attached files
     */
    public $key_list_of_names;
    /**
     * @var string  Key of the session for the mime list of attached files
     */
    public $key_list_of_mimes;

    /**
     * @var array  Substitution keys
     */
    public $substit = array();

    /**
     * @var DictionaryLine[]  List of message template available
     */
    public $message_templates_list = array();
    /**
     * @var DictionaryLine[]  List of notify template available
     */
    public $notify_templates_list = array();
    /**
     * @var DictionaryLine[]  List of knowledge base available
     */
    public $knowledge_base_list = array();

    /**
     * Constructor
     *
     * @param   RequestManager          $object     Request manager object
     * @param   DoliDB                  $db         Database handler
     */
    public function __construct($db, &$object)
    {
        $this->db = $db;
        $this->withform=1;
        $this->withcancel=1;

        $this->requestmanager = $object;
        $this->formdictionary = new FormDictionary($this->db);

        $this->key_list_of_paths = "listofpaths-rm" . $this->requestmanager->id;
        $this->key_list_of_names = "listofnames-rm" . $this->requestmanager->id;
        $this->key_list_of_mimes = "listofmimes-rm" . $this->requestmanager->id;
    }

    /**
     * Clear list of attached files in send mail form (also stored in session)
     *
     * @return	void
     */
    function clear_attached_files()
    {
        global $conf, $user;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        // Set tmp user directory
        $vardir = $conf->user->dir_output . "/" . $user->id;
        $upload_dir = $vardir . '/temp/rm-' . $this->requestmanager->id . '/';
        if (is_dir($upload_dir)) dol_delete_dir_recursive($upload_dir);

        unset($_SESSION[$this->key_list_of_paths]);
        unset($_SESSION[$this->key_list_of_names]);
        unset($_SESSION[$this->key_list_of_mimes]);
    }

    /**
     * Add a file into the list of attached files (stored in SECTION array)
     *
     * @param 	string   $path   Full absolute path on filesystem of file, including file name
     * @param 	string   $file   Only filename
     * @param 	string   $type   Mime type
     * @return	void
     */
    function add_attached_files($path, $file, $type)
    {
        $listofpaths = array();
        $listofnames = array();
        $listofmimes = array();

        if (!empty($_SESSION[$this->key_list_of_paths])) $listofpaths = explode(';', $_SESSION[$this->key_list_of_paths]);
        if (!empty($_SESSION[$this->key_list_of_names])) $listofnames = explode(';', $_SESSION[$this->key_list_of_names]);
        if (!empty($_SESSION[$this->key_list_of_mimes])) $listofmimes = explode(';', $_SESSION[$this->key_list_of_mimes]);
        if (!in_array($file, $listofnames)) {
            $listofpaths[] = $path;
            $listofnames[] = $file;
            $listofmimes[] = $type;
            $_SESSION[$this->key_list_of_paths] = join(';', $listofpaths);
            $_SESSION[$this->key_list_of_names] = join(';', $listofnames);
            $_SESSION[$this->key_list_of_mimes] = join(';', $listofmimes);
        }
    }

    /**
     * Remove a file from the list of attached files (stored in SECTION array)
     *
     * @param  	string	$keytodelete     Key in file array (0, 1, 2, ...)
     * @return	void
     */
    function remove_attached_files($keytodelete)
    {
        $listofpaths=array();
        $listofnames=array();
        $listofmimes=array();

        if (! empty($_SESSION[$this->key_list_of_paths])) $listofpaths=explode(';',$_SESSION[$this->key_list_of_paths]);
        if (! empty($_SESSION[$this->key_list_of_names])) $listofnames=explode(';',$_SESSION[$this->key_list_of_names]);
        if (! empty($_SESSION[$this->key_list_of_mimes])) $listofmimes=explode(';',$_SESSION[$this->key_list_of_mimes]);
        if ($keytodelete >= 0)
        {
            unset ($listofpaths[$keytodelete]);
            unset ($listofnames[$keytodelete]);
            unset ($listofmimes[$keytodelete]);
            $_SESSION[$this->key_list_of_paths]=join(';',$listofpaths);
            $_SESSION[$this->key_list_of_names]=join(';',$listofnames);
            $_SESSION[$this->key_list_of_mimes]=join(';',$listofmimes);
        }
    }

    /**
     * Remove all file from the list of attached files (stored in SECTION array and physical files)
     *
     * @return	void
     */
    function remove_all_attached_files()
    {
        global $langs;

        $listofpaths = array();
        $listofnames = array();
        $listofmimes = array();

        if (! empty($_SESSION[$this->key_list_of_paths])) $listofpaths=explode(';',$_SESSION[$this->key_list_of_paths]);
        if (! empty($_SESSION[$this->key_list_of_names])) $listofnames=explode(';',$_SESSION[$this->key_list_of_names]);
        if (! empty($_SESSION[$this->key_list_of_mimes])) $listofmimes=explode(';',$_SESSION[$this->key_list_of_mimes]);

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        foreach ($listofpaths as $key => $value) {
            $pathtodelete = $value;
            $filetodelete = $listofnames[$key];
            $result = dol_delete_file($pathtodelete, 1); // Delete uploded Files

            $langs->load("other");
            setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');

            $this->remove_attached_files($key); // Update Session
        }
    }

    /**
     * Return list of attached files (stored in SECTION array)
     *
     * @return	array       array('paths'=> ,'names'=>, 'mimes'=> )
     */
    function get_attached_files()
    {
        $listofpaths=array();
        $listofnames=array();
        $listofmimes=array();

        if (! empty($_SESSION[$this->key_list_of_paths])) $listofpaths=explode(';',$_SESSION[$this->key_list_of_paths]);
        if (! empty($_SESSION[$this->key_list_of_names])) $listofnames=explode(';',$_SESSION[$this->key_list_of_names]);
        if (! empty($_SESSION[$this->key_list_of_mimes])) $listofmimes=explode(';',$_SESSION[$this->key_list_of_mimes]);
        return array('paths'=>$listofpaths, 'names'=>$listofnames, 'mimes'=>$listofmimes);
    }

    /**
     *  Output html form to send a message
     *
     * @param   string      $addfileaction      Name of action when posting file attachments
     * @param   string      $removefileaction   Name of action when removing file attachments
     * @return  string                          HTML string with form to send a message
     */
    function get_message_form($addfileaction='addfile', $removefileaction='removefile')
    {
        global $conf, $langs, $user, $hookmanager, $form;

        dol_include_once('/requestmanager/class/requestmanagermessage.class.php');

        if (!is_object($form)) $form = new Form($this->db);

        $langs->load("other");

        $hookmanager->initHooks(array('requestmanagerformmessage'));

        $parameters = array(
            'addfileaction' => &$addfileaction,
            'removefileaction' => &$removefileaction,
        );
        $reshook = $hookmanager->executeHooks('getRequestManagerMessageForm', $parameters, $this);
        if (!empty($reshook)) {
            return $hookmanager->resPrint;
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

        // Select template
        //--------------------------
        $out .= '<div class="center" style="padding: 0px 0 12px 0">' . "\n";
        if (count($modelmessage_array) > 0) {
            $out .= $langs->trans('RequestManagerSelectTemplate') . ': ' . $form->selectarray('modelmessageselected', $modelmessage_array, 0, 1);
        } else {
            // Do not put disabled on option, it is already on select and it makes chrome crazy.
            $out .= $langs->trans('RequestManagerSelectTemplate') . ': <select name="modelmessageselected" disabled="disabled"><option value="none">' . $langs->trans("RequestManagerNoMessageTemplateDefined") . '</option></select>';
        }
        if ($user->admin) $out .= '&nbsp;' . info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv('Setup') . ' - ' . $langs->transnoentitiesnoconv('Module163018Name')), 1);
        $out .= ' &nbsp; ';
        $out .= '<input class="button" type="submit" value="' . $langs->trans('Apply') . '" name="modelselected" id="modelselected"' . (count($modelmessage_array) > 0 ? '' : ' disabled="disabled"') . '>';
        $out .= '</div>';

        $out .= '<table class="border" width="100%">' . "\n";

        // Message type
        //-----------------
        $message_type_out = RequestManagerMessage::MESSAGE_TYPE_OUT;
        $message_type_private = RequestManagerMessage::MESSAGE_TYPE_PRIVATE;
        $message_type_in = RequestManagerMessage::MESSAGE_TYPE_IN;
        $message_type = GETPOST('message_type', 'alpha', 2);
        $out .= '<tr>';
        $out .= '<td class="fieldrequired" width="180">' . $langs->trans("RequestManagerMessageType") . '</td>';
        $out .= '<td>';
        $out .= '<input type="radio" id="message_type_out" name="message_type" value="' . RequestManagerMessage::MESSAGE_TYPE_OUT . '"' . ($message_type != RequestManagerMessage::MESSAGE_TYPE_PRIVATE && $message_type != RequestManagerMessage::MESSAGE_TYPE_IN ? ' checked="checked"' : '') . '/>';
        $out .= '&nbsp;<label for="message_type_out">' . $langs->trans("RequestManagerMessageTypeOut") . '&nbsp;' . img_help(0, $langs->trans("RequestManagerMessageTypeOutHelp")) . '</label>';
        $out .= ' &nbsp; ';
        $out .= '<input type="radio" id="message_type_private" name="message_type" value="' . RequestManagerMessage::MESSAGE_TYPE_PRIVATE . '"' . ($message_type == RequestManagerMessage::MESSAGE_TYPE_PRIVATE ? ' checked="checked"' : '') . '/>';
        $out .= '&nbsp;<label for="message_type_private">' . $langs->trans("RequestManagerMessageTypePrivate") . '&nbsp;' . img_help(0, $langs->trans("RequestManagerMessageTypePrivateHelp")) . '</label>';
        $out .= ' &nbsp; ';
        $out .= '<input type="radio" id="message_type_in" name="message_type" value="' . RequestManagerMessage::MESSAGE_TYPE_IN . '"' . ($message_type == RequestManagerMessage::MESSAGE_TYPE_IN ? ' checked="checked"' : '') . '/>';
        $out .= '&nbsp;<label for="message_type_in">' . $langs->trans("RequestManagerMessageTypeIn") . '&nbsp;' . img_help(0, $langs->trans("RequestManagerMessageTypeInHelp")) . '</label>';
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
                                $('td#subject_label').addClass('fieldrequired');
                                $('input#subject').prop('disabled', false);
                                break;
                            case $message_type_private:
                                $('input#notify_requesters').prop('disabled', true);
                                $('input#notify_watchers').prop('disabled', true);
                                $('td#subject_label').removeClass('fieldrequired');
                                $('input#subject').prop('disabled', true);
                                break;
                            case $message_type_in:
                                $('input#notify_requesters').prop('disabled', true);
                                $('input#notify_watchers').prop('disabled', true);
                                $('td#subject_label').removeClass('fieldrequired');
                                $('input#subject').prop('disabled', false);
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
        $out .= '<input type="checkbox" id="notify_assigned" name="notify_assigned" value="1"' . (!empty($notify_assigned) ? ' checked="checked"' : '') . ' />';
        $out .= '&nbsp;<label for="notify_assigned">' . $langs->trans("RequestManagerAssigned") . '</label>';
        $out .= ' &nbsp; ';
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
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $requestmanagermessage); // Note that $action and $object may have been modified by hook
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
            $default_tags = $eventconfidentiality->getDefaultTags($object->elementtype);
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
                $tmp = '<tr id="' . $line->id . '">';
                $tmp .= '<td>' . $line->fields['label'] . '</td>';
                $tmp .= '<td>';
                $tmp .= '<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_VISIBLE . '" name="ec_mode_' . $line->id . '" value="0"' . ($mode == EventConfidentiality::MODE_VISIBLE ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_VISIBLE . '">' . $langs->trans('EventConfidentialityModeVisible') . '</label>';
                $tmp .= '&nbsp;<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_BLURRED . '" name="ec_mode_' . $line->id . '" value="1"' . ($mode == EventConfidentiality::MODE_BLURRED ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_BLURRED . '">' . $langs->trans('EventConfidentialityModeBlurred') . '</label>';
                $tmp .= '&nbsp;<input type="radio" id="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_HIDDEN . '" name="ec_mode_' . $line->id . '" value="2"' . ($mode == EventConfidentiality::MODE_HIDDEN ? ' checked="checked"' : "") . '><label for="ec_mode_' . $line->id . '_' . EventConfidentiality::MODE_HIDDEN . '">' . $langs->trans('EventConfidentialityModeHidden') . '</label>';
                $tmp .= '</td>';
                $tmp .= '</tr>';
                if (empty($line->fields['external'])) {
                    $internal_tags .= $tmp;
                } else {
                    $external_tags .= $tmp;
                }
            }
            // Internal tags
            $out .= '<tr>';
            $out .= '<td class="nowrap" class="titlefield">' . $langs->trans("EventConfidentialityTagInterneLabel") . '</td>';
            $out .= '<td colspan="3"><table class="noborder margintable centpercent">';
            $out .= '<tr><th class="liste_titre" width="40%">Tags</th><th class="liste_titre">Mode</th></tr>';
            $out .= $internal_tags;
            $out .= '</table></td>';
            $out .= '</tr>';
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
                RequestManagerMessage::MESSAGE_TYPE_IN => 'AC_RM_IN',
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
                    })
                } else {
                    $.map($('[id^="ec_mode_"][id$="_$hidden_mode"]'), function(val, idx) {
                        $(val).prop('checked', true);
                    });
                }
            }
        });
    </script>
SCRIPT;
        }

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

        // Substitution help
        //-----------------------
        // List of help for fields
        dol_include_once('/requestmanager/class/requestmanagersubstitutes.class.php');
        $subsituteKeys = RequestManagerSubstitutes::getAvailableSubstitutesKeyFromRequest($this->db, 1, $this->requestmanager);
        $this->substit = RequestManagerSubstitutes::setSubstitutesFromRequest($this->db, $this->requestmanager);
        $helpSubstitution = $langs->trans("AvailableVariables") . ':<br>';
        $helpSubstitution .= "<div style='display: block; overflow: auto; height: 700px;'><table class='nobordernopadding'>";
        foreach ($subsituteKeys as $key => $label) {
            $helpSubstitution .= "<tr><td><span style='margin-right: 10px;'>" . $key . ' :</span></td><td>' . $label . '</td></tr>';
        }
        $helpSubstitution .= '</table></div>';

        // Get knowledge base list
        //--------------------------
        $this->requestmanager->fetch_tags();
        $result = $this->fetchAllKnowledgeBase($this->requestmanager->fk_type, $this->requestmanager->tag_ids);
        if ($result < 0) {
            setEventMessages($this->error, null, 'errors');
        }
        $modelknowledgebase_array = array();
        foreach ($this->knowledge_base_list as $line) {
            $modelknowledgebase_array[$line->id] = $line->fields['title'];
        }

        // Get default message template
        //----------------------------------
        $knowledgebase_ids = !empty($this->param["knowledgebase_ids"]) ? $this->param["knowledgebase_ids"] : array();

        // Select knowledge base
        //------------------------
        $out .= '<tr>';
        $out .= '<td width="180">' . $langs->trans("RequestManagerMessageKnowledgeBase") . '</td>';
        $out .= '<td>';
        if (count($modelknowledgebase_array) > 0) {
            $out .= $form->multiselectarray('knowledgebaseselected', $modelknowledgebase_array, $knowledgebase_ids, '', 0, ' minwidth300');
        } else {
            // Do not put disabled on option, it is already on select and it makes chrome crazy.
            $out .= '<select name="knowledgebaseselected" disabled="disabled"><option value="none">' . $langs->trans("RequestManagerNoKnowledgeBaseDefined") . '</option></select>';
        }
        if ($user->admin) $out .= '&nbsp;' .info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv('Setup') . ' - ' . $langs->transnoentitiesnoconv('Module163018Name')), 1);
        $out .= ' &nbsp; ';
        $out .= '<input class="button" type="submit" value="' . $langs->trans('RequestManagerAddKnowledgeBaseDescriptions') . '" name="addknowledgebasedescription" id="addknowledgebasedescription"' . (is_array($knowledgebase_ids) && count($knowledgebase_ids) > 0 ? '' : ' disabled="disabled"') . '>';
        $out .= "</td></tr>\n";

        // Subject
        //-----------------
        $default_subject = !empty($default_message['subject']) ? $default_message['subject'] : '';
        $subject = !empty($default_subject) ? $default_subject : GETPOST('subject', 'alpha', 2);
        $subject = make_substitutions($subject, $this->substit);

        $out .= '<tr>';
        $out .= '<td width="180" valign="top" id="subject_label">' . $langs->trans("RequestManagerSubject");
        $out .= '&nbsp;' . $form->textwithpicto('', $helpSubstitution, 1, 'help', '', 0, 2, 'substitution');
        $out .= '</td>';
        $out .= '<td>';
        $out .= '<input type="text" id="subject" name="subject" style="width: 95%;" max="255" value="'.dol_escape_htmltag($subject).'">';
        $out .= "</td></tr>\n";

        // Message
        //-----------------
        $default_body = !empty($default_message['message']) ? $default_message['message'] : '';
        $message = !empty($default_body) ? $default_body : GETPOST('message', 'alpha', 2);
        if (GETPOST('addknowledgebasedescription', 'alpha') != '') {
            foreach ($knowledgebase_ids as $knowledge_base_id) {
                $knowledge_base_selected = $this->knowledge_base_list[$knowledge_base_id]->fields;
                if (!empty($knowledge_base_selected['description'])) {
                    $message = dol_concatdesc($message, "\n" . $knowledge_base_selected['title'] . ' :');
                    $message = dol_concatdesc($message, $knowledge_base_selected['description']);
                }
            }
        }
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

                    // Resize tooltip box
                    $("#knowledgebaseselected").on('change', function () {
                        $('#addknowledgebasedescription').prop('disabled', $(this).val().length == 0)
                    });
                });
            </script>
SCRIPT;

        $out .= "<!-- End form message -->\n";

        return $out;
    }

    /**
	 *  Load message template
	 *
     * @param	int			$id				Id template
     * @return	array		                Template infos
	 */
	private function getMessageTemplate($id)
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagermessagetemplate');

        $template = $dictionaryLine->fields;

        $result = $dictionaryLine->fetch($id);
        if ($result < 0) {
            $this->error = $dictionaryLine->errorsToString();
            return $template;
        }

        $template = $dictionaryLine->fields;

        return $template;
    }

    /**
	 *  Load notify template
	 *
     * @param	int			$id				Id template
     * @return	array		                Template infos
	 */
	private function getNotifyTemplate($id)
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionaryLine = Dictionary::getDictionaryLine($this->db, 'requestmanager', 'requestmanagernotifytemplate');

        $template = $dictionaryLine->fields;

        $result = $dictionaryLine->fetch($id);
        if ($result < 0) {
            $this->error = $dictionaryLine->errorsToString();
            return $template;
        }

        $template = $dictionaryLine->fields;

        return $template;
    }

	/**
	 *  Load all active message templates
	 *
     * @param	int		    $request_type	Id of the request type
	 * @return	int		                    <0 if KO, nb of records found if OK
	 */
	public function fetchAllMessageTemplate($request_type)
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagermessagetemplate');

        $this->message_templates_list = array();

        $lines = $dictionary->fetch_lines(1, array('request_type' => array($request_type)), array('position' => 'ASC'), 0, 0, false, true);
        if ($lines < 0) {
            $this->error = $dictionary->errorsToString();
            return -1;
        }

        $this->message_templates_list = $lines;

        return count($lines);
    }

    /**
	 *  Load all active notify templates
	 *
     * @param	int		    $request_type	Id of the request type
     * @param	string		$type_template	Get message for key module
	 * @return	int		                    <0 if KO, nb of records found if OK
	 */
	public function fetchAllNotifyTemplate($request_type, $type_template='message_template_user')
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagernotifytemplate');

        $this->notify_templates_list = array();

        $lines = $dictionary->fetch_lines(1, array('template_type' => array($type_template), 'request_type' => array($request_type)), array('position' => 'ASC'), 0, 0, false, true);
        if ($lines < 0) {
            $this->error = $dictionary->errorsToString();
            return -1;
        }

        $this->notify_templates_list = $lines;

        return count($lines);
    }

    /**
	 *  Load all active knowledge base
	 *
     * @param	int		    $request_type	Id of the request type
     * @param	array		$tag_ids	    List of tag/category produit ID
	 * @return	int		                    <0 if KO, nb of records found if OK
	 */
	public function fetchAllKnowledgeBase($request_type, $tag_ids)
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagerknowledgebase');

        $this->knowledge_base_list = array();
        if (!is_array($tag_ids)) $tag_ids = array();

        $lines = $dictionary->fetch_lines(1, array('request_type' => array($request_type)), array('position' => 'ASC'), 0, 0, false, true);
        if ($lines < 0) {
            $this->error = $dictionary->errorsToString();
            return -1;
        }

        foreach ($lines as $line) {
            if (!empty($line->fields['categorie'])) {
                $categories = array_filter(array_map('trim', explode(',', $line->fields['categorie'])), 'strlen');
                $found = false;
                foreach ($tag_ids as $tag_id) {
                    if (in_array($tag_id, $categories)) {
                        $found = true;
                        break;
                    }
                }
            } else {
                $found = true;
            }

            if ($found) {
                $this->knowledge_base_list[$line->id] = $line;
            }
        }

        return count($lines);
    }
}
