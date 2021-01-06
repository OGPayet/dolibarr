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
 * \file        class/digitalsignaturepeople.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       This file is a CRUD class file for DigitalSignaturePeople (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for DigitalSignaturePeople
 */
class DigitalSignaturePeople extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digitalsignaturepeople';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digitalsignaturemanager_digitalsignaturepeople';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for digitalsignaturepeople. Must be the part after the 'object_' into object_digitalsignaturepeople.png
	 */
	public $picto = 'digitalsignaturepeople@digitalsignaturemanager';

	/**
	 * @var DigitalSignatureRequest linkedDigitalSignatureRequest
	 */
	public $digitalSignatureRequest;

	const STATUS_DRAFT = 0;
	const STATUS_WAITING_TO_SIGN = 1;
	const STATUS_SHOULD_SIGN = 2;
	const STATUS_REFUSED = 3;
	const STATUS_SUCCESS = 4;
	const STATUS_PROCESS_STOPPED_BEFORE = 9;

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
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'En attente de demande de signature', '2'=>'Signature refus&eacute;e', '3'=>'Signature termin&eacute;e', '4'=>'Processus stopp&eacute;e', '9'=>'Erreur Technique'),),
		'lastName' => array('type'=>'varchar(255)', 'label'=>'Last name of the signatory person', 'enabled'=>'1', 'position'=>502, 'notnull'=>0, 'visible'=>1,),
		'firstName' => array('type'=>'varchar(255)', 'label'=>'First name of the signatory person', 'enabled'=>'1', 'position'=>503, 'notnull'=>0, 'visible'=>1,),
		'phoneNumber' => array('type'=>'varchar(255)', 'label'=>'Phone number of the signatory to identify him', 'enabled'=>'1', 'position'=>504, 'notnull'=>0, 'visible'=>1,),
		'mail' => array('type'=>'varchar(255)', 'label'=>'Email Address of the signatory to identify him', 'enabled'=>'1', 'position'=>504, 'notnull'=>0, 'visible'=>1,),
		'providerSignatoryId' => array('type'=>'varchar(255)', 'label'=>'Id of the signatory in the provider system', 'enabled'=>'1', 'position'=>504, 'notnull'=>0, 'visible'=>1,),
		'fk_digitalsignaturerequest' => array('type'=>'integer:DigitalSignatureRequest:digitalsignaturemanager/class/digitalsignaturerequest.class.php	', 'label'=>'Linked Digital Signature request', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'position' => array('type'=>'integer', 'label'=>'Position of the signatory people into signature process', 'enabled'=>'1', 'position'=>11, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'fk_people_object' => array('type'=>'integer', 'label'=>'Id of the linked people (contact or user)', 'enabled'=>'1', 'position'=>20, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'fk_people_type' => array('type'=>'varchar(255)', 'label'=>'Type of the linked people', 'enabled'=>'1', 'position'=>21, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
	);
	public $rowid;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	public $lastName;
	public $firstName;
	public $phoneNumber;
	public $mail;
	public $providerSignatoryId;
	public $fk_digitalsignaturerequest;
	public $position;
	public $fk_people_object;
	public $fk_people_type;
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

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->digitalsignaturemanager->digitalsignaturepeople->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
		$this->status = self::STATUS_DRAFT;
		$langs->load("digitalsignaturemanager@digitalsignaturemanager");
		$this->labelStatus = array(
			self::STATUS_DRAFT => $langs->trans('DigitalSignaturePeopleDraft'),
			self::STATUS_WAITING_TO_SIGN => $langs->trans('DigitalSignaturePeopleWaitingToSign'),
			self::STATUS_SHOULD_SIGN => $langs->trans('DigitalSignaturePeopleShouldSign'),
			self::STATUS_SUCCESS => $langs->trans('DigitalSignaturePeopleSuccessfullySigned'),
			self::STATUS_REFUSED => $langs->trans('DigitalSignaturePeopleRefusedToSign'),
			self::STATUS_PROCESS_STOPPED_BEFORE => $langs->trans('DigitalSignaturePeopleDontHaveBeenAskedToSign'),
		);

		$this->labelStatusShort = array(
			self::STATUS_DRAFT => $langs->trans('DigitalSignaturePeopleDraftShort'),
			self::STATUS_WAITING_TO_SIGN => $langs->trans('DigitalSignaturePeopleWaitingToSignShort'),
			self::STATUS_SHOULD_SIGN => $langs->trans('DigitalSignaturePeopleShouldSignShort'),
			self::STATUS_SUCCESS => $langs->trans('DigitalSignaturePeopleSuccessfullySignedShort'),
			self::STATUS_REFUSED => $langs->trans('DigitalSignaturePeopleRefusedToSignShort'),
			self::STATUS_PROCESS_STOPPED_BEFORE => $langs->trans('DigitalSignaturePeopleDontHaveBeenAskedToSignShort'),
		);

		$this->statusType = array(
			self::STATUS_DRAFT => 'status0',
			self::STATUS_WAITING_TO_SIGN => 'status1',
			self::STATUS_SHOULD_SIGN => 'status3',
			self::STATUS_SUCCESS => 'status4',
			self::STATUS_REFUSED => 'status8',
			self::STATUS_PROCESS_STOPPED_BEFORE => 'status5',
		);
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
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Create or Update object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function createOrUpdate(User $user, $notrigger)
	{
		if($this->id) {
			return $this->update($user, $notrigger);
		}
		else {
			return $this->create($user, $notrigger);
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
		return $this->fetchCommon($id, $ref);
	}

	/**
	 * Function to fetch all people linked to a digital signature request
	 * @param DigitalSignatureRequest $digitalSignatureRequest
	 * @return array|int array of digital signature people
	 */

	public function fetchPeopleOfDigitalSignatureRequest(&$digitalSignatureRequest)
	{
		if(!$digitalSignatureRequest || !$digitalSignatureRequest->id) {
			return 0;
		}
		$this->digitalSignatureRequest = $digitalSignatureRequest;
		$result = $this->fetchAll('ASC', 'position', 0, 0, array('fk_digitalsignaturerequest'=>$digitalSignatureRequest->id));
		if(is_array($result)) {
			foreach($result as $people) {
				$people->digitalSignatureRequest = $digitalSignatureRequest;
				$people->fetch_optionals();
			}
			return $result;
		}
		return -1;
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
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

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
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT)
		{
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'DIGITALSIGNATUREPEOPLE_UNVALIDATE');
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

		$label = '<u>'.$langs->trans("DigitalSignaturePeople").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (isset($this->status)) {
			$label .= '<br><b>'.$langs->trans("Status").":</b> ".$this->getLibStatut(5);
		}

		$url = dol_buildpath('/digitalsignaturemanager/digitalsignaturepeople_card.php', 1).'?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowDigitalSignaturePeople");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		}
		else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					}
					else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				}
				else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('digitalsignaturepeopledao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 * Function to get display Name of this people
	 * @return string
	 */
	public function displayName($separator = " ")
	{
		return $this->lastName . $separator . $this->firstName;
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
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort))
		{
			global $langs;
			//$langs->load("digitalsignaturemanager");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			//$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			//$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			//$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			//$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		//if ($status == self::STATUS_CANCELED) $statusType = 'status6';
		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	* Is this object editable by the user
	* @return boolean
	*/
	public function isEditable()
	{
		return $this->digitalSignatureRequest && $this->digitalSignatureRequest->isEditable();
	}

	/**
	 * Function to manage start of signature process
	 * 	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function manageStartOfLinkedSignatureRequest($user, $notrigger = false)
	{
		return $this->setStatusCommon($user, self::STATUS_WAITING_TO_SIGN, $notrigger, 'DIGITALSIGNATUREPEOPLE_WAITINGTOSIGN');
	}

	/**
	 * Function to indicate that this signatory people should sign now
	 * 	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function managePeopleShouldSignNow($user, $notrigger = false)
	{
		return $this->setStatusCommon($user, self::STATUS_SHOULD_SIGN, $notrigger, 'DIGITALSIGNATUREPEOPLE_SHOULDSIGN');
	}

	/**
	 * Function to manage people that successfully signed
	 * 	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function manageSuccessfullySigned($user, $notrigger = false)
	{
		return $this->setStatusCommon($user, self::STATUS_SUCCESS, $notrigger, 'DIGITALSIGNATUREPEOPLE_SUCCESS');
	}

	/**
	 * Function to manage people that refused to sign
	 * 	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function manageRefusedToSign($user, $notrigger = false)
	{
		return $this->setStatusCommon($user, self::STATUS_REFUSED, $notrigger, 'DIGITALSIGNATUREPEOPLE_SUCCESS');
	}

	/**
	 * Function to manage people that can't signed because somebody refused to sign before
	 * 	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function manageUserCantHaveSigned($user, $notrigger = false)
	{
		return $this->setStatusCommon($user, self::STATUS_PROCESS_STOPPED_BEFORE, $notrigger, 'DIGITALSIGNATUREPEOPLE_PROCESSSTOPPEDBEFORE');
	}

	/**
	 * Function to validate that all needed data have been put in order to be able to create one request
	 * @return array arrayOfErrors
	 */
	public function checkDataValidForCreateRequestOnProvider()
	{
		global $langs;
		$errors = array();
		if(empty($this->lastName)) {
			$errors[] = $langs->trans('DigitalSignaturePeopleMissingLastName', $this->displayName(""));
		}
		if(empty($this->firstName)) {
			$errors[] = $langs->trans('DigitalSignaturePeopleMissingFirstName', $this->displayName(""));
		}
		if(empty($this->phoneNumber)) {
			$errors[] = $langs->trans('DigitalSignaturePeopleMissingPhoneNumber', $this->displayName());
		}
		if(empty($this->mail)){
			$errors[] = $langs->trans('DigitalSignaturePeopleMissingMail', $this->displayName());
		}
		return $errors;
	}
}
