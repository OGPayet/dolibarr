<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/admin.php
 * 	\ingroup	owntheme
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

require_once DOL_DOCUMENT_ROOT . '/owntheme/lib/owntheme.lib.php';


// Translations
$langs->load("admin");
$langs->load("owntheme@owntheme");

// Access control
if (! $user->admin) accessforbidden();



/*
 * Actions
 */
$mesg="";
$action = GETPOST('action', 'alpha');

if (preg_match('/^set/',$action)) {
  // This is to force to add a new param after css urls to force new file loading
  // This set must be done before calling llxHeader().
//  $_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');
}

// set toggled options
if ($action == 'onoff') { 
  $name = GETPOST ( 'name', 'text' );
  $value = GETPOST ( 'value', 'int' );
	
	if ($value) {
		$res = dolibarr_set_const($db, $name, 1, 'yesno', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, $name, 0, 'yesno', 0, '', $conf->entity);
	}
	
	if (! $res > 0)	$error ++;
	
	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
} 

// set defaults colors
elseif ($action == 'set_def_colors') {
	$_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');
	$mesg = "<font class='ok'>".$langs->trans("DefaultsColorsMsg")."</font>";
	$source = dol_buildpath('/owntheme/css/as_style.min.css.default');
	$dest = dol_buildpath('/owntheme/css/as_style.min.css');
	copy($source,$dest);
	$col1="#6a89cc";dolibarr_set_const($db, "OWNTHEME_COL1", $col1,'chaine',0,'',$conf->entity);
	$col2="#60a3bc";dolibarr_set_const($db, "OWNTHEME_COL2", $col2,'chaine',0,'',$conf->entity);
	$col3="#e9e9e9";dolibarr_set_const($db, "OWNTHEME_COL_BODY_BCKGRD", $col3,'chaine',0,'',$conf->entity);
	$col5_="#474c80";dolibarr_set_const($db, "OWNTHEME_COL_LOGO_BCKGRD", $col5_,'chaine',0,'',$conf->entity);
	$col6_="#b8c6e5";dolibarr_set_const($db, "OWNTHEME_COL_TXT_MENU", $col6_,'chaine',0,'',$conf->entity);
	$col4="#474c80";dolibarr_set_const($db, "OWNTHEME_COL_HEADER_BCKGRD", $col4,'chaine',0,'',$conf->entity);
}

// set primary or secondary colors
elseif ($action == 'set_color') {
	$_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');
	$name = GETPOST ( 'name', 'text' );
	$value = GETPOST ( 'value', 'text' );
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
	$file = dol_buildpath('/owntheme/css/as_style.min.css');

	$oldvalue = dolibarr_get_const($db, $name);
	$newvalue = '#' . strtoupper($value);
	dolibarr_set_const($db, $name, $newvalue ,'chaine',0,'',$conf->entity);

	$file_contents = file_get_contents($file);
	$file_contents = str_replace($oldvalue,$newvalue,$file_contents);
	file_put_contents($file,$file_contents);
}

// set font sizes
elseif ($action == 'set_hfs') {
	$_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');

	$dhfs = GETPOST ( 'OWNTHEME_D_HEADER_FONT_SIZE', 'alpha' );
	if (!empty($dhfs)) {
		$res = dolibarr_set_const($db, 'OWNTHEME_D_HEADER_FONT_SIZE', $dhfs, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'OWNTHEME_D_HEADER_FONT_SIZE', '000000', 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0)	$error ++;

	$shfs = GETPOST ( 'OWNTHEME_S_HEADER_FONT_SIZE', 'alpha' );
	if (!empty($shfs)) {
		$res = dolibarr_set_const($db, 'OWNTHEME_S_HEADER_FONT_SIZE', $shfs, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'OWNTHEME_S_HEADER_FONT_SIZE', '000000', 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0)	$error ++;
}

elseif ($action == 'set_vmfs') {
	$_SESSION['dol_resetcache']=dol_print_date(dol_now(),'dayhourlog');
	$dvmfs = GETPOST ( 'OWNTHEME_D_VMENU_FONT_SIZE', 'alpha' );
	if (!empty($dvmfs)) {
		$res = dolibarr_set_const($db, 'OWNTHEME_D_VMENU_FONT_SIZE', $dvmfs, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'OWNTHEME_D_VMENU_FONT_SIZE', '000000', 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0)	$error ++;

	$svmfs = GETPOST ( 'OWNTHEME_S_VMENU_FONT_SIZE', 'alpha' );
	if (!empty($svmfs)) {
		$res = dolibarr_set_const($db, 'OWNTHEME_S_VMENU_FONT_SIZE', $svmfs, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'OWNTHEME_S_VMENU_FONT_SIZE', '000000', 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0)	$error ++;

	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

// Get settings
$col1=$conf->global->OWNTHEME_COL1;
$col2=$conf->global->OWNTHEME_COL2;
$col3=$conf->global->OWNTHEME_COL_BODY_BCKGRD;
$col5_=$conf->global->OWNTHEME_COL_LOGO_BCKGRD;
$col6_=$conf->global->OWNTHEME_COL_TXT_MENU;
$col4=$conf->global->OWNTHEME_COL_HEADER_BCKGRD;
$act_rem_dhfs=$conf->global->OWNTHEME_D_HEADER_FONT_SIZE;
$act_rem_shfs=$conf->global->OWNTHEME_S_HEADER_FONT_SIZE;
$act_rem_dvmfs=$conf->global->OWNTHEME_D_VMENU_FONT_SIZE;
$act_rem_svmfs=$conf->global->OWNTHEME_S_VMENU_FONT_SIZE;

/*
 * View
 */
$page_name = "OwnThemeSetup";
llxHeader('', $langs->trans($page_name),'','','','', array('/owntheme/js/jscolor.js','/owntheme/js/jquery.ui.touch-punch.min.js'),'' );

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = owntheme_admin_prepare_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("Module500500Name"),
	0,
	"logo@owntheme"
);

dol_htmloutput_mesg($mesg);


// Setup page goes here

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
	act_dhfs = Math.round(parseFloat(act_rem_dhfs.split("rem")[0]) * root_font_size),
	act_px_dhfs = ( act_dhfs.toString() ) + "px",

	act_rem_shfs = "' . $act_rem_shfs . '",
	act_shfs = Math.round(parseFloat(act_rem_shfs.split("rem")[0]) * root_font_size),
	act_px_shfs = ( act_shfs.toString() ) + "px";

	act_rem_dvmfs = "' . $act_rem_dvmfs . '",
	act_dvmfs = Math.round(parseFloat(act_rem_dvmfs.split("rem")[0]) * root_font_size),
	act_px_dvmfs = ( act_dvmfs.toString() ) + "px",

	act_rem_svmfs = "' . $act_rem_svmfs . '",
	act_svmfs = Math.round(parseFloat(act_rem_svmfs.split("rem")[0]) * root_font_size),
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
		var dhfs_sel_value = ui.value,
			new_dhfs = def_dhfs + dhfs_sel_value,
			rem_dhfs = (new_dhfs / root_font_size).toString() + "rem";
		$("#dhfs-disp-val").html(new_dhfs.toString() + "px");
		$("#dhfs-stor-val").val(rem_dhfs);
		$("#tmenu_tooltip").css("font-size",rem_dhfs);
		$(".login_block").css("font-size",rem_dhfs);
		sizes_calc();
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
	sizes_calc();
	$("#shfs-slider").on("slide",function(event, ui) {
		var shfs_sel_value = ui.value,
			new_shfs = def_shfs + shfs_sel_value,
			rem_shfs = (new_shfs / root_font_size).toString() + "rem";
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
		var dvmfs_sel_value = ui.value,
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
	sizes_calc();
	$("#svmfs-slider").on("slide",function(event, ui) {
		var svmfs_sel_value = ui.value,
			new_svmfs = def_svmfs + svmfs_sel_value,
			rem_svmfs = (new_svmfs / root_font_size).toString() + "rem";
		$("#svmfs-disp-val").html(new_svmfs.toString() + "px");
		$("#svmfs-stor-val").val(rem_svmfs);
	});

});
';

print '</script>'."\n";

print '<br>'."\n";

// COLORS
print '<div class="subsetting-title">' . $langs->trans("AS_SettingsColors") . '</div>';
print '<table class="noborder as-settings-colors">';

// HEADER ROW
/*
print '<tr class="liste_titre">';
print 	'<th>' . $langs->trans("Name") . '</td>';
print 	'<th>' . $langs->trans("Value") . '</td>';
print "</tr>\n";
*/
print '
<tr class="liste_titre">
	<th>' . $langs->trans("Name") . '</td>
	<th>' . $langs->trans("Value") . '</td>
</tr>
'."\n";

// SET PRIMARY COLOR
print '
<tr class="pair">
	<td>' . $langs->trans("PrimaryColor") . '</td>
	<td>
		<form id="col1-form" method="post" action="admin.php">
			<input type="hidden" name="action" value="set_color">
			<input type="hidden" name="name" value="OWNTHEME_COL1">
			<input id="col1" class="color" type=text name="value" value="' . $col1 . '">
			<input type="submit" class="button" value="Valider">
		</form>
	</td>
</tr>
'."\n";

// SET SECONDARY COLOR
print '
<tr class="pair">
	<td>' . $langs->trans("SecondaryColor") . '</td>
	<td>
		<form id="col2-form" method="post" action="admin.php">
			<input type="hidden" name="action" value="set_color">
			<input type="hidden" name="name" value="OWNTHEME_COL2">
			<input id="col2" class="color" type=text name="value" value="' . $col2 . '">
			<input type="submit" class="button" value="Valider">
		</form>
	</td>
</tr>
'."\n";

// SET HEADER BACKGROUND COLOR
print '
<tr class="pair">
	<td>' . $langs->trans("HeaderBckgrdColor") . '</td>
	<td>
		<form id="col4-form" method="post" action="admin.php">
			<input type="hidden" name="action" value="set_color">
			<input type="hidden" name="name" value="OWNTHEME_COL_HEADER_BCKGRD">
			<input id="col4" class="color" type=text name="value" value="' . $col4 . '">
			<input type="submit" class="button" value="Valider">
		</form>
	</td>
</tr>
'."\n";

// SET BODY BACKGROUND COLOR
print '
<tr class="pair">
	<td>' . $langs->trans("BodyBckgrdColor") . '</td>
	<td>
		<form id="col3-form" method="post" action="admin.php">
			<input type="hidden" name="action" value="set_color">
			<input type="hidden" name="name" value="OWNTHEME_COL_BODY_BCKGRD">
			<input id="col3" class="color" type=text name="value" value="' . $col3 . '">
			<input type="submit" class="button" value="Valider">
		</form>
	</td>
</tr>
'."\n";

// SET LOGO BACKGROUND COLOR
print '
<tr class="pair">
	<td>' . $langs->trans("LogoBckgrdColor") . '</td>
	<td>
		<form id="col5-form" method="post" action="admin.php">
			<input type="hidden" name="action" value="set_color">
			<input type="hidden" name="name" value="OWNTHEME_COL_LOGO_BCKGRD">
			<input id="col5_" class="color" type=text name="value" value="' . $col5_ . '">
			<input type="submit" class="button" value="Valider">
		</form>
	</td>
</tr>
'."\n";

// SET LOGO TEXT MENU COLOR
print '
<tr class="pair">
	<td>' . $langs->trans("TxtMenuColor") . '</td>
	<td>
		<form id="col5-form" method="post" action="admin.php">
			<input type="hidden" name="action" value="set_color">
			<input type="hidden" name="name" value="OWNTHEME_COL_TXT_MENU">
			<input id="col6_" class="color" type=text name="value" value="' . $col6_ . '">
			<input type="submit" class="button" value="Valider">
		</form>
	</td>
</tr>
'."\n";

print '</table>'."\n";

// SET DEFAULTS COLORS
print '
<br>
<form id="as-def-colors" method="post" action="admin.php">
	<input type="hidden" name="action" value="set_def_colors">
	<input type="submit" class="button" value="' . $langs->trans("DefaultsColors") . '">
</form>
<br>
<br>
'."\n";

	// <label for="defcolors">' . $langs->trans("DefaultsColors") . ' : </label>

// FONT SIZES
print '<div class="subsetting-title">' . $langs->trans("AS_SettingsFontSizes") . '</div>';
print '
<table class="noborder as-settings-font-sizes">
	<col class="col1" />
	<col class="col2" />
	<col class="col3" />
	<col class="col4" />
	<col class="col5" />
	<col class="col6" />
	<thead>
		<tr class="liste_titre">
			<th class="">' . $langs->trans("Element") . '</th>
			<th class="hideonsmartphone"" colspan="2">' . $langs->trans("Desktop") . '</th>
			<th class="" colspan="2">' . $langs->trans("Smartphone") . '</th>
			<th class="">' . $langs->trans("Validation") . '</th>
		</tr>
	</thead>
'."\n";

print '<tbody>'."\n";


// HEADER FONT SIZE
print '
	<form method="post" action="' . $_SERVER ['PHP_SELF'] . '" enctype="multipart/form-data" >
	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
	<input type="hidden" name="action" value="set_hfs">
	<tr class="pair">
		<td>'.$langs->trans('HeaderFontSize').'</td>

		<td id="dhfs-val-cell" class="value-cell hideonsmartphone">
			<div id="dhfs-disp-val"></div>
			<input id="dhfs-stor-val" type="hidden" name="OWNTHEME_D_HEADER_FONT_SIZE">
		</td>
		<td class="slider-cell hideonsmartphone"><div id="dhfs-slider"></div></td>

		<td id="shfs-val-cell" class="value-cell">
			<div id="shfs-disp-val"></div>
			<input id="shfs-stor-val" type="hidden" name="OWNTHEME_S_HEADER_FONT_SIZE">
		</td>
		<td class="slider-cell"><div id="shfs-slider"></div></td>

		<td><input type="submit" class="button" value="Valider"></td>
	</tr>
	</form>
'."\n";

// SLIDING MENU FONT SIZE
print '
	<form method="post" action="' . $_SERVER ['PHP_SELF'] . '" enctype="multipart/form-data" >
	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
	<input type="hidden" name="action" value="set_vmfs">
	<tr class="pair">
		<td>'.$langs->trans('VmenuFontSize').'</td>

		<td id="dvmfs-val-cell" class="value-cell hideonsmartphone">
			<div id="dvmfs-disp-val"></div>
			<input id="dvmfs-stor-val" type="hidden" name="OWNTHEME_D_VMENU_FONT_SIZE">
		</td>
		<td class="slider-cell hideonsmartphone"><div id="dvmfs-slider"></div></td>

		<td id="svmfs-val-cell" class="value-cell">
			<div id="svmfs-disp-val"></div>
			<input id="svmfs-stor-val" type="hidden" name="OWNTHEME_S_VMENU_FONT_SIZE">
		</td>
		<td class="slider-cell"><div id="svmfs-slider"></div></td>

		<td><input type="submit" class="button" value="Valider"></td>
	</tr>
	</form>
'."\n";

print '
</tbody>
</table>
<br>
<br>
';

// OPTIONS
// print '<div class="subsetting-title">' . $langs->trans("AS_SettingsOptions") . '</div>';
// print '<table class="noborder as-settings-options">';

// print '<tr class="liste_titre">';
// print 	'<th>' . $langs->trans("Name") . '</td>';
// print 	'<th class="hideonsmartphone">' . $langs->trans("Description") . '</td>';
// print 	'<th>' . $langs->trans("Value") . '</td>';
// print "</tr>\n";

// FIXED VERTICAL MENU
// print '<tr class="pair">';
// print 	'<td>'.$langs->trans('FixedMenu').'</td>';
// print 	'<td class="hideonsmartphone">'.$langs->trans('FixedMenuDescr').'</td>';
// $name='OWNTHEME_FIXED_MENU';
// if (! empty ( $conf->global->OWNTHEME_FIXED_MENU )) {
// print 	'<td><a href="' . $_SERVER ['PHP_SELF'] . '?action=onoff&name='.$name.'&value=0">';
// print img_picto ( $langs->trans ( "Enabled" ), 'switch_on' );
// print 	"</a></td>";
// } else {
// print 	'<td><a href="' . $_SERVER ['PHP_SELF'] . '?action=onoff&name='.$name.'&value=1">';
// print img_picto ( $langs->trans ( "Disabled" ), 'switch_off' );
// print 	"</a></td>";
// }
// print "</tr>\n";

// print '</table>';
// print '<br>';
// print '<br>';


// // ADVANCED OPTIONS
// print '<div class="subsetting-title">' . $langs->trans("AS_AdvSettingsOptions") . '</div>';
// print '<table class="noborder as-settings-options">';

// print '<tr class="liste_titre">';
// print 	'<th>' . $langs->trans("Name") . '</td>';
// print 	'<th class="hideonsmartphone">' . $langs->trans("Description") . '</td>';
// print 	'<th>' . $langs->trans("Value") . '</td>';
// print "</tr>\n";

// // CUSTOM CSS
// print '<tr class="pair">';
// print 	'<td>'.$langs->trans('CustomCSS').'</td>';
// print 	'<td class="hideonsmartphone">'.$langs->trans('CustomCSSDescr').'</td>';
// $name='OWNTHEME_CUSTOM_CSS';
// if (! empty ( $conf->global->OWNTHEME_CUSTOM_CSS )) {
// print 	'<td><a href="' . $_SERVER ['PHP_SELF'] . '?action=onoff&name='.$name.'&value=0">';
// print img_picto ( $langs->trans ( "Enabled" ), 'switch_on' );
// print 	"</a></td>";
// } else {
// print 	'<td><a href="' . $_SERVER ['PHP_SELF'] . '?action=onoff&name='.$name.'&value=1">';
// print img_picto ( $langs->trans ( "Disabled" ), 'switch_off' );
// print 	"</a></td>";
// }
// print "</tr>\n";

// // CUSTOM JS
// print '<tr class="pair">';
// print 	'<td>'.$langs->trans('CustomJS').'</td>';
// print 	'<td class="hideonsmartphone">'.$langs->trans('CustomJSDescr').'</td>';
// $name='OWNTHEME_CUSTOM_JS';
// if (! empty ( $conf->global->OWNTHEME_CUSTOM_JS )) {
// print 	'<td><a href="' . $_SERVER ['PHP_SELF'] . '?action=onoff&name='.$name.'&value=0">';
// print img_picto ( $langs->trans ( "Enabled" ), 'switch_on' );
// print 	"</a></td>";
// } else {
// print 	'<td><a href="' . $_SERVER ['PHP_SELF'] . '?action=onoff&name='.$name.'&value=1">';
// print img_picto ( $langs->trans ( "Disabled" ), 'switch_off' );
// print 	"</a></td>";
// }
// print "</tr>\n";

// print '</table>';

print '<br>';


// Page end
dol_fiche_end();
llxFooter();
$db->close();
?>