<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2016      Florian Henry         <florian.henry@atm-consulting.fr>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under the	terms of the GNU General Public	License	as published by
 * the Free Software Foundation; either	version	2 of the License, or
 * (at your option) any later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * \file htdocs/fourn/commande/dispatch.php
 * \ingroup commande
 * \brief Page to dispatch receiving
 */

namespace CORE;

// Load Dolibarr environment
$res  = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res  = @include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp  = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i    = strlen($tmp) - 1;
$j    = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include(substr($tmp, 0, ($i + 1))."/main.inc.php");
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php");
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include("../main.inc.php");
if (!$res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (!$res && file_exists("../../../../main.inc.php")) $res = @include("../../../../main.inc.php");
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include("../../../../../main.inc.php");
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.dispatch.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';


namespace CORE\WAREHOUSECHILD;

dol_include_once('/warehousechild/class/html.formproduct.class.php');
dol_include_once('/warehousechild/class/fournisseur.commande.class.php');
dol_include_once('/warehousechild/class/html.formwarehousechild.class.php');

//use \CommandeFournisseur as CommandeFournisseur;
use \ExtraFields as ExtraFields;
use \Form as Form;
//use FormProduct as FormProduct;
use \FormProjets as FormProjets;
use \ProductFournisseur as ProductFournisseur;
use \Entrepot as Entrepot;
use \CommandeFournisseurDispatch as CommandeFournisseurDispatch;
use \Societe as Societe;
use \User as User;
use \WarehouseschildForm as WarehouseschildForm;

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');
$langs->load('warehousechild@warehousechild');
if (! empty($conf->productbatch->enabled))
	$langs->load('productbatch');

// Security check
$id = GETPOST("id", 'int');
$ref = GETPOST('ref');
$lineid = GETPOST('lineid', 'int');
$action = GETPOST('action','aZ09');
$confirm = GETPOST('confirm','alpha');

if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

if (empty($conf->stock->enabled)) {
	accessforbidden();
}

// Recuperation de l'id de projet
$projectid = 0;
if ($_GET["projectid"])
	$projectid = GETPOST("projectid", 'int');

$object = new CommandeFournisseur($db);

if ($id > 0 || ! empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$result = $object->fetch_thirdparty();
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * Actions
 */

if ($action == 'checkdispatchline' && ! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error=0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (! $result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (! $error)
	{
		$result = $supplierorderdispatch->setStatut(1);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}

	if (! $error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'uncheckdispatchline' && ! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error=0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (! $result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (! $error)
	{
		$result = $supplierorderdispatch->setStatut(0);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'denydispatchline' && ! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error=0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (! $result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (! $error)
	{
		$result = $supplierorderdispatch->setStatut(2);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

$dispatchLineList         = array(); // list of dispatched lines
$equipementExistsLineList = array(); // list of equipment id supplier already exists (created)
$nbEquipementExists       = 0;
if ($action == 'dispatch' && $user->rights->fournisseur->commande->receptionner) {

    // module to create equipment
    if ($conf->equipement->enabled) {
        dol_include_once('/equipement/class/equipement.class.php');

        if (! empty($conf->global->EQUIPEMENT_ADDON)
            && is_readable(dol_buildpath("/equipement/core/modules/equipement/" . $conf->global->EQUIPEMENT_ADDON . ".php")))
            dol_include_once("/equipement/core/modules/equipement/" . $conf->global->EQUIPEMENT_ADDON . ".php");
    }

    $error = 0;
    $notrigger = 0;

    // check all posted values
    $dispatchLineProductList = array();
    foreach ($_POST as $key => $value) {
        $isProduct      = FALSE;
        $isProductBatch = FALSE;
        $numTour = 0;
        $index   = 0;

        // without batch module enabled
        if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
            $isProduct = TRUE;
            $numTour   = intval($reg[1]);
            $index     = intval($reg[2]);
        }

        // with batch module enabled
        if (preg_match('/^product_batch_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
            $isProductBatch = TRUE;
            $numTour        = intval($reg[1]);
            $index          = intval($reg[2]);
        }

        // line product or product batch
        if ($isProduct || $isProductBatch) {
            $suffix = '_' . $numTour . '_' . $index;

            $fkCommandeFournisseurLine = GETPOST('fk_commandefourndet' . $suffix, 'int');
            $fkProduct  = GETPOST('product' . $suffix, 'int');
            $qtyOrdered = GETPOST('qty_ordered' . $suffix, 'int');
            $qtyToDispatch = GETPOST('qty' . $suffix);

            // product
            $dispatchProduct = new \Product($db);
            $dispatchProduct->fetch($fkProduct);
            $productToSerialize = $dispatchProduct->array_options['options_synergiestech_to_serialize'];
            $dispatchLineProductList[$key] = $dispatchProduct;

            // add dispatch line
            if (!isset($dispatchLineList[$index])) $dispatchLineList[$index] = array();
            $dispatchLineList[$index][$numTour] = array('fk_commande_fournisseurdet' => $fkCommandeFournisseurLine, 'qty_ordered' => $qtyOrdered, 'qty_to_dispatch' => $qtyToDispatch);

            if ($productToSerialize) {
                $serialFournArray = GETPOST('serialfourn' . $suffix, 'array');
                $serialMethod = GETPOST('serialmethod' . $suffix, 'int');

                // only for external serial numbers
                if ($serialMethod == 2) {
                    if (!isset($equipementExistsLineList[$index])) $equipementExistsLineList[$index] = array();
                    if (!isset($equipementExistsLineList[$index][$numTour])) $equipementExistsLineList[$index][$numTour] = array('equipement_list' => array());

                    // check all equipments selected
                    foreach ($serialFournArray as $serialFourn) {
                        if (!empty($serialFourn)) {
                            // check if equipment already exists for this supplier and sent (no warehouse)
                            $sql = "SELECT e.rowid";
                            $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as e";
                            $sql .= " WHERE e.ref = '" . $db->escape($serialFourn) . "'";
                            $sql .= " AND e.fk_soc_fourn = " . $object->thirdparty->id;
                            $sql .= " AND e.fk_entrepot IS NULL";
                            $sql .= " AND e.entity = " . getEntity('equipement');

                            $resql = $db->query($sql);
                            if (!$resql) {
                                $error++;
                                $object->error = $db->lasterror();
                                $object->errors = $object->error;
                            } else {
                                $num = $db->num_rows($resql);
                                if ($num > 0) {
                                    $obj = $db->fetch_object($resql);

                                    $equipementExists = new \Equipement($db);
                                    $equipementExists->fetch($obj->rowid);

                                    $equipementExistsLineList[$index][$numTour]['equipement_list'][] = $equipementExists;

                                    $nbEquipementExists++; // nb equipments already exist
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // we can create all equipments
    if (!$error && ($nbEquipementExists<=0 || !empty($confirm))) {
        $db->begin();

        $pos = 0;
        foreach ($dispatchLineProductList as $key => $dispatchProduct) {
            $isProduct = FALSE;
            $isProductBatch = FALSE;
            $numTour = 0;
            $index   = 0;

            // without batch module enabled
            if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
                $isProduct = TRUE;
                $numTour   = intval($reg[1]);
                $index     = intval($reg[2]);
            }

            // with batch module enabled
            if (preg_match('/^product_batch_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
                $isProductBatch = TRUE;
                $numTour        = intval($reg[1]);
                $index          = intval($reg[2]);
            }

            // line product or product batch
            if ($isProduct || $isProductBatch) {
                $pos++;
                $suffix = '_' . $numTour . '_' . $index;

                $fkCommandeFournisseurLine = GETPOST('fk_commandefourndet' . $suffix, 'int');
                $commandeFournisseurLinePU = GETPOST('pu' . $suffix); // this is unit price including discount
                $fkProduct = GETPOST('product' . $suffix, 'int');
                $qtyToDispatch = GETPOST('qty' . $suffix);
                $fkEntrepot = GETPOST('entrepot' . $suffix, 'int');

                $dispatchLot = '';
                $dDLUO = '';
                $dDLC = '';
                if ($isProductBatch) {
                    $dispatchLot = GETPOST('lot_number' . $suffix, 'alpha');
                    $dDLUO = dol_mktime(12, 0, 0, $_POST['dluo' . $suffix . 'month'], $_POST['dluo' . $suffix . 'day'], $_POST['dluo' . $suffix . 'year']);
                    $dDLC = dol_mktime(12, 0, 0, $_POST['dlc' . $suffix . 'month'], $_POST['dlc' . $suffix . 'day'], $_POST['dlc' . $suffix . 'year']);
                }

                // product
                $productToSerialize = $dispatchProduct->array_options['options_synergiestech_to_serialize'];

                // equipment
                if ($productToSerialize) {
                    $equipementExistsList = $equipementExistsLineList[$index][$numTour]['equipement_list'];

                    $objectequipement = new \Equipement($db);
                    $objectequipement->fk_product = $fkProduct;
                    $objectequipement->fk_entrepot = $fkEntrepot;
                    $serialFournArray = GETPOST('serialfourn' . $suffix, 'array');
                    $objectequipement->SerialMethod = GETPOST('serialmethod' . $suffix, 'int');
                    $objectequipement->numversion = GETPOST('numversion' . $suffix, 'alpha');
                    /*
                    $datee = dol_mktime(
                        '23', '59', '59',
                        $_POST["datee" . $suffix . "month"],
                        $_POST["datee" . $suffix . "day"],
                        $_POST["datee" . $suffix . "year"]
                    );
                    $objectequipement->datee = $datee;
                    $dateo = dol_mktime(
                        '23', '59', '59',
                        $_POST["dateo" . $suffix . "month"],
                        $_POST["dateo" . $suffix . "day"],
                        $_POST["dateo" . $suffix . "year"]
                    );
                    $objectequipement->dateo = $dateo;
                    */
                }

                // set line for errors
                $errorLine = $langs->transnoentities('Product') . ' ' . $dispatchProduct->ref . ' - ' . $langs->transnoentities('Line') . ' ' . ($numTour + 1);

                // we ask to move a qty
                if ($qtyToDispatch != 0) {
                    if (!($fkEntrepot > 0)) {
                        $error++;
                        dol_syslog('No dispatch for line ' . $key . ' as no warehouse choosed', LOG_ERR);
                        $text = $langs->transnoentities('Warehouse') . ', ' . $errorLine;
                        $object->error = $langs->trans('ErrorFieldRequired', $text);
                        $object->errors[] = $object->error;
                    }

                    if ($isProductBatch) {
                        if (!($dispatchLot || $dDLUO || $dDLC)) {
                            $error++;
                            dol_syslog('No dispatch for line ' . $key . ' as serial/eat-by/sellby date are not set', LOG_ERR);
                            $text = $langs->transnoentities('atleast1batchfield') . ', ' . $errorLine;
                            $object->error = $langs->trans('ErrorFieldRequired', $text);
                            $object->errors[] = $object->error;
                        }
                    }

                    if (!$error) {
                        if ($qtyToDispatch < 0) {
                            $comment = $langs->trans("WarehousechildSupplierOrderDispatchCorrect", $object->ref);
                        } else {
                            $comment = GETPOST('comment');
                        }
                    }

                    if (!$error) {
                        // dispatch product with movements
                        $commandeFournisseurDispatchId = $object->dispatchProduct($user, $fkProduct, $qtyToDispatch, $fkEntrepot, $commandeFournisseurLinePU, $comment, $dDLC, $dDLUO, $dispatchLot, $fkCommandeFournisseurLine, $notrigger);
                        if ($commandeFournisseurDispatchId < 0) {
                            $error++;
                        }
                    }
                }

                // create or remove equipment
                if (!$error && $productToSerialize) {
                    $qtyEquipementToDispatch = $qtyToDispatch;

                    // only for external serial numbers
                    if ($objectequipement->SerialMethod == 2) {
                        if ($confirm == 'yes') {
                            // remove all equipment already exists
                            foreach ($serialFournArray as $serialFournKey => $serialFournRef) {
                                foreach ($equipementExistsList as $equipementExists) {
                                    if ($serialFournRef == $equipementExists->ref) {
                                        // return equipment in the warehouse
                                        $equipementExists->fk_commande_fourn = $object->id;
                                        $equipementExists->fk_commande_fournisseur_dispatch = $commandeFournisseurDispatchId;
                                        $equipementExists->description = $langs->trans("SupplierOrder") . ":" . $object->ref . '<br />' . $equipementExists->description;
                                        $equipementExists->fk_entrepot = $fkEntrepot;

                                        // update supplier order
                                        $ret = $equipementExists->updateSupplierOrder($user);
                                        if ($ret < 0) {
                                            $error++;
                                            $object->error    = $equipementExists->error;
                                            $object->errors[] = $object->error;
                                        }

                                        // add event line for this equipement
                                        $now = dol_now();
                                        $equipementExistsEvtType = dol_getIdFromCode($db, 'RECEPT', 'c_equipementevt_type', 'code', 'rowid');
                                        $ret = $equipementExists->addline(
                                            $equipementExists->id,
                                            $equipementExistsEvtType,
                                            $langs->trans('WarehousechildEquipementForceReplaceInSelectedWarehouse', $equipementExists->getNomUrl(1)),
                                            $now,
                                            $now,
                                            '',
                                            '',
                                            '',
                                            '',
                                            '',
                                            '',
                                            '',
                                            ''
                                        );
                                        if ($ret < 0) {
                                            $error++;
                                            $object->error    = $equipementExists->error;
                                            $object->errors[] = $object->error;
                                        }

                                        if (!$error) {
                                            unset($serialFournArray[$serialFournKey]);
                                            $qtyEquipementToDispatch--;
                                        }
                                    }

                                    if ($error) {
                                        break;
                                    }
                                }
                            }
                        }
                        $objectequipement->SerialFourn = implode(';', $serialFournArray); // serial numbers to create (external only)
                    }

                    if (!$error) {
                        if ($qtyEquipementToDispatch > 0) {
                            $objectequipement->fk_soc_fourn = $object->thirdparty->id;
                            $objectequipement->author = $user->id;
                            $objectequipement->description = $langs->trans("SupplierOrder") . ":" . $object->ref;
                            $objectequipement->fk_commande_fourn = $object->id;
                            $objectequipement->fk_commande_fournisseur_dispatch = $commandeFournisseurDispatchId;

                            // selon le mode de serialisation de l'equipement
                            switch ($objectequipement->SerialMethod) {
                                case 1 : // en mode generation auto, on cree des numeros de series internes
                                    $objectequipement->quantity = 1;
                                    $objectequipement->nbAddEquipement = $qtyEquipementToDispatch;
                                    break;
                                case 2 : // en mode generation a partir de la liste on determine en fonction de la saisie
                                    $objectequipement->quantity = 1;
                                    $objectequipement->nbAddEquipement = $qtyEquipementToDispatch; // sera calcule en fonction
                                    break;
                                case 3 : // en mode gestion de lot
                                    $objectequipement->quantity = $qtyEquipementToDispatch;
                                    $objectequipement->nbAddEquipement = 1;
                                    break;
                            }

                            // create equipment
                            $result = $objectequipement->create();
                            if ($result < 0) {
                                $error++;
                                $object->error = $errorLine . ' : ' . $objectequipement->error;
                                $object->errors[] = $object->error;
                            }
                        } else if ($qtyEquipementToDispatch < 0) {
                            $serialFournRemoveArray = GETPOST('serialfourn_remove' . $suffix, 'array') ? GETPOST('serialfourn_remove' . $suffix, 'array') : array();

                            // check if quanity to dispatch matches with serialFournRemoveArray
//                            if (count($serialFournRemoveArray) != abs($qtyEquipementToDispatch)) {
//                                $error++;
//                                $object->error = $errorLine . ' : ' . $langs->trans('WarehousechildErrorSupplierOrderDispatchLineIncorrectRemoveQty');
//                                $object->errors[] = $object->error;
//                            } else {
                                foreach ($serialFournRemoveArray as $serialFournRemove) {
                                    // find equipement by serial number
                                    $equipementToRemove = new \Equipement($db);

                                    $result = $equipementToRemove->fetch($serialFournRemove);
                                    if ($result < 0) {
                                        $error++;
                                        $object->error = $errorLine . ' : ' . $langs->trans('WarehousechildErrorSupplierOrderDispatchLineIncorrectEquipmentRef');
                                        $object->errors[] = $object->error;
                                        break;
                                    }

                                    // check if equipment reference correspond to this supplier order and product and already dispatched
                                    if ($equipementToRemove->fk_commande_fourn != $object->id || $equipementToRemove->fk_product != $fkProduct || (!($equipementToRemove->fk_commande_fournisseur_dispatch > 0))) {
                                        $error++;
                                        $object->error = $errorLine . ' : ' . $langs->trans('WarehousechildErrorSupplierOrderDispatchLineEquipmentRefNotMatch');
                                        $object->errors[] = $object->error;
                                        break;
                                    }

                                    // remove this equipment (change id of supplier order dispatch line and set warehouse to null)
                                    $equipementToRemove->fk_commande_fournisseur_dispatch = $commandeFournisseurDispatchId;
                                    $result = $equipementToRemove->setFkCommandeFournisseurDispatch($user);
                                    if ($result < 0) {
                                        $error++;
                                        $object->error = $errorLine . ' : ' . $langs->trans('WarehousechildErrorSupplierOrderDispatchLineImpossibleToChangeDispatchLine');
                                        $object->errors[] = $object->error;
                                        break;
                                    }

                                    $result = $equipementToRemove->set_entrepot($user, -1);
                                    if ($result < 0) {
                                        $error++;
                                        $object->error = $errorLine . ' : ' . $langs->trans('WarehousechildErrorSupplierOrderDispatchLineImpossibleToChangeWarehouse');
                                        $object->errors[] = $object->error;
                                        break;
                                    }
                                }
//                            }
                        }
                    }
                }
            }
        }

        if (! $error) {
            // modify status before recalculate
            $result = $object->setStatus($user, 3);
            if ($result < 0) {
                $error++;
            }
        }

        if (! $error) {
            $result = $object->calcAndSetStatusDispatch($user, GETPOST('closeopenorder')?1:0, GETPOST('comment'));
            if ($result < 0) {
                $error ++;
            }
        }

        if (! $notrigger && ! $error) {
            global $conf, $langs, $user;
            // Call trigger

            $result = $object->call_trigger('ORDER_SUPPLIER_DISPATCH', $user);
            // End call triggers

            if ($result < 0) {
                $error++;
            }
        }

        // commit or rollback
        if ($error) {
            $db->rollback();
        } else {
            $db->commit();
        }
    }

	if ($error) {
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
	    if ($nbEquipementExists<=0 || !empty($confirm)) {
            // Modification - Open-DSI - Begin - Hack for the redirect to set equipments if has products serializable
            //if (!isset($object->context['workflow_to_serialize'])) {
            //    header("Location: dispatch.php?id=" . $id);
            //}
            // Modification - Open-DSI - End - Hack for the redirect to set equipments if has products serializable

            header("Location: " . $_SERVER['SELF_PHP'] . "?id=" . $id);
            exit();
        }
    }
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$warehouseschildForm = new WarehouseschildForm($db);
$formproduct = new FormProduct($db);
$warehouse_static = new Entrepot($db);
$supplierorderdispatch = new CommandeFournisseurDispatch($db);

$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
//llxHeader('', $langs->trans("Order"), $help_url, '', 0, 0, array('/fourn/js/lib_dispatch.js'));
llxHeader('', $langs->trans("Order"), $help_url, '', 0, 0, array('/warehousechild/js/lib_dispatch.js?sid=' . dol_now()));

if ($id > 0 || ! empty($ref)) {
	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = ordersupplier_prepare_head($object);

	$title = $langs->trans("SupplierOrder");
	dol_fiche_head($head, 'dispatch', $title, -1, 'order');


	// form confirm
    $formconfirm = '';

    // dispatch equipement form confirm
    if ($action=='dispatch' && empty($confirm) && $nbEquipementExists>0) {
        $question = '';
        $question .= $langs->trans('WarehousechildEquipementAlreadyExistsList') . ' : ' . '<br />';
        foreach ($equipementExistsLineList as $equipementExistsLine) {
            foreach ($equipementExistsLine as $equipementExistsLineArray) {
                $equipementExistsArray = $equipementExistsLineArray['equipement_list'];

                foreach ($equipementExistsArray as $equipementExists) {
                    $question .= ' - ' . $equipementExists->ref . '<br />';
                }
            }
        }
        $question .= $langs->trans('WarehousechildEquipementReplaceInSelectedWarehouse');

        $formconfirmQuestion = array();

        $formconfirm = $warehouseschildForm->formconfirm_submit($_SERVER['PHP_SELF'] . "?id=" . $object->id, $langs->trans("WarehousechildEquipementExistsConfirm"), $question, 'dispatch', $formconfirmQuestion, 'no', 1, 200, 500, 'form_dispatch');
    }

    print $formconfirm;


	// Supplier order card

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
	// Ref supplier
	$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->fournisseur->commande->creer)
	    {
	        if ($action != 'classify')
	            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	            $morehtmlref.=' : ';
			if ($action == 'classify') {
	                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	                $morehtmlref.='<input type="hidden" name="action" value="classin">';
	                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	                $morehtmlref.='</form>';
	            } else {
	                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	            }
	    } else {
	        if (! empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
	            $morehtmlref.=$proj->ref;
	            $morehtmlref.='</a>';
	        } else {
	            $morehtmlref.='';
	        }
	    }
	}
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Date
	if ($object->methode_commande_id > 0) {
		print '<tr><td class="titlefield">' . $langs->trans("Date") . '</td><td>';
		if ($object->date_commande) {
			print dol_print_date($object->date_commande, "dayhourtext") . "\n";
		}
		print "</td></tr>";

		if ($object->methode_commande) {
			print '<tr><td>' . $langs->trans("Method") . '</td><td>' . $object->getInputMethod() . '</td></tr>';
		}
	}

	// Auteur
	print '<tr><td>' . $langs->trans("AuthorRequest") . '</td>';
	print '<td>' . $author->getNomUrl(1, '', 0, 0, 0) . '</td>';
	print '</tr>';

	print "</table>";

	print '</div>';

	// if ($mesg) print $mesg;
	print '<br>';

	$disabled = 1;
	if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
		$disabled = 0;

	// Line of orders
	if ($object->statut <= 2 || $object->statut >= 6) {
		print $langs->trans("OrderStatusNotReadyToDispatch");
	}

	if ($object->statut == 3 || $object->statut == 4 || $object->statut == 5) {
		$entrepot = new Entrepot($db);
		$listwarehouses = $entrepot->list_array(1);

		print '<form name="form_dispatch" method="POST" action="dispatch.php?id=' . $object->id . '">';
        print '<input type="hidden" id="form_dispatch_confirm" name="confirm" value="' . $confirm . '" />';
		print '<input type="hidden" id="fk_commande_fourn" name="fk_commande_fourn" value="' . $object->id  . '" />';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
		print '<input type="hidden" name="action" value="dispatch" />';
        print '<input type="hidden" id="url_to_get_supplier_order_dispatch_equipement" name="url_to_get_supplier_order_dispatch_equipement" value="' .  dol_buildpath('/warehousechild/ajax/supplier_order_dispatch_equipement.php', 1) . '" />';

		print '<div class="div-table-responsive">';
		print '<table class="noborder" width="100%">';

		// Set $products_dispatched with qty dispatched for each product id
		$products_dispatched = array();
		$sql = "SELECT l.rowid, cfd.fk_product, sum(cfd.qty) as qty";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet as l on l.rowid = cfd.fk_commandefourndet";
		$sql .= " WHERE cfd.fk_commande = " . $object->id;
		$sql .= " GROUP BY l.rowid, cfd.fk_product";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$objd = $db->fetch_object($resql);
					$products_dispatched[$objd->rowid] = price2num($objd->qty, 5);
					$i++;
				}
			}
			$db->free($resql);
		}

		// no dipatch line
        if (empty($dispatchLineList)) {
            $sql = "SELECT l.rowid, l.fk_product, l.subprice, l.remise_percent, SUM(l.qty) as qty,";
            $sql .= " p.ref, p.label, p.tobatch";
            $sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as l";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON l.fk_product=p.rowid";
            $sql .= " WHERE l.fk_commande = " . $object->id;
            if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
                $sql .= " AND l.product_type = 0";
            $sql .= " GROUP BY p.ref, p.label, p.tobatch, l.rowid, l.fk_product, l.subprice, l.remise_percent"; // Calculation of amount dispatched is done per fk_product so we must group by fk_product
            $sql .= " ORDER BY p.ref, p.label";

            $resql = $db->query($sql);
            if (!$resql) {
                dol_print_error($db);
            } else {
                $index = 0;
                $numTour = 0;

                while ($objp = $db->fetch_object($resql)) {
                    if ($objp->fk_product > 0) {
                        $remaintodispatch = price2num($objp->qty - (( float ) $products_dispatched[$objp->rowid]), 5); // calculation of dispatched
                        if ($remaintodispatch < 0)
                            $remaintodispatch = 0;

                        if (!isset($dispatchLineList[$index]))   $dispatchLineList[$index] = array();
                        $dispatchLineList[$index][$numTour] = array('fk_commande_fournisseurdet' => $objp->rowid, 'qty_ordered' => $objp->qty, 'qty_to_dispatch' => $remaintodispatch);
                        $index++;
                    }
                }

                $db->free($resql);
            }
        }

        if (!empty($dispatchLineList)) {
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Description") . '</td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '<td align="right">' . $langs->trans("QtyOrdered") . '</td>';
            print '<td align="right">' . $langs->trans("QtyDispatchedShort") . '</td>';
            print '<td align="right">' . $langs->trans("QtyToDispatchShort") . '</td>';
            print '<td width="32"></td>';
            print '<td align="center">' . $langs->trans("Warehouse") . '</td>';
            print '<td align="center">' . $langs->trans('EquipmentSerialMethod') . '</td>';
            print '<td align="left">' . $langs->trans('ExternalSerial') . '</td>';
            print '<td align="left">' . $langs->trans('VersionNumber') . '</td>';
            print "</tr>\n";

            if (! empty($conf->productbatch->enabled)) {
                print '<tr class="liste_titre">';
                print '<td></td>';
                print '<td>' . $langs->trans("batch_number") . '</td>';
                print '<td>' . $langs->trans("EatByDate") . '</td>';
                print '<td>' . $langs->trans("SellByDate") . '</td>';
                print '<td colspan="5">&nbsp;</td>';
                print "</tr>\n";
            }

            $nbproduct = 0; // Nb of predefined product lines to dispatch (already done or not) if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is off (default)
            $outjs = '';
            foreach ($dispatchLineList as $index => $dispatchLine) {
                // supplier order line
                $dispatchCommandeFournisseurLine = new \CommandeFournisseurLigne($db);
                $dispatchCommandeFournisseurLine->fetch($dispatchLine[0]['fk_commande_fournisseurdet']);
                $commandeFournisseurLineId            = $dispatchCommandeFournisseurLine->id;
                $commandeFournisseurLineSubprice      = $dispatchCommandeFournisseurLine->subprice;
                $commandeFournisseurLineRemisePercent = $dispatchCommandeFournisseurLine->remise_percent;

                // order qty
                $qtyOrdered = $dispatchLine[0]['qty_ordered'];

                // product
                $dispatchProduct = new \Product($db);
                $dispatchProduct->fetch($dispatchCommandeFournisseurLine->fk_product);
                $fkProduct          = $dispatchCommandeFournisseurLine->fk_product;
                $productRef         = $dispatchProduct->ref;
                $productLabel       = $dispatchProduct->label;
                $productToBatch     = $dispatchProduct->status_batch;
                $productToSerialize = $dispatchProduct->array_options['options_synergiestech_to_serialize'];

                $remaintodispatch = price2num($qtyOrdered - (( float ) $products_dispatched[$commandeFournisseurLineId]), 5); // Calculation of dispatched
                if ($remaintodispatch < 0)
                    $remaintodispatch = 0;


                if ($remaintodispatch || empty($conf->global->SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED)) {
                    $nbproduct++;

                    print "\n";
                    print '<!-- Line to dispatch ' . '_0_' . $index . ' -->' . "\n";
                    print '<tr class="oddeven">';

                    $linktoprod = '<a href="' . DOL_URL_ROOT . '/product/fournisseurs.php?id=' . $fkProduct . '">' . img_object($langs->trans("ShowProduct"), 'product') . ' ' . $productRef . '</a>';
                    $linktoprod .= ' - ' . $productLabel . "\n";

                    if (! empty($conf->productbatch->enabled)) {
                        if ($productToBatch) {
                            print '<td colspan="4">';
                            print $linktoprod;
                            print "</td>";
                        } else {
                            print '<td>';
                            print $linktoprod;
                            print "</td>";
                            print '<td colspan="3">';
                            print $langs->trans("ProductDoesNotUseBatchSerial");
                            print '</td>';
                        }
                    } else {
                        print '<td colspan="4">';
                        print $linktoprod;
                        print "</td>";
                    }

                    // Define unit price for PMP calculation
                    $up_ht_disc = $commandeFournisseurLineSubprice;
                    if (! empty($commandeFournisseurLineRemisePercent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP))
                        $up_ht_disc = price2num($up_ht_disc * (100 - $commandeFournisseurLineRemisePercent) / 100, 'MU');

                    // qty ordered
                    print '<td align="right">' . $qtyOrdered . '</td>';

                    // already dispatched
                    print '<td align="right">' . $products_dispatched[$commandeFournisseurLineId] . '</td>';

                    if (! empty($conf->productbatch->enabled) && $productToBatch == 1) {
                        $type = 'batch';
                        print '<td></td>'; // Qty to dispatch
                        print '<td></td>'; // Dispatch column
                        print '<td></td>'; // Warehouse column
                        print '<td></td>'; // serial method
                        print '<td></td>'; // serial fourn
                        print '<td></td>'; // num version
                    } else {
                        $type = 'dispatch';
                        print '<td></td>'; // Qty to dispatch
                        print '<td></td>'; // Dispatch column
                        print '<td></td>'; // Warehouse column
                        print '<td></td>'; // serial method
                        print '<td></td>'; // serial fourn
                        print '<td></td>'; // num version
                    }

                    print '</tr>';

                    foreach ($dispatchLine as $numTour => $dispatch) {
                        $suffix = '_' . $numTour . '_' . $index;
                        $qtyToDispatch = $dispatch['qty_to_dispatch'];

                        print '<tr class="oddeven" name="' . $type . $suffix . '">';

                        if ($type == 'btach') {
                            // type batch
                            print '<td>';
                            print '<input name="fk_commandefourndet' . $suffix . '" type="hidden" value="' . $commandeFournisseurLineId . '">';
                            print '<input name="product_batch' . $suffix . '" type="hidden" value="' . $fkProduct . '">';

                            print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
                            if (! empty($conf->global->SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT)) // Not tested !
                            {
                                print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu' . $suffix . '" type="text" value="' . price2num($up_ht_disc, 'MU') . '">';
                            }
                            else
                            {
                                print '<input class="maxwidth75" name="pu' . $suffix . '" type="hidden" value="' . price2num($up_ht_disc, 'MU') . '">';
                            }

                            // hidden fields for js function
                            print '<input id="qty_ordered' . $suffix . '" type="hidden" value="' . $qtyOrdered . '">';
                            print '<input id="qty_dispatched' . $suffix . '" type="hidden" value="' . ( float ) $products_dispatched[$commandeFournisseurLineId] . '">';
                            print '</td>';

                            print '<td>';
                            print '<input type="text" class="inputlotnumber" id="lot_number' . $suffix . '" name="lot_number' . $suffix . '" size="40" value="' . GETPOST('lot_number' . $suffix) . '">';
                            print '</td>';
                            print '<td>';
                            $dlcdatesuffix = dol_mktime(0, 0, 0, GETPOST('dlc' . $suffix . 'month'), GETPOST('dlc' . $suffix . 'day'), GETPOST('dlc' . $suffix . 'year'));
                            $form->select_date($dlcdatesuffix, 'dlc' . $suffix, '', '', 1, "");
                            print '</td>';
                            print '<td>';
                            $dluodatesuffix = dol_mktime(0, 0, 0, GETPOST('dluo' . $suffix . 'month'), GETPOST('dluo' . $suffix . 'day'), GETPOST('dluo' . $suffix . 'year'));
                            $form->select_date($dluodatesuffix, 'dluo' . $suffix, '', '', 1, "");
                            print '</td>';
                            print '<td colspan="2">&nbsp</td>'; // Qty ordered + qty already dispatached
                        } else {
                            // type dispatch
                            print '<td colspan="6">';
                            print '<input name="fk_commandefourndet' . $suffix . '" type="hidden" value="' . $commandeFournisseurLineId . '">';
                            print '<input id="product' . $suffix . '" name="product' . $suffix . '" type="hidden" value="' . $fkProduct . '">';

                            print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
                            if (!empty($conf->global->SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT)) // Not tested !
                            {
                                print $langs->trans("BuyingPrice") . ': <input class="maxwidth75" name="pu' . $suffix . '" type="text" value="' . price2num($up_ht_disc, 'MU') . '">';
                            } else {
                                print '<input class="maxwidth75" name="pu' . $suffix . '" type="hidden" value="' . price2num($up_ht_disc, 'MU') . '">';
                            }

                            // hidden fields for js function
                            print '<input type="hidden" id="qty_ordered' . $suffix . '" name="qty_ordered' . $suffix . '" value="' . $qtyOrdered . '">';
                            print '<input type="hidden" id="qty_dispatched' . $suffix . '" name="qty_dispatched' . $suffix . '" value="' . ( float )$products_dispatched[$commandeFournisseurLineId] . '">';
                            print '</td>';
                        }

                        // qty to dispatch
                        print '<td align="right">';
                        print '<input id="qty' . $suffix . '" name="qty' . $suffix . '" type="text" size="8" value="' . (GETPOST('qty' . $suffix) != '' ? GETPOST('qty' . $suffix) : $qtyToDispatch) . '">';
                        print '</td>';

                        // dispatch button
                        print '<td>';
                        if ($type == 'batch') {
                            // type batch
                            print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLineWarehousechild(' . $index . ',\'' . $type . '\')"');
                        } else {
                            // type dispatch
                            print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLineWarehousechild(' . $index . ',\'' . $type . '\', \'qtymissing\', true, ' . ($productToSerialize == 1 ? 1 : 0) . ')"');
                        }
                        print '</td>';

                        // warehouse
                        print '<td align="right">';
                        if (count($listwarehouses) >= 1) {
                            print $formproduct->selectWarehouses(GETPOST("entrepot" . $suffix), "entrepot" . $suffix, '', 0, 0, $fkProduct, '', 1);
                        } else {
                            $langs->load("errors");
                            print $langs->trans("ErrorNoWarehouseDefined");
                        }
                        print "</td>\n";

                        // serial method
                        print '<td id="td_serialmethod' . $suffix . '" align="center" valign="top">';
                        if ($productToSerialize == 1) {
                            $dispatchSerialMethod = GETPOST('serialmethod' . $suffix, 'int') ? GETPOST('serialmethod' . $suffix, 'int') : $conf->global->EQUIPEMENT_DEFAULTSERIALMODE;
                            $arraySerialMethod = array(
                                1 => $langs->trans("InternalSerial"),
                                2 => $langs->trans("ExternalSerial"),
                                3 => $langs->trans("SeriesMode")
                            );
                            print $form->selectarray("serialmethod" . $suffix, $arraySerialMethod, $dispatchSerialMethod, 0, 0, 0, '', 0, 0, ($qtyToDispatch <= 0 ? 1 : 0));
                        }
                        print '</td>';

                        // serial fourn
                        print '<td id="td_serialfourn' . $suffix . '" valign="top">';
                        if ($productToSerialize == 1) {
                            if ($qtyToDispatch < 0) {
                                $dispatchSerialFournRemoveArray = GETPOST('serialfourn_remove' . $suffix, 'array') ? GETPOST('serialfourn_remove' . $suffix, 'array') : array();
                                $outjs .= 'getSupplierOrderDispatchEquipementWarehousechild(' . $numTour . ', ' . $index . ', ' . json_encode($dispatchSerialFournRemoveArray) . ');';
                            } else if ($qtyToDispatch > 0) {
                                $dispatchSerialFournArray = GETPOST('serialfourn' . $suffix, 'array') ? GETPOST('serialfourn' . $suffix, 'array') : array();
                                for ($numSerialFourn = 0; $numSerialFourn < $qtyToDispatch; $numSerialFourn++) {
                                    $dispatchSerialFourn = isset($dispatchSerialFournArray[$numSerialFourn]) ? $dispatchSerialFournArray[$numSerialFourn] : '';
                                    print '<input type="text" class="serialfourn' . $suffix . '" name="serialfourn' . $suffix . '[]" value="' . $dispatchSerialFourn . '" /><br />';
                                }
                            }
                        }
                        print '</td>';

                        // num version
                        print '<td id="td_numversion' . $suffix . '">';
                        if ($productToSerialize == 1) {
                            $dispatchNumversion = GETPOST('numversion' . $suffix, 'alpha') ? GETPOST('numversion' . $suffix, 'alpha') : '';
                            print '<input type="text" id="numversion' . $suffix . '" name="numversion' . $suffix . '" value="' . $dispatchNumversion . '"' . ($qtyToDispatch <= 0 ? ' disabled' : '') . '/>';
                        }
                        print '</td>';

                        print '</tr>';

                        // change qty to disptach
                        $outjs .= 'jQuery("#qty' . $suffix . '").change(function(){';
                        $outjs .= ' addInputSerialFournWarehousechild(' . $numTour . ', ' . $index . ');';
                        $outjs .= '});';

                        // change warehouse
                        $outjs .= 'jQuery("#entrepot' . $suffix . '").change(function(){';
                        $outjs .= ' addInputSerialFournWarehousechild(' . $numTour . ', ' . $index . ');';
                        $outjs .= '});';

                        // disable input serial fourn for method internal and series
                        $outjs .= 'disableInputSerialFournWarehousechild(' . $numTour . ', ' . $index . ');';
                        $outjs .= 'jQuery("#serialmethod' . $suffix . '").change(function(){';
                        $outjs .= ' disableInputSerialFournWarehousechild(' . $numTour . ', ' . $index . ');';
                        $outjs .= '});';
                    }
                }
            }

            // javascript code
            if (!empty($outjs)) {
                print '<script type="text/javascript">';
                print 'jQuery(document).ready(function(){';
                print $outjs;
                print '});';
                print '</script>';
            }
        }

		print "</table>\n";
		print '</div>';
		print "<br>\n";

		if ($nbproduct)
		{
            $checkboxlabel=$langs->trans("CloseReceivedSupplierOrdersAutomatically", $langs->transnoentitiesnoconv($object->statuts[5]));

			print '<br><div class="center">';
            print $langs->trans("Comment") . ' : ';
			print '<input type="text" class="minwidth400" maxlength="128" name="comment" value="';
			print $_POST["comment"] ? GETPOST("comment") : $langs->trans("DispatchSupplierOrder", $object->ref);
			// print ' / '.$object->ref_supplier; // Not yet available
			print '" class="flat"><br>';

			print '<input type="checkbox" checked="checked" name="closeopenorder"> '.$checkboxlabel;

			print '<br><input type="submit" class="button" value="' . $langs->trans("DispatchVerb") . '"';
			if (count($listwarehouses) <= 0)
				print ' disabled';
			print '>';
			print '</div>';
		}

		// Message if nothing to dispatch
		if (! $nbproduct) {
			if (empty($conf->global->SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED))
				print '<div class="opacitymedium">'.$langs->trans("NoPredefinedProductToDispatch").'</div>';		// No predefined line at all
			else
				print '<div class="opacitymedium">'.$langs->trans("NoMorePredefinedProductToDispatch").'</div>';	// No predefined line that remain to be dispatched.
		}

		print '</form>';
	}

	dol_fiche_end();


	// List of lines already dispatched
	$sql = "SELECT p.ref, p.label,";
	$sql .= " e.rowid as warehouse_id, e.label as entrepot,";
	$sql .= " cfd.rowid as dispatchlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status";
	$sql .= " FROM " . MAIN_DB_PREFIX . "product as p,";
	$sql .= " " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e ON cfd.fk_entrepot = e.rowid";
	$sql .= " WHERE cfd.fk_commande = " . $object->id;
	$sql .= " AND cfd.fk_product = p.rowid";
	$sql .= " ORDER BY cfd.rowid ASC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num > 0) {
			print "<br>\n";

			print load_fiche_titre($langs->trans("ReceivingForSameOrder"));

			print '<div class="div-table-responsive">';
			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("Product") . '</td>';
			if (! empty($conf->productbatch->enabled)) {
				print '<td>' . $langs->trans("batch_number") . '</td>';
				print '<td>' . $langs->trans("EatByDate") . '</td>';
				print '<td>' . $langs->trans("SellByDate") . '</td>';
			}
			print '<td align="right">' . $langs->trans("QtyDispatched") . '</td>';
			print '<td></td>';
			print '<td>' . $langs->trans("Warehouse") . '</td>';
			print '<td>' . $langs->trans("Comment") . '</td>';
			if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS))
				print '<td align="center" colspan="2">' . $langs->trans("Status") . '</td>';
			print "</tr>\n";

			$var = false;

			while ( $i < $num ) {
				$objp = $db->fetch_object($resql);

				print "<tr " . $bc[$var] . ">";
				print '<td>';
				print '<a href="' . DOL_URL_ROOT . '/product/fournisseurs.php?id=' . $objp->fk_product . '">' . img_object($langs->trans("ShowProduct"), 'product') . ' ' . $objp->ref . '</a>';
				print ' - ' . $objp->label;
				print "</td>\n";

				if (! empty($conf->productbatch->enabled)) {
					print '<td>' . $objp->batch . '</td>';
					print '<td>' . dol_print_date($db->jdate($objp->eatby), 'day') . '</td>';
					print '<td>' . dol_print_date($db->jdate($objp->sellby), 'day') . '</td>';
				}

				// Qty
				print '<td align="right">' . $objp->qty . '</td>';
				print '<td>&nbsp;</td>';

				// Warehouse
				print '<td>';
				$warehouse_static->id = $objp->warehouse_id;
				$warehouse_static->libelle = $objp->entrepot;
				print $warehouse_static->getNomUrl(1);
				print '</td>';

				// Comment
				print '<td class="tdoverflowmax300">' . $objp->comment . '</td>';

				// Status
				if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) {
					print '<td align="right">';
					$supplierorderdispatch->status = (empty($objp->status) ? 0 : $objp->status);
					// print $supplierorderdispatch->status;
					print $supplierorderdispatch->getLibStatut(5);
					print '</td>';

					// Add button to check/uncheck disaptching
					print '<td align="center">';
					if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check)))
					{
						if (empty($objp->status)) {
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Approve") . '</a>';
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Deny") . '</a>';
						} else {
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Disapprove") . '</a>';
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Deny") . '</a>';
						}
					} else {
						$disabled = '';
						if ($object->statut == 5)
							$disabled = 1;
						if (empty($objp->status)) {
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=checkdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Approve") . '</a>';
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=denydispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Deny") . '</a>';
						}
						if ($objp->status == 1) {
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=uncheckdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Reinit") . '</a>';
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=denydispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Deny") . '</a>';
						}
						if ($objp->status == 2) {
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=uncheckdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Reinit") . '</a>';
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=checkdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Approve") . '</a>';
						}
					}
					print '</td>';
				}

				print "</tr>\n";

				$i ++;
				$var = ! $var;
			}
			$db->free($resql);

			print "</table>\n";
			print '</div>';
		}
	} else {
		dol_print_error($db);
	}
}

llxFooter();

$db->close();
