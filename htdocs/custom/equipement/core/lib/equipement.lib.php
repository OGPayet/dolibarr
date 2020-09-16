<?php
/* Copyright (C) 2012-2017	Charlene Benke		<charlie@patas-monkey.com>
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

/**
 *	\file	   htdocs/equipement/core/lib/equipement.lib.php
 *	\brief	  Ensemble de fonctions de base pour le module equipement
 *	\ingroup	equipement
 */

/**
*  Return array head with list of tabs to view object informations.
*
*  @param	Object	$object		Product
*  @return	array   			head array with tabs
*/
function equipement_admin_prepare_head($object=null)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/equipement/admin/equipement.php", 1);
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'equipement_admin');

	$head[$h][0] = dol_buildpath('/equipement/admin/equipement_extrafields.php', 1);
	$head[$h][1] = $langs->trans("ExtraFieldsEquip");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/admin/equipementevt_extrafields.php', 1);
	$head[$h][1] = $langs->trans("ExtraFieldsEvt");
	$head[$h][2] = 'attributesevt';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/admin/about.php', 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'equipement_admin', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function equipement_prepare_head($object)
{
	global $langs, $conf; //, $user;
	$langs->load("equipement@equipement");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/equipement/card.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/events.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Event');
	$nbEvents= $object->get_Events();
	if ($nbEvents > 0) $head[$h][1].= ' <span class="badge">'.$nbEvents.'</span>';
	$head[$h][2] = 'event';
	$h++;

	// g�rer la composition d'un �quipement avec factory
	if ($conf->global->MAIN_MODULE_FACTORY) {
		$head[$h][0] = dol_buildpath('/equipement/composition.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Composition');
		$head[$h][2] = 'composition';
		$h++;
	}

	$head[$h][0] = dol_buildpath('/equipement/consumption.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Consumption');
	$nbConsumption= $object->get_Consumptions();
	if ($nbConsumption > 0) $head[$h][1].= ' <span class="badge">'.$nbConsumption.'</span>';

	$head[$h][2] = 'consumption';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/contact.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Contact');
	$head[$h][2] = 'contact';
	$h++;

	// Show more tabs from modules
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'equipement');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$head[$h][0] = dol_buildpath('/equipement/note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		$nbNotes = ($object->note_private?1:0);
		$nbNotes+= ($object->note_public?1:0);
		if ($nbNotes > 0) $head[$h][1].= ' <span class="badge">'.$nbNotes.'</span>';
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->equipement->dir_output .'/'.dol_sanitizeFileName($object->id);
	$nbFiles = count(dol_dir_list($upload_dir, 'files'));
	$head[$h][0] = dol_buildpath('/equipement/document.php?id='.$object->id, 1);
	$head[$h][1] = $langs->trans("Documents");
	if ($nbFiles > 0) $head[$h][1].= ' <span class="badge">'.$nbFiles.'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/info.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function equipement_entrepot_prepare_head($object)
{
	global $langs, $conf;
	$langs->load("equipement@equipement");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/equipement/tabs/entrepot.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("EquipementInStock");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/tabs/entrepotadd.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("EntrepotAdd");
	$head[$h][2] = 'add';
	$h++;

// in operation module
//	$head[$h][0] = dol_buildpath('/operation/equipement/entrepot/moveout.php', 1).'?id='.$object->id;
//	$head[$h][1] = $langs->trans("EntrepotMoveOut");
//	$head[$h][2] = 'moveout';
//	$h++;
//
//	$head[$h][0] = dol_buildpath('/operation/equipement/entrepot/movein.php', 1).'?id='.$object->id;
//	$head[$h][1] = $langs->trans("EntrepotMovein");
//	$head[$h][2] = 'movein';
//	$h++;
//
//
//	$head[$h][0] = dol_buildpath('/operation/equipement/entrepot/movelist.php', 1).'?id='.$object->id;
//	$head[$h][1] = $langs->trans("EntrepotMoveList");
//	$head[$h][2] = 'movelist';
//	$h++;

	// Show more tabs from modules
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'equipemententrepot');
	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function equipement_product_prepare_head($object)
{
	global $langs, $conf;
	$langs->load("equipement@equipement");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/equipement/tabs/produit.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("ListOfEquipements");
	$head[$h][2] = 'equipement';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/tabs/produitsociete.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("ProductSociete");
	$head[$h][2] = 'societe';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/tabs/predefevent.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("PrefinedEvent");
	$head[$h][2] = 'event';
	$h++;

	// Show more tabs from modules
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'equipementproduct');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function equipement_event_prepare_head($object)
{
	global $langs, $conf;
	$langs->load("equipement@equipement");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/equipement/events/card.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'event';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/events/consumedproduct.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("ConsumedProduct");
	$head[$h][2] = 'product';
	$h++;

	$head[$h][0] = dol_buildpath('/equipement/events/elementlink.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("LinkEvent");
	$head[$h][2] = 'link';
	$h++;

	// Show more tabs from modules
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'equipementevent');
	return $head;
}



///**
// *	Return list of entrepot (for the stock
//  *
// *	@param  string	$selected	   Preselected type
// *	@param  string	$htmlname	   Name of field in html form
// * 	@param	int		$showempty		Add an empty field
// * 	@param	int		$hidetext		Do not show label before combo box
// *  @return	void
// */
function select_entrepot($selected='', $htmlname='entrepotid', $showempty=0, $hidetext=0, $size=0, $addchkbox=1, $filterstatus='',
                         $disabled=0, $fk_product=0, $empty_label='', $showstock=0, $forcecombo=0, $events=array(), $morecss='minwidth200', $exclude='', $showfullpath=1, $noprint=0)
{
    global $conf, $db, $langs; //, $user;

    dol_syslog("Equipement.Lib::select_entrepot $selected, $htmlname, $showempty, $hidetext, $size, $addchkbox, $filterstatus, $disabled, $fk_product, $empty_label, $showstock, $forcecombo, $events, $morecss, $exclude, $showfullpath", LOG_DEBUG);

    require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
    $formproduct = new FormProduct($db);

    $out = '';
    if (empty($conf->global->ENTREPOT_EXTRA_STATUS)) $filterstatus = '';
    $formproduct->loadWarehouses($fk_product, '', $filterstatus, true, $exclude);
    $nbofwarehouses = count($formproduct->cache_warehouses);

    if (empty($hidetext)) print $langs->trans("EntrepotStock") . ': ';

    if ($nbofwarehouses > 0) {
        $nodatarole = '';
        if ($conf->use_javascript_ajax && !$forcecombo) {
            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            $comboenhancement = ajax_combobox($htmlname, $events);
            $out .= $comboenhancement;
            $nodatarole = ($comboenhancement ? ' data-role="none"' : '');
        }

        $out .= '<select class="flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($disabled ? ' disabled' : '') . ' id="' . $htmlname . '" name="' . ($htmlname . ($disabled ? '_disabled' : '')) . '"' . $nodatarole . (!empty($size) ? ' size="' . $size . '"' : '') . '>';
        if ($showempty) $out .= '<option value="-1">' . ($empty_label ? $empty_label : '&nbsp;') . '</option>';
        foreach ($formproduct->cache_warehouses as $id => $arraytypes) {
            $out .= '<option value="' . $id . '"';
            if ($selected == $id || ($selected == 'ifone' && $nbofwarehouses == 1)) $out .= ' selected';
            $out .= '>';
            if ($showfullpath) $out .= $arraytypes['full_label'];
            else $out .= $arraytypes['label'];
            if (($fk_product || ($showstock > 0)) && ($arraytypes['stock'] != 0 || ($showstock > 0))) $out .= ' (' . $langs->trans("Stock") . ':' . $arraytypes['stock'] . ')';
            if ($arraytypes['zip'])
                $out .= '</option>';
        }
        $out .= '</select>';
        if ($disabled) $out .= '<input type="hidden" name="' . $htmlname . '" value="' . (($selected > 0) ? $selected : '') . '">';

        if ($addchkbox) {
            $out .= '&nbsp;&nbsp;<input type="checkbox" name="' . $htmlname . 'move"' . (!empty($conf->global->EQUIPEMENT_CHKBOXSTOCKMVTON) ? ' checked' : '') . ' value=1>';
            $out .= '&nbsp;' . $langs->trans("CreateStockMovement");
        }
    } else {
        // si pas de liste, on positionne un hidden à vide
        $out .= '<input type="hidden" name="' . $htmlname . '" value=-1>';
        $out .= '<input type="hidden" name="' . $htmlname . 'move" value=-1>';
        $langs->load("errors");
        $out .= $langs->trans("ErrorNoWarehouseDefined");
    }

    if ($noprint) {
        return $out;
    } else {
        print $out;
    }
}

function select_equipements($selected='', $filterproduct='', $filterentrepot='', $htmlname='equipementid', $showempty=0, $hidetext=0, $showadditionnalinfo=0)
{
	global $db, $langs, $conf; //, $user;
	if (empty($hidetext)) print $langs->trans("Equipement").': ';

	// boucle sur les �quipements valides
	$sql = "SELECT rowid, ref, unitweight, datee, numversion";
	$sql.= " FROM ".MAIN_DB_PREFIX."equipement";
	$sql.= " WHERE fk_statut >= 1";
	if ($filterproduct)
		$sql.=" and fk_product=".$filterproduct;
	if ($filterentrepot)
		$sql.=" and fk_entrepot=".$filterentrepot;
	$sql.= " ORDER BY ref DESC";

	dol_syslog("Equipement.Lib::select_equipement sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->ref;
				if ($conf->global->EQUIPEMENT_SHOWADDITIONNALINFO == "1")
					print " (".$obj->numversion." - " .price($obj->unitweight)." Kg - ".dol_print_date($obj->datee, 'day').")";
				print "</option>";
				$i++;
			}
			print '</select>';
		}
		else
			print $langs->trans("NoEquipementsFind");
	}

}

function select_contracts($selected='', $filtersoc='', $htmlname='contratid', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("Contracts").': ';

	// boucle sur les contrats valides
	$sql = "SELECT rowid, fk_soc, ref, mise_en_service, fin_validite";
	$sql.= " FROM ".MAIN_DB_PREFIX."contrat";
	$sql.= " WHERE statut >= 1";
	if ($filtersoc) {
		$sql.=" AND ( fk_soc=".$filtersoc;
		if ($selected)
			$sql.=" or rowid=".$selected;
		$sql.=" )";
	}
	$sql.= " ORDER BY date_contrat DESC";

	dol_syslog("Equipement.Lib::select_contrats sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected)
					print ' selected="selected"';
				print ">".$obj->ref." (".dol_print_date($obj->mise_en_service, 'day');
				print " - ".dol_print_date($obj->fin_validite, 'day').")</option>";
				$i++;
			}
			print '</select>';
		} else
			print $langs->trans("NoContractsFind");
	}
}

function select_interventions($selected='', $filtersoc='0', $htmlname='fichinterid', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("Intervention").': ';

	// boucle sur les interventions valides
	$sql = "SELECT rowid, fk_soc, ref "; //, dateo, datee";
	$sql.= " FROM ".MAIN_DB_PREFIX."fichinter";
	$sql.= " WHERE fk_statut >= 1";
	// si une soci�t� est s�lectionn�
	// ou si il s'agit d'une interventions s�lectionn� sur une autre soci�t� (historique)
	if ($filtersoc) {
		$sql.=" and ( fk_soc=".$filtersoc;
		if ($selected)
			$sql.=" or rowid=".$selected;
		$sql.=" )";
	}

	//$sql.= " ORDER BY dateo DESC";
	$sql.= " ORDER BY ref DESC";

	dol_syslog("Equipement.Lib::select_interventions sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->ref." </option>";
				$i++;
			}
			print '</select>';
		} else
			print $langs->trans("NoFichintersFind");
	}
}

function select_produitEntrepot($selected='', $fk_entrepot, $htmlname='fk_product', $showempty=0, $hidetext=0, $size=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("RefFactFourn").': ';

	// boucle produit dans l'entrepot
	$sql = "SELECT p.rowid as rowid, p.ref, p.label as produit, ps.reel";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_stock ps, ".MAIN_DB_PREFIX."product p";
	$sql.= " WHERE ps.fk_product = p.rowid";
	$sql.= " AND ps.reel <> 0";	// We do not show if stock is 0 (no product in this warehouse)
	$sql.= " AND ps.fk_entrepot = ".$fk_entrepot;
	$sql.= " ORDER BY p.ref";

	dol_syslog("Equipement.Lib::select_produitEntrepot sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			if ($size == 0)
				print '<select  class="flat" name="'.$htmlname.'">';
			else
				print '<select onChange="MM_jumpMenu(\'parent\',this,0)"  class="flat" size='.$size.' name="'.$htmlname.'">';

			if ($showempty) {
				print '<option value="entrepot.php?id='.$fk_entrepot.'"';
				if ($selected == -1) print ' selected="selected"';
				print '>'.$langs->trans("All").'</option>';
			}

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($size ==0)
					print '<option value="'.$obj->rowid.'"';
				else
					print '<option value="entrepot.php?id='.$fk_entrepot.'&search_refProduct='.$obj->rowid.'"';

				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->ref." - ".$obj->label." (".$obj->reel.")</option>";
				$i++;
			}
			print '</select>';
		}
		else // si pas de liste, on positionne un hidden � vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';

	}
}

function select_expeditions($selected='0', $filtersoc='0', $htmlname='expeditionid', $showempty=0, $hidetext=0, $allexpedition=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("Expeditions").': ';

	// boucle sur les expeditions
	$sql = "SELECT rowid, ref, fk_soc, tracking_number, date_expedition";
	$sql.= " FROM ".MAIN_DB_PREFIX."expedition";
	$sql.= " WHERE 1=1";

	// on ajoute des �quipements que sur les exp�ditions en pr�paration
	$sql.= " AND fk_statut <> 2";

	if ($filtersoc) {
		$sql.=" and ( fk_soc=".$filtersoc;
		if ($selected)
			$sql.=" or rowid=".$selected;
		$sql.=" )";
	}
	$sql.= " ORDER BY date_expedition DESC";

	dol_syslog("Equipement.Lib::select_interventions sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->ref." (".dol_print_date($obj->date_expedition, 'day')." - ".$obj->tracking_number.")</option>";
				$i++;
			}
			print '</select>';

		}
		else
			print $langs->trans("NoExpeditionsFind");
	}
}

function select_factfourn($selected='', $fournid, $htmlname='fk_factfourn', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("RefFactFourn").': ';

	// boucle sur les factures fournisseurs
	$sql = "SELECT rowid, ref, datef, total_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn";
	$sql.= " where fk_soc=".$fournid ;
	//$sql.= " and statut >= 1";
	$sql.= " ORDER BY datef desc";

	dol_syslog("Equipement.Lib::select_factfourn sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->ref." - ".dol_print_date($obj->datef, 'day')." - ".price($obj->total_ttc)."</option>";
				$i++;
			}
			print '</select>';
		}
		else	// si pas de liste, on positionne un hidden � vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
	}
}

function select_commfourn($selected='', $fournid, $htmlname='fk_commande_fourn', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("RefCommFourn").': ';

	// boucle sur les factures fournisseurs
	$sql = "SELECT rowid, ref, date_commande, total_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
	$sql.= " where fk_soc=".$fournid ;
	//$sql.= " and statut >= 1";
	$sql.= " ORDER BY date_commande desc";

	dol_syslog("Equipement.Lib::select_factfourn sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->ref." - ".dol_print_date($obj->date_commande, 'day')." - ".price($obj->total_ttc)."</option>";
				$i++;
			}
			print '</select>';
		}
		else	// si pas de liste, on positionne un hidden � vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
	}
}

function select_facture($selected='', $clientid, $htmlname='fk_fact_client', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("RefFactClient").': ';

	// boucle sur les factures
	$sql = "SELECT rowid, facnumber, datef, total_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture";
	$sql.= " where fk_soc=".$clientid ;
	//$sql.= " and statut >= 1";
	$sql.= " ORDER BY datef desc";
	dol_syslog("Equipement.Lib::select_facture sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->facnumber." - ".dol_print_date($obj->datef, 'day')." - ".price($obj->total_ttc)."</option>";
				$i++;
			}
			print '</select>';
		}
		else	// si pas de liste, on positionne un hidden � vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
	}
}

/**
 *	Return list of status of equipement
 *
 *	@param  string	$selected	   Preselected type
 *	@param  string	$htmlname	   Name of field in html form
 * 	@param	int		$showempty		Add an empty field
 * 	@param	int		$hidetext		Do not show label before combo box
 * 	@param	string	$forceall		Force to show products and services in combo list, whatever are activated modules
 *  @return	void
 */
function select_equipement_etat($selected='', $htmlname='fk_etatequipement', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("EquipementState").': ';

	// boucle sur les entrepots
	$sql = "SELECT rowid, libelle";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_equipement_etat";
	$sql.= " WHERE active = 1";

	dol_syslog("Equipement.Lib::select_equipement_etat sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$langs->trans($obj->libelle)."</option>";
				$i++;
			}
			print '</select>';
		}
		else	// si pas de liste, on positionne un hidden � vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
	}
}

/**
 *	Return list of types of event of equipement
 *
 *	@param  string	$selected	   Preselected type
 *	@param  string	$htmlname	   Name of field in html form
 * 	@param	int		$showempty		Add an empty field
 * 	@param	int		$hidetext		Do not show label before combo box
 * 	@param	string	$forceall		Force to show products and services in combo list, whatever are activated modules
 *  @return	void
 */
function select_equipementevt_type($selected='', $htmlname='fk_equipementevt_type', $showempty=0, $hidetext=0)
{
	global $db, $langs; //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("TypeofEquipementEvent").': ';

	// boucle sur les entrepots
	$sql = "SELECT rowid, libelle, color";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_equipementevt_type";
	$sql.= " WHERE active = 1";

	dol_syslog("Equipement.Lib::select_equipementevt_type sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->color)
					print " style='background-color: #".$obj->color.";' ";
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$langs->trans($obj->libelle)."</option>";
				$i++;
			}
			print '</select>';
		}
		else	// si pas de liste, on positionne un hidden � vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
	}
}

function print_lotequipement($lineid, $fk_product, $fk_entrepot, $nbsend)
{
	global $db; // , $langs;

	$sql = "SELECT e.rowid, e.ref, e.description, e.fk_product, e.fk_statut, e.fk_entrepot,";
	$sql.= " e.numversion, e.quantity, e.datec, e.datev, e.datee, e.dateo, e.unitweight";
	$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
	$sql.= " WHERE e.fk_product=".$fk_product;
	$sql.= " AND e.fk_entrepot=".$fk_entrepot;
	$sql.= " AND e.fk_statut=1";
	$sql.= " AND e.quantity > 1";
	$sql.= " ORDER BY datee ASC";
//print $sql;
	dol_syslog("equipement.lib::print_lotequipement sql=".$sql, LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<table>';
			// pour g�rer le nombre � exp�dier
			$nbexpedition=$nbsend;
			while ($i < $num) {
				print "<tr>";
				$obj = $db->fetch_object($resql);

				print "<td>".$obj->ref.' - '.dol_print_date($obj->datee, "daytext")."</td>";
				print "<td><input type=text size=3 name='lotEquipement[".$lineid."-".$obj->fk_product."][".$obj->rowid."-".$obj->quantity."]' ";
				if ($nbexpedition==0) {
					// si il y en assez � exp�dier, on affiche si on veux ventiler l'envoie selon les lots
					print " value='0'>";
				} elseif ($obj->quantity >= $nbexpedition) {
					print " value='".$nbexpedition."'>";
					$nbexpedition=0;
				} else {	// si il n'y en a pas assez
					print " value='".$obj->quantity."'>";
					$nbexpedition=$nbexpedition-$obj->quantity;
				}
				print '</td>';
				print "<td align=left>/".$obj->quantity."</td>";
				print "</tr>";
				$i++;
			}
			print '</table>';
		}
//		else
//			print $langs->trans("NoLotForThisEquipement");
	}
}

function print_equipementdispo($lineid, $fk_product, $fk_entrepot, $nbsend)
{
	global $db, $langs;

	// on commence par r�cup�rer la liste des lots pr�sents dans l'entrepot
	$sql = "SELECT e.numversion, e.datee, e.dateo, count(*) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
	$sql.= " WHERE e.fk_product=".$fk_product;
	$sql.= " AND e.fk_entrepot=".$fk_entrepot;
	$sql.= " AND e.fk_statut=1";
	$sql.= " AND e.quantity = 1";
	$sql.= " GROUP BY e.numversion, e.datee, e.dateo";
	$sql.= " ORDER BY datee ASC";

	dol_syslog("equipement.lib::print_lotequipement sql=".$sql, LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql) {
		$numlot = $db->num_rows($resql);
		$i = 0;
		if ($numlot) {
			print '<table>';
			while ($i < $numlot) {
				$objlot = $db->fetch_object($resql);
				if ($objlot->nb > 0) {
					$lotequipement=($objlot->numversion?$objlot->numversion:"(".$langs->trans("Empty").")")." (".$objlot->nb.")";
					$titlelot = " [".dol_print_date($db->jdate($objlot->dateo), 'day');
					$titlelot.= " - ".dol_print_date($db->jdate($objlot->datee), "day")."] ";

					print '<tr><td><div class="lot" title="'.$titlelot.'">';
					print img_picto("", "edit_add")."&nbsp;";
					print $lotequipement;

					print '</div >';
				}
				$sql = "SELECT e.rowid, e.ref, e.description, e.unitweight";
				$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
				$sql.= " WHERE e.fk_product=".$fk_product;
				$sql.= " AND e.fk_entrepot=".$fk_entrepot;
				$sql.= " AND e.fk_statut=1";
				$sql.= " AND e.quantity = 1";
				$sql.= " AND e.numversion = '".$objlot->numversion."'" ;
				if ($objlot->dateo)
					$sql.= " AND e.dateo ='".$objlot->dateo."'";
				if ($objlot->datee)
					$sql.= " AND e.datee ='".$objlot->datee."'";
				$sql.= " ORDER BY ref ASC";

			//print $sql.'<br>';
				dol_syslog("equipement.lib::print_lotequipement sql=".$sql, LOG_DEBUG);
				$resqlcontent=$db->query($sql);
				if ($resqlcontent) {
					$num = $db->num_rows($resqlcontent);
					$j = 0;
					if ($num) {
						print '<div class="lotcontent">';
						while ($j < $num) {
							$obj = $db->fetch_object($resqlcontent);
							if ($obj->ref) {
								$titleequipement="";
								if ($obj->unitweight)
									$titleequipement= $langs->trans("UnitWeight").' : '.$obj->unitweight."\n";
								print "<div style='float: left;background:#E0E0E0;margin:2px;padding:2px;'";
								print " title='".$titleequipement."'>";
								print "<input type=checkbox id='".$obj->ref."' name='chkequipement[".$lineid."-".$obj->rowid."]' value='".$obj->rowid."'>";
								print "&nbsp;".$obj->ref."&nbsp;&nbsp;</div>";
								if (($j+1) % 10 == 0 ) print '<br>';
							} else
								break;
							$j++;
						}
						print '</div></td></tr>';
					}
				}
				$i++;
			}
			print '</table>';
		}
	}
}

function select_projects($selected='', $filtersoc='', $htmlname='projectid', $showempty=0, $hidetext=0)
{
	global $db, $langs;  //, $user, $conf;

	if (empty($hidetext)) print $langs->trans("project").': ';

	// boucle sur les interventions valides
	$sql = "SELECT rowid, fk_soc, ref "; //, dateo, datee";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet";
	$sql.= " WHERE fk_statut = 1";
	// si une soci�t� est s�lectionn�
	// ou si il s'agit d'un projet s�lectionn� sur une autre soci�t� (historique)
	if ($filtersoc) {
		$sql.=" and ( fk_soc=".$filtersoc;
		if ($selected)
			$sql.=" or rowid=".$selected.")";
		else
			$sql.=" )";
	}

	$sql.= " ORDER BY ref DESC";

	dol_syslog("Equipement.Lib::select_projects sql=".$sql);
	$resql=$db->query($sql);

	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->ref." </option>";
				$i++;
			}
			print '</select>';
		}
		else
			print $langs->trans("NoProjectsFind");
	}
}