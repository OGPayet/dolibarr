<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */

dol_include_once('/framework/class/apiregistration.class.php');



class PageConfigSubModule {

	public $db,
						$module,
						$ModuleLangs,
						$filelang,
						$context,
						$headtabs,
						$greffon;

	/**
		@var object Descriptor of current module
	*/
	public static $descriptor;

	/**
		@brief constructor
	*/
	public function __construct($db, $module){
		$this->db = $db ;
		$this->module = $module;

		$this->ModuleLangs = ucwords($module);

		$this->filelang = $this->module.'@'.$this->module;


		$this->Master = new stdclass;

	}

	/**
		@fn GetDescriptor()
		@brief loader for descriptor of current module
		@return none or Descriptor Class
	*/
	public function GetDescriptor($return=false){
		global $db;
		$master = 'mod'.ucwords($this->module);
		$e = dol_include_once('/'.$this->originalmodule.'/core/modules/'.$master.'.class.php');
		if(!$e){
			$master = 'mod'.$this->module;
			$e = dol_include_once('/'.$this->originalmodule.'/core/modules/'.$master.'.class.php');
		}
		if(!$e){
			$master = 'mod'.ucwords($this->originalmodule);
			$e = dol_include_once('/'.$this->originalmodule.'/core/modules/'.$master.'.class.php');
			if(!$e){
				$master = 'mod'.$this->originalmodule;
				$e = dol_include_once('/'.$this->originalmodule.'/core/modules/'.$master.'.class.php');
			}
		}

		if(class_exists($master))
			if(!$return)
				self::$descriptor = new $master($db);
			else
				return  new $master($db);
	}


	public function GetLoadedDescriptor($greffon, $module){
		global $db, $user, $conf, $langs;

		$M = new self($db,  $this->module);

// 		$M->ReceiveContext( $greffon, $module );
		$M->originalmodule = $module;
        $M->module = $this->module;
		$D = $M->GetDescriptor(true);


		if(is_object($D)) {
			self::$descriptor = $D;
// 			$M->PrepareContext();
			self::$descriptor->PrepareDisplayConfig();
		}

		$H = $M->SubModulePrepareHead( new stdClass, $M->originalmodule );

	}



	/**
		@fn GetBuildFile()
		@brief loader for build file of current module
	*/
	protected function GetBuildFile(){
		if(file_exists(  dol_buildPath( '/'.$this->module .'/core/build.json', 0) ) )
				$build = json_decode( @file_get_contents(dol_buildPath( '/'.$this->module .'/core/build.json', 0) ) );

		if(is_object($build)){
			global $conf;

			// 			editor
			$this->editorname = $build->editor->name;
			// 			For update linked
			$build->editor->api;
			$this->Master->api_key = $this->api_key = @$build->editor->apikey;


			$cstname = "FRAMEWORKAPIKEYLINK".strtoupper(str_replace(' ', '', trim($this->editorname)));

			$this->apiregis = new ApiRegistration( $build->editor->api, $build->editor->apikey, $conf->global->{$cstname} );

		}
	}

	/**
		@fn ReceiveContext( $page )
		@brief constructor
		@param string name of current page for display, not contain ext.
	*/
	public function ReceiveContext( $page, $module = false ){

		global $langs, $conf;

		$this->currentpage = $page;
		$this->originalmodule =(( $module !=false) ? $module : $this->module );
		$this->greffon = GETPOST('greffon');

		$this->GetBuildFile();

		// Translations
		$langs->load("admin");
		$langs->load($this->filelang);

		$this->context = new stdclass();

		if($this->currentpage == 'editor'){
			$this->context->type = 'file';
			$this->context->filemd = 'EDITOR.md';
		}
		elseif($this->currentpage == 'abouts'){
			$this->context->type = 'file';
			$this->context->filemd = 'README.md';
		}
// 		elseif( empty($conf->global->{$cstname} ) || $conf->global->{$cstname} == '2FC3D1F725B83FBFAB12C5EBAD1A9'){

		elseif( isset( $this->apiregis) && !empty( $this->apiregis->api_key ) && /*substr(*/$conf->global->{$cstname}/*,2)*/ == $this->apiregis->api_key){
				$this->context->type = 'config';
				$this->currentpage =  'registration';
		}
		elseif(empty($this->currentpage) || $this->currentpage =='index' ) {
			$this->context->type = 'file';
			$this->context->filemd =  'ChangeLog';
		}
		elseif($this->currentpage == 'faq'){
			$this->context->type = 'file';
			$this->context->filemd = 'FAQ.md';
		}
		else
			$this->context->type = 'config';

		$this->GetDescriptor();


		$this->PrepareContext();

		if(is_object(self::$descriptor))
			self::$descriptor->PrepareDisplayConfig();

		$this->headtabs = $this->SubModulePrepareHead( new stdClass, $this->originalmodule );
	}

	/**
		@brief constructor
	*/
	public function DisplayPage(){
		global $langs, $conf;

		// Translations
		$langs->load("admin");
		$langs->load("framework@framework");
		$langs->load($this->filelang);


		$this->DisplayTopPage();

		if($this->context->type == 'file'){
			$this->CallLocalMd();

			$this->CallMasterMd();
		}
		else {
			$this->context->subobj->DisplayPage();
		}

		$this->DisplayBottomPage();
	}

	/**
		@brief Generic Top of page config
	*/
	protected function DisplayTopPage(){
		global $langs, $conf;

		// Translations
		$langs->load("admin");
		$langs->load($this->filelang);


		llxHeader(
			'',
			$langs->trans($this->ModuleLangs."ConfigTitle")
			);

		// Subheader
		print_fiche_titre(
			$langs->trans($this->ModuleLangs."ConfigTitle"),
			'<a href="' . dol_buildpath( '/admin/modules.php',1) .'">'. $langs->trans("BackToModuleList") . '</a>'
			);

		// Configuration header
		dol_fiche_head(
			$this->headtabs ,
			(!empty($this->currentpage)? $this->currentpage : 'index'),
			$langs->trans($this->ModuleLangs."ConfigTitle"),
			0,
			$this->filelang
			);


		echo '<br>';
	}

	/**
		@brief prepare context of page and construct all link of ressource
	*/
	protected function PrepareContext(){
		global $langs, $conf;


		if($this->currentpage == 'othermods' ){
			$this->context->subclass = $class = 'sub'.$this->currentpage;


			dol_include_once( '/framework/admin/class/'.$class.'.class.php');
			$this->context->subobj =  new $class($this);

			$this->context->subobj->PrepareContext();

			$this->originalmodule =$this->module;
			$this->module = 'framework';
		}
		elseif($this->currentpage == 'registration' ){
			$this->context->subclass = $class = 'sub'.$this->currentpage;


			dol_include_once( '/framework/admin/class/'.$class.'.class.php');
			$this->context->subobj =  new $class($this);

			$this->context->subobj->PrepareContext();

			$this->originalmodule =$this->module;
			$this->module = 'framework';
		}
		elseif(in_array($this->currentpage , array('greffon', 'extrafields', 'dynamical') ) ){
			$this->context->subclass = $class = 'sub'.$this->currentpage;

			dol_include_once( '/framework/admin/class/'.$class.'.class.php');
			$this->context->subobj =  new $class($this);

			$this->context->subobj->PrepareContext();
		}
		elseif($this->context->type == 'config' ){

			$this->context->subclass = $class = 'sub'.$this->currentpage;

			dol_include_once( '/'.$this->module .'/admin/class/'.$class.'.class.php');
			$this->context->subobj =  new $class($this);

			$this->context->subobj->PrepareContext();
		}



	}

	/**
		@fn DisplayBottomPage()
		@brief Generic Top of page config
	*/
	protected function DisplayBottomPage(){
		global $langs, $conf;

		// Translations
		$langs->load("admin");
		$langs->load($this->filelang);

		llxFooter();

		$this->db->close();
	}



	/**
		@fn CallLocalMd()
		@brief call local file for display
		@return none
	*/
	protected function CallLocalMd(){
		$path = false;

		$path = dol_buildpath('/'.$this->module.'/'.$this->context->filemd, 0);
		if(!file_exists($path))
			$path = dol_buildpath('/'.$this->module.'/'.$this->context->filemd.'.md', 0);


		if($path){
			$buffer = @file_get_contents($path);
			echo Markdown($buffer);
		}

	}

	/**
		@fn CallLocalMd()
		@brief call editor file for display
		@return none
	*/
	protected function CallMasterMd(){
		if(	$this->context->filemd == 'README.md' )
			$file = 'LICENCE.md';
		else
			$file = $this->context->filemd;

		$path = false;
		if(strtoupper($this->editorname) == 'OSCSS-SHOP'){
			$path = dol_buildpath('/framework/docs/'.$file, 0);
			if(!file_exists($path))
				$path = dol_buildpath('/framework/docs/'.$file.'.md', 0);
		}
		else {
			$path = dol_buildpath('/'.$this->module.'/docs/'.$file, 0);
			if(!file_exists($path))
				$path = dol_buildpath('/'.$this->module.'/docs/'.$file.'.md', 0);
		}

		if(!file_exists($path)) {
			$buffer = @file_get_contents($path);
			echo Markdown($buffer);
		}
	}

	/**
		@fn SubModulePrepareHead($object, $module)
		@brief this function is generaly in lib specific in module, but here, is directly implemented in this class
		@param object $object
		@param string $module current module called
	*/
	function SubModulePrepareHead($object, $module) {

    global $langs, $conf;

    $h = 0;
    $head = array();



//     $dir = dol_buildpath("/framework/admin/tpl");
//
//     if (is_dir($dir)) {
//
//         $handle = opendir($dir);
//         if (is_resource($handle)) {
//             $var = true;
//
//             while (($file = readdir($handle)) !== false) {
//
//
//                 if (preg_match('/(php|tpl)$/', $file) && $file != 'othermodule.tpl') {

//                     $name = substr($file, 0, -4);

// var_dump(self::$descriptor);
// exit;
// print_r(self::$descriptor);
// exit;

// 		self::$descriptor
		$exclude = array();

// 		if($module !='framework' ){
			$head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=registration" , 1);
			$head[$h][1] = $langs->trans(ucwords('registration'));
			$head[$h][2] = 'registration';
			$h++;
//     }
//     else
			$exclude = array('othermodule.tpl', 'extrafields.tpl');


    $dir = dol_buildpath("/" . $module . "/admin/class");

    if (is_dir($dir) && $module !='framework') {

        $handle = opendir($dir);
        if (is_resource($handle)) {
            $var = true;

            while (($file = readdir($handle)) !== false) {


                if (preg_match('/(php)$/', $file) && !in_array($file, $exclude) ) {

										// not use dol_include_once
// 										include_once($dir.'/'.$file);
										$name = substr($file, 3, -10);
//
// 											$class = 'sub'.ucwords($name);
// 											if(class_exists($class)){
// 												$subo = new $class($this);
// 											}
//
//
// 										if(!is_object($subo) || empty($subo->special) || !$subo->special) {
											$head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=" . $name, 1);
											$head[$h][1] = $langs->trans(ucwords($name));
											$head[$h][2] = $name;
											$h++;
//                     }

                }
            }
            closedir($handle);
        }
    }

    if( count(DolModExts::$Display) > 0 ) {
			$head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=dynamical", 1);
			$head[$h][1] = $langs->trans("Dynamical");
			$head[$h][2] = 'dynamical';
			$h++;
    }

// 		if($module =='framework' ) {
			$head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=greffon", 1);
			$head[$h][1] = $langs->trans("Greffon");
			$head[$h][2] = 'greffon';
			$h++;
//     }

    if( is_object(self::$descriptor) &&  $module !='framework')
			if( isset(self::$descriptor->module_parts['useextrafields']) && is_array(self::$descriptor->module_parts['useextrafields'] ) )  {

				foreach(self::$descriptor->module_parts['useextrafields'] as $row) {

					$head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=extrafields".((self::$descriptor->code== $row)?'':'&code='.$row), 1);
					$head[$h][1] = $langs->trans("Extrafields".$row);
					$head[$h][2] = 'extrafields'.$row;
					$h++;
				}
			}

    $head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php", 1);
    $head[$h][1] = $langs->trans("Note");
    $head[$h][2] = 'index';
    $h++;

//     $head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=othermods", 1);
//     $head[$h][1] = $langs->trans("othermods");
//     $head[$h][2] = 'othermods';
//     $h++;

//     complete_head_from_modules($conf, $langs, $object, $head, $h, $module);
//
//     complete_head_from_modules($conf, $langs, $object, $head, $h, $module, 'remove');

    if (file_exists(dol_buildpath("/" . $module . "/FAQ.md", 0))) {
        $head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=faq", 1);
        $head[$h][1] = $langs->trans("Faq");
        $head[$h][2] = 'faq';
        $h++;
    }

    $head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=editor", 1);
    $head[$h][1] = $langs->trans("Editor");
    $head[$h][2] = 'editor';
    $h++;

    if($module !='framework' ){
			$langs->load('framework@framework');

			$head[$h][0] = dol_buildpath("/framework/admin/index.php?page=editor" , 1);
			$head[$h][1] = $langs->trans("editorframework");
			$head[$h][2] = 'editorframework';
			$h++;
    }


    $head[$h][0] = dol_buildpath("/" . $module . "/admin/index.php?page=abouts", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    return $head;
}
}

?>