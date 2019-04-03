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
 *	\file		htdocs/equipement/card.php
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

if (! empty($conf->global->EQUIPEMENT_ADDON)
	&& is_readable(dol_buildpath("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php")))
	dol_include_once("/equipement/core/modules/equipement/".$conf->global->EQUIPEMENT_ADDON.".php");

$langs->load("companies");
$langs->load("products");
$langs->load("equipement@equipement");

$action				= GETPOST('action', 'alpha');
$id					= GETPOST('id', 'int');
$ref				= GETPOST('ref', 'alpha');
$productid			= GETPOST('productid', 'int');
$action				= GETPOST('action', 'alpha');
$confirm			= GETPOST('confirm', 'alpha');
$mesg				= GETPOST('msg', 'alpha');
$socFournid 		= GETPOST('fk_soc_fourn', 'alpha');
$socClienid 		= GETPOST('fk_soc_client', 'alpha');
$nbAddEquipement 	= GETPOST('nbAddEquipement', 'int');
$serialMethod		= GETPOST('serialMethod', 'int');
$fk_product_batch	= (GETPOST('fk_product_batch', 'int')?GETPOST('fk_product_batch', 'int'):0);
$dateendlot			= GETPOST('dateendlot');



// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');


$object = new Equipement($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label("equipement");

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('equipementcard', 'globalcard'));

$permissiondellink=$user->rights->equipement->creer;	// Used by the include of actions_dellink.inc.php



/*
 * Actions
 */
$parameters = array('id'=> $id, 'product' => $product);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook)) {
    include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';        // Must be include, not include_once

    if ($action == 'confirm_cutEquipement' && $confirm == 'yes' && $user->rights->equipement->creer) {
        if ($object->fetch($id, $ref)) {
            $ref_new = GETPOST("ref_new");
            $quantitynew = GETPOST("quantitynew");
            $cloneevent = GETPOST("cloneevent");
            $object->cut_equipement($ref_new, $quantitynew, $cloneevent);
            Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit;
        }
    } else if ($action == 'add' && $user->rights->equipement->creer) {
        $object->fk_product = $productid;
        $object->fk_soc_fourn = $socFournid;
        $object->fk_soc_client = $socClienid;
        $object->author = $user->id;
        $object->unitweight = GETPOST('unitweight', 'alpha');
        $object->description = GETPOST('description');
        $object->ref = $ref;
        $object->fk_entrepot = GETPOST('fk_entrepot', 'alpha');
        $object->isentrepotmove = (GETPOST('fk_entrepotmove', 'alpha') ? 1 : 0);
        $object->fk_etatequipement = GETPOST('fk_etatequipement');

        $datee = dol_mktime('23', '59', '59', $_POST["dateemonth"], $_POST["dateeday"], $_POST["dateeyear"]);
        $object->datee = $datee;

        $dated = dol_mktime('23', '59', '59', $_POST["datedmonth"], $_POST["datedday"], $_POST["datedyear"]);
        $object->dated = $dated;

        $dateo = dol_mktime('23', '59', '59', $_POST["dateomonth"], $_POST["dateoday"], $_POST["dateoyear"]);
        $object->dateo = $dateo;

        $object->note_private = GETPOST('note_private', 'alpha');
        $object->note_public = GETPOST('note_public', 'alpha');
        $object->quantity = GETPOST('quantity', 'int');
        $object->nbAddEquipement = $nbAddEquipement;
        $object->SerialMethod = $serialMethod;
        $object->SerialFourn = GETPOST('SerialFourn', 'alpha');
        $object->numversion = GETPOST('numversion', 'alpha');
        $object->model_pdf = GETPOST('modelpdf', 'alpha');
        $object->fk_factory = GETPOST('factoryid', 'int');
        $object->fk_product_batch = $fk_product_batch;

        //var_dump($object);
        //exit;

        if ($object->fk_product > 0) {
            $result = $object->create();
            if ($result > 0) {
                $id = $result;      // Force raffraichissement sur fiche venant d'etre cree
                $action = '';
            } else {
                $langs->load("errors");
                $mesg = '<div class="error">' . $langs->trans($object->error) . '</div>';
                $action = 'create';
            }
        } else {
            $mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("ThirdParty")) . '</div>';
            $action = 'create';
        }
    }

    if ($id > 0 || !empty($ref)) {
        $object->fetch($id, $ref);
        $object->fetch_thirdparty();
    }

    if ($action == 'confirm_validate'
        && $confirm == 'yes'
        && $user->rights->equipement->creer
    ) {
        $object->fetch_thirdparty();

        $result = $object->setValid($user);
        if ($result >= 0) {
            // Define output language
            $outputlangs = $langs;
            $newlang = '';
            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
                $newlang = GETPOST('lang_id', 'alpha');
            if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                $newlang = $object->thirdparty->default_lang;
            if (!empty($newlang)) {
                $outputlangs = new Translate("", $conf);
                $outputlangs->setDefaultLang($newlang);
            }
            // g�n�re le pdf qui n'existe pas pour le moment
            $result = equipement_create($db, $object, GETPOST('model', 'alpha'), $outputlangs);
            Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit;
        } else
            $mesg = '<div class="error">' . $object->error . '</div>';
    } else if ($action == 'confirm_modify' && $confirm == 'yes' && $user->rights->equipement->creer) {

        $result = $object->setDraft($user);
        if ($result >= 0) {
            // Define output language
            $outputlangs = $langs;
            $newlang = '';
            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
                $newlang = GETPOST('lang_id', 'alpha');
            if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                $newlang = $object->thirdparty->default_lang;
            if (!empty($newlang)) {
                $outputlangs = new Translate("", $conf);
                $outputlangs->setDefaultLang($newlang);
            }
            $result = equipement_create(
                $db, $object,
                (!GETPOST('model', 'alpha')) ? $object->model : GETPOST('model', 'apha'),
                $outputlangs
            );
            Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit;
        } else
            $mesg = '<div class="error">' . $object->error . '</div>';

    } elseif ($action == 'builddoc' && $user->rights->equipement->creer) {
        /*
         * Build doc
         */
        $object->fetch_lines();

        if (GETPOST('model', 'alpha'))
            $object->setDocModel($user, GETPOST('model', 'alpha'));


        // Define output language
        $outputlangs = $langs;
        $newlang = '';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
            $newlang = GETPOST('lang_id', 'alpha');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang))
            $newlang = $object->thirdparty->default_lang;
        if (!empty($newlang)) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($newlang);
        }
        $result = equipement_create($db, $object, GETPOST('model', 'alpha'), $outputlangs);
        if ($result <= 0) {
            dol_print_error($db, $result);
            exit;
        }
        $action = "";
    } elseif ($action == 'remove_file') {
        // Remove file in doc form
        if ($object->fetch($id, $ref)) {
            require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

            $object->fetch_thirdparty();

            $langs->load("other");
            $upload_dir = $conf->equipement->dir_output;
            $file = $upload_dir . '/' . GETPOST('file');
            $ret = dol_delete_file($file, 0, 0, 0, $object);
            if ($ret) {
                $mesg = $langs->trans("FileWasRemoved", GETPOST('file'));
                setEventMessage($mesg, 'mesgs');
                header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
                exit();
            }
        }
    } elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->equipement->supprimer) {
        $object->delete($user);

        Header('Location: list.php?leftmenu=equipement');
        exit;
    } elseif ($action == 'confirm_delete' && $confirm != 'yes' && $user->rights->equipement->supprimer)
        $action = '';
    elseif ((substr($action, 0, 7) == 'setExFi' || $action == 'update_extras') && $user->rights->equipement->creer) {
        $keyExFi = substr($action, 7);

        $res = $object->fetch_optionals($object->id, $extralabels);
        if ($ret < 0) {
            $error++;
            setEventMessage($object->errorsToString(), 'errors');
        }
        if (!$error) {
            if (substr($action, 0, 7) == 'setExFi') {
                $object->array_options["options_" . $keyExFi] = $_POST["options_" . $keyExFi];
            } else {
                // Fill array 'array_options' with data from update form
                $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
                $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
                if ($ret < 0) {
                    $error++;
                    //setEventMessage($extrafields->error, 'errors'); // already in setOptionalsFromPost
                }
            }
        }
        if (!$error) {
            $result = $object->insertExtraFields();
            if ($result < 0) {
                $error++;
                setEventMessage($object->errorsToString(), 'errors');
            }
        }
        if ($error) {
            if (substr($action, 0, 7) == 'setExFi') {
                $action = 'ExFi';
            } else {
                $action = 'edit_extras';
            }
        }
    } else if ($action == 'setnumref' && $user->rights->equipement->majserial) {
        $result = $object->set_numref($user, GETPOST('editnumref', 'alpha'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setnumversion' && $user->rights->equipement->creer) {
        $result = $object->set_numversion($user, GETPOST('numversion', 'alpha'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setquantity' && $user->rights->equipement->creer) {
        $result = $object->set_quantity($user, GETPOST('quantity', 'alpha'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setunitweight' && $user->rights->equipement->creer) {
        $result = $object->set_unitweight($user, GETPOST('unitweight', 'alpha'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setnumimmocompta' && $user->rights->equipement->creer) {
        $result = $object->set_numimmocompta($user, GETPOST('numimmocompta', 'alpha'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setentrepot' && $user->rights->equipement->creer) {
        $result = $object->set_entrepot($user, GETPOST('fk_entrepot', 'alpha'), (GETPOST('fk_entrepotmove', 'alpha') ? 1 : 0));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setdescription' && $user->rights->equipement->creer) {
        $result = $object->set_description($user, GETPOST('description'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setnote_public' && $user->rights->equipement->creer) {
        $result = $object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setnote_private' && $user->rights->equipement->creer) {
        $result = $object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setetatequip' && $user->rights->equipement->creer) {
        $result = $object->set_etatEquipement($user, GETPOST('fk_etatequipement'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setclient' && $user->rights->equipement->creer) {
        if (GETPOST("emptyclient"))
            $result = $object->set_client($user, 0);
        else
            $result = $object->set_client($user, GETPOST('fk_soc_client'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setfactclient' && $user->rights->equipement->creer) {
        $result = $object->set_fact_client($user, GETPOST('fk_fact_client'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setfactfourn' && $user->rights->equipement->creer) {
        $result = $object->set_fact_fourn($user, GETPOST('fk_facture_fourn'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setcommandefourn' && $user->rights->equipement->creer) {
        $result = $object->set_commande_fourn($user, GETPOST('fk_commande_fourn'));
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setdatee') {
        $datee = dol_mktime('23', '59', '59', $_POST["dateemonth"], $_POST["dateeday"], $_POST["dateeyear"]);
        $result = $object->set_datee($user, $datee);
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setdated') {
        $dated = dol_mktime('23', '59', '59', $_POST["datedmonth"], $_POST["datedday"], $_POST["datedyear"]);
        $result = $object->set_dated($user, $dated);
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    } else if ($action == 'setdateo') {
        $dateo = dol_mktime('23', '59', '59', $_POST["dateomonth"], $_POST["dateoday"], $_POST["dateoyear"]);
        $result = $object->set_dateo($user, $dateo);
        if ($result < 0) dol_print_error($db, $object->error);
        $action = "";
    }
}


/*
 * View
 */
$extralabels=$extrafields->fetch_name_optionals_label('equipement');

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader();


if ($action == 'create') {
	/*
	 * Mode creation
	 * Creation d'un nouvel �quipement
	 */

	$prod=new Product($db);

	dol_htmloutput_mesg($mesg);

	if ( $conf->global->EQUIPEMENT_ADDON == "") {
		dol_print_error($db, $langs->trans("Error")." ".$langs->trans("Error_EQUIPEMENT_ADDON_NotDefined"));
		Print $langs->trans("EQUIPEMENTADDONNotDefined");
		exit;
	}
	$object->date = dol_now();

	$obj = $conf->global->EQUIPEMENT_ADDON;
	//$obj = "mod_".$obj;



	$modequipement = new $obj;
	$numpr = $modequipement->getNextValue($soc, $object);

	// si le produit est s�lectionn�
	if ($productid > 0) {
		$prod->fetch($productid);

		// si le num�ro de lot est actif et pas de lot encore s�lectionn�
		if ($conf->productbatch->enabled && $fk_product_batch == 0) {
			$lstproductbatch = array();
			$sql = "SELECT pb.rowid, pb.tms, pb.fk_product_stock,";
			$sql.= " pb.sellby, pb.eatby, pb.batch, pb.qty,";
			$sql.= " ps.fk_entrepot";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_batch as pb";
			$sql.= " ,".MAIN_DB_PREFIX."product_stock as ps";
			$sql.= " WHERE pb.fk_product_stock=ps.rowid";
			$sql.= " AND ps.fk_product=".$productid;
			$sql.= " AND qty <> 0";

			dol_syslog("productbatch::findAll", LOG_DEBUG);
			$resql=$db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i=0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					$tmp['fk_product_stock'] = $obj->fk_product_stock;
					$tmp['sellby'] = $db->jdate($obj->sellby);
					$tmp['eatby'] = $db->jdate($obj->eatby);
					$tmp['batch'] = $obj->batch;
					$tmp['qty'] = $obj->qty;
					$tmp['import_key'] = $obj->import_key;
					$tmp['fk_entrepot'] = $obj->fk_entrepot;
					$lstproductbatch[$obj->rowid]= $tmp;
					$i++;
				}
			}
			// si pas de num�ro de lot dispo on passe directement � la suite
			if (count($lstproductbatch) == 0)
				$fk_product_batch = -1;
			else {
				print_fiche_titre($langs->trans("AddEquipementSelectBatchLot"));

				$prod=new Product($db);
				$entrepot=new Entrepot($db);
				// sinon on demande la s�lection du product batch
				print '<form name="equipement" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="create">';
				print '<input type="hidden" name="serialMethod" value='.GETPOST('serialMethod').'>';
				print '<input type="hidden" name="productid" value='.$productid.'>';
				print '<input type="hidden" name="factoryid" value='.GETPOST('factoryid').'>';

				print '<table class="border" width="100%">';
				print '<tr class="liste_titre">';
				print '<th class="liste_titre" width=100px>'.$langs->trans("Warehouse").'</td>';
				print '<th class="liste_titre" width=100px>'.$langs->trans("Product").'</td>';
				print '<th class="liste_titre" width=100px>'.$langs->trans("BatchLabel").'</td>';
				print '<th class="liste_titre" width=80px>'.$langs->trans("DateEatBy").'</td>';
				print '<th class="liste_titre" width=80px>'.$langs->trans("DateSellBy").'</td>';
				print '<th class="liste_titre" align=right width=50px>'.$langs->trans("Qty").'</td>';
				print '<th class="liste_titre" width=20px></td>';

				print "</tr>\n";

				foreach ($lstproductbatch as $key => $value) {
					print '<tr>';
					$entrepot->fetch($value['fk_entrepot']);
					print '<td>'.$entrepot->getNomUrl(2).'</td>';
					$prod->fetch($productid);
					print '<td>'.$prod->getNomUrl(2).'</td>';
					print '<td>'.$value['batch'].'</td>';

					print '<td>'.dol_print_date($value['eatby'], 'day').'</td>';
					print '<td>'.dol_print_date($value['sellby'], 'day').'</td>';
					print '<td align=right>'.$value['qty'].'</td>';
					print '<td align=center><input type=radio name="fk_product_batch" value="'.$key.'"></td>';
					print '</tr>';
				}
				print '<tr>';
				print '<td colspan=6 align=right>'.$langs->trans("NoSelectBatchLot").'</td>';
				print '<td align=center><input type=radio name="fk_product_batch" value="-1" checked=true></td>';
				print '</tr>';

				print '<tr>';
				print '<td align=right colspan=4>'.$langs->trans("DateEndSelected").'</td>';
				print '<td  align=center>';
				$arraydateendequipement=array(	'eatby'=>$langs->trans("DateEatBy"),
												'sellby'=>$langs->trans("DateSellBy"));
				print $form->selectarray('dateendlot', $arraydateendequipement, "");
				print '</td><td align=center colspan=2>';
				print '<input type=submit name="Save" value="'.$langs->trans("Save").'">';
				print '</td></tr>';

				print '</table>';
				print '</form>';
			}

		}
		// si pas de batch/lot ou un lot a �t� s�lectionn� ou pas
		if ( empty($conf->productbatch->enabled) || $conf->productbatch->enabled && $fk_product_batch <> 0) {
			print_fiche_titre($langs->trans("AddEquipement"));

			print '<form name="equipement" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="serialMethod" value='.GETPOST('serialMethod').'>';
			print '<input type="hidden" name="productid" value='.$prod->id.'>';
			print '<input type="hidden" name="factoryid" value='.GETPOST('factoryid').'>';
			print '<input type="hidden" name="fk_product_batch" value='.GETPOST('fk_product_batch').'>';

			print '<table class="border" width="100%">';

			print '<tr><td class="fieldrequired">';
			print '<table class="nobordernopadding" width="100%">';
			print '<tr><td >'.$langs->trans("Product").'</td><td align=right>';
			print "<a href=# onclick=\"$('#descprod').toggle();\" >".img_picto("", "edit_add")."</a>";
			print '</td></tr></table></td><td colspan="3">';
			print $prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';
			print "<tr style='display:none' id='descprod'>";
			print '<td colspan=2>'.$prod->description.'</td><tr>';
			if ($fk_product_batch > 0) {
				$sql = "SELECT pb.rowid, pb.sellby, pb.eatby,";
				$sql.= " pb.batch, pb.qty, ps.fk_entrepot";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_batch as pb";
				$sql.= " ,".MAIN_DB_PREFIX."product_stock as ps";
				$sql.= " WHERE pb.fk_product_stock=ps.rowid";
				$sql.= " AND pb.rowid=".$fk_product_batch;

				dol_syslog("card::addequiment", LOG_DEBUG);
				$resql=$db->query($sql);
				if ($resql) {
					if ($db->num_rows($resql)) {
						$obj = $db->fetch_object($resql);
						$numversion=$obj->batch;
						$quantity=$obj->qty;
						$datee=$obj->$dateendlot;
						$fk_entrepot=$obj->fk_entrepot;
						switch(GETPOST('serialMethod')) {
							case 1 : // Internal Serial
								$nbAddEquipement = $obj->qty;
								$quantity = 1;
								break;

							case 2 : // External Serial
								$nbAddEquipement = 1;
								$quantity = 1;
								break;

							case 3 : // Series Mode
								$quantity = $obj->qty;
								$nbAddEquipement = 1;
								break;
						}
					}
				}
			} else {
				$numversion=GETPOST('numversion');
				$fk_entrepot=GETPOST('fk_entrepot');
				$datee=GETPOST('datee');
				$dated=GETPOST('dated');
				switch(GETPOST('serialMethod')) {
					case 1 : // Internal Serial
						$nbAddEquipement = (GETPOST("qtyEquipement")?GETPOST("qtyEquipement") :"1");
						$quantity = 1;
						break;

					case 2 : // External Serial
						$nbAddEquipement = 1;
						$quantity = 1;
						break;

					case 3 : // Series Mode
						$quantity = (GETPOST("qtyEquipement")?GETPOST("qtyEquipement") :"1");
						$nbAddEquipement = 1;
						break;
				}
			}


			print '<tr><td '.(GETPOST('serialMethod')==3?' class="fieldrequired" ':'').' >';
			print $langs->trans("VersionNumber").'</td>';
			print '<td>';
			if ($fk_product_batch > 0)
				print '<input name="numversion" readonly STYLE="background-color: #D0D0D0;" value="'.$numversion.'">';
			else
				print '<input name="numversion" value="'.$numversion.'">';
			print '</td></tr>';
			// si produit a des fournisseurs
			$lstSupplier=$prod->list_suppliers();
			if (count($lstSupplier)) {
				print '<tr><td >'.$langs->trans("Fournisseur").'</td><td>';
				// si un seul fournisseur on l'affiche sans choix possible
				if (count($lstSupplier) ==1) {
					print '<input type="hidden" name="fk_soc_fourn" value='.$lstSupplier[0].'>';
					$soc=new Societe($db);
					$soc->fetch($lstSupplier[0]);
					print $soc->getNomUrl(1);
				} else {
					//sinon c'est une liste de s�lection
					$filterList="";
					foreach ($lstSupplier as &$arr) {
						$filterList.=" s.rowid=".$arr." OR";
					}
					//on vire le dernier 'or' et on rajoute la parenth�se de fin
					$filterList= "( ".substr($filterList, 0, -2).")";
					print $form->select_company('', 'fk_soc_fourn', $filterList, 1);
				}
				print '</td></tr>';
			}

			switch(GETPOST('serialMethod')) {
				case 1 : // Internal Serial
					// Si c'est un produit interne on g�n�re nous-m�me les num�ros de s�rie
					print '<tr><td class="fieldrequired">'.$langs->trans("RefStart").'</td>';
					print '<td><input type=text name="ref" value="'.$numpr.'" readonly STYLE="background-color: #D0D0D0;" >';
					print '</td></tr>'."\n";
					print '<tr><td class="fieldrequired">'.$langs->trans("nbEquipementToCreate").'</td>';
					if ($fk_product_batch > 0)
						print '<td >'.'<input type=hidden name="nbAddEquipement" value="'.$nbAddEquipement.'">'.$nbAddEquipement;
					else
						print '<td >'.'<input type=text name="nbAddEquipement" value="'.$nbAddEquipement.'">';
					print '<input type=hidden name="quantity" value="1">';
					print "</td></tr>\n";
					break;

				case 2 : // External Serial
					print '<tr><td class="fieldrequired">';
					print $form->textwithpicto(
									$langs->trans("ExternalSerial"),
									$langs->trans("YouCanAddMultipleSerialWithSeparator"),
									1
					).'</td>';
					print '<td>';
					print '<textarea name="SerialFourn" cols="80" rows="'.ROWS_3.'"></textarea>';
					print '<input type=hidden name="quantity" value="1">';
					print '</td></tr>'."\n";
					break;

				case 3 : // Series Mode
					print '<tr><td class="fieldrequired">'.$langs->trans("Quantity").'</td>';
					if ($fk_product_batch > 0)
						print '<td><input name="quantity" readonly STYLE="background-color: #D0D0D0;" size=2 value="'.$quantity.'">';
					else
						print '<td >'.'<input type=text name="quantity" size=2  value="'.$quantity.'">';
					print '<input type=hidden name="nbAddEquipement" value="1">';
					print '</td></tr>'."\n";
					break;

				default :
					print '<tr><td class="fieldrequired">'.$langs->trans("Quantity").'</td>';
					print '<td >'.$langs->trans("SerialMethodError")."=".GETPOST('serialMethod');
					print '</td></tr>'."\n";
					break;

			}

			// poid du produit
			print '<tr><td >'.$langs->trans("UnitWeight").'</td>';
			print '<td><input type=text size=4 name="unitweight" value="">';
			print '</td></tr>'."\n";

			// l'entrepot d'affectation est saisissable par d�faut, sauf en mode product batch
			print '<tr><td >'.$langs->trans("EntrepotStock").'</td><td>';
			if ($fk_product_batch > 0) {
				$entrepotStatic=new Entrepot($db);
				$entrepotStatic->fetch($fk_entrepot);
				print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
				print '<input type=hidden name="fk_entrepot" value="'.$fk_entrepot.'">';
			} else {
				// si on vient d'un of, on ne cr�e pas de mvt de stock
				if (substr($numversion, 0, 2) == "OF") {
					select_entrepot(GETPOST('fk_entrepot'), 'fk_entrepot', 1, 1, 0, 0);
					print "&nbsp;".$langs->trans("StockAlreadyMovedOnFactory");
				} else
					select_entrepot(GETPOST('fk_entrepot'), 'fk_entrepot', 1, 1, 0, 1);
			}
			print '</td></tr>'."\n";

			// le client est saisissable aussi � la cr�ation pour g�rer le pb des acc�s limit�
			print '<tr><td >'.$langs->trans("Client").'</td><td>';
			print $form->select_company($object->fk_soc_client, 'fk_soc_client', '', 1);
			print '</td></tr>'."\n";

			// Date open
			print '<tr><td>'.$langs->trans("Dateo").'</td><td>';
			print $form->select_date($object->dateo, 'dateo', 0, 0, '', "dateo");
			print '</td></tr>'."\n";

			if ($conf->global->EQUIPEMENT_USEDLUODATE == 1) {
				// Date DLUO
				print '<tr><td>'.$langs->trans("DateDluo").'</td><td>';
				print $form->select_date($object->dated, 'dated', 0, 0, '', "dated");
				print '</td></tr>'."\n";
			} else
				print '<input type=hidden name=dated value="">';

			// Date end
			print '<tr><td>'.$langs->trans("Datee").'</td><td>';
			if ($fk_product_batch > 0) {
				print '<input type=hidden name=dateeday value="'.substr($datee, 8, 2).'">';
				print '<input type=hidden name=dateemonth value="'.substr($datee, 5, 2).'">';
				print '<input type=hidden name=dateeyear value="'.substr($datee, 0, 4).'">';

				print '<input type=text size=9 name="datee" readonly STYLE="background-color: #D0D0D0;" value="';
				print dol_print_date($datee, 'day').'">';
			} else
				print $form->select_date('', 'datee', 0, 0, 1, "datee");
			print '</td></tr>'."\n";

			// Description (must be a textarea and not html must be allowed (used in list view)
			print '<tr><td valign="top">'.$langs->trans("Description").'</td>';
			print '<td>';
			print '<textarea name="description" cols="80" rows="'.ROWS_3.'"></textarea>';
			print '</td></tr>';

			// Model
			print '<tr>';
			print '<td>'.$langs->trans("DefaultModel").'</td>';
			print '<td colspan="2">';
			$liste=ModeleEquipement::liste_modeles($db);
			print $form->selectarray('model', $liste, $conf->global->EQUIPEMENT_ADDON_PDF);
			print "</td></tr>";

			// Etat de l'�quipement
			print '<tr><td>'.$langs->trans("EtatEquip").'</td>';
			print select_equipement_etat("", 'fk_etatequipement', 1, 1);
			print "</td></tr>";

			// Public note
			print '<tr>';
			print '<td valign="top">'.$langs->trans('NotePublic').'</td>';
			print '<td valign="top" colspan="2">';
			print '<textarea name="note_public" cols="80" rows="'.ROWS_3.'"></textarea>';
			print '</td></tr>';

			// Private note
			if (! $user->societe_id) {
				print '<tr>';
				print '<td valign="top">'.$langs->trans('NotePrivate').'</td>';
				print '<td valign="top" colspan="2">';
				print '<textarea name="note_private" cols="80" rows="'.ROWS_3.'"></textarea>';
				print '</td></tr>';
			}

			print '</table>';
			print '<center><br>';
			print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftEquipement").'">';
			print '</center>';
			print '</form>';
		}
	} else {
		print_fiche_titre($langs->trans("AddEquipementSelectProduct"));

		// premiere �tape on s�lectionne le produit correspondand � l'�quipement
		print '<form name="equipement" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		print '<table class="border" width="100%">';
		print '<tr><td class="fieldrequired">'.$langs->trans("Products").'</td><td>';
		print $form->select_produits('', 'productid', 0, $conf->product->limit_size, 0, -1, 2, '', 0);
		print '</td></tr>';
		print '<tr><td class="fieldrequired">'.$langs->trans("EquipmentSerialMethod").'</td><td>';
		$arraySerialMethod=array(
						'1'=>$langs->trans("InternalSerial"),
						'2'=>$langs->trans("ExternalSerial"),
						'3'=>$langs->trans("SeriesMode")
		);
		print $form->selectarray("serialMethod", $arraySerialMethod, $conf->global->EQUIPEMENT_DEFAULTSERIALMODE);
		print '</td></tr>';
		print '</table>';
		print '<br><center>';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftEquipement").'">';
		print '</center>';
		print '</form>';
	}
} elseif ($id > 0 || ! empty($ref)) {
	/*
	 * Affichage en mode visu
	 */

	$object->fetch($id, $ref);
	if (!$id) $id=$object->id;
	$object->fetch_thirdparty();
	$res=$object->fetch_optionals($object->id, $extralabels);

	dol_htmloutput_mesg($mesg);

	$head = equipement_prepare_head($object);

	dol_fiche_head($head, 'card', $langs->trans("EquipementCard"), 0, 'equipement@equipement');

	// Confirmation de la suppression de l'�quipement
	if ($action == 'delete') {
		$ret=$form->form_confirm(
						$_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteEquipement'),
						$langs->trans('ConfirmDeleteEquipement'), 'confirm_delete', '', 0, 1
		);
		if ($ret == 'html') print '<br>';
	}

	// Confirmation validation
	if ($action == 'validate') {
		$ret=$form->form_confirm(
						$_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateEquipement'),
						$langs->trans('ConfirmValidateEquipement'), 'confirm_validate', '', 0, 1
		);
		if ($ret == 'html') print '<br>';
	}

	// Confirmation de la validation de la fiche d'intervention
	if ($action == 'modify') {
		$ret=$form->form_confirm(
						$_SERVER["PHP_SELF"].'?id='.$object->id,
						$langs->trans('ModifyEquipement'), $langs->trans('ConfirmModifyEquipement'),
						'confirm_modify', '', 0, 1
		);
		if ($ret == 'html') print '<br>';
	}

	// Confirmation de la suppression d'une ligne d'intervention
	if ($action == 'ask_deleteline') {
		$ret=$form->form_confirm(
						$_SERVER["PHP_SELF"].'?id='.$object->id.'&line_id='.GETPOST('line_id', 'int'),
						$langs->trans('DeleteEquipementLine'), $langs->trans('ConfirmDeleteEquipementLine'),
						'confirm_deleteline', '', 0, 1
		);
		if ($ret == 'html') print '<br>';
	}

	print '<table class="border" width="100%">';


	// Ref
	print '<tr><td width=250px><table class="nobordernopadding" width="100%"><tr><td >'.$langs->trans("Ref").'</td>';
	if ($action != 'editnumref' && $object->statut == 0 && $user->rights->equipement->majserial) {
		// si l'�quipement est � l'�tat brouillon et l'habilition requise est active on a le droit de modifier la r�f�rence
		print '<td align="right">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=editnumref&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editnumref') {
		print '<form name="editnumref" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setnumref">';
		print '<input type="text" name="editnumref" value="'.$object->ref.'">';
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		$linkback = '<a href="list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	}
	print '</td></tr>';

	// produit
	$prod=new Product($db);
	$prod->fetch($object->fk_product);
	print '<tr><td class="fieldrequired"><table class="nobordernopadding" width="100%"><tr><td >';
	print $langs->trans("ProductUsed").'</td><td align=right>';
	print "<a href=# onclick=\"$('#descprod').toggle();\" >".img_picto("", "edit_add")."</a>";
	print '</td></tr></table></td><td colspan="3">';
	print $prod->getNomUrl(1)." : ".$prod->label.'</td></tr>';
	print "<tr style='display:none' id='descprod'>";
	print '<td></td><td>'.$prod->description.'</td><tr>';


	// Num�ro de version
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("VersionNumber").'</td>';
	if ($action != 'editnumversion' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editnumversion&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editnumversion') {
		print '<form name="editnumversion" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setnumversion">';
		print '<input type="text" name="numversion" value="'.$object->numversion.'">';
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else
		print $object->numversion;
	print '</td></tr>';

	// quantit� modifiable et visible uniquement si sup�rieur � 1
	if ($object->quantity >1) {
		print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("Quantity").'</td>';
		if ($action != 'editquantity' && $object->statut == 0) {
			print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editquantity&amp;id='.$object->id.'">';
			print img_edit($langs->trans('Modify'), 1).'</a></td>';
		}
		print '</tr></table></td><td colspan="3">';
		if ($action == 'editquantity') {
			print '<form name="editquantity" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setquantity">';
			print '<input type="text" name="quantity" value="'.$object->quantity.'">';
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else
			print $object->quantity;
		print '</td></tr>';
	}

	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("UnitWeight").'</td>';
	if ($action != 'editunitweight' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editunitweight&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editunitweight') {
		print '<form name="editunitweight" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setunitweight">';
		print '<input type="text" name="unitweight" value="'.$object->unitweight.'">';
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else
		print $object->unitweight;
	print '</td></tr>';

	// fournisseur, lui on ne le change pas, si pas bon on supprime l'�quipement
	print '<tr><td class="fieldrequired">'.$langs->trans("Fournisseur").'</td><td>';
	if ($object->fk_soc_fourn > 0 ) {
		$soc=new Societe($db);
		$soc->fetch($object->fk_soc_fourn);
		print $soc->getNomUrl(1);
	}
	print '</td></tr>';

    // commande fournisseur
	if ($user->rights->fournisseur->commande->lire) {
		print '<tr><td><table class="nobordernopadding" width="100%">';
		print '<tr><td>'.$langs->trans("RefCommFourn").'</td>';
		// la commande fournisseur est modifiable si il y a un founisseur de s�lectionn�
		if ($action != 'editcommfourn' && $object->statut == 0)
			if ( $object->fk_soc_fourn > 0) {
				print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editfactfourn&id='.$object->id.'">';
				print img_edit($langs->trans('Modify'), 1).'</a></td>';
			} else
				print '<td align="right">'.img_info($langs->trans('NoSupplierSelected'), 1).'</td>';

		print '</tr></table></td><td colspan="3">';
		if ($action == 'editcommfourn') {
			print '<form name="editfactfourn" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setfactfourn">';
			// liste des commmandes fournisseurs disponible
			print select_commfourn($object->fk_commande_fourn, $object->fk_soc_fourn, 'fk_commande_fourn', 1, 1);

			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			if ($object->fk_commande_fourn) {
				$commfournstatic = new CommandeFournisseur($db);
                $commfournstatic->fetch($object->fk_commande_fourn);
				print $commfournstatic->getNomUrl(1).' - '.dol_print_date($commfournstatic->date_commande, 'day');
				print ' - '.price($commfournstatic->total_ttc);
			}
		}
		print '</td></tr>';
	}

	// facture fournisseur
	if ($user->rights->facture->lire) {
		print '<tr><td><table class="nobordernopadding" width="100%">';
		print '<tr><td>'.$langs->trans("RefFactFourn").'</td>';
		// la facture fournisseur est modifiable ssi il y a un founisseur de s�lectionn�
		if ($action != 'editfactfourn' && $object->statut == 0)
			if ( $object->fk_soc_fourn > 0) {
				print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editfactfourn&id='.$object->id.'">';
				print img_edit($langs->trans('Modify'), 1).'</a></td>';
			} else
				print '<td align="right">'.img_info($langs->trans('NoSupplierSelected'), 1).'</td>';

		print '</tr></table></td><td colspan="3">';
		if ($action == 'editfactfourn') {
			print '<form name="editfactfourn" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setfactfourn">';
			// liste des factures fournisseurs disponible
			print select_factfourn($object->fk_fact_fourn, $object->fk_soc_fourn, 'fk_facture_fourn', 1, 1);

			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			if ($object->fk_fact_fourn) {
				$factfournstatic = new FactureFournisseur($db);
				$factfournstatic->fetch($object->fk_fact_fourn);
				print $factfournstatic->getNomUrl(1).' - '.dol_print_date($factfournstatic->date, 'day');
				print ' - '.price($factfournstatic->total_ttc);
			}
		}
		print '</td></tr>';
	}

	// Lieu de stockage

	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("EntrepotStock").'</td>';
	if ($action != 'editstock' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editstock&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editstock') {
		print '<form name="editstock" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setentrepot">';
		select_entrepot($object->fk_entrepot, 'fk_entrepot', 1, 1);
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		if ($object->fk_entrepot>0) {
			$entrepotStatic=new Entrepot($db);
			$entrepotStatic->fetch($object->fk_entrepot);
			print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
		}
	}
	print '</td></tr>';


	// Client
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("Client").'</td>';
	if ($action != 'editclient' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editclient&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editclient') {
		print '<form name="editclient" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setclient">';
		print $form->select_company($object->fk_soc_client, 'fk_soc_client', '', 1);
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		if ($conf->global->COMPANY_USE_SEARCH_TO_SELECT )
			print "<br><input type=checkbox name=emptyclient value=1>".$langs->trans('EmptyCustomer');

		print '</form>';
	} else {
		if ($object->fk_soc_client >0) {
			$soc=new Societe($db);
			$soc->fetch($object->fk_soc_client);
			print $soc->getNomUrl(1);
		}
	}
	print '</td></tr>';

	// facture client
	if ($user->rights->facture->lire) {
		//on autorise la saisie de la facture client SSI il y a un client de s�lectionn�
		print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("RefFactClient").'</td>';
		if ($action != 'editfactclient' && $object->statut == 0) {
			print '<td align="right">';
			if ( $object->fk_soc_client > 0 ) {
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=editfactclient&amp;id='.$object->id.'">';
				print img_edit($langs->trans('Modify'), 1).'</a></td>';
			} else
				print img_info($langs->trans('NoCustomerSelected'), 1).'</td>';
		}
		print '</tr></table></td><td colspan="3">';
		if ($action == 'editfactclient') {
			print '<form name="editfactclient" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setfactclient">';
			print select_facture($object->fk_fact_client, $object->fk_soc_client, 'fk_fact_client', 1, 1);
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			if ($object->fk_fact_client) {
				$facturestatic=new Facture($db);
				$facturestatic->fetch($object->fk_fact_client);
				print $facturestatic->getNomUrl(1).' - '.dol_print_date($facturestatic->date, 'day');
				print ' - '.price($facturestatic->total_ttc);
			}
		}
		print '</td></tr>';
	}
	// Date start
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("Dateo").'</td>';
	if ($action != 'editdateo' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdateo&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editdateo') {
		print '<form name="editdateo" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setdateo">';
		print $form->select_date($object->dateo, 'dateo', 0, 0, '', "dateo");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
		print dol_print_date($object->dateo, 'day');

	print '</td></tr>';

	if ($conf->global->EQUIPEMENT_USEDLUODATE == 1) {
		// Date DLUO
		print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("DateDluo").'</td>';
		if ($action != 'editdatee' && $object->statut == 0) {
			print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdated&amp;id='.$object->id.'">';
			print img_edit($langs->trans('Modify'), 1).'</a></td>';
		}
		print '</tr></table></td><td colspan="3">';
		if ($action == 'editdated') {
			print '<form name="editdated" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setdated">';
			print $form->select_date($object->dated, 'dated', 0, 0, '', "dated");
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
			print dol_print_date($object->dated, 'day');
		print '</td></tr>';
	}

	// Date end
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("Datee").'</td>';
	if ($action != 'editdatee' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdatee&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editdatee') {
		print '<form name="editdatee" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setdatee">';
		print $form->select_date($object->datee, 'datee', 0, 0, '', "datee");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
		print dol_print_date($object->datee, 'day');

	print '</td></tr>';

	// Extrafields
	if (DOL_VERSION < "3.7.0") {
		$parameters = array( 'colspan' => ' colspan="3"');
		// Note that $action and $object may have been modified by
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
		if (empty($reshook) && ! empty($extrafields->attribute_label)) {
			foreach ($extrafields->attribute_label as $key=>$label) {
				$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);

				print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$label.'</td>';
				if ($action != 'ExFi'.$key && $object->statut == 0) {
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=ExFi'.$key.'&amp;id='.$object->id.'">';
					print img_edit($langs->trans('Modify'), 1).'</a></td>';
				}
				print '</tr></table></td><td colspan="3">';
				if ($action == 'ExFi'.$key) {
					print '<form name="ExFi'.$key.'" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="setExFi'.$key.'">';
					print $extrafields->Showinputfield($key, $value);
					print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
					print '</form>';
				} else
					print $extrafields->showOutputField($key, $value);
				print '</td></tr>'."\n";
			}
		}
	} else {
		$cols = 3;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
	}

	// Description (must be a textarea and not html must be allowed (used in list view)
	print '<tr><td valign="top">';
	print $form->editfieldkey(
					"Description", 'description', $object->description, $object,
					$user->rights->equipement->creer, 'textarea'
	);
	print '</td><td colspan="3">';
	print $form->editfieldval(
					"Description", 'description', $object->description, $object,
					$user->rights->equipement->creer, 'textarea:4:100'
	);
	print '</td>';
	print '</tr>';

	// Etat de l'�quipement
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("EtatEquip").'</td>';
	if ($action != 'editetatequip' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editetatequip&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editetatequip') {
		print '<form name="editetatequip" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setetatequip">';
		print select_equipement_etat($object->fk_etatequipement, 'fk_etatequipement', 1, 1);
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		if ($object->etatequiplibelle)
			print $langs->trans($object->etatequiplibelle);
	}

	print '</td></tr>';


	// Num�ro de immo compta
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("NumImmoCompta").'</td>';
	if ($action != 'editnumimmo' && $object->statut == 0) {
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editnumimmo&amp;id='.$object->id.'">';
		print img_edit($langs->trans('Modify'), 1).'</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editnumimmo') {
		print '<form name="editnumimmo" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setnumimmocompta">';
		print '<input type="text" name="numimmocompta" value="'.$object->numimmocompta.'">';
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
		print $object->numimmocompta;

	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

	print "</table><br>";

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
		require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
		$formcompany= new FormCompany($db);

		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include(DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php');
	}

	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include(DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php');
	}
    print '</div>';
    print "\n";

// Define confirmation messages




//$equipementstatic= new Equipement($db);
    $newref=$object->ref." (1)";
//while(true) {
//	$equipementstatic->fetch(0, $newref);
//	if ($equipementstatic->id > 0)
//		$newref.= " (1)";
//	else
//		break;
//}

    $formquestioncutEquipement=array(
        'text' => $langs->trans("ConfirmCut"),
        array(
            'type' => 'text',
            'name' => 'ref_new',
            'label' => $langs->trans("NewRefForCutEquipment"),
            'value' => $object->ref." (1)",
            'size'=>24
        ),
        array(
            'type' => 'text',
            'name' => 'quantitynew',
            'label' => $langs->trans("QuantitytoCut"),
            'value' => '1',
            'size'=>5
        ),
        array(
            'type' => 'checkbox',
            'name' => 'cloneevent',
            'label' => $langs->trans("CloneContentEquipment"),
            'value' => 1
        ),
    );


// Clone confirmation
    if ($action == 'cutEquipment' && empty($conf->use_javascript_ajax))
        print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,
            $langs->trans('CutEquipment'), $langs->trans('ConfirmcutEquipement', $object->ref),
            'confirm_cutEquipement', $formquestioncutEquipement, 'yes', 'action-cutEquimenent', 230, 600
        );

    /* Barre d'action				*/
//    if ($action == '' ) {
        print '<div class="tabsAction">';

        $parameters = array('id'=> $id, 'product' => $product);
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
        // modified by hook
        if (empty($reshook)) {
            // D�coupage d'un lot si il y en a plus d'un
            if ($object->quantity > 1 && $user->rights->equipement->creer ) {
                if (! empty($conf->use_javascript_ajax)) {
                    print '<div class="inline-block divButAction">';
                    print '<span id="action-cutEquimenent" class="butAction">'.$langs->trans('CutSerial').'</span>';
                    print '</div>'."\n";
                    print $form->formconfirm(
                        $_SERVER["PHP_SELF"].'?id='.$object->id,
                        $langs->trans('CutEquipment'), $langs->trans('ConfirmcutEquipement', $object->ref),
                        'confirm_cutEquipement', $formquestioncutEquipement, 'yes', 'action-cutEquimenent', 230, 600
                    );
                } else {
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cutEquipment"';
                    print '>'.$langs->trans("CutSerial").'</a>';
                }

            }

            // Validate
            if ($object->statut == 0 && $user->rights->equipement->creer ) {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=validate"';
                print '>'.$langs->trans("Valid").'</a>';
            }

            // Modify
            if ($object->statut == 1  && $user->rights->equipement->creer) {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=modify"';
                print '>'.$langs->trans("Modify").'</a>';
            }

            // Delete
            if (($object->statut == 0
                    && $user->rights->equipement->creer)
                || $user->rights->equipement->supprimer) {
                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
                print '>'.$langs->trans('Delete').'</a>';
            }

        }

        // predef event button
        $sql = "SELECT eet.rowid, eet.libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_equipementevt_type as eet";
        $sql.= " , ".MAIN_DB_PREFIX."equipementevt_predef as eep";
        $sql.= " WHERE eep.fk_equipementevt_type = eet.rowid";
        $sql.= " AND eep.fk_product = ".$object->fk_product;
        $sql.= " AND eet.active = 1";

        $result=$db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            if ($num > 0) {
                print "<br>";
                print $langs->trans("PredefinedEvent");
                $i = 0;
                while ($i < $num) {
                    $objp = $db->fetch_object($result);
                    print '<a class="butAction" href="events.php?id='.$object->id.'&prefefid='.$objp->rowid.'" ';
                    print '>'.$langs->trans($objp->libelle).'</a>';
                    $i++;
                }
            }
            $db->free($result);
        }

        print '</div>';
        print '<br>';

        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; // ancre
        /*
         * Built documents
         */
        $filename=dol_sanitizeFileName($object->ref);
        $filedir=$conf->equipement->dir_output . "/".$object->ref;
        $urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
        $genallowed=$user->rights->equipement->creer;
        $delallowed=$user->rights->equipement->supprimer;

        $var=true;

        print "<br>\n";
        $somethingshown=$formfile->show_documents(
            'equipement', $filename, $filedir, $urlsource,
            $genallowed, $delallowed, $object->model_pdf,
            1, 0, 0, 28, 0, '', '', '', $soc->default_lang
        );

        if (strcmp(DOL_VERSION, "6.0.0") >= 0) {
            // Show links to link elements
            $linktoelem = $form->showLinkToObjectBlock($object, null, array('equipement'));
            $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
        } elseif (DOL_VERSION >= "5.0.0")
            $somethingshown = $form->showLinkedObjectBlock($object, "");
        else
            $somethingshown=$object->showLinkedObjectBlock();

        print '</div><div class="fichehalfright"><div class="ficheaddleft">';

        print '</div></div></div>';
//    }
}

llxFooter();
$db->close();
