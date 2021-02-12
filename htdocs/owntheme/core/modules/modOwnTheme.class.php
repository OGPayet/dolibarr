<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
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
 * 	\defgroup	OwnTheme	OwnTheme module
 * 	\brief		OwnTheme module descriptor.
 * 	\file		core/modules/modOwnTheme.class.php
 * 	\ingroup	owntheme
 * 	\brief		Description and activation file for module OwnTheme
 */

include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
/**
 * Description and activation class for module OwnTheme
 */
class modOwnTheme extends DolibarrModules
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
		$this->numero = 688810926;
		$this->rights_class = 'owntheme';

		$this->family = "Next Thèmes";
		$this->module_position = '42';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Thème de Dolibarr, Affichage responsive et Couleurs personnalisées";
		$this->version = '12.3';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->special = 0;
		$this->picto = 'thumb@owntheme';

		$this->module_parts = array(
			'menus' => 1,
			'css' => array('owntheme/css/as_style.min.css'),
			'js' => array('owntheme/js/dol_url_root.js.php','owntheme/js/as_code.min.js'),
			'hooks' => array('toprightmenu'),
		);

		$this->dirs = array();
		$this->config_page_url = array("admin.php@owntheme");
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->phpmin = array(5, 3);
		$this->need_dolibarr_version = array(4, 0);
		$this->langfiles = array("owntheme@owntheme");

		// Dictionaries
		if (! isset($conf->owntheme->enabled)) {
			$conf->owntheme=new stdClass();
			$conf->owntheme->enabled = 0;
		}
		$this->dictionaries = array();
		$this->boxes = array(); // Boxes list

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		// Exports
		$r = 0;

		// Constants
		$this->const = array ();
		$r = 0;

		$r ++;
		$this->const [$r] [0] = "MAIN_FORCETHEME";	// name
		$this->const [$r] [1] = "chaine";			// type
		$this->const [$r] [2] = 'owntheme';		// def value
		$this->const [$r] [3] = '';					// note
		$this->const [$r] [4] = 0;					// visible
		$this->const [$r] [5] = 'current';

		$r ++;
		$this->const [$r] [0] = "MAIN_MENU_STANDARD_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'owntheme_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = 'current';

		$r ++;
		$this->const [$r] [0] = "MAIN_MENUFRONT_STANDARD_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'owntheme_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = 'current';

		$r ++;
		$this->const [$r] [0] = "MAIN_MENU_SMARTPHONE_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'owntheme_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = 'current';

		$r ++;
		$this->const [$r] [0] = "MAIN_MENUFRONT_SMARTPHONE_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'owntheme_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = 'current';

		$r ++;
		$this->const [$r] [0] = "DOL_VERSION";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = '';
		$this->const [$r] [3] = 'Dolibarr version';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = 'current';

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
		$sql = array();

		$result = $this->loadTables();

		$res = dolibarr_set_const($this->db, "PACKTHEMEACTIVATEDTHEME", "modOwnTheme", 'chaine', 0, '', 0);

		// $res = dolibarr_set_const($this->db, "PACKTHEME_NEXTTHEME_ACTIF", 0, 'yesno', 0, '', 0); 	if (! $res > 0)	$error ++;
		// $res = dolibarr_set_const($this->db, "PACKTHEME_LIGHTTHEME_ACTIF", 0, 'yesno', 0, '', 0); 	if (! $res > 0)	$error ++;
		// $res = dolibarr_set_const($this->db, "PACKTHEME_OWNTHEME_ACTIF", 1, 'yesno', 0, '', 0); 	if (! $res > 0)	$error ++;
		// $res = dolibarr_set_const($this->db, "PACKTHEME_THEME3D_ACTIF", 0, 'yesno', 0, '', 0); 	if (! $res > 0)	$error ++;

		$arraythems = array("modNextTheme","modLightTheme","modTheme3d","modMenu3dResponsive","modMyTheme","modBeCreative","modBlueTheme","modOrangTheme");
		global $conf;
		foreach ($arraythems as $value) {
			$keymodulelowercase=strtolower(preg_replace('/^mod/','',$value));
			if (in_array($keymodulelowercase, $conf->modules))
				$result=unActivateModule($value);
		}

	    // on first module activation only
	    $new = dol_buildpath('/owntheme/inst/firstUse');
	    // print_r($new);
	    // die();
	    // if (file_exists($new)) {

			if ( !file_exists(dol_buildpath('/owntheme/css/as_style_custom.css')) )
				copy(dol_buildpath('/owntheme/inst/css/as_style_custom.css'),dol_buildpath('/owntheme/css/as_style_custom.css'));
			if ( !file_exists(dol_buildpath('/owntheme/js/as_code_custom.js')) )
				copy(dol_buildpath('/owntheme/inst/js/as_code_custom.js'),dol_buildpath('/owntheme/js/as_code_custom.js'));

		    // set old primary and secondary colors
			$file = dol_buildpath('/owntheme/css/as_style.min.css');
	    	$def_col1 = "#6a89cc";
	    	$def_col2 = "#60a3bc";
	    	$col1 = dolibarr_get_const($this->db,'OWNTHEME_COL1',0);
	    	$col2 = dolibarr_get_const($this->db,'OWNTHEME_COL2',0);

			if ( !empty($col1) || !empty($col2) )	{
				$file_contents = file_get_contents($file);
				if ( !empty($col1) ) $file_contents = str_replace($def_col1,$col1,$file_contents);
				if ( !empty($col2) ) $file_contents = str_replace($def_col2,$col2,$file_contents);
				file_put_contents($file,$file_contents);
			}
	    	if(!$col1) dolibarr_set_const($this->db,'OWNTHEME_COL1',$def_col1,'chaine',0,'',0);
	    	if(!$col2) dolibarr_set_const($this->db,'OWNTHEME_COL2',$def_col2,'chaine',0,'',0);

			//delete older version files
			if (file_exists(dol_buildpath('/owntheme/js/custom.min.js')))
				unlink(dol_buildpath('/owntheme/js/custom.min.js'));
			if (file_exists(dol_buildpath('/owntheme/css/style.min.css')))
				unlink(dol_buildpath('/owntheme/css/style.min.css'));

		    // update or create owntheme theme folder from inst folder
			// $source = dol_buildpath('/owntheme/inst/theme');
			// $dest = dol_buildpath('/theme');
			// cpy4($source,$dest);

			// rmdir($new);
	    // }

		// $source = dol_buildpath('/theme/eldy/img/favicon.ico');
		// $dest = dol_buildpath('/theme/owntheme/img/favicon.ico');

		// dol_copy($source, $dest, 0775, 1);

    	$source = dol_buildpath('/owntheme/inst/theme/');
		$dest = dol_buildpath('/theme/');

		//ADEDDCODE
		// cpy4($source,$dest);

		//ADEDDCODE
		$dcd = dolCopyDir($source, $dest, 0775, 1);

		//ADEDDCODE
		if($dcd){
	     	$check = dol_buildpath('/theme/owntheme');
		    // if (!file_exists($check)) {

				// replace weather images
				$source = dol_buildpath('/owntheme/img/weather.new');
				$dest = dol_buildpath('/theme/owntheme/weather');
				dolCopyDir($source,$dest, 0775, 1);

				// copy images from eldy theme
				$source = dol_buildpath('/theme/eldy/img');
				$dest = dol_buildpath('/theme/owntheme/img');
				dolCopyDir($source,$dest, 0775, 1);
		    // }

		    // ADDEDFINALVERSION
			$theperm = dol_buildpath('/theme/owntheme');
			ownthemepermissionto($theperm);

	    	// update webmail file
	    	if ( dolibarr_get_const($this->db,'MAIN_MODULE_WEBMAIL') ) {
				copy(dol_buildpath('/webmail/list_messages.php'),dol_buildpath('/owntheme/inst/webmail/list_messages.php'));
				copy(dol_buildpath('/owntheme/inst/webmail/list_messages_modified.php'),dol_buildpath('/webmail/list_messages.php'));
			}

			$installed_ver = dolibarr_get_const($this->db,'MAIN_VERSION_LAST_INSTALL',0);
			$upgraded_ver = dolibarr_get_const($this->db,'MAIN_VERSION_LAST_UPGRADE',0);
			if ($upgraded_ver!="") {
				$dol_version = $upgraded_ver;
			} else {
				$dol_version = $installed_ver;
			}

			//dolibarr_set_const($this->db,'DOL_VERSION',$dol_version,'chaine',0,'Dolibarr version',$conf->entity);
			dolibarr_set_const($this->db,'MAIN_THEME','owntheme','chaine',0,'Sets OwnTheme Theme',$conf->entity);
			dolibarr_set_const($this->db,'MAIN_MENU_STANDARD','owntheme_menu.php','chaine',0,'',$conf->entity);
			dolibarr_set_const($this->db,'MAIN_MENUFRONT_STANDARD','owntheme_menu.php','chaine',0,'',$conf->entity);
			dolibarr_set_const($this->db,'MAIN_MENU_SMARTPHONE','owntheme_menu.php','chaine',0,'',$conf->entity);
			dolibarr_set_const($this->db,'MAIN_MENUFRONT_SMARTPHONE','owntheme_menu.php','chaine',0,'',$conf->entity);

			if (!dolibarr_get_const($this->db,'OWNTHEME_COL1',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_COL1',"#6a89cc",'chaine',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_COL2',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_COL2',"#60a3bc",'chaine',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_COL_BODY_BCKGRD',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_COL_BODY_BCKGRD',"#E9E9E9",'chaine',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_COL_LOGO_BCKGRD',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_COL_LOGO_BCKGRD',"#474c80",'chaine',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_COL_TXT_MENU',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_COL_TXT_MENU',"#b8c6e5",'chaine',0,'',$conf->entity);

			if (!dolibarr_get_const($this->db,'OWNTHEME_COL_HEADER_BCKGRD',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_COL_HEADER_BCKGRD',"#474c80",'chaine',0,'',$conf->entity);
			if ( !dolibarr_get_const($this->db,'OWNTHEME_CUSTOM_CSS',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_CUSTOM_CSS',0,'yesno',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_CUSTOM_JS',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_CUSTOM_JS',0,'yesno',0,'',$conf->entity);
			// if (!dolibarr_get_const($this->db,'OWNTHEME_FIXED_MENU',0))
				dolibarr_set_const($this->db,'OWNTHEME_FIXED_MENU',0,'yesno',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_D_HEADER_FONT_SIZE',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_D_HEADER_FONT_SIZE','1.7rem','chaine',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_S_HEADER_FONT_SIZE',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_S_HEADER_FONT_SIZE','1.6rem','chaine',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_D_VMENU_FONT_SIZE',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_D_VMENU_FONT_SIZE','1.2rem','chaine',0,'',$conf->entity);
			if (!dolibarr_get_const($this->db,'OWNTHEME_S_VMENU_FONT_SIZE',$conf->entity))
				dolibarr_set_const($this->db,'OWNTHEME_S_VMENU_FONT_SIZE','1.2rem','chaine',0,'',$conf->entity);

			return $this->_init($sql, $options);
		}
		return '';
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
		$sql = array();
		global $conf;
		dolibarr_del_const($this->db,'MAIN_FORCETHEME', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENU_STANDARD_FORCED', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_STANDARD_FORCED', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENU_SMARTPHONE_FORCED', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_SMARTPHONE_FORCED', $conf->entity);

		dolibarr_del_const($this->db,'MAIN_THEME', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENU_STANDARD', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_STANDARD', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENU_SMARTPHONE', $conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_SMARTPHONE', $conf->entity);

		// Local
		dolibarr_del_const($this->db,'MAIN_MODULE_OWNTHEME_CSS',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MODULE_OWNTHEME_HOOKS',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MODULE_OWNTHEME_JS',$conf->entity);

		// Theme
		dolibarr_set_const($this->db,'MAIN_THEME','eldy','chaine',0,'',$conf->entity);
		dolibarr_set_const($this->db,'MAIN_MENU_STANDARD','eldy_menu.php','chaine',0,'',$conf->entity);
		dolibarr_set_const($this->db,'MAIN_MENUFRONT_STANDARD','eldy_menu.php','chaine',0,'',$conf->entity);
		dolibarr_set_const($this->db,'MAIN_MENU_SMARTPHONE','eldy_menu.php','chaine',0,'',$conf->entity);
		dolibarr_set_const($this->db,'MAIN_MENUFRONT_SMARTPHONE','eldy_menu.php','chaine',0,'',$conf->entity);

		dolibarr_del_const($this->db,'MAIN_FORCETHEME',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENU_STANDARD_FORCED',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_STANDARD_FORCED',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENU_SMARTPHONE_FORCED',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_SMARTPHONE_FORCED',$conf->entity);

		// $source = dol_buildpath('/owntheme/img/weather.org');
		// $dest = dol_buildpath('/theme/owntheme/weather');
		// cpy4($source,$dest);
		// @chmod($todlt, 0777);
		// dol_delete_dir_recursive($todlt,0,0);

		//ADEDDCODE
		// $todlt = dol_buildpath('/theme/owntheme');
		// removeDirRecursivelyOwnTheme($todlt);

		// if ( file_exists( dol_buildpath('/webmail/list_messages.php') ) )
		// 	copy(dol_buildpath('/owntheme/inst/webmail/list_messages.php'),dol_buildpath('/webmail/list_messages.php'));

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /owntheme/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/owntheme/sql/');
	}

}



//ADEDDCODE
function removeDirRecursivelyOwnTheme($dir){
	$count = 0;
	if (dol_is_dir($dir))
	{
		$dir_osencoded=dol_osencode($dir);
		if ($handle = opendir("$dir_osencoded"))
		{
			while (false !== ($item = readdir($handle)))
			{
				if (! utf8_check($item)) $item=utf8_encode($item);  // should be useless

				if ($item != "." && $item != "..")
				{
					if (is_dir(dol_osencode("$dir/$item")) && ! is_link(dol_osencode("$dir/$item")))
					{
						$count=removeDirRecursivelyOwnTheme("$dir/$item");
					}
					else
					{
						@chmod("$dir/$item", octdec(777));
						$result=dol_delete_file("$dir/$item",0,1);
					}
				}
			}

			closedir($handle);

			// if (empty($onlysub))
			// {
				$result=dol_delete_dir($dir, $nophperrors);
			// }
		}
	}
}


function ownthemepermissionto($source){
    if(is_dir($source)) {
    	@chmod($source, 0775);
        $dir_handle=opendir($source);
        while($file=readdir($dir_handle)){
            if($file!="." && $file!=".."){
                if(is_dir($source."/".$file)){
                    @chmod($source."/".$file, 0775);
                    ownthemepermissionto($source."/".$file);
                } else {
                    @chmod($source."/".$file, 0664);
                }
            }
        }
        closedir($dir_handle);
    } else {
        @chmod($source, 0664);
    }
}


// copy recursive
function cpy4($source, $dest){
    if(is_dir($source)) {
        $dir_handle=opendir($source);
        while($file=readdir($dir_handle)){
            if($file!="." && $file!=".."){
                if(is_dir($source."/".$file)){
                    if(!is_dir($dest."/".$file)){
                        mkdir($dest."/".$file);
                    }
                    cpy4($source."/".$file, $dest."/".$file);
                } else {
                    copy($source."/".$file, $dest."/".$file);
                }
            }
        }
        closedir($dir_handle);
    } else {
        copy($source, $dest);
    }
}


//ADEDDCODE entity=0;
