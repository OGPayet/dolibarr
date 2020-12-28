<?php
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom"

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


dol_include_once('/parcautomobile/class/interventions_parc.class.php');
dol_include_once('/parcautomobile/class/typeintervention.class.php');
dol_include_once('/parcautomobile/class/vehiculeparc.class.php');
dol_include_once('/parcautomobile/class/kilometrage.class.php');
dol_include_once('/parcautomobile/class/costsvehicule.class.php');
dol_include_once('/parcautomobile/class/marques.class.php');
dol_include_once('/parcautomobile/class/modeles.class.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/parcautomobile/lib/parcautomobile.lib.php');


$langs->load('parcautomobile@parcautomobile');

$modname = $langs->trans("suivi_intervention");
// Initial Objects
$typeintervention = new typeintervention($db);
$intervention = new interventions_parc($db);
$extrafields = new ExtraFields($db);
$kilometrage = new kilometrage($db);

$vehicules = new vehiculeparc($db);
$costs = new costsvehicule($db);
$user_ = new User($db);
$soc = new Societe($db);
$model = new modeles($db);
$marque = new marques($db);
$form        = new Form($db);

$extrafields->fetch_name_optionals_label($intervention->table_element);

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

if (!empty($id) && $action == "pdf") {
    global $langs,$mysoc;
        // print_r($conf->global->MAIN_INFO_SOCIETE_NOM);die();
    require_once dol_buildpath('/parcautomobile/pdf/pdf.lib.php');

    $object = new interventions_parc($db);
    $object->fetch($id);

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label($object->table_element);
    $object->fetch($item->rowid);
    $object->fetch_optionals();

    $pdf->SetFont('times', '', 9, '', true);
    $pdf->AddPage('P');
    $array_format = pdf_getFormat();
    $intervention->fetch($id);
    $item = $intervention;
    $object=$intervention;
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

    // $carac_emetteur .= pdf_build_address($langs, $emetteur, $object->thirdparty, '', 0, 'source', $object);

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
    require_once dol_buildpath('/parcautomobile/interventions_parc/export/intervention.php');
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

    // ------------------------------------------------------------------------- Views

print '<div class="parcautomobilecardfile">';
if($action == "add")
    require_once 'z-actions/create.php';

if($action == "edit")
    require_once 'z-actions/edit.php';

if( ($id && empty($action)) || $action == "delete" )
    require_once 'z-actions/show.php';

print '</div>';
?>
<script>
    $(function(){
        $('.datepickerparc').datepicker({
            dateFormat:'dd/mm/yy',
        });
        $('#select_typeintervention').select2();
        $('#select_vehicule').change(function(){
            $id=$(this).val();
            $.ajax({
                data:{'id':$id},
                url:"<?php echo dol_buildpath('/parcautomobile/conducteur.php',2); ?>",
                type:'POST',
                dataType:'json',
                success:function(data){
                    $('#acheteur').text(data['name']);
                    $('#unite').text(data['unite']);
                }
            });
        });

        $('#add_lign_service').click(function(){

            $id=$('#tr_services tr').length+1;
            $('#tr_services').append('<tr id="'+$id+'"><td class="type_service"><select name="services['+$id+'][type]" class="type_service"><?php echo $typeintervention->get_types(); ?> </select></td><td class="note_service"><input type="text" name="services['+$id+'][note]" ></td> <td class="prix_service"><input type="number" onchange="total_services(this)" required name="services['+$id+'][prix]" step="0.001" min="0"><a style="cursor: pointer;" float="right" class=""  onclick="delete_tr(this)" ><img src="<?php echo dol_buildpath('/parcautomobile/img/delete.png',2) ?>"></a></td></tr>');
            $('select.type_service').select2();

        });

    });
    function delete_tr(id) {
        $id=$(id).data('id');
        $total=$('#total_services').find('input').val();
        $prix=$(id).parent().find('input').val();
        $total= parseFloat($total) - parseFloat($prix);
          $('#total_services').find('input').val($total);
          $('#total_services').find('strong').text($total);

        $(id).parent().parent().remove();

    }
    function total_services(prix) {
        var $total=0;
        $('.prix_service').each(function(){
          console.log($(this).find('input'));
          var prix=$(this).find('input').val();
          $total = parseFloat($total) + parseFloat(prix);
          $('#total_services').find('input').val($total);
        });
        $('#total_services').find('strong').text($total);

    }
</script>
<?php

llxFooter();

if (is_object($db)) $db->close();
?>