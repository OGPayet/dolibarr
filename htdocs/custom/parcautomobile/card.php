<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

dol_include_once('/parcautomobile/class/statut.class.php');
dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/parcautomobile/class/marques.class.php');
dol_include_once('/parcautomobile/class/etiquettes_parc.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/class/kilometrage.class.php');
dol_include_once('/parcautomobile/class/contrat_parc.class.php');
dol_include_once('/parcautomobile/class/interventions_parc.class.php');
dol_include_once('/parcautomobile/class/costsvehicule.class.php');
dol_include_once('/parcautomobile/class/suivi_essence.class.php');
dol_include_once('/core/class/html.form.class.php');


require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

$langs->load('parcautomobile@parcautomobile');
$modname = $langs->trans("vehicule");
// Initial Objects
$parc = new vehiculeparc($db);
$extrafields = new ExtraFields($db);

$modeles       = new modeles($db);
$marques       = new marques($db);
$statut        = new statut($db);
$etiquettes    = new etiquettes_parc($db);
$interventions = new interventions_parc($db);
$typeintervention = new typeintervention($db);
$contrats      = new contrat_parc($db);
$costs         = new costsvehicule($db);
$kilometrage   = new kilometrage($db);
$suivi_essence = new suivi_essence($db);
$form          = new Form($db);
$form          = new Form($db);
$user_         = new User($db);
$soc           = new Societe($db);

$extrafields->fetch_name_optionals_label($parc->table_element);

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

$action=GETPOST('action');
if (!empty($id) && $action == "pdf") {
    global $langs,$mysoc;
        // print_r($conf->global->MAIN_INFO_SOCIETE_NOM);die();
    require_once dol_buildpath('/parcautomobile/pdf/pdf.lib.php');


     $object = new vehiculeparc($db);
    $object->fetch($id);
    $item = $object;

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);
    $object->fetch($item->rowid);
    $object->fetch_optionals();


    $pdf->SetFont('times', '', 9, '', true);
    $pdf->AddPage('P');
    $array_format = pdf_getFormat();
    $parc->fetch($id);
    $item = $parc;
    $object=$parc;

    $marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
    $marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;
    $margin = $marge_haute+$marge_basse+45;

    $page_largeur = $formatarray['width'];
    $page_hauteur = $formatarray['height'];
    $format = array($page_largeur,$page_hauteur);

    $marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
    $marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
    $marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
    $marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;
    $emetteur = $mysoc;



    $default_font_size = pdf_getPDFFontSize($langs);

    pdf_pagehead($pdf,$langs,$page_hauteur);

    // // Show Draft Watermark
    // if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
    // {
    //     pdf_watermark($pdf,$langs,$page_hauteur,$page_largeur,'mm',$conf->global->COMMANDE_DRAFT_WATERMARK);
    // }

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('helvetica','B', $default_font_size + 2);

    $posy=$marge_haute;
    $posx=$page_largeur-$marge_droite-100;

    $pdf->SetXY($marge_gauche,$posy);

    // Logo
    $logo=$conf->mycompany->dir_output.'/logos/'.$emetteur->logo;

    if ($emetteur->logo)
    {
        if (is_readable($logo))
        {
            $height=pdf_getHeightForLogo($logo);
            $pdf->Image($logo, $marge_gauche, $posy, 0, $height); // width=0 (auto)
        }
        else
        {
            $pdf->SetTextColor(200,0,0);
            $pdf->SetFont('helvetica','B', $default_font_size -2);
            $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
            $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
        }
    }
    else
    {
        $text=$emetteur->name;
        $pdf->MultiCell(40, 2, $langs->convToOutputCharset($text), 0, 'L');
    }


    $pdf->SetFont('helvetica','B', $default_font_size );
    $pdf->SetXY($posx,$posy);
    $pdf->SetTextColor(0,0,0);
    $pdf->MultiCell(100, 3, date("d/m/Y"), '', 'R');

    // $pdf->SetFont('helvetica','B',$default_font_size);
    // $posy+=5;
    // $pdf->SetXY($posx,$posy);
    // $pdf->SetTextColor(0,0,60);
    // $pdf->MultiCell(100, 4, $langs->transnoentities("Objet")." : " . $langs->convToOutputCharset($object->objet), '', 'R');

    //info vehicule
    $pdf->SetFont('helvetica', '', 9, '', true);
    // Sender properties
    $carac_emetteur='';
    // Add internal contact of proposal if defined
    $arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
    if (count($arrayidcontact) > 0)
    {
        $object->fetch_user($arrayidcontact[0]);
        $labelbeforecontactname=($langs->transnoentities("FromContactName")!='FromContactName'?$langs->transnoentities("FromContactName"):$langs->transnoentities("Name"));
        $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$labelbeforecontactname." ".$langs->convToOutputCharset($object->user->getFullName($langs))."\n";
    }

    if(isset($object->thirdparty))
    $carac_emetteur .= pdf_build_address($langs, $emetteur, $object->thirdparty, '', 0, 'source', $object);

    // Show sender
    $posy=42+$top_shift;
    $posx=$marge_gauche+5;
    if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$page_largeur-$marge_droite;
    $hautcadre=10;

    // Show sender frame
    $pdf->SetFont('helvetica','', $default_font_size - 2);
    $pdf->SetXY($posx,$posy);

    $pdf->SetXY($posx,$posy-10);
    $pdf->SetFillColor(230,230,230);
    // $pdf->MultiCell($page_largeur, $hautcadre, $parc->get_nom($item->rowid), 0, 'C', 1);
    $pdf->SetTextColor(0,0,60);

    // Show sender name
    // $pdf->SetXY($posx+2,$posy+3);
    // $pdf->SetFont('','B', $default_font_size);
    // $posy=$pdf->getY();

    // Show sender information
    // $pdf->SetXY($posx+2,$posy);
    // $pdf->SetFont('','', $default_font_size - 1);
    // $pdf->MultiCell($page_largeur, 4, $carac_emetteur, 0, 'L');

    // $pdf->SetFont('','', $default_font_size - 1);


    // If CUSTOMER contact defined on order, we use it
    // $usecontact=false;
    // $arrayidcontact=$object->getIdContact('external','CUSTOMER');
    // if (count($arrayidcontact) > 0)
    // {
    //     $usecontact=true;
    //     $result=$object->fetch_contact($arrayidcontact[0]);
    // }





    $pdf->setPrintFooter(true);
    // require template
    require_once dol_buildpath('/parcautomobile/export/vehicule.php');
    // echo $html;
    // die();
    $posy=$pdf->getY();
    $pdf->SetXY($posx,$posy);
    $pdf->writeHTML($html, true, false, true, false, '');
    ob_start();
    $pdf->Output('vehicule.pdf', 'I');
    // ob_end_clean();
    die();
}


$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);
print_fiche_titre($modname);


?>

<script>
    $(document).ready(function(){
        $('.datepickerparc').datepicker({dateFormat:'dd/mm/yy'});
        $('.unite').select2();
        $('#select_etiquettes').select2();
        $('#select_model').select2();
        $('#type_carburant').select2();
        $('#conducteur').select2();
        $('.anne').select2();
        $('.transmission').select2();
        $('.type_kilom').select2();
        $('#select_statut').select2();
        $('#select_unite').select2();

        $('#importer').click(function(){
            $('#logo').trigger('click');
        });
        $('#logo').change(function(){
            $val  = $('#logo').val().split('\\');
            $name = $val[$val.length-1];
            $('#name').css('display','inherit');
            $('#name').val($name);
        });
    });

    function change_anne(that) {
        anne=$(that).val();
        select='<select name="anne_model" class="select_anne" onchange="change_anne(this)">';
        var i=anne-10;
        $nb=<?php echo date('Y') ?>;
        for (i ; i <=2019 ; i++) {
            select+='<option value="'+i+'" >'+i+'</option>';
        }
        select+='</select>';
        select = select.replace('value="'+anne+'"', 'value="'+anne+'" selected');

        $(that).parent().html(select);
    }

    function get_logo(that) {
        $model=$(that).val();
        console.log($(that).find('option:selected').attr('tag_img'));
        img=$(that).find('option:selected').attr('tag_img');
        console.log(img);
        if(img){
            img='<img src="'+img+'" height="80px">';
            $('#img').html(img);
        }else{
             $('#img').html('');
        }
    }
</script>
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