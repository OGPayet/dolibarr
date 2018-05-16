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



Class FormDolModExts {

	function construct($db){
		$this->db = $db;
	}


	/**
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
	*/
	public function SelectUser($cstarray, DolModExts $object){
		global $form, $conf, $langs, $db;

		if(!is_object($form))
			$form = new Form($db);

		$namecst = $cstarray[0];

		ob_start();

		$form->select_users( $conf->global->{$namecst} ,$namecst,1);
		$out1 = ob_get_contents();
		ob_end_clean();

		$output ='';
		$output .="<tr>";

			$output .='<td >'.$langs->trans($cstarray[3]).'</td>';
			$output .='<td align="right">';
			$output .= $out1;
			$output .='</td><td align="right">';
			$output .='<input type="submit" class="button" name="modify'.$namecst.'" value="'.$langs->trans("Modify").'">';
			$output .="</td>";

		$output .='</tr>';

		return $output;
	}

	/**
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
	*/
	public function selectWarehouses($cstarray, DolModExts $object){
	// generic
		global $conf, $langs, $db;

		$langs->load("stocks");
		require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';


		if(!is_object($formproduct))
			$formproduct = new FormProduct($db);

		$namecst = $cstarray[0];


		$output ='';
		$output .="<tr>";

			$output .='<td >'.$langs->trans($cstarray[3]).'</td>';
			$output .='<td align="right">';
			$output .= $formproduct->selectWarehouses($namecst,$cstarray[9]['arrayselect'],$conf->global->{$namecst});
			$output .='</td><td align="right">';
			$output .='<input type="submit" class="button" name="modify'.$namecst.'" value="'.$langs->trans("Modify").'">';
			$output .="</td>";

		$output .='</tr>';

		return $output;
	}


	/**
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
	*/
	public function InputText($cstarray, DolModExts $object){
	// generic
		global $conf, $langs, $db;
		//
		global $form;

		if(!is_object($form))
			$form = new Form($db);

		$namecst = $cstarray[0];


		$output ='';
		$output .="<tr>";

			$output .='<td >'.$langs->trans($cstarray[3]).'</td>';
			$output .='<td align="right">';
			$output .= '<input type="text" name="'.$namecst.'" value="'.$conf->global->{$namecst}.'" />';
			$output .='</td><td align="right">';
			$output .='<input type="submit" class="button" name="modify'.$namecst.'" value="'.$langs->trans("Modify").'">';
			$output .="</td>";

		$output .='</tr>';

		return $output;
	}


	/**
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
	*/
	public function SelectSearchMode($cstarray, DolModExts $object){
	// generic
		global $conf, $langs, $db;
		//
		global $form;

		if(!is_object($form))
			$form = new Form($db);

		$namecst = $cstarray[0];


		$output ='';
		$output .="<tr>";
		if (! $conf->use_javascript_ajax)
		{
			$output .= '<td class="nowrap" align="right" colspan="3">';
			$output .= $langs->trans("NotAvailableWhenAjaxDisabled");
			$output .= "</td>";
		}
		else
		{
			$output .='<td >'.$langs->trans($cstarray[3]).'</td>';
			$output .='<td align="right">';
			$output .= $form->selectarray($namecst,$cstarray[9]['arrayselect'],$conf->global->{$namecst});
			$output .='</td><td align="right">';
			$output .='<input type="submit" class="button" name="modify'.$namecst.'" value="'.$langs->trans("Modify").'">';
			$output .="</td>";
		}
		$output .='</tr>';

		return $output;
	}


	/**
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
	*/
	public function ConstantOnOff($cstarray, DolModExts $object){
// generic
		global $conf, $langs, $db;
		//
		global $form;

		if(!is_object($form))
			$form = new Form($db);

		$namecst = $cstarray[0];

		$output ='';
		$output .="<tr>";
		$output .='<td >'.$langs->trans($cstarray[3]).'</td>';
		$output .='<td align="right">';
		$output .=ajax_constantonoff($namecst);
		$output .="</td>";
		$output .='</tr>';

		return $output;
	}


	/**
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
	*/
	public function SelectYesNo($cstarray, DolModExts $object){
// generic
		global $conf, $langs, $db;
		//
		global $form;

		if(!is_object($form))
			$form = new Form($db);

		$namecst = $cstarray[0];

// 		print_r($cstarray);
		$output ='';
		$output .="<tr>";
		$output .='<td width="80%">'.$langs->trans($cstarray[3]).'</td>';
		$output .='<td width="60" align="right">';
		$arrval=array('0'=>$langs->trans("No"),
			'1'=>$langs->trans("Yes"),
		);
		$output .=$form->selectyesno($namecst, $conf->global->{$namecst}, 1);

		$output .='</td><td align="right">';
		$output .='<input type="submit" class="button" name="modify'.$namecst.'" value="'.$langs->trans("Modify").'">';
		$output .="</td>";
		$output .='</tr>';

		return $output;
	}


	/**
		@brief use for automatic display block config of section numering module for object
		module name: Sample
		directory : sample
		descripor : modSample
		Use Const config in db  : SAMPLE_ADDON
		search model in /sample/core/modules/sample/
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
		@param string  $type [option] type of sub module
		@param string  $name [option] name of sub module
		@param string  $keyconf [option] key var config used for stock value of config
	*/
	public function DisplayBlockNumberingRule($cstarray, DolModExts $object , $type='', $name='', $keyconf=''){
		// generic
		global $conf, $langs, $db;

		//
		global $form;



		if(!is_object($form))
			$form = new Form($db);

		ob_start();
		$output='';

		if( empty($type ) )
			$type = $object->code;
		if( empty($name ) )
			$name = $object->name;
		if( empty($keyconf ) )
			$keyconf =strtoupper($type).'_ADDON';


		dol_include_once('/'.$type.'/class/'.$type.'.class.php');

		$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);


		$output .=load_fiche_titre($langs->trans($type."NumberingModules"), '', '');

		$output .='<table class="noborder" width="100%">';
		$output .='<tr class="liste_titre">';
		$output .='<td width="100">'.$langs->trans("Name").'</td>';
		$output .='<td>'.$langs->trans("Description").'</td>';
		$output .='<td>'.$langs->trans("Example").'</td>';
		$output .='<td align="center" width="60">'.$langs->trans("Activated").'</td>';
		$output .='<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
		$output .="</tr>\n";

		clearstatcache();

		foreach ($dirmodels as $reldir)
		{
			$dir = dol_buildpath($reldir."core/modules/".$type."/");

			if (is_dir($dir))
			{
				$handle = opendir($dir);
				if (is_resource($handle))
				{
					$var=true;

					while (($file = readdir($handle))!==false)
					{
						if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
						{
							$file = $reg[1];
							$classname = substr($file,4);

							require_once $dir.$file.'.php';

							$module = new $file;

							// Show modules according to features level
							if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

							if ($module->isEnabled())
							{
								$var=!$var;
								$output .='<tr '.$bc[$var].'><td>'.$module->name."</td><td>\n";
								$output .=$module->info();
								$output .='</td>';

								// Show example of numbering model
								$output .='<td class="nowrap">';
								$tmp=$module->getExample();
								if (preg_match('/^Error/',$tmp)) $output .='<div class="error">'.$langs->trans($tmp).'</div>';
								elseif ($tmp=='NotConfigured') $output .=$langs->trans($tmp);
								else $output .=$tmp;
								$output .='</td>'."\n";

								$output .='<td align="center">';
								if ($conf->global->{$keyconf} == 'mod_'.$classname)
								{
									$output .=img_picto($langs->trans("Activated"),'switch_on');
								}
								else
								{
									$output .='<a href="'.$_SERVER["PHP_SELF"].'?page='.GETPOST('page').'&action=setmod&amp;scandir='.$keyconf.'&amp;value=mod_'.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
								}
								$output .='</td>';

								$object=new $name($db);
								$object->initAsSpecimen();

								// Info
								$htmltooltip='';
								$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
								$nextval=$module->getNextValue($mysoc,$object);
								if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
								{
									$htmltooltip.=''.$langs->trans("NextValue").': ';
									if ($nextval)
									{
										$htmltooltip.=$nextval.'<br>';
									}
									else
									{
										$htmltooltip.=$langs->trans($module->error).'<br>';
									}
								}

								$output .='<td align="center">';
								$output .=$form->textwithpicto('',$htmltooltip,1,0);
								$output .='</td>';

								$output .='</tr>';
							}
						}
					}
					closedir($handle);
				}
			}
		}

		$output .='</table><br>';

		$output .= ob_get_contents();
		ob_end_flush();
// 			var_dump($output);
		return $output;
	}
	/**
		@brief use for automatic display block config of section modele fo object document (pdf, odt)
		module name: Sample
		directory : sample
		descripor : modSample
		Use Const config in db  : SAMPLE_ADDON_PDF
		search model in /sample/core/modules/sample/doc
		@param array all param current line const_name def
		@param object $object result of class parent of descriptor
		@param string  $type [option] type of sub module
		@param string  $name [option] name of sub module
		@param string  $keyconf [option] key var config used for stock value of config
	*/
	function DisplayBlockModel($cstarray, DolModExts $object , $type='', $name='', $keyconf=''){

			// generic
			global $conf, $langs, $db;

			//
			global $form;

			if(!is_object($form))
				$form = new Form($db);

			ob_start();
			$output='';


			if( empty($type ) )
				$type = $object->code;
			if( empty($name ) )
				$name = $object->name;
			if( empty($keyconf ) )
				$keyconf =strtoupper($type).'_ADDON_PDF';

			$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
			/*
			* Document templates generators
			*/

			$output.= load_fiche_titre($langs->trans($type."ModelModule"), '', '');

			// Defini tableau def de modele
			$def = array();

			$sql = "SELECT nom";
			$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
			$sql.= " WHERE type = '".$type."'";
			$sql.= " AND entity = ".$conf->entity;
// echo $sql;
			$resql=$db->query($sql);

			if ($resql)
			{
				$i = 0;
				$num_rows=$db->num_rows($resql);
				while ($i < $num_rows)
				{
					$array = $db->fetch_array($resql);
					array_push($def, $array[0]);
					$i++;
				}
			}
			else
			{
				dol_print_error($db);
			}
			$output.= "<table class=\"noborder\" width=\"100%\">\n";
			$output.= "<tr class=\"liste_titre\">\n";
			$output.= '  <td width="100">'.$langs->trans("Name")."</td>\n";
			$output.= "  <td>".$langs->trans("Description")."</td>\n";
			$output.= '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
			$output.= '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
			$output.= '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
			$output.= '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
			$output.= "</tr>\n";

// 			clearstatcache();

			$var=true;
			foreach ($dirmodels as $reldir)
			{
				foreach (array('','/doc') as $valdir)
				{
					$dir = dol_buildpath($reldir."core/modules/".$type."/".$valdir);

					if (is_dir($dir))
					{
						$handle=opendir($dir);
						if (is_resource($handle))
						{
							while (($file = readdir($handle))!==false)
							{
								$filelist[]=$file;
							}
							closedir($handle);
							arsort($filelist);

							foreach($filelist as $file)
							{
								if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
								{
									if (file_exists($dir.'/'.$file))
									{
										$name = substr($file, 4, dol_strlen($file) -16);
										$classname = substr($file, 0, dol_strlen($file) -12);

										require_once $dir.'/'.$file;
										$module = new $classname($db);

										$modulequalified=1;
										if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
										if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

										if ($modulequalified)
										{
											$var=!$var;
											$output.= '<tr '.$bc[$var].'><td width="100">';
											$output.= (empty($module->name)?$name:$module->name);
											$output.= "</td><td>\n";

											if (method_exists($module,'info'))
												/// @remarks Specifical for framework engine, the second param used for dynamical section config
												$output.= $module->info($langs, $object->code);
											else
												$output.= $module->description;
											$output.= "</td>\n";

											// Active
											if (in_array($name, $def))
											{
												$output.= "<td align=\"center\">\n";
												$output.= '<a href="'.$_SERVER["PHP_SELF"].'?page='.GETPOST('page').'&action=del&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
												$output.= img_picto($langs->trans("Enabled"),'switch_on');
												$output.= '</a>';
												$output.= "</td>";
											}
											else
											{
												$output.= "<td align=\"center\">\n";
												$output.= '<a href="'.$_SERVER["PHP_SELF"].'?page='.GETPOST('page').'&action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
												$output.= "</td>";
											}

											// Default
											$output.= "<td align=\"center\">";
											if ($conf->global->{$keyconf} == "$name")
											{
												$output.= img_picto($langs->trans("Default"),'on');
											}
											else
											{
												$output.= '<a href="'.$_SERVER["PHP_SELF"].'?page='.GETPOST('page').'&action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
											}
											$output.= '</td>';

											// Info
											$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
											$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
											if ($module->type == 'pdf')
											{
												$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
											}
											$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
											$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);

											$output.= '<td align="center">';
											$output.= $form->textwithpicto('',$htmltooltip,1,0);
											$output.= '</td>';

											// Preview
											$output.= '<td align="center">';
											if ($module->type == 'pdf')
											{
												$output.= '<a href="'.$_SERVER["PHP_SELF"].'?page='.GETPOST('page').'&action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
											}
											else
											{
												$output.= img_object($langs->trans("PreviewNotAvailable"),'generic');
											}
											$output.= '</td>';

											$output.= "</tr>\n";
										}
									}
								}
							}
						}
					}
				}
			}

			$output.= '</table><br/>';


			$output .= ob_get_contents();
			ob_end_flush();
// 			var_dump($output);
			return $output;
	}



}
