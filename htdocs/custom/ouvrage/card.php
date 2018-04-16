<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../main.inc.php'; // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
dol_include_once('/ouvrage/class/ouvrage.class.php');
dol_include_once('/product/class/product.class.php');

global $langs, $user;

$langs->load("ouvrage@ouvrage");

$action=(GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$desc=GETPOST('desc', 'alpha');
$label=GETPOST('label', 'alpha');
$tva_tx=GETPOST('tva_tx', 'alpha');
$confirm=GETPOST('confirm', 'alpha');

$products = GETPOST('products');
$quantity = GETPOST('quantity');

$ouvrage = new Ouvrage($db);

$ouvrage_type = $conf->global->OUVRAGE_TYPE;

if ($id > 0) {
    $ouvrage->fetch($id);
}
// Delete a product
    if ($action == 'confirm_delete' && $confirm != 'yes') { $action=''; }
    if ($action == 'confirm_delete' && $confirm == 'yes')
    {


        $result = $ouvrage->delete($ouvrage->id);

        if ($result > 0)
        {
            setEventMessages($langs->trans($conf->global->OUVRAGE_TYPE."OUVRAGEDELETED"), null, 'mesgs');
            header('Location: '.dol_buildpath('/ouvrage/list.php', 1));

            exit;
        }
        else
        {
		setEventMessages($langs->trans($object->error), null, 'errors');
            $reload = 0;
            $action='';
        }
    }

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->Ouvrage->{$langs->trans("RightsO21")})
{

    if (!GETPOST('clone_ref'))
    {
            setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
    }
    else
    {

        if ($ouvrage->id > 0)
        {
                // Because createFromClone modifies the object, we must clone it so that we can restore it later
                $orig = clone $ouvrage;

                $result=$ouvrage->createFromClone(GETPOST('clone_ref'));
                if ($result > 0)
                {
                        header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
                        exit;
                }
                else
                {
                        setEventMessages($ouvrage->error, $ouvrage->errors, 'errors');
                        $ouvrage = $orig;
                }
        }
    }
    $action='';
}



// Add a product or service
if ($action == 'create')
{
    $error=0;

    if (!GETPOST('label'))
    {
        setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('Label')), null, 'errors');
        $action = "create";
        $error++;
    }
    if (empty($ref))
    {
        setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('Ref')), null, 'errors');
        $action = "create";
        $error++;
    }

    if (! $error) {
        $ouvrage->ref = $ref;
        $ouvrage->label = $label;
        $ouvrage->description = $desc;
        $ouvrage->tva = $tva_tx;

        if (count($products) > 0) {
            $order = 1;
            foreach ($products as $k=>$product) {
                $ouvrage->addProduct($product, $quantity[$k], $order++);
            }
        }

        $id = $ouvrage->create($user);


        if ($id > 0) {
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
            exit;
        } else  {
            if (count($ouvrage->errors)) {
                setEventMessages($ouvrage->error, $ouvrage->errors, 'errors');
            } else {
                setEventMessages($langs->trans($ouvrage->error), null, 'errors');
            }
            $action = "create";
            header("Location: ".$_SERVER['PHP_SELF']."?action=".$action);
        }
    } else {
        header("Location: ".$_SERVER['PHP_SELF']."?action=add");
        exit;
    }

}

// Update Ouvrage
if ($action == 'update')
{
    $error=0;

    if (!GETPOST('label'))
    {
        setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('Label')), null, 'errors');
        $action = "create";
        $error++;
    }
    if (empty($ref))
    {
        setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('Ref')), null, 'errors');
        $action = "create";
        $error++;
    }

    if (! $error) {
        $ouvrage->ref = $ref;
        $ouvrage->label = $label;
        $ouvrage->description = $desc;
        $ouvrage->tva = $tva_tx;

        $ouvrage->dets = array();

        if (count($products) > 0) {
            $order = 1;
            foreach ($products as $k=>$product) {
                $ouvrage->addProduct($product, $quantity[$k], $order++);
            }
        }

        if ($ouvrage->update($id, $user)) {
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$ouvrage->id);
            exit;
        } else  {
            if (count($object->errors)) {
                setEventMessages($ouvrage->error, $ouvrage->errors, 'errors');
            } else {
                setEventMessages($langs->trans($ouvrage->error), null, 'errors');
            }
            $action = "create";
        }
    }
}

$morejs=array("/ouvrage/js/ouvragecard.js");

if (DOL_VERSION >= '7') {
    $morejs=array("/ouvrage/js/ouvragecardv7.js");
}

$morecss=array("/ouvrage/css/ouvragecard.css");
$form = new Form($db);

$h = 0;
$head = array();

$head[$h][0] = dol_buildpath("/ouvrage/card.php", 1);
$head[$h][1] = $langs->trans("Card");
$head[$h][2] = 'card';
$h++;

$head[$h][0] = dol_buildpath("/ouvrage/document.php?id=".$id, 1);
$head[$h][1] = $langs->trans("Documents");
$head[$h][2] = 'documents';
$h++;

if ($action == 'add') {

    llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE.'NEW_OUVRAGE'),'','','','',$morejs,$morecss,0,0);
    print load_fiche_titre($langs->trans($conf->global->OUVRAGE_TYPE.'NEW_OUVRAGE'),'','title_products.png');



dol_fiche_head($head, 'card', $titre, -1, $picto);
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="create">';
print '<input type="hidden" name="type" value="'.$type.'">'."\n";

print '<table class="border centpercent">';

        print '<tr>';


        print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag(GETPOST('ref')?GETPOST('ref'):$tmpcode).'">';
        if ($refalreadyexists)
        {
            print $langs->trans("RefAlreadyExists");
        }
        print '</td></tr>';

        // Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label')).'"></td></tr>';

        // Description
        print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';

        $doleditor = new DolEditor('desc', GETPOST('desc'), '', 160, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_4, '90%');
        $doleditor->Create();
        print "</td></tr>";
        print '</tr>';

        //TVA
        print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
        print $form->load_tva("tva_tx",-1,$mysoc,'');
        print '</td></tr>';

        //Pro
        print '<tr>';
	print '<td></td>';// . /*$langs->trans('ProductAndService')*/ . '</td>';
		print '<td colspan="2">';
		print '<div class="ouvrageProductServiceContainer"><ul id="sortable"></ul></div>';
		print '</td>';
	print '</tr>' . "\n";

        //Product
	print '<tr>';
	print '<td>' . $langs->trans('AddProduct') . '</td>';
		print '<td colspan="2">';
		print $form->select_produits('', 'productid', 0);
		print '</td>';
	print '</tr>' . "\n";


        //Product
	print '<tr>';
	print '<td>' . $langs->trans('AddService') . '</td>';
		print '<td colspan="2">';
		print $form->select_produits('', 'serviceid', 1);
		print '</td>';
	print '</tr>' . "\n";

print '</table>';
dol_fiche_end();

print '<div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

            print '</form>';

}

if ($action == 'view' || $action == '' || $action == 'clone') {

llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE.'FICHEOUVRAGE'),'','','','',$morejs,$morecss,0,0);
    print load_fiche_titre($langs->trans($conf->global->OUVRAGE_TYPE.'FICHEOUVRAGE'),'','title_products.png');

dol_fiche_head($head, 'card', $titre, -1, $picto);
    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">';

        print '<tr>';

        print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3">'.$ouvrage->ref;
        print '</td></tr>';

        // Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3">'.$ouvrage->label.'</td></tr>';

        // Description
        print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';

        echo $ouvrage->description;
        print "</td></tr>";
        print '</tr>';

        //TVA
        print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
        echo $ouvrage->tva;
        print '</td></tr>';

        //Pro
        print '<tr>';
	print '<td>' . $langs->trans('ProductAndService') . '</td>';
		print '<td colspan="2">';
		print '<div class="ouvrageProductServiceContainer">'
                . '<table class="border centpercent">'
                        . '<tr>'
                        . '<td width="20%"><strong>'.$langs->trans("Product").' / '.$langs->trans("Service").'</strong></td>'
                        . '<td><strong>'.$langs->trans("Quantity").'</strong></td>'
                        . '</tr>';
                $product = new Product($db);

                foreach ($ouvrage->dets as $det) {
                    $product->fetch($det['product']);
                        echo '<tr>'
                        . '<td>'.$product->label . ' ('.$product->ref.') </td>'
                        . '<td>'.$det['qty'].'</td>'
                        . '</tr>';
                }

                print '</table>'
                . '</div>';
		print '</td>';
	print '</tr>' . "\n";

print '</table>';
echo '<div class="tabsAction">';
echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$ouvrage->id.'">'.$langs->trans("Modify").'</a></div>';



    // Clone
    if ($user->rights->Ouvrage->{$langs->trans("RightsO21")}) {
            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $ouvrage->id . '&amp;action=clone">' . $langs->trans("ToClone") . '</a></div>';
    }

    if (! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
	            {
	                print '<div class="inline-block divButAction"><span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span></div>'."\n";
	            }
	            else
				{
	                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$ouvrage->id.'">'.$langs->trans("Delete").'</a></div>';
	            }


    print '</div>';
    print '</div>';
}

if ($action == 'edit' && $ouvrage->id > 0) {

llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE.'EDITOUVRAGE'),'','','','',$morejs,$morecss,0,0);
    print load_fiche_titre($langs->trans($conf->global->OUVRAGE_TYPE.'EDITOUVRAGE'),'','title_products.png');

dol_fiche_head($head, 'card', $titre, -1, $picto);
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$ouvrage->id.'">';

print '<table class="border centpercent">';

        print '<tr>';

        print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($ouvrage->ref).'">';
        if ($refalreadyexists)
        {
            print $langs->trans("RefAlreadyExists");
        }
        print '</td></tr>';

        // Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($ouvrage->label).'"></td></tr>';

        // Description
        print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';

        $doleditor = new DolEditor('desc', $ouvrage->description, '', 160, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_4, '90%');
        $doleditor->Create();
        print "</td></tr>";
        print '</tr>';

        //TVA
        print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
        print $form->load_tva("tva_tx",$ouvrage->tva,$mysoc,'');
        print '</td></tr>';

        //Pro
        print '<tr>';
	print '<td></td>';// . /*$langs->trans('ProductAndService')*/ . '</td>';
		print '<td colspan="2">';
		print '<div class="ouvrageProductServiceContainer"><ul id="sortable">';
                $product = new Product($db);
                foreach ($ouvrage->dets as $det) {
                    $product->fetch($det['product']);
                        echo '<li class="ui-state-default"><span class="icon-drag-drop"></span>'
                        . '<input type="hidden" name="products[]" value="'.$product->id.'">'
                        . $product->label . ' (' . $product->ref . ') '
                        . '<input type="number" name="quantity[]" value="'.$det['qty'].'" length="4"><a href="#" class="delete">X</a>'
                        . '</li>';
                }
                print '</ul></div>';
		print '</td>';
	print '</tr>' . "\n";

        //Product
	print '<tr>';
	print '<td>' . $langs->trans('AddProduct') . '</td>';
		print '<td colspan="2">';
		print $form->select_produits('', 'productid', 0);
		print '</td>';
	print '</tr>' . "\n";


        //Product
	print '<tr>';
	print '<td>' . $langs->trans('AddService') . '</td>';
		print '<td colspan="2">';
		print $form->select_produits('', 'serviceid', 1);
		print '</td>';
	print '</tr>' . "\n";

print '</table>';
dol_fiche_end();

print '<div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

            print '</form>';
}

// Confirm delete product
if (($action == 'delete' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))	// Output when action = clone if jmobile or no js
	|| (! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
    print $form->formconfirm("card.php?id=".$ouvrage->id,$langs->trans($conf->global->OUVRAGE_TYPE."DeleteOuvrage"),$langs->trans($conf->global->OUVRAGE_TYPE."DeleteOuvrageConfirmation"),"confirm_delete",'',0,"action-delete");
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
                                                array('type' => 'text','name' => 'clone_ref','label' => $langs->trans("Ref"),'value' => $ouvrage->ref . '.1'));
        // Paiement incomplet. On demande si motif = escompte ou autre*/
        print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $ouvrage->id, $langs->trans($conf->global->OUVRAGE_TYPE.'Clone'), $langs->trans($conf->global->OUVRAGE_TYPE.'ConfirmClone', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
}

?>

<style>
    .ouvrageProductServiceContainer span.icon-drag-drop {
        background: url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png' ?>) no-repeat 50% 50%;
    }
</style>

<?php
llxFooter();
