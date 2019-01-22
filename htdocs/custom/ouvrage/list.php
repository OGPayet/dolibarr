<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../main.inc.php'; // From "custom" directory
}

global $langs, $user;

$langs->load("ouvrage@ouvrage");

// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="w.rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

$search_id=GETPOST('search_id','int');
$search_ref=GETPOST('search_ref','int');
$search_label=GETPOST('search_label','alpha');

$params='';

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") ||GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{

$search_id='';
$search_ref='';
$search_label='';
	$search_array_options=array();
}

if ($search_id != '') $params.= '&amp;search_id='.urlencode($search_id);
if ($search_ref != '') $params.= '&amp;search_ref='.urlencode($search_ref);
if ($search_label != '') $params.= '&amp;search_label='.urlencode($search_label);

// Definition of fields for list
$arrayfields=array(

'w.rowid'=>array('label'=>$langs->trans("ID"), 'checked'=>1),
'w.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
'w.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
);

llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE."LISTINGOUVRAGE"));

$sql = "SELECT w.* FROM ".MAIN_DB_PREFIX."works as w";
$sql.= " WHERE entity = " . $conf->entity;

if ($search_id) $sql.= natural_search("rowid",$search_id);
if ($search_ref) $sql.= natural_search("ref",$search_ref);
if ($search_label) $sql.= natural_search("label",$search_label);

$sql.=$db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    $params='';
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

    if ($search_id != '') $params.= '&amp;search_id='.urlencode($search_id);
    if ($search_ref != '') $params.= '&amp;search_ref='.urlencode($search_ref);
    if ($search_label != '') $params.= '&amp;search_label='.urlencode($search_label);



//Pour test
$title = $langs->trans($conf->global->OUVRAGE_TYPE."LISTINGOUVRAGE");
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $params, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';

    // Fields title
    print '<tr class="liste_titre">';
    //
if (! empty($arrayfields['w.rowid']['checked'])) print_liste_field_titre($arrayfields['w.rowid']['label'],$_SERVER['PHP_SELF'],'w.rowid','',$params,'',$sortfield,$sortorder);
if (! empty($arrayfields['w.ref']['checked'])) print_liste_field_titre($arrayfields['w.ref']['label'],$_SERVER['PHP_SELF'],'w.ref','',$params,'',$sortfield,$sortorder);
if (! empty($arrayfields['w.label']['checked'])) print_liste_field_titre($arrayfields['w.label']['label'],$_SERVER['PHP_SELF'],'w.label','',$params,'',$sortfield,$sortorder);

print_liste_field_titre('', $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
    print '</tr>'."\n";

    // Fields title search
	print '<tr class="liste_titre">';
	//
if (! empty($arrayfields['w.rowid']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_id" value="'.$search_id.'" size="10"></td>';
if (! empty($arrayfields['w.ref']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="10"></td>';
if (! empty($arrayfields['w.label']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_label" value="'.$search_label.'" size="10"></td>';


    // Action column
    if (method_exists ( $form , 'showFilterAndCheckAddButtons' )) {
	print '<td class="liste_titre" align="right">';
    $searchpitco=$form->showFilterAndCheckAddButtons(0);
    print $searchpitco;
    print '</td>';
}
	print '</tr>'."\n";
        $i--;
        while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);
        if ($obj)
        {
            $var = !$var;

            // Show here line of result
            print '<tr '.$bc[$var].'>';
            if (! empty($arrayfields['w.rowid']['checked']))
            {
                print '<td><a href="'.dol_buildpath('/ouvrage/card.php?id='.$obj->rowid, 1).'">'.$obj->rowid.'</a></td>';
		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['w.ref']['checked']))
            {
                print '<td><a href="'.dol_buildpath('/ouvrage/card.php?id='.$obj->rowid, 1).'">'.$obj->ref.'</a></td>';
		    if (! $i) $totalarray['nbfield']++;
            }
            if (! empty($arrayfields['w.label']['checked']))
            {
                print '<td>'.$obj->label.'</td>';
		    if (! $i) $totalarray['nbfield']++;
            }

            // Action column
            print '<td></td>';
            if (! $i) $totalarray['nbfield']++;

            print '</tr>';
        }
        $i++;
    }

    $db->free($resql);


print '</table>';
print '</form>';
} else {
    $error++;
    dol_print_error($db);
}
// End of page
llxFooter();
