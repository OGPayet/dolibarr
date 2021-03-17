<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include '../main.inc.php';
}     // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include '../../main.inc.php';
}   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include '../../../main.inc.php';
}   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php")) {
    $res = @include '../../../dolibarr/htdocs/main.inc.php';
}     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) {
    $res = @include '../../../../dolibarr/htdocs/main.inc.php';
}   // Used on dev env only
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
dol_include_once('/ouvrage/class/ouvrage.class.php');
dol_include_once('/ouvrage/lib/ouvrage_work.lib.php');
dol_include_once('/product/class/product.class.php');

global $langs, $user;

$langs->load("ouvrage@ouvrage");

$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$desc = GETPOST('description', 'alpha');
if (!empty($conf->global->PRODUCT_USE_UNITS)) {
    $fk_unit = GETPOST('fk_unit', 'int');
}
$fk_product = GETPOST('fk_product', 'int');
$label = GETPOST('label', 'alpha');
$tva_tx = GETPOST('fk_tva');
$confirm = GETPOST('confirm', 'alpha');
$products = GETPOST('products');
$quantity = GETPOST('quantity');

$object = new Ouvrage($db);
$extrafields = new ExtraFields($db);
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
$object_type = $conf->global->OUVRAGE_TYPE;

if ($id > 0 || !empty($ref)) {
    $object->fetch($id, $ref);
    if ($object->id > 0) {
        $object->fetch_optionals();
    }

    $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));


}

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';  // Must be include, not include_once.
include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';
// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha')) {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
}


if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}


// Delete a product
if ($action == 'confirm_delete' && $confirm != 'yes') {
    $action = '';
}

if ($action == 'confirm_delete' && $confirm == 'yes') {
    $result = $object->delete($object->id);

    if ($result > 0) {
        setEventMessages($langs->trans($conf->global->OUVRAGE_TYPE . "OUVRAGEDELETED"), null, 'mesgs');
        header('Location: ' . dol_buildpath('/ouvrage/list.php', 1));

        exit;
    } else {
        setEventMessages($langs->trans($object->error), null, 'errors');
        $reload = 0;
        $action = '';
    }
}

if ($action == 'update_extras') {
    // Fill array 'array_options' with data from add form
    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
    $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
    if ($ret < 0) {
        $error++;
    }

    if (!$error) {
        // Actions on extra fields (by external module or standard code)
        // TODO le hook fait double emploi avec le trigger !!
        $hookmanager->initHooks(array('worksdao'));
        $parameters = array('id' => $object->id);
        $reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $object,
            $action); // Note that $action and $object may have been modified by
        // some hooks
        if (empty($reshook)) {
            $result = $object->insertExtraFields();
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }
        } else {
            if ($reshook < 0) {
                $error++;
            }
        }
    }

    if ($error) {
        $action = 'edit_extras';
    }
}


// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->ouvrage->write) {
    if (!GETPOST('clone_ref')) {
        setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
    } else {
        if ($object->id > 0) {
            // Because createFromClone modifies the object, we must clone it so that we can restore it later
            $orig = clone $object;

            $result = $object->createFromClone(GETPOST('clone_ref'));
            if ($result > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
                exit;
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
                $object = $orig;
            }
        }
    }

    $action = '';
}


// Add a product or service
if ($action == 'add' && $user->rights->ouvrage->write) {
    $error = 0;

    if (!empty(GETPOST('cancel'))) {
        header("Location: " . dol_buildpath('ouvrage/list.php', 1));
        exit;
    }

    if (!$error) {
        $object->ref = $ref;
        $object->label = $label;
        $object->description = $desc;
        $object->fk_tva = $tva_tx;
        if (empty($conf->global->PRODUCT_USE_UNITS)) {
            $object->fk_unit = $fk_unit;
        }
        $object->fk_product = $fk_product;
        $object->entity = $conf->entity;
        $object->array_options = array();

        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
        $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));

        if (count($products) > 0) {
            $order = 1;
            if ($products != null) {
                foreach ($products as $k => $product) {
                    $object->addProduct($product, $quantity[$k], $order++);
                }
            }
        }

        $id = $object->create($user);

        if ($id > 0) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
            exit;
        } else {
            if (count($object->errors)) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                setEventMessages($langs->trans($object->error), null, 'errors');
            }

            $action = "create";
            header("Location: " . $_SERVER['PHP_SELF'] . "?action=" . $action);
        }
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?action=create");
        exit;
    }
}

// Update Ouvrage
if ($action == 'update' && $user->rights->ouvrage->write) {

    $object->ref = $ref;
    $object->label = $label;
    $object->description = $desc;
    $object->fk_tva = $tva_tx;

    if (empty($conf->global->PRODUCT_USE_UNITS)) {
        $object->fk_unit = $fk_unit;
    }
    $object->fk_product = $fk_product;
    $error = 0;

    $object->fetch_optionals();
    $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));


    if (!GETPOST('label')) {
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), null, 'errors');
        $action = "edit";
        $error++;
    }

    if (empty($ref)) {
        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Ref')), null, 'errors');
        $action = "edit";
        $error++;
    }

    if (!$error) {

        $object->dets = array();

        if (count($products) > 0) {
            $order = 1;
            foreach ($products as $k => $product) {
                $object->addProduct($product, $quantity[$k], $order++);
            }
        }

        if ($object->update($id, $user)) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
            exit;
        } else {
            if (count($object->errors)) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                setEventMessages($langs->trans($object->error), null, 'errors');
            }

            $action = "edit";
        }
    }
    $action = "edit";
}

$morejs = array("/ouvrage/js/ouvragecard.js.php");

if (((int)DOL_VERSION) >= '7' && !empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
    $morejs = array("/ouvrage/js/ouvrageCardHideChild.js.php");
} else if (((int)DOL_VERSION) >= '7') {
    $morejs = array("/ouvrage/js/ouvragecardv7.js.php");
}

$morecss = array("/ouvrage/css/ouvragecard.css");
$form = new Form($db);

if ($action == 'create' && $user->rights->ouvrage->write) {
    llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE . 'NEW_OUVRAGE'), '', '', '', '', $morejs, $morecss, 0, 0);

    dol_fiche_head($head, 'card', $titre, -1, $picto);
    print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="type" value="' . $type . '">' . "\n";

    print '<div class="div-table-responsive">';
    print '<table class="border centpercent">';

    // Common attributes
    include 'core/tpl/commonfields_add.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    if (empty($conf->global->PRODUCT_USE_UNITS)) {
        echo "<script>
            $().ready(function () {
                $('#fk_unit').parent().parent().hide(); 
            });
        </script>";
    }

    /*
    print '<tr>';


    print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("Ref") . '</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="' . dol_escape_htmltag(GETPOST('ref') ? GETPOST('ref') : $tmpcode) . '">';
    if ($refalreadyexists) {
        print $langs->trans("RefAlreadyExists");
    }

    print '</td></tr>';

    // Label
    print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td colspan="3"><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="' . dol_escape_htmltag(GETPOST('label')) . '"></td></tr>';

    // Description
    print '<tr><td class="tdtop">' . $langs->trans("Description") . '</td><td colspan="3">';

    $doleditor = new DolEditor('desc', GETPOST('desc'), '', 160, 'dolibarr_full', '', false, true,
        $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_4, '90%');
    $doleditor->Create();
    print "</td></tr>";
    print '</tr>';

    //TVA
    print '<tr><td>' . $langs->trans("VATRate") . '</td><td>';
    print $form->load_tva("tva_tx", -1, $mysoc, '');
    print '</td></tr>';
*/
    //Pro
    print '<tr>';
    print '<td ></td>';// . /*$langs->trans('ProductAndService')*/ . '</td>';
    print '<td>';
    print '<div class="ouvrageProductServiceContainer"><ul id="sortable"></ul></div>';
    print '</td>';
    print '</tr>' . "\n";

    //Product
    if ($conf->global->MAIN_MODULE_PRODUCT) {
        print '<tr>';
        print '<td>' . $langs->trans('AddProduct') . '</td>';
        print '<td>';
        print $form->select_produits('', 'productid', 0, 200000);
        print '<button class="btn btn-secondary" style="margin-left: 2%; display: none !important;" id="addProduct" type="button">' . $langs->trans("Add") . '</button>';
        print '</td>';
        print '</tr>' . "\n";
    }

    print '<tr>';
    print '<td></td>';
    print '<td>';
    print '<div id="attributes_box"></div>';
    print '</td>';
    print '</tr>';


    //Product
    if ($conf->global->MAIN_MODULE_SERVICE) {
        print '<tr>';
        print '<td>' . $langs->trans('AddService') . '</td>';
        print '<td>';
        print $form->select_produits('', 'serviceid', 1, 200000);
        print '</td>';
        print '</tr>' . "\n";
    }

    print '</table>';
    print '</div>';
    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
    print '</div>';

    print '</form>';
}

if ($action == 'update_extras' || $action == 'edit_extras' || $action == 'view' || $action == '' || $action == 'clone' && $user->rights->ouvrage->write) {

    llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE . 'FICHEOUVRAGE'), '', '', '', '', $morejs, $morecss, 0, 0);


    $res = $object->fetch_optionals();
    $head = workPrepareHead($object);
    dol_fiche_head($head, 'card', $langs->trans("Work"), -1, $object->picto);
    $linkback = '<a href="' . dol_buildpath('/ouvrage/list.php',
            1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';

    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';

    print '<div class="underbanner clearboth"></div>';

    print '<div class="div-table-responsive">';
    print '<table class="border centpercent">' . "\n";
    // Common attributes
    //$keyforbreak='fieldkeytoswitchonsecondcolumn';
    include 'core/tpl/commonfields_view.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
    print '</table>';
    print '</div>';


    print '<div style="margin-top: 10%">';
    print '<div class="div-table-responsive">';
    print '<table class="border centpercent">' . "\n";
    print '<tr>'
        . '<td width="20%"><strong>' . $langs->trans("Product") . ' / ' . $langs->trans("Service") . '</strong></td>'
        . '<td class="center"><strong>' . $langs->trans("Quantity") . '</strong></td>';
    $product = new Product($db);

    foreach ($object->dets as $det) {
        $product->fetch($det['product']);
        echo '<tr>'
            . '<td>' . $product->label . ' (' . $product->ref . ') </td>'
            . '<td class="center">' . $det['qty'] . '</td>'
            . '</tr>';
    }
    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    echo '<div class="tabsAction">';
    if ($user->rights->ouvrage->write) {
        echo '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&amp;id=' . $object->id . '">' . $langs->trans("Modify") . '</a></div>';
    }

    // Clone
    if ($user->rights->ouvrage->write) {
        print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=clone">' . $langs->trans("ToClone") . '</a></div>';
    }

    if ($user->rights->ouvrage->delete) {
        if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)) {
            print '<div class="inline-block divButAction"><span id="action-delete" class="butActionDelete">' . $langs->trans('Delete') . '</span></div>' . "\n";
        } else {
            print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?action=delete&amp;id=' . $object->id . '">' . $langs->trans("Delete") . '</a></div>';
        }
    }

    print '</div>';
    print '</div>';
}

if ($action == 'edit' && $object->id > 0 && $user->rights->ouvrage->write) {
    llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE . 'EDITOUVRAGE'), '', '', '', '', $morejs, $morecss, 0, 0);

    $res = $object->fetch_optionals();

    $head = workPrepareHead($object);
    dol_fiche_head($head, 'card', $langs->trans("Work"), -1, $object->picto);
    print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';

    print '<div class="div-table-responsive">';
    print '<table class="border centpercent">';
    // Common attributes
    include 'core/tpl/commonfields_edit.tpl.php';

    if (empty($conf->global->PRODUCT_USE_UNITS)) {
        echo "<script>
            $().ready(function () {
                $('#fk_unit').parent().parent().hide(); 
            });
        </script>";
    }

    // Other attributes
   // include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    //Pro
    print '<tr>';
    print '<td></td>';// . /*$langs->trans('ProductAndService')*/ . '</td>';
    print '<td>';
    print '<div class="ouvrageProductServiceContainer"><ul id="sortable">';
    $product = new Product($db);
    foreach ($object->dets as $det) {
        $product->fetch($det['product']);
        echo '<li class="ui-state-default" style="cursor: grab;"><span class="icon-drag-drop"></span>'
            . '<input type="hidden" name="products[]" value="' . $product->id . '">'
            . $product->ref . ' - ' . $product->label;
        if (!empty($conf->global->PRODUCT_USE_UNITS) && !empty($product->fk_unit)) {
            if ((int)DOL_VERSION >= 12) {
                echo ' - ' . $langs->trans($product->getLabelOfUnit('short')) . ' - ';
            } else {
                echo ' - ' . $langs->trans('unit' . strtoupper($product->getLabelOfUnit('short'))) . ' - ';
            }
        }
        echo '<input type="number" step="' . $conf->global->OUVRAGE_QUANTITY_STEP . '" min="0" name="quantity[]" value="' . $det['qty'] . '" length="4" style="width:5vw;"/><a href="#" class="delete">X</a>'
            . '</li>';
    }

    print '</ul></div>';
    print '</td>';
    print '</tr>' . "\n";

    //Product
    if ($conf->global->MAIN_MODULE_PRODUCT) {
        print '<tr>';
        print '<td>' . $langs->trans('AddProduct') . '</td>';
        print '<td>';
        print $form->select_produits('', 'productid', 0, 200000);
        print '<button class="btn btn-secondary" style="margin-left: 2%; display:none !important;" id="addProduct" type="button">' . $langs->trans("Add") . '</button>';
        print '</td>';
        print '</tr>' . "\n";
    }

    print '<tr>';
    print '<td></td>';
    print '<td>';
    print '<div id="attributes_box"></div>';
    print '</td>';
    print '</tr>';

    //Service
    if ($conf->global->MAIN_MODULE_SERVICE) {
        print '<tr>';
        print '<td>' . $langs->trans('AddService') . '</td>';
        print '<td>';
        print $form->select_produits('', 'serviceid', 1, 200000);
        print '</td>';
        print '</tr>' . "\n";
    }

    print '</table>';
    print '</div>';
    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
    print '</div>';

    print '</form>';
}

// Confirm delete product
if (($action == 'delete' && $user->rights->ouvrage->write && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))    // Output when action = clone if jmobile or no js
    || (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))                            // Always output when not jmobile nor js
{
    print $form->formconfirm("card.php?id=" . $object->id,
        $langs->trans($conf->global->OUVRAGE_TYPE . "DeleteOuvrage"),
        $langs->trans($conf->global->OUVRAGE_TYPE . "DeleteOuvrageConfirmation"), "confirm_delete", '', 0,
        "action-delete");
}

// Confirm clone
if ($action == 'clone') {
    // Create an array for form
    $formquestion = array(
        // 'text' => $langs->trans("ConfirmClone"),
        // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
        // 1),
        // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
        // => 1),
        array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("Ref"), 'value' => $object->ref . '.1')
    );
    // Paiement incomplet. On demande si motif = escompte ou autre*/
    print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id,
        $langs->trans($conf->global->OUVRAGE_TYPE . 'Clone'),
        $langs->trans($conf->global->OUVRAGE_TYPE . 'ConfirmClone', $object->ref), 'confirm_clone', $formquestion,
        'yes', 1);
}

if (empty($conf->global->PRODUCT_USE_UNITS)) {
    echo "<script>
        $().ready(function () {
            $('td.titlefield.fieldname_fk_unit').parent().hide();
        });
    </script>";
}
?>

    <style>
        .ouvrageProductServiceContainer span.icon-drag-drop {
            background: url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png' ?>) no-repeat 50% 50%;
        }
    </style>

<?php
llxFooter();
