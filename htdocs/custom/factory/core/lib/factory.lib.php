<?php
/* Copyright (C) 2014-2016 Charlie BENKE  <charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *		\file	   /factory/lib/factory.lib.php
 *		\brief	  Ensemble de fonctions de base pour le module factory
 *	  \ingroup	factory
 */

function factory_admin_prepare_head()
{
	global $langs, $conf;
	$langs->load('factory@factory');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/factory/admin/factory.php", 1);
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';
	$h++;

	$head[$h][0] = dol_buildpath("/factory/admin/factory_extrafields.php", 1);
	$head[$h][1] = $langs->trans("Extrafields");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dol_buildpath("/factory/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'factory_admin');

	return $head;
}

function factory_product_prepare_head($object, $user=0)
{
	global $langs, $conf;
	$langs->load('factory@factory');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/factory/product/index.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("Composition");
	$head[$h][2] = 'composition';
	$h++;
	$head[$h][0] = dol_buildpath("/factory/product/direct.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("DirectBuild");
	$head[$h][2] = 'directbuild';
	$h++;

	$head[$h][0] = dol_buildpath("/factory/product/fiche.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("OrderBuild");
	$head[$h][2] = 'neworderbuild';
	$h++;

	$head[$h][0] = dol_buildpath("/factory/product/list.php?fk_status=1&id=".$object->id, 1);
	$head[$h][1] = $langs->trans("OrderBuildList");
	$head[$h][2] = 'orderbuildlist';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'factory_product');

	$head[$h][0] = dol_buildpath("/factory/product/list.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("OrderBuildHistory");
	$head[$h][2] = 'orderbuildhistory';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab


	return $head;
}

function factory_prepare_head($object, $user=0)
{
	global $langs, $conf;
	$langs->load('factory@factory');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/factory/fiche.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("FactoryOrder");
	$head[$h][2] = 'factoryorder';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		$head[$h][0] = dol_buildpath("/factory/contact.php?id=".$object->id, 1);
		$head[$h][1] = $langs->trans("Contacts");
		$head[$h][2] = 'contact';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/factory/report.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("FactoryReport");
	$head[$h][2] = 'factoryreport';
	$h++;

	$head[$h][0] = dol_buildpath("/factory/movement.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("FactoryMovement");
	$head[$h][2] = 'factorymovement';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->factory->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview\.png)$'));

	$head[$h][0] = dol_buildpath("/factory/documents.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("Documents");
	if ($nbFiles > 0) $head[$h][1].= ' <span class="badge">'.$nbFiles.'</span>';
	$head[$h][2] = 'document';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath("/factory/note.php?id=".$object->id, 1);
		$head[$h][1] = $langs->trans("Notes");
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
		$head[$h][2] = 'notes';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'factory');

	$head[$h][0] = dol_buildpath("/factory/info.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("Infos");
	$head[$h][2] = 'infos';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab


	return $head;
}


/**
 *	Return list of entrepot (for the stock
 *
 *	@param  string	$selected	   Preselected type
 *	@param  string	$htmlname	   Name of field in html form
 * 	@param	int		$showempty		Add an empty field
 * 	@param	int		$hidetext		Do not show label before combo box
 * 	@param	int		$idproduct		display the Qty of product id if
 *  @return	void
 */
function select_entrepot_list($selected='', $htmlname='entrepotid', $showempty=0, $hidetext=0, $idproduct=0)
{
	global $db, $langs; //, $user, $conf;

	$res= "";

	if (empty($hidetext)) $res= $langs->trans("EntrepotStock").': ';

	// boucle sur les entrepots
	$sql = "SELECT rowid, label, zip";
	$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
	//$sql.= " WHERE statut = 1";
	$sql.= " ORDER BY zip, rowid ASC";

	dol_syslog("factory.lib::select_entrepot_list sql=".$sql);

	$resql=$db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			$res.='<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'">';
			if ($showempty) {
				$res.='<option value="-1"';
				if ($selected == -1) $res.=' selected="selected"';
				$res.='>&nbsp;</option>';
			}
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$qtereel=0;
				$sql="select ps.reel FROM ".MAIN_DB_PREFIX."product_stock as ps";
				$sql.= " WHERE ps.fk_product = ".$idproduct;
				$sql.= " AND ps.fk_entrepot = ".$obj->rowid;
				$resreel=$db->query($sql);
				if ($resreel) {
					$objreel = $db->fetch_object($resreel);
					$qtereel=($objreel->reel?$objreel->reel:0);
				}
				$res.='<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) $res.=' selected="selected"';
				$res.=">".$obj->label." (".$qtereel.")</option>";
				$i++;
			}
			$res.='</select>';
		} else {
			// si pas de liste, on positionne un hidden à vide
			$res.='<input type="hidden" name="'.$htmlname.'" value=-1>';
		}
	}
	return $res;
}