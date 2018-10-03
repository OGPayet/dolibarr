<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
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
 * \file        class/doliesign.class.php
 * \ingroup     doliesign
 * \brief       This file is a CRUD class file for doliesign (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/doliesign/lib/doliesign.lib.php');
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for doliesign
 */
class DoliEsign extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'doliesign';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'doliesign';
	/**
	 * @var array  Does doliesign support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;
	/**
	 * @var string String with name of icon for doliesign. Must be the part after the 'object_' into object_doliesign.png
	 */
	public $picto = 'doliesign@doliesign';


	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'showoncombobox' if field must be shown into the label of combobox
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'visible'=>-1, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id",),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'visible'=>-1, 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object",),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'visible'=>-1, 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'index'=>1,),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'visible'=>-1, 'enabled'=>1, 'position'=>30, 'notnull'=>-1, 'searchall'=>1, 'help'=>"Help text",),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'visible'=>1, 'enabled'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'searchall'=>1, 'help'=>"LinkToThirparty",),
		'description' => array('type'=>'text', 'label'=>'Description', 'visible'=>-1, 'enabled'=>1, 'position'=>60, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>1, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'date_sign' => array('type'=>'datetime', 'label'=>'DateSign', 'visible'=>1, 'enabled'=>1, 'position'=>500, 'notnull'=>-1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1,),
		'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'visible'=>1, 'enabled'=>1, 'position'=>510, 'notnull'=>1,),
		'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'visible'=>-1, 'enabled'=>1, 'position'=>511, 'notnull'=>-1,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'visible'=>-2, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'WaitingDoliEsign', '1'=>'Signed', '2'=>'Cancel', '3'=>'Downloaded', '-1'=>'Error')),
		'fk_object' => array('type'=>'integer', 'label'=>'ObjectId', 'visible'=>1, 'enabled'=>1, 'position'=>3, 'notnull'=>-1, 'index'=>1,),
		'object_type' => array('type'=>'varchar(32)', 'label'=>'ObjectType', 'visible'=>1, 'enabled'=>1, 'position'=>22, 'notnull'=>-1, 'comment'=>"key",),
		'id_file' => array('type'=>'varchar(14)', 'label'=>'idFile', 'visible'=>-1, 'enabled'=>1, 'position'=>600, 'notnull'=>-1,),
		'sign_status' => array('type'=>'varchar(128)', 'label'=>'SignStatus', 'visible'=>1, 'enabled'=>1, 'position'=>620, 'notnull'=>-1,),
		'sign_id' => array('type'=>'varchar(64)', 'label'=>'SignId', 'visible'=>-1, 'enabled'=>1, 'position'=>640, 'notnull'=>-1,),
		'api_name' => array('type'=>'varchar(64)', 'label'=>'ApiName', 'visible'=>-1, 'enabled'=>1, 'position'=>640, 'notnull'=>-1,),
		'fk_contact_sign' => array('type'=>'integer', 'label'=>'ContactSignId', 'visible'=>1, 'enabled'=>1, 'position'=>5, 'notnull'=>-1,),
		'fk_user_sign' => array('type'=>'integer', 'label'=>'UserSignId', 'visible'=>1, 'enabled'=>1, 'position'=>5, 'notnull'=>-1,),
		'hash_file' => array('type'=>'varchar(255)', 'label'=>'HashFile', 'visible'=>-1, 'enabled'=>1, 'position'=>605, 'notnull'=>-1,),
		'path_file' => array('type'=>'varchar(255)', 'label'=>'PathFile', 'visible'=>-1, 'enabled'=>1, 'position'=>610, 'notnull'=>-1,),
	);
	public $rowid;
	public $ref;
	public $entity;
	public $label;
	public $fk_soc;
	public $description;
	public $date_creation;
	public $date_sign;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	public $fk_object;
	public $object_type;
	public $id_file;
	public $sign_status;
	public $sign_id;
	public $api_name;
	public $fk_contact_sign;
	public $fk_user_sign;
	public $hash_file;
	public $path_file;

	const STATUS_WAITING = 0;
	const STATUS_SIGNED = 1;
	const STATUS_CANCELED = 2;
	const STATUS_FILE_FETCHED = 3;
	const STATUS_ERROR = 4;


	// END MODULEBUILDER PROPERTIES



	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'doliesigndet';
	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_doliesign';
	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'doliesignline';
	/**
	 * @var array  Array of child tables (child tables to delete before deleting a record)
	 */
	//protected $childtables=array('doliesigndet');
	/**
	 * @var doliesignLine[]     Array of subtable lines
	 */
	//public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) $this->fields['rowid']['visible']=0;
		if (empty($conf->multicompany->enabled)) $this->fields['entity']['enabled']=0;
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
		if (DoliEsign::checkDolVersion('6.0')) {
			return $this->createCommon($user, $notrigger);
		} else {
			return $this->myCreateCommon($user, $notrigger);
		}
	}

	/**
	 * Clone and object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $hookmanager, $langs;
	    $error = 0;

	    dol_syslog(__METHOD__, LOG_DEBUG);

	    $object = new self($this->db);

	    $this->db->begin();

	    // Load source object
	    $object->fetchCommon($fromid);
	    // Reset some properties
	    unset($object->id);
	    unset($object->fk_user_creat);
	    unset($object->import_key);

	    // Clear fields
	    $object->ref = "copy_of_".$object->ref;
	    $object->title = $langs->trans("CopyOf")." ".$object->title;
	    // ...

	    // Create clone
		$object->context['createfromclone'] = 'createfromclone';
	    $result = $object->createCommon($user);
	    if ($result < 0) {
	        $error++;
	        $this->error = $object->error;
	        $this->errors = $object->errors;
	    }

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
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @param int    $objectId   fk signing object
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id = null, $ref = null, $objectId = null, $objectType = '')
	{
		$result = $this->fetchCommon($id, $ref, $objectId, $objectType);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load list of sign tokens for dolibarr object in memory from the database
	 *
	 * @return array         <0 if KO, array of token objects
	 */
	public function fetchTokens($objectId, $objectType)
	{
		$tokenList = array();

		$sql = "SELECT rowid, ref, sign_id, sign_status, status, id_file";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE fk_object = ".$objectId;
		if (isset($objectType)) {
			$sql.= " AND object_type = '".$this->db->escape($objectType)."'";
		}

		$resql=$this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$token = new stdClass;
				$obj = $this->db->fetch_object($resql);
				$token->id = $obj->rowid;
				$token->ref = $obj->ref;
				$token->idDemand = $obj->sign_id;
				$token->status = $obj->status;
				$token->sign_status = $obj->sign_status;
				$tokenList[$i++] = $token;
			}
			$this->db->free($resql);

			return $tokenList;
		} else {
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetchListId ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	/*public function fetchLines()
	{
		$this->lines=array();

		// Load lines with object doliesignLine

		return count($this->lines)?1:0;
	}*/

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		if (DoliEsign::checkDolVersion('6.0')) {
			return $this->updateCommon($user, $notrigger);
		} else {
			return $this->myUpdateCommon($user, $notrigger);
		}
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
		if (DoliEsign::checkDolVersion('6.0')) {
			return $this->deleteCommon($user, $notrigger);
		} else {
			return $this->myDeleteCommon($user, $notrigger);
		}
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
     *  @param	int  	$notooltip					1=Disable tooltip
     *  @param  string  $morecss            		Add more css on link
     *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $morecss='', $save_lastsearch_value=-1)
	{
		global $db, $conf, $langs;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("doliesign") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = dol_buildpath('/doliesign/doliesign_card.php',1).'?id='.$this->id;

        if ($option != 'nolink')
        {
	        // Add param to save lastsearch_values or not
	        $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
	        if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
	        if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("Showdoliesign");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}

	/**
	 *  Return the available status
	 *
	 *  @return array 			       	Label of status
	 */
	public function getStatusList() {
		$list = array();
		$status = 0;
		while (is_string($this->LibStatut($status))) {
			$list[$status] = $this->LibStatut($status);
			$status++;
		}
		return $list;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			$prefix='';
			if ($status == self::STATUS_FILE_FETCHED) return $langs->trans('FetchedDoliEsign');
			if ($status == self::STATUS_CANCELED) return $langs->trans('CancelledDoliEsign');
			if ($status == self::STATUS_SIGNED) return $langs->trans('SignedDoliEsign');
			if ($status == self::STATUS_WAITING) return $langs->trans('WaitingDoliEsign');
			if ($status == self::STATUS_ERROR) return $langs->trans('ErrorDoliEsign');
		}
		if ($mode == 1)
		{
			if ($status == self::STATUS_FILE_FETCHED) return $langs->trans('FetchedDoliEsign');
			if ($status == self::STATUS_CANCELED) return $langs->trans('CancelledDoliEsign');
			if ($status == self::STATUS_SIGNED) return $langs->trans('SignedDoliEsign');
			if ($status == self::STATUS_WAITING) return $langs->trans('WaitingDoliEsign');
			if ($status == self::STATUS_ERROR) return $langs->trans('ErrorDoliEsign');
		}
		if ($mode == 2)
		{
			if ($status == self::STATUS_FILE_FETCHED) return img_picto($langs->trans('FetchedDoliEsign'),'file').' '.$langs->trans('FetchedDoliEsign');
			if ($status == self::STATUS_CANCELED) return img_picto($langs->trans('CancelledDoliEsign'),'cancel').' '.$langs->trans('CancelledDoliEsign');
			if ($status == self::STATUS_SIGNED) return img_picto($langs->trans('SignedDoliEsign'),'signed').' '.$langs->trans('SignedDoliEsign');
			if ($status == self::STATUS_WAITING) return img_picto($langs->trans('WaitingDoliEsign'),'waiting').' '.$langs->trans('WaitingDoliEsign');
			if ($status == self::STATUS_ERROR) return img_picto($langs->trans('ErrorDoliEsign'),'error').' '.$langs->trans('ErrorDoliEsign');
		}
		if ($mode == 3)
		{
			if ($status == self::STATUS_FILE_FETCHED) return img_picto($langs->trans('FetchedDoliEsign'),'file');
			if ($status == self::STATUS_CANCELED) return img_picto($langs->trans('CancelledDoliEsign'),'cancel');
			if ($status == self::STATUS_SIGNED) return img_picto($langs->trans('SignedDoliEsign'),'signed');
			if ($status == self::STATUS_WAITING) return img_picto($langs->trans('WaitingDoliEsign'),'waiting');
			if ($status == self::STATUS_ERROR) return img_picto($langs->trans('ErrorDoliEsign'),'error');
		}
	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql.= ' fk_user_creat, fk_user_modif';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql.= ' WHERE t.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture   = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);

		}
		else
		{
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
	}


	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		$this->output = '';
		$this->error='';

		dol_syslog(__METHOD__, LOG_DEBUG);

		// ...

		return 0;
	}

	/**
	 * Return HTML string to show a field into a page,
	 * override from showOutputField to put own formats
	 *
	 * @param  array   $val		       Array of properties of field to show
	 * @param  string  $key            Key of attribute
	 * @param  object  $object         list object with preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $showsize       Value for css to define size. May also be a numeric.
	 * @return string
	 */
	function showOutputField($val, $key, $object, $moreparam='', $keysuffix='', $keyprefix='', $showsize=0)
	{
		global $conf,$langs,$form;

		if (! is_object($form))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form=new Form($this->db);
		}

		$objectid = $this->id;
		$label = $val['label'];
		$type  = $val['type'];
		$size  = $val['css'];
		$value = $object->$key;
		// Convert var to be able to share same code than showOutputField of extrafields
		if (preg_match('/varchar\((\d+)\)/', $type, $reg))
		{
			$type = 'varchar';		// convert varchar(xx) int varchar
			$size = $reg[1];
		}
		elseif (preg_match('/varchar/', $type)) $type = 'varchar';		// convert varchar(xx) int varchar
		if (is_array($val['arrayofkeyval'])) $type='select';
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) $type='link';

		//$elementtype=$this->attribute_elementtype[$key];	// seems to not be used
		$default=$val['default'];
		$computed=$val['computed'];
		$unique=$val['unique'];
		$required=$val['required'];
		$param=$val['param'];
		if (is_array($val['arrayofkeyval'])) $param['options'] = $val['arrayofkeyval'];
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg))
		{
			$type='link';
			$param['options']=array($reg[1].':'.$reg[2]=>$reg[1].':'.$reg[2]);
		}
		$langfile=$val['langfile'];
		$list=$val['list'];
		$help=$val['help'];
		$hidden=(($val['visible'] == 0) ? 1 : 0);			// If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)

		if ($hidden) return '';

		// If field is a computed field, value must become result of compute
		if ($computed)
		{
			// Make the eval of compute string
			//var_dump($computed);
			$value = dol_eval($computed, 1, 0);
		}

		if (empty($showsize))
		{
			if ($type == 'date')
			{
				//$showsize=10;
				$showsize = 'minwidth100imp';
			}
			elseif ($type == 'datetime')
			{
				//$showsize=19;
				$showsize = 'minwidth200imp';
			}
			elseif (in_array($type,array('int','double','price')))
			{
				//$showsize=10;
				$showsize = 'maxwidth75';
			}
			elseif ($type == 'url')
			{
				$showsize='minwidth400';
			}
			elseif ($type == 'boolean')
			{
				$showsize='';
			}
			else
			{
				if (round($size) < 12)
				{
					$showsize = 'minwidth100';
				}
				else if (round($size) <= 48)
				{
					$showsize = 'minwidth200';
				}
				else
				{
					//$showsize=48;
					$showsize = 'minwidth400';
				}
			}
		}

		// Format output value differently according to properties of field
		if ($key == 'ref' && method_exists($this, 'getNomUrl')) $value=$this->getNomUrl(0, '', 1, '', 1);
		elseif ($key == 'fk_object') {
			if (!empty($value)) {
				if (!empty($object->fk_object) && ! empty($object->object_type)) {
					$value = $this->getOriginUrl($object->fk_object, $object->object_type);
				}
			} else {
				$value = '';
			}
		}
		elseif ($key == 'fk_user_creat') {
			if (!empty($value)) {
				if (isset($object->fk_user_creat)) {
					$value = $this->getOriginUrl($object->fk_user_creat, 'user');
				}
			} else {
				$value = '';
			}
		}
		elseif ($key == 'fk_user_modif') {
			if (!empty($value)) {
				if (isset($object->fk_user_modif)) {
					$value = $this->getOriginUrl($object->fk_user_modif, 'user');
				}
			} else {
				$value = '';
			}
		}
		elseif ($key == 'object_type') {
			if (!empty($value)) {
				$config = new DoliEsignConfig($this->db);
				$value = $config->elementList[$value];
			}
		}
		elseif ($key == 'sign_status') {
			if (!empty($value) &&
					(
						$value == 'COSIGNATURE_FILE_SIGNED' ||
						$value == 'COSIGNATURE_EVENT_OK' ||
						$value == 'done'
					)
			) {
				$value = img_picto($langs->trans('SignedDoliEsign'),'statut4');
			} else {
				$value = img_picto($langs->trans('WaitingDoliEsign'),'statut5');
			}
		}
		elseif ($key == 'fk_contact_sign') {
			if (!empty($value)) {
				if (isset($object->fk_contact_sign)) {
					$value = $this->getOriginUrl($object->fk_contact_sign, 'contact');
				}
			} else {
				$value = '';
			}
		}
		elseif ($key == 'fk_user_sign') {
			if (!empty($value)) {
				if (isset($object->fk_user_sign)) {
					$value = $this->getOriginUrl($object->fk_user_sign, 'user');
				}
			} else {
				$value = '';
			}
		}
		elseif ($key == 'status' && method_exists($this, 'getLibStatut')) $value=$this->getLibStatut(0);
		elseif ($type == 'date')
		{
			$value=dol_print_date($value,'day');
		}
		elseif ($type == 'datetime')
		{
			$value=dol_print_date($value, 'dayhour', 'gmt');
		}
		elseif ($type == 'double')
		{
			if (!empty($value)) {
				$value=price($value);
			}
		}
		elseif ($type == 'boolean')
		{
			$checked='';
			if (!empty($value)) {
				$checked=' checked ';
			}
			$value='<input type="checkbox" '.$checked.' '.($moreparam?$moreparam:'').' readonly disabled>';
		}
		elseif ($type == 'mail')
		{
			$value=dol_print_email($value,0,0,0,64,1,1);
		}
		elseif ($type == 'url')
		{
			$value=dol_print_url($value,'_blank',32,1);
		}
		elseif ($type == 'phone')
		{
			$value=dol_print_phone($value, '', 0, 0, '', '&nbsp;', 1);
		}
		elseif ($type == 'price')
		{
			$value=price($value,0,$langs,0,0,-1,$conf->currency);
		}
		elseif ($type == 'select')
		{
			$value=$param['options'][$value];
		}
		elseif ($type == 'sellist')
		{
			$param_list=array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey="rowid";
			$keyList='rowid';

			if (count($InfoFieldList)>=3)
			{
				$selectkey = $InfoFieldList[2];
				$keyList=$InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|',$InfoFieldList[1]);
			if(is_array($fields_label)) {
				$keyList .=', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT '.$keyList;
			$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra')!==false)
			{
				$sql.= ' as main';
			}
			if ($selectkey=='rowid' && empty($value)) {
				$sql.= " WHERE ".$selectkey."=0";
			} elseif ($selectkey=='rowid') {
				$sql.= " WHERE ".$selectkey."=".$this->db->escape($value);
			}else {
				$sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			}

			//$sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$value='';	// value was used, so now we reste it to use it to build final output

				$obj = $this->db->fetch_object($resql);

				// Several field into label (eq table:code|libelle:rowid)
				$fields_label = explode('|',$InfoFieldList[1]);

				if(is_array($fields_label) && count($fields_label)>1)
				{
					foreach ($fields_label as $field_toshow)
					{
						$translabel='';
						if (!empty($obj->$field_toshow)) {
							$translabel=$langs->trans($obj->$field_toshow);
						}
						if ($translabel!=$field_toshow) {
							$value.=dol_trunc($translabel,18).' ';
						}else {
							$value.=$obj->$field_toshow.' ';
						}
					}
				}
				else
				{
					$translabel='';
					if (!empty($obj->{$InfoFieldList[1]})) {
						$translabel=$langs->trans($obj->{$InfoFieldList[1]});
					}
					if ($translabel!=$obj->{$InfoFieldList[1]}) {
						$value=dol_trunc($translabel,18);
					}else {
						$value=$obj->{$InfoFieldList[1]};
					}
				}
			}
			else dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
		}
		elseif ($type == 'radio')
		{
			$value=$param['options'][$value];
		}
		elseif ($type == 'checkbox')
		{
			$value_arr=explode(',',$value);
			$value='';
			if (is_array($value_arr))
			{
				foreach ($value_arr as $keyval=>$valueval) {
					$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$param['options'][$valueval].'</li>';
				}
			}
			$value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
		}
		elseif ($type == 'chkbxlst')
		{
			$value_arr = explode(',', $value);

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2] . ' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT ' . $keyList;
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this) . ':showOutputField:$type=chkbxlst',LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$value = ''; // value was used, so now we reste it to use it to build final output
				$toprint=array();
				while ( $obj = $this->db->fetch_object($resql) ) {

					// Several field into label (eq table:code|libelle:rowid)
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
						if (is_array($fields_label) && count($fields_label) > 1) {
							foreach ( $fields_label as $field_toshow ) {
								$translabel = '';
								if (! empty($obj->$field_toshow)) {
									$translabel = $langs->trans($obj->$field_toshow);
								}
								if ($translabel != $field_toshow) {
									$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
								} else {
									$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->$field_toshow.'</li>';
								}
							}
						} else {
							$translabel = '';
							if (! empty($obj->{$InfoFieldList[1]})) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							}
							if ($translabel != $obj->{$InfoFieldList[1]}) {
								$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
							} else {
								$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->{$InfoFieldList[1]}.'</li>';
							}
						}
					}
				}
				$value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';

			} else {
				dol_syslog(get_class($this) . '::showOutputField error ' . $this->db->lasterror(), LOG_WARNING);
			}
		}
		elseif ($type == 'link')
		{
			$out='';

			// only if something to display (perf)
			if ($value)
			{
				$param_list=array_keys($param['options']);				// $param_list='ObjectName:classPath'

				$InfoFieldList = explode(":", $param_list[0]);
				$classname=$InfoFieldList[0];
				$classpath=$InfoFieldList[1];
				if (! empty($classpath))
				{
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname))
					{
						$object = new $classname($this->db);
						$object->fetch($value);
						$value=$object->getNomUrl(3);
					}
				}
				else
				{
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			}
		}
		elseif ($type == 'text' || $type == 'html')
		{
			$value=dol_htmlentitiesbr($value);
		}
		elseif ($type == 'password')
		{
			$value=preg_replace('/./i','*',$value);
		}

		//print $type.'-'.$size;
		$out=$value;

		return $out;
	}

	/**
	 * Create object into database, from Dolibarr 6.0 in core
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function myCreateCommon(User $user, $notrigger = false)
	{
		global $langs;

		$error = 0;

		$now=dol_now();

		$fieldvalues = $this->my_set_save_query();
		if (array_key_exists('date_creation', $fieldvalues) && empty($fieldvalues['date_creation'])) $fieldvalues['date_creation']=$this->db->idate($now);
		if (array_key_exists('fk_user_creat', $fieldvalues) && ! ($fieldvalues['fk_user_creat'] > 0)) $fieldvalues['fk_user_creat']=$user->id;
		unset($fieldvalues['rowid']);	// The field 'rowid' is reserved field name for autoincrement field so we don't need it into insert.

		$keys=array();
		$values = array();
		foreach ($fieldvalues as $k => $v) {
			$keys[$k] = $k;
			$value = $this->fields[$k];
			$values[$k] = $this->myQuote($v, $value);
		}

		// Clean and check mandatory
		foreach($keys as $key)
		{
			// If field is an implicit foreign key field
			if (preg_match('/^integer:/i', $this->fields[$key]['type']) && $values[$key] == '-1') $values[$key]='';
			if (! empty($this->fields[$key]['foreignkey']) && $values[$key] == '-1') $values[$key]='';

			//var_dump($key.'-'.$values[$key].'-'.($this->fields[$key]['notnull'] == 1));
			if ($this->fields[$key]['notnull'] == 1 && ! isset($values[$key]))
			{
				$error++;
				$this->errors[]=$langs->trans("ErrorFieldRequired", $this->fields[$key]['label']);
			}

			// If field is an implicit foreign key field
			if (preg_match('/^integer:/i', $this->fields[$key]['type']) && empty($values[$key])) $values[$key]='null';
			if (! empty($this->fields[$key]['foreignkey']) && empty($values[$key])) $values[$key]='null';
		}

		if ($error) return -1;

		$this->db->begin();

		if (! $error)
		{
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= ' ('.implode( ", ", $keys ).')';
			$sql.= ' VALUES ('.implode( ", ", $values ).')';

			$res = $this->db->query($sql);
			if ($res===false) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
		}

		if (! $error)
		{
			$result=$this->insertExtraFields();
			if ($result < 0) $error++;
		}

		if (! $error && ! $notrigger)
		{
			// Call triggers
			$result=$this->call_trigger(strtoupper(get_class($this)).'_CREATE',$user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Add quote to field value if necessary, from Dolibarr 6.0 in core.
	 *
	 * @param 	string|int	$value			Value to protect
	 * @param	array		$fieldsentry	Properties of field
	 * @return 	string
	 */
	protected function myQuote($value, $fieldsentry) {
		if (is_null($value)) return 'NULL';
		else if (preg_match('/^(int|double|real)/i', $fieldsentry['type'])) return $this->db->escape("$value");
		else return "'".$this->db->escape($value)."'";
	}


	/**
	 * Function test if type is array, from Dolibarr 6.0 in core.
	 *
	 * @param   array   $info   content informations of field
	 * @return                  bool
	 */
	protected function myIsArray($info)
	{
		if(is_array($info))
		{
			if(isset($info['type']) && $info['type']=='array') return true;
			else return false;
		}
		else return false;
	}

	/**
	 * Function test if type is date, from Dolibarr 6.0 in core.
	 *
	 * @param   array   $info   content informations of field
	 * @return                  bool
	 */
	public function myIsDate($info)
	{
		if(isset($info['type']) && ($info['type']=='date' || $info['type']=='datetime' || $info['type']=='timestamp')) return true;
		else return false;
	}

	/**
	 * Function test if type is integer, from Dolibarr 6.0 in core.
	 *
	 * @param   array   $info   content informations of field
	 * @return                  bool
	 */
	public function myIsInt($info)
	{
		if(is_array($info))
		{
			if(isset($info['type']) && ($info['type']=='int' || $info['type']=='integer' )) return true;
			else return false;
		}
		else return false;
	}

	/**
	 * Function test if type is float, from Dolibarr 6.0 in core.
	 *
	 * @param   array   $info   content informations of field
	 * @return                  bool
	 */
	public function myIsFloat($info)
	{
		if(is_array($info))
		{
			if (isset($info['type']) && (preg_match('/^(double|real)/i', $info['type']))) return true;
			else return false;
		}
		else return false;
	}

		/**
	 * Function test if type is null, from Dolibarr 6.0 in core.
	 *
	 * @param   array   $info   content informations of field
	 * @return                  bool
	 */
	protected function myIsNull($info)
	{
		if(is_array($info))
		{
			if(isset($info['type']) && $info['type']=='null') return true;
			else return false;
		}
		else return false;
	}

	/**
	 * Function to prepare the values to insert, from Dolibarr 6.0 in core.
	 * Note $this->${field} are set by the page that make the createCommon or the updateCommon.
	 *
	 * @return array
	 */
	private function my_set_save_query()
	{
		global $conf;

		$queryarray=array();
		foreach ($this->fields as $field=>$info)	// Loop on definition of fields
		{
			// Depending on field type ('datetime', ...)
			if($this->myIsDate($info))
			{
				if(empty($this->{$field}))
				{
					$queryarray[$field] = NULL;
				}
				else
				{
					$queryarray[$field] = $this->db->idate($this->{$field});
				}
			}
			else if($this->myIsArray($info))
			{
				$queryarray[$field] = serialize($this->{$field});
			}
			else if($this->myIsInt($info))
			{
				if ($field == 'entity' && is_null($this->{$field})) $queryarray[$field]=$conf->entity;
				else
				{
					$queryarray[$field] = (int) price2num($this->{$field});
					if (empty($queryarray[$field])) $queryarray[$field]=0;		// May be reset to null later if property 'notnull' is -1 for this field.
				}
			}
			else if($this->myIsFloat($info))
			{
				$queryarray[$field] = (double) price2num($this->{$field});
				if (empty($queryarray[$field])) $queryarray[$field]=0;
			}
			else
			{
				$queryarray[$field] = $this->{$field};
			}

			if ($info['type'] == 'timestamp' && empty($queryarray[$field])) unset($queryarray[$field]);
			if (! empty($info['notnull']) && $info['notnull'] == -1 && empty($queryarray[$field])) $queryarray[$field] = null;
		}

		return $queryarray;
	}

	/**
	 * Load object in memory from the database, from Dolibarr 6.0 in core.
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchCommon($id = null, $ref = null, $objectId = null, $objectType = '')
	{
		if (empty($id) && empty($ref) && empty($objectId)) return false;

		$sql = 'SELECT '.$this->my_get_field_list();
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;

		if (!empty($id)) $sql.= ' WHERE rowid = '.$id;
		elseif (! empty($ref)) $sql.= " WHERE ref = ".$this->myQuote($ref, $this->fields['ref']);
		elseif (! empty($objectId)) $sql.= " WHERE fk_object = ".$objectId;
		if (! empty($objectType)) $sql.= " AND object_type = ".$this->myQuote($objectType, $this->fields['object_type']);

		$res = $this->db->query($sql);
		if ($res)
		{
			$obj = $this->db->fetch_object($res);
			if ($obj)
			{
				$this->mySetVarsFromFetchObj($obj);
				return $this->id;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
	}

	/**
	 * Function to load data from a SQL pointer into properties of current object $this, from Dolibarr 6.0 in core.
	 *
	 * @param   stdClass    $obj    Contain data of object from database
	 */
	private function mySetVarsFromFetchObj(&$obj)
	{
		foreach ($this->fields as $field => $info)
		{
			if($this->myIsDate($info))
			{
				if(empty($obj->{$field}) || $obj->{$field} === '0000-00-00 00:00:00' || $obj->{$field} === '1000-01-01 00:00:00') $this->{$field} = 0;
				else $this->{$field} = strtotime($obj->{$field});
			}
			elseif($this->myIsArray($info))
			{
				$this->{$field} = @unserialize($obj->{$field});
				// Hack for data not in UTF8
				if($this->{$field } === FALSE) @unserialize(utf8_decode($obj->{$field}));
			}
			elseif($this->myIsInt($info))
			{
				if ($field == 'rowid') $this->id = (int) $obj->{$field};
				else $this->{$field} = (int) $obj->{$field};
			}
			elseif($this->myIsFloat($info))
			{
				$this->{$field} = (double) $obj->{$field};
			}
			elseif($this->myIsNull($info))
			{
				$val = $obj->{$field};
				// zero is not null
				$this->{$field} = (is_null($val) || (empty($val) && $val!==0 && $val!=='0') ? null : $val);
			}
			else
			{
				$this->{$field} = $obj->{$field};
			}
		}

		// If there is no 'ref' field, we force property ->ref to ->id for a better compatibility with common functions.
		if (! isset($this->fields['ref']) && isset($this->id)) $this->ref = $this->id;
	}

	/**
	 * Function to concat keys of fields, from Dolibarr 6.0 in core.
	 *
	 * @return string
	 */
	private function my_get_field_list()
	{
		$keys = array_keys($this->fields);
		return implode(',', $keys);
	}

		/**
	 * Delete object in database, from Dolibarr 6.0 in core.
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function myDeleteCommon(User $user, $notrigger = false)
	{
		$error=0;

		$this->db->begin();

		if (! $error) {
			if (! $notrigger) {
				// Call triggers
				$result=$this->call_trigger(strtoupper(get_class($this)).'_DELETE', $user);
				if ($result < 0) { $error++; } // Do also here what you must do to rollback action if trigger fail
				// End call triggers
			}
		}

		if (! $error)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE rowid='.$this->id;

			$res = $this->db->query($sql);
			if($res===false) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * function to check dolibarr compatibility
	 *
	 * @param string $minVersion >=
	 * @param string $maxVersion <=
	 *
	 * @return return 0 (not valid) or 1 (valid)
	 */

	static function checkDolVersion($minVersion = '', $maxVersion = '')
	{
		$dolVersion = versiondolibarrarray();
		$dolMajorMinorVersion = $dolVersion[0].'.'.$dolVersion[1];

		if (empty($minVersion)) $minVersion = '5.0';
		if (empty($maxVersion)) $maxVersion = '7.0'; // debugging version
		if (version_compare($minVersion, $dolMajorMinorVersion, '<=') && version_compare($maxVersion, $dolMajorMinorVersion, '>='))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function myUpdateCommon(User $user, $notrigger = false)
	{
		global $langs;

		$error = 0;

		$now=dol_now();

		$fieldvalues = $this->my_set_save_query();
		if (array_key_exists('date_modification', $fieldvalues) && empty($fieldvalues['date_modification'])) $fieldvalues['date_modification']=$this->db->idate($now);
		if (array_key_exists('fk_user_modif', $fieldvalues) && ! ($fieldvalues['fk_user_modif'] > 0)) $fieldvalues['fk_user_modif']=$user->id;
		unset($fieldvalues['rowid']);	// The field 'rowid' is reserved field name for autoincrement field so we don't need it into update.

		$keys=array();
		$values = array();
		foreach ($fieldvalues as $k => $v) {
			$keys[$k] = $k;
			$value = $this->fields[$k];
			$values[$k] = $this->myQuote($v, $value);
			$tmp[] = $k.'='.$this->myQuote($v, $this->fields[$k]);
		}

		// Clean and check mandatory
		foreach($keys as $key)
		{
			if (preg_match('/^integer:/i', $this->fields[$key]['type']) && $values[$key] == '-1') $values[$key]='';		// This is an implicit foreign key field
			if (! empty($this->fields[$key]['foreignkey']) && $values[$key] == '-1') $values[$key]='';					// This is an explicit foreign key field

			//var_dump($key.'-'.$values[$key].'-'.($this->fields[$key]['notnull'] == 1));
			/*
			if ($this->fields[$key]['notnull'] == 1 && empty($values[$key]))
			{
				$error++;
				$this->errors[]=$langs->trans("ErrorFieldRequired", $this->fields[$key]['label']);
			}*/
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET '.implode( ',', $tmp ).' WHERE rowid='.$this->id ;

		$this->db->begin();
		if (! $error)
		{
			$res = $this->db->query($sql);
			if ($res===false)
			{
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		if (! $error && ! $notrigger) {
			// Call triggers
			$result=$this->call_trigger(strtoupper(get_class($this)).'_MODIFY',$user);
			if ($result < 0) { $error++; } //Do also here what you must do to rollback action if trigger fail
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	* Return origin object
	*
	* @param int $fk_origin  Id origin
	* @param int $origintype Type origin
	*
	* @return string
	*/
	public function getOrigin($origintype)
	{
		$origin='';
		switch ($origintype) {
			case 'commande':
				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
				$origin = new Commande($this->db);
				break;
			case 'shipping':
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				$origin = new Expedition($this->db);
				break;
			case 'order_supplier':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$origin = new CommandeFournisseur($this->db);
				break;
			case 'product':
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$origin = new Product($this->db);
				break;
			case 'propal':
				require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
				$origin = new Propal($this->db);
				break;
			case 'member':
				require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				$origin = new Adherent($this->db);
				break;
			case 'facture':
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$origin = new Facture($this->db);
				break;
			case 'contrat':
				require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
				$origin = new Contrat($this->db);
				break;
			case 'expensereport':
				require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
				$origin = new Expensereport($this->db);
				break;
			case 'fichinter':
				require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
				$origin = new Fichinter($this->db);
				break;
			case 'invoice_supplier':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$origin = new FactureFournisseur($this->db);
				break;
			case 'supplier_proposal':
				require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
				$origin = new SupplierProposal($this->db);
				break;
			case 'user':
				require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
				$origin = new User($this->db);
				break;
			case 'contact':
				require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
				$origin = new Contact($this->db);
				break;
			default:
				break;
		}

		if (empty($origin) || ! is_object($origin)) return null;
		return $origin;
	}

	/**
	* Return Url link of origin object
	*
	* @param int $fk_origin  Id origin
	* @param int $origintype Type origin
	*
	* @return string
	*/
	public function getOriginUrl($fk_origin, $origintype)
	{
		$origin=$this->getOrigin($origintype);

		if (empty($origin) || ! is_object($origin)) return '';

		if ($origin->fetch($fk_origin) > 0) {
			return $origin->getNomUrl(1);
		}

		return '';
	}
}

/**
 * Class doliesignLine. You can also remove this and generate a CRUD class for lines objects.
 */
/*
class doliesignLine
{
	// @var int ID
	public $id;
	// @var mixed Sample line property 1
	public $prop1;
	// @var mixed Sample line property 2
	public $prop2;
}
*/
