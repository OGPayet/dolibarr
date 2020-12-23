<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/sepamandat.class.php
 * \ingroup     sepamandatmanager
 * \brief       This file is a CRUD class file for Sepamandat (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/sepamandatmanager/class/sepamandat.class.php');

/**
 * Class for Sepamandat
 */
class SepaMandat extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'sepamandatmanager_sepamandat';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'sepamandatmanager_sepamandat';

	/**
	 * @var string ID to identify managed object.
	 */
	public static $staticElement = 'sepamandatmanager_sepamandat';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public static $static_table_element = 'sepamandatmanager_sepamandat';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for sepamandat. Must be the part after the 'object_' into object_sepamandat.png
	 */
	public $picto = 'sepamandat@sepamandatmanager';

	const STATUS_DRAFT = 0;
	const STATUS_TOSIGN = 1;
	const STATUS_CANCELED = 2;
	const STATUS_SIGNED = 3;
	const STATUS_STALE = 4;

	const TYPE_PUNCTUAL = 1;
	const TYPE_RECURRENT = 2;

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'entity' => array('type' => 'integer', 'label' => 'Entity ID', 'enabled' => '1', 'position' => 1, 'notnull' => 0, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Entity ID"),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php:1:status=1 AND entity IN (__SHARED_ENTITIES__)', 'label' => 'ThirdParty', 'enabled' => '1', 'position' => 50, 'notnull' => 1, 'visible' => 1, 'index' => 1),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 61, 'notnull' => 0, 'visible' => 0,),
		'note_private' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 62, 'notnull' => 0, 'visible' => 0,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 500, 'notnull' => 1, 'visible' => -2,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => -2,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -2,),
		'rum' => array('type' => 'varchar(255)', 'label' => 'code rum', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 4, 'noteditable' => 1),
		'ics' => array('type' => 'varchar(255)', 'label' => 'Sepa Creditor Identifier', 'enabled' => '1', 'position' => 101, 'notnull' => 0, 'visible' => 0,),
		'iban' => array('type' => 'varchar(255)', 'label' => 'Debtor iban', 'enabled' => '1', 'position' => 102, 'notnull' => 0, 'visible' => 1, 'css' => 'minwidth300'),
		'bic' => array('type' => 'varchar(255)', 'label' => 'debtor bic', 'enabled' => '1', 'position' => 103, 'notnull' => 0, 'visible' => 1,),
		'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 50, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array('0' => 'Brouillon', '1' => 'Validé', '9' => 'Annulé'), 'noteditable' => 1),
		'type' => array('type' => 'integer', 'label' => 'Type de mandat', 'enabled' => '1', 'position' => 115, 'notnull' => 1, 'visible' => 1, 'default' => self::TYPE_RECURRENT),
		'date_rum' => array('type' => 'date', 'label' => 'Date du mandat', 'enabled' => '1', 'position' => 500, 'notnull' => 0, 'visible' => 1,),
		'fk_companybankaccount' => array('type' => 'integer:CompanyBankAccount:societe/class/companybankaccount.class.php', 'label' => 'Linked company bank account', 'enabled' => 1, 'visible' => 0, 'index' => 1),
		'fk_generated_ecm' => array('type' => 'integer:ExtendedEcm:sepamandatmanager/class/extendedecm.class.php', 'label' => 'Last generated pdf file', 'enabled' => 1, 'visible' => 0)
	);
	public $rowid;
	public $ref;
	public $fk_soc;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $rum;
	public $ics;
	public $iban;
	public $bic;
	public $status;
	public $type;
	public $date_rum;
	public $fk_companybankaccount;
	public $fk_generated_ecm;
	// END MODULEBUILDER PROPERTIES

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		if (is_object($langs)) {
			$this->labelStatus = array(
				self::STATUS_DRAFT => $langs->trans('SepaMandateDraft'),
				self::STATUS_TOSIGN => $langs->trans('SepaMandateToSign'),
				self::STATUS_SIGNED => $langs->trans('SepaMandateSigned'),
				self::STATUS_CANCELED => $langs->trans('SepaMandateCanceled'),
				self::STATUS_STALE => $langs->trans('SepaMandateStale')
			);

			$this->labelStatusShort = array(
				self::STATUS_DRAFT => $langs->trans('SepaMandateDraftShort'),
				self::STATUS_TOSIGN => $langs->trans('SepaMandateToSignShort'),
				self::STATUS_SIGNED => $langs->trans('SepaMandateSignedShort'),
				self::STATUS_CANCELED => $langs->trans('SepaMandateCanceledShort'),
				self::STATUS_STALE => $langs->trans('SepaMandateStaleShort')
			);

			$this->fields['ics']['label'] = $langs->trans("SepaMandateIcs");
			$this->fields['rum']['label'] = $langs->trans("SepaMandateRum");
			$this->fields['iban']['label'] = $langs->trans("SepaMandateIban");
			$this->fields['bic']['label'] = $langs->trans("SepaMandateBic");
			$this->fields['type']['label'] = $langs->trans("SepaMandateType");
			$this->fields['type']['arrayofkeyval'] = array(
				self::TYPE_RECURRENT => $langs->trans("SepaMandateRecurrentType"),
				self::TYPE_PUNCTUAL => $langs->trans("SepaMandatePunctualType")
			);
		}

		$this->statusType = array(
			self::STATUS_DRAFT => 'status0',
			self::STATUS_TOSIGN => 'status3',
			self::STATUS_CANCELED => 'status5',
			self::STATUS_SIGNED => 'status4',
			self::STATUS_STALE => 'status8'
		);

		$this->fields['status']['arrayofkeyval'] = $this->labelStatus;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$result = $this->createCommon($user, $notrigger);
		if ($result > 0) {
			$result = $this->fetch($this->id);
		}
		return $result;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);


		// Clear fields
		$object->ref = empty($this->fields['ref']['default']) ? "copy_of_" . $object->ref : $this->fields['ref']['default'];
		$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
		$object->status = self::STATUS_DRAFT;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Function to load data from a SQL pointer into properties of current object $this
	 *
	 * @param   stdClass    $obj    Contain data of object from database
	 * @return void
	 */
	public function setVarsFromFetchObj(&$obj)
	{
		parent::setVarsFromFetchObj($obj);
		if (!$this->date_rum) {
			$this->date_rum = null;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		return $result;
	}

	/**
	 * Get module relative directory to document root directory
	 * @return string
	 */
	public function getRelativePathOfModuleDirToDolDataRoot()
	{
		$absoluteModuleDirectory = $this->getAbsoluteModuleDirectory();
		$result = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $absoluteModuleDirectory);
		$result = preg_replace('/^[\\/]/', '', $result);
		return $result;
	}

	/**
	 * Get absolute directory of document of module
	 * @return string
	 */
	public function getAbsoluteModuleDirectory()
	{
		global $conf;
		return $conf->sepamandatmanager->multidir_output[$this->entity ? $this->entity : $conf->entity];
	}

	/**
	 * Function to get relativePathToModuleOfCurrentObject
	 * @return string
	 */
	public function getRelativePathOfFileToModuleDataRoot()
	{
		return $this->specimen > 0 ? '' : $this->id;
	}

	/**
	 * Function to get relativePath of object to dol data root
	 * @return string
	 */
	public function getRelativePathToDolDataRoot()
	{
		return $this->getRelativePathOfModuleDirToDolDataRoot() . '/' . $this->getRelativePathOfFileToModuleDataRoot();
	}

	/**
	 * Function to get absolute path of object
	 * @return string
	 */
	public function getAbsolutePath()
	{
		return DOL_DATA_ROOT . '/' . $this->getRelativePathToDolDataRoot();
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return SepaMandat[]|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		if (empty($this->rum)) {
			$this->rum = $this->getNextNumRum();
		}
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		$result = $this->deleteCommon($user, $notrigger);
		if ($result > 0) {
			$extendedEcm = new ExtendedEcm($this->db);
			$files = $extendedEcm->fetchAll(null, null, null, null, array('filepath' => $this->getRelativePathToDolDataRoot()));
			foreach ($files as $file) {
				$file->deleteFile();
			}
			dol_delete_dir($this->getAbsolutePath());
		}
		return $result;
	}

	/**
	 * Check iban value
	 * @return string[] array of errors
	 */
	public function checkIbanValue()
	{
		global $langs;
		$result = array();
		require_once DOL_DOCUMENT_ROOT . '/includes/php-iban/oophp-iban.php';
		if (empty($this->iban)) {
			$result[] = $langs->trans("SepaMandateNoIban");
		} else {
			$ibanCheck = new IBAN($this->iban);
			if (!$ibanCheck->Verify()) {
				$result[] = $langs->trans("SepaMandateIbanNotValid");
			}
		}
		return $result;
	}

	/**
	 * Check bic value
	 * @return string[] array of errors
	 */
	public function checkBicValue()
	{
		global $langs;
		$result = array();
		if (empty($this->bic)) {
			$result[] = $langs->trans("SepaMandateNoBic");
		} elseif (!(bool) (preg_match('/^[a-z]{6}[0-9a-z]{2}([0-9a-z]{3})?\z/i', $this->bic) == 1)) {
			$result[] = $langs->trans("SepaMandateBicNotValid");
		}
		return $result;
	}

	/**
	 * Check mandat type value
	 * @return string[] array of errors
	 */
	public function checkMandatType()
	{
		global $langs;
		$result = array();
		if (empty($this->type)) {
			$result[] = $langs->trans("SepaMandateNoTypeSet");
		}
		return $result;
	}
	/**
	 * Check ics value
	 * @return string[] array of errors
	 */
	public function checkIcsValue()
	{
		global $langs;
		$result = array();
		if (empty($this->ics)) {
			$result[] = $langs->trans("SepaMandateIcsNotSet");
		}
		return $result;
	}

	/**
	 * Function to check data
	 * @return string[] array of errors
	 */
	public function checkData()
	{
		return array_merge($this->checkIcsValue(), $this->checkIbanValue(), $this->checkBicValue(), $this->checkMandatType());
	}

	/**
	 *	Validate object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function setToSign($user, $notrigger = 0)
	{
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		global $conf;
		// Protection
		if ($this->status != self::STATUS_DRAFT) {
			dol_syslog(get_class($this) . "::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}
		$this->db->begin();
		//We save some values
		$oldRef = $this->ref;
		$oldRum = $this->rum;
		$olDateRum = $this->date_rum;
		$oldStatus = $this->status;

		// Define new ref
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) // empty should not happened, but when it occurs, the test save life
		{
			$this->ref = $this->getNextNumRef();
		}

		if (empty($this->date_rum)) {
			$this->date_rum = dol_now();
		}

		$this->ics = $conf->global->PRELEVEMENT_ICS;

		$validationErrors = $this->checkData();
		$this->errors = array_merge($this->errors, $validationErrors);

		$this->status = self::STATUS_TOSIGN; //Avoid useless call of setStatusCommon
		if (empty($this->errors) && $this->update($user, true) > 0 && !$notrigger) {
			// Call trigger
			$this->call_trigger('SEPAMANDAT_TOSIGN', $user);
			// End call triggers
		}

		if (empty($this->errors)) {
			$this->generatePdf();
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->status = $oldStatus;
			$this->ref = $oldRef;
			$this->rum = $oldRum;
			$this->date_rum = $olDateRum;
			return -1;
		}
	}

	/**
	 * Proper function to override setStatus common which launch triggers without instance update to date
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$status			New status to set (often a constant like self::STATUS_XXX)
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *  @param  string  $triggercode    Trigger code to use
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setStatusCommon($user, $status, $notrigger = 0, $triggercode = '')
	{
		$error = 0;

		$this->db->begin();
		$oldStatus = $this->status;
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " SET status = " . $status;
		$sql .= " WHERE rowid = " . $this->id;

		if ($this->db->query($sql)) {
			if (!$error) {
				$this->oldcopy = clone $this;
				$this->status = $status;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger($triggercode, $user);
				if ($result < 0) $error++;
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				$this->status = $oldStatus;
				return -1;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Set back to draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setBackToDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_TOSIGN) {
			return 0;
		}
		$this->status = self::STATUS_DRAFT;
		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'SEPAMANDAT_UNVALIDATE');
	}

	/**
	 *	Set back to draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setBackToToSign($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_SIGNED && $this->status != self::STATUS_CANCELED) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_TOSIGN, $notrigger, 'SEPAMANDAT_UNSIGNED');
	}

	/**
	 *	Set back to signed status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setSigned($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_TOSIGN) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_SIGNED, $notrigger, 'SEPAMANDAT_SIGNED');
	}

	/**
	 *	Set back to cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setCanceled($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_TOSIGN && $this->status != self::STATUS_SIGNED) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'SEPAMANDAT_CANCELED');
	}

	/**
	 *	Set back to cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setStale($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_SIGNED) {
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_STALE, $notrigger, 'SEPAMANDAT_STALE');
	}

	/**
	 *	Set back to signed status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setBackToSigned($user, $notrigger = 0)
	{
		// Protection
		if ($this->status == self::STATUS_STALE) {
			$triggerName = 'SEPAMANDAT_UNSTALE';
		} elseif ($this->status == self::STATUS_CANCELED) {
			$triggerName = 'SEPAMANDAT_UNCANCELED';
		} else {
			//Protection
			return 0;
		}
		return $this->setStatusCommon($user, self::STATUS_SIGNED, $notrigger, $triggerName);
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<u>' . $langs->trans("Sepamandat") . '</u>';
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		if (isset($this->status)) {
			$label .= '<br><b>' . $langs->trans("Status") . ":</b> " . $this->getLibStatut(5);
		}

		$url = dol_buildpath('/sepamandatmanager/sepamandat_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowSepamandat");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		$linkstart = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('sepamandatdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		$labelStatus = $this->labelStatus[$status];
		$labelStatusShort = $this->labelStatusShort[$status];
		if (version_compare(DOL_VERSION, '12.0.0', '<')) {
			$statusPicto = str_replace("status", "statut", $this->statusType[$status]);

			if ($mode == 0)	return $labelStatus;
			if ($mode == 1)	return $labelStatusShort;
			if ($mode == 2)	return img_picto($labelStatusShort, $statusPicto) . ' ' . $labelStatusShort;
			if ($mode == 3)	return img_picto($labelStatus,  $statusPicto);
			if ($mode == 4)	return img_picto($labelStatus,  $statusPicto) . ' ' . $labelStatus;
			if ($mode == 5)	return '<span class="hideonsmartphone">' . $labelStatusShort . ' </span>' . img_picto($labelStatus,  $statusPicto);
			if ($mode == 6)	return '<span class="hideonsmartphone">' . $labelStatus . ' </span>' . img_picto($labelStatus,  $statusPicto);
		} else {
			return dolGetStatus($labelStatus, $labelStatusShort, '', $this->statusType[$status], $mode);
		}
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql .= ' WHERE t.rowid = ' . $id;
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
		$this->date_rum = dol_now();
		$this->ics = 'ABC123456789';
		$this->ref = $this->getNextNumRef();
		$this->rum = $this->getNextNumRum();
		$this->fk_soc = 3000;
		$thirdparty = new Societe($this->db);
		$thirdparty->initAsSpecimen();
		$this->thirdparty = $thirdparty;
		$this->iban = "FR14 2004 1010 0505 0001 3M02 606";
		$this->bic = 'BIC12345';
		$this->type = self::TYPE_PUNCTUAL;
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("sepamandatmanager@sepamandat");

		if (empty($conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON)) {
			$conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON = 'mod_sepamandat_standard';
		}

		if (!empty($conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON)) {
			$mybool = false;

			$file = $conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON . ".php";
			$classname = $conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/sepamandatmanager/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir . $file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file " . $file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error") . " " . $langs->trans("ClassNotFound") . ' ' . $classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}


	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRum()
	{
		global $langs, $conf;
		$langs->load("sepamandatmanager@sepamandat");

		if (empty($conf->global->SEPAMANDATMANAGER_SEPAMANDATRUM_ADDON)) {
			$conf->global->SEPAMANDATMANAGER_SEPAMANDATRUM_ADDON = 'mod_sepamandatrum_standard';
		}

		if (!empty($conf->global->SEPAMANDATMANAGER_SEPAMANDATRUM_ADDON)) {
			$mybool = false;

			$file = $conf->global->SEPAMANDATMANAGER_SEPAMANDATRUM_ADDON . ".php";
			$classname = $conf->global->SEPAMANDATMANAGER_SEPAMANDATRUM_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/sepamandatmanager/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir . $file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file " . $file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error") . " " . $langs->trans("ClassNotFound") . ' ' . $classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$result = 0;
		$includedocgeneration = 1;

		$langs->load("sepamandatmanager@sepamandatmanager");

		if (!dol_strlen($modele)) {
			$modele = 'standard_sepamandat';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (!empty($conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON_PDF)) {
				$modele = $conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/sepamandatmanager/doc/";

		if ($includedocgeneration) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}

	/**
	 * Function to generate pdf file
	 * @return int <0 if ko, >0 if ok
	 */
	public function generatePdf()
	{
		global $conf, $langs;
		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
			$newlang = $this->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$model = $this->modelpdf;
		return $this->generateDocument($model, $outputlangs);
	}
}
