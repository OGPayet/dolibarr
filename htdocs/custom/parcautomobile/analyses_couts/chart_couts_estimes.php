<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

dol_include_once('/parcautomobile/class/costsvehicule.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/core/class/html.form.class.php');

$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("costsvehicule");
// print_r(picto_from_langcode($langs->defaultlang));die();
// Initial Objects
$costs        = new costsvehicule($db);
$vehicules    = new vehiculeparc($db);
$form         = new Form($db);

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

// print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr);
?>
<link rel="stylesheet" type="text/css" href="<?php echo dol_buildpath('/parcautomobile/analyses_couts/css/style.css',1); ?>">
<script src="<?php echo dol_buildpath('/parcautomobile/analyses_couts/js/plugin/core.js',1); ?>"></script>
<script src="<?php echo dol_buildpath('/parcautomobile/analyses_couts/js/plugin/charts.js',1); ?>"></script>
<script src="<?php echo dol_buildpath('/parcautomobile/analyses_couts/js/plugin/animated.js',1); ?>"></script>

<?php
// $datachart=;
// print_r($datachart);die();
// $tiers_lastthreeyear = $charts_tiers->tiers_LastThreeYear();
$vehicules->fetchAll();
// print_r($vehicules->datachart2(2));die();
// print_r($vehicules->rows);die();


print '<div class="m-portlet width50percent right tiers">';
	print '<div class="m-portlet__head" align="center">';
	print '<div class="m-portlet__head">';
		print '<div class="m-portlet__head-caption">';
			print '<div class="m-portlet__head-title">';
				print '<div class="mainmenu companies topmenuimage iconimg"><span class="mainmenu tmenuimage" id="mainmenuspan_companies"></span></div>';
				print '<h3 class="m-portlet__head-text">'.$langs->trans('analyse_cout_estime').'</h3>';
			print '</div>';
		print '</div>';
		print '<div class="m-portlet__head-tools">';

		print '</div>';
	print '</div>';
	print '<div class="m-portlet__body">';
		print '<div id="chartdiv2" style="height: 400px;">';
			print '<div style="line-height: 400px;"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>';
		print '</div>';
	print '</div>';
print '</div>';
print '<div class="clear"></div>';




?>
<script>
$( function() {
	am4core.ready(function() {

		// Themes begin
		am4core.useTheme(am4themes_animated);
		// Themes end

		// Create chart instance
		var chart = am4core.create("chartdiv2", am4charts.XYChart);
		// Add data
		chart.data = [
		<?php
		for ($i=0; $i < count($vehicules->rows); $i++) {
			$vehicule = $vehicules->rows[$i];
			$data = $vehicules->datachart2($vehicule->rowid);
		?>
			{
				"vehicule":"<?php echo $vehicules->get_nom($vehicule->rowid,1); ?>",
				"contrat": "<?php echo $data[$vehicule->rowid]['contrat'] ?>",
				"services": "<?php echo $data[$vehicule->rowid]['services'] ?>",
			},
		<?php
		}
		?>
		];
		// Create axes
		var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
		categoryAxis.dataFields.category = "vehicule";
		categoryAxis.title.text = "";
		categoryAxis.renderer.grid.template.location = 0;
		categoryAxis.renderer.minGridDistance = 20;
		categoryAxis.renderer.cellStartLocation = 0.1;
		categoryAxis.renderer.cellEndLocation = 0.9;

		var  valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
		valueAxis.min = 0;
		valueAxis.title.text = "<?php echo $langs->trans('montant') ?>";

		// Create series
		function createSeries(field, name, stacked) {
		  var series = chart.series.push(new am4charts.ColumnSeries());
		  series.dataFields.valueY = field;
		  series.dataFields.categoryX = "vehicule";
		  series.name = name;
		  series.columns.template.tooltipText = "[bold]{categoryX}[/] <?php print '\n'; ?> {name}: [bold]{valueY}[/] ";
		  series.stacked = stacked;
		  series.columns.template.width = am4core.percent(95);
		}

		createSeries("contrat", "Contrat", false);
		createSeries("services", "Interventions", false);


		// Add legend
		chart.legend = new am4charts.Legend();
		chart.exporting.menu = new am4core.ExportMenu();

	});

});
</script>





<?php

llxFooter();