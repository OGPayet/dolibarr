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

    /**
     * @var FormDictionary  Instance of the FormDictionary
     */
    public $formdictionary;

    /**
     * @var RequestManager  Instance of the RequestManager
     */
    public $object;

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
     * @var DictionaryLine[]  List of template available
     */
    public $templates_list = array();

    /**
     * Constructor
     *
     * @param   RequestManager  $object     Request manager object
     * @param   DoliDB          $db         Database handler
     */
    public function __construct($db, &$object)
    {
        $this->db = $db;
        $this->requestmanager = $object;
        $this->formdictionary = new FormDictionary($this->db);

        $this->key_list_of_paths = "requestmanagerlop" . $this->requestmanager->ref_ext;
        $this->key_list_of_names = "requestmanagerlon" . $this->requestmanager->ref_ext;
        $this->key_list_of_mimes = "requestmanagerlom" . $this->requestmanager->ref_ext;
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
        $upload_dir = $vardir . '/temp/' . $this->requestmanager->ref_ext . '/';
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
     * @param  	ActionComm	$actioncomm         Event message
     * @param  	string	    $actionurl          Key in file array (0, 1, 2, ...)
     * @param  	string	    $type_template      Key in file array (0, 1, 2, ...)
     * @param  	int	        $template_id        Key in file array (0, 1, 2, ...)
     * @param  	array	    $parameters         Key in file array (0, 1, 2, ...)
     * @param  	string	    $addfileaction      Key in file array (0, 1, 2, ...)
     * @param  	string	    $removefileaction   Key in file array (0, 1, 2, ...)
     * @return  string                          HTML string with form to send a message
     */
    function get_message_form($actioncomm, $actionurl, $type_template='message_template_user', $template_id=0, $parameters=array(), $addfileaction='addfile', $removefileaction='removefile')
    {
        global $langs, $user, $hookmanager, $form;

        if (!is_object($form)) $form = new Form($this->db);

        $langs->load("other");

        $hookmanager->initHooks(array('formrequestmanagermessage'));

        $parameters = array(
            'actioncomm' => &$actioncomm,
            'actionurl' => &$actionurl,
            'type_template' => &$type_template,
            'template_id' => &$template_id,
            'parameters' => $parameters,
            'addfileaction' => &$addfileaction,
            'removefileaction' => &$removefileaction,
        );
        $reshook = $hookmanager->executeHooks('getFormMail', $parameters, $this);
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

        $out .= "\n" . '<!-- Begin form mail --><div id="requestmanagermessageformdiv"></div>' . "\n";
        $out .= '<form method="POST" name="requestmanagermessageform" id="requestmanagermessageform" enctype="multipart/form-data" action="' . $actionurl . '#formrequestmanagermessage">' . "\n";
        //$out .= '<input style="display:none" type="submit" id="addmessage" name="addmessage">';
        $out .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
        $out .= '<a id="formrequestmanagermessage" name="formrequestmanagermessage"></a>';
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $arrKey => $arrValue) {
                    $out .= '<input type="hidden" id="' . $arrKey . '" name="' . $arrKey . '" value="' . $arrValue . '" />' . "\n";
                }
            } else {
                $out .= '<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $value . '" />' . "\n";
            }
        }

        $out .= '<div class="center" style="padding: 0px 0 12px 0">' . "\n";
        // Select knowledge base
        //----------------------
        $knowledgeBaseSelectedId  = strlen(GETPOST('knowledge_base_selected')) ? GETPOST('knowledge_base_selected', 'int') : -1;
        $knowledgeBaseOrderedList = $this->requestmanager->fetchAllDictionaryLinesForKnowledgeBaseAndOrderBy(array('nb_categorie' => SORT_DESC));
        $knowledgeBaseSelectList  = array_column($knowledgeBaseOrderedList, 'title');
        $out .= $langs->trans('RequestManagerSelectKnowledgeBase') . ' : ';
        if ($nbKnowledgeBase = count($knowledgeBaseSelectList) > 0) {
            $out .= '<input type="hidden" name="id_knowledge_base" value="' . $knowledgeBaseOrderedList[$knowledgeBaseSelectedId]['id'] . '" />' . "\n";
            $out .= $form->selectarray('knowledge_base_selected', $knowledgeBaseSelectList, $knowledgeBaseSelectedId, 1);
        } else {
            $out .= '<input type="hidden" name="id_knowledge_base" value="-1" />' . "\n";
            $out .= '<select name="modelmailselected" disabled="disabled"><option value="-1">' . $langs->trans("RequestManagerNoTemplateDefined") . '</option></select>';
        }
        $out .= '<script type="text/javascript" language="javascript">';
        $out .= 'jQuery(document).ready(function() {';
        $out .= '   jQuery("#knowledge_base_selected").change(function() {';
        $out .= '       jQuery("#message_template_selected").val("-1");';
        $out .= '   });';
        $out .= '});';
        $out .= '</script>' . "\n";

        // Select template
        //-----------------
        $result = $this->fetchAllTemplate($this->requestmanager->fk_type, $type_template);
        if ($result < 0) {
            setEventMessages($this->error, $this->errors, 'errors');
        }
        $template_array = array();
        foreach ($this->templates_list as $line) {
            $template_array[$line->id] = $line->fields['label'];
        }
        $out .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
        $out .= $langs->trans('RequestManagerSelectTemplate') . ' : ';
        if ($nTemplate = count($template_array) > 0) {
            $out .= $form->selectarray('message_template_selected', $template_array, $template_id, 1);
        } else {
            $out .= '<select name="modelmailselected" disabled="disabled"><option value="-1">' . $langs->trans("RequestManagerNoTemplateDefined") . '</option></select>';
        }
        if ($user->admin) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFrom", $langs->transnoentitiesnoconv('Setup') . ' - ' . $langs->transnoentitiesnoconv('Module163018Name')), 1);
        $out .= '<script type="text/javascript" language="javascript">';
        $out .= 'jQuery(document).ready(function() {';
        $out .= '   jQuery("#message_template_selected").change(function() {';
        $out .= '       jQuery("#knowledge_base_selected").val("-1");';
        $out .= '   });';
        $out .= '});';
        $out .= '</script>' . "\n";

        // btn apply
        $out .= '&nbsp;&nbsp;';
        $out .= '<input class="button" type="button" value="' . $langs->trans('Apply') . '" id="btn_apply" name="btn_apply" ' . ($nbKnowledgeBase || $nTemplate > 0 ? '' : ' disabled="disabled"') . '>';
        $out .= ' &nbsp; ';
        $out .= '</div>';
        $out .= '<script type="text/javascript" language="javascript">';
        $out .= 'jQuery(document).ready(function() {';
        $out .= '    jQuery("#btn_apply").click(function() {';
        $out .= '        jQuery("#action").val("premessage");';
        $out .= '        if (jQuery("#knowledge_base_selected").val()!==undefined && jQuery("#knowledge_base_selected").val()>=0) {';
        $out .= '           jQuery("#actioncomm").val("knowledge_base_apply");';
        $out .= '        } else {';
        $out .= '           jQuery("#actioncomm").val("message_template_apply");';
        $out .= '        }';
        $out .= '        jQuery("#requestmanagermessageform").submit();';
        $out .= '    });';
        $out .= '})';
        $out .= '</script>' . "\n";

        $out .= '<table class="border" width="100%">' . "\n";

        // Notification by mail
        $messageNotifyByMail = GETPOST('message_notify_by_mail', 'int', 2)?1:0;
        $messageNotifyByMailChecked = '';
        if ($messageNotifyByMail) $messageNotifyByMailChecked = ' checked="checked"';
        $out .= '<tr>';
        $out .= '<td class="fieldrequired" colspan="2">';
        $out .= '<input type="checkbox" id="message_notify_by_mail" name="message_notify_by_mail" value="1"' . $messageNotifyByMailChecked .' /> ' . $langs->trans("RequestManagerMessageNotifyByMail");
        $out .= "</td></tr>\n";
        $out .= '<script type="text/javascript" language="javascript">';
        $out .= 'jQuery(document).ready(function() {';
        $out .= '   jQuery("#message_notify_by_mail").change(function() {';
        $out .= '       if(jQuery(this).is(":checked")) {';
        $out .= '           jQuery("#message_direction2").prop("checked", true);';
        $out .= '           jQuery(".cb_message_direction").prop("disabled", true);';
        $out .= '       } else {';
        $out .= '           jQuery(".cb_message_direction").prop("disabled", false);';
        $out .= '       }';
        $out .= '   });';
        $out .= '});';
        $out .= '</script>' . "\n";

        // Direction
        //-----------------
        dol_include_once('/requestmanager/class/requestmanagernotification.class.php');
        $messageDirection = GETPOST('message_direction', 'int', 2)?intval(GETPOST('message_direction', 'int', 2)):RequestManagerNotification::getMessageDirectionIdDefault();
        $messageDirectionCheckedList = array(RequestManagerNotification::MESSAGE_DIRECTION_ID_IN => '', RequestManagerNotification::MESSAGE_DIRECTION_ID_OUT => '');
        $messageDirectionCheckedList[$messageDirection] .= ' checked="checked"';
        $out .= '<tr>';
        $out .= '<td class="fieldrequired" width="180">' . $langs->trans("RequestManagerMessageDirection") . '</td>';
        $out .= '<td>';
        $out .= '<input type="radio" id="message_direction1" class="cb_message_direction" name="message_direction" value="' . RequestManagerNotification::MESSAGE_DIRECTION_ID_IN . '"' . $messageDirectionCheckedList[RequestManagerNotification::MESSAGE_DIRECTION_ID_IN] . '/> ' . $langs->trans("RequestManagerMessageDirectionIn");
        $out .= '&nbsp;&nbsp;<input type="radio" id="message_direction2" class="cb_message_direction" name="message_direction" value="' . RequestManagerNotification::MESSAGE_DIRECTION_ID_OUT . '"' . $messageDirectionCheckedList[RequestManagerNotification::MESSAGE_DIRECTION_ID_OUT] . '/> ' . $langs->trans("RequestManagerMessageDirectionOut");
        $out .= "</td></tr>\n";

        // Other attributes
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        $extralabels = $extrafields->fetch_name_optionals_label($this->requestmanager->table_element.'_message');
        $parameters = array(
            'actioncomm' => &$actioncomm,
            'actionurl' => &$actionurl,
            'type_template' => &$type_template,
            'template_id' => &$template_id,
            'parameters' => &$parameters,
            'addfileaction' => &$addfileaction,
            'removefileaction' => &$removefileaction,
        );
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $this); // Note that $action and $object may have been modified by hook
        $out .= $hookmanager->resPrint;
	if (empty($reshook) && ! empty($extrafields->attribute_label)) {
            $out .= $actioncomm->showOptionals($extrafields, 'edit');
	}

        // Get message template
        $actionCommPost = GETPOST('actioncomm')?GETPOST('actioncomm'):'';
        $default_message['subject'] = '';
        $default_message['boby']    = '';
        if ($knowledgeBaseSelectedId >= 0) {
            $default_message['subject'] = $knowledgeBaseOrderedList[$knowledgeBaseSelectedId]['title'];
            $default_message['boby']    = $knowledgeBaseOrderedList[$knowledgeBaseSelectedId]['description'];
        } else if ($template_id > 0) {
            $default_message = $this->getEMailTemplate($template_id);
        }
        $this->setSubstitFromObject($this->requestmanager);

        // Subject
        //-----------------
        if ($actionCommPost == 'knowledge_base_apply') {
            $subject = GETPOST('message_subject', 'alpha', 2) ? GETPOST('message_subject', 'alpha', 2) : '';
            if (!$subject && $default_message['subject']) {
                $subject = make_substitutions($default_message['subject'], $this->substit);
            }
        } else {
            if (!empty($default_message['subject'])) {
                $subject = make_substitutions($default_message['subject'], $this->substit);
            } else {
                $subject = GETPOST('message_subject', 'alpha', 2) ? GETPOST('message_subject', 'alpha', 2) : '';
            }
        }
        $out .= '<tr>';
        $out .= '<td class="fieldrequired" width="180">' . $langs->trans("RequestManagerMessageSubject") . '</td>';
        $out .= '<td>';
        $out .= '<input type="text" class="quatrevingtpercent" id="message_subject" name="message_subject" value="' . $subject . '" />';
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
        $out .= '});';
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

        // Message
        //-----------------
        if ($actionCommPost == 'knowledge_base_apply') {
            $message_body = GETPOST('message_body', 'alpha', 2) ? GETPOST('message_body', 'alpha', 2) : '';
            if ($default_message['boby']) {
                if (!empty($message_body)) {
                    $message_body .= '<br />';
                }
                $message_body .= make_substitutions($default_message['boby'], $this->substit);
            }
        } else {
            if (!empty($default_message['boby'])) {
                $message_body = make_substitutions($default_message['boby'], $this->substit);
            } else {
                $message_body = GETPOST('message_body', 'alpha', 2) ? GETPOST('message_body', 'alpha', 2) : '';
            }
        }
        if (!empty($message_body)) {
            // Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
            $message_body = preg_replace("/^(<br>)+/", "", $message_body);
            $message_body = preg_replace("/^\n+/", "", $message_body);
        }
        $out .= '<tr>';
        $out .= '<td class="fieldrequired" width="180" valign="top">' . $langs->trans("RequestManagerMessage") . '</td>';
        $out .= '<td>';
        // Editor wysiwyg
        require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        $doleditor = new DolEditor('message_body', $message_body, '', 280, 'dolibarr_notes', 'In', true, true, 1, 8, '95%');
        $out .= $doleditor->Create(1);
        $out .= "</td></tr>\n";

        $out .= '</table>' . "\n";

        $out .= '<br><div class="center">';
        $out .= '<input class="button" type="submit" id="addmessage" name="addmessage" value="' . $langs->trans("RequestManagerAddMessage") . '"';
        // Add a javascript test to avoid to forget to submit file before sending message
        $out .= ' onClick="if (document.requestmanagermessageform.addedfile.value != \'\') { alert(\'' . dol_escape_js($langs->trans("FileWasNotUploaded")) . '\'); return false; } else { return true; }"';
        $out .= ' />';
        $out .= ' &nbsp; &nbsp; ';
        $out .= '<input class="button" type="submit" id="cancel" name="cancel" value="' . $langs->trans("Cancel") . '" />';
        $out .= '</div>' . "\n";
        $out .= '<script type="text/javascript" language="javascript">';
        $out .= 'jQuery(document).ready(function () {';
        $out .= '    jQuery("#addmessage").click(function() {';
        $out .= '        jQuery("#action").val("addmessage");';
        $out .= '        jQuery("#actioncomm").val("message_add_validate");';
        $out .= '        jQuery("#requestmanagermessageform").submit();';
        $out .= '    });';
        $out .= '});';
        $out .= '</script>' . "\n";

        $out .= '</form>' . "\n";

        // Disable enter key
        $out .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        jQuery(document).ready(function () {
            $(document).on("keypress", '#requestmanagermessageform', function (e) {
                var code = e.keyCode || e.which;
                if (code == 13) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
SCRIPT;

        $out .= "<!-- End form message -->\n";

        return $out;
    }

    /**
	 *  Load template
	 *
     * @param	int			$id				Id template
     * @return	array		                Template infos
	 */
	private function getEMailTemplate($id)
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
	 *  Load all active templates
	 *
     * @param	int		    $request_type	Id of the request type
     * @param	string		$type_template	Get message for key module
	 * @return	int		                    <0 if KO, nb of records found if OK
	 */
	public function fetchAllTemplate($request_type, $type_template='message_template_user')
    {
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagermessagetemplate');

        $this->templates_list = array();

        $lines = $dictionary->fetch_lines(1, array('template_type' => array($type_template), 'request_type' => array($request_type)), array('position' => 'ASC'), 0, 0, false, true);
        if ($lines < 0) {
            $this->error = $dictionary->errorsToString();
            return -1;
        }

        $this->templates_list = $lines;

        return count($lines);
    }

	/**
	 * Set substit array from object
	 *
	 * @param	RequestManager	   $object		  Request manager object
	 * @return	void
	 */
	function setSubstitFromObject($object)
    {
        // Create dinamic tags for __EXTRAFIELD_FIELD__
        $extrafields = new ExtraFields($this->db);
        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
        $object->fetch_optionals($object->id, $extralabels);

        $this->substit = self::getAvailableSubstitKey($object, 0);
    }

	/**
	 * Get list of substitution keys available for message.
	 * This include the complete_substitutions_array and the getCommonSubstitutionArray().
	 *
     * @param   RequestManager  $object     Request manager object
     * @param   int             $keyonly    Return only key, dont load infos
	 * @return	array                       Array of substitution values for message.
	 */
	static function getAvailableSubstitKey($object, $keyonly=1)
    {
        global $langs;

        $vars = array(
            // Request
            '__ID__' => $object->id,
            '__REF__' => $object->ref,
            '__REF_EXT__' => $object->ref_ext,
            '__THIRDPARTY_ID__' => $object->socid,
            '__THIRDPARTY_NAME__' => '',
            '__LABEL__' => $object->label,
            '__DESCRIPTION__' => $object->description,
            '__TYPE_ID__' => $object->fk_type,
            '__TYPE_CODE__' => '',
            '__TYPE_LABEL__' => '',
            '__CATEGORY_ID__' => $object->fk_category,
            '__CATEGORY_CODE__' => '',
            '__CATEGORY_LABEL__' => '',
            '__SOURCE_ID__' => $object->fk_source,
            '__SOURCE_CODE__' => '',
            '__SOURCE_LABEL__' => '',
            '__URGENCY_ID__' => $object->fk_urgency,
            '__URGENCY_CODE__' => '',
            '__URGENCY_LABEL__' => '',
            '__IMPACT_ID__' => $object->fk_impact,
            '__IMPACT_CODE__' => '',
            '__IMPACT_LABEL__' => '',
            '__PRIORITY_ID__' => $object->fk_priority,
            '__PRIORITY_CODE__' => '',
            '__PRIORITY_LABEL__' => '',
            '__NOTIFY_ASSIGNED_BY_EMAIL__' => $object->notify_assigned_by_email,
            '__ASSIGNED_USER_ID__' => $object->assigned_user_id,
            '__ASSIGNED_USER_NAME__' => '',
            '__ASSIGNED_USERGROUP_ID__' => $object->assigned_usergroup_id,
            '__ASSIGNED_USERGROUP_NAME__' => '',
            '__NOTIFY_REQUESTER_BY_EMAIL__' => $object->notify_requester_by_email,
            '__REQUESTERS_NAME__' => '',
            '__NOTIFY_WATCHER_BY_EMAIL__' => $object->notify_watcher_by_email,
            '__WATCHERS_NAME__' => '',
            //'__DURATION__' => '',
            '__DATE_DEADLINE__' => '',
            '__DATE_RESOLVED__' => '',
            '__DATE_CLOTURE__' => '',
            '__USER_RESOLVED_ID__' => $object->user_resolved_id,
            '__USER_RESOLVED_NAME__' => '',
            '__USER_CLOTURE_ID__' => $object->user_cloture_id,
            '__USER_CLOTURE_NAME__' => '',
            '__STATUT__' => $object->statut,
            '__STATUT_LABEL__' => '',
            '__STATUT_TYPE__' => $object->statut_type,
            '__STATUT_TYPE_LABEL__' => '',
            '__DATE_CREATION__' => '',
            '__DATE_MODIFICATION__' => '',
            '__USER_CREATION_ID__' => $object->user_creation_id,
            '__USER_CREATION_NAME__' => '',
            '__USER_MODIFICATION_ID__' => $object->user_modification_id,
            '__USER_MODIFICATION_NAME__' => '',

            // Message
            '__MESSAGE_xxx__' => '',
        );

        if (!$keyonly) {
            // Request
            $vars['__THIRDPARTY_NAME__'] = '';
            $vars['__TYPE_CODE__'] = '';
            $vars['__TYPE_LABEL__'] = '';
            $vars['__CATEGORY_CODE__'] = '';
            $vars['__CATEGORY_LABEL__'] = '';
            $vars['__SOURCE_CODE__'] = '';
            $vars['__SOURCE_LABEL__'] = '';
            $vars['__URGENCY_CODE__'] = '';
            $vars['__URGENCY_LABEL__'] = '';
            $vars['__IMPACT_CODE__'] = '';
            $vars['__IMPACT_LABEL__'] = '';
            $vars['__PRIORITY_CODE__'] = '';
            $vars['__PRIORITY_LABEL__'] = '';
            $vars['__ASSIGNED_USER_NAME__'] = '';
            $vars['__ASSIGNED_USERGROUP_NAME__'] = '';
            $vars['__REQUESTERS_NAME__'] = '';
            $vars['__WATCHERS_NAME__'] = '';
            //$vars['__DURATION__'] = '';
            $vars['__DATE_DEADLINE__'] = '';
            $vars['__DATE_RESOLVED__'] = '';
            $vars['__DATE_CLOTURE__'] = '';
            $vars['__USER_RESOLVED_NAME__'] = '';
            $vars['__USER_CLOTURE_NAME__'] = '';
            $vars['__STATUT_LABEL__'] = '';
            $vars['__STATUT_TYPE_LABEL__'] = '';
            $vars['__DATE_CREATION__'] = '';
            $vars['__DATE_MODIFICATION__'] = '';
            $vars['__USER_CREATION_NAME__'] = '';
            $vars['__USER_MODIFICATION_NAME__'] = '';
        } else {
            // Mail
            $substitutList = self::getAvailableSubstitKeyForMail();
            foreach($substitutList as $key => $value)
            {
                $vars[$key] = $value;
            }
        }

        $tmparray = getCommonSubstitutionArray($langs, 1, array('objectamount'));
        complete_substitutions_array($tmparray, $langs, $object, null, 'requestmanager_completesubstitutionarray');
        foreach ($tmparray as $key => $val) {
            $vars[$key] = $key;
        }

        // Create dynamic tags for __EXTRAFIELD_FIELD__ of message
        $extrafields = new ExtraFields($object->db);
        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element.'_message', true);
        foreach ($extrafields->attribute_label as $key => $val) {
            $substitutionarray['__MESSAGE_EXTRA_' . $key . '__'] = '';
            // For backward compatibiliy
            $substitutionarray['%MESSAGE_EXTRA_' . $key . '%'] = '';
        }

        return $vars;
    }


    /**
     * Get subtitute list for mail
     *
     * @return  array
     */
    public static function getAvailableSubstitKeyForMail()
    {
        $substitList = array(
            '__MAIL_FROM__'    => '',
            '__MAIL_TO__'      => '',
            '__MAIL_SUBJECT__' => '',
            '__MAIL_CONTENT__' => '',
            '__MAIL_CC_TO__'   => '',
            '__MAIL_BCC_TO__'  => ''
        );

        return $substitList;
    }


    /**
     * Set substitute keys for mail
     *
     * @param   $from           Sender email address
     * @param   $to             Recipient email address
     * @param   $subject        Subject of mail
     * @param   $content        [=''] Content of mail
     * @param   $ccTo           [=''] Copy carbone email address
     * @param   $bccTo          [=''] Blind copy carbone email address
     * @return  array
     */
    public static function setAvailableSubstitKeyForMail($from, $to, $subject, $content = '', $ccTo = '', $bccTo = '')
    {
        $substitList['__MAIL_FROM__']    = $from;
        $substitList['__MAIL_TO__']      = $to;
        $substitList['__MAIL_SUBJECT__'] = $subject;
        $substitList['__MAIL_CONTENT__'] = $content;
        $substitList['__MAIL_CC_TO__']   = $ccTo;
        $substitList['__MAIL_BCC_TO__']  = $bccTo;

        return $substitList;
    }
}
