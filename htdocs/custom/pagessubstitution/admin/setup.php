<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/pagessubstitution/admin/setup.php
 *		\ingroup    pagessubstitution
 *		\brief      Page to setup pagessubstitution module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/pagessubstitution/lib/pagessubstitution.lib.php');

$langs->load("admin");
$langs->load("pagessubstitution@pagessubstitution");
$langs->load("opendsi@pagessubstitution");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');

$substitutable_pages_root_path = dol_buildpath('/pagessubstitution/substitutions') . pagessubstitution_get_entity_path();
if (!file_exists($substitutable_pages_root_path)) {
    if (dol_mkdir($substitutable_pages_root_path) < 0) {
        setEventMessage($langs->trans("ErrorCanNotCreateDir", $substitutable_pages_root_path), 'errors');
    }
}


/*
 *	Actions
 */

if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    $value=(GETPOST($code) ? GETPOST($code) : 1);
    if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
} elseif ($action == 'set') {
    $hooks = GETPOST('PAGESSUBSTITUTION_HOOK');
    $ret = pagessubstitution_set_hooks(explode(',', $hooks));
    if ($ret) {
        dolibarr_set_const($db, 'PAGESSUBSTITUTION_HOOK', $hooks, 'chaine', 0, '', $conf->entity);
    }
}


/*
 *	View
 */


llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PagesSubstitutionSetup"),$linkback,'title_setup');
print "<br>\n";

$head=pagessubstitution_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Parameters"), 0, 'action');


print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// PAGESSUBSTITUTION_HOOK_TEST
$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td>' . $langs->trans("PAGESSUBSTITUTION_HOOK_TEST") . '</td>' . "\n";
print '<td align="center">&nbsp;</td>' . "\n";
print '<td align="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
    print ajax_constantonoff('PAGESSUBSTITUTION_HOOK_TEST');
} else {
    if (empty($conf->global->PAGESSUBSTITUTION_HOOK_TEST)) {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_PAGESSUBSTITUTION_HOOK_TEST">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
    } else {
        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_PAGESSUBSTITUTION_HOOK_TEST">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
    }
}
print '</td></tr>' . "\n";

print '</table>';

print '<br>';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("PagesSubstitutionSubstitutionFiles").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

$var = !$var;
print '<tr ' . $bc[$var] . '>' . "\n";
print '<td colspan="3">' . $langs->trans("PagesSubstitutionSubstitutionPagesRootPath", $substitutable_pages_root_path);
print '// Change this following line to use the correct relative path (../, ../../, etc)<br>';
print '// to work if your module directory is into a subdir of root htdocs directory<br>';
print '$res=0;<br>';
print 'if (! $res && file_exists("../../main.inc.php")) $res=@include \'../../main.inc.php\';<br>';
print 'if (! $res && file_exists("../../../main.inc.php")) $res=@include \'../../../main.inc.php\';<br>';
print 'if (! $res && file_exists("../../../../main.inc.php")) $res=@include \'../../../../main.inc.php\';<br>';
print 'if (! $res && file_exists("../../../../../main.inc.php")) $res=@include \'../../../../../main.inc.php\';<br>';
print 'if (! $res && file_exists("../../../../../../main.inc.php")) $res=@include \'../../../../../../main.inc.php\';<br>';
print 'if (! $res && file_exists("../../../../../../../main.inc.php")) $res=@include \'../../../../../../../main.inc.php\';<br>';
print 'if (! $res && file_exists("../../../../../../../../main.inc.php")) $res=@include \'../../../../../../../../main.inc.php\';<br>';
print 'if (! $res) die("Include of main fails");';
print '</td>' . "\n";
print '</tr>' . "\n";

$substitutable_pages = pagessubstitution_scanDirectories($substitutable_pages_root_path);
foreach ($substitutable_pages as $page) {
    $path_page = str_replace($substitutable_pages_root_path, '', $page);
    $const_name = pagessubstitution_get_const_name_from_substitution_path($path_page);

    $var = !$var;
    print '<tr ' . $bc[$var] . '>' . "\n";
    print '<td colspan="2">' . dol_htmlentities($path_page) . '</td>' . "\n";
    print '<td align="right">' . "\n";
    if (!empty($conf->use_javascript_ajax)) {
        print ajax_constantonoff($const_name);
    } else {
        if (empty($conf->global->{$const_name})) {
            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_'.$const_name.'">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
        } else {
            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_'.$const_name.'">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
        }
    }
    print '</td></tr>' . "\n";
}

print '</table>';

dol_fiche_end();

print '<br>';
print '<div align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

llxFooter();

$db->close();
