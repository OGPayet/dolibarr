<?php

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


/**
 * Class to use CRON with module propalautosend
 */
class propalautosendCron
{

	public $db;

	function __construct(&$db) {
		$this->db = $db;
	}

	/**
	 * Method to call with CRON module
	 */
	public function run()
	{
		global $conf, $langs, $user;

		$langs->load('main');

		$TMail = array();
		$TErrorMail = array();
		$today = date('Y-m-d');
		//Requete pour avoir toutes les propals avec une date de relance en date du jour
		$sql = 'SELECT p.rowid FROM '.MAIN_DB_PREFIX.'propal p
		INNER JOIN '.MAIN_DB_PREFIX.'propal_extrafields pe ON (p.rowid = pe.fk_object)
		WHERE p.entity = '.$conf->entity.'
		AND p.fk_statut = 1
		AND pe.date_relance = "'.$this->db->escape($today).'"
		AND p.total_ht > "'.$conf->global->PROPALAUTOSEND_MINIMAL_AMOUNT.'"';

		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) > 0)
		{
			$msgishtml = $conf->fckeditor->enabled && !empty($conf->global->FCKEDITOR_ENABLE_MAIL) ? 1 : 0;

			while ($line = $this->db->fetch_object($resql))
			{
				$subject = $conf->global->PROPALAUTOSEND_MSG_SUBJECT;
				if (empty($subject)) exit("errorSubjectMailIsEmpty");
				$contactFound = false;
				$propal = new Propal($this->db);
				$propal->fetch($line->rowid);
				$propal->fetch_thirdparty();

				$arraySubstitutions = array(
						'__PROPAL_ref' => $propal->ref,
						'__PROPAL_ref_client' => $propal->ref_client,
						'__PROPAL_total_ht' => $propal->total_ht,
						'__PROPAL_total_tva' => $propal->total_tva,
						'__PROPAL_total_ttc' => $propal->total_ttc,
						'__PROPAL_datep' => dol_print_date($propal->datep, '%d/%m/%Y'),
						'__PROPAL_fin_validite' => dol_print_date($propal->fin_validite, '%d/%m/%Y')
				);

				foreach ($arraySubstitutions as $substit => $propalValue)
				{
					$subject = preg_replace('/'.$substit.'\b/', $propalValue, $subject);
				}

				if ($propal->user_author_id > 0)
				{
					$newUser = new User($this->db);
					$newUser->fetch($propal->user_author_id);
				}
				else
				{
					$newUser = &$user;
				}

				$filename_list = array();
				$mimetype_list = array();
				$mimefilename_list = array();

				if (!empty($conf->global->PROPALAUTOSEND_JOIN_PDF))
				{
					$ref = dol_sanitizeFileName($propal->ref);

					$file = $conf->propal->dir_output . '/' . $ref . '/' . $ref . '.pdf';

					$filename = basename($file);
					$mimefile=dol_mimetype($file);
					$filename_list[] = $file;
					$mimetype_list[] = $mimefile;
					$mimefilename_list[] = $filename;
				}

				if ($propal->id > 0)
				{
					$TContact = $propal->liste_contact(-1, 'external');
					foreach ($TContact as $TInfo)
					{

						//Contact client suivi proposition => fk_c_type_contact = 41
						if ($TInfo['code'] == 'CUSTOMER')
						{
							$contact = new Contact($this->db);
							$contact->fetch($TInfo['id']);

							$contactFound = true;
							$mail = $TInfo['email'];

							if (isValidEmail($mail))
							{
								$msg = $conf->global->PROPALAUTOSEND_MSG_CONTACT;
								if (empty($msg)) exit("errorContentMailContactIsEmpty");

								$prefix = '__CONTACT_';
								$TSearch = $TVal = array();
								foreach ($contact as $attr => $val)
								{
									if (!is_array($val) && !is_object($val))
									{
										$TSearch[] = $prefix.$attr;
										$TVal[] = $val;
									}
								}

								//Changement de méthode (pas de str_replace) pour éviter les collisions. Exemple avec __PROPAL_ref et __PROPAL_ref_client
								foreach ($arraySubstitutions as $substit => $propalValue)
								{
									$msg = preg_replace('/'.$substit.'\b/', $propalValue, $msg);
								}
								$msg = preg_replace('/__SIGNATURE__\b/', $newUser->signature, $msg);
								$msg = str_replace($TSearch, $TVal, $msg);

								$TMail[] = $mail;

								// Construct mail
								$CMail = new CMailFile(
										$subject
										,$mail
										,$conf->global->MAIN_MAIL_EMAIL_FROM
										,$msg
										,$filename_list
										,$mimetype_list
										,$mimefilename_list
										,'' //,$addr_cc=""
										,'' //,$addr_bcc=""
										,'' //,$deliveryreceipt=0
										,$msgishtml //,$msgishtml=0*/
										,$conf->global->MAIN_MAIL_ERRORS_TO
										//,$css=''
										);

								// Send mail
								$CMail->sendfile();
								if ($CMail->error) $TErrorMail[] = $CMail->error;
								else $this->_createEvent($newUser, $langs, $conf, $propal, $contact->id, $conf->global->PROPALAUTOSEND_MSG_SUBJECT, $msg, 'socpeople');
							}

						}
					}

					if (!$contactFound)
					{
						$mail = $propal->thirdparty->email;

						if (isValidEmail($mail))
						{
							$msg = $conf->global->PROPALAUTOSEND_MSG_THIRDPARTY;
							if (empty($msg)) exit("errorContentMailTirdpartyIsEmpty");

							$prefix = '__THIRDPARTY_';
							$TSearch = $TVal = array();
							foreach ($propal->thirdparty as $attr => $val)
							{
								if (!is_array($val) && !is_object($val))
								{
									$TSearch[] = $prefix.$attr;
									$TVal[] = $val;
								}
							}

							//Changement de méthode (pas de str_replace) pour éviter les collisions. Exemple avec __PROPAL_ref et __PROPAL_ref_client
							foreach ($arraySubstitutions as $substit => $propalValue)
							{
								$msg = preg_replace('/'.$substit.'\b/', $propalValue, $msg);
							}
							$msg = preg_replace('/__SIGNATURE__\b/', $newUser->signature, $msg);
							$msg = str_replace($TSearch, $TVal, $msg);

							$TMail[] = $mail;

							// Construct mail
							$CMail = new CMailFile(
									$subject
									,$mail
									,$conf->global->MAIN_MAIL_EMAIL_FROM
									,$msg
									,$filename_list
									,$mimetype_list
									,$mimefilename_list
									,'' //,$addr_cc=""
									,'' //,$addr_bcc=""
									,'' //,$deliveryreceipt=0
									,$msgishtml //,$msgishtml=0*/
									,$conf->global->MAIN_MAIL_ERRORS_TO
									//,$css=''
									);

							// Send mail
							$CMail->sendfile();
							/*if ($CMail->error) $TErrorMail[] = $CMail->error;
							 else */$this->_createEvent($user, $langs, $conf, $propal, 0, $conf->global->PROPALAUTOSEND_MSG_SUBJECT, $msg);
						}

					}

				}

			}

			if (is_array($TMail) && count($TMail) > 0) {
				$this->output = "liste des mails ok : ".implode(', ', $TMail);
			}
			if (is_array($TErrorMail) && count($TErrorMail) > 0) {
				$this->output.= "<br />liste des mails en erreur : ".implode(', ', $TErrorMail);
			}
		}
		return 0;
	}


	function _createEvent(&$user, &$langs, &$conf, &$propal, $fk_socpeople, $subject, $message, $type='thirdparty')
	{
		$actionmsg = $actionmsg2 = '';

		if ($type == 'thirdparty') $sendto = $propal->thirdparty->email;
		else $sendto = $propal->thirdparty->contact_get_property((int) $fk_socpeople,'email');

		$actionmsg2=$langs->transnoentities('MailSentBy').' <'.$user->email.'> '.$langs->transnoentities('To').' '.$sendto;
		if ($message)
		{
			$actionmsg=$langs->transnoentities('MailSentBy').' <'.$user->email.'> '.$langs->transnoentities('To').' '.$sendto;
			$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
			$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
			$actionmsg = dol_concatdesc($actionmsg, $message);
		}

		// Initialisation donnees
		$propal->socid			= $propal->thirdparty->id;	// To link to a company
		$propal->sendtoid		= $fk_socpeople;	// To link to a contact/address
		$propal->actiontypecode	= 'AC_PROPSEND';
		$propal->actionmsg		= $actionmsg;  // Long text
		$propal->actionmsg2		= $actionmsg2; // Short text
		$propal->fk_element		= $propal->id;
		$propal->elementtype	= $propal->element;

		// Appel des triggers
		$interface=new Interfaces($this->db);
		$result=$interface->run_triggers('PROPAL_AUTOSENDBYMAIL',$propal,$user,$langs,$conf);
	}
}
