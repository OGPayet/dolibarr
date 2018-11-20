<?php
/*
 * Copyright (C) 2012 Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file mailchimp/core/triggers/interface_90_ModMailchimp_mailchimp.class.php
 * \ingroup mailchimp
 * \brief Trigger fired Dolibarr catch by mailchimp
 */

/**
 * Class of triggers Mailchimp
 */
class InterfaceMailchimp {
	var $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "mailchimp";
		$this->description = "mailchimp trigger";
		$this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
	}

	/**
	 * Return name of trigger file
	 *
	 * @return string Name of trigger file
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * Return description of trigger file
	 *
	 * @return string Description of trigger file
	 */
	function getDesc() {
		return $this->description;
	}

	/**
	 * Return version of trigger file
	 *
	 * @return string Version of trigger file
	 */
	function getVersion() {
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development')
			return $langs->trans("Development");
		elseif ($this->version == 'experimental')
			return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr')
			return DOL_VERSION;
		elseif ($this->version)
			return $this->version;
		else
			return $langs->trans("Unknown");
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 * @param string $action code
	 * @param Object $object
	 * @param User $user user
	 * @param Translate $langs langs
	 * @param conf $conf conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function run_trigger($action, &$object, &$user, &$langs, &$conf) {

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// Users
		if ($action == 'MAILING_TARGET_STATUS_UPDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . " id=" . $object->id);

			/*dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/mailchimp/class/dolmailchimp.class.php');

			// Get segment names
			if (! empty($object->mailchimp_listid)) {
				$mailchimp = new DolMailchimp($this->db);
				$result = $mailchimp->getListDestinaries(array (
						'list_id' => $object->mailchimp_listid
				));
				if ($result < 0) {
					$this->error = $mailchimp->errors;
					dol_syslog(get_class($this) . "::Trigger MAILING_TARGET_STATUS_UPDATE Error : " . $this->error, LOG_ERR);
					return - 1;
				}
			}*/

			return 0;
		}

		if (in_array($action, array('CATEGORY_CREATE', 'CATEGORY_MODIFY', 'CATEGORY_DELETE')))
		{
			dol_include_once('/mailchimp/lib/mailchimp.lib.php');
			$langs->load('mailchimp@mailchimp');

			switch ($action) {
				case 'CATEGORY_CREATE':
				case 'CATEGORY_MODIFY':
					$r = setMailChimpInfoFromCategorie($object);
					//echo $r;exit;
					break;

				case 'CATEGORY_DELETE':
					$r = deleteMailChimpInfoFromCategorie($category);
					break;

				default:
					$r = 1;
					break;
			}
			if(!$r){
				if (function_exists('setEventMessages')) setEventMessages($langs->trans('MailChimp_error_can_not_update_fields'), array(), 'errors');
				else setEventMessage($langs->trans('MailChimp_error_can_not_update_fields'), 'errors');
			}
		}
		if (in_array($action, array('CONTACT_ENABLEDISABLE', 'CONTACT_DELETE')))
		{
				define ('INC_FROM_DOLIBARR',true);

				dol_include_once('/mailchimp/class/dolmailchimp.class.php');
				$mailchimp= new DolMailchimp($this->db);

			switch ($action) {
				case 'CONTACT_DELETE':

					$list = $mailchimp->getListForEmail($object->email);

					foreach ($list['lists'] as &$l) {
						$mailchimp->deleteEmailFromList($l['id'],array('email' => $object->email));
					}
					break;
				case 'CONTACT_ENABLEDISABLE':

					if($object->statut == 0){
						//UNSUSCRIBE
						$list = $mailchimp->getListForEmail($object->email);

						foreach ($list['lists'] as &$l) {

							$mailchimp->unsubscribeEmail($l['id'],$object->email);
						}
					}else if($object->statut == 1){
						//SUSCRIBE VERSION TEST
						//Impossible de les réinscrire message d'erreur : quentinashburst@yahoo.fr has unsubscribed, and cannot be resubscribed by you. To prevent false spam complaints, they'll need to resubscribe of their own free will.
						//$idList = '300710e344';
						//$mailchimp->subscribeEmail($idList, array('email' => $object->email));
					}

					break;
				default:

					break;
			}
		}


		if($action == 'CONTACT_MODIFY'){
			if(strcmp ( $object->email , $object->oldcopy->email  ) != 0 ){
				//TODO Faire pop un message d'alerte pour prévenir que l'adresse antérieure est abonnée
			}
		}
		if (in_array($action, array('CONTACT_ENABLEDISABLE', 'CONTACT_DELETE')))
		{
			define ('INC_FROM_DOLIBARR',true);

			dol_include_once('/mailchimp/class/dolmailchimp.class.php');
			$mailchimp= new DolMailchimp($this->db);

			switch ($action) {
				case 'CONTACT_DELETE':

					$list = $mailchimp->getListForEmail($object->email);

					foreach ($list as &$l) {
						$mailchimp->deleteEmailFromList($l['id'],array('email' => $object->email));
					}
					break;
				case 'CONTACT_ENABLEDISABLE':

					if($object->statut == 0){
						//UNSUSCRIBE
						$list = $mailchimp->getListForEmail($object->email);

						foreach ($list as &$l) {

							$mailchimp->unsubscribeEmail($l['id'],array('email' => $object->email));
						}
					}else if($object->statut == 1){
						//SUSCRIBE VERSION TEST
						//Impossible de les réinscrire message d'erreur : quentinashburst@yahoo.fr has unsubscribed, and cannot be resubscribed by you. To prevent false spam complaints, they'll need to resubscribe of their own free will.
						//$idList = '300710e344';
						//$mailchimp->subscribeEmail($idList, array('email' => $object->email));
					}

					break;
				default:

					break;
			}
		}
		return 0;
	}

	function runTrigger($action, $object, $user, $langs, $conf) {
		//For 8.0 remove warning
		$result=$this->run_trigger($action, $object, $user, $langs, $conf);
		return $result;
	}

}