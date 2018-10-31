<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
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

namespace CORE\WAREHOUSECHILD;

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';


/**
 * Description of product
 *
 * @author Oscss Shop <support@oscss-shop.fr>
 */
class CommandeFournisseur extends \CommandeFournisseur
{
    /**
     * Save a receiving into the tracking table of receiving (commande_fournisseur_dispatch) and add product into stock warehouse.
     *
     * @param 	User		$user					User object making change
     * @param 	int			$product				Id of product to dispatch
     * @param 	double		$qty					Qty to dispatch
     * @param 	int			$entrepot				Id of warehouse to add product
     * @param 	double		$price					Unit Price for PMP value calculation (Unit price without Tax and taking into account discount)
     * @param	string		$comment				Comment for stock movement
     * @param	date		$eatby					eat-by date
     * @param	date		$sellby					sell-by date
     * @param	string		$batch					Lot number
     * @param	int			$fk_commandefourndet	Id of supplier order line
     * @param	int			$notrigger          	1 = notrigger
     * @return 	int						<0 if KO, >0 if OK
     */
    public function dispatchProduct($user, $product, $qty, $entrepot, $price=0, $comment='', $eatby='', $sellby='', $batch='', $fk_commandefourndet=0, $notrigger=0)
    {
        global $conf, $langs;

        $error = 0;
        require_once DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php';

        // Check parameters (if test are wrong here, there is bug into caller)
        if ($entrepot <= 0)
        {
            $this->error='ErrorBadValueForParameterWarehouse';
            return -1;
        }
        /*
        if ($qty <= 0)
        {
            $this->error='ErrorBadValueForParameterQty';
            return -1;
        }
        */

        $dispatchstatus = 1;
        if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) $dispatchstatus = 0;	// Setting dispatch status (a validation step after receiving products) will be done manually to 1 or 2 if this option is on

        $now=dol_now();

        if (($this->statut == 3 || $this->statut == 4 || $this->statut == 5))
        {
            $this->db->begin();

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_dispatch";
            $sql.= " (fk_commande, fk_product, qty, fk_entrepot, fk_user, datec, fk_commandefourndet, status, comment, eatby, sellby, batch) VALUES";
            $sql.= " ('".$this->id."','".$product."','".$qty."',".($entrepot>0?"'".$entrepot."'":"null").",'".$user->id."','".$this->db->idate($now)."','".$fk_commandefourndet."', ".$dispatchstatus.", '".$this->db->escape($comment)."', ";
            $sql.= ($eatby?"'".$this->db->idate($eatby)."'":"null").", ".($sellby?"'".$this->db->idate($sellby)."'":"null").", ".($batch?"'".$batch."'":"null");
            $sql.= ")";

            dol_syslog(get_class($this)."::dispatchProduct", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                if (! $notrigger)
                {
                    global $conf, $langs, $user;
                    // Call trigger
                    $result=$this->call_trigger('LINEORDER_SUPPLIER_DISPATCH',$user);
                    if ($result < 0)
                    {
                        $error++;
                        return -1;
                    }
                    // End call triggers
                }
            }
            else
            {
                $this->error=$this->db->lasterror();
                $error++;
            }

            // Si module stock gere et que incrementation faite depuis un dispatching en stock
            if (! $error && $entrepot > 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
            {
                require_once  DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';

                $mouv = new \MouvementStock($this->db);
                if ($product > 0)
                {
                    // $price should take into account discount (except if option STOCK_EXCLUDE_DISCOUNT_FOR_PMP is on)
                    $mouv->origin = &$this;

                    if ($qty > 0) {
                        $result = $mouv->reception($user, $product, $entrepot, $qty, $price, $comment, $eatby, $sellby, $batch);
                        if ($result < 0) {
                            $this->error = $mouv->error;
                            $this->errors = $mouv->errors;
                            dol_syslog(__METHOD__  . ' ' . $this->error . " " . join(',', $this->errors), LOG_ERR);
                            $error++;
                        }
                    } else if ($qty < 0) {
                        $result = $mouv->livraison($user, $product, $entrepot, -$qty, $price, $comment, '', $eatby, $sellby, $batch);
                        if ($result < 0) {
                            $this->error = $mouv->error;
                            $this->errors = $mouv->errors;
                            dol_syslog(__METHOD__  . ' ' . $this->error . " " . join(',', $this->errors), LOG_ERR);
                            $error++;
                        }
                    }
                }
            }

            if ($error == 0)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error='BadStatusForObject';
            return -2;
        }
    }


    /**
     * Calc status regarding to dispatched stock
     *
     * @param 		User 	$user                   User action
     * @param       int     $closeopenorder         Close if received
     * @param		string	$comment				Comment
     * @return		int		                        <0 if KO, 0 if not applicable, >0 if OK
     */
    public function calcAndSetStatusDispatch(\User $user, $closeopenorder=1, $comment='')
    {
        global $conf, $langs;

        if (! empty($conf->fournisseur->enabled))
        {
            require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.dispatch.class.php';

            $qtydelivered=array();
            $qtywished=array();

            $supplierorderdispatch = new \CommandeFournisseurDispatch($this->db);
            $filter=array('t.fk_commande'=>$this->id);
            if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) {
                $filter['t.status']=1;	// Restrict to lines with status validated
            }

            $ret=$supplierorderdispatch->fetchAll('','',0,0,$filter);
            if ($ret<0)
            {
                $this->error=$supplierorderdispatch->error; $this->errors=$supplierorderdispatch->errors;
                return $ret;
            }
            else
            {
                if (is_array($supplierorderdispatch->lines) && count($supplierorderdispatch->lines)>0)
                {
                    $date_liv = dol_now();

                    // Build array with quantity deliverd by product
                    foreach($supplierorderdispatch->lines as $line) {
                        $qtydelivered[$line->fk_product]+=$line->qty;
                    }
                    foreach($this->lines as $line) {
                        $qtywished[$line->fk_product]+=$line->qty;
                    }

                    $allDelivered = TRUE;
                    foreach ($qtydelivered as $idProduct => $qtyDelivery) {
                        $qtyOrder = $qtywished[$idProduct];

                        // delivered less than ordered
                        if ($qtyDelivery < $qtyOrder) {
                            $allDelivered = FALSE;
                            break;
                        }
                    }

                    // all received or more
                    if ($allDelivered===TRUE) {
                        if ($closeopenorder) {
                            $ret = $this->Livraison($user, $date_liv, 'tot', $comment);
                            if ($ret < 0) {
                                return -1;
                            }
                            return 5;
                        } else {
                            // received partially
                            $ret = $this->Livraison($user, $date_liv, 'par', $comment);
                            if ($ret < 0) {
                                return -1;
                            }
                            return 4;
                        }
                    } else {
                        // received partially
                        $ret = $this->Livraison($user, $date_liv, 'par', $comment);
                        if ($ret<0) {
                            return -1;
                        }
                        return 4;
                    }
                }
                return 1;
            }
        }
        return 0;
    }
}