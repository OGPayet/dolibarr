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
 * 	\defgroup	mailchimp	mailchimp module
 * 	\brief		mailchimp module descriptor.
 * 	\file		core/modules/modMailchimp.class.php
* 	\ingroup	mailchimp
 * 	\brief		Description and activation file for module mailchimp
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module MyModule
 */
class modMailchimp extends DolibarrModules
{

	/**
	 * 	Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * 	@param	DoliDB		$db	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		$this->numero = 103020;

		$this->rights_class = 'mailchimp';

		$this->family = "other";

		$this->name = preg_replace('/^mod/i', '', get_class($this));

		$this->description = "Mailchimp Connector";

		$this->version = '1.17.1';

		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		$this->special = 1;

		$this->picto = 'mailchimp@mailchimp'; // mypicto@mymodule

		$this->module_parts = array('hooks' => array('mailingcard',
													'productstatssupplyorder',
													'productstatsorder',
													'productstatscontract',
													'productstatssupplyinvoice',
													'productstatsinvoice',
													'productstatspropal',
													'categorycard',
													'contactcard'),
									'triggers' => 1


		);

		$this->dirs = array();

		$this->config_page_url = array("admin_mailchimp.php@mailchimp");

		$this->depends = array("modMailing");

		$this->requiredby = array();

		$this->phpmin = array(5, 3);

		$this->need_dolibarr_version = array(3, 8);

		$this->langfiles = array("mailchimp@mailchimp"); // langfiles@mymodule

		$this->const = array(
				0=>array(
					'MAILCHIMP_MAIL_SENDMODE_STD',
					'chaine',
					'',
					'Normal Send mode',
					0,
					'current',
					0
				),
				1=>array(
					'MAILCHIMP_SMTP_PORT_STD',
					'chaine',
					'',
					'Normal SMTP port',
					0,
					'current',
					0
				),
				2=>array(
					'MAILCHIMP_MAIL_SMTP_SERVER_STD',
					'chaine',
					'',
					'Normal SMTP server',
					0,
					'current',
					0
				),
				3=>array(
					'MAILCHIMP_MAIL_SMTPS_ID_STD',
					'chaine',
					'',
					'Normal SMTP identification credential id',
					0,
					'current',
					0
				),
				4=>array(
					'MAILCHIMP_MAIL_SMTPS_PW_STD',
					'chaine',
					'',
					'Normal SMTP identification credential  password',
					0,
					'current',
					0
				),
				5=>array(
					'MAILCHIMP_MAIL_EMAIL_TLS_STD',
					'chaine',
					'',
					'Normal SMTP server use of TSL(SSL)',
					0,
					'current',
					0
				),
				6=>array(
					'MAILCHIMP_MAIL_SENDMODE',
					'chaine',
					'smtps',
					'MailChimp Send mode',
					0,
					'current',
					1
				),
				7=>array(
					'MAILCHIMP_SMTP_PORT',
					'chaine',
					'587',
					'MailChimp SMTP port',
					0,
					'current',
					1
				),
				8=>array(
					'MAILCHIMP_MAIL_SMTP_SERVER',
					'chaine',
					'smtp.mandrillapp.com',
					'MailChimp SMTP server',
					0,
					'current',
					1
				),
				9=>array(
					'MAILCHIMP_MAIL_SMTPS_ID',
					'chaine',
					'',
					'MailChimp SMTP identification credential id',
					0,
					'current',
					1
				),
				10=>array(
					'MAILCHIMP_MAIL_SMTPS_PW',
					'chaine',
					'',
					'MailChimp SMTP identification credential  password',
					0,
					'current',
					1
				),
				11=>array(
					'MAILCHIMP_MAIL_EMAIL_TLS',
					'chaine',
					'0',
					'Normal SMTP server use of TSL(SSL)',
					0,
					'current',
					1
				),
				12=>array(
					'MAILCHIMP_ACTIVE',
					'chaine',
					'0',
					'Module is active',
					0,
					'current',
					1
				),

				13=>array(
					'MAILCHIMP_MAIL_EMAIL_FROM',
					'chaine',
					'',
					'MailChimp default senders',
					0,
					'current',
					0
				),
				14=>array(
					'MAILCHIMP_MAIL_EMAIL_FROM_STD',
					'chaine',
					'',
					'Normal default mail sender',
					0,
					'current',
					0
				),
				15=>array(
					'MAILCHIMP_MANDRILL_API_KEY',
					'chaine',
					'',
					'Mandrill API Key',
					0,
					'current',
					1
				),
				16=>array(
					'MAILCHIMP_ACTIVE_MAILING_ONLY',
					'chaine',
					'0',
					'Module is active only for mailing',
					0,
					'current',
					1
				),
				17=>array(
					'MAILCHIMP_SAVE_ACTIVITY_LOCALY',
					'chaine',
					'0',
					'MSave into dolibarr db the contact activities',
					0,
					'current',
					1
				),
				18=>array(
					'MAILCHIMP_NB_MAX_LIST',
					'chaine',
					'100',
					'Nb max of list campaigns',
					0,
					'current',
					1
				)
		);

		// Export
		$r ++;
		$this->export_code[$r] = $this->rights_class . '_' . $r;
		$this->export_label[$r] = 'ExportDataset_MailinsDestStatus';
		$this->export_icon[$r] = 'bill';
		$this->export_permission[$r] = array (
				array (
						"mailchimp",
						"read"
				)
		);

		$this->export_fields_array[$r] = array (
				'm.rowid' => 'Id',
				'm.titre' => 'Title',
				'm.sujet' => 'Subject',
				'm.email_from' => 'EmailFrom',
				'm.date_creat' => 'DateCreate',
				'm.date_valid' => 'DateValid',
				'm.date_envoi' => 'DateSend',
				'mc.lastname' => 'LastName',
				'mc.firstname' => 'FirstName',
				'mc.email' => 'Email',
				'CASE WHEN mc.statut=-1 THEN \'Error\'
				WHEN mc.statut=0 THEN \'NotSent\'
				WHEN mc.statut=1 THEN \'Sent\'
				WHEN mc.statut=2 THEN \'Open\'
				WHEN mc.statut=3 THEN \'Unsucscribe\'
				WHEN mc.statut=4 THEN \'Click\'
				WHEN mc.statut=5 THEN \'HardBounce\'
				WHEN mc.statut=6 THEN \'SoftBounce\'
				END as statut' => 'Status'
		)
		;
		$this->export_TypeFields_array[$r] = array (
				'm.rowid' => "Text",
				'm.date_valid' => "Date",
				'm.date_envoi' => "Date"
		);
		$this->export_entities_array[$r] = array (
				'm.rowid' => "Id",
				'm.titre' => 'Mailing',
				'm.sujet' => 'Mailing',
				'm.email_from' => 'Mailing',
				'm.date_creat' => 'Mailing',
				'm.date_valid' => 'Mailing',
				'm.date_envoi' => 'Mailing',
				'mc.lastname' => 'Target',
				'mc.firstname' => 'Target',
				'mc.email' => 'Target',
				'CASE WHEN mc.statut=-1 THEN \'Error\'
				WHEN mc.statut=0 THEN \'NotSent\'
				WHEN mc.statut=1 THEN \'Sent\'
				WHEN mc.statut=2 THEN \'Open\'
				WHEN mc.statut=3 THEN \'Unsucscribe\'
				WHEN mc.statut=4 THEN \'Click\'
				WHEN mc.statut=5 THEN \'HardBounce\'
				WHEN mc.statut=6 THEN \'SoftBounce\'
				END as statut' => 'Target'
		);

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = 'FROM llx_mailing as m INNER JOIN llx_mailing_cibles as mc ON m.rowid=mc.fk_mailing';

		// Array to add new pages in new tabs
		$this->tabs = array(
				'emailing:+tabMailChimpSending:MailChimpSending:mailchimp@mailchimp:$user->rights->mailing->creer:/mailchimp/mailchimp/mailchimp.php?id=__ID__',
				'emailing:-targets',
				'emailing:+tabMailChimpTarget:MailChimpTarget:mailchimp@mailchimp:$user->rights->mailing->creer:/mailchimp/mailchimp/target.php?id=__ID__',
				'contact:+tabMailChimpActivites:Module103020Name:mailchimp@mailchimp:$user->rights->mailchimp->read:/mailchimp/mailchimp/contact_activites.php?id=__ID__'
		);

		if (! isset($conf->mailchimp->enabled)) {
			$conf->mailchimp = (object) array();
			$conf->mailchimp->enabled = 0;
		}
		$this->dictionnaries = array();

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // Boxes list
		$r = 0;

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'read';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'write';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'Autoriser la synchronisation manuelle';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'sync';
		$r++;

		if ((float)DOL_VERSION < 3.7 ) { // DOL_VERSION < 3.7
			$is_doli_37_or_more = false;
		} else {
			$is_doli_37_or_more = true;
		}

		// Main menu entries
		$this->menus = array(); // List of menus to add
		$r = 0;

		$this->menu[$r]=array(	'fk_menu'=>0,
		'type'=>'top',
		'titre'=>'Module103020Name',
		'mainmenu'=>'mailchimp',
		'leftmenu'=>'1',
		'url'=>'/mailchimp/index.php',
		'langs'=>'mailchimp@mailchimp',
		'position'=>100,
		'enabled'=>'1',
		'perms'=>'$user->rights->mailchimp->read',
		'target'=>'',
		'user'=>2
		);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=mailchimp',
		'type'=>'left',
		'titre'=>'Module103020Name',
		'leftmenu'=>'MailChimpMenu',
		'url'=>'/mailchimp/mailchimp/destinaries_list.php',
		'langs'=>'mailchimp@mailchimp',
		'position'=>100,
		'enabled'=>'$user->rights->mailchimp->read',
		'perms'=>'$user->rights->mailchimp->read',
		'target'=>'',
		'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=mailchimp,fk_leftmenu=MailChimpMenu',
		'type'=>'left',
		'titre'=>'MailChimpDestList',
		'url'=>'/mailchimp/mailchimp/destinaries_list.php',
		'langs'=>'mailchimp@mailchimp',
		'position'=>101,
		'enabled'=>'$user->rights->mailchimp->read',
		'perms'=>'$user->rights->mailchimp->read',
		'target'=>'',
		'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=mailchimp,fk_leftmenu=MailChimpMenu',
		'type'=>'left',
		'titre'=>'NewMailing',
		'url'=>$is_doli_37_or_more ? '/comm/mailing/card.php?leftmenu=mailing&action=create' : '/comm/mailing/fiche.php?leftmenu=mailing&action=create',
		'langs'=>'mails',
		'position'=>102,
		'enabled'=>'$user->rights->mailchimp->write',
		'perms'=>'$user->rights->mailchimp->write',
		'target'=>'',
		'user'=>2);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=mailchimp,fk_leftmenu=MailChimpMenu',
		'type'=>'left',
		'titre'=>'MailChimpListCampaign',
		'url'=>$is_doli_37_or_more ? '/comm/mailing/list.php' : '/comm/mailing/liste.php',
		'langs'=>'mailchimp@mailchimp',
		'position'=>102,
		'enabled'=>'$user->rights->mailchimp->write',
		'perms'=>'$user->rights->mailchimp->write',
		'target'=>'',
		'user'=>2);
		$r++;

	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		$sql = array(
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SENDMODE.'\' WHERE name=\'MAILCHIMP_MAIL_SENDMODE_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTP_PORT.'\' WHERE name=\'MAILCHIMP_SMTP_PORT_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTP_SERVER.'\' WHERE name=\'MAILCHIMP_MAIL_SMTP_SERVER_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTPS_ID.'\' WHERE name=\'MAILCHIMP_MAIL_SMTPS_ID_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTPS_ID.'\' WHERE name=\'MAILCHIMP_MAIL_SMTPS_ID_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTPS_PW.'\' WHERE name=\'MAILCHIMP_MAIL_SMTPS_PW_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_EMAIL_TLS.'\' WHERE name=\'MAILCHIMP_MAIL_EMAIL_TLS_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_EMAIL_FROM.'\' WHERE name=\'MAILCHIMP_MAIL_EMAIL_FROM_STD\''
		);

		$result = $this->loadTables();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		global $conf;

		$sql = array(
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_MAIL_SENDMODE_STD.'\' WHERE name=\'MAIN_MAIL_SENDMODE\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_SMTP_PORT_STD.'\' WHERE name=\'MAIN_MAIL_SMTP_PORT\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_MAIL_SMTP_SERVER_STD.'\' WHERE name=\'MAIN_MAIL_SMTP_SERVER\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_MAIL_SMTPS_ID_STD.'\' WHERE name=\'MAIN_MAIL_SMTPS_ID\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_MAIL_SMTPS_ID_STD.'\' WHERE name=\'MAIN_MAIL_SMTPS_ID\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_MAIL_SMTPS_PW_STD.'\' WHERE name=\'MAIN_MAIL_SMTPS_PW\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_MAIL_EMAIL_TLS_STD.'\' WHERE name=\'MAIN_MAIL_EMAIL_TLS\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILCHIMP_MAIL_EMAIL_FROM_STD.'\' WHERE name=\'MAIN_MAIL_EMAIL_FROM\''
		);

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /mymodule/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/mailchimp/sql/');
	}
}
