<?php

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once 'lib/infoextranet.lib.php';
require_once 'lib/output.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

global $db, $langs, $conf;

$langs->loadLangs(array("infoextranet@infoextranet"));

// Securite acces client
if (! $user->rights->infoextranet->read) accessforbidden();

// Action and sort for sql
$action = GETPOST('action', 'alpha');
$sortfield=GETPOST('sortfield','alpha');
$sortorder=GETPOST('sortorder','alpha');

// Defaut Sort
if (! $sortfield) $sortfield="s.rowid";
if (! $sortorder) $sortorder="DESC";

// Page management
$page = GETPOST('page','int');
if (!$page) $page = 0;
$limit = GETPOST('limit') ? GETPOST('limit','int') : $conf->liste_limit;
$offset = $limit * $page ;

$form = new Form($db);

// Get all extrafields of section
// Code 42 : Change for section
$extra = getExtrafields('R');

// Array for searching
$search_name = GETPOST('search_name');
$searcharray = array();
foreach ($extra as $key => $field)
{
    $searcharray['search_'.$field['name']] = GETPOST('search_'.$field['name']);
}

// Array of checked for select field
$selectedfields = GETPOST('selectedfields');

// Code 42 : Change for section
if (empty($selectedfields))
    $selectedfields = $conf->global->INFOEXTRANET_SELECTED_R;

$arraychecked = array();
foreach ($extra as $key => $field)
{
    if (strpos($selectedfields, $field['name']) !== false || empty($selectedfields))
        $arraychecked[$field['name']] = 1;
    else
        $arraychecked[$field['name']] = 0;
}

// Array for selected field
$arrayfields = array();
foreach ($extra as $key => $field)
    $arrayfields[$field['name']] = array('label' => $field['label'], 'checked' => $arraychecked[$field['name']]);

/*
 * Action
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_name = '';
    foreach ($extra as $key => $field)
        $searcharray['search_'.$field['name']] = '';
}


/*
 * View
 */

$sql = "SELECT s.rowid, s.nom as name";
foreach ($extra as $key => $field)
{
    $sql.= ", e.".$field['name'];
}

// Code 42 : Change for section
$sql.= " FROM ".MAIN_DB_PREFIX."societe AS s INNER JOIN ".MAIN_DB_PREFIX."societe_extrafields AS e ON s.rowid = e.fk_object WHERE e.c42R_contract >= 0";
foreach ($extra as $key => $field)
{
    if (!empty($searcharray['search_'.$field['name']]))
    {
        $sql.= " AND ";
        if ($field['type'] == 'text' || $field['type'] == 'varchar' || $field['type'] == 'url')
            $sql.= $field['name']." LIKE '%".$searcharray['search_'.$field['name']]."%'";
        else if($field['type'] == 'boolean')
            $sql.= $field['name']."=1";
        else
            $sql.= $field['name']."=".$searcharray['search_'.$field['name']];
    }
}
if (!empty($search_name))
{
    // If it's the first condition, we add WHERE else we add AND
    if ($first)
        $sql.= " WHERE ";
    else
        $sql.= " AND ";
    $sql.= "s.nom LIKE '%".$search_name."%'";

}

// Log query before execute
dol_syslog($sql, LOG_DEBUG);
$totalnboflines=0;
$resql = $db->query($sql);
if ($resql)
{
    $totalnboflines = $db->num_rows($result);
}

// Add limit and sort
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1, $offset);

// Log query before execute
dol_syslog($sql, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) $num = $resql->num_rows;

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label('societe');

llxHeader();

if ($resql)
{
    // barre liste
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    $param = '&limit='.$limit;
    if (!empty($search_name))
        $param.= "&search_name=".$search_name;
    foreach ($extra as $key => $field)
    {
        if (!empty($searcharray['search_'.$field['name']]))
            $param.= "&search_".$field['name']."=".$searcharray['search_'.$field['name']];
    }

    // Code 42 : Change for section
    print_barre_liste('<i class="fa fa-wrench"></i> '.$langs->trans('TitleR'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num,$totalnboflines,'',0,'','',$limit);
    print '<div class="fiche">';
    print '<table class="noborder tagtable liste">';
    print '<tbody>';

    // Display title field
    print '<tr class="liste_titre">';
    print_liste_field_titre('Tiers', $_SERVER["PHP_SELF"], "s.nom","",$param,'',$sortfield,$sortorder);
    foreach ($extra as $key => $field)
    {
        if ($arrayfields[$field['name']]['checked'])
            print_liste_field_titre($field['label'], $_SERVER["PHP_SELF"], "e.".$field['name'],"",$param,'',$sortfield,$sortorder);
    }

    print '<td>'.$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $_SERVER['PHP_SELF']).'</td>'; // Selected arrayfields
    print '</tr>';

    // Display search field
    print '<tr class="liste_titre">';
    print '<td><input class="flat searchstring" size="15" type="text" name="search_name" value="'.$search_name.'"></td>'; // Thirdparty name
    foreach ($extra as $key => $field)
    {
        if ($arrayfields[$field['name']]['checked'])
        {
            print '<td class="liste_titre">';
            print printSearchField($form, $field['name'], $field['type'], $searcharray['search_'.$field['name']]);
            print '</td>';
        }
    }

    // Display search button in search field
    print '<td>';
    $searchpicto=$form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';

    print '</tr>';

    // Display body
    for ($i = 0; $i < $num; $i++)
    {
        $obj = $db->fetch_object($resql);
        $obj_array = (array)$obj;

        print '<tr>';

        $societe = new Societe($db);
        $societe->fetch($obj->rowid);
        print '<td>'.mGetNomUrl($societe, 1, '', 100, 1, 1).'</td>';
        foreach ($extra as $key => $field)
        {
            if ($arrayfields[$field['name']]['checked'])
            {
                if ($field['type'] == 'text' || $field['type'] == 'url')
                    $content = dol_trunc($obj_array[$field['name']], 20);
                else
                    $content = $obj_array[$field['name']];

                print mShowOutput($field['name'], $content, $extrafields);
            }
        }

        print '<td></td>'; // Search <td>
        print '</tr>';
    }

    print '</tbody>';
    print '</table>';
    print '</form>';

}

llxFooter();