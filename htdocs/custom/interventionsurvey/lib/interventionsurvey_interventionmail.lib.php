<?php

class InterventionMail {
    var $db;
    var $object;
    var $user;
    var $error;
	var $errors = array();

    /**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	*/
	function __construct($db, $object, $user) {
        $this->db = $db;
        $this->object = $object;
        $this->user = $user;
		$this->error = 0;
		$this->errors = array();
    }
    
    /**
     * Get recipient email list
     *
     * @return array
    */
    public function getRecipientEmailList() {
        global $action, $hookmanager;

        // Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('interventionmail'));

        $emailList = [];

        include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        $contact = new Contact($this->db);
                                
        $involvedPersonIds = array_map('intval', explode(', ', $this->object->array_options['options_contacts_to_send_fichinter_to']));
        foreach ($involvedPersonIds as $personId) {
            $result = $contact->fetch($personId);

            if ($result > 0 && !empty($contact->email)) {
                array_push($emailList, $contact->email);
            }
        }

        $user = new User($this->db);

        $involvedUsersIds = array_map('intval', explode(', ', $this->object->array_options['options_users_to_send_fichinter_to']));
        foreach ($involvedUsersIds as $userId) {
            $result = $user->fetch($userId);
                                        
            if ($result > 0 && !empty($user->email)) {
                array_push($emailList, $user->email);
            }
        }

        $parameters = array('emailList' => $emailList);
        $reshook = $hookmanager->executeHooks('addMoreToEmail', $parameters, $this->object, $action); // Note that $action and $object may have been modified by some hooks
        if (empty($reshook)) {
            $emailList = array_merge($emailList, $hookmanager->resArray);
        } else if ($reshook > 0) {
            $emailList = $hookmanager->resArray;
        } else {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        $emailList = array_unique($emailList);

        return $emailList;
    }

    /**
     * Get intervention pdf file
     *
     * @return object
    */
    public function getInterventionPdfFile() {
        global $conf;

        $ref = dol_sanitizeFileName($this->object->ref);
        $file = $conf->ficheinter->dir_output . '/' . $ref . '/' . $ref . '.pdf';

        return $file;
    }

    /**
	 *      Return template of email
	 *      Search into table c_email_templates
	 *
	 * 		@param	DoliDB		$db				Database handler
	 * 		@param	string		$type_template	Get message for key module
	 *      @param	string		$user			Use template public or limited to this user
	 *      @param	Translate	$outputlangs	Output lang object
	 *      @param	int			$id				Id template to find
	 *      @param  int         $active         1=Only active template, 0=Only disabled, -1=All
	 *      @return array						array('topic'=>,'content'=>,..)
	 */
	public function getEMailTemplate($db, $type_template, $user, $outputlangs, $id=0, $active=1)
	{
		$ret=array();

		$sql = "SELECT label, topic, content, content_lines, lang";
		$sql.= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
		$sql.= " WHERE type_template='".$db->escape($type_template)."'";
		$sql.= " AND entity IN (".getEntity('c_email_templates', 0).")";
		$sql.= " AND (fk_user is NULL or fk_user = 0 or fk_user = ".$user->id.")";
		if ($active >= 0) $sql.=" AND active = ".$active;
		if (is_object($outputlangs)) $sql.= " AND (lang = '".$outputlangs->defaultlang."' OR lang IS NULL OR lang = '')";
		if (!empty($id)) $sql.= " AND rowid=".$id;
		$sql.= $db->order("position,lang,label","ASC");
		//print $sql;

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);	// Get first found
			if ($obj)
			{
				$ret['label']=$obj->label;
				$ret['topic']=$obj->topic;
				$ret['content']=$obj->content;
				$ret['content_lines']=$obj->content_lines;
				$ret['lang']=$obj->lang;
			}
			else
			{
				$defaultmessage='';
				if     ($type_template=='facture_send')	            { $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoice"); }
	        	elseif ($type_template=='facture_relance')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoiceReminder"); }
	        	elseif ($type_template=='propal_send')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendProposal"); }
	        	elseif ($type_template=='supplier_proposal_send')	{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierProposal"); }
	        	elseif ($type_template=='order_send')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendOrder"); }
	        	elseif ($type_template=='order_supplier_send')		{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierOrder"); }
	        	elseif ($type_template=='invoice_supplier_send')	{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierInvoice"); }
	        	elseif ($type_template=='shipping_send')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendShipping"); }
	        	elseif ($type_template=='fichinter_send')			{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendFichInter"); }
	        	elseif ($type_template=='thirdparty')				{ $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentThirdparty"); }
	        	elseif ($type_template=='user')				        { $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentUser"); }

	        	$ret['label']='default';
	        	$ret['topic']='';
	        	$ret['content']=$defaultmessage;
				$ret['content_lines']='';
	        	$ret['lang']=$outputlangs->defaultlang;
			}

			$db->free($resql);
			return $ret;
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
	}

    /**
     * Send the intervention (with pdf file) by mail
     *
     * @return int
    */
    public function sendInterventionByMail($sendfrom = '', $emailInitTemplate = 'fichinter_send') {
        global $conf, $langs;

        // Define output language
        $outputlangs = $langs;
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang('fr');
        $outputlangs->load("interventions");

        $template = $this->getEMailTemplate($this->db, $emailInitTemplate, $this->user, $outputlangs);

        $paramname = 'id';
        $mode = 'emailfromintervention';
        $_POST['langsmodels'] = $outputlangs->defaultlang;
        $_POST['models'] = 'fichinter_send';
        $_POST['fichinter_id'] = $this->object->id;
        $emailList = $this->getRecipientEmailList();
        $_POST['sendto'] = implode(",", $emailList);
        $_POST['sendmail'] = 'Send email';
        
        $trackid = 'int' . $this->object->id;
        $subject='';$actionmsg='';$actionmsg2='';

        if (! empty($conf->dolimail->enabled)) $langs->load("dolimail@dolimail");
        $langs->load('mails');

        if (is_object($this->object))
        {
            $result = $this->object->fetch($this->object->id);

            $sendtosocid=0;    // Thirdparty on object
            if (method_exists($this->object,"fetch_thirdparty") && $this->object->element != 'societe')
            {
                $result = $this->object->fetch_thirdparty();
                if ($this->object->element == 'user' && $result == 0) $result = 1;    // Even if not found, we consider ok
                $thirdparty=$this->object->thirdparty;
                $sendtosocid=$thirdparty->id;
            }
            else dol_print_error('','Use actions_sendmails.in.php for an element/object that is not supported');
        }
        else $thirdparty = $mysoc;

        if ($result > 0)
        {
            $sendto='';
            $sendtocc='';
            $sendtobcc='';
            $sendtoid = array();

            // Define $sendto
            $receiver=$_POST['receiver'];
            if (! is_array($receiver))
            {
                if ($receiver == '-1') $receiver = array();
                else $receiver = array($receiver);
            }
            $tmparray=array();
            if (trim($_POST['sendto']))
            {
                // Recipients are provided into free text
                $tmparray[] = trim($_POST['sendto']);
            }
            if (count($receiver)>0)
            {
                foreach($receiver as $key=>$val)
                {
                    // Recipient was provided from combo list
                    if ($val == 'thirdparty') // Id of third party
                    {
                        $tmparray[] = $thirdparty->name.' <'.$thirdparty->email.'>';
                    }
                    elseif ($val)	// Id du contact
                    {
                        $tmparray[] = $thirdparty->contact_get_property((int) $val,'email');
                        $sendtoid[] = $val;
                    }
                }
            }
            $sendto = implode(',',$tmparray);

            if (dol_strlen($sendto))
            {
                // Define $urlwithroot
                $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
                $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
                //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

                require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

                $langs->load("commercial");

                $fromtype = 'company';
                if ($sendfrom != '') {
                    $from = $sendfrom .' <'.$sendfrom.'>';
                } else {
                    $from = $conf->global->MAIN_INFO_SOCIETE_NOM .' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
                }
                
                $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';

                $societe = new Societe($this->db);
                $societe->fetch($this->object->socid);    
                $message = $template['content'];

                // Make a change into HTML code to allow to include images from medias directory with an external reabable URL.
                // <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
                // become
                // <img alt="" src="'.$urlwithroot.'viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
                $message=preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $message);

                $deliveryreceipt = 0;

                $subject = $template['topic'];
                $actionmsg2 = $langs->transnoentities('MailSentBy').' '.CMailFile::getValidAddress($from,4,0,1).' '.$langs->transnoentities('To').' '.CMailFile::getValidAddress($sendto,4,0,1);
                if ($message)
                {
                    $actionmsg = $langs->transnoentities('MailFrom').': '.dol_escape_htmltag($from);
                    $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($sendto));
                    $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
                    $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
                    $actionmsg = dol_concatdesc($actionmsg, $message);
                }

                // Create form object
                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
                $formmail = new FormMail($this->db);
                $formmail->trackid = $trackid;      // $trackid must be defined

                $file = $this->getInterventionPdfFile();
                $formmail->clear_attached_files();
			    $formmail->add_attached_files($file, basename($file), dol_mimetype($file));

                $attachedfiles = $formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Feature to push mail sent into Sent folder
                if (! empty($conf->dolimail->enabled))
                {
                    $mailfromid = explode("#", $_POST['frommail'],3);	// $_POST['frommail'] = 'aaa#Sent# <aaa@aaa.com>'	// TODO Use a better way to define Sent dir.
                    if (count($mailfromid)==0) $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                    else
                    {
                        $mbid = $mailfromid[1];

                        /*IMAP Postbox*/
                        $mailboxconfig = new IMAP($this->db);
                        $mailboxconfig->fetch($mbid);
                        if ($mailboxconfig->mailbox_imap_host) $ref=$mailboxconfig->get_ref();

                        $mailboxconfig->folder_id=$mailboxconfig->mailbox_imap_outbox;
                        $mailboxconfig->userfolder_fetch();

                        if ($mailboxconfig->mailbox_save_sent_mails == 1)
                        {

                            $folder=str_replace($ref, '', $mailboxconfig->folder_cache_key);
                            if (!$folder) $folder = "Sent";	// Default Sent folder

                            $mailboxconfig->mbox = imap_open($mailboxconfig->get_connector_url().$folder, $mailboxconfig->mailbox_imap_login, $mailboxconfig->mailbox_imap_password);
                            if (FALSE === $mailboxconfig->mbox)
                            {
                                $info = FALSE;
                                $err = $langs->trans('Error3_Imap_Connection_Error');
                                setEventMessages($err,$mailboxconfig->element, null, 'errors');
                            }
                            else
                            {
                                $mailboxconfig->mailboxid=$_POST['frommail'];
                                $mailboxconfig->foldername=$folder;
                                $from = $mailfromid[0] . $mailfromid[2];
                                $imap = 1;
                            }

                        }
                    }
                }

                $substitutionarray=array(
                    '__DOL_MAIN_URL_ROOT__'=>DOL_MAIN_URL_ROOT,
                    '__ID__' => (is_object($this->object)?$this->object->id:''),
                    '__EMAIL__' => $sendto,
                    '__CHECK_READ__' => (is_object($this->object) && is_object($this->object->thirdparty))?'<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$this->object->thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>':'',
                    '__REF__' => (is_object($this->object)?$this->object->ref:''),
                    '__SIGNATURE__' => (($this->user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))?$this->user->signature:''),
                    '__THIRDPARTY_NAME__' => $societe->nom,
                    '__MYCOMPANY_NAME__' => $conf->global->MAIN_INFO_SOCIETE_NOM
                    /* not available on all object
                    /'__FIRSTNAME__'=>(is_object($object)?$object->firstname:''),
                    '__LASTNAME__'=>(is_object($object)?$object->lastname:''),
                    '__FULLNAME__'=>(is_object($object)?$object->getFullName($langs):''),
                    '__ADDRESS__'=>(is_object($object)?$object->address:''),
                    '__ZIP__'=>(is_object($object)?$object->zip:''),
                    '__TOWN_'=>(is_object($object)?$object->town:''),
                    '__COUNTRY__'=>(is_object($object)?$object->country:''),
                    */
                );
    
                $subject=make_substitutions($subject, $substitutionarray);
                $message=make_substitutions($message, $substitutionarray);

                $sendToList = explode(',', $sendto);
                // Send mail (substitutionarray must be done just before this)
                foreach ($sendToList as $sendTo) {
                    $mailfile = new CMailFile($subject,$sendTo,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$sendtobcc,$deliveryreceipt,-1,'','',$trackid);
                    if ($mailfile->error)
                    {
                        setEventMessage($mailfile->error, 'errors');
                    }
                    else
                    {
                        $result=$mailfile->sendfile();
                        if ($result)
                        {
                            $error=0;

                            // FIXME This must be moved into the trigger for action $trigger_name
                            if (! empty($conf->dolimail->enabled))
                            {
                                $mid = (GETPOST('mid','int') ? GETPOST('mid','int') : 0);	// Original mail id is set ?
                                if ($mid)
                                {
                                    // set imap flag answered if it is an answered mail
                                    $dolimail=new DoliMail($this->db);
                                    $dolimail->id = $mid;
                                    $res=$dolimail->set_prop($this->user, 'answered',1);
                                }
                                if ($imap==1)
                                {
                                    // write mail to IMAP Server
                                    $movemail = $mailboxconfig->putMail($subject,$sendTo,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$folder,$deliveryreceipt,$mailfile);
                                    if ($movemail) setEventMessages($langs->trans("MailMovedToImapFolder",$folder), null, 'mesgs');
                                    else setEventMessages($langs->trans("MailMovedToImapFolder_Warning",$folder), null, 'warnings');
                                }
                            }

                            // Initialisation of datas
                            if (is_object($this->object))
                            {
                                if (empty($actiontypecode)) $actiontypecode='AC_OTH_AUTO'; // Event insert into agenda automatically

                                $this->object->socid			= $sendtosocid;	   // To link to a company
                                $this->object->sendtoid		    = $sendtoid;	   // To link to contacts/addresses. This is an array.
                                $this->object->actiontypecode	= $actiontypecode; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                                $this->object->actionmsg		= $actionmsg;      // Long text
                                $this->object->actionmsg2		= $actionmsg2;     // Short text
                                $this->object->trackid          = $trackid;
                                $this->object->fk_element		= $object->id;
                                $this->object->elementtype	    = $object->element;
                                $this->object->attachedfiles	= $attachedfiles;

                                // Call of triggers
                                if (! empty($trigger_name))
                                {
                                    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                                    $interface=new Interfaces($this->db);
                                    $result=$interface->run_triggers($trigger_name,$this->object,$this->user,$langs,$conf);
                                    if ($result < 0) {
                                        $error++; $errors=$interface->errors;
                                    }
                                }
                            }

                            if ($error)
                            {
                                dol_print_error($this->db);
                            }
                        }
                        else
                        {
                            $langs->load("other");
                            $mesg='<div class="error">';
                            if ($mailfile->error)
                            {
                                $mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendTo);
                                $mesg.='<br>'.$mailfile->error;
                            }
                            else
                            {
                                $mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                            }
                            $mesg.='</div>';

                            setEventMessages($mesg, null, 'warnings');
                        }
                    }
                }
            }
            else
            {
                $langs->load("errors");
                setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
                dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
            }
        }
        else
        {
            $langs->load("other");
            setEventMessages($langs->trans('ErrorFailedToReadEntity',$this->object->element), null, 'errors');
            dol_syslog('Failed to read data of object id='.$this->object->id.' element='.$this->object->element);
        }

    }
}

?>