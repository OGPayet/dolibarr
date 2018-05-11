<?php

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../../../main.inc.php'; // From "custom" directory
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';

dol_include_once('/ouvrage/class/ouvrage.class.php');
dol_include_once('/product/class/product.class.php');

global $langs, $user, $db;

$langs->load("ouvrage@ouvrage");

if (! empty($conf->margin->enabled)) {
    $langs->load('margins');
}

$ouvrage = new Ouvrage($db);
$ouvrage->fetch(GETPOST('id','alpha'));

$form = new Form($db);


$formmargin = new FormMargin($db);

// Ouvrage prix unitaire
$ouvragepu = 0;
foreach ($ouvrage->dets as $det) {

    $product = new Product($db);
    $product->fetch($det['product']);
    if ($product->status == 1) {
        $ouvragepu += $det['qty'] * floatval($product->price);
    }
}

?>
<table id="tablelinesouvrage" class="noborder noshadow" width="100%">
    <tbody>
        <tr class="liste_titre nodrag nodrop">
            <td class="linecoldescription"><?php echo $langs->trans('Description') ?></td>
            <td class="linecolvat"><?php echo $langs->trans('VAT') ?></td>
            <td class="linecoluht">P.U. HT</td>
            <td class="linecolqty">Qté</td>
            <td class="linecoldiscount">Réduc.</td>
            <?php if (! empty($conf->margin->enabled) && $user->rights->margins->creer) : ?>
            <td class="linecolmargin1 margininfos">Prix de revient</td>
            <?php endif ?>
            <td class="linecolht">Total HT</td>
        </tr>
        <tr>
            <td><?php echo $ouvrage->label ?><input type="hidden" name="ouvrage[id]" value="<?php echo GETPOST('id','alpha') ?>"/></td>
            <td><?php echo $form->load_tva("tva_tx_ouvrage",$ouvrage->tva,$mysoc,''); ?></td>
            <td class="ouvrage-price-unit"><?php echo $ouvragepu ?></td>
            <td><input type="number" name="ouvrage[qty]" size="2" value="1" step="1" min="0" style="max-width: 70px;"/></td>
            <td><input type="number" name="ouvrage[reduc]" size="2" value="0" step="1" min="0" max="100" />%</td>
            <?php if (! empty($conf->margin->enabled) && $user->rights->margins->creer) : ?>
            <td><input type="number" name="ouvrage[marge]" disabled="disabled" min="0" value="0" style="max-width: 70px;"/></td>
            <?php endif ?>
            <td class="ouvrage-price-total">0</td>
        </tr>

        <?php foreach ($ouvrage->dets as $det) : ?>
            <?php $product = new Product($db) ?>
            <?php $product->fetch($det['product']) ?>
        <?php if ($product->status == 1) : ?>
            <tr>
                <td><?php echo $product->label ?></td>
                <td class="product-tva"><?php echo $ouvrage->tva ?></td>
                <td class="product-price"><input type="number" size="5" name="ouvrage[product][<?php echo $det['product'] ?>][price_ht]" value="<?php echo round(floatval($product->price), 2) ?>" step="0.01" min="0" style="max-width: 70px;"></td>
                <td class="product-qty"><input type="number" data-default="<?php echo $det['qty'] ?>" size="2" name="ouvrage[product][<?php echo $det['product'] ?>][qty]" value="<?php echo $det['qty'] ?>" step="1" min="0"  style="max-width: 70px;" /></td>
                <td class="product-reduc"><input type="number" name="ouvrage[product][<?php echo $det['product'] ?>][reduc]" size="2" value="0" step="1" min="0" max="100"  style="max-width: 70px;"/>%</td>
                <?php if (! empty($conf->margin->enabled) && $user->rights->margins->creer) : ?>
                <td class="product-marge"><input type="number" name="ouvrage[product][<?php echo $det['product'] ?>][marge]" size="5" value="0" step="0.01" min="0" data-product="<?php echo $det['product'] ?>" style="max-width: 70px;"/></td>
                <?php endif ?>
                <td class="product-price-total"><?php echo price($product->price*$det['qty']) ?></td>
            </tr>
            <?php endif ?>
        <?php endforeach ?>



</table>