<?php

if ($action == 'update' && $request_method === 'POST') {

    $page  = GETPOST('page');

    // $d1 = GETPOST('debut');
    // $f1 = GETPOST('fin');
    $id=GETPOST('id');
  
    $label = GETPOST('label');
   

    $data = array(
        'label'         =>  addslashes($label),
    );

    $isvalid = $marque->update($id, $data);
    // $composantes_new = (GETPOST('composantes_new'));
    // $composantes = (GETPOST('composantes'));
    // $composants_deleted = explode(',', GETPOST('composants_deleted'));
   
    if ($isvalid > 0) {
        $upload_dir = $conf->parcautomobile->dir_output.'/marques/'.$id.'/';
        if(!empty($_FILES['logo']['name'])){
            if($marque->logo_soc){
                $file=$upload_dir."/".$marque->logo;
                unlink($file);
            }
            $TFile = $_FILES['logo'];
            $logo = array('logo' => dol_sanitizeFileName($TFile['name'],''));
            if (dol_mkdir($upload_dir) >= 0)
            {
                $destfull = $upload_dir.$TFile['name'];
                $info     = pathinfo($destfull); 
                
                $filname    = dol_sanitizeFileName($TFile['name'],'');
                $destfull   = $info['dirname'].'/'.$filname;
                $destfull   = dol_string_nohtmltag($destfull);
                $resupload  = dol_move_uploaded_file($TFile['tmp_name'], $destfull, 0, 0, $TFile['error'], 0);
                $marque->update($id,$logo);
            }
        }
        header('Location: ./index.php?page='.$page);
        exit;
    } 
    else {
        header('Location: ./card.php?id='. $id .'&update=0');
        exit;
    }
}


if($action == "edit"){

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="">';

    print '<input type="hidden" name="action" value="update" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border nc_table_" width="100%">';
        print '<tbody>';
            $marque->fetch($id);
            $item = $marque;
            print '<tr>';
                print '<td style="width:20%">'.$langs->trans('label_marque').'</td>';
                print '<td style="width:80%"><input type="text" class="" id="label" value="'.$item->label.'" style="padding:8px 0px 8px 8px; width:100%" name="label"  autocomplete="off"/>';
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td>'.$langs->trans('photo_').'</td>';
                print '<td>';
                    print '<div id="wrapper"> <ul>';
                               
                      

                        if(!empty($item->logo)){
                            $minifile = getImageFileNameForSize($item->logo, '');  
                            $dt_files = getAdvancedPreviewUrl('parcautomobile', '/marques/'.$item->rowid.'/'.$minifile, 1, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));

                            print '<li> <a href="'.$dt_files['url'].'" class="'.$dt_files['css'].'" target="'.$dt_files['target'].'" mime="'.$dt_files['mime'].'">' ;
                                print '<img class="photo"  title="'.$minifile.'" alt="Fichier binaire" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file=marques/'.$item->rowid.'/'.$minifile.'&perm=download" border="0" name="image" >';
                            print '</a> </li>';
                        }
                        print '<input type="file" name="logo" id="logo" ">';
                           
                    print '</ul></div>';
                print '</td>';
            print '</tr>';
        print '</tbody>';
    print '</table>';


    // Actions

    print '<table class="" width="100%">';
    print '<tr>';
        print '<td colspan="2" >';
            print '<br>';
            print '<input type="submit" style="display:none" id="sub_valid" value="'.$langs->trans('Validate').'" style="" name="bouton" class="butAction" />';
            print '<a  class="butAction" id="btn_valid">'.$langs->trans('Validate').'</a>';
            
            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';
    print '<div id="lightbox" style="display:none;"><p>X</p><div id="content"><img src="" /></div></div>';

    ?>
    <?php
}

?>

<script>
    $(document).ready(function() {
        $('#btn_valid').click(function(){
            $('#sub_valid').trigger('click');
        })
    })
</script>


