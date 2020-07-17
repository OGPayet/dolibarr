<?php
/* Copyright (C) 2014-2017	Charlie Benke	 <charles.fr@benke.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file	   htdocs/equipement/tabs/fichinterAdd.php
 *	\brief	  List of Equipement for join Events with a fichinter
 *	\ingroup	equipement
 */
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php";
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

if (! empty($conf->global->EQUIPEMENT_ADDON)
	&& is_readable(dol_buildpath("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php")))
	dol_include_once("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php");


$langs->load("equipement@equipement");
$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id=GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
// Security check
if ($user->societe_id) $socid=$user->societe_id;

/* ----- OpenDSI - Access rights on equipements in supplier order - Begin ----- */
//$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');
/* ----- OpenDSI - Access rights on equipements in supplier order - End ----- */


$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="e.datec";

$limit = $conf->liste_limit;

$search_ref=GETPOST('search_ref', 'alpha');
$search_refProduct=GETPOST('search_refProduct', 'alpha');
$search_company_fourn=GETPOST('search_company_fourn', 'alpha');
$search_company_client=GETPOST('search_company_client', 'alpha');
$search_entrepot=GETPOST('search_entrepot', 'alpha');

$search_equipevttype=GETPOST('search_equipevttype', 'alpha');
if ($search_equipevttype=="-1") $search_equipevttype="";


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('tab_supplier_order'));

$object = new CommandeFournisseur($db);
$result = $object->fetch($id);
$object->fetch_thirdparty();


// List of lines to serialize
$dispatched_sql = "SELECT p.ref, p.label, p.description, p.fk_product_type, SUM(IFNULL(eq.quantity, 0)) as nb_serialized,";
$dispatched_sql .= " e.rowid as warehouse_id, e.label as entrepot,";
$dispatched_sql .= " cfd.rowid as dispatchlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status";
$dispatched_sql .= " FROM " . MAIN_DB_PREFIX . "product as p,";
$dispatched_sql .= " " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
$dispatched_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS eq ON eq.fk_commande_fournisseur_dispatch = cfd.rowid";
$dispatched_sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e ON cfd.fk_entrepot = e.rowid";
$dispatched_sql .= " WHERE cfd.fk_commande = " . $object->id;
$dispatched_sql .= " AND cfd.fk_product = p.rowid";
$dispatched_sql .= " GROUP BY cfd.rowid";
$dispatched_sql .= " ORDER BY cfd.rowid ASC";
// modified by hook
$parameters = array();
$reshook = $hookmanager->executeHooks('sqlLinesToSerialize', $parameters, $object, $action);
if (!empty($hookmanager->resPrint)) $dispatched_sql = $hookmanager->resPrint;

$nb_to_serialize = 0;
$dispatched_lines = array();
$resql = $db->query($dispatched_sql);
if ($resql) {
    while ($objp = $db->fetch_object($resql)) {
        $remains_serialized = $objp->qty - $objp->nb_serialized;
        $nb_to_serialize += ($remains_serialized > 0 ? $remains_serialized : 0);
        $dispatched_lines[] = $objp;
    }
}

if ($action == 'addequipement') {
    $error = 0;

    $db->begin();

    $objectequipement = new equipement($db);
    foreach ($dispatched_lines as $line) {
        // only recept on serial product
        if ($line->fk_product > 0) {
            // on regarde si il y a des equipements a creer (qty > O)
            $qty = GETPOST('quantity-' . $line->dispatchlineid, 'int');
            if (0 < $qty && $qty <= ($line->qty - $line->nb_serialized)) {
                $objectequipement->fk_product = $line->fk_product;
                $objectequipement->fk_soc_fourn = $object->thirdparty->id;
                //$objectequipement->fk_soc_client = $idMeteoOmnium;
                $objectequipement->author = $user->id;
                $objectequipement->description = $langs->trans("SupplierOrder") . ":" . $object->ref;
                //$objectequipement->ref = $ref;
                $objectequipement->fk_entrepot = GETPOST('fk_entrepot-' . $line->dispatchlineid, 'alpha');
                $objectequipement->fk_commande_fourn = $object->id;
                $objectequipement->fk_commande_fournisseur_dispatch = $line->dispatchlineid;
                $datee = dol_mktime(
                    '23', '59', '59',
                    $_POST["datee-" . $line->dispatchlineid . "month"],
                    $_POST["datee-" . $line->dispatchlineid . "day"],
                    $_POST["datee-" . $line->dispatchlineid . "year"]
                );
                $objectequipement->datee = $datee;
                $dateo = dol_mktime(
                    '23', '59', '59',
                    $_POST["dateo-" . $line->dispatchlineid . "month"],
                    $_POST["dateo-" . $line->dispatchlineid . "day"],
                    $_POST["dateo-" . $line->dispatchlineid . "year"]
                );
                $objectequipement->dateo = $dateo;

                $serialFournArray = GETPOST('SerialFourn-' . $line->dispatchlineid, 'array');

                $objectequipement->SerialMethod = GETPOST('SerialMethod-' . $line->dispatchlineid, 'int');
                $objectequipement->SerialFourn  = implode(';', $serialFournArray);
                $objectequipement->numversion   = GETPOST('numversion-' . $line->dispatchlineid, 'alpha');

                // selon le mode de serialisation de l'equipement
                switch (GETPOST('SerialMethod-' . $line->dispatchlineid, 'int')) {
                    case 1 : // en mode generation auto, on cree des numeros de series internes
                        $objectequipement->quantity = 1;
                        $objectequipement->nbAddEquipement = $qty;
                        break;
                    case 2 : // en mode generation a partir de la liste on determine en fonction de la saisie
                        $objectequipement->quantity = 1;
                        $objectequipement->nbAddEquipement = $qty; // sera calcule en fonction
                        break;
                    case 3 : // en mode gestion de lot
                        $objectequipement->quantity = $qty;
                        $objectequipement->nbAddEquipement = 1;
                        break;
                }

                $result = $objectequipement->create();

                if ($result < 0) {
                    $error++;
                }
            }
        }
    }

    // commit or rollback
    if ($error) {
        $db->rollback();

        setEventMessages($objectequipement->error, $objectequipement->errors, 'errors');
        //header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        // reload object
        $object = new CommandeFournisseur($db);
        $result = $object->fetch($id);
        $object->fetch_thirdparty();
    } else {
        $db->commit();

        setEventMessage($langs->trans("EquipementAdded"), 'mesgs');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
        exit();
    }

    $action = "";
}


/*
 *	View
 */

$mesg = '';

$form = new Form($db);
llxHeader();

$head = ordersupplier_prepare_head($object);
dol_fiche_head($head, 'equipement', $langs->trans("SupplierOrder"), 0, 'order');
dol_htmloutput_mesg($mesg);


print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
$linkback.= $langs->trans("BackToList").'</a>';

// Ref
print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
print '<td colspan="2">';
print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
print '</td>';
print '</tr>';

// Ref supplier
print '<tr><td width="20%">'.$langs->trans("RefSupplier").'</td>';
print '<td colspan="2">';
print $object->ref_supplier;
print '</td></tr>';

// Fournisseur
print '<tr><td>'.$langs->trans("Supplier")."</td>";
print '<td colspan="2">'.$object->thirdparty->getNomUrl(1, 'supplier').'</td>';
print '</tr>';

// Statut
print '<tr>';
print '<td>'.$langs->trans("Status").'</td>';
print '<td colspan="2">';
print $object->getLibStatut(4);
print "</td></tr>";
print "</table>";

dol_fiche_end();


print '<form name="equipement" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="addequipement">';
print '<input type="hidden" name="id" value="' . $id . '">';

print '<table id="tablelines" class="noborder noshadow" width="100%">';

print '<tr class="liste_titre">';
print '<td align="left" width="200">' . $langs->trans('Label') . '</td>';
print '<td align="right" width="75">' . $langs->trans('Qty') . '</td>';
print '<td align="center" width="150">' . $langs->trans('EquipmentSerialMethod') . '</td>';
print '<td align="left" width="250">' . $langs->trans('ExternalSerial') . '</td>';
//print '<td align="left" width="50">' . $langs->trans('Quantity') . '</td>';
print '<td align="left" width="100">' . $langs->trans('VersionNumber') . '</td>';
print '<td align="left" width="100">' . $langs->trans('EntrepotStock') . '</td>';
print '<td align="right" width="100">' . $langs->trans('Dateo') . '</td>';
print '<td align="right" width="100">' . $langs->trans('Datee') . '</td>';
print "</tr>\n";

$var = false;
if ($nb_to_serialize) {

    $outjs = '';

    foreach ($dispatched_lines as $line) {
        $remains_serialized = $line->qty - $line->nb_serialized;

        // only recept on serial product
        if ($line->fk_product > 0 && $remains_serialized > 0) {
            print "<tr " . $bc[$var] . ">";

            // Show product and description
            print '<td valign=top>';

            print '<input type=hidden name="fk_product[' . $line->dispatchlineid . ']" value="' . $line->fk_product . '">';
            $product_static = new ProductFournisseur($db);
            $product_static->fetch($line->fk_product);
            $text = $product_static->getNomUrl(1, 'supplier');
            if (!empty($product_static->label)) $text .= ' - ' . $product_static->label;
            $description = ($conf->global->PRODUIT_DESC_IN_FORM ? '' : dol_htmlentitiesbr($line->description));
            print $form->textwithtooltip($text, $description, 3, '', '', $i);

            // Show range
            print_date_range($date_start, $date_end);

            // Add description in form
            if (!empty($conf->global->PRODUIT_DESC_IN_FORM))
                print ($line->description && $line->description != $product_static->libelle) ? '<br>' . dol_htmlentitiesbr($line->description) : '';

            print '<td  valign=top align="right" class="nowrap">' . $line->qty . '</td>';

            // serial method
            $dispatchSerialMethod = GETPOST('SerialMethod-' . $line->dispatchlineid, 'int') ? GETPOST('SerialMethod-' . $line->dispatchlineid, 'int') : $conf->global->EQUIPEMENT_DEFAULTSERIALMODE;
            print '<td  valign=top align="center" >';
            $arraySerialMethod = array(
                '1' => $langs->trans("InternalSerial"),
                '2' => $langs->trans("ExternalSerial"),
                '3' => $langs->trans("SeriesMode")
            );
            print $form->selectarray("SerialMethod-" . $line->dispatchlineid, $arraySerialMethod, $dispatchSerialMethod);
            print '</td>';

            // serial fourn
            $dispatchSerialFournArray = GETPOST('SerialFourn-' . $line->dispatchlineid, 'array') ? GETPOST('SerialFourn-' . $line->dispatchlineid, 'array') : array();
            print '<td valign="top">';
            for ($i = 0; $i < $remains_serialized; $i++) {
                $dispatchSerialFourn = isset($dispatchSerialFournArray[$i]) ? $dispatchSerialFournArray[$i] : '';
                print '<input type="text" class="SerialFourn-' . $line->dispatchlineid . '" name="SerialFourn-' . $line->dispatchlineid . '[]" value="' . $dispatchSerialFourn . '" />';
            }
            print '</td>';

            // quantity
            //$dispatchQuantity = isset($_POST['quantity-' . $line->dispatchlineid]) ? GETPOST('quantity-' . $line->dispatchlineid, 'int') : $remains_serialized;
            //print '<td  valign=top><input type="number" name="quantity-' . $line->dispatchlineid . '" min="0" max="'.$remains_serialized.'" size="2" value="' . $dispatchQuantity . '"'.($remains_serialized > 0 ? '' : ' disabled') .'></td>';
            print '<input type="hidden" name="quantity-' . $line->dispatchlineid . '" value="' . $remains_serialized . '"' . ($remains_serialized > 0 ? '' : ' disabled') . ' />';

            // num version
            $dispatchNumversion = GETPOST('numversion-' . $line->dispatchlineid, 'alpha') ? GETPOST('numversion-' . $line->dispatchlineid, 'alpha') : '';
            print '<td  valign=top><input type="text" name="numversion-' . $line->dispatchlineid . '" value="' . $dispatchNumversion .'"></td>';

            // entrepot
            //$dispatchWarehouse = GETPOST('fk_entrepot-' . $line->dispatchlineid, 'alpha') ? GETPOST('fk_entrepot-' . $line->dispatchlineid, 'alpha') : $line->warehouse_id;
            print '<td  valign=top>';
            print '<input type="hidden" name="fk_entrepot-' . $line->dispatchlineid . '" value="' . $line->warehouse_id . '" />';
            select_entrepot($line->warehouse_id, 'fk_entrepot-' . $line->dispatchlineid, 1, 1, 0, 0, '', 1, $line->fk_product, '', 1);
            print '</td>';

            // Date open
            print '<td  valign=top align=right>';
            print $form->select_date(
                    '', 'dateo-' . $line->dispatchlineid, 0, 0, '', 'dateo[' . $line->dispatchlineid . ']'
                ) . '</td>' . "\n";

            // Date end
            print '<td  valign=top	align=right>';
            print $form->select_date(
                    '', 'datee-' . $line->dispatchlineid, 0, 0, 1, 'datee[' . $line->dispatchlineid . ']'
                ) . '</td>' . "\n";
            print '</tr>';

            $var = !$var;

            // disable input serial fourn for method internal and series
            $outjs .= 'if (jQuery("#SerialMethod-' . $line->dispatchlineid . '").val()!=2) {';
            $outjs .= ' jQuery(".SerialFourn-' . $line->dispatchlineid . '").prop("disabled", true);';
            $outjs .= '}';
            $outjs .= 'jQuery("#SerialMethod-' . $line->dispatchlineid . '").change(function(){';
            $outjs .= ' if (this.value!=2) {';
            $outjs .= '     jQuery(".SerialFourn-' . $line->dispatchlineid . '").prop("disabled", true);';
            $outjs .= ' } else {';
            $outjs .= ' jQuery(".SerialFourn-' . $line->dispatchlineid . '").prop("disabled", false);';
            $outjs .= ' }';
            $outjs .= '});';
        }
    }

    if (!empty($outjs)) {
        print '<script type="text/javascript">';
        print 'jQuery(document).ready(function(){';
        print $outjs;
        print '});';
        print '</script>';
    }

    print <<<SCRIPT
    <script>
        $(document).ready(function(){
            function check_quantity(object) {
                var val = parseFloat(object.val());
                var min = parseFloat(object.attr('min'));
                var max = parseFloat(object.attr('max'));

                if (val <= min) object.val(min);
                else if (val > max) object.val(max);
            }
            $('input[name^="quantity-"]').on('input', function() {
                check_quantity($(this));
            })
        });
    </script>
SCRIPT;

    $db->free($resql);
} else {
    print "<tr " . $bc[$var] . ">";
    print '<td align="left" colspan="9">' . $langs->trans('EquipmentNoMoreProductToSerialize') . '</td>';
    print "</tr>\n";
}
print '</table>';

if ($nb_to_serialize) {
    // Button
    print '<div class="tabsAction">';
    print '<input type="submit" class="button" value="' . $langs->trans("AddEquipement") . '">';
    print '</div>';
}
print '</form>';
print '<br>';


$sql = "SELECT";
$sql.= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.label,";
$sql.= " e.unitweight, e.quantity,";
$sql.= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_etatequipement, et.libelle as etatequiplibelle,";
$sql.= " ee.rowid as eerowid, ee.datee, ee.dateo, eet.libelle as equipevttypelibelle, ee.fk_equipementevt_type,";
$sql.= " ee.fk_fichinter, fi.ref as reffichinter, ee.fk_contrat, co.ref as refcontrat,";
$sql.= " ee.fk_expedition, exp.ref as refexpedition ";

$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as et on e.fk_etatequipement = et.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt as ee ON e.rowid=ee.fk_equipement";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter as fi on ee.fk_fichinter = fi.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as co on ee.fk_contrat = co.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as exp on ee.fk_expedition = exp.rowid";

$sql.= ' WHERE e.entity IN (' . getEntity('equipement') . ')';
$sql.= " and e.fk_commande_fourn=".$id;

if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)		$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
if ($search_entrepot)		$sql .= " AND ent.label like '%".$db->escape($search_entrepot)."%'";
if ($search_company_client)	$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;
if ($search_equipevttype)	$sql .= " AND ee.fk_equipementevt_type =".$search_equipevttype;

$sql.= " ORDER BY ".$sortfield." ".$sortorder;
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$equipementstatic=new Equipement($db);

	$urlparam="&amp;id=".$id;
	if ($search_ref)				$urlparam .= "&amp;search_ref=".$db->escape($search_ref);
	if ($search_refProduct)			$urlparam .= "&amp;search_refProduct=".$db->escape($search_refProduct);
	if ($search_entrepot)			$urlparam .= "&amp;search_entrepot=".$db->escape($search_entrepot);
	if ($search_company_client)		$urlparam .= "&amp;search_company_client=".$db->escape($search_company_client);
	if ($search_etatequipement>=0)	$urlparam .= "&amp;search_etatequipement=".$search_etatequipement;
	if ($search_equipevttype>=0)	$urlparam .= "&amp;search_equipevttype=".$search_equipevttype;

	print_barre_liste(
					$langs->trans("ListOfEquipements").' ('.$num.')', $page, "expedition.php",
					$urlparam, $sortfield, $sortorder, '', $num
	);

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
	print '<table class="noborder" width="100%">';

	print "<tr class='liste_titre'>";
	print_liste_field_titre(
					$langs->trans("Ref"), $_SERVER["PHP_SELF"], "e.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("RefProduit"), $_SERVER["PHP_SELF"], "p.ref",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre(
					$langs->trans("Entrepot"), $_SERVER["PHP_SELF"], "ent.label",
					"", $urlparam, '', $sortfield, $sortorder
	);
	print_liste_field_titre($langs->trans("Dateo"), $_SERVER["PHP_SELF"], "e.dateo", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Datee"), $_SERVER["PHP_SELF"], "e.datee", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("UnitWeight"), $_SERVER["PHP_SELF"], "e.unitweight", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Contrat"), $_SERVER["PHP_SELF"], "co.ref", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Expedition"), $_SERVER["PHP_SELF"], "exp.ref", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("EtatEquip"), $_SERVER["PHP_SELF"], "e.fk_equipementetat", "", $urlparam, '', $sortfield, $sortorder);
	print_liste_field_titre(
					$langs->trans("TypeofEquipementEvent"), $_SERVER["PHP_SELF"], "ee.fk_equipementevt_type",
					"", $urlparam, ' colspan=2 ', $sortfield, $sortorder
	);
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8"></td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_entrepot" value="'.$search_entrepot.'" size="10"></td>';


	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdatee" value="'.$monthdatee.'">';
	$syear = $yeardatee;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardatee" value="'.$syear.'">';
	print '</td>';

	print '<td class="liste_titre" colspan="1" align="right">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
	$syear = $yeardateo;
	if ($syear == '') $syear = date("Y");
	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardateo" value="'.$syear.'">';
	print '</td>';
	print '<td></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refcontrat" value="'.$search_refcontrat.'" size="10"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" name="search_refexpedition" value="'.$search_refexpedition.'" size="10"></td>';

	// liste des �tat des �quipements
	print '<td class="liste_titre" align="right">';
	print select_equipement_etat($search_etatequipement, 'search_etatequipement', 1, 1);
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print select_equipementevt_type($search_equipevttype, 'search_equipevttype', 1, 1);
	print '</td><td>';
	print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";


	$var=True;
	$total = 0;
	$totalWeight=0;
	$i = 0;
	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print "<td>";
		$equipementstatic->id=$objp->rowid;
		$equipementstatic->ref=$objp->ref;
		print $equipementstatic->getNomUrl(1);
		print "</td>";

		print '<td>';
		if ($objp->fk_product) {
			$productstatic=new Product($db);
			$productstatic->fetch($objp->fk_product);
			print $productstatic->getNomUrl(1);
		}
		print '</td>';

		// entrepot

		print "<td>";
		if ($objp->fk_entrepot>0) {
			$entrepotstatic = new Entrepot($db);
			$entrepotstatic->fetch($objp->fk_entrepot);
			print $entrepotstatic->getNomUrl(1);
		}
		print '</td>';



		print '</td>';
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo), 'day')."</td>\n";
		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee), 'day')."</td>\n";

		print "<td nowrap align='right'>".price($objp->unitweight)."</td>\n";
		$totalWeight+= ($objp->unitweight*$objp->quantity);

		print "<td>";
		if ($objp->fk_contrat>0) {
			$contrat = new Contrat($db);
			$contrat->fetch($objp->fk_contrat);
			print $contrat->getNomUrl(1);
			if ($objp->fk_soc_client != $contrat->socid) {
				$soc = new Societe($db);
				$soc->fetch($contrat->socid);
				print "<br>".$soc->getNomUrl(1);
			}
		}
		print '</td>';

		print "<td>";
		if ($objp->fk_fichinter>0) {
			$fichinter = new Fichinter($db);
			$fichinter->fetch($objp->fk_fichinter);
			print $fichinter->getNomUrl(1);
			if ($objp->fk_soc_client != $fichinter->socid) {
				$soc = new Societe($db);
				$soc->fetch($fichinter->socid);
				print "<br>".$soc->getNomUrl(1);
			}
		}
		print '</td>';

		print '<td align="right">'.($objp->etatequiplibelle ? $langs->trans($objp->etatequiplibelle):'').'</td>';
		print '<td align="right">'.($objp->equipevttypelibelle ? $langs->trans($objp->equipevttypelibelle):'').'</td>';
		print '<td align="right">';
        print '</td>';
		print "</tr>\n";

		$i++;
	}
	print '<tr class="liste_total"><td colspan="4" align=right class="liste_total"><b>'.$langs->trans("Total").'</b></td>';
	print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td>';
	print '<td align="right" nowrap="nowrap" class="liste_total">'.price($totalWeight).'</td><td colspan=5>&nbsp;</td>';
	print '</tr>';

	print '</table>';
	print "</form>\n";
	$db->free($result);
} else
	dol_print_error($db);

llxFooter();
$db->close();