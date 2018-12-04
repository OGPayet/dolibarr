<?php
/* Copyright (C) 2012-2017	Charlene BENKE	<charlie@patas-monkey.com>
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
 *	\file		htdocs/equipement/corret_ref_and_links_preprod.php
 *	\brief		Fichier fiche equipement
 *	\ingroup	equipement
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php";
require_once DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php";
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/equipement/core/modules/equipement/modules_equipement.php');
dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

// upadte ref equipement
//$sql  = "UPDATE " . MAIN_DB_PREFIX . "equipement";
//$sql .= " SET ref = TRIM(ref)";
//$db->query($sql);

// equipemnt event
$sql  = "SELECT ee.rowid, ee.description";
$sql .= " FROM " . MAIN_DB_PREFIX . "equipementevt as ee";

$resql = $db->query($sql);
if ($resql) {
	// upate equipement event
    while ($obj = $db->fetch_object($resql)) {
        $equipementEvtDescription = $obj->description;

        $descriptionPatternArray = array(
		'#img\ssrc="/synergies-tech/custom/equipement/img/object_equipement.png"#',
		'#a\shref="/synergies-tech/custom/equipement/card.php#',
		'#img\ssrc="/synergies-tech/theme/eldy/img/object_product.png"#',
		'#a\shref="/synergies-tech/product/card.php#'
        );
        $descriptionReplacementArray = array(
		'img src="/custom/equipement/img/object_equipement.png"',
		'a href="/custom/equipement/card.php',
		'img src="/theme/eldy/img/object_product.png"',
		'a href="/product/card.php'
        );
        $equipementEvtDescriptionReplace = preg_replace($descriptionPatternArray, $descriptionReplacementArray, $equipementEvtDescription);

        if (strlen($equipementEvtDescriptionReplace) != strlen($equipementEvtDescription)) {
            $sqlUpdate  = "UPDATE " . MAIN_DB_PREFIX . "equipementevt";
            $sqlUpdate .= " SET description = '" . $db->escape($equipementEvtDescriptionReplace) . "'";
            $sqlUpdate .= " WHERE rowid = " . $obj->rowid;

            $db->query($sqlUpdate);
        }
    }
    $db->free($resql);
}

$db->close();
