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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';

dol_include_once('/ouvrage/class/ouvrage.class.php');
dol_include_once('/product/class/product.class.php');
dol_include_once('/comm/propal/class/propal.class.php');
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/compta/facture/class/facture.class.php');

global $langs, $user, $db;

$langs->load("ouvrage@ouvrage");

if (!empty($conf->margin->enabled)) {
    $langs->load('margins');
}

$ouvrage = new Ouvrage($db);
$ouvrage->fetch(GETPOST('id', 'alpha'));
$ob = GETPOST('o');

if (!empty($ob)) {
    $object = new $ob['element']($db);
    $object->fetch($ob['id']);
}
$form = new Form($db);

$formmargin = new FormMargin($db);

// Ouvrage prix unitaire
$ouvragepu = 0;
foreach ($ouvrage->dets as $det) {
    $product = new Product($db);
    $product->fetch($det['product']);
    $pu_ht = $ouvrage->getPrice($product, $object);
    $ouvragepu += $det['qty'] * floatval($pu_ht);
}
$reduc = 0;
if (!empty($object->thirdparty->remise_percent)) {
    $reduc = $object->thirdparty->remise_percent;
}
?>

<table id="tablelinesouvrage" class="noborder nobackground" width="100%">
    <thead>
    <tr class="liste_titre nodrag nodrop" style="background-color: rgb(233,234,237) !important;">
        <?php if (!empty($conf->variants->enabled)) { ?>
            <td class="linecoldescription"><?php echo $langs->trans('Product') ?></td>
        <?php } ?>
        <td class="linecoldescription"><?php echo $langs->trans('Description') ?></td>
        <?php if (!empty($conf->global->PRODUCT_USE_UNITS)) { ?>
            <td class="linecolvat"><?php echo $langs->trans('Unit') ?></td>
        <?php } ?>
        <td class="linecolvat"><?php echo $langs->trans('VAT') ?></td>
        <td class="linecoluht">P.U. HT</td>
        <td class="linecolqty">Qté</td>
        <td class="linecoldiscount">Réduc.</td>
        <?php if (!empty($conf->margin->enabled) && $user->rights->margins->creer) : ?>
            <td class="linecolmargin1 margininfos">Prix de revient</td>
        <?php endif ?>
        <td class="linecolht">Total HT</td>
    </tr>
    <tr style="background-color: rgb(233,234,237) !important;">
        <td><?php echo $ouvrage->label ?><input type="hidden" name="ouvrage[id]"
                                                value="<?php echo GETPOST('id', 'alpha') ?>"/></td>
        <?php if (!empty($conf->variants->enabled)) { ?>
            <td></td>
        <?php } ?>
        <?php if (!empty($conf->global->PRODUCT_USE_UNITS)) { ?>
            <td><?php echo $form->selectUnits($ouvrage->fk_unit, "ouvrage[unit]", 0); ?></td>
        <?php } ?>
        <td><input type="hidden"
                   name="ouvrage[fk_product]"
                   value="<?php echo $ouvrage->fk_product; ?>"/><?php echo $form->load_tva("tva_tx_ouvrage", $ouvrage->fk_tva, $mysoc, ''); ?>
        </td>
        <td class="ouvrage-price-unit"><?php echo $ouvragepu ?></td>
        <td><input type="number" name="ouvrage[qty]" size="2" value="1"
                   step="<?php print $conf->global->OUVRAGE_QUANTITY_STEP ?>" min="0" style="max-width: 70px;"/>
        </td>
        <td><input type="number" name="ouvrage[reduc]" size="2" value="<?php echo $reduc ?>" step="0.01" min="0"
                   max="100"/>%
        </td>
        <?php if (!empty($conf->margin->enabled) && $user->rights->margins->creer) : ?>
            <td><input type="number" name="ouvrage[marge]" disabled="disabled" min="0" value="0"
                       style="max-width: 70px;"/></td>
        <?php endif ?>
        <td class="ouvrage-price-total">0</td>
    </tr>
    </thead>
    <tbody>
    <?php $i = 1; ?>
    <?php foreach ($ouvrage->dets as $det) : ?>
        <?php $product = new Product($db) ?>
        <?php $product->fetch($det['product']);
        $pu_ht = $ouvrage->getPrice($product, $object);

        $sql = "SELECT fk_prod_attr, fk_prod_attr_val ";
        $sql .= "FROM " . MAIN_DB_PREFIX . "product_attribute_combination2val as pacv ";
        $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "product_attribute as pa ";
        $sql .= "ON pacv.fk_prod_attr = pa.rowid ";
        $sql .= "WHERE fk_prod_combination = (";
        $sql .= "SELECT DISTINCT rowid FROM ";
        $sql .= MAIN_DB_PREFIX . "product_attribute_combination ";
        $sql .= "WHERE fk_product_child = " . $product->id . " ";
        $sql .= "AND entity = " . $conf->entity . ") ";
        $sql .= "AND entity = " . $conf->entity;

        $arrayCombinations = array();
        $parentID = -1;

        $resql = $db->query($sql);
        if ($resql) {
            while ($attr = $db->fetch_object($resql)) {
                $arrayCombinations[$attr->fk_prod_attr] = $attr->fk_prod_attr_val;
            }

            $sql = "SELECT DISTINCT fk_product_parent ";
            $sql .= "FROM " . MAIN_DB_PREFIX . "product_attribute_combination as pac ";
            $sql .= "WHERE fk_product_child = " . $product->id . " ";
            $sql .= "AND entity = " . $conf->entity;

            $resql = $db->query($sql);
            if ($resql) {
                while ($parent = $db->fetch_object($resql)) {
                    $parentID = $parent->fk_product_parent;
                }
            }

            if ($parentID == -1) {
                $sql = "SELECT DISTINCT fk_product_parent ";
                $sql .= "FROM " . MAIN_DB_PREFIX . "product_attribute_combination as pac ";
                $sql .= "WHERE fk_product_parent = " . $product->id . " ";
                $sql .= "AND entity = " . $conf->entity;

                $resql = $db->query($sql);
                if ($resql) {
                    while ($parent = $db->fetch_object($resql)) {
                        $parentID = $parent->fk_product_parent;
                    }
                }
            }
        }

        if ($product->status == 1) : ?>
            <tr style="background-color: <?php (($i % 2 != 0) ? print "#fff" : print "#eee"); ?> !important;">
                <?php
                if ($conf->variants->enabled) {
                    $conf->variants->enabled = 0;
                    ?>
                    <td><?php print $form->select_produits($product->id, 'ouvrageProductID' . $i, 0, 200000); ?></td>
                    <?php
                    $conf->variants->enabled = 1;
                }
                ?>
                <td id="product-label<?php print $i ?>"><?php echo $product->label ?></td>
                <?php if (!empty($conf->global->PRODUCT_USE_UNITS)) { ?>
                    <td><?php echo $form->selectUnits($product->fk_unit, "ouvrage[product][" . $det['product'] . "][unit]", 1); ?></td>
                <?php } ?>
                <td class="product-tva"><?php echo $ouvrage->tva ?></td>
                <td class="product-price">
                    <input type="number" size="5"
                           id="product-price<?php print $i ?>"
                           name="ouvrage[product][<?php echo $det['product'] ?>][price_ht]"
                           value="<?php echo round(floatval($pu_ht), 2) ?>" step="0.01" min="0"
                           style="max-width: 70px;">
                </td>
                <td class="product-qty">
                    <input type="number" data-default="<?php echo $det['qty'] ?>" size="2"
                           id="product-quantity<?php print $i ?>"
                           name="ouvrage[product][<?php echo $det['product'] ?>][qty]"
                           value="<?php echo $det['qty'] ?>" step="<?php print $conf->global->OUVRAGE_QUANTITY_STEP ?>"
                           min="0"
                           style="max-width: 70px;"/>
                </td>
                <td class="product-reduc">
                    <input type="number"
                           id="product-reduc<?php print $i ?>"
                           name="ouvrage[product][<?php echo $det['product'] ?>][reduc]" size="2"
                           value="<?php echo $reduc ?>" step="0.01" min="0" max="100"
                           style="max-width: 70px;"/>%
                </td>
                <?php if (!empty($conf->margin->enabled) && $user->rights->margins->creer) : ?>
                    <td class="product-marge">
                        <input type="number"
                               id="product-marge<?php print $i ?>"
                               name="ouvrage[product][<?php echo $det['product'] ?>][marge]"
                               size="5" value="0" step="0.01" min="0"
                               data-product="<?php echo $det['product'] ?>"
                               style="max-width: 70px;"/>
                    </td>
                <?php endif ?>
                <td class="product-price-total"
                    id="product-price-total"><?php echo price(($pu_ht * $det['qty']) - ($pu_ht * ($reduc / 100) * $det['qty'])) ?></td>
            </tr>
            <tr style="background-color: <?php (($i % 2 != 0) ? print "#fff" : print "#eee"); ?> !important; margin: 1%; margin-bottom: 2%;">
                <td colspan="8" id="variants_options_product<?php print $i; ?>"
                    style="display: none !important;height: 75px;">
                    <span id="variants_box<?php print $i; ?>"></span>
                    <button type="button"
                            id="addProduct<?php print $i; ?>">
                        <?php print $langs->transnoentitiesnoconv("Modify"); ?>
                    </button>
                </td>
            </tr>

        <?php if (!empty($conf->variants->enabled)) { ?>
            <script>
                selected = <?php echo json_encode($arrayCombinations) ?>;
                combvalues = {};

                if ("<?php print $parentID; ?>" !== "-1") {
                    $.getJSON("<?php echo dol_buildpath('/variants/ajax/getCombinations.php', 2) ?>", {
                        id: <?php print $parentID?>
                    }, function (data) {
                        $('#variants_box<?php print $i; ?>').empty();

                        $.each(data, function (key, val) {

                            combvalues[val.id] = val.values;

                            var span = $("#variants_box<?php print $i; ?>");

                            span = $(document.createElement('div')).text(val.label).css({
                                'font-weight': 'bold',
                                'display': 'table-cell',
                                'text-align': 'right'
                            });

                            var html = $(document.createElement('select')).attr('name', 'combinations[' + val.id + ']').css({
                                'margin-left': '15px',
                                'margin-right': '15px',
                                'white-space': 'pre'
                            }).append(
                                $(document.createElement('option')).val('')
                            );

                            selected = <?php echo json_encode($arrayCombinations) ?>;

                            $.each(combvalues[val.id], function (key, val) {
                                var tag = $(document.createElement('option')).val(val.id).html(val.value);

                                if (selected[val.fk_product_attribute] == val.id) {
                                    tag.attr('selected', 'selected');
                                }

                                html.append(tag);
                            });

                            span.append(html);

                            $('#variants_box<?php print $i;?>').append(span);
                        });
                        $("#variants_options_product<?php print $i; ?>").show();
                    });
                }

                $().ready(function () {
                    if (<?php print $conf->variants->enabled ?>) {
                        $("#search_ouvrageProductID<?php print $i ?>").prop("disabled", true);
                    }

                    $("#addProduct<?php print $i ?>").click(function () {
                        $.getJSON("<?php echo dol_buildpath('/variants/ajax/getCombinations.php', 2) ?>", {
                                id: <?php print $parentID ?>
                            },
                            function (data) {
                                var productREF = [];
                                var nbNull = 0;

                                for (i = data.length - 1; i >= 0; i--) {
                                    productREF[i] = $("#variants_box<?php print $i?> select[name='combinations[" + (i + 1) + "]']").val();
                                    if (productREF[i] == "") {
                                        nbNull++;
                                    }
                                }

                                if (nbNull == data.length) {
                                    productREF = null;
                                }

                                ajaxURL = "<?php echo dol_buildpath("custom/ouvrage/ajax/getProduct.php", 1); ?>";

                                $.ajax({
                                    url: ajaxURL,
                                    type: "POST",
                                    data: {
                                        "action": "getProductToAdd",
                                        "parentID": <?php print $parentID; ?>,
                                        "productREF": productREF,
                                        "socID": <?php print $object->socid; ?>,
                                    },
                                }).done(function (data) {
                                    data = JSON.parse(data);

                                    if (data.error != 0) {
                                        alert(data.message);
                                    } else {
                                        var valid = true;
                                        $('input[id^="ouvrageProductID"]').each(function () {
                                            if ($(this).val() === data.productID) {
                                                alert("<?php print $langs->transnoentitiesnoconv("OUVRAGE_PRODUCT_ALREADY_IN"); ?>");
                                                valid = false;
                                            }
                                        });

                                        if (!valid) {
                                            return;
                                        }

                                        $("#search_ouvrageProductID<?php print $i ?>").val(data.productREF);
                                        $("#ouvrageProductID<?php print $i ?>").val(data.productID);

                                        $("#product-label<?php print $i ?>").text(data.productLabel);

                                        $("#product-price<?php print $i ?>").val(data.productPrice);
                                        $("#product-price<?php print $i ?>").trigger("change");
                                        $("#product-price<?php print $i ?>").attr('name', 'ouvrage[product][' + data.productID + '][price_ht]');

                                        $("#product-quantity<?php print $i ?>").attr('name', 'ouvrage[product][' + data.productID + '][qty]');

                                        $("#product-reduc<?php print $i ?>").val(data.productDiscount);
                                        $("#product-reduc<?php print $i ?>").trigger("change");
                                        $("#product-reduc<?php print $i ?>").attr('name', 'ouvrage[product][' + data.productID + '][reduc]');

                                        $("#product-marge<?php print $i ?>").val(data.productCost);
                                        $("#product-marge<?php print $i ?>").trigger("change");
                                        $("#product-marge<?php print $i ?>").attr('name', 'ouvrage[product][' + data.productID + '][marge]');
                                    }
                                }).fail(function () {
                                    alert("<?php print $langs->transnoentitiesnoconv("OUVRAGE_SERVER_ERROR"); ?>");
                                });
                            }
                        );
                    });
                });
                <?php if ($selected): ?>
                $("#<?php echo 'ouvrageProductID' . $i ?>").change();
                <?php endif ?>
            </script>
        <?php } ?>

            <?php $i++; ?>
        <?php endif ?>
    <?php endforeach ?>

    </tbody>
</table>