<?php
/* Change Tiers - Change Third Party since propal, invoice or order card
 * Copyright (C) 2018       Inovea-conseil.com     <info@inovea-conseil.com>
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module MyModule
 */
class modChangetiers extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
    public function __construct($db) {
        global $langs,$conf;

        $this->db = $db;

        $this->numero = 432446;

        $this->rights_class = 'Changetiers';

        $this->family = "Inovea Conseil";
	$this->special = 0;

        $this->module_position = 500;

        $this->name = "changetiers";

        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Module432446Desc";
        $this->editor_name = 'Inovea Conseil';
        $this->editor_url = 'https://www.inovea-conseil.com';

        $this->version = '1.5';

        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        $this->picto='inoveaconseil@changetiers';

        $this->module_parts = array(
            'js' => array(/*'/changetiers/js/conf_changetiers.js',*/ '/changetiers/js/changetiers.js'),
            'hooks' => array(
                'propalcard',
                'invoicecard',
                'ordercard',
                'ordersuppliercard',
                'supplier_proposalcard',
                'invoicesuppliercard',
                'expeditioncard',
            ),
            //'triggers' => 1,
        );

        $this->dirs = array();

        // Config pages. Put here list of php page, stored into dolitest/admin directory, to use to setup module.
        $this->config_page_url = array("admin.php@changetiers");

        // Dependencies
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->phpmin = array(5,0);
        $this->need_dolibarr_version = array(5,0);
        $this->langfiles = array("changetiers@changetiers");
        $this->warnings_activation = array('FR'=>'WarningNoteModuleInoveaConseilForFrenchLaw');
        $this->const = array();

        $this->tabs = array();

        if (! isset($conf->changetiers) || ! isset($conf->changetiers->enabled)) {
                $conf->changetiers=new stdClass();
                $conf->changetiers->enabled=0;
        }

        // Dictionaries
        $this->dictionaries=array();
        $this->boxes = array();

        // Cronjobs
        $this->cronjobs = array();

        $this->menu = array();
        $r=0;
        $r=1;
    }

    /**
     * Init function
     *
     * @param      string	$options    Options when enabling module ('', 'noboxes')
     * @return     int             	1 if OK, 0 if KO
     */
    public function init($options='')
    {
        $sql = array();

        //écriture du fichier de config js
        //$file = __DIR__.'/../../js/conf_changetiers.js';
        //file_put_contents($file, '');

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param      string	$options    Options when enabling module ('', 'noboxes')
     * @return     int             	1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }

}
