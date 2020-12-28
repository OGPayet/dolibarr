<?php

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {

    if (!$id || $id <= 0) {
        header('Location: ./card.php?action=request&error=dalete_failed&id='.$id);
        exit;
    }

    $page  = GETPOST('page');

    $marque->fetch($id);
    $modeles->fetchAll('','','',0,0,'AND marque ='.$id);
    for ($i=0; $i < count($modeles) ; $i++) {
        $model=$modeles->rows[$i];
        $modeles->fetch($model->rowid);
        $modeles->delete();
    }
    $error = $marque->delete();

    if ($error == 1) {
        $dir = $conf->parcautomobile->dir_output.'/marques/'.$id.'/';
        $files=scandir($dir);
        foreach ($files as $file) {
            if($file != '.' && $file!='..'){
                $dir = $conf->parcautomobile->dir_output.'/marques/'.$id.'/'.$file;
                if(file_exists($dir)){
                    unlink($dir);
                }
            }
        }
        header('Location: index.php?delete='.$id.'&page='.$page);
        exit;
    }
    else {
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}


if( ($id && empty($action)) || $action == "delete" ){

    // $h = 0;
    // $head = array();
    // $head[$h][0] = dol_buildpath("/parcautomobile/card.php?id=".$id, 1);
    // $head[$h][1] = $langs->trans($modname);
    // $head[$h][2] = 'affichage';
    // $h++;
    // dol_fiche_head($head,'affichage',"",0,"logo@avancementtravaux");


    if($action == "delete"){
        print $form->formconfirm("card.php?id=".$id."&page=".$page,$langs->trans('Confirmation') , $langs->trans('msgconfirmdelet'),"confirm_delete", 'index.php?page='.$page, 0, 1);
    }

    // if (!$user->rights->avancementtravaux->gestion->consulter) {
    //     accessforbidden();
    // }
    // $avancementtravaux->fetchAll('','',0,0,' and rowid = '.$id);
    $marque->fetch($id);
    $item = $marque;



    dol_include_once('/parcautomobile/class/parcautomobile.class.php');
    $parcautomobile = new parcautomobile($db);
    $linkback = '<a href="./index.php?page='.$page.'">'.$langs->trans("BackToList").'</a>';
    print $parcautomobile->showNavigations($item, $linkback);






    // $extrafields = new ExtraFields($db);
    // $extralabels=$extrafields->fetch_name_optionals_label($item->table_element);
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" class="">';

    print '<input type="hidden" name="confirm" itemue="no" id="confirm" />';
    print '<input type="hidden" name="id" value="'.$id.'" />';
    print '<input type="hidden" name="page" value="'.$page.'" />';
    print '<table class="border" width="100%">';

        print '<tbody>';
            print '<tr>';
                print '<td style="width:20% !important">'.$langs->trans('label_marque');
                print '<td style="width:80% !important">'.$item->label.'</td>';
            print '</tr>';

            print '<tr>';
                print '<td >'.$langs->trans('photo_');
                print '<td>';
                    print '<div id="wrapper"> <ul>';
                        if(!empty($item->logo)){
                            $minifile = getImageFileNameForSize($item->logo, '');
                            $dt_files = getAdvancedPreviewUrl('parcautomobile', '/marques/'.$item->rowid.'/'.$minifile, 1, '&entity='.(!empty($object->entity)?$object->entity:$conf->entity));

                            print '<li> <a href="'.$dt_files['url'].'" class="'.$dt_files['css'].'" target="'.$dt_files['target'].'" mime="'.$dt_files['mime'].'">' ;
                                print '<img class="photo" height="80" title="'.$minifile.'" alt="Fichier binaire" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=parcautomobile&entity='.(!empty($object->entity)?$object->entity:$conf->entity).'&file=marques/'.$item->rowid.'/'.$minifile.'&perm=download" border="0" name="image" >';
                            print '</a> </li>';
                        }
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
            print '<a href="./card.php?id='.$id.'&action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
            print '<a href="./card.php?id='.$id.'&action=delete" class="butAction butActionDelete">'.$langs->trans('Delete').'</a>';
            print '<a href="./index.php?page='.$page.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</td>';
    print '</tr>';
    print '</table>';

    print '</form>';

    print '<div id="lightbox" style="display:none;"><p>X</p><div id="content"><img src="" /></div></div>';

}

?>
