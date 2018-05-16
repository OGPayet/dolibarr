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



dol_include_once('/framework/class/dolmodexts.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

Class subDynamical {
	/**
	*/
	public $apiregis;


	/**
	*/
	static public $type2label =array('') ;
	/**
	*/
	static public $tmptype2label  ;
	/**
	*/
	static public $extrafields  ;
	/**
	*/
	static public $form  ;

	public function __construct( PageConfigSubModule $Master){

		$this->Master = $Master;

		$this->result = array();

		$this->urlapi = '';




	}



    public function PrepareContext() {
			global $langs, $conf, $db, $html, $mysoc, $result, $submodisprev, $master, $subarray, $user, $form;
// 			$this->GetDescriptor();


			$action = GETPOST('action','alpha');
			$label = GETPOST('label','alpha');
			$scandir = GETPOST('scandir','alpha');

			$value = GETPOST('value','alpha');




			$form = self::$form = new Form($this->Master->db);




			switch($action){
				/**
					@remarks Section Of generic Config
				*/
				case 'setmainoptions':
					$process = DolModExts::$CheckForm['setmainoptions'];
				break;

				/**
					@remarks Section Of Modele Config
				*/
				case 'set':
				case 'del':
				case 'setdoc':
				case 'setModuleOptions':
				case 'specimen':

					if( empty($type ) )
						$type = PageConfigSubModule::$descriptor->code;
					if( empty($name ) )
						$name = $this->child->name;
					if( empty($keyconf ) )
						$keyconf =strtoupper($type).'_ADDON_PDF';
				break;

				default:
					$process = DolModExts::$CheckForm['aa'];
			}





			switch($action){
				/**
					@remarks Section Of generic Config
				*/
				case 'setmainoptions':

// 				var_dump($process);
					foreach($process as $key=>$row) {
// 					var_dump(__file__);
							if(isset($_POST[$key]) || isset($_GET[$key])){



								$e=dolibarr_set_const($db, $key,GETPOST($key),$row,0,'',$conf->entity);
								$conf->global->{$key} = GETPOST($key);
//
// 								PageConfigSubModule::$descriptor->EndLoader();
// 								PageConfigSubModule::$descriptor->PrepareDisplayConfig();
							}
					}

			 break;


			 case 'setmod':
// 					// TODO Verifier si module numerotation choisi peut etre active
// 					// par appel methode canBeActivated
//
					$modeGP = $process[$scandir];
					if($modeGP =='chaine')
						$modeGP = 'alpha';


					dolibarr_set_const($db, $scandir ,GETPOST('value',$modeGP),$process[$scandir],0,'',$conf->entity);
// 				}
				break ;


				/**
					@remarks Section Of Modele Config
				*/
				case 'set':

						$ret = addDocumentModel($value, $type, $label, $scandir);
				break;

				case 'del':

					$ret = delDocumentModel($value, $type);
					if ($ret > 0)
					{
						if ($conf->global->{$keyconf} == "$value") dolibarr_del_const($db, $keyconf,$conf->entity);
					}

				break;


				// Set default model
				case 'setdoc':

					dolibarr_set_const($db, $keyconf,$value,'chaine',0,'',$conf->entity);

					// On active le modele
					$ret = delDocumentModel($value, $type);
					if ($ret > 0)
					{
						$ret = addDocumentModel($value, $type, $label, $scandir);
					}

				break;

				case 'specimen':

					$modele=GETPOST('module','alpha');

					dol_include_once('/'.$type."/class/".$type.".class.php");
					$class= ucwords($type);
					$obj = new $class($db);
					$obj->initAsSpecimen();

					// Search template files
					$file=''; $classname=''; $filefound=0;
					$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
					foreach($dirmodels as $reldir)
					{
						$file=dol_buildpath($reldir."core/modules/".$type."/doc/pdf_".$modele.".modules.php",0);
						if (file_exists($file))
						{
							$filefound=1;
							$classname = "pdf_".$modele;
							break;
						}
					}

					if ($filefound)
					{
						require_once $file;

						$module = new $classname($db);

						if ($module->write_file($obj,$langs) > 0)
						{
							header("Location: ".DOL_URL_ROOT."/document.php?modulepart=".$type."&file=SPECIMEN.pdf");
							return;
						}
						else
						{
							setEventMessages($module->error, $module->errors, 'errors');
							dol_syslog($module->error, LOG_ERR);
						}
					}
					else
					{
						setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
						dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
					}
				break;

				case 'setModuleOptions':

					$post_size=count($_POST);

					$db->begin();

					for($i=0;$i < $post_size;$i++)
					{
						if (array_key_exists('param'.$i,$_POST))
						{
							$param=GETPOST("param".$i,'alpha');
							$value=GETPOST("value".$i,'alpha');
							if ($param) $res = dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
							if (! $res > 0) $error++;
						}
					}
					if (! $error)
					{
						$db->commit();
						setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
					}
					else
					{
						$db->rollback();
								setEventMessages($langs->trans("Error"), null, 'errors');
					}
				break;

			}
    }

    /**
      @brief constructor
     */
    public function DisplayPage() {
        global $db,$langs, $conf, $html, $result, $currentmod, $submodisprev, $master, $subarray, $form;



				foreach(DolModExts::$Display as $k=>$Row){

					if( is_string($k) && strlen($k)>2 ) {
// 					print_r($Row);

						print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="page" value="'.GETPOST('page').'">';
						print '<input type="hidden" name="action" value="'.$k.'">';

						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre">';
						print "<td>".$langs->trans("Parameters")."</td>\n";
						print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
						print '<td width="80">&nbsp;</td></tr>'."\n";
					}
// 				print_r($Row);
					foreach($Row as $key=>$r)
						print  $r;

					if( is_string($k) && strlen($k)>2 ) {
						print '</table></form>';
						print '<br>';
					}

				}
    }

}
