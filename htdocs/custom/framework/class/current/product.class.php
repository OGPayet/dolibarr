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

// dol_include_once('/compta/facture/class/facture.class.php'); // used for constant
// dol_include_once('/product/class/product.class.php'); // used for invoice supplier
//


namespace CORE;
dol_include_once('/product/class/product.class.php');


namespace CORE\FRAMEWORK;
use \CORE\FRAMEWORK\Product as Product ;



class Product
	extends \Product{

    public $OSE_loaded_version = DOL_VERSION;


	/**
	 * Check TYPE constants
	 * @var int
	 */
	var $type = self::TYPE_PRODUCT;


	/**
	 * Regular product
	 */
	const TYPE_PRODUCT = 0;
	/**
	 * Service
	 */
	const TYPE_SERVICE = 1;


    /**
     *  Load information for tab info
     *
     *  @param  int		$id     Id of thirdparty to load
     *  @return	void
     */
    function info($id)
    {
        $sql = "SELECT p.rowid, p.ref, p.datec as date_creation, p.tms as date_modification,";
        $sql.= " p.fk_user_author, p.fk_user_author as fk_user_modif";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as p";
        $sql.= " WHERE p.rowid = ".$id;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if ($obj->fk_user_author) {
                    $cuser = new \User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation     = $cuser;
                }

                if ($obj->fk_user_modif) {
                    $muser = new \User($this->db);
                    $muser->fetch($obj->fk_user_modif);
                    $this->user_modification = $muser;
                }

                $this->ref			     = $obj->ref;
                $this->date_creation     = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
            }

            $this->db->free($result);

        }
        else
		{
            dol_print_error($this->db);
        }
    }

		/**
			@fn
			@brief
		*/
		public function __call($name, $args) {

			if(preg_match('#(update|create|delete)#', $name))  {
			print_r($args);

			$result = 0;
				$key = strtoupper('prev_'.__CLASS__.'_'.$name);
     // Call trigger
			$result=$this->call_trigger('PREV_'.$key,$user);
			if ($result < 0) { $error++; }
			// End call triggers

			if ($result >= 0)
				$result = parent::$name($user,$notrigger);
				if ($result < 0) { $error++; }
// 			    var_dump(__file__);
//     exit;

			// Call trigger
			if ($result >= 0)
			$result=$this->call_trigger('POST_'.$key,$user);
			if ($result < 0) { $error++; }
			// End call triggers

			return $result;
			}
			return false;
		}


		public function create($user,$notrigger=0){

			$result = 0;

     // Call trigger
			$result=$this->call_trigger('PREV_PRODUCT_MODIFY',$user);
			if ($result < 0) { $error++; }
			// End call triggers

			if ($result >= 0)
				$result = parent::create($user,$notrigger);
				if ($result < 0) { $error++; }
// 			    var_dump(__file__);
//     exit;

			// Call trigger
			if ($result >= 0)
			$result=$this->call_trigger('POST_PRODUCT_MODIFY',$user);
			if ($result < 0) { $error++; }
			// End call triggers

			return $result;
		}
    public function update($id, $user, $notrigger=false, $action='update'){

			$result = 0;

     // Call trigger
			$result=$this->call_trigger('PREV_PRODUCT_MODIFY',$user);
			if ($result < 0) { $error++; }
			// End call triggers

			if ($result >= 0)
				$result = parent::update($id, $user, $notrigger, $action);
				if ($result < 0) { $error++; }
// 			    var_dump(__file__);
//     exit;

			// Call trigger
			if ($result >= 0)
			$result=$this->call_trigger('POST_PRODUCT_MODIFY',$user);
			if ($result < 0) { $error++; }
			// End call triggers

			return $result;
		}

    /**
     * Return if object has a sell-by date or eat-by date
     *
     * @return  boolean     True if it's has
     */
	function hasbatch()
	{
		return ($this->status_batch > 0 ? true : false);
	}

}
