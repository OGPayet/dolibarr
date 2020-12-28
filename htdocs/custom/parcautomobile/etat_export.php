<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/parcautomobile/class/statut.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("etat_export");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects



$parcautomobile     = new vehiculeparc($db);
$vehicules          = new vehiculeparc($db);
$model        		= new modeles($db);
$marque        		= new marques($db);
$statut = new statut($db);
$user_ = new User($db);
$form           = new Form($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];


if (!$user->rights->parcautomobile->gestion->consulter) {
	accessforbidden();
}


$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);

print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);

$selectbydate = '<select>';
	$selectbydate .= '<option value="date">'.$langs->trans("date").'</option>';
	$selectbydate .= '<option value="day">'.$langs->trans("Day").'</option>';
	$selectbydate .= '<option value="month">'.$langs->trans("Month").'</option>';
	$selectbydate .= '<option value="year">'.$langs->trans("Year").'</option>';
$selectbydate .= '</select>';

$selectmoduls = '<select>';
	$selectmoduls .= '<option value="vehicul">'.$langs->trans("vehicul").'</option>';
	$selectmoduls .= '<option value="kilomterage">'.$langs->trans("kilomterage").'</option>';
	$selectmoduls .= '<option value="costs">'.$langs->trans("costs").'</option>';
	$selectmoduls .= '<option value="intervention">'.$langs->trans("intervention").'</option>';
$selectmoduls .= '</select>';

print '<form method="get" action="'.$_SERVER["PHP_SELF"].'" class="index_parc">'."\n";

	print '<table id="table-1" class="noborder" style="width: 100%;" >';

		print '<tbody>';
			print '<tr '.$bc[$var].' >';
			print '<td align="center" style="" >';
				print '<sapn></span>';
				print $selectmoduls;
			print '</td>';
			print '<td align="center" style="">';
				print '<sapn></span>';
				print $selectbydate;
				print '<span>:</span>';
				print '<div class="input_date"></div>';
			print '</td>';

			print '<td align="center" style="">';
				print '<a class="button">'.$langs->trans("Export").'</a>';
			print '</td>';

			print '</tr>';
		print '</tbody>';

	print '</table>';
print '</form>';

?>

<script>
	$(function(){
		$('#select_unite').select2();
		$('.datepickerparc').datepicker({
			dataFormat:'dd/mm/yyyy',
		});
	})
</script>

<?php

llxFooter();