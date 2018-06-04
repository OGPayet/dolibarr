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

require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

class warehousechild extends Entrepot
{

    /**
     * \brief       brief
     * \details     details
     * \param       string  param         desc
     * \return      bool  true si vrai
     */
    function get_element_list()
    {
        $children = array();
        $this->get_children_warehouses($this->id, $children);
        return $children;
    }

    function has_child($id)
    {
        global $conf;

        $sql = "SELECT COUNT(*) as total";
        $sql .= " FROM ".MAIN_DB_PREFIX."entrepot";
        $sql .= " WHERE fk_parent=".$id;

        //print $sql;
        $result = $this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            $ret = $obj->total;
            $this->db->free($result);
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return $ret > 0;
    }

    function createChildren($args, $parent = null, $prename = '', $preabb = '')
    {
        global $childwh;
        $arg = array_shift($args);
        //var_dump($arg);
        for ($i =  $arg['start']; $i <=  $arg['start'] + $arg['qty'] - 1; $i++) {
            $indice = $i;
            if ($arg['setup'] === 'letter') { // si on veut des lettres
                $indice = chr($indice + 64);
            }
            $name = ((!empty($prename)) ? $prename.$arg['separator'] : '').$arg['name'].$arg['separator2'].$indice;
            $abb  = ((!empty($prename)) ? $preabb.$arg['separator'] : '').$arg['abb'].$arg['separator2'].$indice;
            if ($childwh || empty($args)) {
                $childparent = $this->createEnt($name, $abb, $parent);
            } else {
                $childparent = $parent;
                if (!$childwh) {
                    $childprename = $name;
                    $childpreabb  = $abb;
                }
            }
            if (!empty($args)) {
                $this->createChildren($args, $childparent, $childprename, $childpreabb);
            }
        }
    }

    function createEnt($name, $abb, $parent=0)
    {
//        if($parent==0){
//            die('no parent');
//        }
        global $user;
        $staticEnt            = new Entrepot($this->db);
        $staticEnt->libelle   = $name;
        $staticEnt->lieu      = $abb;
        $staticEnt->fk_parent = $parent;
        $staticEnt->statut    = 1;
        $staticEnt->create($user);
        $newid                = $staticEnt->id;
        if ($newid <= 0) {
            die("Impossible de créer , fixer ce problème avec ceci<hr>ALTER TABLE `llx_entrepot` DROP INDEX `uk_entrepot_label`, ADD UNIQUE `uk_entrepot_label` (`label`, `entity`, `fk_parent`) USING BTREE;");
        }
//        $newid= rand(5000,10000);
//        echo "<hr>$name | $abb | $parent | $newid<br>";
        return $newid;
    }
}