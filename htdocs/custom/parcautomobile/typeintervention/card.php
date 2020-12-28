<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

dol_include_once('/parcautomobile/class/typeintervention.class.php');
dol_include_once('/core/class/html.form.class.php');



$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("typeintervention");
// Initial Objects
$typeintervention = new typeintervention($db);
$form        = new Form($db);
// Get parameters
$request_method = $_SERVER['REQUEST_METHOD'];
$action         = GETPOST('action', 'alpha');
$page           = GETPOST('page');
$id             = (int) ( (!empty($_GET['id'])) ? $_GET['id'] : GETPOST('id') ) ;

$error  = false;
if (!$user->rights->parcautomobile->gestion->consulter) {
    accessforbidden();
}

if(in_array($action, ["add","edit"])) {
    if (!$user->rights->parcautomobile->gestion->update) {
      accessforbidden();
    }
}
if($action == "delete") {
    if (!$user->rights->parcautomobile->gestion->delete) {
      accessforbidden();
    }

}

// ------------------------------------------------------------------------- Actions "Create/Update/Delete"
if ($action == 'create' && $request_method === 'POST') {
    require_once 'z-actions/create.php';
}

if ($action == 'update' && $request_method === 'POST') {
    require_once 'z-actions/edit.php';
}

// If delete of request
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    require_once 'z-actions/show.php';
}

if ($action == 'confirm_deconstruction' && GETPOST('confirm') == 'yes' ) {
    require_once 'z-actions/edit.php';
}

if ($action == 'confirm_rebut' && GETPOST('confirm') == 'yes' ) {
    require_once 'z-actions/edit.php';
}


$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);
print_fiche_titre($modname);


?>

<?php
print '<div class="parcautomobilecardfile">';
    // die($action);
    // ------------------------------------------------------------------------- Views
    if($action == "add")
        require_once 'z-actions/create.php';

    if($action == "edit")
        require_once 'z-actions/edit.php';

    if( ($id && empty($action)) || $action == "delete" )
        require_once 'z-actions/show.php';

print '</div>';

    ?>
<?php

llxFooter();

if (is_object($db)) $db->close();
?>