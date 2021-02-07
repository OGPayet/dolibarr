<?php 
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 



require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/parcautomobile/class/marques.class.php');
dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/core/class/html.form.class.php');


$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("marques");
// Initial Objects
$marque = new marques($db);
$modeles = new modeles($db);
$form        = new Form($db);
// Get parameters
$request_method = $_SERVER['REQUEST_METHOD'];
$action         = GETPOST('action', 'alpha');
$page           = GETPOST('page');
$id             = (int) ( (!empty($_GET['id'])) ? $_GET['id'] : GETPOST('id') ) ;

$error  = false;
if (!$user->rights->parcautomobile->lire) {
    accessforbidden();
}

if(in_array($action, ["add","edit"])) {
    if (!$user->rights->parcautomobile->creer) {
      accessforbidden();
    }
}
if($action == "delete") {
    if (!$user->rights->parcautomobile->supprimer) {
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
    print '<div class="parcautomobilemarques">';
    if($action == "add")
        require_once 'z-actions/create.php';

    if($action == "edit")
        require_once 'z-actions/edit.php';

    if( ($id && empty($action)) || $action == "delete" )
        require_once 'z-actions/show.php';
    print '</div>';
    
print '</div>';
    ?>
    
<script>
    $(function(){
        $('#importer').click(function(){
            $('#logo').trigger('click');
        });
        $('#logo').change(function(){
            $('#photo').hide();
            $val  = $('#logo').val().split('\\');
            $name = $val[$val.length-1];
            $('#name').css('display','inherit');
            $('#name').val($name);
        });

         $('.lightbox_trigger').click(function(e) {
            e.preventDefault();
            var image_href = $(this).attr("href");
            $('#lightbox #content').html('<img src="' + image_href + '" />');
            $('#lightbox').show();
        });

         $('#lightbox,#lightbox p').click(function() {
            $('#lightbox').hide();
        });
    });
</script>
<?php

llxFooter();

if (is_object($db)) $db->close();
?>