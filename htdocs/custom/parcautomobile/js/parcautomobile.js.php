<?php
// $res=0;
// if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
// if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"


// require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
// dol_include_once('/parcautomobile/class/interventions_parc.class.php');
// global $langs, $db;

// $interventions = new interventions_parc($db);


// $datesent = dolibarr_get_const($db,'PARCAUTOMOBILE_CHECKINTERVENTIONSFORMAIL',0);

// $datenow = date('Y-m-d');

// $diffday = 0;

// $action   			= $_GET['action'];

// if(!empty($datesent) && $action == "testmail"){
// 	$diffday = $interventions->checkInterventionsMails($datesent, $datenow);
// }
// if(empty($datesent) || $diffday < 0){
// 	// $interventions->checkInterventionsMails();
// }
// echo "diffday : ".$diffday;

// // if(!empty($_SESSION["checkifsentemail"])){

// // }

?>

$(document).ready(function(){
	textarea_autosize($('#notes_txt'));
	textarea_autosize($('#notes_txt'));
})
function textarea_autosize(x){
    $(x).each(function(textarea) {
        $(this).css('resize', 'none');
    }).on('input', function () {
        $(this).css('height', 'auto');
        $(this).height($(this)[0].scrollHeight);
    });
}