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

class WarehouseschildForm extends Form
{
    public $predefinedSubdivide;

    public function __construct($db)
    {
        parent::__construct($db);

        $this->predefinedSubdivide   = array();
        $this->predefinedSubdivide[] = array('name' => 'Batiment');
        $this->predefinedSubdivide[] = array('name' => 'Etage');
        $this->predefinedSubdivide[] = array('name' => 'Salle');
        $this->predefinedSubdivide[] = array('name' => 'Allée');
        $this->predefinedSubdivide[] = array('name' => 'Armoire');
        $this->predefinedSubdivide[] = array('name' => 'Rayon');
        $this->predefinedSubdivide[] = array('name' => 'Tiroir');
        $this->predefinedSubdivide[] = array('name' => 'Alvéole');
        $this->predefinedSubdivide[] = array('name' => '');
        $this->predefinedSubdivide[] = array('name' => '');
    }

    function displayForm()
    {

        print '<form method="POST">';


        print '<table class="border liste">';
        print '<thead><th>';
        print 'Nom<sup>*</sup>';
        print '</th><th>';
        print 'Abréviation';
        print '</th><th>';
        print 'Premier';
        print '</th><th>';
        print 'Quantité';
        print '</th><th>';
        print 'Réglages';
        print '</th><th>';
        print 'Séparateur de niveau';
        print '</th><th>';
        print 'Séparateur de nombre';
        print '</th></thead>';
        $i = 0;
        foreach ($this->predefinedSubdivide as $level) {
            print '<tr><td>';
            print '<input type="text" class="center" name="name['.$i.']" value="'.$level['name'].'" placeholder="non utilisé" />';

            print '</td><td>';
            print '<input type="text" class="center" name="abb['.$i.']" value="'.substr($level['name'], 0, 3).'" />';

            print '</td><td>';
            print '<input type="number" class="center" name="start['.$i.']" value="1" step="1" min="1" max="256"/>';

            print '</td><td>';
            print '<input type="number" class="center" name="qty['.$i.']" value="2" step="1" min="1" max="256"/>';

            print '</td><td>';
            print ' <label><input type="radio" name="setup['.$i.']" value="digit" checked /> chiffres</label>';
            print ' / <label><input type="radio" name="setup['.$i.']" value="letter" /> lettres</label>';

            print '</td><td align="center">';
            print '<input type="text" class="center" name="separator['.$i.']" value=" - " size="3" />';

            print '</td><td align="center">';
            print '<input type="text" class="center" name="separator2['.$i.']" value="_" size="3" />';

            print '</td></tr>';
            $i++;
        }
        print '</table>';
        print '<br /><label><input type="radio" name="childwh" value="true" checked/> ';
        print '    Créer un entrepot pour chaque niveau';
        print '</label><br />';
        print 'ou';
        print '<br /><label><input type="radio" name="childwh" value="false"  /> ';
        print 'Créer un entrepot uniquement pour le dernier niveau en reprenant les noms des parents';
        print '</label><br/><br />';
        print '<input class="butAction" type="submit" value="Créer les entrepots enfants" />';
        print '</form>';
    }

    function productFavoriteWC($object)
    {
        $sql   = "SELECT fk_target FROM	".MAIN_DB_PREFIX."element_element WHERE	fk_source = $object->id AND sourcetype = 'product' AND targettype = 'stock'";
        //var_dump($sql);
        $resql = $this->db->query($sql);
        print '<table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;"><tbody><tr><td class="nobordernopadding" valign="middle"><div class="titre">Entrepôt par défaut</div></td></tr></tbody></table>';
        print '<div class="div-table-responsive-no-min">
    <table class="liste formdoc noborder" summary="listofdocumentstable" width="100%"><tbody>
    <tr class="liste_titre">
    <th colspan="5" class="formdoc liste_titre maxwidthonsmartphone" align="center">&nbsp;</th></tr>';
        if ($resql) {
            require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
            $staticEntrepot = new Entrepot($this->db);
            $num            = $this->db->num_rows($resql);
            $i              = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $fetch = $staticEntrepot->fetch($obj->fk_target);
                if($fetch==1) {//pas supprimé
                    print '<tr class="oddeven"><td colspan="3" class="">';
                    print $staticEntrepot->getNomUrl(1, '', 1);
                    print '</td></tr>';
                }
                $i++;
            }
        } else {
            print '<tr class="oddeven"><td colspan="3" class="opacitymedium">Aucun</td></tr>';
        }
        print '</tbody></table></div>';
    }
}