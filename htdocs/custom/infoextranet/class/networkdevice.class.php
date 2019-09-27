<?php
/**
 * \file        class/networkdevice.class.php
 * \ingroup     infoextranet
 * \brief       This file is a CRUD class file for NetworkDevice(Create/Read/Update/Delete)
 */


// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/custom/infoextranet/class/device.class.php';

/**
 * Class for NetworkDevice
 */

class NetworkDevice extends Device
{

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
    public $network_fields = array(
            'mac_adress'           => array('type'=>'varchar(255)',    'label'=>'Adresse MAC',         'visible'=>1,   'enabled'=>1,   'notnull'=>1,   'index'=>1,     'position'=>10),
            'login'                => array('type'=>'varchar(255)',    'label'=>'Identifiant',         'visible'=>1,   'enabled'=>1,   'notnull'=>1,   'index'=>1,     'position'=>10),
            'password'             => array('type'=>'varchar(255)',    'label'=>'Mot de passe',        'visible'=>1,   'enabled'=>1,   'notnull'=>1,   'index'=>1,     'position'=>10),
    );
    public $mac_adress;
    public $login;
    public $password;

    // END MODULEBUILDER PROPERTIES

    // If this object has a subtable with lines

    /**
     * @var int    Name of subtable line
     */
    //public $table_element_line = 'networkdevicedet';
    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    //public $fk_element = 'fk_networkdevice';
    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    //public $class_element_line = 'NetworkDeviceline';
    /**
     * @var array  Array of child tables (child tables to delete before deleting a record)
     */
    //protected $childtables=array('networkdevicedet');
    /**
     * @var NetworkDeviceLine[]     Array of subtable lines
     */
    //public $lines = array();


    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db);

        $this->fields = array_merge($this->field_list, $this->network_fields);
        $this->element = 'networkdevice';
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

        $label = '<u>' . $langs->trans("NetworkDevice") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('name') . ':</b> ' . $this->name;

        $url = dol_buildpath('/infoextranet/networkdevice_card.php',1).'?id='.$this->id;

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
                $label=$langs->trans("ShowNetworkDevice");
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
     * Add device for thirdparty
     *
     * @param   int         $socid          Thirdparty id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function addNetworkDevice($socid)
    {
        global $db;

        if ($socid < 0)
            return -1;

        $sql = "SELECT fk_device FROM ".MAIN_DB_PREFIX."infoextranet_societe_device WHERE fk_soc=".$socid." AND fk_device=".$this->rowid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows > 0)
            return 0;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."infoextranet_societe_device (fk_soc, fk_device, tms) VALUES ('".$socid."', '".$this->rowid."', CURRENT_TIMESTAMP)";

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }

    /**
     * Delete device of thirdparty
     *
     * @param   int         $socid          Thirdparty id
     * @return  int                         > 0 on succes, < 0 on error and 0 if already exist
     */
    public function deleteNetworkDevice($socid)
    {
        global $db;

        if ($socid < 0)
            return -1;

        $sql = "SELECT fk_networkdevice FROM ".MAIN_DB_PREFIX."infoextranet_societe_device WHERE fk_soc=".$socid." AND fk_networkdevice=".$this->rowid;
        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql && $resql->num_rows == 0)
            return 0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."infoextranet_societe_device WHERE fk_soc=".$socid." AND fk_networkdevice=".$this->rowid;

        dol_syslog($sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
            return 1;
        else
            return -1;
    }


}
