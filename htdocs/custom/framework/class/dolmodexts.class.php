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

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

dol_include_once('/framework/class/form.dolmodexts.class.php');
dol_include_once('/framework/class/updateOscssshop.class.php');
dol_include_once('/framework/class/fixversion.class.php');
dol_include_once('/framework/class/masterlink.class.php');

class DolModExts extends DolibarrModules
{
		public $code;

		/**
			@var array containt list of params for display config (dynamical config)
		*/
		static public $Display = array();
		/**
			@var array containt list of POST var put in process save (dynamical config)
		*/
		static public $CheckForm = array();
		/**
			@var 0 none 1 true
		*/
		public $greffon = 0;

		/**
			@var string Name of herit class
		*/
		public $herit = '';



    /**
      @brief Load our global setups
     */
    public function loadOscssConf()
    {
        global $langs;

        $langs->load("framework@framework");
        // create our own family
        $this->family          = "Oscss Shop";
        if($this->name == "framework")
					$this->module_position = 0;
				else
					$this->module_position = ($this->module_position + 1 );
        $this->familyinfo      = array(
						array('position' => '000'
								, 'label' => $langs->trans("Oscss Shop")
								));
        if ($this->name != 'framework') {
            // Bug 5.0.0
            // Le module 'Kpi' dépend du module 'framework' qui est manquant, aussi le module 'Kpi' peut ne pas fonctionner correctement.
            // Merci d'installer le module 'framework' ou désactiver le module 'Kpi' si vous ne souhaitez pas avoir de mauvaise surprise
            // $this->depends = array('framework');
        }
    }

    /**
     *      \brief      Function called when module is updated.
     *      \return     int             1 if OK, 0 if KO
     */
    function update()
    {
        if (isset($this->updateManager) && is_object($this->updateManager)) {
            return $this->updateManager->download();
        }
    }

    /**
     *      \brief      Function called to get distant version.
     *      \return     string              version available
     */
    function getUpdateVersion()
    {
        if (isset($this->updateManager) && is_object($this->updateManager)) {
            return $this->updateManager->getVersion();
        }
    }

    /**
    */
		public function GetFileBuild(){

			$this->code = strtolower($this->name);

			$pref = substr(DOL_DOCUMENT_ROOT,0, - strlen(DOL_URL_ROOT) );

			if(!empty($this->greffon) && file_exists( /*$pref .*/ dol_buildPath( '/'.$this->herit .'/core/build_'.$this->code .'.json', 0) ) )
				$build = json_decode( file_get_contents( /*$pref .*/ dol_buildPath( '/'.$this->herit .'/core/build_'.$this->code .'.json', 0) ) );
			elseif(file_exists( /*$pref .*/ dol_buildPath( '/'.$this->name .'/core/build.json', 0) ) )
				$build = json_decode( file_get_contents( /*$pref .*/ dol_buildPath( '/'.$this->name .'/core/build.json', 0) ) );
			elseif(file_exists(/* $pref .*/ dol_buildPath( '/'.$this->code .'/core/build.json', 0) ) )
				$build = json_decode( file_get_contents( /*$pref . */dol_buildPath( '/'.$this->code .'/core/build.json', 0) ));
		// load default descriptor based on framework build.json
			elseif(file_exists(/* $pref .*/ dol_buildPath( '/framework/core/build.json', 0) ) ){
				$build = json_decode( file_get_contents( /*$pref . */dol_buildPath( '/framework/core/build.json', 0) ));
				// Version forced at 0; for view module, put level 2 in Framework config
				$build->version =0;
				$build->revision =0;
			}

			if(is_object($build)){
				// version
				$this->version = $build->version ;
				// revision
        $this->revision = $build->revision ;

        // editor
				$this->editor_name = $build->editor->name;
        $this->editor_url = $build->editor->url;

				// For update linked
				$this->server = $build->editor->api;
				$this->api_key = $build->editor->key;

			}


			$this->const_name = 'MAIN_MODULE_'.strtoupper($this->code);

			// Name of image file used for this module.
			// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
			// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
			$this->picto = $this->code.".png@".$this->code;

			// Constantes
			$this->const = array();

			// Array to add new pages in new tabs
			$this->tabs = array();

			// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->special = 0;

			// langs
			$this->langfiles = array($this->code."@".$this->code, "users");

			// Permissions
			$this->rights = array();  // Permission array used by this module
			$this->rights_class = $this->code;
			$this->rights_admin_allowed = 0;  // Admin is always granted of permission (even when module is disabled)




			// Main menu entries
			$this->menu = array();   // List of menus to add


			// Defined all module parts (triggers, login, substitutions, menus, etc...) (0=disable,1=enable)
        $this->module_parts = array(
        );

        $this->config_page_url = array("index.php@".$this->code);
		}


    /**
     * Adds generic parts
     *
     * @return  int Error count (0 if OK)
     */
    function insert_module_parts()
    {
        global $conf;

        $error=0;

        if (is_array($this->module_parts) && ! empty($this->module_parts))
        {
            foreach($this->module_parts as $key => $value)
            {
			if (is_array($value) && count($value) == 0) continue;	// Discard empty arrays

			$entity=$conf->entity; // Reset the current entity
			$newvalue = $value;

				if($this->greffon){
// 						$back = $this->const_name;

// var_dump($key);
						if($key == 'hooks'){
							if(isset($conf->modules_parts['hooks'][$this->herit])) {
								$value = array_merge($conf->modules_parts['hooks'][$this->herit],$value);
							}

// 							var_dump($newvalue);
// print_r($conf->modules_parts['hooks'][$this->herit]);
						}
// 							$this->const_name = 'MAIN_MODULE_'.strtoupper($this->code);
// 							$key = $this->herit;


					}

			// Serialize array parameters
			if (is_array($value))
			{
				// Can defined other parameters
				if (is_array($value['data']) && ! empty($value['data']))
				{
					$newvalue = json_encode($value['data']);
					if (isset($value['entity'])) $entity = $value['entity'];
				}
				else if (isset($value['data']) && !is_array($value['data']))
				{
					$newvalue = $value['data'];
					if (isset($value['entity'])) $entity = $value['entity'];
				}
				else
				{
					$newvalue = json_encode($value);
				}
			}

//     			var_dump($value);
			// Hack for correctly support of master module
			if($this->greffon && $key == 'hooks'){
						$back = $this->const_name;
// 						if()
							$this->const_name = 'MAIN_MODULE_'.strtoupper($this->herit);
// 							$key = $this->herit;
// 					}



                $sql = "UPDATE ".MAIN_DB_PREFIX."const SET";

                $sql.= " value = ".$this->db->encrypt($newvalue, 1)." ";
                $sql.= " WHERE  name = ";
                $sql.= $this->db->encrypt($this->const_name."_".strtoupper($key), 1);
                $sql.= " LIMIT 1";
// echo $sql.'<br />';

                dol_syslog(get_class($this)."::insert_const_".$key."", LOG_DEBUG);
                $resql=$this->db->query($sql,1);
                if (! $resql)
                {
                    if ($this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS')
                    {
                        $error++;
                        $this->error=$this->db->lasterror();
                    }
                    else
                    {
                        dol_syslog(get_class($this)."::insert_const_".$key." Record already exists.", LOG_WARNING);
                    }
                }


			// Hack for correctly support of master module
// 		if($this->greffon){
// 						$back = $this->const_name;
						if($key == 'hooks')
							$this->const_name = $back;
// 							$key = $this->herit;
					}
					else{

                $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (";
                $sql.= "name";
                $sql.= ", type";
                $sql.= ", value";
                $sql.= ", note";
                $sql.= ", visible";
                $sql.= ", entity";
                $sql.= ")";
                $sql.= " VALUES (";
                $sql.= $this->db->encrypt($this->const_name."_".strtoupper($key), 1);
                $sql.= ", 'chaine'";
                $sql.= ", ".$this->db->encrypt($newvalue, 1);
                $sql.= ", null";
                $sql.= ", '0'";
                $sql.= ", ".$entity;
                $sql.= ")";
// echo $sql.'<br />';
                dol_syslog(get_class($this)."::insert_const_".$key."", LOG_DEBUG);
                $resql=$this->db->query($sql,1);
                if (! $resql)
                {
                    if ($this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS')
                    {
                        $error++;
                        $this->error=$this->db->lasterror();
                    }
                    else
                    {
                        dol_syslog(get_class($this)."::insert_const_".$key." Record already exists.", LOG_WARNING);
                    }
                }
					}

            }
        }

//         var_dump($this->module_parts);
// exit;
//         exit;
        return $error;
    }


		/**
			@fn EndLoader()
			@brief this method construct other params for config based in support params specifical this engine framework
		*/
		public function EndLoader(){
			$this->FV = new Fixversion($this->db);

			global $conf;

			$r = count($this->const);

			// adapated config for section module_parts activated
			if(isset($this->module_parts['genericmodels']) && is_array( $this->module_parts['genericmodels'] )){

				// force model in 1, core dolibarr config
// 				if(!isset($this->module_parts['genericmodels']) || empty($this->module_parts['genericmodels']) )
					$this->module_parts['models'] = 1;


				// check if dir exists
				foreach($this->module_parts['genericmodels'] as $type=>$val) {

					if($type == 'internal')
						$loop = array($this->code => $val);
					else
						$loop = $val;


					foreach($loop as $key=>$v) {
						if(is_array($v['type']) && in_array('pdf', $v['type'])){
							// add config for stock actiavted module
							$this->const[$r][0] =  strtoupper( (($type !='internal')? $this->code.'_' : '').$key )."_ADDON_PDF";
							$this->const[$r][1] = "chaine";
							$this->const[$r][2] = "";
							$this->const[$r][3] = 'Name of PDF/ODT module manager class';
							$this->const[$r][4] = 0;
							$this->const[$r][9] = array(
									'method'=>'DisplayBlockModel'
								);

							if($type !='internal') {

								$this->const[$r][9]['external'] = $key;

								$this->const[$r][9]['perms'] =( (isset($conf->{$key}) && isset($conf->global->{strtoupper($this->code).'_USE_'.strtoupper($key)}) && ((int)$conf->global->{strtoupper($this->code).'_USE_'.strtoupper($key)} == 1))? 1 : 0 );
							}
// 								$this->const[$r][6] =$key;
							$r++;
						}

						if(is_array($v['type']) &&in_array('odt', $v['type'])){
							// add config for stock actiavted module
							$this->const[$r][0] = strtoupper( (($type !='internal')? $this->code.'_' : '').$key )."_ADDON_PDF_ODT_PATH";
							$this->const[$r][1] = "chaine";
							$this->const[$r][2] = "";
							$this->const[$r][3] = 'DOL_DATA_ROOT/doctemplates/'.$key;
							$this->const[$r][4] = 0;
							if(!in_array('pdf', $v['type'])) {
								$this->const[$r][9] = array(
									'method'=>'DisplayBlockModel'
								);

								$this->const[$r][5] = 'DisplayBlockModel';

								if($type !='internal') {
									$this->const[$r][9]['external'] = $key;

									$this->const[$r][9]['perms'] =( (isset($conf->{$key}) && isset($conf->global->{strtoupper($this->code).'_USE_'.strtoupper($key)}) && ((int)$conf->global->{strtoupper($this->code).'_USE_'.strtoupper($key)} == 1))? 1 : 0 );
								}
							}
							$r++;
						}
					}
        }
			}


			// generic module ref
			if(isset($this->module_parts['numberingrule']) && is_array($this->module_parts['numberingrule']) ) {

        $r = count($this->const);

				foreach($this->module_parts['numberingrule'] as $type=>$val) {

					if($type == 'internal')
						$loop = array($this->code => $val);
					else
						$loop = $val;

					foreach($loop as $key=>$v) {

						$this->const[$r][0] = strtoupper( (($type !='internal')? $this->code.'_' : '').$key)."_ADDON";
						$this->const[$r][1] = "chaine";
						$this->const[$r][2] = "";
						$this->const[$r][3] = 'NameofNumberingRuleobjectmanagerclass'.$key;
						$this->const[$r][4] = 0;


						$this->const[$r][9] = array(
									'method'=>'DisplayBlockNumberingRule'
								);

						if($type !='internal') {
									$this->const[$r][9]['external'] = $key;

									$this->const[$r][9]['perms'] =( (isset($conf->{$key}) && isset($conf->global->{strtoupper($this->code).'_USE_'.strtoupper($key)}) && ((int)$conf->global->{strtoupper($this->code).'_USE_'.strtoupper($key)} == 1))? 1 : 0 );
								}

						$r++;
					}
        }
			}


			// generic extrafields parts
			if(isset($this->module_parts['useextrafields']) && $this->module_parts['useextrafields']==1) {
			}
			if(isset($this->module_parts['useextrafieldsline']) && $this->module_parts['useextrafieldsline']==1) {
			}


			if(isset($this->module_parts['autotabs']) && $this->module_parts['autotabs']==1){
				$this->loadtabs('/'.$this->code.'/core/tabs/', '');
			}

			/**
				Fixe var autorised in process Update config
			*/
			$this->PrepareCheckForm();

			/**
				Fix mecanical registration and update
			*/
			$key_editor_link = "FRAMEWORKAPIKEYLINK".strtoupper(str_replace(' ', '', trim($this->editor_name)));
			if (!empty($conf->global->{$key_editor_link}) && !empty($this->server) ) {
				//update
				$this->updateManager = new updateOscssshop($this->code);

				$this->updateManager->setServer($this->server);
				$this->updateManager->setKey($conf->global->{$key_editor_link});

				// compare version and auto update
				$this->updateManager->insertUpdateLink($this, $langs);
			}

		}

		/**
			@fn PrepareDisplayConfig()
			@brief this methode is called in Admin Display Constructor Page
		*/
		public function PrepareDisplayConfig(){
				global $db;
				$FormDolModExts = new FormDolModExts($db);

				foreach($this->const as $k=>$v) {

					$opt = $v[9];
					$grp = 'aa';
					// groupby
					if(isset($opt['group']) && !empty($opt['group']) )
						$grp = $opt['group'];


					if(!isset($opt['perms']) || (int)$opt['perms']>0){
						if(isset($opt['external']) && !empty($opt['external']))
							self::$Display[$grp][$v[0]] = $FormDolModExts->{$opt['method']}( $v, $this, $opt['external'], $v[2], $v[0] );
						elseif(isset($opt['method']) && !empty($opt['method']))
							self::$Display[$grp][$v[0]] = $FormDolModExts->{$opt['method']}( $v,  $this );
					}

					$v = array();
				}
		}

		/**
			@fn PrepareCheckForm()
			@brief this methode is called in Admin Display Constructor Page
		*/
		public function PrepareCheckForm(){
				global $db;

				foreach($this->const as $k=>$v) {
					$opt = $v[9];
					$grp = 'aa';
					// groupby
					if(isset($opt['group']) && !empty($opt['group']) )
						$grp = $opt['group'];

					self::$CheckForm[$grp][$v[0]] = $v[1];

				}
		}

    /**
     *      \brief      Function called to reset a module.
     *      \return     int              1 if OK, 0 if KO
     */
    function reset()
    {
        return $this->remove() && $this->init();
    }

    /**
     * 		Create tables, keys and data required by module
     * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * 		and create data commands must be stored in directory /mymodule/sql/
     * 		This function is called by this->init
     *
     * 		@return		int		<=0 if KO, >0 if OK
     */
    function load_tables()
    {
        return $this->_load_tables('/'.$this->code.'/sql/');
    }



    /**
      @brief Construct list of boxes
     */
    protected function loadtabs($path, $namespace = '')
    {
				dol_include_once('/framework/class/autotabs.class.php');
        // Boxes

        $pattern = dol_buildpath($path).'*.php';
        $files   = glob($pattern);
        $i       = 0;
        foreach ($files as $file) {

            $cl  = basename($file, '.tabs.class.php');
            $class = $namespace.'Tabs'.ucwords($cl);
//             $path.$file.'.php';
            if (true /*dol_include_once($path.basename($file))*/) {

								$sr = '';

								 $sr = '';
								 $sr.=(string)$this->FV->GetTabsByType($cl).':';
								 $sr.='+';
								 $sr.=$this->code.'tabs'.$cl.':';
								 $sr.=$this->name.'Tabs'.ucwords($cl).':';
								 $sr.=$this->code.'@'.$this->code.':'.$this->code.':';
								 $sr.='/framework/tabs/generic.php?mod='.$this->code.'&tab='.$cl.'&'.(string)$this->FV->GetNameidByType($cl).'=__ID__';


								$this->tabs[$cl] = $sr;
            }
        }
    }


    /**
      @brief Construct list of boxes
     */
    public function loadbox($path, $namespace = '')
    {
        // Boxes
        $this->boxes = array(
        );

        $pattern = dol_buildpath($path).'*.php';
        $files   = glob($pattern);
        $i       = 0;

        if(is_array($files))
        foreach ($files as $file) {

            $file  = basename($file, '.php');
            $class = $namespace.$file;
            $path.$file.'.php';
            if (dol_include_once($path.$file.'.php')) {

                $this->boxes[$i]['file'] = "$file.php@".$this->name;

                $defaulton               = array();
                if (class_exists($class)) {
                    $obj = new $class($db);

                    foreach ($obj->defaulton as $k => $row)
                        $defaulton[] = ucwords(strtolower($row));
                }

                $this->boxes[$i]['file'] = "$file.php@".$this->name;


                if (is_array($defaulton)) $this->boxes[$i]['enabledbydefaulton'] = $defaulton;
                else $this->boxes[$i]['enabledbydefaulton'] = array("newboxdefonly");

                $this->boxes[$i]['note'] = $file;


                $i++;
            }
        }
    }

    /**
     * Adds boxes
     *
     * @param   string  $option Options when disabling module ('newboxdefonly'=insert only boxes definition)
     *
     * @return  int             Error count (0 if OK)
     */
    public function insert_boxes($option = '')
    {
        global $conf;

        if( !isset($conf->dashboard) || ! $conf->dashboard->enabled )
					return parent::insert_boxes($option='');

        dol_include_once('/dashboard/class/infobox.class.php');

        $err = 0;

        if (is_array($this->boxes)) {
//             $pos_name = InfoBox::getListOfPagesForBoxes();
            $this->db->begin();

            foreach ($this->boxes as $key => $value) {



                $file               = isset($this->boxes[$key]['file']) ? $this->boxes[$key]['file'] : '';
                $note               = isset($this->boxes[$key]['note']) ? $this->boxes[$key]['note'] : '';
                $enabledbydefaulton = $this->boxes[$key]['enabledbydefaulton']; //(isset($this->boxes[$key]['enabledbydefaulton'])? $this->boxes[$key]['enabledbydefaulton'] : array('Home') );



                $pos_name = DASHBOARDInfoBox::getListOfPagesForBoxes();

                if (empty($file))
                        $file = isset($this->boxes[$key][1]) ? $this->boxes[$key][1] : ''; // For backward compatibility
                if (empty($note))
                        $note = isset($this->boxes[$key][2]) ? $this->boxes[$key][2] : ''; // For backward compatibility



// Search if boxes def already present
                $sql    = "SELECT count(*) as nb, rowid FROM ".MAIN_DB_PREFIX."boxes_def";
                $sql    .= " WHERE file = '".$this->db->escape($file)."'";
                $sql    .= " AND entity = ".$conf->entity;
                if ($note) $sql    .= " AND note ='".$this->db->escape($note)."'";
// echo $sql.'<br />'."\n";
                dol_syslog(get_class($this)."::insert_boxes", LOG_DEBUG);
                $result = $this->db->query($sql);

//                 var_dump($result);

                if ($result) {
                    $obj = $this->db->fetch_object($result);
//                     print_r($obj);
                    if ((int) $obj->nb <= 0) {

                        if (!$err) {
                            $sql   = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file, entity, note)";
                            $sql   .= " VALUES ('".$this->db->escape($file)."', ";
                            $sql   .= $conf->entity.", ";
                            $sql   .= $note ? "'".$this->db->escape($note)."'" : "null";
                            $sql   .= ") ON DUPLICATE KEY UPDATE
                file='{$this->db->escape($file)}',
                entity='{$conf->entity}'  ";
                            if ($note) $sql   .= " ,note='{$this->db->escape($note)}' ";
// echo $sql.'<br />'."\n";
                            dol_syslog(get_class($this)."::insert_boxes", LOG_DEBUG);
                            $resql = $this->db->query($sql);

                            if (!$resql) $err++;
                        }

                        $lastid = $this->db->last_insert_id(MAIN_DB_PREFIX."boxes_def", "rowid");
                    } else $lastid = $obj->rowid;
                    /*
                      if(!$err){
                      var_dump($err);
                      print_r($obj);
                      } */

                    if (!$err && !preg_match('/newboxdefonly/', $option)) {


                        foreach ($pos_name as $key2 => $val2) {


                            if (is_array($enabledbydefaulton) && in_array(ucwords(strtolower($val2)),
                                    $enabledbydefaulton)) {

                                $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (box_id,position,box_order,fk_user,entity)";
                                $sql .= " VALUES (".$lastid.", ".$key2.", '0', 0, ".$conf->entity.")ON DUPLICATE KEY UPDATE
																		box_id='{$lastid}'
																	, position='".$key2."'
																	, fk_user=0
																	, entity='{$key2->entity}'";

// echo $sql.'<br />'."\n";
//                                 dol_syslog(get_class($this)."::insert_boxes onto page ".$key2."=".$val2."", LOG_DEBUG);

                                $resql = $this->db->query($sql);
//                                        var_dump($resql);
                                if (!$resql) $err++;
//                                  var_dump($resql);
                            }
                        }
                    }

//                         if (! $err)
//                         {
//                             $this->db->commit();
//                         }
//                         else
//                         {
//                             $this->error=$this->db->lasterror();
//                             $this->db->rollback();
//                         }
//                     }
                    // else box already registered into database
                }
                else {
                    $this->error = $this->db->lasterror();
                    $err++;
                }
            }
            if (!$err) {
                $this->db->commit();
            } else {
                $this->error = $this->db->lasterror();
                $this->db->rollback();
            }
        }

        return $err;
    }

    /**
     * Removes boxes
     *
     * @return  int Error count (0 if OK)
     */
    public function delete_boxes()
    {
        global $conf;

        $err = 0;

        if (is_array($this->boxes)) {
            foreach ($this->boxes as $key => $value) {
                //$titre = $this->boxes[$key][0];
                $file = $this->boxes[$key]['file'];
                //$note  = $this->boxes[$key][2];
                // TODO If the box is also included by another module and the other module is still on, we should not remove it.
                // For the moment, we manage this with hard coded exception
                //print "Remove box ".$file.'<br>';
                if ($file == 'box_graph_product_distribution.php') {
                    if (!empty($conf->produit->enabled) || !empty($conf->service->enabled)) {
                        dol_syslog("We discard disabling of module ".$file." because another module still active require it.");
                        continue;
                    }
                }

                if (empty($file))
                        $file = isset($this->boxes[$key][1]) ? $this->boxes[$key][1] : ''; // For backward compatibility



//                 if ($this->db->type == 'sqlite3') {
//                     // sqlite doesn't support "USING" syntax.
//                     // TODO: remove this dependency.
//                     $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes ";
//                     $sql .= "WHERE ".MAIN_DB_PREFIX."boxes.box_id IN (";
//                     $sql .= "SELECT ".MAIN_DB_PREFIX."boxes_def.rowid ";
//                     $sql .= "FROM ".MAIN_DB_PREFIX."boxes_def ";
//                     $sql .= "WHERE ".MAIN_DB_PREFIX."boxes_def.file = '".$this->db->escape($file)."') ";
//                     $sql .= "AND ".MAIN_DB_PREFIX."boxes.entity = ".$conf->entity;
//                 } else {

                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."boxes_def WHERE ";
                $sql .= "  file = '".$this->db->escape($file)."'";
                $sql .= " AND entity = ".$conf->entity;

//                     echo $sql;


                $resql = $this->db->query($sql);


                if ($resql) {
                    $obj = $this->db->fetch_object($resql);

                    if ($obj->rowid > 0) {


                        $sql   = "DELETE  FROM ".MAIN_DB_PREFIX."boxes ";
                        $sql   .= " WHERE ";
                        $sql   .= " box_id = '".$obj->rowid."'";
// echo $sql;
                        $resql = $this->db->query($sql);
                        if (!$resql) {
                            $this->error = $this->db->lasterror();
                            $err++;
                        } else {
                            $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
                            $sql .= " WHERE file = '".$this->db->escape($file)."'";
                            $sql .= " AND entity = ".$conf->entity;

// 													echo $sql;
                            dol_syslog(get_class($this)."::delete_boxes", LOG_DEBUG);
                            $resql = $this->db->query($sql);


                            if (!$resql) {
                                $this->error = $this->db->lasterror();
                                $err++;
                            }
                        }
                    }
                }
            }
        }

        return $err;
    }




    /**
			@remark Compatibilité for Dolibarr < 5.0
    */

    /**
     * Gives the long description of a module. First check README-la_LA.md then README.md
     * If not markdown files found, it return translated value of the key ->descriptionlong.
     *
     * @return  string                  Long description of a module from README.md of from property.
     */
    function getDescLong()
    {
        global $langs;
        $langs->load("admin");

        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

        $pathoffile = $this->getDescLongReadmeFound();

        if ($pathoffile)     // Mostly for external modules
        {
            $content = file_get_contents($pathoffile);

            if ((float) DOL_VERSION >= 6.0)
            {
                @include_once DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';
                $content = dolMd2Html($content, 'parsedown',
                    array(
                        'doc/'=>dol_buildpath(strtolower($this->name).'/doc/', 1),
                        'img/'=>dol_buildpath(strtolower($this->name).'/img/', 1),
                        'images/'=>dol_buildpath(strtolower($this->name).'/imgages/', 1),
                    ));
            }
            else
            {
                $content = nl2br($content);
            }
        }
        else                // Mostly for internal modules
        {
            if (! empty($this->descriptionlong))
            {
                if (is_array($this->langfiles))
                {
                    foreach($this->langfiles as $val)
                    {
                        if ($val) $langs->load($val);
                    }
                }

                $content = $langs->trans($this->descriptionlong);
            }
        }

        return $content;
    }

    /**
     * Return path of file if a README file was found.
     *
     * @return  string      Path of file if a README file was found.
     */
    function getDescLongReadmeFound()
    {
        $filefound= false;

        // Define path to file README.md.
        // First check README-la_LA.md then README.md
        $pathoffile = dol_buildpath(strtolower($this->name).'/README-'.$langs->defaultlang.'.md', 0);
        if (dol_is_file($pathoffile))
        {
            $filefound = true;
        }
        if (! $filefound)
        {
            $pathoffile = dol_buildpath(strtolower($this->name).'/README.md', 0);
            if (dol_is_file($pathoffile))
            {
                $filefound = true;
            }
        }

        return ($filefound?$pathoffile:'');
    }


    /**
     * Gives the changelog. First check ChangeLog-la_LA.md then ChangeLog.md
     *
     * @return  string  Content of ChangeLog
     */
    function getChangeLog()
    {
        global $langs;
        $langs->load("admin");

        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

        $filefound= false;

        // Define path to file README.md.
        // First check README-la_LA.md then README.md
        $pathoffile = dol_buildpath(strtolower($this->name).'/ChangeLog-'.$langs->defaultlang.'.md', 0);
        if (dol_is_file($pathoffile))
        {
            $filefound = true;
        }
        if (! $filefound)
        {
            $pathoffile = dol_buildpath(strtolower($this->name).'/ChangeLog.md', 0);
            if (dol_is_file($pathoffile))
            {
                $filefound = true;
            }
        }

        if ($filefound)     // Mostly for external modules
        {
            $content = file_get_contents($pathoffile);

            if ((float) DOL_VERSION >= 6.0)
            {
                @include_once DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';
                $content = dolMd2Html($content, 'parsedown', array('doc/'=>dol_buildpath(strtolower($this->name).'/doc/', 1)));
            }
            else
            {
                $content = nl2br($content);
            }
        }

        return $content;
    }

    /**
     * Gives the publisher name
     *
     * @return  string  Publisher name
     */
    function getPublisher()
    {
        return $this->editor_name;
    }

    /**
     * Gives the publisher url
     *
     * @return  string  Publisher url
     */
    function getPublisherUrl()
    {
        return $this->editor_url;
    }

    /**
     * 		install module
     * 		This function is called by this->init
     *
     * 		@return		int		<=0 if KO, >0 if OK
     */
    function _init($array_sql, $options='')
    {
        global $conf;
        if (isset($this->masterlink) && is_array($this->masterlink) && isset($conf->masterlink) && $conf->masterlink->enabled) { // install masterlinks
            $ml = new masterlink($this->db);
            foreach ($this->masterlink as $masterlink) {
                $ml->original = $masterlink['original'];
                $ml->custom = $masterlink['custom'];
                $ml->entity = $masterlink['entity'];
                $ml->active = $masterlink['active'];
                $ml->create();
            }
        }
        return parent::_init($array_sql, $options);
    }

    /**
     * 		remove module
     * 		This function is called by this->remove
     *
     * 		@return		int		<=0 if KO, >0 if OK
     */
    function _remove($array_sql, $options='')
    {
        global $conf;
        if (isset($this->masterlink) && is_array($this->masterlink) && isset($conf->masterlink) && $conf->masterlink->enabled) { // install masterlinks
            $ml = new masterlink($this->db);
            foreach ($this->masterlink as $masterlink) {
                $ml->original = $masterlink['original'];
                $ml->custom = $masterlink['custom'];
                $ml->delete();
            }
        }
        return parent::_remove($array_sql, $options);
    }

}