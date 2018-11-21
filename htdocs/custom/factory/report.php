<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2017	Charlie BENKE			<charlie@patas-monkey.com>
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
 *  \file	   htdocs/factory/report.php
 *  \ingroup	factory
 *  \brief	  Page des Ordres de fabrication sur la fiche produit
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/core/lib/product.lib.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";

require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

dol_include_once('/factory/class/factory.class.php');
dol_include_once('/factory/core/lib/factory.lib.php');
dol_include_once('/factory/class/html.factoryformproduct.class.php');
if (!empty($conf->equipement->enabled)) {
    dol_include_once('/equipement/class/equipement.class.php');
}

if (! empty($conf->global->FACTORY_ADDON)
	&& is_readable(dol_buildpath("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php")))
	dol_include_once("/factory/core/modules/factory/".$conf->global->FACTORY_ADDON.".php");


$langs->load("bills");
$langs->load("products");
$langs->load("stocks");
$langs->load("factory@factory");

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$cancel=GETPOST('cancel', 'alpha');
$key=GETPOST('key');
$parent=GETPOST('parent');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result = restrictedArea($user, 'factory');

$mesg = '';

$product = new Product($db);
$factory = new Factory($db);
$form = new Form($db);

$productid=0;
if ($id || $ref) {
	// l'of et le produit associe
	$result = $factory->fetch($id, $ref);
	$result = $product->fetch($factory->fk_product);
	$id = $factory->id;
}

// all dispatched lines
$dispatchLineList = array();
$dispatchPrefix   = '';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('factoryreport'));

$parameters = array('product' => $product);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $factory, $action);
// Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*
 * Actions
 */

if (empty($reshook)) {
	if ($action == 'closeof' && $user->rights->factory->creer && $factory->statut == 1) {
	    $error = 0;

        $componentProductStatic  = new Product($db);
	    $componentEntrepotStatic = new Entrepot($db);

		$factory->qty_made=GETPOST("qtymade");
		$factory->date_end_made=dol_mktime(
						GETPOST('madeendhour', 'int'), GETPOST('madeendmin', 'int'), 0,
						GETPOST('madeendmonth', 'int'), GETPOST('madeendday', 'int'), GETPOST('madeendyear', 'int')
		);
		$factory->duration_made=GETPOST("duration_madehour")*3600+GETPOST("duration_mademin")*60;
		$factory->description = GETPOST("description");

		// si rien de fabrique le statut de l'of est mis a annule
		if (GETPOST("qtymade") == 0)
			$factory->statut = 3;
		else
			$factory->statut = 2;

		// get new components to add
        $componentSuffixNewList = array();

        // get all component product
        $sql  = "SELECT";
        $sql .= " fd.rowid as fd_rowid";
        $sql .= ", fd.fk_product as id, fd.qty_unit as qtyunit, fd.pmp as pmp, fd.price as price";
        $sql .= ", fd.globalqty, fd.description";
        $sql .= ", fd.id_dispatched_line";
        $sql .= " FROM " . MAIN_DB_PREFIX . "factorydet as fd";
        $sql .= " WHERE fd.fk_factory = " . $factory->id;

        $resql = $db->query($sql);
        if (!$resql) {
            $error++;
            $factory->error = $db->lasterror();
            $factory->errors[] = $factory->error;
        }

        $componentValueArray = array();
        $factoryLineExistsList = array();
        while ($obj = $db->fetch_object($resql)) {
            if ($obj->id_dispatched_line == 0) {
                $componentValueArray[$obj->id] = array(
                    'id'          => $obj->id,
                    'nb'          => $obj->qtyunit,
                    'pmp'         => $obj->pmp,
                    'price'       => $obj->price,
                    'globalqty'   => $obj->globalqty,
                    'description' => $obj->description
                );
            }

            $factoryLineExistsList[$obj->id][$obj->id_dispatched_line] = $obj->fd_rowid;
        }

        // for all posted values
        $componentProductEquipementIdList = array();
        foreach ($_POST as $postKey => $postValue) {
            $matches = array();

            // it's a dispatched line of factory
            if (preg_match('#^' . $dispatchPrefix . 'id_component_product_([0-9]+)_([0-9]+)$#', $postKey, $matches)) {
                $componentProductId = intval($matches[1]);
                $lineNum            = $matches[2];
                $dispatchSuffix     = $componentProductId . '_' . $lineNum;

                // get post values
                $componentQtyUsed                     = GETPOST($dispatchPrefix . 'qtyused_' . $dispatchSuffix, 'int') > 0 ? GETPOST('qtyused_' . $dispatchSuffix, 'int') : 0;
                $componentQtyDeleted                  = GETPOST($dispatchPrefix . 'qtydeleted_' . $dispatchSuffix, 'int') > 0 ? GETPOST('qtydeleted_' . $dispatchSuffix, 'int') : 0;
                $componentProductToSerialize          = GETPOST($dispatchPrefix . 'product_serializel_' . $dispatchSuffix, 'int');
                $componentProductEquipementUsedIdList = GETPOST($dispatchPrefix . 'equipementused_' . $dispatchSuffix, 'array') ? GETPOST($dispatchPrefix . 'equipementused_' . $dispatchSuffix, 'array') : array();
                $componentProductEquipementLostIdList = GETPOST($dispatchPrefix . 'equipementlost_' . $dispatchSuffix, 'array') ? GETPOST($dispatchPrefix . 'equipementlost_' . $dispatchSuffix, 'array') : array();
                $equipementLostFkEntrepot             = GETPOST($dispatchPrefix . 'id_entrepotlost_' . $dispatchSuffix, 'int') ? GETPOST($dispatchPrefix . 'id_entrepotlost_' . $dispatchSuffix, 'int') : NULL;

                // component product
                $componentProduct = new Product($db);
                $componentProduct->fetch($componentProductId);
                $componentProductValueArray = $componentValueArray[$componentProductId];

                // set line for errors
                $errorLine = $langs->trans('Product') . ' ' . $componentProduct->ref . ' - ' . $langs->trans('Line') . ' ' . ($lineNum + 1);

                // add to dispatched lines
                $dispatchLineList[$dispatchSuffix] = array(
                    'component_product'      => $componentProduct,
                    'line'                   => intval($lineNum),
                    'fk_factorydet'          => 0,
                    'nb'                     => $componentProductValueArray['qtyplanned'],
                    'value_array'            => $componentProductValueArray,
                    'equipementused_id_list' => $componentProductEquipementUsedIdList,
                    'equipementlost_id_list' => $componentProductEquipementLostIdList,
                    'entrepotlost_id'        => $equipementLostFkEntrepot
                );

                // find new lines
                if (!isset($factoryLineExistsList[$componentProductId][$lineNum])) {
                    $componentSuffixNewList[] = $dispatchSuffix;
                } else {
                    $dispatchLineList[$dispatchSuffix]['fk_factorydet'] = $factoryLineExistsList[$componentProductId][$lineNum];
                }

                // check equipement qty to use and delete with equipement used and delete list
                if ($componentProductToSerialize == 1) {
                    if ($componentQtyUsed != count($componentProductEquipementUsedIdList)) {
                        $error++;
                        $factory->error    = $errorLine . ' : ' . $langs->trans('EquipementErrorQtyToUse');
                        $factory->errors[] = $factory->error;
                    }

                    if ($componentQtyDeleted != count($componentProductEquipementLostIdList)) {
                        $error++;
                        $factory->error    = $errorLine . ' : ' . $langs->trans('EquipementErrorQtyLost');
                        $factory->errors[] = $factory->error;
                    }
                }

                // check all serial numbers for each component product
                if (!isset($componentProductEquipementIdList[$componentProductId])) {
                    $componentProductEquipementIdList[$componentProductId] = array();
                }
                foreach ($componentProductEquipementUsedIdList as $equipementId) {
                    if (in_array($equipementId, $componentProductEquipementIdList[$componentProductId])) {
                        $error++;
                        $factory->error    = $errorLine . ' : ' . $langs->trans('EquipementErrorReferenceAlreadyUsed');
                        $factory->errors[] = $factory->error;
                    } else {
                        $componentProductEquipementIdList[$componentProductId][] = $equipementId;
                    }
                }
                foreach ($componentProductEquipementLostIdList as $equipementId) {
                    if (in_array($equipementId, $componentProductEquipementIdList[$componentProductId])) {
                        $error++;
                        $factory->error    = $errorLine . ' : ' . $langs->trans('EquipementErrorReferenceAlreadyUsed');
                        $factory->errors[] = $factory->error;
                    } else {
                        $componentProductEquipementIdList[$componentProductId][] = $equipementId;
                    }
                }

                // check quantity deleted and selected warehouse for quantity lost
                if ($componentQtyDeleted>0 && !($equipementLostFkEntrepot>0)) {
                    $error++;
                    $factory->error    = $errorLine . ' : ' . $langs->trans('EquipementErrorWarehouseLostNone');
                    $factory->errors[] = $factory->error;
                }
            }
        }

        if (!$error) {
            $now = dol_now();

            $db->begin();

            // first save new dispatched lines
            foreach ($componentSuffixNewList as $componentSuffixNew) {
                $componentSuffixArray = explode('_', $componentSuffixNew);
                if (count($componentSuffixArray) == 2) {
                    $componentIdProduct        = $componentSuffixArray[0];
                    $componentIdDispatchedLine = $componentSuffixArray[1];

                    $componentFkEntrepot = GETPOST($dispatchPrefix . 'id_entrepot_' . $componentSuffixNew, 'int') ? GETPOST($dispatchPrefix . 'id_entrepot_' . $componentSuffixNew, 'int') : NULL;
                    $componentQtyUsed    = GETPOST($dispatchPrefix . 'qtyused_' . $componentSuffixNew, 'int') > 0 ? GETPOST($dispatchPrefix . 'qtyused_' . $componentSuffixNew, 'int') : 0;

                    // add new factory line
                    $fkFactoryDet = $factory->createof_component($factory->id, 0, $componentValueArray[$componentIdProduct], 0, $componentFkEntrepot, $componentIdDispatchedLine, $componentQtyUsed);
                    if ($fkFactoryDet < 0) {
                        $error++;
                    }

                    // add new dispatch line
                    $dispatchLineList[$dispatchSuffix]['fk_factorydet'] = $fkFactoryDet;

                    // associate equipment list to use
                    if (!$error) {
                        $equipementUsedIdList = GETPOST($dispatchPrefix . 'equipementused_' . $componentSuffixNew, 'array') ? GETPOST($dispatchPrefix . 'equipementused_' . $componentSuffixNew, 'array') : array();

                        foreach ($equipementUsedIdList as $equipementUsedId) {
                            $equipementUsed = new Equipement($db);
                            $equipementUsed->fetch($equipementUsedId);

                            // add line fk_equipement, fk_factory and fk_factorydet in equipementevt
                            $ret = $equipementUsed->addline($equipementUsed->id, -1, '', $now, $now, '', '', '', '', '', '', 0, 0, 0, 0, $factory->id, $fkFactoryDet);
                            if ($ret < 0) {
                                $error++;
                                $factory->error    = $equipementUsed->errorsToString();
                                $factory->errors[] = $factory->error;
                            }

                            if ($error) {
                                break;
                            }
                        }
                    }
                }

                if ($error) {
                    break;
                }
            }

            if (!$error) {
                //on memorise les infos de l'OF
                $sql  = "UPDATE " . MAIN_DB_PREFIX . "factory";
                $sql .= " SET date_end_made = " . ($factory->date_end_made ? $db->idate($factory->date_end_made) : 'null');
                $sql .= " , duration_made = " . ($factory->duration_made ? $factory->duration_made : 'null');
                $sql .= " , qty_made = " . ($factory->qty_made ? $factory->qty_made : 'null');
                $sql .= " , description = '" . $db->escape($factory->description) . "'";
                $sql .= " , fk_statut = 2";
                $sql .= " WHERE rowid = " . $id;

                if (!$db->query($sql)) {
                    $error++;
                    $factory->error = $db->lasterror();
                    $factory->errors[] = $factory->error;
                }

                if (!$error) {
                    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
                    $mouvP = new MouvementStock($db);
                    $mouvP->origin = new Factory($db);
                    $mouvP->origin->id = $id;

                    // for all dispatched lines
                    foreach ($dispatchLineList as $dispatchLine) {
                        $totprixfabrication = 0;

                        // component product
                        $componentProduct   = $dispatchLine['component_product'];
                        $componentProductId = $componentProduct->id;
                        $lineNum            = $dispatchLine['line'];

                        // component product values
                        $value               = $dispatchLine['value_array'];
                        $componentProductPMP = $value['pmp'];

                        // dispatch values
                        $dispatchSuffix = $componentProductId . '_' . $lineNum;

                        // factory line
                        $fkFactoryDet = $dispatchLine['fk_factorydet'];

                        // get post values
                        $componentFkEntrepot                  = GETPOST($dispatchPrefix . 'id_entrepot_' . $dispatchSuffix, 'int') ? GETPOST($dispatchPrefix . 'id_entrepot_' . $dispatchSuffix, 'int') : NULL;
                        $componentQtyUsed                     = GETPOST($dispatchPrefix . 'qtyused_' . $dispatchSuffix, 'int') > 0 ? GETPOST($dispatchPrefix . 'qtyused_' . $dispatchSuffix, 'int') : 0;
                        $componentQtyDeleted                  = GETPOST($dispatchPrefix . 'qtydeleted_' . $dispatchSuffix, 'int') > 0 ? GETPOST($dispatchPrefix . 'qtydeleted_' . $dispatchSuffix, 'int') : 0;
                        $componentProductToSerialize          = GETPOST($dispatchPrefix . 'product_serializel_' . $dispatchSuffix, 'int');
                        $componentProductEquipementUsedIdList = GETPOST($dispatchPrefix . 'equipementused_' . $dispatchSuffix, 'array') ? GETPOST($dispatchPrefix . 'equipementused_' . $dispatchSuffix, 'array') : array();
                        $componentProductEquipementLostIdList = GETPOST($dispatchPrefix . 'equipementlost_' . $dispatchSuffix, 'array') ? GETPOST($dispatchPrefix . 'equipementlost_' . $dispatchSuffix, 'array') : array();
                        $equipementLostFkEntrepot             = GETPOST($dispatchPrefix . 'id_entrepotlost_' . $dispatchSuffix, 'int') ? GETPOST($dispatchPrefix . 'id_entrepotlost_' . $dispatchSuffix, 'int') : NULL;

                        // set line for errors
                        $errorLine = $langs->trans('Product') . ' ' . $componentProduct->ref . ' - ' . $langs->trans('Line') . ' ' . ($lineNum + 1);

                        if ($componentQtyUsed > 0 && empty($componentFkEntrepot)) {
                            $error++;
                            $factory->error    = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Warehouse'));
                            $factory->errors[] = $factory->error;
                            break;
                        }

                        // on met a jour les infos des lignes de l'OF
                        $sql = "UPDATE " . MAIN_DB_PREFIX . "factorydet ";
                        $sql .= " SET qty_used = " . $componentQtyUsed;
                        $sql .= ", qty_deleted = " . $componentQtyDeleted;
                        $sql .= ", fk_entrepot = " . ($componentFkEntrepot > 0 ? $componentFkEntrepot : 'NULL');
                        $sql .= " WHERE fk_factory = " . $id;
                        $sql .= " AND fk_product = " . $componentProductId;
                        $sql .= " AND id_dispatched_line = " . $lineNum;

                        if (!$db->query($sql)) {
                            $error++;
                            $factory->error    = $db->lasterror();
                            $factory->errors[] = $factory->error;
                        } else {
                            // s'il a ete detruit
                            if ($componentQtyDeleted > 0) {
                                // le prix est a 0 pour ne pas impacter le pmp
                                $idmv = $mouvP->livraison($user, $componentProductId, $componentFkEntrepot, $componentQtyDeleted, 0, $langs->trans("DeletedFactory", $factory->ref), $factory->date_end_made);
                                if ($idmv < 0) {
                                    $error++;
                                    $componentEntrepotStatic->fetch($componentFkEntrepot);
                                    $factory->error    = $errorLine . " : " . $mouvP->error . " (" . $componentEntrepotStatic->libelle . ")";
                                    $factory->errors[] = $factory->error;
                                }

                                if (!$error) {
                                    // delete equipment (put in waste warehouse selected)
                                    if ($componentProductToSerialize == 1) {
                                        foreach ($componentProductEquipementLostIdList as $equipementLostId) {
                                            $equipementLost = new Equipement($db);
                                            $equipementLost->fetch($equipementLostId);

                                            $ret = $equipementLost->set_entrepot($user, $equipementLostFkEntrepot);
                                            if ($ret < 0) {
                                                $error++;
                                                $factory->error    = $errorLine . " : " . $equipementLost->errorsToString();
                                                $factory->errors[] = $factory->error;
                                            }

                                            if ($error) {
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            if (!$error) {
                                if ($componentQtyUsed != 0) {
                                    // le prix est a 0 pour ne pas impacter le pmp
                                    $idmv = $mouvP->livraison($user, $componentProductId, $componentFkEntrepot, $componentQtyUsed, 0, $langs->trans("UsedforFactory", $factory->ref), $factory->date_end_made);
                                    if ($idmv < 0) {
                                        $error++;
                                        $componentEntrepotStatic->fetch($componentFkEntrepot);
                                        $factory->error = $errorLine . " : " . $mouvP->error . " (" . $componentEntrepotStatic->libelle . ")";
                                        $factory->errors[] = $factory->error;
                                    }

                                    // remove equipment list to use from warehouse of component
                                    if (!$error) {
                                        if ($componentProductToSerialize == 1) {
                                            foreach ($componentProductEquipementUsedIdList as $equipementUsedId) {
                                                $equipementUsed = new Equipement($db);
                                                $equipementUsed->fetch($equipementUsedId);

                                                $ret = $equipementUsed->set_entrepot($user, -1);
                                                if ($ret < 0) {
                                                    $error++;
                                                    $factory->error = $errorLine . " : " . $equipementUsed->errorsToString();
                                                    $factory->errors[] = $factory->error;
                                                }

                                                if ($error) {
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // on totalise le prix d'achat des composants utilises pour determiner un prix de fabrication et mettre a jour le pmp du produit fabrique
                            // attention on prend les quantites utilisees et detruites
                            $totprixfabrication += $componentQtyUsed * $componentProductPMP;
                            $totprixfabrication += $componentQtyDeleted * $componentProductPMP;
                        }

                        if ($error) {
                            break;
                        }
                    }

                    if (!$error) {
                        // on ajoute un mouvement de stock d'entree de produit
                        if ($factory->qty_made != 0) {
                            $idmv = $mouvP->reception($user, $factory->fk_product, $factory->fk_entrepot, $factory->qty_made, ($totprixfabrication / $factory->qty_made), $langs->trans("BuildedFactory", $factory->ref), $factory->date_end_made);
                            if ($idmv < 0) {
                                $error++;
                                $componentProductStatic->fetch($factory->fk_product);
                                $componentEntrepotStatic->fetch($factory->fk_entrepot);
                                $factory->error    = $componentProductStatic->ref . " : " . $mouvP->error . " (" . $componentEntrepotStatic->libelle . ")";
                                $factory->errors[] = $factory->error;
                            }
                        }
                    }

                    if (!$error) {
                        // Call trigger
                        $result = $factory->call_trigger('FACTORY_CLOSE', $user);
                    }
                }
            }

            // commit or rollback
            if ($error) {
                $db->rollback();
            } else {
                $db->commit();
            }
        }

		// messages
		if ($error) {
		    setEventMessages($factory->error, $factory->errors, 'errors');
		    // reload factory
            $factory->fetch($factory->id);
        } else {
            setEventMessage($langs->trans("BuildedFactory", $factory->ref), 'mesgs');
            // on redirige pour eviter le doublement
            header("Location: ". $_SERVER["PHP_SELF"] . '?id='.$factory->id);
            exit();
        }

        $action = "";
	}

	if ($action == 'reopenof') {
		$factory->statut = 1;
		$sql = "UPDATE ".MAIN_DB_PREFIX."factory ";
		$sql.= " SET fk_statut =1";
		$sql.= " WHERE rowid = ".$id;
		if ($db->query($sql)) {
			// on supprimera les mouvements de stock quand le mouvement sera stocké V6?
		}
		$action="";
	}
}
/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
if (!empty($conf->equipement->enabled)) {
    $equipementStatic = new Equipement($db);
}

llxHeader("", "", $langs->trans("CardFactory"), '', 0, 0, array('/custom/factory/js/factory_dispatcher.js?sid=' . dol_now()));

dol_htmloutput_mesg($mesg);

$head=factory_prepare_head($factory, $user);
$titre=$langs->trans("Factory");
$picto="factory@factory";
dol_fiche_head($head, 'factoryreport', $titre, 0, $picto);


print '<form name="closeof" action="'.$_SERVER["PHP_SELF"].'?id='.$factory->id.'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="closeof">';
if (!empty($conf->equipement->enabled)) {
    print '<input type="hidden" id="url_to_get_all_equipement_in_warehouse" name="url_to_get_all_equipement_in_warehouse" value="' . dol_buildpath('/equipement/ajax/all_equipement_in_warehouse.php', 1) . '" />';
}
print '<table class="border" width="100%">';
print "<tr>";

//$bproduit = ($product->isproduct());

// Reference
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan=3>';
print $form->showrefnav($factory, 'ref', '', 1, 'ref');
print '</td></tr>';


// Lieu de stockage
print '<tr><td>'.$langs->trans("Warehouse").'</td><td colspan=3>';
if ($factory->fk_entrepot>0) {
	$entrepotStatic=new Entrepot($db);
	$entrepotStatic->fetch($factory->fk_entrepot);
	print $entrepotStatic->getNomUrl(1)." - ".$entrepotStatic->lieu." (".$entrepotStatic->zip.")" ;
}

print '</td></tr>';

// Date start planned
print '<tr><td width=20%>'.$langs->trans("FactoryDateStartPlanned").'</td><td width=30%>';
print dol_print_date($factory->date_start_planned, 'day');
print '</td><td width=20%>'.$langs->trans("DateStartMade").'</td><td width=30%>';
print dol_print_date($factory->date_start_made, 'day');
print '</td></tr>';

// Date end planned
print '<tr><td>'.$langs->trans("FactoryDateEndPlanned").'</td><td>';
print dol_print_date($factory->date_end_planned,'day');
print '</td><td>'.$langs->trans("DateEndMade").'</td><td>';
if ($factory->statut == 1)
	print $form->select_date(
					($factory->date_end_made ? $factory->date_end_made : $factory->date_end_planned),
					'madeend', 0, 0, '', "madeend"
	);
else
	print dol_print_date($factory->date_end_made, 'day');
print '</td></tr>';

// quantity
print '<tr><td>'.$langs->trans("QuantityPlanned").'</td><td>';
print $factory->qty_planned;
print '</td><td>'.$langs->trans("QuantityMade").'</td><td>';
if ($factory->statut == 1)
	print '<input type="text" id="qtymade" name="qtymade" size=6 value="'.($factory->qty_made ? $factory->qty_made : $factory->qty_planned).'">';
else
	print $factory->qty_made;
print '</td></tr>';

// duration
print '<tr><td>'.$langs->trans("FactoryDurationPlanned").'</td><td>';
print convertSecondToTime($factory->duration_planned, 'allhourmin');
print '</td><td>'.$langs->trans("DurationMade").'</td><td>';

if ($factory->statut == 1)
	print $form->select_duration(
					'duration_made',
					($factory->duration_made ? $factory->duration_made : $factory->duration_planned),
					0, 'text'
	);
else
	print convertSecondToTime($factory->duration_made, 'allhourmin');
print '</td></tr>';

print '<tr><td>'.$langs->trans('Status').'</td><td colspan=3>'.$factory->getLibStatut(4).'</td></tr>';
print '<tr><td valign=top>'.$langs->trans('Description').'</td><td colspan=3>';
if ($factory->statut == 1)
	print '<textarea name="description" wrap="soft" cols="120" rows="'.ROWS_4.'">'.$factory->description.'</textarea>';
else
	print str_replace(array("\r\n", "\n"), "<br>", $factory->description);
print '</td></tr>';
print '</table>';
print '<br>';


// tableau de description du produit
print '<table width=100% ><tr><td valign=top width=40%>';
print_fiche_titre($langs->trans("ProducttoBuild"), '', '');

print '<table class="border" width="100%">';

//$bproduit = ($object->isproduct());
print '<tr><td width=30% class="fieldrequired">'.$langs->trans("Product").'</td>';
print '<td>'.$product->getNomUrl(1)." : ".$product->label.'</td></tr>';

// TVA
print '<tr><td>'.$langs->trans("VATRate").'</td>';
print '<td>'.vatrate($product->tva_tx.($product->tva_npr?'*':''), true).'</td></tr>';

// Price
print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
if ($product->price_base_type == 'TTC') {
	print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
	$sale="";
} else {
	print price($product->price).' '.$langs->trans($product->price_base_type);
	$sale=$product->price;
}
print '</td></tr>';

// Price minimum
print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
if ($product->price_base_type == 'TTC')
	print price($product->price_min_ttc).' '.$langs->trans($product->price_base_type);
else
	print price($product->price_min).' '.$langs->trans($product->price_base_type);
print '</td></tr>';

// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
print $product->getLibStatut(2, 0);
print '</td></tr>';

// Status (to buy)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
print $product->getLibStatut(2, 1);
print '</td></tr>';

print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
$product->load_stock();
print '<td>'.$product->stock_reel.'</td></tr>';

print '</table>';

print '</td>';

// tableau de description de la composition du produit
print '<td  valign=top>';

// indique si on a déjà une composition de présente ou pas
$compositionpresente=0;

//$prods_arbo =$factory->getChildsOF($id);
if (empty($dispatchLineList)) {
    $sql  = "SELECT";
    $sql .= " fd.rowid as fd_rowid";
    $sql .= ", fd.fk_product as id";
    $sql .= ", fd.qty_used as qtyused";
    $sql .= ", fd.qty_deleted as qtydeleted";
    $sql .= ", fd.globalqty";
    $sql .= ", fd.description";
    $sql .= ", fd.qty_unit as qtyunit";
    $sql .= ", fd.qty_planned as qtyplanned";
    //$sql .= ", fd.fk_mvtstockplanned as mvtstockplanned";
    //$sql .= ", fd.fk_mvtstockused as mvtstockused";
    //$sql .= ", fd.pmp as pmp";
    //$sql .= ", fd.price as price";
    $sql .= ", fd.fk_entrepot as child_fk_entrepot";
    $sql .= ", fd.id_dispatched_line";
    //$sql .= ", p.label as label";
    //$sql .= ", p.ref";
    //$sql .= ", p.fk_product_type";
    $sql .= " FROM " . MAIN_DB_PREFIX . "factorydet as fd";
    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
    $sql .= " WHERE fd.fk_factory = " . $id;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $valueArray = array(
                'id'                => $obj->id,
                'nb'                => $obj->qtyunit,
                'globalqty'         => $obj->globalqty,
                'description'       => $obj->description,
                'qtyused'           => $obj->qtyused,
                'qtydeleted'        => $obj->qtydeleted,
                'qtyplanned'        => $obj->qtyplanned,
                'child_fk_entrepot' => $obj->child_fk_entrepot
            );

            // component product
            $componentProduct = new Product($db);
            $componentProduct->fetch($obj->id);
            $componentProductId = $componentProduct->id;

            // equipment used and lost id list
            $equipementUsedIdList = array();
            $equipementLostIdList = array();
            $entrepotLostId       = NULL;
            if (!empty($conf->equipement->enabled)) {
                $sqlEquipementEvt  = "SELECT ee.rowid";
                $sqlEquipementEvt .= ", ee.fk_equipement";
                $sqlEquipementEvt .= ", e.fk_entrepot";
                $sqlEquipementEvt .= " FROM " . MAIN_DB_PREFIX . "equipementevt as ee";
                $sqlEquipementEvt .= " INNER JOIN " . MAIN_DB_PREFIX . "equipement as e ON e.rowid = ee.fk_equipement";
                $sqlEquipementEvt .= " WHERE ee.fk_factory = " . $id;
                $sqlEquipementEvt .= " AND ee.fk_factorydet = " . $obj->fd_rowid;

                $resqlEquipementEvt = $db->query($sqlEquipementEvt);
                if ($resqlEquipementEvt) {
                    while ($obje = $db->fetch_object($resqlEquipementEvt)) {
                        // modification mode
                        if ($factory->statut == 1) {
                            $equipementUsedIdList[] = $obje->fk_equipement;
                        }
                        // validated mode
                        else {
                            if ($obje->fk_entrepot > 0) {
                                $equipementLostIdList[] = $obje->fk_equipement;
                                $entrepotLostId = intval($obje->fk_entrepot);
                            } else {
                                $equipementUsedIdList[] = $obje->fk_equipement;
                            }
                        }
                    }

                    $db->free($resqlEquipementEvt);
                }
            }

            // dispatch values
            $lineNum        = intval($obj->id_dispatched_line);
            $dispatchSuffix = $componentProduct->id . '_' . $lineNum;

            // add dispatch line
            $dispatchLineList[$dispatchSuffix] = array(
                'component_product'      => $componentProduct,
                'line'                   => $lineNum,
                'fk_factorydet'          => $obj->fd_rowid,
                'nb'                     => $obj->qtyplanned,
                'value_array'            => $valueArray,
                'equipementused_id_list' => $equipementUsedIdList,
                'equipementlost_id_list' => $equipementLostIdList,
                'entrepotlost_id'        => $entrepotLostId
            );
        }

        $db->free($resql);
    }
}

print_fiche_titre($langs->trans("FactorisedProductsNumber").' : '.count($dispatchLineList),'','');

// List of subproducts
if (count($dispatchLineList) > 0) {
    // list of component product id
    $componentProductIdList = array();

    // all js lines
    $outjsLineList = array();
    $outjsQtyMadeChangeList = array();

	$compositionpresente=1;
	print '<table class="border" >';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
    print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Warehouse").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyUnitNeed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("FactoryQtyPlanned").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyConsummed").'</td>';
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyLosed").'</td>';
    if (!empty($conf->equipement->enabled)) {
        print '<td class="liste_titre" align="center">' . $langs->trans("EquipementLost") . '</td>';
    }
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyUsed").'</td>';
    if (!empty($conf->equipement->enabled)) {
        print '<td class="liste_titre" align="center">' . $langs->trans("EquipementUsed") . '</td>';
    }
	print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyRestocked").'</td>';

	print '</tr>';
	$mntTot=0;
	$pmpTot=0;

	$productEntrepotStatic = new Entrepot($db);
    $factoryformproduct = new FactoryFormProduct($db);

    foreach ($dispatchLineList as $dispatchLine) {
        // component product
        $componentProduct      = $dispatchLine['component_product'];
        $componentProductId    = $componentProduct->id;
        $componentProductLabel = $componentProduct->label;

        // component product values
        $valueArray                  = $dispatchLine['value_array'];
        $componentProductGlobalQty   = $valueArray['globalqty'];
        $componentProductQtyPlanned  = $valueArray['qtyplanned'];
        $componentProductQtyUsed     = $valueArray['qtyused'];
        $componentProductQtyDeleted  = $valueArray['qtydeleted'];
        $componentProductFkEntrepot  = $valueArray['child_fk_entrepot'];
        $componentProductDescription = $valueArray['description'];
        $componentProductQtyUnit     = $valueArray['nb'];

        // dispatcher
        $dispactherList = array(
            'id'                     => $componentProductId,
            'name'                   => '',
            'line'                   => $dispatchLine['line'],
            'nb'                     => $componentProductQtyPlanned,
            'equipementused_id_list' => $dispatchLine['equipementused_id_list'],
            'equipementlost_id_list' => $dispatchLine['equipementlost_id_list'],
            'entrepotlost_id'        => $dispatchLine['entrepotlost_id'],
            'btn_nb'                 => 0,
            'mode'                   => 'select',
            'unlock_qty'             => 'true',
            'element_type'           => ''
        );
        $dispatchSuffix = $dispactherList['id'] . '_' . $dispatchLine['line'];

        // get post values
        $componentFkEntrepot = GETPOST($dispatchPrefix . 'id_entrepot_' . $dispatchSuffix, 'int')>0 ? GETPOST($dispatchPrefix . 'id_entrepot_' . $dispatchSuffix, 'int') : $componentProductFkEntrepot;
        $componentQtyUsed    = GETPOST($dispatchPrefix . 'qtyused_' . $dispatchSuffix, 'int')!='' ? GETPOST($dispatchPrefix . 'qtyused_' . $dispatchSuffix, 'int') : $componentProductQtyPlanned;
        $componentQtyDeleted = GETPOST($dispatchPrefix . 'qtydeleted_' . $dispatchSuffix, 'int')!='' ? GETPOST($dispatchPrefix . 'qtydeleted_' . $dispatchSuffix, 'int') : 0;

        // verify if product have child then display it after the product name
        $tmpChildArbo = $factory->getChildsArbo($componentProductId);
        $nbChildArbo = "";
        if (count($tmpChildArbo) > 0) $nbChildArbo = " (" . count($tmpChildArbo) . ")";

		print '<tr name="' . $dispatchPrefix . $dispatchSuffix . '">';

        // dispatch component ref
		print '<td align="left">'.$factory->getNomUrlFactory($componentProductId, 1,'fiche').$nbChildArbo;
        print '<input type="hidden" id="' . $dispatchPrefix . 'id_component_product_'  .$dispatchSuffix . '" name="' . $dispatchPrefix . 'id_component_product_'  .$dispatchSuffix . '" value="' . $componentProductId . '" />';
		print $factory->PopupProduct($componentProductId);
		print '</td>';

		// dispatch component label (with description)
		print '<td align="left" title="' . $componentProductDescription . '">' . $componentProductLabel . '</td>';

		// component warehouse
        print '<td>';
        if ($factory->statut == 1) {
            print $factoryformproduct->selectWarehouses($componentFkEntrepot, $dispatchPrefix . 'id_entrepot_' . $dispatchSuffix, 'warehouseopen,warehouseinternal', 0, 0, $componentProductId, '', 0, 1, null, 'minwidth100', '', 1, TRUE);
        } else {
            if ($componentProductFkEntrepot>0 && $productEntrepotStatic->fetch($componentProductFkEntrepot)) {
                print $productEntrepotStatic->getNomUrl(1);
            }
        }
        print '</td>';

        // component qty unit
		print '<td align="center">'.$componentProductQtyUnit;
		if ($componentProductGlobalQty == 1) {
            print "&nbsp;G";
            print '<input type="hidden" id="' . $dispatchPrefix . 'qtyunit_' . $dispatchSuffix . '" value="1" />';
        } else {
            print '<input type="hidden" id="' . $dispatchPrefix . 'qtyunit_' . $dispatchSuffix . '" value="' . $componentProductQtyUnit . '" />';
        }
		print '</td>';

		// component qty planned
		print '<td align="center">'.($componentProductQtyPlanned).'</td>';

		if ($factory->statut == 1) {
			// si c'est la premiere saisie on alimente avec les valeurs par defaut
			if ($componentProductQtyUsed) {
				print '<td align="right">'.$componentProductQtyUsed.'</td>';
				print '<td align="center">';
				print '<input type="text" size="4" name="qtydeleted_'.$dispatchSuffix.'" value="'.($componentProductQtyDeleted).'"></td>';
				print '<td align="right">'.($componentProductQtyUsed+$componentProductQtyDeleted).'</td>';
				print '<td align="right">'.($componentProductQtyPlanned-($componentProductQtyUsed+$componentProductQtyDeleted)).'</td>';
			} else {
			    // javascript for line
                $outjs = '';

			    // dispatch product to serialize
                $componentProductToSerialize = 0;
                $equipementList = array();
                if (!empty($conf->equipement->enabled)) {
                    if ($componentProduct->array_options['options_synergiestech_to_serialize']==1) {
                        $componentProductToSerialize = 1;
                        $dispactherList['element_type'] = 'equipement';
                        $dispactherList['element_data'] = json_encode(["actionname" => "getAllEquipementInWarehouse", "htmlname_middle" => "equipementused_", "copyto_htmlname_middle" => "equipementlost_"]);

                        // find all equipments for a product and warehouse
                        if ($componentFkEntrepot > 0) {
                            $resql = $equipementStatic->findAllByFkProductAndFkEntrepot($componentProductId, $componentFkEntrepot);
                            if ($resql) {
                                while ($obj = $db->fetch_object($resql)) {
                                    $equipementList[$obj->rowid] = $obj->ref;
                                }
                            }
                        }
                    }

                    print '<input type="hidden" name="' . $dispatchPrefix . 'product_serializel_' . $dispatchSuffix . '" value="' . $componentProductToSerialize . '" />';
                }

			    // dispatch qty planned
				print '<td align="center">'.$componentProductQtyPlanned.'</td>';

				// dispatch qty lost
				print '<td align="center"><input type="text" size="4" name="' . $dispatchPrefix . 'qtydeleted_' . $dispatchSuffix . '" value="' . $componentQtyDeleted . '" /></td>';
                if (!empty($conf->equipement->enabled)) {
                    // dispatch lost equipment
                    $multiSelectEquipement      = '';
                    $selectWarehousesEquipement = '';

                    if ($componentProductToSerialize==1) {
                        // warehouses for lost quantities
                        $equipementLostFkEntrepot   = GETPOST($dispatchPrefix . 'id_entrepotlost_' . $dispatchSuffix, 'int')>0 ? GETPOST($dispatchPrefix . 'id_entrepotlost_' . $dispatchSuffix, 'int') : '';
                        $selectWarehousesEquipement = $langs->trans("EquipementWarehouseForLost") . ' : ' . $factoryformproduct->selectWarehouses($equipementLostFkEntrepot, $dispatchPrefix . 'id_entrepotlost_' . $dispatchSuffix, 'warehouseopen,warehouseinternal', 1, 0, $componentProductId, '', 0, 1, null, 'minwidth100', '', 1, FALSE);

                        // multiselect equipment lost
                        $idEquipementList = array();
                        if (GETPOST($dispatchPrefix . 'equipementlost_' . $dispatchSuffix, 'array')) {
                            $idEquipementList = GETPOST($dispatchPrefix . 'equipementlost_' . $dispatchSuffix, 'array');
                        }
                        $multiSelectEquipement = Form::multiselectarray($dispatchPrefix . 'equipementlost_' . $dispatchSuffix, $equipementList, $idEquipementList, 0, 0, '', 0, 200);
                    }

                    print '<td>';
                    print  $selectWarehousesEquipement;
                    print '<span id="' . $dispatchPrefix . 'equipementlost_multiselect_' . $dispatchSuffix . '">' . $multiSelectEquipement . '</span>';
                    print '</td>';
                }

                // dispatch qty used
				print '<td><input type="text" size="4" id="' . $dispatchPrefix . 'qtyused_' . $dispatchSuffix . '" name="' . $dispatchPrefix . 'qtyused_' . $dispatchSuffix . '" value="' . $componentQtyUsed . '" /></td>';
                if (!empty($conf->equipement->enabled)) {
                    // dispatch used equipment
                    $multiSelectEquipement = '';

                    if ($componentProductToSerialize==1) {
                        // multiselect equipment used
                        $idEquipementList = $dispactherList['equipementused_id_list'];
                        if (GETPOST($dispatchPrefix . 'equipementused_' . $dispatchSuffix, 'array')) {
                            $idEquipementList = GETPOST($dispatchPrefix . 'equipementused_' . $dispatchSuffix, 'array');
                        }
                        $multiSelectEquipement = Form::multiselectarray($dispatchPrefix . 'equipementused_' . $dispatchSuffix, $equipementList, $idEquipementList, 0, 0, '', 0, 200);
                    }

                    print '<td>';
                    print '<span id="' . $dispatchPrefix . 'equipementused_multiselect_' . $dispatchSuffix . '">' . $multiSelectEquipement . '</span>';
                    print '</td>';
                }

                // dispatch action
                print '<td name="' . $dispatchPrefix . 'action_' . $dispatchSuffix . '">';
				if ($dispactherList['line']===0) {
                    print img_picto($langs->trans('AddDispatchBatchLine'), 'split.png');
                    // on dispatcher img click
                    $outjs .= 'jQuery("td[name=\"' . $dispatchPrefix . 'action_' . $dispatchSuffix . '\"] img").click(function(){';
                    $outjs .= 'FactoryDispatcher.addLineFromDispatcher(' . $dispactherList['id'] . ',\'' . $dispactherList['name'] . '\', \'' . $dispactherList['mode'] . '\', ' . $dispactherList['unlock_qty'] . ', \'' . $dispactherList['element_type'] . '\', \'' . $dispactherList['element_data'] . '\');';
                    $outjs .= '});';
                }
                print '</td>';

                // on warehouse change
                $outjs .= 'jQuery("#' . $dispatchPrefix . 'id_entrepot_' . $dispatchSuffix . '").change(function(){';
                $outjs .= 'FactoryDispatcher.getAllEquipementInSelectedWarehouse(\'' . $dispactherList['id'] . '\', \'' . $dispactherList['name'] . '\', \'' . $dispactherList['line'] . '\', \'' . $dispactherList['element_data'] . '\');';
                $outjs .= '});';
				$outjsLineList[] = $outjs;
			}

            if (!array_key_exists($componentProductId, $componentProductIdList)) {
                $outjsQtyMadeChangeList[] = 'jQuery("#' . $dispatchPrefix . 'qtyused_' . $dispatchSuffix . '").val(this.value*jQuery("#' . $dispatchPrefix . 'qtyunit_' . $dispatchSuffix . '").val());';
            } else {
                $outjsQtyMadeChangeList[] = 'jQuery("#' . $dispatchPrefix .'qtyused_' . $dispatchSuffix . '").val(0);';
            }
            $componentProductIdList[$componentProductId] = $componentProductId;
		} else {
		    // qty used (consumed)
			print '<td align="right">'.$componentProductQtyUsed.'</td>';

			// qty deleted (lost)
			print '<td align="right">'.$componentProductQtyDeleted.'</td>';

            // equipment lost
            if (!empty($conf->equipement->enabled)) {
                print '<td>';
                // warehouse for lost quantity
                $entrepotLostId = $dispactherList['entrepotlost_id'];
                if ($entrepotLostId > 0) {
                    $entrepotLost = new Entrepot($db);
                    $entrepotLost->fetch($entrepotLostId);

                    print $entrepotLost->getNomUrl(1) . '<br />';
                }

                foreach ($dispactherList['equipementlost_id_list'] as $equipementId) {
                    $equipementLost = new Equipement($db);
                    $equipementLost->fetch($equipementId);

                    print '';
                    print $equipementLost->getNomUrl(1) . '<br />';
                }
                print '</td>';
            }

            // qty used and delete
			print '<td align="right">'.($componentProductQtyUsed+$componentProductQtyDeleted).'</td>';

            // equipment used
            if (!empty($conf->equipement->enabled)) {
                print '<td>';
                foreach ($dispactherList['equipementused_id_list'] as $equipementId) {
                    $equipementUsed = new Equipement($db);
                    $equipementUsed->fetch($equipementId);

                    print $equipementUsed->getNomUrl(1) . '<br />';
                }
                print '</td>';
            }

            // qty return in stock
			print '<td align="right">'.($componentProductQtyPlanned-($componentProductQtyUsed+$componentProductQtyDeleted)).'</td>';
		}
		print '</tr>';
	}
	print '</table>';

    // javascript
    if ($factory->statut == 1) {
        $out  = '<script type="text/javascript" language="javascript">';
        $out .= 'jQuery(document).ready(function(){';

        // add all js lines
        foreach ($outjsLineList as $outjs) {
            $out .= $outjs;
        }

        // on qty made change
        $out .= 'jQuery("#qtymade").on("change", function(){';
        foreach ($outjsQtyMadeChangeList as $outjsQtyMadeChange) {
            $out .=  $outjsQtyMadeChange;
        }
        $out .=  '});';

        $out .=  '});';
        $out .=  '</script>';

        print $out;
    }
}
print '</td>';
print '</tr></table>';

$parameters = array( 'colspan' => ' colspan="3"');
// Note that $action and $object may have been modified by
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $factory, $action);

/* Barre d'action		*/
if ($action == '') {
	print '<div class="tabsAction">';
	if ($user->rights->factory->creer && $factory->statut == 1) {
        print '<input type=submit class="butAction" value="'.$langs->trans("CloseFactory").'">';
    }
	print '</div>';
}

print '</form>';


print '<br><hr><br>';
print_fiche_titre($langs->trans("FactoryMovement"), '', '');
// list des mouvements associés à l'of

$productstatic=new Product($db);
$movement=new MouvementStock($db);
$form=new Form($db);

$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.fk_product_type as type,";
$sql.= " e.label as stock, e.rowid as entrepot_id, e.lieu,";
$sql.= " m.rowid as mid, m.value, m.datem, m.label, m.fk_origin, m.origintype";
//$sql.= ", m.inventorycode, m.batch, m.eatby, m.sellby";
$sql.= " FROM (".MAIN_DB_PREFIX."entrepot as e,";
$sql.= " ".MAIN_DB_PREFIX."product as p,";
$sql.= " ".MAIN_DB_PREFIX."stock_mouvement as m)";
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = e.rowid";
$sql.= " AND e.entity IN (".getEntity('stock', 1).")";
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
$sql.= " AND m.fk_origin = ".$id;
$sql.= " AND m.origintype = 'factory'";

$sql.= $db->order($sortfield, $sortorder);

//print $sql;

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$param='';
	if ($id) $param.='&id='.$id;
	print '<table class="noborder" width="100%">';
	print "<tr class='liste_titre'>";
	print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "m.datem", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ProductRef"), $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ProductLabel"), $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LabelMovement"), $_SERVER["PHP_SELF"], "m.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Units"), $_SERVER["PHP_SELF"], "m.value", "", $param, 'align="right"', $sortfield, $sortorder);
	print "</tr>\n";

	$arrayofuniqueproduct=array();

	$var=True;
	$i=0;
	while ($i < $num) {
		$objp = $db->fetch_object($resql);

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>'.dol_print_date($db->jdate($objp->datem), 'dayhour').'</td>';
		// Product ref
		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->product_ref;
		$productstatic->label=$objp->produit;
		$productstatic->type=$objp->type;
		print $productstatic->getNomUrl(1, '', 16);
		print "</td>\n";
		// Product label
		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->produit;
		$productstatic->type=$objp->type;
		print $productstatic->getNomUrl(1, '', 16);
		print "</td>\n";
		// Label of movement
		print '<td>'.$objp->label.'</td>';
		// Value
		print '<td align="right">';
		if ($objp->value > 0) print '+';
		print $objp->value.'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);
	print "</table></form><br>";
}

llxFooter();
$db->close();