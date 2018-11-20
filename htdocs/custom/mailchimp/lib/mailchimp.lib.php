<?php
/* <MailChimp connector>
 * Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/mailchimp.lib.php
 *	\ingroup	mailchimp
 */

function mailchimpAdminPrepareHead() {
	global $langs, $conf;

	$langs->load("mailchimp@mailchimp");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/mailchimp/admin/admin_mailchimp.php", 1);
	$head[$h][1] = $langs->trans("SettingsMailChimp");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/mailchimp/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'mailchimp');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'mailchimp', 'remove');

	return $head;
}

/**
 * TODO remove because function is useless and unuse
 *
 * @param unknown $fk_category
 * @return NULL[]
 */
function getEmailToRemove($fk_category)
{
	global $db, $conf;

	$sql = 'SELECT DISTINCT c.email
			FROM '.MAIN_DB_PREFIX.'socpeople c
			INNER JOIN '.MAIN_DB_PREFIX.'categorie_contact cc ON (c.rowid = cc.fk_socpeople)
			WHERE cc.fk_categorie = '. (int) $fk_category.'
			AND c.entity = '.$conf->entity.'
			AND (c.statut = 0 OR c.no_email = 1)';
	dol_syslog(__FILE__. " function::getEmailToRemove SQL = " .$sql, LOG_DEBUG);

	$TEmail = array();
	$resql = $db->query($sql);
	if ($resql)
	{
		while ($row = $db->fetch_object($resql))
		{
			if (isValidEmail($row->email)) $TEmail[] = $row->email;
		}
	}

	return $TEmail;
}

/**
 *
 * @param unknown $fk_category
 * @return NULL[]
 */
function getEmailToAdd($fk_category, $TEntity)
{
	global $db, $conf;

	$sql = 'SELECT DISTINCT c.email
			FROM '.MAIN_DB_PREFIX.'socpeople c
			INNER JOIN '.MAIN_DB_PREFIX.'categorie_contact cc ON (c.rowid = cc.fk_socpeople)
			WHERE cc.fk_categorie = '.(int) $fk_category.'
			AND c.entity IN ('.implode(',', $TEntity).')
			AND c.statut = 1
			AND c.no_email = 0';
	dol_syslog(__FILE__. " function::getEmailToAdd SQL = " .$sql, LOG_DEBUG);

	$TEmail = array();
	$resql = $db->query($sql);
	if ($resql)
	{
		while ($row = $db->fetch_object($resql))
		{
			if (isValidEmail($row->email)) $TEmail[] = strtolower($row->email);
		}
	}

	return $TEmail;
}

/**
 *
 * @param unknown $fk_categorie
 * @return Object[]|boolean
 */
function getMailChimpInfoFromCategorie($fk_categorie)
{
	global $db, $conf;

	$sql = 'SELECT mailchimp_listid, mailchimp_segmentid FROM '.MAIN_DB_PREFIX.'mailchimp_category_contact WHERE fk_category = ' . (int) $fk_categorie. ' AND entity = ' . $conf->entity;
	dol_syslog(__FILE__. " function::getMailChimpInfoFromCategorie SQL = " .$sql, LOG_DEBUG);

	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0)
	{
		$TInfo = array();
		while ($row = $db->fetch_object($resql)) $TInfo[] = $row;

		return $TInfo;
	}

	return false;
}

/**
 *
 * @param unknown $category
 * @return number
 */
function setMailChimpInfoFromCategorie(&$category)
{
	global $db, $conf;

	$r = deleteMailChimpInfoFromCategorie($category);
	if ($r)
	{
		// TODO à faire évoluer plus tard en tableau avec un multiselect côté html
		$mailchimp_listid = GETPOST('mailchimp_listid');
		$mailchimp_segmentid = GETPOST('mailchimp_segmentid');

		// Si aucune association alors pas besoin de faire d'insert
		if (empty($mailchimp_listid) && empty($mailchimp_segmentid)) return 1;

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'mailchimp_category_contact (
			entity,
			mailchimp_listid,
			mailchimp_segmentid,
			fk_category)
		VALUES (
			'.$conf->entity.',
			"'.$db->escape($mailchimp_listid).'",
			"'.$db->escape($mailchimp_segmentid).'",
			'.$category->id.'
		)';

		dol_syslog(__FILE__. " function::setMailChimpInfoFromCategorie SQL = " .$sql, LOG_DEBUG);

		$resql = $db->query($sql);

		if ($resql) return 1;
	}

	return 0;
}

/**
 *
 * @param unknown $category
 * @return number
 */
function deleteMailChimpInfoFromCategorie(&$category)
{
	global $db, $conf;

	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'mailchimp_category_contact WHERE fk_category = ' . (int) $category->id . ' AND entity = ' . $conf->entity;
	dol_syslog(__FILE__. " function::deleteMailChimpInfoFromCategorie SQL = " .$sql, LOG_DEBUG);

	$resql = $db->query($sql);

	if ($resql) return 1;
	else return 0;
}

/**
 *
 * @param unknown $fk_category
 * @param unknown $mailchimp_listid
 * @param number $mailchimp_segmentid
 * @param string $fromCron
 * @return boolean|NULL|unknown
 */
function _doUpdateMailchimpList($TEntity, $fk_category, $mailchimp_listid, $mailchimp_segmentid = 0, $fromCron = false)
{
	global $db,$langs;

	if (empty($fk_category) || empty($mailchimp_listid)) return false;

	$mailchimp = new DolMailchimp($db);

	if ($fromCron)
	{
		echo 'fk_category = '.$fk_category."\n";
		echo 'mailchimp_listid = '.$mailchimp_listid."\n";
		echo 'mailchimp_segmentid = '.$mailchimp_segmentid."\n";
	}

	// Emails à ajouter depuis Dolibarr
	$TEmailToAdd = getEmailToAdd($fk_category, $TEntity);

	// Emails déjà présent sur Mailchimp
	$mailchimp->mailchimp_listid = $mailchimp_listid;
	$mailchimp->mailchimp_segmentid = $mailchimp_segmentid;

	if (empty($mailchimp->mailchimp_segmentid)) $mailchimp->getEmailList(); // @see $mailchimp->email_lines after that
	else
	{
		// Je stock les emails présent dans la liste juste avant de travailler avec la liste des emails du segment, car si j'ajoute un email dans le segment qui n'est pas présent dans la liste, l'API n'ajoute pas l'adresse
		$mailchimp->getEmailList();
		$TEmailInList = $mailchimp->email_lines;

		$mailchimp->getEmailSegment();
	}

	$TEmailAlreadyExists = &$mailchimp->email_lines;

	// Tableau qui contiendra un diff des emails à ajouter et/ou supprimer
	$TEmailDiff = array('add' => array(), 'del' => array());
	foreach ($TEmailToAdd as $email)
	{
		// Email qui n'existe pas sur Mailchimp => donc ajout
		if (!in_array($email, $TEmailAlreadyExists))
		{
			$TEmailDiff['add'][] = $email;
			//if ($fromCron) echo '[ADD] '.$email."\n";
		}
	}

	if ($fromCron) echo '[COUNT ADD] = '.count($TEmailDiff['add'])."\n\n";

	// [UNIQUEMENT SUR CRON] Si l'action est déclenché depuis l'interface alors on ne gère que l'ajout et surtout pas la suppression car une même catégorie peut être associé à la même list et segment depuis une autre entité avec un listing d'email différent
	if ($fromCron)
	{
		foreach ($TEmailAlreadyExists as $email)
		{
			// Email sur Mailchimp qui n'est pas présent dans la liste d'ajout de Dolibarr => donc suppression
			if (!in_array($email, $TEmailToAdd))
			{
				$TEmailDiff['del'][] = $email;
				//if ($fromCron) echo '[DEL] '.$email."\n";
			}
		}
		if ($fromCron) echo '[COUNT DEL] = '.count($TEmailDiff['del'])."\n\n";

		// Update d'un segment mais il faut aussi ce préocupper des emails présents dans la liste et non dans le segment
		if (!empty($TEmailInList))
		{
			$TEmailToRemoveFromList = array();
			foreach ($TEmailInList as $email)
			{
				if (!in_array($email, $TEmailToAdd))
				{
					$TEmailToRemoveFromList[] = $email;
				}
			}
		}
	}

	// Check email, si ajout dans segment je doit vérifier que l'email est bien présent dans la liste (si l'email n'est pas dans la liste je doit l'ajouter avant de l'ajouter au segment)
	$TEmailToAddInListBeforeSegment = array();
	foreach ($TEmailDiff['add'] as $email)
	{
		if (!in_array($email, $TEmailInList)) $TEmailToAddInListBeforeSegment[] = $email;
	}


	/**
	 *
	 * ATTENTION : 	si des adresses mails reviennent souvent en ajout mais qu'elles ne sont finalement pas
	 * 				ajouté sur la liste et/ou segment sur la plat forme Mailchimp, il est probable que
	 * 				l'email en question est "cleaned" ou "unsubcribed", donc ne peut pas être ajouté de
	 * 				manière classic
	 *
	 */



	if (!empty($TEmailToAddInListBeforeSegment))
	{
		if ($fromCron) {
			echo "\n".'[ADD LIST] Tableau des emails qui seront ajoutés dans la liste avant le segment :';
			var_dump($TEmailToAddInListBeforeSegment);
		}
		$mailchimp->addEmailToList($mailchimp->mailchimp_listid, $TEmailToAddInListBeforeSegment);
		unset($TEmailToAddInListBeforeSegment);
	}

	if (!empty($TEmailDiff['add']))
	{
		if (empty($mailchimp->mailchimp_segmentid)){
			if ($fromCron) {
				echo "\n".'[ADD LIST] Tableau des emails qui seront ajoutés dans la liste :';
				var_dump($TEmailDiff['add']);
			}
			$r = $mailchimp->addEmailToList($mailchimp->mailchimp_listid, $TEmailDiff['add']);
		}
		else {
			if ($fromCron) {
				echo "\n".'[ADD SEGMENT] Tableau des emails qui seront ajoutés dans le segment :';
				var_dump($TEmailDiff['add']);
			}
			$r = $mailchimp->updateSegment($mailchimp->mailchimp_listid, $mailchimp->mailchimp_segmentid, $TEmailDiff['add']);
		}

		if ($r < 0)
		{
			if ($fromCron) exit('[CATCH ERROR ON ADD] = '.$mailchimp->error);
			else {
				if (function_exists('setEventMessages')) setEventMessages($langs->trans('mailchimp_error_syn', $mailchimp->error), array(), 'errors');
				else setEventMessage($langs->trans('mailchimp_error_syn', $mailchimp->error), 'errors');
			}
		}

		//unset($TEmailDiff['add']);
	}

	if (!empty($TEmailDiff['del']))
	{
		if (empty($mailchimp->mailchimp_segmentid)) {
			if ($fromCron) {
				echo "\n".'[DEL LIST] Tableau des emails qui seront supprimées de la liste :';
				var_dump($TEmailDiff['del']);
			}
			$r = $mailchimp->deleteEmailFromList($mailchimp->mailchimp_listid, $TEmailDiff['del']);
		}
		else {
			if ($fromCron) {
				echo "\n".'[DEL SEGMENT] Tableau des emails qui seront supprimées du segment PUIS de la list :';
				var_dump($TEmailDiff['del']);
			}
			$r = $mailchimp->deleteEmailFromSegment($mailchimp->mailchimp_listid, $mailchimp->mailchimp_segmentid, $TEmailDiff['del']);
			if ($r >= 0 && !empty($TEmailToRemoveFromList)) $r = $mailchimp->deleteEmailFromList($mailchimp->mailchimp_listid, $TEmailToRemoveFromList);
		}

		if ($r < 0)
		{
			if ($fromCron) exit('[CATCH ERROR ON DEL] = '.$mailchimp->error);
			else {
				if (function_exists('setEventMessages')) setEventMessages($langs->trans('mailchimp_error_syn', $mailchimp->error), array(), 'error');
				else setEventMessage($langs->trans('mailchimp_error_syn', $mailchimp->error), 'error');
			}
		}

		//unset($TEmailDiff['del']);
	}

	return $TEmailDiff;
}
