<?php

/* Copyright (C) 2014		 Oscim       <support@oscim.fr>
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


 require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

Class subgreffon {

    public function __construct(PageConfigSubModule $Master) {

        $this->Master = $Master;
    }

    public function PrepareContext() {
        global $langs, $conf, $html, $user;

        $html = new Form($this->Master->db);
				$db = $this->Master->db;


//         if (GETPOST("action") == 'setvalue') {
//
//             dolibarr_set_const($this->Master->db, "CODE_ACTIONCOMM_DOLMESSAGE", GETPOST("code_actioncomm_dolmessage"), 'chaine', 0, '', $conf->entity);
//
//             dolibarr_set_const($this->Master->db, "DOLMESSAGE_TABS_TIERS", GETPOST("dolmessage_tabs_tiers"), 'integer', 0, '', $conf->entity);
//         }
				$action = GETPOST("action") ;
				$value = GETPOST("value") ;

				if ($action == 'set' && $user->admin)
				{
						$resarray = activateModule($value);
						if (! empty($resarray['errors'])) setEventMessages('', $resarray['errors'], 'errors');
					else
					{
							//var_dump($resarray);exit;
							if ($resarray['nbperms'] > 0)
							{
									$tmpsql="SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."user WHERE admin <> 1";
									$resqltmp=$db->query($tmpsql);
									if ($resqltmp)
									{
											$obj=$db->fetch_object($resqltmp);
											//var_dump($obj->nb);exit;
											if ($obj && $obj->nb > 1)
											{
													$msg = $langs->trans('ModuleEnabledAdminMustCheckRights');
													setEventMessages($msg, null, 'warnings');
											}
									}
									else dol_print_error($db);
							}
					}
						header("Location: ".$_SERVER["PHP_SELF"]."?page=greffon&mode=".$mode.$param.($page_y?'&page_y='.$page_y:''));
					exit;
				}

				if ($action == 'reset' && $user->admin)
				{
						$result=unActivateModule($value);
						if ($result) setEventMessages($result, null, 'errors');
						header("Location: ".$_SERVER["PHP_SELF"]."?page=greffon&mode=".$mode.$param.($page_y?'&page_y='.$page_y:''));
					exit;
				}



    }

    /**
      @brief constructor
     */
    public function DisplayPage() {
        global $langs, $conf, $html, $hookmanager;

        // Translations
        $langs->load("admin");
        $langs->load($this->Master->filelang);

				$mode = 'common';
        $db = $this->db;
				$hookmanager->initHooks(array('adminmodules','globaladmin', 'admingreffon'));



$modulesdir = array(dol_buildpath('/'.$this->Master->originalmodule.'/core/modules/',0 )); //dolGetModulesDirs();

// print_r($modulesdir);
$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$i = 0;	// is a sequencer of modules found
$j = 0;	// j is module number. Automatically affected if module number not defined.
$modNameLoaded=array();

foreach ($modulesdir as $dir)
{
	// Load modules attributes in arrays (name, numero, orders) from dir directory
	//print $dir."\n<br>";
	dol_syslog("Scan directory ".$dir." for module descriptor files (modXXX.class.php)");
	$handle=@opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
			//print "$i ".$file."\n<br>";
		    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
		    {
		        $modName = substr($file, 0, dol_strlen($file) - 10);

		        if ($modName && substr($modName,  3)  !=$this->Master->originalmodule)
		        {
				if (! empty($modNameLoaded[$modName]))
				{
					$mesg="Error: Module ".$modName." was found twice: Into ".$modNameLoaded[$modName]." and ".$dir.". You probably have an old file on your disk.<br>";
					setEventMessages($mesg, null, 'warnings');
					dol_syslog($mesg, LOG_ERR);
						continue;
				}

		            try
		            {
// 		            echo $dir.$file."<br />";
		                $res=include_once $dir.$file;
		                if (class_exists($modName))
						{
							try {
				                $objMod = new $modName($db);
								$modNameLoaded[$modName]=$dir;

				        if (! $objMod->numero > 0 && $modName != 'modUser')
					{
						dol_syslog('The module descriptor '.$modName.' must have a numero property', LOG_ERR);
					}
								$j = $objMod->numero;

							$modulequalified=1;

							// We discard modules according to features level (PS: if module is activated we always show it)
							$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));
							if ($objMod->version == 'development'  && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 2))) $modulequalified=0;
							if ($objMod->version == 'experimental' && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 1))) $modulequalified=0;
								if (preg_match('/deprecated/', $objMod->version) && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL >= 0))) $modulequalified=0;

							// We discard modules according to property disabled
// 		    					if (! empty($objMod->hidden)) $modulequalified=0;

							if ($modulequalified > 0)
							{
							    $publisher=dol_escape_htmltag($objMod->getPublisher());
							    $external=($objMod->isCoreOrExternalModule() == 'external');
							    if ($external)
							    {
							        if ($publisher)
							        {
							            $arrayofnatures['external_'.$publisher]=$langs->trans("External").' - '.$publisher;
							        }
							        else
							        {
							            $arrayofnatures['external_']=$langs->trans("External").' - '.$langs->trans("UnknownPublishers");
							        }
							    }
							    ksort($arrayofnatures);
							}

							// Define array $categ with categ with at least one qualified module
							if ($modulequalified > 0)
							{
								$modules[$i] = $objMod;
					            $filename[$i]= $modName;

					            $special = $objMod->special;

					            // Gives the possibility to the module, to provide his own family info and position of this family
					            if (is_array($objMod->familyinfo) && !empty($objMod->familyinfo)) {
							if (!is_array($familyinfo)) $familyinfo=array();
							$familyinfo = array_merge($familyinfo, $objMod->familyinfo);
							$familykey = key($objMod->familyinfo);
					            } else {
							$familykey = $objMod->family;
					            }

					            $moduleposition = ($objMod->module_position?$objMod->module_position:'500');
					            if ($moduleposition == 500 && ($objMod->isCoreOrExternalModule() == 'external'))
					            {
					                $moduleposition = 800;
					            }

					            if ($special == 1) $familykey='interface';

					            $orders[$i]  = $familyinfo[$familykey]['position']."_".$familykey."_".$moduleposition."_".$j;   // Sort by family, then by module position then number
								$dirmod[$i]  = $dir;
								//print $i.'-'.$dirmod[$i].'<br>';
					            // Set categ[$i]
								$specialstring = isset($specialtostring[$special])?$specialtostring[$special]:'unknown';
					            if ($objMod->version == 'development' || $objMod->version == 'experimental') $specialstring='expdev';
								if (isset($categ[$specialstring])) $categ[$specialstring]++;					// Array of all different modules categories
					            else $categ[$specialstring]=1;
								$j++;
					            $i++;
							}
							else dol_syslog("Module ".get_class($objMod)." not qualified");
							}
					catch(Exception $e)
					{
					     dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
					}
						}
				else
						{
							print "Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)<br>";
						}
					}
		            catch(Exception $e)
		            {
		                 dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
		            }
		        }
		    }
		}
		closedir($handle);
	}
	else
	{
		dol_syslog("htdocs/admin/modulehelp.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
	}
}


asort($orders);
// var_dump($orders);
// var_dump($categ);
// var_dump($modules);

//             	print_r($modules);
//     	exit;
//         dol_include_once('/' . $this->Master->module . '/admin/tpl/' . $this->Master->currentpage . '.tpl');

// 				foreeach()






		$form = new Form($db);



/*
if ($mode == 'common')
{*/
    dol_set_focus('#search_keyword');

    print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.GETPOST('page').'">';

    dol_fiche_head($head, $mode, '', -1);

    $moreforfilter = '';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('Keyword') . ': <input type="text" id="search_keyword" name="search_keyword" value="'.dol_escape_htmltag($search_keyword).'">';
    $moreforfilter.= '</div>';
    $moreforfilter.='<div class="divsearchfield">';
//     $moreforfilter.= $langs->trans('Origin') . ': '.$form->selectarray('search_nature', $arrayofnatures, dol_escape_htmltag($search_nature), 1);
    $moreforfilter.= '</div>';
    if (! empty($conf->global->MAIN_FEATURES_LEVEL))
    {
        $array_version = array('stable'=>$langs->transnoentitiesnoconv("Stable"));
        if ($conf->global->MAIN_FEATURES_LEVEL < 0) $array_version['deprecated']=$langs->trans("Deprecated");
        if ($conf->global->MAIN_FEATURES_LEVEL > 0) $array_version['experimental']=$langs->trans("Experimental");
        if ($conf->global->MAIN_FEATURES_LEVEL > 1) $array_version['development']=$langs->trans("Development");
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.= $langs->trans('Version') . ': '.$form->selectarray('search_version', $array_version, $search_version, 1);
        $moreforfilter.= '</div>';
    }
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('Status') . ': '.$form->selectarray('search_status', array('active'=>$langs->transnoentitiesnoconv("Enabled"), 'disabled'=>$langs->transnoentitiesnoconv("Disabled")), $search_status, 1);
    $moreforfilter.= '</div>';
    $moreforfilter.=' ';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.='<input type="submit" name="buttonsubmit" class="button" value="'.dol_escape_htmltag($langs->trans("Refresh")).'">';
    $moreforfilter.=' ';
    $moreforfilter.='<input type="submit" name="buttonreset" class="button" value="'.dol_escape_htmltag($langs->trans("Reset")).'">';
    $moreforfilter.= '</div>';

    if (! empty($moreforfilter))
    {
        print $moreforfilter;
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
    }

    $moreforfilter='';

    print '<div class="clearboth"></div><br>';

    $parameters=array();
    $reshook=$hookmanager->executeHooks('insertExtraHeader',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    // Show list of modules

    $oldfamily='';

    foreach ($orders as $key => $value)
    {
//     var_dump($key,$value);
        $tab=explode('_',$value);
        $familyposition=$tab[0]; $familykey=$tab[1]; $module_position=$tab[2]; $numero=$tab[3];

        $modName = $filename[$key];
	$objMod  = $modules[$key];
	$dirofmodule = $dirmod[$key];

//     	  var_dump($key,$modName);
// //     	print_r($objMod);
//     	exit;

	$special = $objMod->special;

//     	print $objMod->name." - ".$key." - ".$objMod->special.' - '.$objMod->version."<br>";
	//if (($mode != (isset($specialtostring[$special])?$specialtostring[$special]:'unknown') && $mode != 'expdev')
//     	if (($special >= 4 && $mode != 'expdev')
//     		|| ($mode == 'expdev' && $objMod->version != 'development' && $objMod->version != 'experimental')) continue;    // Discard if not for current tab
//
//         if (! $objMod->getName())
//         {
//         	dol_syslog("Error for module ".$key." - Property name of module looks empty", LOG_WARNING);
//       		continue;
//         }

        $const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));

        // Check filters
        $modulename=$objMod->getName();
        $moduletechnicalname=$objMod->name;
        $moduledesc=$objMod->getDesc();
        $moduledesclong=$objMod->getDescLong();
        $moduleauthor='';//$objMod->getPublisher();

        // We discard showing according to filters
        if ($search_keyword)
        {
            $qualified=0;
            if (preg_match('/'.preg_quote($search_keyword).'/i', $modulename)
                || preg_match('/'.preg_quote($search_keyword).'/i', $moduletechnicalname)
                || preg_match('/'.preg_quote($search_keyword).'/i', $moduledesc)
                || preg_match('/'.preg_quote($search_keyword).'/i', $moduledesclong)
                || preg_match('/'.preg_quote($search_keyword).'/i', $moduleauthor)
                ) $qualified=1;
            if (! $qualified) continue;
        }
        if ($search_status)
        {
            if ($search_status == 'active' && empty($conf->global->$const_name)) continue;
            if ($search_status == 'disabled' && ! empty($conf->global->$const_name)) continue;
        }
        if ($search_nature)
        {
            if (preg_match('/^external/',$search_nature) && $objMod->isCoreOrExternalModule() != 'external') continue;
            if (preg_match('/^external_(.*)$/',$search_nature, $reg))
            {
                //print $reg[1].'-'.dol_escape_htmltag($objMod->getPublisher());
                $publisher=dol_escape_htmltag($objMod->getPublisher());
                if ($reg[1] && dol_escape_htmltag($reg[1]) != $publisher) continue;
                if (! $reg[1] && ! empty($publisher)) continue;
            }
            if ($search_nature == 'core' && $objMod->isCoreOrExternalModule() == 'external') continue;
        }
        if ($search_version)
        {
            if (($objMod->version == 'development' || $objMod->version == 'experimental' || preg_match('/deprecated/', $objMod->version)) && $search_version == 'stable') continue;
            if ($objMod->version != 'development'  && ($search_version == 'development')) continue;
            if ($objMod->version != 'experimental' && ($search_version == 'experimental')) continue;
            if (! preg_match('/deprecated/', $objMod->version) && ($search_version == 'deprecated')) continue;
        }

        // Load all lang files of module
        if (isset($objMod->langfiles) && is_array($objMod->langfiles))
        {
		foreach($objMod->langfiles as $domain)
		{
			$langs->load($domain);
		}
        }

        // Print a separator if we change family
        if ($familykey!=$oldfamily)
        {
		if ($oldfamily) print '</table></div><br>';

            $familytext=empty($familyinfo[$familykey]['label'])?$familykey:$familyinfo[$familykey]['label'];
            print_fiche_titre($familytext, '', '');

            print '<div class="div-table-responsive">';
		print '<table class="tagtable liste" summary="list_of_modules">'."\n";

		$atleastoneforfamily=0;
        }

        $atleastoneforfamily++;

        if ($familykey!=$oldfamily)
        {
		$familytext=empty($familyinfo[$familykey]['label'])?$familykey:$familyinfo[$familykey]['label'];
		$oldfamily=$familykey;
        }




        // Version (with picto warning or not)
        $version=$objMod->getVersion(0);
        $versiontrans='';
        if (preg_match('/development/i', $version))  $versiontrans.=img_warning($langs->trans("Development"), 'style="float: left"');
        if (preg_match('/experimental/i', $version)) $versiontrans.=img_warning($langs->trans("Experimental"), 'style="float: left"');
        if (preg_match('/deprecated/i', $version))   $versiontrans.=img_warning($langs->trans("Deprecated"), 'style="float: left"');
        $versiontrans.=$objMod->getVersion(1);

        // Define imginfo
        $imginfo="info";
        if ($objMod->isCoreOrExternalModule() == 'external')
        {
            $imginfo="info_black";
        }

        print '<tr>'."\n";

        // Picto + Name of module
        print '  <td width="200px">';
        $alttext='';
        //if (is_array($objMod->need_dolibarr_version)) $alttext.=($alttext?' - ':'').'Dolibarr >= '.join('.',$objMod->need_dolibarr_version);
        //if (is_array($objMod->phpmin)) $alttext.=($alttext?' - ':'').'PHP >= '.join('.',$objMod->phpmin);
        if (! empty($objMod->picto))
        {
		if (preg_match('/^\//i',$objMod->picto)) print img_picto($alttext,$objMod->picto,' width="14px"',1);
		else print img_object($alttext, $objMod->picto, 'class="valignmiddle" width="14px"');
        }
        else
        {
		print img_object($alttext, 'generic', 'class="valignmiddle"');
        }
        print ' <span class="valignmiddle">'.$objMod->getName().'</span>';
        print "</td>\n";

        // Desc
        print '<td class="valignmiddle tdoverflowmax300">';
        print nl2br($objMod->getDesc());
        print "</td>\n";

        // Help
        print '<td class="center nowrap" style="width: 82px;">';
        //print $form->textwithpicto('', $text, 1, $imginfo, 'minheight20', 0, 2, 1);
        print '<a href="javascript:document_preview(\''.DOL_URL_ROOT.'/admin/modulehelp.php?id='.$objMod->numero.'\',\'text/html\',\''.dol_escape_js($langs->trans("Module")).'\')">'.img_picto($langs->trans("ClickToShowDescription"), $imginfo).'</a>';
        print '</td>';

        // Version
        print '<td class="center nowrap" width="120px">';
        print $versiontrans;
        print "</td>\n";

        // Activate/Disable and Setup (2 columns)
        if (! empty($conf->global->$const_name))	// If module is already activated
        {
		$disableSetup = 0;

		// Link enable/disabme
		print '<td class="center valignmiddle" width="60px">';
		if (! empty($arrayofwarnings[$modName]))
	        {
                print '<!-- This module has a warning to show when we activate it (note: your country is '.$mysoc->country_code.') -->'."\n";
	        }
	        if (! empty($objMod->disabled))
		{
			print $langs->trans("Disabled");
		}
		else if (! empty($objMod->always_enabled) || ((! empty($conf->multicompany->enabled) && $objMod->core_enabled) && ($user->entity || $conf->entity!=1)))
		{
			print $langs->trans("Required");
			if (! empty($conf->multicompany->enabled) && $user->entity) $disableSetup++;
		}
		else
		{
			print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?page=greffon&amp;id='.$objMod->numero.'&amp;module_position='.$module_position.'&amp;action=reset&amp;value=' . $modName . '&amp;mode=' . $mode . $param . '">';
			print img_picto($langs->trans("Activated"),'switch_on');
			print '</a>';
		}
		print '</td>'."\n";

//         	Link config
		if (! empty($objMod->config_page_url) && !$disableSetup)
		{
			if (is_array($objMod->config_page_url))
			{
				print '<td class="tdsetuppicto right" width="60px">';
				$i=0;
				foreach ($objMod->config_page_url as $page)
				{
					$urlpage=$page;
					if ($i++)
					{
						print '<a href="'.$urlpage.'" title="'.$langs->trans($page).'">'.img_picto(ucfirst($page),"setup").'</a>';
						//    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
					}
					else
					{
						if (preg_match('/^([^@]+)@([^@]+)$/i',$urlpage,$regs))
						{
							print '<a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a>';
						}
						else
						{
							print '<a href="'.$urlpage.'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a>';
						}
					}
				}
				print "</td>\n";
			}
			else if (preg_match('/^([^@]+)@([^@]+)$/i',$objMod->config_page_url,$regs))
			{
				print '<td class="tdsetuppicto right valignmiddle" width="60px"><a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a></td>';
			}
			else
			{
				print '<td class="tdsetuppicto right valignmiddle" width="60px"><a href="'.$objMod->config_page_url.'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a></td>';
			}
		}
		else
		{
			print '<td class="tdsetuppicto right valignmiddle" width="60px">'.img_picto($langs->trans("NothingToSetup"),"setup",'class="opacitytransp" style="padding-right: 6px"').'</td>';
		}

        }
        else	// Module not yet activated
		{
		    // Link enable/disable
		print '<td class="center valignmiddle" width="60px">';
		    if (! empty($objMod->always_enabled))
		{
			// Should never happened
		}
		else if (! empty($objMod->disabled))
		{
			print $langs->trans("Disabled");
		}
		else
		{
			// Module qualified for activation
		    $warningmessage='';
			if (! empty($arrayofwarnings[$modName]))
			{
                    print '<!-- This module has a warning to show when we activate it (note: your country is '.$mysoc->country_code.') -->'."\n";
			    foreach ($arrayofwarnings[$modName] as $keycountry => $cursorwarningmessage)
			    {
			        $warningmessage .= ($warningmessage?"\n":"").$langs->trans($cursorwarningmessage, $objMod->getName(), $mysoc->country_code);
			    }
			}
			if ($objMod->isCoreOrExternalModule() == 'external' && ! empty($arrayofwarningsext))
			{
			    print '<!-- This module is an external module and it may have a warning to show (note: your country is '.$mysoc->country_code.') -->'."\n";
			    foreach ($arrayofwarningsext as $keymodule => $arrayofwarningsextbycountry)
			    {
                        $keymodulelowercase=strtolower(preg_replace('/^mod/','',$keymodule));
                        if (in_array($keymodulelowercase, $conf->modules))    // If module that request warning is on
			        {
				    foreach ($arrayofwarningsextbycountry as $keycountry => $cursorwarningmessage)
				    {
				        if ($keycountry == 'always' || $keycountry == $mysoc->country_code)
				        {
				            $warningmessage .= ($warningmessage?"\n":"").$langs->trans($cursorwarningmessage, $objMod->getName(), $mysoc->country_code, $modules[$keymodule]->getName());
				            $warningmessage .= ($warningmessage?"\n":"").($warningmessage?"\n":"").$langs->trans("Module").' : '.$objMod->getName();
				            if (! empty($objMod->editor_name)) $warningmessage .= ($warningmessage?"\n":"").$langs->trans("Publisher").' : '.$objMod->editor_name;
				            if (! empty($objMod->editor_name)) $warningmessage .= ($warningmessage?"\n":"").$langs->trans("ModuleTriggeringThisWarning").' : '.$modules[$keymodule]->getName();
				        }
				    }
			        }
			    }
			}
		    print '<!-- Message to show: '.$warningmessage.' -->'."\n";
			print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?page=greffon&amp;id='.$objMod->numero.'&amp;module_position='.$module_position.'&amp;action=set&amp;value=' . $modName . '&amp;mode=' . $mode . $param . '"';
			if ($warningmessage) print ' onclick="return confirm(\''.dol_escape_js($warningmessage).'\');"';
			print '>';
			print img_picto($langs->trans("Disabled"),'switch_off');
			print "</a>\n";
		}
		print "</td>\n";

		// Link config
		print '<td class="tdsetuppicto right valignmiddle" width="60px">'.img_picto($langs->trans("NothingToSetup"),"setup",'class="opacitytransp" style="padding-right: 6px"').'</td>';
        }

        print "</tr>\n";
    }

    if ($oldfamily)
    {
        print "</table>\n";
        print '</div>';
    }

    dol_fiche_end();

    // Show warning about external users
    print info_admin(showModulesExludedForExternal($modules))."\n";

    print '</form>';


    }

}
