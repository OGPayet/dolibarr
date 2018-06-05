<?php

/**
 * execute an sql and send an array of objects
 * for mode = 1, sql is id => label
 * mode= 2 renvoie une liste unique d'id sql select xxx as id
 * mode = 3 une liste d'objets indexé sur une colonne id
 */
function tia_sqlarray($sql, $mode=0)
{
	global $db;

	$resql = $db->query($sql);


	if (!$resql)
	{
		dol_syslog(basename(__FILE__)."::".__FUNCTION__.":: erreur sql ".$sql , LOG_ERR);
		return -1;
	}
	$list = array();
	while ($obj = $db->fetch_object($resql)) {
		if ($mode == 1) $list[$obj->id] = $obj->label;
		elseif ($mode == 2) $list[] = $obj->id; // obtenir une liste d'ids
		elseif ($mode == 3) $list[$obj->id] = $obj;
		else $list[] = $obj; // classique tableau d'objet
	}

	return $list;
}

/**
 * renvoie la valeur unique sql identifiée par value.
 * @param unknown $sql
 * @return NULL|valeur ou '' si non trouvée
 */
function tia_simplesql($sql)
{
	global $db;

	$resql = $db->query($sql);

	if (!$resql)
	{
		dol_syslog(basename(__FILE__)."::".__FUNCTION__.":: erreur sql ".$sql , LOG_ERR);
		return null;
	}

	$obj = $db->fetch_object($resql);
	if ($obj) $value = $obj->value;
	else $value = '';

	return $value;

}


/**
 *  créé un règlement paypal/ogone pour une facture donnée
 *
 */
function tia_invoice_paiement($type, $invoice, $paypalinfos= array(), $modpaye=0)
{
	global $conf, $langs, $db, $user;

	require_once DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php";
	require_once DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php";

// TODO optimiser pour passer le code mode de paiement et non l'ID
	if ($invoice->statut != 1)
	{
		dol_syslog(__FUNCTION__.":: erreur statut facture ".$invoice->statut. "must be 1 (validatetd) no paiement created ", LOG_WARN);
		return -1;
	}
	$factid = $invoice->id;
	// charger le tiers
	$invoice->fetch_thirdparty();
/*
	$compte = get_compte($type);

	if (empty($compte))
	{
		dol_syslog(__FUNCTION__.":: erreur configuration définir le compte pour paiement ".$type,LOG_ERR);
		return -1;
	}
*/
	if (empty($modpaye))
	{
		$sql = "SELECT id FROM ".MAIN_DB_PREFIX."c_paiement WHERE code ='PPL' ";
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($obj = $db->fetch_object($resql)) $modpaye = $obj->id;
		}
		if (!$modpaye)
		{
			dol_syslog(__FUNCTION__.":: erreur configuration définir TIA_PPL_MODE ",LOG_ERR);
			return -1;
		}
	}

	dol_syslog(__FUNCTION__."::user ".$user->login, LOG_DEBUG);

	$account_id = $conf->global->TIA_PPL_ACCOUNT;
	if (empty($account_id))
	{
		dol_syslog(__FUNCTION__."::no PPL account ", LOG_ERR);
		return -1;
	}

	dol_syslog(__FUNCTION__.":: infos paypal ".print_r($paypalinfos, true), LOG_DEBUG);

	$amounts["$factid"] = $invoice->total_ttc;

	$db->begin();
	$error = 0;

	// Creation de la ligne paiement
	$paiement = new Paiement($db);
	$paiement->datepaye     = mktime(0, 0, 0, date("m"), date("d"), date("y")); //$fact->date;
	$paiement->amounts      = $amounts;   // Tableau de montant
	$paiement->paiementid   = $modpaye; //clé du mode paiement CB
	$paiement->num_paiement = '';
	$paiement->note         = $langs->trans('PayedByPaypal');

	$paiement_id = $paiement->create($user);

	dol_syslog(__FUNCTION__."::create paiement paiement_id ".$paiement_id, LOG_DEBUG);

	if ($paiement_id > 0)
	{
		if ($conf->banque->enabled)
		{
			$acc = new Account($db);
			$acc->fetch($account_id);
			$label = '('.$langs->trans('CustomerInvoicePayment').')';
			dol_syslog(__FUNCTION__.'::create_paiement facture '.$totalpaiement." ".$paiement->paiementid." ".$label, LOG_DEBUG);
			$bank_line_id = $acc->addline($paiement->datepaye,
					$paiement->paiementid,
					$label,
					$amounts["$factid"],
					$paiement->num_paiement,
					'',
					$user,
					'', //emetteur
					'');  // banque du chèque

			dol_syslog(__FUNCTION__."::create paiement bank_line_id ".$bank_line_id. '  '.$acc->error, LOG_DEBUG);

			if ($bank_line_id > 0)
			{
				$paiement->update_fk_bank($bank_line_id);
				// Mise a jour liens (pour chaque facture concernees par le paiement)
				foreach ($paiement->amounts as $key => $value)
				{
					$facid = $key;
				//	$fac = new Facture($db);
				//	$fac->fetch($facid);
					$acc->add_url_line($bank_line_id,
							$paiement_id,
							DOL_URL_ROOT.'/compta/paiement/'.tia_cardfile().'?id=',
							'(paiement)',
							'payment');
					$acc->add_url_line($bank_line_id,
							$invoice->client->id,
							DOL_URL_ROOT.'/compta/'.tia_cardfile().'?socid=',
							$invoice->client->nom,
							'company');
				}
			} //bank_line_id
			else
			{
				$error++;
				dol_syslog(__FUNCTION__.":: erreur insertion llx_bank  ".$acc_error, LOG_DEBUG);
			}
		} // bank enabled
		else
		{
			dol_syslog(__FUNCTION__.":: activez la gestion des paiements !", LOG_ERR);
			$error ++;
		}

		if ($error == 0)
		{
			dol_syslog("ws_import::create paiement création réussie ", LOG_DEBUG);
			$db->commit();
			// ça s'est bien passé, on solde la facture
			$result = $invoice->set_paid($user);
			return 1;
		}
		else
		{
			dol_syslog("ws_import::create paiement erreur paiement ", LOG_ERR);
			$db->rollback();
			return -1;
		}
	}


}

function tia_get_compte($type)
{
	switch ($type)
	{
		case 'PPL' :
			$ret = $conf->global->TIA_PPL_ACCOUNT;
			break;
		case 'OGN' :
			$ret = $conf->global->OGN_ACCOUNT;
			break;
		default:
			$ret = '';
	}
	return $ret;
}

function tia_cardfile()
{
	if (version_compare(DOL_VERSION, '3.7.0') >= 0) return 'card.php';
	else return 'fiche.php';
}

/**
 * combine le tablea de pdf en un seul fichier
 * @param unknown $filesarray
 * @param unknown $outfile
 */
function merge_pdf($filesarray, $diroutput, $outfile, $type='commande')
{
	//require_once(DOL_DOCUMENT_ROOT."/includes/tcpdf/tcpdf.php");
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');

	global $db, $langs;
	dol_syslog(__FUNCTION__.'::'.print_r($filesarray, true), LOG_DEBUG);
	dol_syslog(__FUNCTION__.'::'.$diroutput.'/'.$outfile, LOG_DEBUG);

	// Define output language (Here it is not used because we do only merging existing PDF)
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	// Create empty PDF
	$pdf=pdf_getInstance();


	if (class_exists('TCPDF'))
	{
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
	}
	$pdf->SetFont(pdf_getPDFFont($langs));

	if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);
	dol_syslog(__FUNCTION__.'::ici 1', LOG_DEBUG);

	foreach($filesarray as $file)
	{
		$pagecount = $pdf->setSourceFile($file);
		for ($i = 1; $i <= $pagecount; $i++)
		{
			dol_syslog(__FUNCTION__.'::fichier '.$file, LOG_DEBUG);
			$tplidx = $pdf->importPage($i);
			$s = $pdf->getTemplatesize($tplidx);
			$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
			$pdf->useTemplate($tplidx);
		}
	}

	// Create output dir if not exists

	dol_mkdir($diroutput);

	// Save merged file
	if ($pagecount)
	{
		$now=dol_now();
		$file=$diroutput.'/'.$outfile.'_'.dol_print_date($now,'dayhourlog').'.pdf';
		$pdf->Output($file,'F');
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));
	}
	else
	{
		//setEventMessage($langs->trans('NoPDFAvailableForChecked'),'errors');
		dol_syslog(__FUNCTION__.":: error No pages in report ", LOG_ERR);
		return -1;
	}

	return 1;
}



/**
 * fusionne les bons de livraison (pdf) et les imprime
 **/

function create_fusion_pdf($listpdf)
{

	require_once(DOL_DOCUMENT_ROOT."/includes/tcpdf/tcpdf.php");
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');
	dol_syslog(basename(__FILE__)."::".__FUNCTION__." ** begin ", LOG_DEBUG);
	if (is_array($list_shipping) && count($list_shipping) > 0)
	{
		global $db, $langs, $conf;


		// liste les fichiers
		$files = array() ;
		$exp = new Expedition ($db);
		foreach($list_shipping as $expedid){
			$exp->fetch($expedid);
			// bug dolibarr	$file = $exp->pdf_filename;
			$file = $conf->expedition->dir_output . "/sending/" .$exp->ref . "/" . $exp->ref.".pdf";
			dol_syslog(basename(__FILE__)."::".__FUNCTION__." : fichier ".$file,LOG_DEBUG);
			if (!file_exists($file))
			{
				dol_syslog(basename(__FILE__)."::".__FUNCTION__." : Fichier $file n'existe pas ", LOG_WARN);
			}
			else
			{
				$files[]=$file;
			}
		}
		/*  TODO deprecated ??
		 // Create empty PDF
		$pdf=new TCPDF('P','mm','A4');
		if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

		if (class_exists('TCPDF'))
		{
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		}
		dol_syslog(basename(__FILE__)."::".__FUNCTION__." : A traiter ".print_r($files,true), LOG_DEBUG);
		$pages = 0;
		foreach($files as $file)
		{
		// Charge un document PDF depuis un fichier.
		$pagecount = $pdf->setSourceFile($file);
		for ($i = 1; $i <= $pagecount; $i++)
		{
		$tplidx = $pdf->importPage($i);
		$s = $pdf->getTemplatesize($tplidx);
		$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
		$pdf->useTemplate($tplidx);
		}
		$pages += $pagecount;
		}

		// Create output dir if not exists
		$diroutputpdf = $conf->logistique->dir_temp;
		dol_mkdir($diroutputpdf);

		// Save merged file
		$filename=strtolower(dol_sanitizeFileName($langs->transnoentities("mergetBL")));
		dol_syslog(basename(__FILE__)."::".__FUNCTION__." : pages = ".$pages, LOG_DEBUG);
		if ($pages)
		{
		$file=$diroutputpdf.'/'.$filename.'.pdf';
		$pdf->Output($file,'F');
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));

		// affichage
		clearstatcache();

		$attachment=true;
		if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;
		$type= dol_mimetype($filename); //'application/octet-stream';

		if ($encoding)   header('Content-Encoding: '.$encoding);
		// if ($type)       header('Content-Type: '.$type);
		header('Content-Type: Application/pdf');
		if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
		else header('Content-Disposition: inline; filename="'.$filename.'"');

		// Ajout directives pour resoudre bug IE
		header('Cache-Control: Public, must-revalidate');
		header('Pragma: public');

		readfile($file);
		exit;
		}
		else
		{
		dol_syslog(basename(__FILE__)."::".__FUNCTION__." : ".$langs->trans('NoPDFAvailableForChecked'), LOG_INFO);
		}
		*/
	}
	else dol_syslog(basename(__FILE__)."::".__FUNCTION__." : list_shipping is empty", LOG_WARN);
	dol_syslog(basename(__FILE__)."::".__FUNCTION__." ** end ", LOG_DEBUG);
	return 1;

}

/**
 *
 * @param unknown $emails liste emails
 * @param unknown $subject sujet (sans carac html)
 * @param unknown $content message
 * @param unknown $joinedfiles fichiers à joindre (chemin absolu)
 * @return number
 */
function tia_sendnotif($emails, $subject, $content, $joinedfiles = array(), $from = "")
{
	global $db, $langs, $conf, $user;

	$errors = array();

	if (empty($emails)) return 0 ;


	$message .= $content;

	$filenames = array();
	$mimetypes = array();
	foreach($joinedfiles as $fich)
	{
		$filenames[] = basename($fich);
		$mimetypes[] = mime_content_type($fich);
	}


	$sendtocc = '';
	$deliveryreceipt = '';
	if (empty($from) ) $from = $conf->global->MAILING_EMAIL_FROM;
	if (empty($from))
	{
		dol_syslog(__FUNCTION__.":: no valid sender from ".$from ,LOG_ERR);
		return -1;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

	$listeemails = explode(',', $emails);
	foreach ($listeemails as $email)
	{
		$mailfile = new CMailFile ( $subject, $email, $from, $message, $joinedfiles, $mimetypes, $filenames, $sendtocc, '', $deliveryreceipt, 1 );
		if ($mailfile->error)
		{
			$mesg = 'Error ' . $mailfile->error ;
		}
		else
		{
			$result = $mailfile->sendfile ();
			if ($result) {
				$mesg = $langs->trans ( 'MailSuccessfulySent', $mailfile->getValidAddress ( $from, 2 ), $mailfile->getValidAddress ( $email, 2 ) ); // Must not contains "
			}
			else
			{
				if ($mailfile->error)
				{
					$mesg .= $langs->trans ( 'ErrorFailedToSendMail', $from, $email );
					$mesg .=  $mailfile->error;
				}
				else
				{
					$mesg .= 'No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
				}
				dol_syslog(__FUNCTION__.":: No Mail send ".$mesg, LOG_WARNING);
			}
		}
		unset($mailfile);
	}

	return 1;
}

/**
 * ajoute un contact à un mailing
 * @param unknown $idmailing
 * @param unknown $idcontact
 * @return number
*/
function tia_add_contact2mailing($idmailing, $idcontact)
{
	global $db, $langs, $conf, $user;

	$errors = array();

	if (empty($idmailing) || empty($idcontact))
	{
		dol_syslog(__FUNCTION__.":: mailing or contact undefined ", LOG_ERR);
		$errors[]="mailing or contact undefined";
		//setEventMessages($langs->trans("Cmp_Error"), $errors, "errors");
		return -1;
	}

	require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
	require_once DOL_DOCUMENT_ROOT."/comm/mailing/class/mailing.class.php";

	$mailing = new Mailing($db);
	if ($mailing->fetch($idmailing) < 0)
	{
		dol_syslog(__FUNCTION__.":: mailing $idmailing not found ".$mailing->errorsToString(), LOG_ERR);
		$errors[] = "mailing $idmailing not found";
	}
	if ($mailing->statut > 2)
	{
		dol_syslog(__FUNCTION__.":: mailing $idmailing completely sent ", LOG_ERR);
		$errors[] = "mailing $idmailing completely sent";
	}
	//TODO si email validé faut-il ajouter ?

	$contact = new Contact($db);
	if ($contact->fetch($idcontact) < 0)
	{
		dol_syslog(__FUNCTION__.":: contact $idcontact not found ".$contact->errorsToString(), LOG_ERR);
		$errors[] = "contact $idcontact not found";
	}
	if (empty($contact->email))
	{
		dol_syslog(__FUNCTION__.":: contact $idcontact has no email ", LOG_ERR);
		$errors[] = "contact $idcontact has no email";
	}

	if (count($errors))
	{
		setEventMessages($langs->trans("Cmp_Error"), $errors, "errors");
		return -1;
	}

	// si contact à ne pas contacter
	if ($contact->no_email)
	{
		dol_syslog(__FUNCTION__.":: contact $idcontact no email", LOG_WARNING);
		return 1;
	}

	$db->begin();
	// affectation
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_cibles";
	$sql.= " (fk_mailing,";
	$sql.= " fk_contact,";
	$sql.= " lastname, firstname, email, other, source_url, source_id,";
	$sql.= " tag,";
	$sql.= " source_type)";
	$sql.= " VALUES (".$idmailing.",";
	$sql.= 	$idcontact.",";
	$sql.= "'".$db->escape($contact->lastname)."',";
	$sql.= "'".$db->escape($contact->firstname)."',";
	$sql.= "'".$db->escape($contact->email)."',";
	$sql.= "'',"; // other
	$sql.= "'".$db->escape($contact->getNomUrl())."',";
//	$sql .= "'',";
	$sql.=  $idcontact .","; // source
	$sql .= "'".$db->escape(dol_hash($contact->email.';'.$contact->lastname.';'.$idmailing.';'.$conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY))."',";
	$sql .= "'".$db->escape('contact')."')";
	dol_syslog(__FUNCTION__."::sql $sql", LOG_DEBUG);

	$result=$db->query($sql);
	if ($result)
	{
		$lastid = $db->last_insert_id(MAIN_DB_PREFIX."mailing_cibles");
		$mailing->nbemail +=1;
	}
	else
	{
		if ($db->errno() != 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			// Si erreur autre que doublon
			dol_syslog(__FUNCTION__."::sql $sql ".$this->db->error(), LOG_ERR);
			$errors[]="Error sql $sql";
		}
		//else dol_syslog($targetarray['email'].'DB_ERROR_RECORD_ALREADY_EXISTS', LOG_WARNING);
	}

	// maj du mailing
	$sql = "UPDATE ".MAIN_DB_PREFIX."mailing";
	$sql .= " SET nbemail = ".$mailing->nbemail." WHERE rowid = ".$mailing->id;
	if (!$db->query($sql))
	{
		dol_syslog(__FUNCTION__."::sql $sql ".$this->db->error(), LOG_ERR);
		$errors[]="Error sql $sql";
	}

	if (count($errors))
	{
		$db->rollback();
		setEventMessages($langs->trans("Cmp_Error"), $errors, "errors");
		return -1;
	}
	else $db->commit();

	return $lastid;
}