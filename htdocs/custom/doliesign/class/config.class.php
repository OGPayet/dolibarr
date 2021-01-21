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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file        class/config.class.php
 * \ingroup     doliesign
 * \brief       This file is a CRUD class file for Config (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/doliesign/class/doliesign.class.php');

/**
 * Class for DoliEsignConfig
 */
class DoliEsignConfig extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'doliesign_config';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'doliesign_config';
	/**
	 * @var array  Does config support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;
	/**
	 * @var string String with name of icon for config. Must be the part after the 'object_' into object_config.png
	 */
	public $picto = 'config@doliesign';


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
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'visible'=>1, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id",),
		'module' => array('type'=>'varchar(64)', 'label'=>'Module', 'visible'=>1, 'enabled'=>1, 'position'=>40, 'notnull'=>1,),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'visible'=>-1, 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'index'=>1,),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'visible'=>1, 'enabled'=>1, 'position'=>30, 'notnull'=>-1, 'searchall'=>1, 'help'=>"Help text",),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1,),
		'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'visible'=>-2, 'enabled'=>1, 'position'=>510, 'notnull'=>1,),
		'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'visible'=>-2, 'enabled'=>1, 'position'=>511, 'notnull'=>-1,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'visible'=>-2, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Disable', '1'=>'Enable'), 'default'=>'1'),
		'fk_c_type_contact' => array('type'=>'integer', 'label'=>'TypeContact', 'visible'=>1, 'enabled'=>1, 'position'=>70, 'notnull'=>-1,),
		'sign_coordinate' => array('type'=>'varchar(64)', 'label'=>'SignCoordinate', 'visible'=>1, 'enabled'=>1, 'position'=>80, 'notnull'=>1,),
		'cgv_sign_coordinate' => array('type'=>'varchar(64)', 'label'=>'CgvSignCoordinate', 'visible'=>1, 'enabled'=>1, 'position'=>100, 'notnull'=>-1,)
	);
	public $rowid;
	public $entity;
	public $label;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	public $module;
	public $fk_c_type_contact;
	public $sign_coordinate;
	public $cgv_sign_coordinate;
	// END MODULEBUILDER PROPERTIES



	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'configdet';
	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_config';
	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'Configline';
	/**
	 * @var array  Array of child tables (child tables to delete before deleting a record)
	 */
	//protected $childtables=array('configdet');
	/**
	 * @var ConfigLine[]     Array of subtable lines
	 */
	//public $lines = array();

	public $elementList = array();

	public $defaultFkTypeContact = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf,$langs;

		$this->db = $db;

		if (empty($conf->multicompany->enabled)) $this->fields['entity']['enabled']=0;
		$langs->load("orders");
		$langs->load("contracts");
		$langs->load("projects");
		$langs->load("propal");
		$langs->load("bills");
		$langs->load("interventions");
		$this->elementList = array(
				''				    => '',
				//'societe'           => $langs->trans('ThirdParty'),
				//'invoice_supplier'  => $langs->trans('SupplierBill'),
				//'order_supplier'    => $langs->trans('SupplierOrder'),
				//'project'           => $langs->trans('Project'),
				//'project_task'      => $langs->trans('Task'),
				//'agenda'			=> $langs->trans('Agenda'),
				'contrat'           => $langs->trans('Contract'),
				'propal'            => $langs->trans('Proposal'),
				'commande'          => $langs->trans('Order'),
				//'facture'           => $langs->trans('Bill'),
				//'resource'           => $langs->trans('Resource'),
				'fichinter'         => $langs->trans('InterventionCard')
		);
		if (! empty($conf->global->MAIN_SUPPORT_SHARED_CONTACT_BETWEEN_THIRDPARTIES)) $this->elementList["societe"] = $langs->trans('ThirdParty');

		//complete_elementList_with_modules($this->elementList);

		asort($this->elementList);

		$this->defaultFkTypeContact = array(
			'propal' => 41,
			'commande' => 101,
			'fichinter' => 131,
			'contrat' => 22
		);

		if (! empty($conf->global->DOLIESIGN_CGV_FILENAME)) {
			$this->fields['cgv_sign_coordinate']['visible'] = 1;
		} else {
			$this->fields['cgv_sign_coordinate']['visible'] = -1;
		}
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
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		$result = $this->fetchCommon($id);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load list of object id's in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchListId($module = '')
	{
		$idList = array();

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE status = 1";
		if (isset($module)) {
			$sql.= " AND module = '".$this->db->escape($module)."'";
		}

		$resql=$this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$this->dataset=null;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$idList[$i++] = $obj->rowid;
			}
			$this->db->free($resql);

			return $idList;
		} else {
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetchListId ".$this->error, LOG_ERR);
			return -1;
		}
	}

	public function validate()
	{
		$result = false;
		if (preg_match('/^[0-9]*,[0-9]*,[0-9]*,[0-9]*/', $this->sign_coordinate)) {
			$result = true;
		} else {
			$this->errors[]="DoliEsignInvalidCoordinates";
			$result = false;
		}
		if (! empty($this->cgv_sign_coordinate)) {
			if (preg_match('/^[0-9]*,[0-9]*,[0-9]*,[0-9]*/', $this->cgv_sign_coordinate)) {
				$result = true;
			} else {
				$this->errors[]="DoliEsignInvalidCgvCoordinates";
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	/*public function fetchLines()
	{
		$this->lines=array();

		// Load lines with object ConfigLine

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

        $label = '<u>' . $langs->trans("Config") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Id') . ':</b> ' . $this->id;

        $url = dol_buildpath('/doliesign/config_card.php',1).'?id='.$this->id;

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
                $label=$langs->trans("ShowConfig");
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
		if ($withpicto != 2) $result.= $this->id;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
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
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			$prefix='';
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 6)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
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
		global $conf,$langs,$form,$user;

		if (! is_object($form))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form=new Form($this->db);
		}
		require_once DOL_DOCUMENT_ROOT .'/core/class/html.formmail.class.php';
		$formMail=new FormMail($this->db);



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
		if ($key == 'rowid' && method_exists($this, 'getNomUrl')) $value=$this->getNomUrl(0, '', 1, '', 1);

		elseif ($key == 'fk_user_creat') {
			if (!empty($value)) {
				if (isset($object->fk_user_creat)) {
					$value = $this->getOriginUrl($object->fk_user_creat, 'user');
				}
			}
		}
		elseif ($key == 'fk_user_modif') {
			if (!empty($value)) {
				if (isset($object->fk_user_modif)) {
					$value = $this->getOriginUrl($object->fk_user_modif, 'user');
				}
			}
		}
		elseif ($key == 'module') {
			if (!empty($value)) {
				$value = $this->elementList[$value];
			}
		}
		elseif ($key == 'fk_c_type_contact') {
			if (!empty($value)) {
				$typeContacts = $this->get_type_contact_label($object->module, 'all');
				$value = $typeContacts[$value];
			}
		}

		elseif ($key == 'status' && method_exists($this, 'getLibStatut')) $value=$this->getLibStatut(3);
		elseif ($type == 'date')
		{
			$value=dol_print_date($value,'day');
		}
		elseif ($type == 'datetime')
		{
			$value=dol_print_date($value,'dayhour');
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
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param  array   		$val	       Array of properties for field to show
	 * @param  string  		$key           Key of attribute
	 * @param  string  		$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  		$moreparam     To add more parametes on html input tag
	 * @param  string  		$keysuffix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$showsize      Value for css to define size. May also be a numeric.
	 * @return string
	 */
	function showInputField($val, $key, $value, $action='', $moreparam='', $keysuffix='', $keyprefix='', $showsize=0)
	{
		global $conf,$langs,$form;

		if (! is_object($form))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form=new Form($this->db);
		}

		$objectid = $this->id;

		$label= $val['label'];
		$type = $val['type'];
		$size = $val['css'];

		// Convert var to be able to share same code than showInputField of extrafields
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
		$hidden=(abs($val['visible'])!=1 ? 1 : 0);
		$help=$val['help'];

		if ($computed)
		{
			if (! preg_match('/^search_/', $keyprefix)) return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
			else return '';
		}

		// Use in priorit showsize from parameters, then $val['css'] then autodefine
		if (empty($showsize) && ! empty($val['css']))
		{
			$showsize = $val['css'];
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
		//var_dump($showsize.' '.$size);

		if (in_array($type,array('date','datetime')))
		{
			$tmp=explode(',',$size);
			$newsize=$tmp[0];

			$showtime = in_array($type,array('datetime')) ? 1 : 0;

			// Do not show current date when field not required (see select_date() method)
			if (!$required && $value == '') $value = '-1';

			// TODO Must also support $moreparam
			$out = $form->select_date($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, ($keyprefix != 'search_' ? 1 : 0), 1, 0, 1);
		}
		elseif ($key == 'module') {
			// only editable available in create mode
			if ($action == 'edit') {
				$out = $form->selectarray('module', $this->elementList,$value, 0, 0, 0, '', 0, 0, 1);
				// workaround for disabled selectarray which dous not post
				$out.='<input type="hidden" name="module" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'"'.($moreparam?$moreparam:'').'>';
			} else {
				$out = $form->selectarray('module', $this->elementList,$value);
			}

		}
		elseif ($key == 'fk_c_type_contact') {
			if (empty($value)) {
				$value=$object->defaultFkTypeContact[$this->module];
			}
			$typeContacts = $this->get_type_contact_label($this->module, 'all');
			$out = $form->selectarray('fk_c_type_contact', $typeContacts, $value);
		}
		elseif (in_array($type,array('int','integer')))
		{
			$tmp=explode(',',$size);
			$newsize=$tmp[0];
			$out='<input type="text" class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$newsize.'" value="'.$value.'"'.($moreparam?$moreparam:'').'>';
		}
		elseif (preg_match('/varchar/', $type))
		{
			$out='<input type="text" class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$size.'" value="'.dol_escape_htmltag($value).'"'.($moreparam?$moreparam:'').'>';
		}
		elseif (in_array($type, array('mail', 'phone', 'url')))
		{
			$out='<input type="text" class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'>';
		}
		elseif ($type == 'text')
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor=new DolEditor($keyprefix.$key.$keysuffix,$value,'',200,'dolibarr_notes','In',false,false,0,ROWS_5,'90%');
			$out=$doleditor->Create(1);
		}
		elseif ($type == 'html')
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor=new DolEditor($keyprefix.$key.$keysuffix,$value,'',200,'dolibarr_notes','In',false,false,! empty($conf->fckeditor->enabled) && $conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_5,'90%');
			$out=$doleditor->Create(1);
		}
		elseif ($type == 'boolean')
		{
			$checked='';
			if (!empty($value)) {
				$checked=' checked value="1" ';
			} else {
				$checked=' value="1" ';
			}
			$out='<input type="checkbox" class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam?$moreparam:'').'>';
		}
		elseif ($type == 'price')
		{
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value=price($value);
			}
			$out='<input type="text" class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'> '.$langs->getCurrencySymbol($conf->currency);
		}
		elseif ($type == 'double')
		{
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value=price($value);
			}
			$out='<input type="text" class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'> ';
		}
		elseif ($type == 'select')
		{
			$out = '';
			if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out.= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out.='<select class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam?$moreparam:'').'>';
			if ((! isset($val['default'])) || ($val['notnull'] != 1)) $out.='<option value="0">&nbsp;</option>';
			if (empty($value) && ! empty($default)) $value = $default;
			foreach ($param['options'] as $key => $val)
			{
				if ((string) $key == '') continue;
				list($val, $parent) = explode('|', $val);
				$out.='<option value="'.$key.'"';
				$out.= (((string) $value == (string) $key)?' selected':'');
				$out.= (!empty($parent)?' parent="'.$parent.'"':'');
				$out.='>'.$val.'</option>';
			}
			$out.='</select>';
		}
		elseif ($type == 'sellist')
		{
			$out = '';
			if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out.= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out.='<select class="flat '.$showsize.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam?$moreparam:'').'>';
			if (is_array($param['options']))
			{
				$param_list=array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList=(empty($InfoFieldList[2])?'rowid':$InfoFieldList[2].' as rowid');


				if (count($InfoFieldList) > 4 && ! empty($InfoFieldList[4]))
				{
					if (strpos($InfoFieldList[4], 'extra.') !== false)
					{
						$keyList='main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList=$InfoFieldList[2].' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && ! empty($InfoFieldList[3]))
				{
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList.= ', '.$parentField;
				}

				$fields_label = explode('|',$InfoFieldList[1]);
				if (is_array($fields_label))
				{
					$keyList .=', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere='';
				$sql = 'SELECT '.$keyList;
				$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
				if (!empty($InfoFieldList[4]))
				{
					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$')!==false) {
						$InfoFieldList[4]=str_replace('$SEL$','SELECT',$InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$')!==false && !empty($objectid)) {
						$InfoFieldList[4]=str_replace('$ID$',$objectid,$InfoFieldList[4]);
					} else {
						$InfoFieldList[4]=str_replace('$ID$','0',$InfoFieldList[4]);
					}
					//We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra')!==false)
					{
						$sql.= ' as main, '.MAIN_DB_PREFIX .$InfoFieldList[0].'_extrafields as extra';
						$sqlwhere.= ' WHERE extra.fk_object=main.'.$InfoFieldList[2]. ' AND '.$InfoFieldList[4];
					}
					else
					{
						$sqlwhere.= ' WHERE '.$InfoFieldList[4];
					}
				}
				else
				{
					$sqlwhere.= ' WHERE 1=1';
				}
				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0],array('tablewithentity')))
				{
					$sqlwhere.= ' AND entity = '.$conf->entity;
				}
				$sql.=$sqlwhere;
				//print $sql;

				$sql .= ' ORDER BY ' . implode(', ', $fields_label);

				dol_syslog(get_class($this).'::showInputField type=sellist', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					$out.='<option value="0">&nbsp;</option>';
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num)
					{
						$labeltoshow='';
						$obj = $this->db->fetch_object($resql);

						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|',$InfoFieldList[1]);
						if(is_array($fields_label))
						{
							$notrans = true;
							foreach ($fields_label as $field_toshow)
							{
								$labeltoshow.= $obj->$field_toshow.' ';
							}
						}
						else
						{
							$labeltoshow=$obj->{$InfoFieldList[1]};
						}
						$labeltoshow=dol_trunc($labeltoshow,45);

						if ($value==$obj->rowid)
						{
							foreach ($fields_label as $field_toshow)
							{
								$translabel=$langs->trans($obj->$field_toshow);
								if ($translabel!=$obj->$field_toshow) {
									$labeltoshow=dol_trunc($translabel,18).' ';
								}else {
									$labeltoshow=dol_trunc($obj->$field_toshow,18).' ';
								}
							}
							$out.='<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
						}
						else
						{
							if(!$notrans)
							{
								$translabel=$langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel!=$obj->{$InfoFieldList[1]}) {
									$labeltoshow=dol_trunc($translabel,18);
								}
								else {
									$labeltoshow=dol_trunc($obj->{$InfoFieldList[1]},18);
								}
							}
							if (empty($labeltoshow)) $labeltoshow='(not defined)';
							if ($value==$obj->rowid)
							{
								$out.='<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
							}

							if (!empty($InfoFieldList[3]))
							{
								$parent = $parentName.':'.$obj->{$parentField};
							}

							$out.='<option value="'.$obj->rowid.'"';
							$out.= ($value==$obj->rowid?' selected':'');
							$out.= (!empty($parent)?' parent="'.$parent.'"':'');
							$out.='>'.$labeltoshow.'</option>';
						}

						$i++;
					}
					$this->db->free($resql);
				}
				else {
					print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
				}
			}
			$out.='</select>';
		}
		elseif ($type == 'checkbox')
		{
			$value_arr=explode(',',$value);
			$out=$form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options'])?null:$param['options']), $value_arr, '', 0, '', 0, '100%');
		}
		elseif ($type == 'radio')
		{
			$out='';
			foreach ($param['options'] as $keyopt => $val)
			{
				$out.='<input class="flat '.$showsize.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam?$moreparam:'');
				$out.=' value="'.$keyopt.'"';
				$out.=' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
				$out.= ($value==$keyopt?'checked':'');
				$out.='/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$val.'</label><br>';
			}
		}
		elseif ($type == 'chkbxlst')
		{
			if (is_array($value)) {
				$value_arr = $value;
			}
			else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2] . ' as rowid');

				if (count($InfoFieldList) > 3 && ! empty($InfoFieldList[3])) {
					list ( $parentName, $parentField ) = explode('|', $InfoFieldList[3]);
					$keyList .= ', ' . $parentField;
				}
				if (count($InfoFieldList) > 4 && ! empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.' . $InfoFieldList[2] . ' as rowid';
					} else {
						$keyList = $InfoFieldList[2] . ' as rowid';
					}
				}

				$fields_label = explode('|', $InfoFieldList[1]);
				if (is_array($fields_label)) {
					$keyList .= ', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere = '';
				$sql = 'SELECT ' . $keyList;
				$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
				if (! empty($InfoFieldList[4])) {

					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$')!==false) {
						$InfoFieldList[4]=str_replace('$SEL$','SELECT',$InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$')!==false && !empty($objectid)) {
						$InfoFieldList[4]=str_replace('$ID$',$objectid,$InfoFieldList[4]);
					} else {
						$InfoFieldList[4]=str_replace('$ID$','0',$InfoFieldList[4]);
					}

					// We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra') !== false) {
						$sql .= ' as main, ' . MAIN_DB_PREFIX . $InfoFieldList[0] . '_extrafields as extra';
						$sqlwhere .= ' WHERE extra.fk_object=main.' . $InfoFieldList[2] . ' AND ' . $InfoFieldList[4];
					} else {
						$sqlwhere .= ' WHERE ' . $InfoFieldList[4];
					}
				} else {
					$sqlwhere .= ' WHERE 1=1';
				}
				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0], array ('tablewithentity')))
				{
					$sqlwhere .= ' AND entity = ' . $conf->entity;
				}
				// $sql.=preg_replace('/^ AND /','',$sqlwhere);
				// print $sql;

				$sql .= $sqlwhere;
				dol_syslog(get_class($this) . '::showInputField type=chkbxlst',LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;

					$data=array();

					while ( $i < $num ) {
						$labeltoshow = '';
						$obj = $this->db->fetch_object($resql);

						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);
						if (is_array($fields_label)) {
							$notrans = true;
							foreach ( $fields_label as $field_toshow ) {
								$labeltoshow .= $obj->$field_toshow . ' ';
							}
						} else {
							$labeltoshow = $obj->{$InfoFieldList[1]};
						}
						$labeltoshow = dol_trunc($labeltoshow, 45);

						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							foreach ( $fields_label as $field_toshow ) {
								$translabel = $langs->trans($obj->$field_toshow);
								if ($translabel != $obj->$field_toshow) {
									$labeltoshow = dol_trunc($translabel, 18) . ' ';
								} else {
									$labeltoshow = dol_trunc($obj->$field_toshow, 18) . ' ';
								}
							}

							$data[$obj->rowid]=$labeltoshow;

						} else {
							if (! $notrans) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$labeltoshow = dol_trunc($translabel, 18);
								} else {
									$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
								}
							}
							if (empty($labeltoshow))
								$labeltoshow = '(not defined)';

								if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
									$data[$obj->rowid]=$labeltoshow;
								}

								if (! empty($InfoFieldList[3])) {
									$parent = $parentName . ':' . $obj->{$parentField};
								}

								$data[$obj->rowid]=$labeltoshow;
						}

						$i ++;
					}
					$this->db->free($resql);

					$out=$form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');

				} else {
					print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
				}
			}
			$out .= '</select>';
		}
		elseif ($type == 'link')
		{
			$param_list=array_keys($param['options']);				// $param_list='ObjectName:classPath'
			$showempty=(($val['notnull'] == 1 && $val['default'] != '')?0:1);
			$out=$form->selectForForms($param_list[0], $keyprefix.$key.$keysuffix, $value, $showempty);
		}
		elseif ($type == 'password')
		{
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			$out='<input type="'.($keyprefix=='search_'?'text':'password').'" class="flat '.$showsize.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'>';
		}
		if (!empty($hidden)) {
			$out='<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
		}
		/* Add comments
		 if ($type == 'date') $out.=' (YYYY-MM-DD)';
		 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
		 */
		return $out;
	}

	/**
	 *      Return array with list of possible values for type of contacts
	 *
	 *      @param	string	$source     'internal', 'external' or 'all'
	 *      @param	string	$order		Sort order by : 'position', 'code', 'rowid'...
	 *      @param  int		$option     0=Return array id->label, 1=Return array code->label
	 *      @param  int		$activeonly 0=all status of contact, 1=only the active
	 *		@param	string	$code		Type of contact (Example: 'CUSTOMER', 'SERVICE')
	 *      @return array       		Array list of type of contacts (id->label if option=0, code->label if option=1)
	 */
	function get_type_contact_label($element, $source='external', $order='position')
	{
		global $langs;

		if (empty($order)) $order='position';
		if ($order == 'position') $order.=',code';

		$tab = array();
		$sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle, tc.position";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql.= " WHERE tc.element='".$this->db->escape($element)."'";
		$sql.= " AND tc.active=1"; // only the active types
		if (! empty($source) && $source != 'all') $sql.= " AND tc.source='".$this->db->escape($source)."'";
		$sql.= $this->db->order($order,'ASC');

		//print "sql=".$sql;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$transkey="TypeContact_".$this->element."_".$source."_".$obj->code;
				$libelle_type=($langs->trans($transkey)!=$transkey ? $langs->trans($transkey) : $obj->libelle);
				$tab[$obj->rowid]=$libelle_type;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->lasterror();
			//dol_print_error($this->db);
			return null;
		}
	}

	/**
	 *      Return array with list of possible values for type of contacts
	 *
	 *      @param	string	$source     'internal', 'external' or 'all'
	 *      @param	string	$order		Sort order by : 'position', 'code', 'rowid'...
	 *      @param  int		$option     0=Return array id->label, 1=Return array code->label
	 *      @param  int		$activeonly 0=all status of contact, 1=only the active
	 *		@param	string	$code		Type of contact (Example: 'CUSTOMER', 'SERVICE')
	 *      @return array       		Array list of type of contacts (id->label if option=0, code->label if option=1)
	 */
	function get_type_contact_code($element, $source='external', $order='position')
	{
		global $langs;

		if (empty($order)) $order='position';
		if ($order == 'position') $order.=',code';

		$tab = array();
		$sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle, tc.position";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql.= " WHERE tc.element='".$this->db->escape($element)."'";
		$sql.= " AND tc.active=1"; // only the active types
		if (! empty($source) && $source != 'all') $sql.= " AND tc.source='".$this->db->escape($source)."'";
		$sql.= $this->db->order($order,'ASC');

		//print "sql=".$sql;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$tab[$obj->rowid]=$obj->code;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->lasterror();
			//dol_print_error($this->db);
			return null;
		}
	}

	/**
	 *      Return array with list of possible values for type of contacts
	 *
	 *      @param	string	$element    L'élément du type de contact
	 *      @return array       		Array list of type of contacts (id->label if option=0, code->label if option=1)
	 */
	function get_source_contact_code($element)
	{
		global $langs;

		$tab = array();
		$sql = "SELECT DISTINCT tc.rowid, tc.source";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql.= " WHERE tc.element='".$this->db->escape($element)."'";
		$sql.= " AND tc.active=1"; // only the active types

		//print "sql=".$sql;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$tab[$obj->rowid]=$obj->source;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->lasterror();
			//dol_print_error($this->db);
			return null;
		}
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
			default:
				break;
		}

		if (empty($origin) || ! is_object($origin)) return '';

		if ($origin->fetch($fk_origin) > 0) {
			return $origin->getNomUrl(1);
		}

		return '';
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
	public function fetchCommon($id = null, $ref = null, $morewhere = '')
	{
		if (empty($id) && empty($ref)) return false;

		$sql = 'SELECT '.$this->my_get_field_list();
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;

		if (!empty($id)) $sql.= ' WHERE rowid = '.$id;

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
}

/**
 * Class ConfigLine. You can also remove this and generate a CRUD class for lines objects.
 */
/*
class ConfigLine
{
	// @var int ID
	public $id;
	// @var mixed Sample line property 1
	public $prop1;
	// @var mixed Sample line property 2
	public $prop2;
}
*/
