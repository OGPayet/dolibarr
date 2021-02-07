<?php 
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


dol_include_once('/parcautomobile/class/kilometrage.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/core/class/html.form.class.php');


$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("detail_kilom");
// Initial Objects
$extrafields = new ExtraFields($db);
$kilometrage = new kilometrage($db);
$vehicule    = new vehiculeparc($db);
$model       = new modeles($db);
$marque      = new marques($db);
$form        = new Form($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($kilometrage->table_element);


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


if (!empty($id) && $action == "pdf") {
    global $langs,$mysoc;
        // print_r($conf->global->MAIN_INFO_SOCIETE_NOM);die();
    require_once dol_buildpath('/parcautomobile/pdf/pdf.lib.php');

    $object = new kilometrage($db);
    $object->fetch($id);
    $item = $object;

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);
    $object->fetch($item->rowid);
    $object->fetch_optionals();

    $user_ = new User($db);
    $soc = new Societe($db);

    $pdf->SetFont('times', '', 9, '', true);
    $pdf->AddPage('P');
    $array_format = pdf_getFormat();

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

    if($object->thirdparty)
    $carac_emetteur .= pdf_build_address($langs, $emetteur, $object->thirdparty, '', 0, 'source', $object);

    // Show sender
    $posy=42+$top_shift;
    $posx=$marge_gauche+5;
    if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$page_largeur-$marge_droite;
    $hautcadre=10;

    // Show sender frame
    $pdf->SetFont('helvetica','', $default_font_size - 2);
    $pdf->SetXY($posx,$posy);
    
    $pdf->SetXY($posx,$posy);
    $pdf->SetFillColor(230,230,230);
    $pdf->SetTextColor(0,0,60);

    $pdf->setPrintFooter(true);
    // require template
    require_once dol_buildpath('/parcautomobile/kilometrage/export/kilometrage.php');

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
<script>
    $(function(){
        $('.datepickerparc').datepicker({
            dateFormat:'dd/mm/yy',
        });

        $('#select_vehicule').change(function(){
            $id=$(this).val();
            $.ajax({
                data:{'id':$id},
                url:"<?php echo dol_buildpath('/parcautomobile/conducteur.php',2); ?>",
                type:'POST',
                dataType:'json',
                success:function(data){
                    $('#unite').text(data['unite']);
                }
            });

        });
    })
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