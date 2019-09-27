<?php
/**
 * \file        class/device.class.php
 * \ingroup     infoextranet
 * \brief       This file is a CRUD class file for Device (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for Device
 */
class Device extends CommonObject
{

    /**
     * @var string ID to identify managed object
     */
    public $element = 'device';
    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'infoextranet_device';
    /**
     * @var int  Does device support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;
    /**
     * @var int  Does device support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;
    /**
     * @var string String with name of icon for device. Must be the part after the 'object_' into object_device.png
     */
    public $picto = 'device@infoextranet';


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
        'rowid'             	=> array('type'=>'integer',         'label'=>'TechnicalID',         'visible'=>-1,  'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id",),
        'entity'            	=> array('type'=>'integer',         'label'=>'Entity', 'visible'=>-1, 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'index'=>1,),
        'types'             	=> array('type'=>'sellist', 'param'=>array("options" => array("infoextranet_devicetypes:label:rowid::active=1" => null)),'label'=>'Type', 'notnull' =>1,'visible'=>1,   'enabled'=>1, 'position'=>10),
        'name'              	=> array('type'=>'varchar(255)',    'label'=>'Name',                'visible'=>1,   'enabled'=>1, 'notnull'=>1, 'index'=>1, 'position'=>1, 'searchall'=>1, 'showoncombobox'=>1),
        'mark'              	=> array('type'=>'sellist', 'param'=>array("options" => array("infoextranet_devicemodels:label:rowid::active=1" => null)),    'label'=>'Marque',              'visible'=>-1, 'enabled'=>1, 'position'=>21, 'notnull'=>-1),
        'model'             	=> array('type'=>'sellist', 'param'=>array("options" => array("infoextranet_devicelabels:label:rowid::active=1" => null)),    'label'=>'Modèle',              'visible'=>-1, 'enabled'=>1, 'position'=>22, 'notnull'=>-1),
        'serial_number'     	=> array('type'=>'varchar(255)',    'label'=>'Numéro de série',     'visible'=>-1, 'enabled'=>1, 'position'=>23, 'notnull'=>-1),
        'fk_soc_maintenance'    => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'Tier de maintenance', 'visible'=>1, 'enabled'=>1, 'position'=>47, 'notnull'=>-1, 'index'=>1, 'searchall'=>1),
        'fk_con_maintenance'    => array('type'=>'integer:Contact:contact/class/contact.class.php', 'label'=>'Contact de maintenance', 'visible'=>1, 'enabled'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'searchall'=>1),
        'owner'					=> array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'Tier possédant', 'visible'=>1, 'enabled'=>1, 'position'=>15, 'notnull'=>-1, 'index'=>1, 'searchall'=>1),
        'id_ip'      			=> array('type'=>'integer:AddressExtra:custom/infoextranet/class/address.class.php', 'label'=>'Adresse', 'visible'=>0, 'enabled'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'searchall'=>1),
        'garantee_time'     	=> array('type'=>'integer',         'label'=>'Durée de garantie (mois)',    'visible'=>-1, 'enabled'=>1, 'position'=>26, 'notnull'=>-1),
        'imp_time'     	        => array('type'=>'date',         'label'=>"Date d'achat",    'visible'=>-1, 'enabled'=>1, 'position'=>26, 'notnull'=>-1),
        'os_type'           	=> array('type'=>'sellist', 'param'=>array("options" => array("infoextranet_osfirmwaretypes:label:rowid::active=1" => null)),    'label'=>'Type OS ou Firmware',              'visible'=>-1, 'enabled'=>1, 'position'=>27, 'notnull'=>-1),
        'id_role'           	=> array('type'=>'integer:Role:custom/infoextranet/class/role.class.php', 'label'=>'Rôle', 'visible'=>1, 'enabled'=>0, 'position'=>51, 'notnull'=>-1, 'index'=>1, 'searchall'=>1),
        'save_t'            	=> array('type'=>'boolean',         'label'=>'Sauvegarde',          'visible'=>-1, 'enabled'=>1, 'position'=>29, 'notnull'=>-1),
        'under_contract'    	=> array('type'=>'boolean',         'label'=>'Sous contrat',        'visible'=>-1, 'enabled'=>1, 'position'=>20, 'notnull'=>-1, 'index'=>1,),
        'id_oc'             	=> array('type'=>'integer',         'label'=>'ID inventaire Ocs',    'visible'=>-1, 'enabled'=>1, 'position'=>31, 'notnull'=>-1),
        'public_note'       	=> array('type'=>'html',            'label'=>'NotePublic',          'visible'=>-1, 'enabled'=>1, 'position'=>61, 'notnull'=>-1,),
        'date_creation'     	=> array('type'=>'datetime',        'label'=>'DateCreation',        'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
        'tms' 					=> array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1,),
        'fk_user_creat'     	=> array('type'=>'integer',         'label'=>'UserAuthor',          'visible'=>-2, 'enabled'=>1, 'position'=>510, 'notnull'=>1,),
        'fk_user_modif'     	=> array('type'=>'integer',         'label'=>'UserModif',           'visible'=>-2, 'enabled'=>1, 'position'=>511, 'notnull'=>-1,),
        'device_type'       	=> array('type'=>'varchar(255)',    'label'=>'Type',                'visible'=>-2,   'enabled'=>1, 'position'=>1),
    );

    protected $field_list = array();

    public $rowid;
    public $entity;
    public $types;
    public $name;
    public $mark;
    public $model;
    public $serial_number;
    public $fk_soc_maintenance;
    public $fk_con_maintenance;
    public $date_creation;
    public $guarantee_time;
    public $os_type;
    public $id_role;
    public $save_t;
    public $under_contract;
    public $id_contacts;
    public $id_ocs;
    public $id_ip;
    public $public_note;
    public $fk_user_creat;
    public $fk_user_modif;
    public $tms;
    public $device_type;
    public $owner;
    // END MODULEBUILDER PROPERTIES


    // If this object has a subtable with lines

    /**
     * @var int    Name of subtable line
     */
    //public $table_element_line = 'devicedet';
    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    //public $fk_element = 'fk_device';
    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    //public $class_element_line = 'Deviceline';
    /**
     * @var array  Array of child tables (child tables to delete before deleting a record)
     */
    //protected $childtables=array('devicedet');
    /**
     * @var DeviceLine[]     Array of subtable lines
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

        $this->field_list = $this->fields;
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
        $object->name = "copy_of_".$object->name;
        $object->title = $langs->trans("CopyOf")." ".$object->title;
        // ...

        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $result = $object->createCommon($user);
        if ($result < 0) {
            $error++;
            $this->error = $object->error;
            $this->errors = $object->errors;
        }else{
            $resId = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
        }

        // End
        if (!$error) {
            $this->db->commit();

            // Adding all address to the cloned device
            $devices = $this->getAllLinkedAddresses();

            if ($devices !=null) {
                foreach ($devices as $device) {
                    $object->fetch($resId);
                    $object->addDeviceToAddress($device);
                }
            }
            $roles = $this->getAllLinkedRoles();

            if ($roles !=null) {
                foreach ($roles as $role) {
                    $object->fetch($resId);
                    $object->addDeviceToRole($role);
                }
            }
            return $object;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Get all Roles linked to a Device
     *
     * @return  array                         array on succes, null on error
     */
    public function getAllLinkedRoles(){
        global $db;

        $sql = 'SELECT * FROM `llx_infoextranet_device_role`, `llx_infoextranet_role`
				WHERE llx_infoextranet_role.rowid = llx_infoextranet_device_role.fk_role
				AND llx_infoextranet_device_role.fk_device ='.$this->rowid.'
				ORDER BY llx_infoextranet_role.name';

        dol_syslog($sql, LOG_DEBUG);

        $resql = $db->query($sql);
        if ($resql)
        {
            foreach ($resql as $key => $field)
                $arr[] = $field['fk_role'];
        }
        return $arr;
    }


    /**
     * Add Device to an Address
     *
     * @param   int $addressId				 Address id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function addDeviceToAddress($addressId)
    {
        global $db;

        if ($addressId < 0)
            return -1;

        $sql = "SELECT fk_device FROM ".MAIN_DB_PREFIX."infoextranet_device_ip WHERE fk_ip=".$addressId." AND fk_device=".$this->rowid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows > 0)
            return 0;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."infoextranet_device_ip (fk_device, fk_ip, tms) VALUES ('".$this->rowid."', '".$addressId."', CURRENT_TIMESTAMP)";

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }

    /**
     * Add Device to a Role
     *
     * @param   int $roleId				    Role id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function addDeviceToRole($roleId)
    {
        global $db;

        if ($roleId < 0)
            return -1;

        $sql = "SELECT fk_device FROM ".MAIN_DB_PREFIX."infoextranet_device_role WHERE fk_role=".$roleId." AND fk_device=".$this->rowid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows > 0)
            return 0;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."infoextranet_device_role (fk_device, fk_role, tms) VALUES ('".$this->rowid."', '".$roleId."', CURRENT_TIMESTAMP)";

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }

    /**
     * Clone and object into another one
     *
     * @param  	User 	$user      	User that creates
     * @param  	int 	$fromid     Id of object to clone
     * @return 	mixed 				New object created, <0 if KO
     */
    public function cloneUser(User $user, $fromid)
    {
        return $this->createFromClone($user, $fromid);
    }

    /**
     * Load object in memory from the database
     *
     * @param   int             $id   Id object
     * @param   string          $name  name
     * @return  int             <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $name = null)
    {
        $result = $this->fetchCommon($id, $name);
        if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
        $this->rowid = $id;
        return $result;
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

        $label = '<u>' . $langs->trans("Device") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('name') . ':</b> ' . $this->name;

        $url = dol_buildpath('/infoextranet/device_card.php',1).'?id='.$this->id;

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
                $label=$langs->trans("ShowDevice");
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
        if ($withpicto != 2) $result.= $this->name;
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
     * Get the name of the Device
     *
     * @return string   return string on succes, null on error
     */
    public function getDeviceTypeName(){

	global $db;
	$ret = '';

		$sql = "SELECT label FROM ".MAIN_DB_PREFIX."infoextranet_devicetypes WHERE rowid=". $this->types;
		dol_syslog($sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if($resql){
			$ret = $db->fetch_row($resql);
			return $ret[0];
		}

		return $ret;
	}


    /**
     * Add device for thirdparty
     *
     * @param   int         $socid          Thirdparty id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function addDevice($socid)
    {
        global $db;

        if ($socid < 0) {
            return -1;
        }

        $sql = "SELECT fk_device FROM ".MAIN_DB_PREFIX."infoextranet_societe_device WHERE fk_soc=".$socid." AND fk_device=".$this->rowid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
       // die(var_dump($sql));
        if ($resql && $resql->num_rows > 0) {
            return 0;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."infoextranet_societe_device (fk_soc, fk_device, tms) VALUES ('".$socid."', '".$this->rowid."', CURRENT_TIMESTAMP)";

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);

        if (!$resql) {
            return -1;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."infoextranet_device SET owner = ".$socid. ", under_contract = null WHERE rowid = ".$this->rowid ;
        $resql = $db->query($sql);


        if ($resql) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Delete device of thirdparty
     *
     * @param   int         $socid          Thirdparty id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function deleteDevice($socid)
    {
        global $db;

        if ($socid < 0)
            return -1;

        $sql = "SELECT fk_device FROM ".MAIN_DB_PREFIX."infoextranet_societe_device WHERE fk_soc=".$socid." AND fk_device=".$this->rowid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows == 0)
            return 0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."infoextranet_societe_device WHERE fk_soc=".$socid." AND fk_device=".$this->rowid;

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }

	/**
	 * Get all Thirdparty of an device
	 *
	 * @param   int         $id         Thirdparty of device
	 * @return  array                   array($id, $id, ....)
	 */
	function getOwners()
	{
		global $db;

		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'infoextranet_societe_device sd INNER JOIN '.MAIN_DB_PREFIX.'societe AS a ON sd.fk_soc = a.rowid WHERE sd.fk_device='.$this->rowid.' ORDER BY a.nom';
		$arr = array();
		$resql = $db->query($sql);
		if ($resql)
		{
			foreach ($resql as $key => $field)
				$arr[] = $field;
		}

		return $arr;
	}

    /**
     *  Get all Addresses linked to a Device
     *
     * @return  array                       array on succes, null on error
     */
	public function getAllLinkedAddresses(){
		global $db;
		$staticaddresse = new AddressExtra($db);

		$sql = 'SELECT * FROM `llx_infoextranet_device_ip`, `llx_infoextranet_ip`
				WHERE llx_infoextranet_ip.rowid = llx_infoextranet_device_ip.fk_ip
				AND llx_infoextranet_device_ip.fk_device ='.$this->rowid.'
				ORDER BY llx_infoextranet_ip.type';

		dol_syslog($sql, LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql)
		{
			foreach ($resql as $key => $field)
				$arr[] = $field['fk_ip'];
		}
		return $arr;
	}

    /**
     *  Get all Devices linked to an Address
     *
     * @return  array                         array on succes, null on error
     */
	public function getAllLinkedDevicesLink(){
		global $db;
		$return = '';

		$addresses = $this->getAllLinkedAddresses();

		if($addresses){
			foreach ($addresses as $address){
				$addresseOject = new AddressExtra($db);
				$addresseOject->fetch($address);

				$ret[] = $addresseOject->getNomUrl(1);
			}

			$return = implode('<br/>', $ret);

			return $return;

		}

		return $return;
	}

    /**
     * Delete app of maintain thirdparty
     *
     * @param   int         $socid          Thirdparty id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function deleteDeviceThirdparty($socid)
    {
        global $db;

        if ($socid < 0)
            return -1;

        $sql =  "SELECT * FROM ".MAIN_DB_PREFIX."infoextranet_device WHERE 1";
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows == 0)
            return 0;

        $sql = "UPDATE ".MAIN_DB_PREFIX."infoextranet_device SET fk_soc_maintenance= null WHERE fk_soc_maintenance=".$socid." AND rowid=".$this->rowid;

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }

    /**
     * Add Device for maintain thirdparty
     *
     * @param   int         $socid          Thirdparty id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function addDeviceThirdparty($socid)
    {
        global $db;

        if ($socid < 0)
            return -1;
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "infoextranet_device WHERE 1";
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows == 0)
            return 0;

        $sql = "UPDATE " . MAIN_DB_PREFIX . "infoextranet_device SET fk_soc_maintenance=" . $socid . " WHERE rowid=" . $this->rowid;

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);

        if ($resql) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Delete a Device on Contract linked to a Thirdparty
     *
     * @param $socid                    Thirdparty id
     * @return int                      > 0 on succes, < 0 on error and 0 if already exist
     */
    public function deleteDeviceOnContractByThirdparty($socid)
    {
        global $db;

        if ($socid < 0)
            return -1;

        $sql =  "SELECT * FROM ".MAIN_DB_PREFIX."infoextranet_device WHERE 1";
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows == 0)
            return 0;

        $sql = "UPDATE ".MAIN_DB_PREFIX."infoextranet_device SET under_contract= null WHERE owner=".$socid." AND rowid=".$this->rowid;

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }

    /**
     * Add a Device on Contract to link to a Thirdparty
     *
     * @param $socid                    Thirdparty id
     * @return int                      > 0 on succes, < 0 on error and 0 if already exist
     */
    public function addDeviceOnContractByThirdparty($socid)
    {
        global $db;

        if ($socid < 0)
            return -1;
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "infoextranet_device WHERE 1";
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows == 0)
            return 0;

        $sql = "UPDATE " . MAIN_DB_PREFIX . "infoextranet_device SET owner = " . $socid . ", under_contract = 1 WHERE rowid = " . $this->rowid;

        //die(var_dump($sql));
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);

        if (!$resql) {
            return -1;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."infoextranet_societe_device SET fk_soc = null , fk_device = null WHERE fk_device = ".$this->rowid ;
        $resql = $db->query($sql);

        if ($resql) {
            return 1;
        } else {
            return -1;
        }
    }


    /**
     * Add role for a Device
     *
     * @param   int         $deviceid       Device id
     * @param   int         $roleid         Role id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function addRole($deviceid, $roleid)
    {
        global $db;

        if ($roleid < 0)
            return -1;

        $sql = "SELECT fk_role FROM ".MAIN_DB_PREFIX."infoextranet_device_role WHERE fk_role=".$roleid." AND fk_device=".$deviceid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows > 0)
            return 0;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."infoextranet_device_role (fk_device, fk_role, tms) VALUES ('".$deviceid."', '".$roleid."', CURRENT_TIMESTAMP)";

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }

    /**
     * Delete role for a Device
     *
     * @param   int         $deviceid       Device id
     * @param   int         $roleid         Role id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function deleteRole($deviceid, $roleid)
    {
        global $db;

        if ($roleid < 0)
            return -1;

        $sql = "SELECT fk_role FROM ".MAIN_DB_PREFIX."infoextranet_device_role WHERE fk_role=".$roleid." AND fk_device=".$deviceid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows == 0)
            return 0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."infoextranet_device_role WHERE fk_role=".$roleid." AND fk_device=".$deviceid;

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }
}
