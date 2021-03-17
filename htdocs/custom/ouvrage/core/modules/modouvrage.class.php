<?php
/* Ouvrage
 * Copyright (C) 2017       Inovea-conseil.com     <info@inovea-conseil.com>
 */

/**
 * \defgroup SMS Satisfaction
 * \file    core/modules/modouvrage.class.php
 * \ingroup ouvrage
 *
 * Ouvrage
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

/**
 *  Description and activation class for module ouvrage
 */
class modOuvrage extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        $this->numero = 432406;

        $this->rights_class = 'ouvrage';

        $this->family = "Inovea Conseil";
        $this->special = 0;

        $this->module_position = 500;

        $this->name = "ouvrage";

        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Module432406Desc";
        $this->editor_url = 'https://www.inovea-conseil.com';

        $this->version = '3.1.1';

        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

        $this->picto = 'inoveaconseil@ouvrage';

        $this->module_parts = array(
            /*'css' => array(''),*/
            'hooks' => array(
                'invoicecard',
                'propalcard',
                'ordercard',
                'pdf_getlineunit'
            ),
            'triggers' => 1,
            'models' => 1,
        );

        $this->dirs = array();

        // Config pages. Put here list of php page, stored into dolitest/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@ouvrage");

        // Dependencies
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array('modMilestone');
        $this->phpmin = array(5, 0);
        $this->need_dolibarr_version = array(7, 0);
        $this->langfiles = array("ouvrage@ouvrage");

        $this->const = array();
        $country = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
        if ($country[0] == $conf->entity && $country[2] == "France") {
            $this->editor_url = "<a target='_blank' href='https://www.inovea-conseil.com/'>www.inovea-conseil.com</a> (<a target='_blank' href='https://www.dolibiz.com/wp-content/uploads/attestation/attestation-" . $this->name . "-" . $this->version . ".pdf'>Attestation NF525</a>)";
        } else {
            $this->editor_url = 'https://www.inovea-conseil.com';
        }
        $this->url_last_version = "https://www.dolibiz.com/wp-content/uploads/lastversion/last_version-ouvrage.txt";

        $this->tabs = array();

        if (!isset($conf->ouvrage) || !isset($conf->ouvrage->enabled)) {
            $conf->ouvrage = new stdClass();
            $conf->ouvrage->enabled = 0;
        }

        // Dictionaries
        $this->dictionaries = array();
        $this->boxes = array();

        // Cronjobs
        $this->cronjobs = array();

        // Permissions
        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = $this->numero . $r;    // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->trans("RightsO1");    // Permission label
        $this->rights[$r][3] = 1;                    // Permission by default for new user (0/1)
        $this->rights[$r][4] = 'read';                // In php code, permission will be checked by test if ($user->rights->affectedvehicle->level1->level2)
        $this->rights[$r][5] = '';

        $r++;
        $this->rights[$r][0] = $this->numero . $r;
        $this->rights[$r][1] = $langs->trans("RightsO2");
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'write';
        $this->rights[$r][5] = '';
        $r++;

        $this->rights[$r][0] = $this->numero . $r;
        $this->rights[$r][1] = $langs->trans("RightsO3");
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'delete';
        $this->rights[$r][5] = '';

        $r = 0;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=products',            // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'mainmenu' => 'products',
            'leftmenu' => 'ouvrage',
            'type' => 'left',                            // This is a Left menu entry
            'titre' => $langs->trans($conf->global->OUVRAGE_TYPE . 'OUVRAGES'),
            'url' => '/ouvrage/list.php?mainmenu=products&leftmenu=ouvrage',
            'langs' => 'ouvrage@ouvrage',            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 200,
            'enabled' => '$conf->ouvrage->enabled',  // Define condition to show or hide menu entry. Use '$conf->ouvrage->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms' => '$user->rights->ouvrage->read',                // Use 'perms'=>'$user->rights->ouvrage->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 2);
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=products,fk_leftmenu=ouvrage',            // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'mainmenu' => 'products',
            'type' => 'left',                            // This is a Left menu entry
            'titre' => $langs->trans($conf->global->OUVRAGE_TYPE . 'NEW_OUVRAGE'),
            'url' => '/ouvrage/card.php?mainmenu=products&leftmenu=ouvrage&action=create',
            'langs' => 'ouvrage@ouvrage',            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 201,
            'enabled' => '$conf->ouvrage->enabled',  // Define condition to show or hide menu entry. Use '$conf->ouvrage->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms' => '$user->rights->ouvrage->write',                // Use 'perms'=>'$user->rights->ouvrage->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 2);
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=products,fk_leftmenu=ouvrage',            // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'mainmenu' => 'products',
            'type' => 'left',                            // This is a Left menu entry
            'titre' => 'LISTE_OUVRAGES',
            'url' => '/ouvrage/list.php?mainmenu=products&leftmenu=ouvrage',
            'langs' => 'ouvrage@ouvrage',            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 202,
            'enabled' => '$conf->ouvrage->enabled',  // Define condition to show or hide menu entry. Use '$conf->ouvrage->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms' => '$user->rights->ouvrage->read',                // Use 'perms'=>'$user->rights->ouvrage->level1->level2' if you want your menu with a permission rules
            'target' => '',
            'user' => 2);
        //$this->menu = array();
        $r = 0;
        $r = 1;
    }

    /**
     * Init function
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return     int                1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf;

        $sql = array();

        $this->_load_tables('/ouvrage/sql/');

        $sql = array('INSERT IGNORE INTO ' . MAIN_DB_PREFIX . 'document_model (nom ,entity ,type) VALUES (\'ouvrage\', \'1\', \'propal\');',
            'INSERT IGNORE INTO ' . MAIN_DB_PREFIX . 'document_model (nom ,entity ,type) VALUES (\'ouvrage_fact\', \'1\', \'invoice\');',
            'INSERT IGNORE INTO ' . MAIN_DB_PREFIX . 'document_model (nom ,entity ,type) VALUES (\'ouvrage_com\', \'1\', \'order\');'
        );
      
        dolibarr_set_const($this->db, "PRODUIT_USE_SEARCH_TO_SELECT", 1);
        if (empty(dolibarr_get_const($this->db, "OUVRAGE_QUANTITY_STEP", $conf->entity))) {
            dolibarr_set_const($this->db, "OUVRAGE_QUANTITY_STEP", 1, 'double(24,8)', 0, '', $conf->entity);
        }

        dolibarr_set_const($this->db, "PROPALE_ADDON_PDF", 'ouvrage');
        dolibarr_set_const($this->db, "COMMANDE_ADDON_PDF", 'ouvrage_com');
        dolibarr_set_const($this->db, "FACTURE_ADDON_PDF", 'ouvrage_fact');

        dolibarr_set_const($this->db, "MAIN_SECURITY_CSRF_WITH_TOKEN", '0');

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return     int                1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array(
            'DELETE FROM ' . MAIN_DB_PREFIX . 'document_model WHERE nom LIKE "ouvrage" AND type IN ("invoice","order","propal") AND entity = 1;',
        );

        dolibarr_del_const($this->db, "PROPALE_ADDON_PDF");
        dolibarr_del_const($this->db, "COMMANDE_ADDON_PDF");
        dolibarr_del_const($this->db, "FACTURE_ADDON_PDF");

        return $this->_remove($sql, $options);
    }
}
