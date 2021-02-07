<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

dol_include_once('/core/class/html.form.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/lib/parcautomobile.lib.php');


// print '<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>';
// print '<script src="https://cdnjs.cloudflare.com/ajax/libs/web-animations/2.3.1/web-animations.min.js"></script>';
// print '<script src="https://unpkg.com/muuri@0.6.3/dist/muuri.min.js"></script>';

$langs->load('admin');
$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("parcautomobileSetup");

// Access control
// if (! $user->admin) accessforbidden();

$extrafields = new ExtraFields($db);
$form        = new Form($db);
$parc        = new vehiculeparc($db);

// List of supported format
$tmptype2label=ExtraFields::$type2label;
$type2label=array('');
foreach ($tmptype2label as $key => $val) $type2label[$key]=$langs->transnoentitiesnoconv($val);


$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];

$action=GETPOST('action', 'alpha');
$attrname=GETPOST('attrname', 'alpha');
$elementtype='vehiculeparc'; //Must be the $table_element of the class that manage extrafield

require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';

$textobject = $langs->trans("vehiculeparc");

$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($modname, $linkback, 'title_setup');

// print_fiche_titre($modname);
$head = parcautomobileAdminPrepareHead();
dol_fiche_head(
    $head,
    'champsvehicule',
    $langs->trans("Champs_vehicule"),
    0,
    "parcautomobile@parcautomobile"
);


if($user->rights->parcautomobile->lire)
    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';
else
    print '<div class="" align="center"><span class="opacitymedium">'.$langs->trans("NotEnoughPermissions").'</span></div>';


dol_fiche_end();
// Buttons
if ($user->rights->parcautomobile->creer && $user->rights->parcautomobile->lire) {
	if ($action != 'create' && $action != 'edit')
	{
		print '<div class="tabsAction">';
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."?action=create\">".$langs->trans("NewChamp").'</a></div>';
		print "</div>";
	}
}



/* ************************************************************************** */
/*                                                                            */
/* Creation of an optional field											  */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
	print '<div name="topofform"></div><br>';
	print load_fiche_titre($langs->trans('NewChamps'));

    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition of an optional field                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname))
{
	print '<div name="topofform"></div><br>';
	print load_fiche_titre($langs->trans("FieldEdition", $attrname));

    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}


// End of page
llxFooter();
$db->close();


?>




