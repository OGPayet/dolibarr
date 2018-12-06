<?php
/* Copyright (C) 2014-2017	Charlene BENKE	<charlie@patas-monkey.com>
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

/**
 *		\file	   htdocs/coefpricr/core/lib/coefpricr.lib.php
 *		\brief	  Ensemble de fonctions de base pour customline
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object		 Member
 *  @return array		   		head
 */

function getChangeLog($appliname, $urlmonkey = 'https://www.patas-monkey.com')
{
	global $langs;

	$ret= '<table width="100%" cellspacing="5" bgcolor="#E0E0E0">';
	$ret.='<tbody>';
	$ret.='<tr>';
	$ret.='<td rowspan="3" align="center"><a href="http://www.patas-monkey.com">';
	$ret.='<img src="'.$urlmonkey.'/images/patas-monkey_logo.png" alt="" /></a>';
	$ret.='<br/>';
	$ret.='<b>'.$langs->trans("Slogan").'</b>';
	$ret.='</td>';
	$inputstyle ="cursor:pointer; font-family: Happy Monkey; background-color: #ff6600; font-variant: small-caps;";
	$inputstyle.="font-size: 14px; font-weight: bold; height: 30px; width: 150px;";
	$ret.='<td align="center" ><a href="'.$urlmonkey.'/index.php/fr/modules-dolibarr" target="_blank">';
	$ret.=' <input style="'.$inputstyle.'" name="readmore" type="button" value="'.$langs->trans("LienModules").'" /></a>';
	$ret.='</td>';
	$ret.='<td rowspan="3" align="center">';
	$ret.='<b>'.$langs->trans("LienDolistore").'</b><br/>';
	$ret.='<a href="'.str_replace("www", "docs", $urlmonkey).'/dolistore" target="_blank">';
	$ret.='<img border="0" width="180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a>';
	$ret.='</td>';
	$ret.='</tr>';
	$ret.='<tr align="center">';
	$ret.='<td width="20%"><a href="'.$urlmonkey.'/index.php/fr/services" target="_blank">';
	$ret.='<input style="'.$inputstyle.'" name="readmore" type="button" value="'.$langs->trans("LienServices").'" /></a>';
	$ret.='</td>';
	$ret.='</tr>';
	$ret.='<tr align="center">';
	$ret.='<td align="center" ><a href="'.str_replace("www", "docs", $urlmonkey).'/documentation" target="_blank">';
	$ret.='<input style="'.$inputstyle.'" name="readmore" type="button" value="'.$langs->trans("LienDoc").'" /></a>';
	$ret.='</td>';
	$ret.='</tr>';
	$ret.='</tbody>';
	$ret.='</table>';
	$ret.='<br><br>';

	print_titre($langs->trans("Changelog"));
	$ret.='<br>';
	$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
	$changelog = @file_get_contents(
					str_replace("www", "dlbdemo", $urlmonkey).'/htdocs/custom/'.$appliname.'/changelog.xml',
					false, $context
	);
	// not connected
	if ($changelog === false)
		$tblversionslast=array();
	else {
		$sxelast = simplexml_load_string(nl2br($changelog));
		if ($sxelast === false)
			$tblversionslast=array();
		else
			$tblversionslast=$sxelast->Version;
	}
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string(nl2br(file_get_contents(dol_buildpath("/".$appliname, 0).'/changelog.xml')));
		if ($sxe === false) {
			$ret.="Erreur lors du chargement du XML\n";
			foreach (libxml_get_errors() as $error)
				$ret.=$error->message;
			exit;
		} else
			$tblversions=$sxe->Version;

		$ret.='<table class="noborder" >';
		$ret.='<tr class="liste_titre">';
		$ret.='<th align=center width=100px>'.$langs->trans("NumberVersion").'</th>';
		$ret.='<th align=center width=100px>'.$langs->trans("MonthVersion").'</th>';
		$ret.='<th align=left >'.$langs->trans("ChangesVersion").'</th></tr>' ;
		$var = true;


		if (count($tblversionslast) > count($tblversions)) {
			// il y a du nouveau
			for ($i = count($tblversionslast)-1; $i >=0; $i--) {
				$var = ! $var;
				$color="";
				if (empty($sxe->xpath('//Version[@Number="'.$tblversionslast[$i]->attributes()->Number.'"]')))
					$color=" bgcolor=#FF6600 ";
				$ret.="<tr $bc[$var]>";
				$ret.='<td align=center '.$color.' valign=top>'.$tblversionslast[$i]->attributes()->Number.'</td>';
				$ret.='<td align=center '.$color.' valign=top>'.$tblversionslast[$i]->attributes()->MonthVersion.'</td>' ;
				$lineversion=$tblversionslast[$i]->change;
				$ret.='<td align=left '.$color.' valign=top>';
				//var_dump($lineversion);
				foreach ($lineversion as $changeline)
					$ret.= $changeline->attributes()->type.'&nbsp;-&nbsp;'.$changeline.'<br>';
				print '</td></tr>';
			}
		} elseif (count($tblversionslast) < count($tblversions) && count($tblversionslast) > 0) {
			// version exp�rimentale
			for ($i = count($tblversions)-1; $i >=0; $i--) {
				$var = ! $var;
				$color="";
				if (empty($sxelast->xpath('//Version[@Number="'.$tblversions[$i]->attributes()->Number.'"]')))
					$color=" bgcolor=lightgreen ";
				$ret.="<tr $bc[$var]>";
				$ret.='<td align=center '.$color.' valign=top>'.$tblversions[$i]->attributes()->Number.'</td>';
				$ret.='<td align=center '.$color.' valign=top>'.$tblversions[$i]->attributes()->MonthVersion.'</td>' ;
				$ret.='<td align=left '.$color.' valign=top>';
				$lineversion=$tblversions[$i]->change;
				//var_dump($lineversion);
				foreach ($lineversion as $changeline)
					$ret.=$changeline->attributes()->type.'&nbsp;-&nbsp;'.$changeline.'<br>';
				$ret.='</td></tr>';
			}
		} else {
			//on est � jour des versions ou pas de connection internet
			for ($i = count($tblversions)-1; $i >=0; $i--) {
				$var = ! $var;
				$ret.="<tr $bc[$var]>";
				$ret.='<td align=center valign=top>'.$tblversions[$i]->attributes()->Number.'</td>';
				$ret.='<td align=center valign=top>'.$tblversions[$i]->attributes()->MonthVersion.'</td>' ;
				$lineversion=$tblversions[$i]->change;
				$ret.='<td align=left valign=top>';
				//var_dump($lineversion);
				foreach ($lineversion as $changeline)
					$ret.=$changeline->attributes()->type.'&nbsp;-&nbsp;'.$changeline.'<br>';

				$ret.='</td></tr>';
			}
		}
		$ret.='</table><br>';
		return $ret;
}