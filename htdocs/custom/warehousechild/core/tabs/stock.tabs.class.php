<?php
/* Copyright (C) 2017 	oscss-shop 					<support@oscss-shop.fr>
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
 */

/**
 * 	\file       htdocs/comm/propal/note.php
 * 	\ingroup    propal
 * 	\brief      Fiche d'information sur une proposition commerciale
 */
// namespace CORE\QUALITYREPORT;
use \Form;
use \Formother;
use \FormFile;
use \FormQualityReport;
use \UserGroup;
use \User;
use \ExtraFields;
// use \Propal;
// use \ExtraFields;
use \CORE\FRAMEWORK\Entrepot as Entrepot;
use \CORE\FRAMEWORK\AutoTabs as AutoTabs;
use \CORE\FRAMEWORK\AutoTabsRequired as AutoTabsRequired;
//
// use \QualityReport;
use \Task;

// use \AutoTabsRequired;
// use \dolmessage;


dol_include_once('/warehousechild/class/warehousechild.class.php');
dol_include_once('/warehousechild/class/html.formwarehousechild.class.php');
dol_include_once('/warehousechild/core/lib/warehousechild.lib.php');
dol_include_once('/framework/core/lib/framework.lib.php');

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load("companies");

$langs->load("warehousechild@warehousechild");

// Security check
// if ($user->societe_id) $socid=$user->societe_id;
// $result = restrictedArea($user, 'propale', $id, 'propal');


global $childwh;

Class TabsStock extends AutoTabs implements
AutoTabsRequired
{
    public
    /**
      @var
     */
        $name
        ,
        /**
          @var array
         */
        $refparam = array(
            'id' => 'int'
            , 'ref' => 'alpha'
            , 'action' => 'alpha'
            , 'mod' => 'alpha'
            , 'tab' => 'alpha'
            )

    ;

    /**
      @fn Init()
      @brief
      @param
      @return
     */
    public function _Init()
    {
        $file = basename(__FILE__);

        $this->type = substr($file, 0, strpos($file, '.'));

        $class = $this->FV->GetClassByType($this->type);
        // load Specific context

        $this->object         = newClass($class);
        $this->warehousechild = new warehousechild($this->db);

        return true;
    }

    /**
      @fn Init()
      @brief
      @param
      @return
     */
    public function _Process()
    {
        global $childwh, $separator;

        if ($this->GetParams('id') != null || $this->GetParams('ref') != null) {

            $this->object->fetch($this->GetParams('id'), $this->GetParams('ref'));
            $this->object->fetch_thirdparty();
// 			$this->societe = new Societe($this->db);
// 			$this->societe->fetch($this->object->socid);
        }


        $childwh = GETPOST('childwh', 'alpha') === 'true';
        //$separator = GETPOST('separator');
        $i       = 0;
        $args    = array();

        foreach (GETPOST('name', 'array') as $nom) {
            if (!empty($nom)) {
                $tmp               = array();
                $tmp['name']       = ''.$nom;
                $tmp['abb']        = GETPOST('abb')[$i];
                $tmp['start']      = (int) GETPOST('start')[$i];
                $tmp['qty']        = (int) GETPOST('qty')[$i];
                $tmp['setup']      = GETPOST('setup')[$i];
                $tmp['separator']  = GETPOST('separator')[$i];
                $tmp['separator2'] = GETPOST('separator2')[$i];
                $args[]            = $tmp;
            }
            $i++;
        }
        if (count($args) > 1) {
//            echo'<pre>';
//            var_dump($args);
            $this->warehousechild->createChildren($args, $this->GetParams('id'));
        }
        return true;
    }

    /**
      @fn Display()
      @brief
      @param
      @return
     */
    public function _Display()
    {
        global $trans, $conf, $user;


        print '<div class="fichecenter">';
        print '<div class="underbanner clearboth"></div>';

        if (!$this->warehousechild->has_child($this->GetParams('id')) && $user->rights->stock->creer || GETPOST('force','alpha')==='1') {

            $formfile = new WarehouseschildForm($this->db);

            $formfile->displayForm();
        } else {
            DrawLink($this->warehousechild
                , $this->type
                , $this->object
                , (string) $this->FV->GetClassByType($this->type)
                , (string) $this->FV->GetTableByType($this->type)
                , (string) $this->FV->GetDatefieldnameByType($this->type)
                , (string) $this->FV->GetLangByType($this->type)
                , 'warehousechild'
            );
$path='/framework/tabs/generic.php?mod=warehousechild&tab=stock&force=1&id='.$this->GetParams('id');
            print '<div class="tabsAction"><a class="butAction" href="'.dol_buildpath($path,2).'">';
            print 'Créer d\'autres entrepots enfants';
            print'</a></div>';
        }

        print '</div>';
        return true;
    }
}