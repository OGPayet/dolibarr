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


/**
* 	\file       framework/core/lib/greffon.lib.php
* 	\brief      Ensemble de fonctions de base pour les greffons
* 	\ingroup    framework
*/



/**
	Creation alert sur les greffon sous forme de modal avec liens pour recherche, chargement activation
	@param
	@param
	@return string for print
*/
function FormAlertGreffon($greffon, $module){
	global $conf, $form, $langs, $user;
	$greffon = strtolower($greffon);
	if(!empty($conf->$greffon) && empty($conf->$greffon->enable) ) {
		$formquestion = array(
					'text' =>  $langs->trans('GreffonActivateProcess',$greffon, $module)
		);
		$formconfirm = $form->formconfirm(dol_buildpath('/'.$module.'/admin/index.php', 1).'?page=greffon&greffon='.$greffon.'&module=dolmessage', $langs->trans('GreffonNoFound'), $text, 'alertvalidate', $formquestion, 1, 1, 240);
	}
	elseif(empty($conf->$greffon)/* && empty($conf->$greffon->enable*/) {
		$formquestion = array(
					'text' =>  $langs->trans('GreffonAddInModule',$greffon, $module)
		);
		$formconfirm = $form->formconfirm(dol_buildpath('/'.$module.'/admin/index.php', 1).'?page=greffon&greffon='.$greffon.'&module=dolmessage', $langs->trans('GreffonNoFound'), $text, 'alertvalidate', $formquestion, 1, 1, 240);
	}

	return $formconfirm;
}

/**
	@param string $params complet url query string passed by &xx=yy...
	@param string $path url for page or null
*/
function GetLinkExtendsGreffon( $params='', $path=''){
	$querystring='action=alertgreffon';

	$s ='';
	if(!empty($path))
		$s.=dol_buildpath($path, 1);

	$s.='?'.$querystring;

	if(!empty($params)){
		if(substr($params, 0,1) !='&')
			$s.='&';

		$s.=$params;
	}

	return $s;
}

/**
	@brief check state module for test if ok and enable
	@param string $module  name of module
	@return true activate false no activate
*/
function CheckStateMod($module){
	global $conf;
	$module = strtolower($module);
	if(empty($conf->$module))
		return false;
	if(empty($conf->$module->enabled))
		return false;

	return true;
}
