<?php
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php"))
    $res = @include '../main.inc.php';     // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php"))
    $res = @include '../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include '../../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res)
    die("Include of main fails");

dol_include_once('/product/class/product.class.php');

dol_include_once('/variants/class/ProductCombination.class.php');

global $db, $user, $mysoc;

$action = GETPOST("action");
$parentID = GETPOST("parentID");
$productREF = GETPOST("productREF");
$socID = GETPOST("socID");

function resetIndex($productREF)
{
    $return = array();
    for ($i = 0; $i < count($productREF); $i++) {
        if (!empty($productREF[$i])) {
            $return[$i + 1] = $productREF[$i];
        }
    }

    return $return;
}

if ($action == "getProduct") {
    if ($parentID == null) {
        $response = array(
            "message" => $langs->transnoentitiesnoconv("OUVRAGE_NO_PARENT_ID_WAS_PASSED"),
            "error" => 1,
        );
        echo json_encode($response);
        exit();
    }

    if ($productREF != null) {
        $productCombination = new ProductCombination($db);
        $productCombination = $productCombination->fetchByProductCombination2ValuePairs($parentID, resetIndex($productREF));

        $product = new Product($db);
        $product->fetch($productCombination->fk_product_child);
        if ($product->id != null) {
            $response = array(
                "productID" => $product->id,
                "productLabel" => $product->ref . " - " . $product->label,
                "error" => 0,
            );
        } else {
            $response = array(
                "message" => $langs->transnoentitiesnoconv("OUVRAGE_NO_PRODUCT_FOUND"),
                "error" => 2,
            );
        }
    } else {
        $product = new Product($db);
        $product->fetch($parentID);
        if ($product->id != null) {
            $response = array(
                "productID" => $product->id,
                "productLabel" => $product->ref . " - " . $product->label,
                "error" => 0,
            );
        } else {
            $response = array(
                "message" => $langs->transnoentitiesnoconv("OUVRAGE_NO_PRODUCT_FOUND"),
                "error" => 3,
            );
        }
    }

    echo json_encode($response);
    exit();
}

if ($action == "getProductToAdd") {
    $soc = new Societe($db);
    $soc->fetch($socID);

    if ($parentID == -1) {
        $response = array(
            "message" => $langs->transnoentitiesnoconv("OUVRAGE_NO_PRODUCT_REF_WAS_PASSED"),
            "error" => 1,
        );
        echo json_encode($response);
        exit();
    }

    if ($productREF != null) {
        $productCombination = new ProductCombination($db);
        $productCombination = $productCombination->fetchByProductCombination2ValuePairs($parentID, resetIndex($productREF));

        $product = new Product($db);
        $product->fetch($productCombination->fk_product_child);

        if ($product->id != null) {
            if ((int)DOL_VERSION <= 9) {
                if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
                    require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

                    $prodcustprice = new Productcustomerprice($db);

                    $filter = array('t.fk_product' => $prod->id, 't.fk_soc' => $object->thirdparty->id);

                    $result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
                    if ($result) {
                        if (count($prodcustprice->lines) > 0) {
                            $productPrice = price($prodcustprice->lines[0]->price);
                        }
                    }
                } else {
                    $productPrice = $product->price;
                }
            } else {
                $productPrice = price2num($product->getSellPrice($mysoc, $soc)["pu_ht"], 2);
            }
            $response = array(
                "productID" => $product->id,
                "productREF" => $product->ref,
                "productLabel" => $product->label,
                "productPrice" => $productPrice,
                "productDiscount" => (($soc->remise_percent == null) ? 0 : $soc->remise_percent),
                "productCost" => (($product->cost_price == null) ? 0 : $product->cost_price),
                "error" => 0,
            );
        } else {
            $response = array(
                "message" => $langs->transnoentitiesnoconv("OUVRAGE_NO_PRODUCT_FOUND"),
                "error" => 2,
            );
        }
    } else {
        $product = new Product($db);
        $product->fetch($parentID);
        if ($product->id != null) {
            $response = array(
                "productID" => $product->id,
                "productREF" => $product->ref,
                "productLabel" => $product->ref . " - " . $product->label,
                "error" => 0,
            );
        } else {
            $response = array(
                "message" => $langs->transnoentitiesnoconv("OUVRAGE_NO_PRODUCT_FOUND"),
                "error" => 3,
            );
        }
    }

    echo json_encode($response);
    exit();
}