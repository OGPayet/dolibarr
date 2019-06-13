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
 *	\file       requestmanager/core/class/requestmanagernotify.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class to manage notification to assigned, requesters and watchers
 */

/**
 *	Class to manage notification to assigned, requesters and watchers
 *
 */
class RequestManagerNotify
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * Const type notification
     */
    const TYPE_ASSIGNED_MODIFIED = 1;
    const TYPE_STATUS_MODIFIED = 2;
    const TYPE_MESSAGE_ADDED = 3;
    const TYPE_REQUEST_CREATED = 4;

    /**
     * Const notify to
     */
    const NOTIFY_TO_ASSIGNED = 1;
    const NOTIFY_TO_REQUESTERS = 2;
    const NOTIFY_TO_WATCHERS = 3;


    /**
     * Constructor
     *
     * @param   DoliDB      $db     Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Send notification to the assigned, requesters, watchers for a type of notification
     *
     * @param   string                      $type                       Type notification (add message, assigned modified or status modified)
     * @param   RequestManager              $requestmanager             Request manager object
     * @param   RequestManagerMessage       $requestmanagermessage      Request manager message object
     * @return  int                                                     <0 if KO, >0 if OK
     */
    public function sendNotify($type, &$requestmanager, &$requestmanagermessage=null)
    {
        global $conf, $langs, $user;

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionary = Dictionary::getDictionary($this->db, 'requestmanager', 'requestmanagernotifytemplate');

        $result = $dictionary->fetch_lines(1, array('type' => array($type), 'request_type' => array($requestmanager->fk_type)));
        if ($result < 0) {
            $this->error = $dictionary->error;
            $this->errors = $dictionary->errors;
            return -1;
        }

        // Get notification templates
        $assigned_template = null;
        $requesters_template = null;
        $watchers_template = null;
        foreach ($dictionary->lines as $line) {
            if (in_array(self::NOTIFY_TO_ASSIGNED, explode(',', $line->fields['notify_to']))) {
                $assigned_template = $line->fields;
            }
            if (in_array(self::NOTIFY_TO_REQUESTERS, explode(',',$line->fields['notify_to']))) {
                $requesters_template = $line->fields;
            }
            if (in_array(self::NOTIFY_TO_WATCHERS, explode(',',$line->fields['notify_to']))) {
                $watchers_template = $line->fields;
            }
        }

        if (isset($assigned_template) || isset($requesters_template) || isset($watchers_template)) {
            // Get substitutes values
            $substitutes_array = RequestManagerSubstitutes::setSubstitutesFromRequest($this->db, $requestmanager, 1, 1);
            if (isset($requestmanagermessage)) {
                $substitutes_message_array = RequestManagerSubstitutes::setSubstitutesFromRequestMessage($this->db, $requestmanagermessage);
                $substitutes_array = array_merge($substitutes_array, $substitutes_message_array);
            }
            // Get attached files
            $attachedfiles = is_array($requestmanagermessage->attached_files) ? $requestmanagermessage->attached_files : array('paths'=>array(), 'names'=>array(), 'mimes'=>array());

            // Send email to assigned
            if (!empty($conf->global->REQUESTMANAGER_NOTIFICATION_ASSIGNED_BY_EMAIL)) {
                if (isset($assigned_template)) {
                    if ((isset($requestmanagermessage) && !empty($requestmanagermessage->notify_assigned)) || (!isset($requestmanagermessage) && !empty($requestmanager->notify_assigned_by_email))) {
                        $emails = $this->_getAssignedEmails($requestmanager);
                        if (count($emails)) {
                            if (!empty($conf->global->REQUESTMANAGER_SPLIT_ASSIGNED_NOTIFICATION)) {
                                $sendto_list = array_chunk($emails, 1, true);
                            } else {
                                $sendto_list = array($emails);
                            }

                            $subject = make_substitutions($assigned_template['subject'], $substitutes_array);
                            $signature = $assigned_template['signature'];
                            if (empty($signature)) $signature = $conf->global->REQUESTMANAGER_ASSIGNED_NOTIFICATION_SIGNATURE;
                            if (empty($signature) && !empty($conf->global->REQUESTMANAGER_ASSIGNED_NOTIFICATION_USER_SIGNATURE)) $signature = $user->signature;
                            $body = !empty($signature) ? dol_concatdesc($assigned_template['body'], '<br>' . $signature) : $assigned_template['body'];
                            $body = make_substitutions($body, $substitutes_array);

                            $from = !empty($conf->global->REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM) ? $conf->global->REQUESTMANAGER_ASSIGNED_NOTIFICATION_SEND_FROM : $this->_formatEmail($user->getFullName($langs), $user->email);

                            $sendtocc = $sendtobcc = "";
                            $deliveryreceipt = 0;

                            foreach ($sendto_list as $sendto) {
                                $result = $this->_sendEmail($subject, $sendto, $from, $body, $attachedfiles['paths'], $attachedfiles['mimes'], $attachedfiles['names'], $sendtocc, $sendtobcc, $deliveryreceipt, -1);
                                if ($result > 0) {
                                    $this->_createSendNotificationEvent($requestmanager, $subject, $sendto, array_keys($sendto), $from, $body, $attachedfiles, $sendtocc, $sendtobcc);
                                }
                            }
                        }
                    }
                } else {
                    //return -1; Error or warning template not defined
                }
            }

            // Send email to requesters
            if (isset($requesters_template)) {
                if ((isset($requestmanagermessage) && !empty($requestmanagermessage->notify_requesters)) || (!isset($requestmanagermessage) && !empty($requestmanager->notify_requester_by_email))) {
                    $emails = $this->_getRequesterEmails($requestmanager);
                    if (count($emails)) {
                        if (!empty($conf->global->REQUESTMANAGER_SPLIT_REQUESTER_NOTIFICATION)) {
                            $sendto_list = array_chunk($emails, 1, true);
                        } else {
                            $sendto_list = array($emails);
                        }

                        $subject = make_substitutions($requesters_template['subject'], $substitutes_array);
                        $signature = $requesters_template['signature'];
                        if (empty($signature)) $signature = $conf->global->REQUESTMANAGER_REQUESTER_NOTIFICATION_SIGNATURE;
                        if (empty($signature) && !empty($conf->global->REQUESTMANAGER_REQUESTER_NOTIFICATION_USER_SIGNATURE)) $signature = $user->signature;
                        $body = !empty($signature) ? dol_concatdesc($requesters_template['body'], '<br>' . $signature) : $requesters_template['body'];
                        $body = make_substitutions($body, $substitutes_array);

                        $from = !empty($conf->global->REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM) ? $conf->global->REQUESTMANAGER_REQUESTER_NOTIFICATION_SEND_FROM : $this->_formatEmail($user->getFullName($langs), $user->email);

                        $sendtocc = $sendtobcc = "";
                        $deliveryreceipt = 0;

                        foreach ($sendto_list as $sendto) {
                            $result = $this->_sendEmail($subject, $sendto, $from, $body, $attachedfiles['paths'], $attachedfiles['mimes'], $attachedfiles['names'], $sendtocc, $sendtobcc, $deliveryreceipt, -1);
                            if ($result > 0) {
                                $this->_createSendNotificationEvent($requestmanager, $subject, $sendto, array_keys($sendto), $from, $body, $attachedfiles, $sendtocc, $sendtobcc);
                            }
                        }
                    }
                }
            } else {
                //return -1; Error or warning template not defined
            }

            // Send email to watchers
            if (isset($watchers_template)) {
                if ((isset($requestmanagermessage) && !empty($requestmanagermessage->notify_watchers)) || (!isset($requestmanagermessage) && !empty($requestmanager->notify_watcher_by_email))) {
                    $emails = $this->_getWatcherEmails($requestmanager);
                    if (count($emails)) {
                        if (!empty($conf->global->REQUESTMANAGER_SPLIT_WATCHERS_NOTIFICATION)) {
                            $sendto_list = array_chunk($emails, 1, true);
                        } else {
                            $sendto_list = array($emails);
                        }

                        $subject = make_substitutions($watchers_template['subject'], $substitutes_array);
                        $signature = $watchers_template['signature'];
                        if (empty($signature)) $signature = $conf->global->REQUESTMANAGER_WATCHERS_NOTIFICATION_SIGNATURE;
                        if (empty($signature) && !empty($conf->global->REQUESTMANAGER_WATCHERS_NOTIFICATION_USER_SIGNATURE)) $signature = $user->signature;
                        $body = !empty($signature) ? dol_concatdesc($watchers_template['body'], '<br>' . $signature) : $watchers_template['body'];
                        $body = make_substitutions($body, $substitutes_array);

                        $from = !empty($conf->global->REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM) ? $conf->global->REQUESTMANAGER_WATCHERS_NOTIFICATION_SEND_FROM : $this->_formatEmail($user->getFullName($langs), $user->email);

                        $sendtocc = $sendtobcc = "";
                        $deliveryreceipt = 0;

                        foreach ($sendto_list as $sendto) {
                            $result = $this->_sendEmail($subject, $sendto, $from, $body, $attachedfiles['paths'], $attachedfiles['mimes'], $attachedfiles['names'], $sendtocc, $sendtobcc, $deliveryreceipt, -1);
                            if ($result > 0) {
                                $this->_createSendNotificationEvent($requestmanager, $subject, $sendto, array_keys($sendto), $from, $body, $attachedfiles, $sendtocc, $sendtobcc);
                            }
                        }
                    }
                }
            } else {
                //return -1; Error or warning template not defined
            }
        }

        return 1;
    }

    /**
     *  Get emails list of all the assigned to the request
     *
     * @param   RequestManager      $requestmanager         RequestManager instance
     * @return  array
     */
    private function _getAssignedEmails(&$requestmanager) {
        global $langs, $user;

        $requestmanager->fetch_assigned(1);

        $emails = array();
        foreach($requestmanager->assigned_user_list as $user_f) {
            if (!isset($emails[$user_f->id]) && $user->id != $user_f->id && !empty($user_f->statut)) {
                $emails[$user_f->id] = $this->_formatEmail($user_f->getFullName($langs), $user_f->email);
            }
        }

        foreach($requestmanager->assigned_usergroup_list as $usergroup) {
            foreach($usergroup->members as $user_f) {
                if (!isset($emails[$user_f->id]) && $user->id != $user_f->id && !empty($user_f->statut)) {
                    $emails[$user_f->id] = $this->_formatEmail($user_f->getFullName($langs), $user_f->email);
                }
            }
        }

        return $emails;
    }

    /**
     *  Get emails list of all the requesters to the request
     *
     * @param   RequestManager      $requestmanager         RequestManager instance
     * @return  array
     */
    private function _getRequesterEmails(&$requestmanager) {
        global $langs;

        $requestmanager->fetch_requesters(1);

        $emails = array();
        foreach($requestmanager->requester_list as $contact) {
            if (!isset($emails[$contact->id]) && !empty($contact->statut)) {
                $emails[$contact->id] = $this->_formatEmail($contact->getFullName($langs), $contact->email);
            }
        }

        return $emails;
    }

    /**
     *  Get emails list of all the watchers to the request
     *
     * @param   RequestManager      $requestmanager         RequestManager instance
     * @return  array
     */
    private function _getWatcherEmails(&$requestmanager) {
        global $langs;

        $requestmanager->fetch_watchers(1);

        $emails = array();
        foreach($requestmanager->watcher_list as $contact) {
            if (!isset($emails[$contact->id]) && !empty($contact->statut)) {
                $emails[$contact->id] = $this->_formatEmail($contact->getFullName($langs), $contact->email);
            }
        }

        return $emails;
    }

    /**
     *  Get emails list of all the assigned to the request
     *
     * @param   string      $name       Name of the user
     * @param   string      $name       Address email
     * @return  string                  Formatted email (RFC 2822: "Name firstname <email>" or "email" or "<email>")
     */
    private function _formatEmail($name, $email)
    {
        if (!preg_match('/<|>/i', $email) && !empty($name)) {
            $email = str_replace(array('<', '>'), '', $name) . ' <' . $email . '>';
        }

        return $email;
    }

    /**
     *  Send notification to the assigned, requesters, watchers for a type of notification
     *
     * @param   string	        $subject             Topic/Subject of mail
	 * @param   array|string	$sendto              List of recipients emails  (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	        $from                Sender email               (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	        $body                Body message
	 * @param   array	        $filename_list       List of files to attach (full path of filename on file system)
	 * @param   array	        $mimetype_list       List of MIME type of attached files
	 * @param   array	        $mimefilename_list   List of attached file name in message
	 * @param   array|string	$sendtocc            Email cc
	 * @param   array|string	$sendtobcc           Email bcc (Note: This is autocompleted with MAIN_MAIL_AUTOCOPY_TO if defined)
	 * @param   int		        $deliveryreceipt     Ask a delivery receipt
	 * @param   int		        $msgishtml           1=String IS already html, 0=String IS NOT html, -1=Unknown make autodetection (with fast mode, not reliable)
	 * @param   string	        $errors_to      	 Email for errors-to
	 * @param   string	        $css                 Css option
	 * @param   string          $moreinheader        More in header. $moreinheader must contains the "\r\n" (TODO not supported for other MAIL_SEND_MODE different than 'phpmail' and 'smtps' for the moment)
	 * @param   string          $sendcontext      	 'standard', 'emailing', ...
     * @return  int                                  <0 if KO, >0 if OK
     */
    private function _sendEmail($subject, $sendto, $from, $body, $filename_list=array(), $mimetype_list=array(), $mimefilename_list=array(), $sendtocc="", $sendtobcc="", $deliveryreceipt=0, $msgishtml=0, $errors_to='', $css='', $moreinheader='', $sendcontext='standard')
    {
        global $db, $conf, $langs, $user, $dolibarr_main_url_root;

        //if (!empty($conf->dolimail->enabled)) $langs->load("dolimail@dolimail");
        $langs->load('mails');

        // Check parameters
        $sendto = is_array($sendto) ? implode(', ', $sendto) : $sendto;
        $sendtocc = is_array($sendtocc) ? implode(', ', $sendtocc) : $sendtocc;
        $sendtobcc = is_array($sendtobcc) ? implode(', ', $sendtobcc) : $sendtobcc;

        if (dol_strlen($sendto)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

            // Define $urlwithroot
            $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
            $urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;        // This is to use external domain name found into config file
            //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

            // Make a change into HTML code to allow to include images from medias directory with an external reabable URL.
            // <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
            // become
            // <img alt="" src="'.$urlwithroot.'viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
            $body = preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1' . $urlwithroot . '/viewimage.php\2modulepart=medias\3file=\4\5', $body);

            // Feature to push mail sent into Sent folder
//            if (! empty($conf->dolimail->enabled)) {
//                $mailfromid = explode("#", $_POST['frommail'], 3);    // $_POST['frommail'] = 'aaa#Sent# <aaa@aaa.com>'	// TODO Use a better way to define Sent dir.
//                if (count($mailfromid) == 0) $from = $_POST['fromname'] . ' <' . $_POST['frommail'] . '>';
//                else {
//                    $mbid = $mailfromid[1];
//
//                    /*IMAP Postbox*/
//                    $mailboxconfig = new IMAP($db);
//                    $mailboxconfig->fetch($mbid);
//                    if ($mailboxconfig->mailbox_imap_host) $ref = $mailboxconfig->get_ref();
//
//                    $mailboxconfig->folder_id = $mailboxconfig->mailbox_imap_outbox;
//                    $mailboxconfig->userfolder_fetch();
//
//                    if ($mailboxconfig->mailbox_save_sent_mails == 1) {
//
//                        $folder = str_replace($ref, '', $mailboxconfig->folder_cache_key);
//                        if (!$folder) $folder = "Sent";    // Default Sent folder
//
//                        $mailboxconfig->mbox = imap_open($mailboxconfig->get_connector_url() . $folder, $mailboxconfig->mailbox_imap_login, $mailboxconfig->mailbox_imap_password);
//                        if (FALSE === $mailboxconfig->mbox) {
//                            $info = FALSE;
//                            $err = $langs->trans('Error3_Imap_Connection_Error');
//                            setEventMessages($err, $mailboxconfig->element, null, 'errors');
//                        } else {
//                            $mailboxconfig->mailboxid = $_POST['frommail'];
//                            $mailboxconfig->foldername = $folder;
//                            $from = $mailfromid[0] . $mailfromid[2];
//                            $imap = 1;
//                        }
//
//                    }
//                }
//            }

            // Send mail
            $mailfile = new CMailFile($subject, $sendto, $from, $body, $filename_list, $mimetype_list, $mimefilename_list, $sendtocc, $sendtobcc, $deliveryreceipt, $msgishtml, $errors_to, $css, '', $moreinheader, $sendcontext);
            if ($mailfile->error) {
                $this->errors[] = $mailfile->error;
                setEventMessage($mailfile->error, 'errors');
            } else {
                $result = $mailfile->sendfile();
                if ($result) {
                    $error = 0;

                    // FIXME This must be moved into the trigger for action $trigger_name
//                    if (!empty($conf->dolimail->enabled)) {
//                        $mid = (GETPOST('mid', 'int') ? GETPOST('mid', 'int') : 0);    // Original mail id is set ?
//                        if ($mid) {
//                            // set imap flag answered if it is an answered mail
//                            $dolimail = new DoliMail($db);
//                            $dolimail->id = $mid;
//                            $res = $dolimail->set_prop($user, 'answered', 1);
//                        }
//                        if ($imap == 1) {
//                            // write mail to IMAP Server
//                            $movemail = $mailboxconfig->putMail($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $folder, $deliveryreceipt, $mailfile);
//                            if ($movemail) setEventMessages($langs->trans("MailMovedToImapFolder", $folder), null, 'mesgs');
//                            else setEventMessages($langs->trans("MailMovedToImapFolder_Warning", $folder), null, 'warnings');
//                        }
//                    }
//
//                    // Initialisation of datas
//                    if (is_object($object)) {
//                        if (empty($actiontypecode)) $actiontypecode = 'AC_OTH_AUTO'; // Event insert into agenda automatically
//
//                        $object->socid = $sendtosocid;       // To link to a company
//                        $object->sendtoid = $sendtoid;       // To link to contacts/addresses. This is an array.
//                        $object->actiontypecode = $actiontypecode; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
//                        $object->actionmsg = $actionmsg;      // Long text
//                        $object->actionmsg2 = $actionmsg2;     // Short text
//                        $object->trackid = $trackid;
//                        $object->fk_element = $object->id;
//                        $object->elementtype = $object->element;
//                        $object->attachedfiles = $attachedfiles;
//
//                        // Call of triggers
//                        if (!empty($trigger_name)) {
//                            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
//                            $interface = new Interfaces($db);
//                            $result = $interface->run_triggers($trigger_name, $object, $user, $langs, $conf);
//                            if ($result < 0) {
//                                $error++;
//                                $errors = $interface->errors;
//                            }
//                        }
//                    }

                    if (!$error) {
                        $mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2));
                        setEventMessages($mesg, null, 'mesgs');
                    }

                    return 1;
                } else {
                    $langs->load("other");
                    $mesg = '<div class="error">';
                    if ($mailfile->error) {
                        $mesg .= $langs->trans('ErrorFailedToSendMail', $from, $sendto);
                        $mesg .= '<br>' . $mailfile->error;
                    } else {
                        $mesg .= 'No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                    }
                    $mesg .= '</div>';

                    $this->errors[] = $mesg;
                    setEventMessages($mesg, null, 'warnings');
                }
            }
        } else {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
            $this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo"));
            dol_syslog('Try to send notify email with no recipient defined', LOG_WARNING);
        }

        return -1;
    }

    /**
     *
     * @param   RequestManager      $requestmanager     RequestManager instance
     * @param   string	            $subject            Topic/Subject of mail
	 * @param   array|string        $sendto             List of recipients emails  (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	            $from               Sender email               (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	            $body               Body message
	 * @param   array	            $attachedfiles      List of files to attach array( 'paths' => full path of filename on file system, 'mimes' => List of MIME type of attached files, 'names' => List of attached file name in message )
	 * @param   array|string        $sendtocc           Email cc
	 * @param   array|string        $sendtobcc          Email bcc (Note: This is autocompleted with MAIN_MAIL_AUTOCOPY_TO if defined)
     * @param   string              $other              Other information add to the description of the event
     * @return  int                                     <0 if KO, >0 if OK
     */
    private function _createSendNotificationEvent(&$requestmanager, $subject, $sendto, $sendtoid, $from, $body, $attachedfiles=array(), $sendtocc="", $sendtobcc="",$other="")
    {
        global $conf, $langs, $user;

        // Check parameters
        $sendto = is_array($sendto) ? implode(', ', $sendto) : $sendto;
        $sendtocc = is_array($sendtocc) ? implode(', ', $sendtocc) : $sendtocc;
        $sendtobcc = is_array($sendtobcc) ? implode(', ', $sendtobcc) : $sendtobcc;

        $now = dol_now();

        // Set societeforaction.
        $requestmanager->fetch_thirdparty();
        $societeforaction = $requestmanager->thirdparty;

        // Set contactforaction if there is only 1 contact.
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        $contactforaction = new Contact($this->db);
        if (is_array($sendtoid)) {
            if (count($sendtoid) == 1) $contactforaction->fetch(reset($sendtoid));
        } else {
            if ($sendtoid > 0) $contactforaction->fetch($sendtoid);
        }

        // Set projectid.
        $projectid = isset($requestmanager->fk_project) ? $requestmanager->fk_project : 0;

        // Set title and description
        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        $actionmsg2 = $langs->transnoentities('MailSentBy') . ' ' . CMailFile::getValidAddress($from, 4, 0, 1) . ' ' . $langs->transnoentities('To') . ' ' . CMailFile::getValidAddress($sendto, 4, 0, 1);
        $actionmsg = '';
        if ($body) {
            $actionmsg = $langs->transnoentities('MailFrom') . ': ' . dol_escape_htmltag($from);
            $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTo') . ': ' . dol_escape_htmltag($sendto));
            if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailCC') . ": " . dol_escape_htmltag($sendtocc));
            if ($sendtobcc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailCCC') . ": " . dol_escape_htmltag($sendtobcc));
            $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
            $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
            $actionmsg = dol_concatdesc($actionmsg, $body);
        }
        $actionmsg .= '<br><br>' . $langs->transnoentities("Author") . ': ' . $user->login;
        if (is_array($attachedfiles) && array_key_exists('names', $attachedfiles) && count($attachedfiles['names']) > 0) {
            $actionmsg = dol_concatdesc($actionmsg, '<br>' . $langs->transnoentities("AttachedFiles") . ': ' . implode(';', $attachedfiles['names']));
        }

        // Concat other information
        $actionmsg = dol_concatdesc($actionmsg, $other);

        // Insertion action
        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
        $actioncomm = new ActionComm($this->db);
        $actioncomm->type_code = 'AC_OTH_AUTO';
        //$actioncomm->code = 'AC_' . $action;
        $actioncomm->label = $actionmsg2;
        $actioncomm->note = $actionmsg;
        $actioncomm->fk_project = $projectid;
        $actioncomm->datep = $now;
        $actioncomm->datef = $now;
        $actioncomm->durationp = 0;
        $actioncomm->punctual = 1;
        $actioncomm->percentage = -1;   // Not applicable
        $actioncomm->societe = $societeforaction;
        $actioncomm->contact = $contactforaction;
        $actioncomm->socid = $societeforaction->id;
        $actioncomm->contactid = $contactforaction->id;
        $actioncomm->authorid = $user->id;   // User saving action
        $actioncomm->userownerid = $user->id;    // Owner of action
        $actioncomm->fk_element = $requestmanager->id;
        $actioncomm->elementtype = $requestmanager->element;

        $ret = $actioncomm->create($user);       // User creating action

        if ($ret > 0 && $conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO) {
            if (is_array($attachedfiles) && array_key_exists('paths', $attachedfiles) && count($attachedfiles['paths']) > 0) {
                foreach ($attachedfiles['paths'] as $key => $filespath) {
                    $srcfile = $filespath;
                    $destdir = $conf->agenda->dir_output . '/' . $ret;
                    $destfile = $destdir . '/' . $attachedfiles['names'][$key];
                    if (dol_mkdir($destdir) >= 0) {
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                        dol_copy($srcfile, $destfile);
                    }
                }
            }
        }

        if ($ret > 0) {
            return 1;
        } else {
            $error = "Failed to insert event : " . $actioncomm->error . " " . join(',', $actioncomm->errors);
            $this->error = $error;
            $this->errors = $actioncomm->errors;

            dol_syslog(__METHOD__ . ': Error:' . $this->error, LOG_ERR);
            return -1;
        }
    }
}