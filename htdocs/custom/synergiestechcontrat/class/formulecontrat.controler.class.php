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

namespace CORE;

// namespace CORE\FRAMEWORK;
require( dol_buildpath('/framework/vendor/Mustache/Autoloader.php', 0) );




namespace CORE\QUALITYREPORT;

use \Mustache_Autoloader as Mustache_Autoloader;
use \Mustache_Engine as Mustache_Engine;
use \Mustache_Loader_FilesystemLoader as Mustache_Loader_FilesystemLoader;
use \Mustache_Exception_UnknownTemplateException as Mustache_Exception_UnknownTemplateException;
use \Mustache_Loader_CascadingLoader as Mustache_Loader_CascadingLoader;
use \Mustache_Loader_StringLoader as Mustache_Loader_StringLoader;
use \Mustache_Logger_StreamLogger as Mustache_Logger_StreamLogger;

Class Qualityreportcontroler{

	/**
		@var string name of type object
	*/
	public $type = '';

	/**
		@var string name of context (card, note..)
	*/
	public $context = '';
	/**
		@var string name of context (card, note..)
	*/
	public $action = '';
	/**
		@var object Mustache
	*/
	public $Mustache = '';


	public function __construct($db){
	}

	/**
		@param $context string , name of context, no space use alpa&&num
		@param êaction string , no space use alpa&&num
	*/
	public function PreProcess($type, $context, $action){

		var_dump($context);
		$this->type = $type;
		$this->context = $context;
		$this->action = $action;


		Mustache_Autoloader::register();

		$this->Mustache = new Mustache_Engine(array(
				'template_class_prefix' => '__Mustache_',
// 				'cache' => DOL_DATA_ROOT.'/framework/mustache/tmp',
// 				'cache_file_mode' => 0666, // Please, configure your umask instead of doing this :)
// 				'cache_lambda_templates' => false,
				//load or file.html in tpl or direct <html>
				'loader' => new Mustache_Loader_CascadingLoader(array(
					new Mustache_Loader_FilesystemLoader( dol_buildpath('/framework/views/', 0), array('extension' => '.html')),
					new Mustache_Loader_FilesystemLoader( dol_buildpath('/'.$this->type.'/views/', 0), array('extension' => '.html')),
	// 				new Mustache_Loader_FilesystemLoader(ROOT_DIR.'templates/default/js', array('extension' => '.js')),
					new Mustache_Loader_StringLoader(),
					)),
				'partials_loader' => new Mustache_Loader_CascadingLoader(array(
					new Mustache_Loader_FilesystemLoader(dol_buildpath('/framework/views/partials', 0) , array('extension' => '.html')),
					new Mustache_Loader_FilesystemLoader(dol_buildpath('/'.$this->type.'/views/partials', 0) , array('extension' => '.html')),
					)),
				'helpers' => array(
					'__' => function($text) {
						global $langs;
						return $langs->trans($text);
					}/*,
					'Personnel' => function($text) {
						$tmp = $_SESSION['Personnel'];
						if (isset($tmp->$text)) {
							return $tmp->$text;
						} else {
							return '';
						}
						}*/
					),
				'escape' => function($value) {
					return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
				},
	// 			'charset' => CHARSET,
				'logger' => new Mustache_Logger_StreamLogger('php://stderr'),
				'strict_callables' => true,
				'pragmas' => [Mustache_Engine::PRAGMA_FILTERS],
				)
			);
	}


	public function Process(){
	}

	public function Draw($LocalDisplays){

// 		$LocalDisplays = new Qualityreportmodele($this->action, $userWrite);

		$loader = new Mustache_Loader_FilesystemLoader( dol_buildpath('/'.$this->type.'/views/', 0), array('extension' => '.html') ) ;

		if(in_array($action, array('clone', 'delete', 'reopen', 'validate', 'close')) )
			$tpl = '{{{foo}}}';
		else
			$tpl = $loader->load('neutral');




		return $this->Mustache->render(
			$tpl
			, $LocalDisplays
			);

	}


}
