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
dol_include_once('/fourn/class/fournisseur.product.class.php');


namespace CORE\FRAMEWORK;
use \CORE\FRAMEWORK\ProductFournisseur as ProductFournisseur ;



class ProductFournisseur
	extends \ProductFournisseur{

    public $OSE_loaded_version = DOL_VERSION;



    /**
			@brief Method qui modifie comportement original de la class parent
		*    Modify the purchase price for a supplier
		*
		*    @param  	int			$qty				Min quantity for which price is valid
		*    @param  	float		$buyprice			Purchase price for the quantity min
		*    @param  	User		$user				Object user user made changes
		*    @param  	string		$price_base_type	HT or TTC
		*    @param  	Societe		$fourn				Supplier
		*    @param  	int			$availability		Product availability
		*    @param	string		$ref_fourn			Supplier ref
		*    @param	float		$tva_tx				VAT rate
		*    @param  	string		$charges			costs affering to product
		*    @param  	float		$remise_percent		Discount  regarding qty (percent)
		*    @param  	float		$remise				Discount  regarding qty (amount)
		*    @param  	int			$newnpr				Set NPR or not
		*    @param	int			$delivery_time_days	Delay in days for delivery (max). May be '' if not defined.
     *    @return	int								<0 if KO, >=0 if OK
     */
    function update_buyprice($qty, $buyprice, $user, $price_base_type, $fourn, $availability, $ref_fourn, $tva_tx, $charges=0, $remise_percent=0, $remise=0, $newnpr=0, $delivery_time_days=0)
    {
        global $conf, $langs;
        //global $mysoc;

        // Clean parameter
        if (empty($qty)) $qty=0;
        if (empty($buyprice)) $buyprice=0;
        if (empty($charges)) $charges=0;
        if (empty($availability)) $availability=0;
        if (empty($remise_percent)) $remise_percent=0;
        if ($delivery_time_days != '' && ! is_numeric($delivery_time_days)) $delivery_time_days = '';
        if ($price_base_type == 'TTC')
		{
			//$ttx = get_default_tva($fourn,$mysoc,$this->id);	// We must use the VAT rate defined by user and not calculate it
			$ttx = $tva_tx;
			$buyprice = $buyprice/(1+($ttx/100));
		}
        $buyprice=price2num($buyprice,'MU');
		$charges=price2num($charges,'MU');
        $qty=price2num($qty);
		$error=0;

		$unitBuyPrice = price2num($buyprice/$qty,'MU');
		$unitCharges = price2num($charges/$qty,'MU');

		$now=dol_now();

        $this->db->begin();


        if ($this->product_fourn_price_id)
        {


        if ( !empty($conf->global->PRODUCT_PRICE_SUPPLIER_NO_LOG)){
					// Do a copy of current record into log table
					// Insert request
					$sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_fournisseur_price_log(";
					$sql .= "datec,";
					$sql .= "fk_product_fournisseur,";
					$sql .= "price,";
					$sql .= "quantity,";
					$sql .= "fk_user";
					$sql .= ") 		";
					$sql .= "SELECT";
					$sql .= " t.datec,";
					$sql .= " t.rowid,";
					$sql .= " t.price,";
					$sql .= " t.quantity,";
					$sql .= " t.fk_user";

					$sql .= " FROM " . MAIN_DB_PREFIX . "product_fournisseur_price as t";
					$sql .= " WHERE t.rowid = " . $this->product_fourn_price_id;

// 					$this->db->begin();
					dol_syslog(get_class($this) . "::update", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (! $resql) {
						$error ++;
						$this->errors [] = "Error " . $this->db->lasterror();
					}
       }



			$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql.= " SET fk_user = " . $user->id." ,";
            $sql.= " ref_fourn = '" . $this->db->escape($ref_fourn) . "',";
			$sql.= " price = ".price2num($buyprice).",";
			$sql.= " datec = NOW() , ";
			$sql.= " quantity = ".$qty.",";
			$sql.= " remise_percent = ".$remise_percent.",";
			$sql.= " remise = ".$remise.",";
			$sql.= " unitprice = ".$unitBuyPrice.",";
			$sql.= " unitcharges = ".$unitCharges.",";
			$sql.= " tva_tx = ".$tva_tx.",";
			$sql.= " fk_availability = ".$availability.",";
			$sql.= " entity = ".$conf->entity.",";
			$sql.= " info_bits = ".$newnpr.",";
			$sql.= " charges = ".$charges.",";
			$sql.= " delivery_time_days = ".($delivery_time_days != '' ? $delivery_time_days : 'null');
			$sql.= " WHERE rowid = ".$this->product_fourn_price_id;
			// TODO Add price_base_type and price_ttc

			dol_syslog(get_class($this).'::update_buyprice', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
                // Call trigger
                $result=$this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_UPDATE',$user);
                if ($result < 0) $error++;
                // End call triggers

				if (empty($error))
				{
					$this->db->commit();
					return 0;
				}
				else
				{
					$this->db->rollback();
					return 1;
				}
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				$this->db->rollback();
				return -2;
			}
        }

        else
        {
			// Delete price for this quantity
			$sql = "DELETE FROM  ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql.= " WHERE fk_soc = ".$fourn->id." AND ref_fourn = '".$this->db->escape($ref_fourn)."' AND quantity = ".$qty." AND entity = ".$conf->entity;
				dol_syslog(get_class($this).'::update_buyprice', LOG_DEBUG);
			$resql=$this->db->query($sql);
				if ($resql)
				{
		            // Add price for this quantity to supplier
		            $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price(";
		            $sql.= "datec, fk_product, fk_soc, ref_fourn, fk_user, price, quantity, remise_percent, remise, unitprice, tva_tx, charges, unitcharges, fk_availability, info_bits, entity, delivery_time_days)";
		            $sql.= " values('".$this->db->idate($now)."',";
		            $sql.= " ".$this->id.",";
		            $sql.= " ".$fourn->id.",";
		            $sql.= " '".$this->db->escape($ref_fourn)."',";
		            $sql.= " ".$user->id.",";
		            $sql.= " ".$buyprice.",";
		            $sql.= " ".$qty.",";
					$sql.= " ".$remise_percent.",";
					$sql.= " ".$remise.",";
		            $sql.= " ".$unitBuyPrice.",";
		            $sql.= " ".$tva_tx.",";
		            $sql.= " ".$charges.",";
		            $sql.= " ".$unitCharges.",";
		            $sql.= " ".$availability.",";
		            $sql.= " ".$newnpr.",";
		            $sql.= $conf->entity.",";
		            $sql.= $delivery_time_days;
		            $sql.=")";

		            dol_syslog(get_class($this)."::update_buyprice", LOG_DEBUG);
		            if (! $this->db->query($sql))
		            {
		                $error++;
		            }

		            if (! $error  && !empty($conf->global->PRODUCT_PRICE_SUPPLIER_NO_LOG))
		            {
		                // Add record into log table
		                $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price_log(";
		                $sql.= "datec, fk_product_fournisseur,fk_user,price,quantity)";
		                $sql.= "values('".$this->db->idate($now)."',";
		                $sql.= " ".$this->product_fourn_id.",";
		                $sql.= " ".$user->id.",";
		                $sql.= " ".price2num($buyprice).",";
		                $sql.= " ".$qty;
		                $sql.=")";

		                $resql=$this->db->query($sql);
		                if (! $resql)
		                {
		                    $error++;
		                }
		            }


		            if (! $error)
		            {
                        // Call trigger
                        $result=$this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_CREATE',$user);
                        if ($result < 0) $error++;
                        // End call triggers

					if (empty($error))
					{
						$this->db->commit();
						return 0;
					}
					else
					{
						$this->db->rollback();
						return 1;
					}
		            }
		            else
		            {
		                $this->error=$this->db->error()." sql=".$sql;
		                $this->db->rollback();
		                return -2;
		            }
		        }
		        else
		        {
		            $this->error=$this->db->error()." sql=".$sql;
		            $this->db->rollback();
		            return -1;
		        }
		    }
    }




    /**
			@brief method specific oscss-shop&Co
    */
    public function SearchRefProdForFourn($id_fourn, $ref_fourn, $qty=1){

// 			fk_product
				$sql = "SELECT * FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
        $sql.= " WHERE ref_fourn = '" . $this->db->escape($ref_fourn) .
							"' AND fk_soc = ".$id_fourn.
							" AND quantity <='".$qty."' " ;

//         echo $sql;
        dol_syslog(get_class($this)."::fetch_product_fournisseur_price", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
		$this->product_fourn_price_id	= $obj->rowid;
		$this->fourn_ref				= $obj->ref_fourn; // deprecated
	            $this->ref_supplier             = $obj->ref_fourn;
		$this->fourn_price				= $obj->price;
		$this->fourn_charges            = $obj->charges;
		$this->fourn_qty                = $obj->quantity;
		$this->fourn_remise_percent     = $obj->remise_percent;
		$this->fourn_remise             = $obj->remise;
		$this->fourn_unitprice          = $obj->unitprice;
		$this->fourn_unitcharges        = $obj->unitcharges;
		$this->fourn_tva_tx					= $obj->tva_tx;
		$this->product_id				= $obj->fk_product;	// deprecated
		$this->fk_product				= $obj->fk_product;
		$this->fk_availability			= $obj->fk_availability;
							$this->delivery_time_days		= $obj->delivery_time_days;
		//$this->fourn_tva_npr			= $obj->fourn_tva_npr; // TODO this field not exist in llx_product_fournisseur_price. We should add it ?
                $this->fk_supplier_price_expression      = $obj->fk_supplier_price_expression;

                if (empty($ignore_expression) && !empty($this->fk_supplier_price_expression)) {
                    $priceparser = new PriceParser($this->db);
                    $price_result = $priceparser->parseProductSupplier($this->fk_product, $this->fk_supplier_price_expression, $this->fourn_qty, $this->fourn_tva_tx);
                    if ($price_result >= 0) {
			$this->fourn_price = $price_result;
			//recalculation of unitprice, as probably the price changed...
	                    if ($this->fourn_qty!=0)
	                    {
	                        $this->fourn_unitprice = price2num($this->fourn_price/$this->fourn_qty,'MU');
	                    }
	                    else
	                    {
	                        $this->fourn_unitprice="";
	                    }
                    }
                }

		return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

}