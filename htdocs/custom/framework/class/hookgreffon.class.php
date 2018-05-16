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


require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';





class HookGreffon
	Extends HookManager{

	public $greffon = array();

	function __construct($db){
		// load speicifc greffon this in hooks
		$this->db = $db;

// 		global $conf;
// 		foreach($conf->modules_parts['greffon']as $k=>$v)
// 			foreach($v as $k1=<$v1) {
// 				if($v1 == 'dolmessage'){
//
// 				}
// 			}

	}

	function initGreffon($arraycontext)
	{
		global $conf;

		// Test if there is hooks to manage
        if (! is_array($conf->modules_parts['greffon']) || empty($conf->modules_parts['greffon'])) return;

        // For backward compatibility
		if (! is_array($arraycontext)) $arraycontext=array($arraycontext);

		$this->contextarray=array_unique(array_merge($arraycontext,(array)$this->contextarray));    // All contexts are concatenated

		foreach($conf->modules_parts['greffon'] as $module => $greffon)
		{
			if ($conf->$module->enabled)
			{

				if (is_array($greffon)) $arrayhooks=$greffon;    // New system
				else $arrayhooks=explode(':',$greffon);        // Old system (for backward compatibility)


				foreach($arraycontext as $context)
				{


					if (in_array($context,$arrayhooks) || in_array('all',$arrayhooks))    // We instantiate action class only if hook is required
					{

						$path 		= '/'.$greffon[0].'/class/';
						$actionfile = 'actions_'.$module.'.class.php';
						$pathroot	= '';

						// Include actions class overwriting hooks
						dol_syslog('Loading hook:' . $actionfile, LOG_INFO);
						$resaction=dol_include_once($path.$actionfile);
						if ($resaction)
						{
						$controlclassname = 'Actions'.ucfirst($module);
						$actionInstance = new $controlclassname($this->db);
						$this->greffon[$context][$module] = $actionInstance;
						}
					}
				}
			}
		}

		return 1;
	}




}