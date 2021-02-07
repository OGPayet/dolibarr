<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/quicklist/admin/setup.php
 *		\ingroup    quicklist
 *		\brief      Page to setup quicklist module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/quicklist/lib/quicklist.lib.php');

$langs->load("admin");
$langs->load("quicklist@quicklist");
$langs->load("opendsi@quicklist");

if (!$user->admin) accessforbidden();

$button_colors = array (
    'QUICKLIST_COLOR_BUTTON_MINUS',
    'QUICKLIST_COLOR_BUTTON_FILTER',
    'QUICKLIST_COLOR_BUTTON_PLUS'
);

/*
 *	Actions
 */
$action = GETPOST('action','aZ09');

// set colors
if ($action == 'update') {
    $error = 0;

    foreach ($button_colors as $constname) {
        $constvalue = GETPOST($constname, 'alpha');
        $constvalue = '#'.$constvalue;

        if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
            $error ++;
        }
    }

    if (! $error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }

    $_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');
}

if ($action == 'setshowfilters') {
    $setshowfilters = GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "QUICKLIST_SHOW_FILTERS_LIST_ABOVE_TABLE", $setshowfilters, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'mesgs');
    }
}

/*
 *	View
 */


$wikihelp='EN:QuickList_En|FR:QuickList_Fr|ES:QuickList_Es';
llxHeader('', $langs->trans("QuickListSetup"), $wikihelp,'','','', array('/quicklist/js/jscolor.js','/quicklist/js/jquery.ui.touch-punch.min.js'),'' );

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("QuickListSetup"),$linkback,'title_setup');
print "<br>\n";

$head = quicklist_admin_prepare_head();

dol_fiche_head($head, 'settings', $langs->trans("Module163011Name"), 0, 'opendsi@quicklist');

dol_htmloutput_mesg($mesg);

print '<script type="text/javascript">';
print 'r(function(){';
print '	var els = document.getElementsByTagName("link");';
print '	var els_length = els.length;';
print '	for (var i = 0, l = els_length; i < l; i++) {';
print '    var el = els[i];';
print '	   if (el.href.search("style.min.css") >= 0) {';
print '        el.href += "?" + Math.floor(Math.random() * 100);';
print '    }';
print '	}';
print '});';
print 'function r(f){/in/.test(document.readyState)?setTimeout("r("+f+")",9):f()}';
// slider
print '
$(document).ready(function() {
	var root_font_size = parseInt($("html").css("font-size").split("px")[0]),
	def_dhfs = 1.7 * root_font_size,
	def_shfs = 1.6 * root_font_size,
	def_dvmfs = 1.2 * root_font_size,
	def_svmfs = 1.2 * root_font_size,

	act_rem_dhfs = "' . $act_rem_dhfs . '",
	act_dhfs = parseFloat(act_rem_dhfs.split("rem")[0]) * root_font_size,
	act_px_dhfs = ( act_dhfs.toString() ) + "px",

	act_rem_shfs = "' . $act_rem_shfs . '",
	act_shfs = parseFloat(act_rem_shfs.split("rem")[0]) * root_font_size,
	act_px_shfs = ( act_shfs.toString() ) + "px";

	act_rem_dvmfs = "' . $act_rem_dvmfs . '",
	act_dvmfs = parseFloat(act_rem_dvmfs.split("rem")[0]) * root_font_size,
	act_px_dvmfs = ( act_dvmfs.toString() ) + "px",

	act_rem_svmfs = "' . $act_rem_svmfs . '",
	act_svmfs = parseFloat(act_rem_svmfs.split("rem")[0]) * root_font_size,
	act_px_svmfs = ( act_svmfs.toString() ) + "px";

	$("#dhfs-slider").slider({
		animate: "fast",
		min: -8,
		max: 8,
		step:1
	});
	$("#dhfs-disp-val").html(act_px_dhfs);
	$("#dhfs-stor-val").val(act_rem_dhfs);
	$("#dhfs-slider").slider("value",act_dhfs - def_dhfs);
	$("#dhfs-slider").on("slide",function(event, ui) {
		var dhfs_sel_value = $("#dhfs-slider").slider("value"),
			new_dhfs = def_dhfs + dhfs_sel_value,
			rem_dhfs = (new_dhfs / root_font_size).toString() + "rem";
		$("#dhfs-disp-val").html(new_dhfs.toString() + "px");
		$("#dhfs-stor-val").val(rem_dhfs);
		$("#tmenu_tooltip").css("font-size",rem_dhfs);
		$(".login_block").css("font-size",rem_dhfs);
	});

	$("#shfs-slider").slider({
		animate: "fast",
		min: -8,
		max: 8,
		step:1
	});
	$("#shfs-disp-val").html(act_px_shfs);
	$("#shfs-stor-val").val(act_rem_shfs);
	$("#shfs-slider").slider("value",act_shfs - def_shfs);
	$("#shfs-slider").on("slide",function(event, ui) {
		var shfs_sel_value = $("#shfs-slider").slider("value");
		var new_shfs = def_shfs + shfs_sel_value;
		var rem_shfs = (new_shfs / root_font_size).toString() + "rem";
		$("#shfs-disp-val").html(new_shfs.toString() + "px");
		$("#shfs-stor-val").val(rem_shfs);
	});

	$("#dvmfs-slider").slider({
		animate: "fast",
		min: -8,
		max: 8,
		step:1
	});
	$("#dvmfs-disp-val").html(act_px_dvmfs);
	$("#dvmfs-stor-val").val(act_rem_dvmfs);
	$("#dvmfs-slider").slider("value",act_dvmfs - def_dvmfs);
	$("#dvmfs-slider").on("slide",function(event, ui) {
		var dvmfs_sel_value = $("#dvmfs-slider").slider("value"),
			new_dvmfs = def_dvmfs + dvmfs_sel_value,
			rem_dvmfs = (new_dvmfs / root_font_size).toString() + "rem";
		$("#dvmfs-disp-val").html(new_dvmfs.toString() + "px");
		$("#dvmfs-stor-val").val(rem_dvmfs);
		$("#id-left").css("font-size",rem_dvmfs);
	});

	$("#svmfs-slider").slider({
		animate: "fast",
		min: -8,
		max: 8,
		step:1
	});
	$("#svmfs-disp-val").html(act_px_svmfs);
	$("#svmfs-stor-val").val(act_rem_svmfs);
	$("#svmfs-slider").slider("value",act_svmfs - def_svmfs);
	$("#svmfs-slider").on("slide",function(event, ui) {
		var svmfs_sel_value = $("#svmfs-slider").slider("value");
		var new_svmfs = def_svmfs + svmfs_sel_value;
		var rem_svmfs = (new_svmfs / root_font_size).toString() + "rem";
		$("#svmfs-disp-val").html(new_svmfs.toString() + "px");
		$("#svmfs-stor-val").val(rem_svmfs);
	});

});
';

print '</script>'."\n";

/********************************************************
 *  Options
 ********************************************************/

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';

// Params
print '<table class="noborder centpercent as-settings-colors">';
print '<tr class="liste_titre">';
print '<td colspan="2" style="background: #a0a0a0">' .$langs->trans('Option').'</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td width="50%">'.$langs->trans("QuickListShowFiltersListAboveTableDesc").'</td>';
if (!empty($conf->global->QUICKLIST_SHOW_FILTERS_LIST_ABOVE_TABLE)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setshowfilters&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setshowfilters&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

// Buttons
print '<tr class="liste_titre">';
print '<td colspan="2" style="background: #a0a0a0">' . $langs->trans('QuickListAdminColorsButton') . '</td>';
print '</tr>'."\n";

// Set colors
$num = count($button_colors);
if ($num)
{
    foreach ($button_colors as $key) {
        print '<tr class="value oddeven">';

        // Param
        $label = $langs->trans($key);
        print '<td width="50%">' . $label . '</td>';

        // Value
        print '<td class="right">';
        print '<input type="text" class="color" id="' . $conf->global->$key . '" name="' . $key . '" value="' . $conf->global->$key . '">';
        print '</td></tr>';
    }
}

print '</table>'."\n";

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Modify')) . '" name="button">';
print '</div>';

print '</form>';
print '<br>';

// End of page
llxFooter();
$db->close();
