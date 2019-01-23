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
dol_include_once('/framework/class/api.client.inc.php');
include_once ODTPHP_PATHTOPCLZIP.'/pclzip.lib.php';
//dol_include_once('/framework/class/PclZip/pclzip.lib.php');

class updateOscssshop
{
    var $installDir;
    var $module;
    var $server  = 'http://dolistore.oscss-shop.fr/api/';
    var $api_key = '5C6494B713AC6588BB98475528B4C';
    var $checked = false;
    var $version = '0.0.0';
    var $downloadId;

    /**
    */
    function __construct($module = FALSE)
    {
    global $langs;

        $langs->load('framework@framework');

        if ($module !== FALSE) {
            $this->setModule($module);
        }
    }


		/**
		*/
    function download($module = FALSE)
    {
        if ($module !== FALSE) {
            $this->setModule($module);
        }
        if (!$this->checked) {
            $this->getVersion();
        }
        if ($this->version == '0.0.0') { // no version found
            return false;
        }

        /*echo*/ $this->installDir = dol_buildpath("/$this->module/", 0);
     /*echo*/    $zipFile          = "{$this->installDir}{$this->module}-{$this->version}.zip";
        $output           = false;
        if (class_exists('apiClient')) {
            $api  = new apiClient();
            $url  = "{$this->server}packages/{$this->downloadId}/?api_key={$this->api_key}";
            $api->send($url);


            if (file_exists($zipFile)) unlink($zipFile); // cleanup
            $size = file_put_contents($zipFile, $api->curl_response);
            if ($size > 0) {
                $zip  = new PclZip($zipFile);
                if (($list = $zip->listContent()) == 0) {
                    return FALSE;
                }
                echo '<pre>';
                $zipPath = 'htdocs/custom';
                foreach ($list as $file) { // find zip structure
                    $matches     = null;
                    $returnValue = preg_match('/^(.*?)\\/'.$this->module.'*/i', $file['filename'], $matches);
                    if ($returnValue) {
                        $zipPath = $matches[1];
                        $output  = true;
                        break;
                    }
                }
                if ($output) {
//                 var_dump(__file__);

                    /*var_dump(*/$zip->extract(PCLZIP_OPT_PATH, $this->installDir, PCLZIP_OPT_REMOVE_PATH, $zipPath.'/'.$this->module) /*)*/;

                    if ($zip->errorCode == 0) return true;
                }
            }
        }
//         exit;

        return $output;
    }

    /**
			@fn getVersion($module = FALSE)
			@brief
				check level module in dolibarr cf MAIN_FEATURES_LEVEL

			@param
			@return version string xx.xx.xx
    */
    function getVersion($module = FALSE){
			global $conf;

			if ($module !== FALSE) {
					$this->setModule($module);
			}
			if ($this->checked) return $this->version;
			if (class_exists('apiClient')) {
					$api = new apiClient();
					$url = "{$this->server}versions/?filter[original_name]={$this->module}-&order_by=original_name&order=desc&api_key={$this->api_key}";
					$api->send($url);
					if ($api->decoded->total > 0) { // find something and return most recent version

						foreach($api->decoded->data as $k=>$r){
							if((int)$r->version >0 || $r->version =='trunk') {
								if( empty($conf->global->MAIN_FEATURES_LEVEL) || $conf->global->MAIN_FEATURES_LEVEL <= 0 ){
										preg_match_all('#([0-9]{0,})*#',$r->version, $match);

									//  search unsatble beta  xx.yy.zz for yy is pair
									if(!($match[0][2]%2 == 1)) {
										$this->checked    = true;
										$this->downloadId = $r->id_download;
										$this->version    = $r->version;

										break;
									}
								}
								elseif( $conf->global->MAIN_FEATURES_LEVEL >= 1 ){
									preg_match_all('#([0-9]{0,})*#',$r->version, $match);

									//  search unsatble beta  xx.yy.zz for yy is unpair
									if($match[0][2]%2 == 1) {
										$this->checked    = true;
										$this->downloadId = $r->id_download;
										$this->version    = $r->version;

										break;
									}

								}
	// 							elseif( $conf->global->MAIN_FEATURES_LEVEL == 2 ){
	// 								preg_match_all('#([0-9]{0,})[.]#',$r->version, $match);
	//
	// 								if($r->version == 'trunk'){
	// 									$this->checked    = true;
	// 									$this->downloadId = $r->id_download;
	// 									$this->version    = $r->version;
	//
	// 									break;
	// 								}
	// 							}
							}
						}


						if( // if last reveision is not beta, force downlaod new stable
								!empty($conf->global->MAIN_FEATURES_LEVEL)
								&& $conf->global->MAIN_FEATURES_LEVEL >= 1
								&& $this->version =='0.0.0'
							)
						{
							$this->checked    = true;
							$this->downloadId = $api->decoded->data[0]->id_download;
							$this->version    = $api->decoded->data[0]->version;
						}

						return $this->version;
					}
			}

			return '0.0.0';
    }

    /**
			@fn setModule($module)
			@brief Fix name of module
    */
    function setModule($module)
    {
        return $this->module = $module;
    }

    /**
			@fn setServer($server)
			@brief Fix url of server
    */
    function setServer($server)
    {
        return $this->server = $server;
    }

		/**
			@fn setKey($key)
			@brief Fix url of server
    */
    function setKey($key)
    {
        return $this->api_key = substr($key,2);
    }

    /**
    */
    function insertUpdateLink(&$module, $langs)
    {
        global $langs;

        $langs->load('framework@framework');

        $distVer = $this->getVersion();



        if (version_compare($distVer, $module->getVersion(), '>')) {

            $module->description = '<a class="button" href="'.dol_buildPath('/framework/update.php?module='.strtolower($module->name),
                    2).'">'.$langs->trans("UpdateTo", "$distVer").' - '.$distVer .'</a> '.$module->description;
        }
    }
}