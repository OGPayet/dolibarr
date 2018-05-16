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



require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

Class subExtrafields {
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
        global $langs, $conf, $html, $mysoc, $result, $submodisprev, $master, $subarray, $user;


				$action=GETPOST('action', 'alpha');
				$code=GETPOST('code', 'alpha');

				$elementtype=PageConfigSubModule::$descriptor->code; //Must be the $table_element of the class that manage extrafield
				$code=$elementtype=((!empty($code)&&$elementtype!=$code) ?$code : $elementtype); //Must be the $table_element of the class that manage extrafield


				//hack for forms actions
				$_SERVER["PHP_SELF"] .= '?page=extrafields'.((!empty($code)&&$elementtype!=$code) ? '&code='.$code : ''  );

        $form = self::$form = new Form($this->Master->db);
				$db = $this->Master->db;

				$extrafields = self::$extrafields = new ExtraFields($db);

				// List of supported format
				$tmptype2label = self::$tmptype2label=ExtraFields::$type2label;
// 				$this->type2label=array('');
				foreach (self::$tmptype2label as $key => $val)
					self::$type2label[$key]=$langs->trans($val);

				if (!$user->admin) accessforbidden();


				/*
				* Actions
				*/

				require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';



// 				header('Location : '.$this->Master->module.'/admin/index.php?page=extrafields');
// 				exit;
    }

    /**
      @brief constructor
     */
    public function DisplayPage() {
        global $langs, $conf, $html, $result, $currentmod, $submodisprev, $master, $subarray;

        $code=GETPOST('code', 'alpha');
        $action=GETPOST('action', 'alpha');
				$attrname=GETPOST('attrname', 'alpha');
				$elementtype=PageConfigSubModule::$descriptor->code;
$elementtype=((!empty($code)&&$elementtype!=$code)?$code : $elementtype);

        // Translations
        $langs->load("admin");
        $langs->load($this->Master->filelang);

				$master = $this->Master;
        $submodisprev = $this->submodisprev ;
        $subarray =  $this->apiregis->subarray;
        $currentmod = $this->Master->originalmodule;
// echo '/' . $this->Master->originalmodule . '/admin/tpl/' . $this->Master->currentpage . '.tpl';

				ob_start();
        dol_include_once('/framework/admin/tpl/' . $this->Master->currentpage . '.tpl');

        $output = ob_get_contents();
				ob_end_clean();

// 				var_dump($output);
// 				exit;
// 				$output = preg_replace('#(page=extrafields[?])#i','page=extrafields&',$output );

				if(!preg_match('#(page=extrafields&code='.$code.')#i', $output))
					$output = preg_replace('#(page=extrafields[&|^|?])#i','page=extrafields&code='.$code .'&',$output );

				print $output;
    }

}
