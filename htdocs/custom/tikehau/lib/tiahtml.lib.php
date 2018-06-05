<?php
/**
 * Fonctions html
 */

/**
 * Affiche un formulaire
 * @param string $action
 * @param string $buttontext
 * @param int $id
 * @param string $picture
 * @return string
 */
function tia_button($action, $buttontext, $id=0, $picture='', $idname='id', $options='', $noform=0, $hidden=array())
{
		global $langs;

		$ret = '';

		if (!$noform) $ret = '<form action="" method="POST" name="'.$action.'" '.$options.' >';
		$ret .= '<input type="hidden" name="action" value="'.$action.'">';
		if ($id) $ret .= '<input type="hidden" name="'.(empty($idname)?'id':$idname).'" value="'.$id.'">';
		//dol_syslog(print_r($hidden,true), LOG_DEBUG);
		if (count($hidden)) foreach($hidden as $field => $value) $ret .=  '<input type="hidden" name="'.$field.'" value="'.$value.'">';
		if ($picture)
		{
			if (file_exists(dol_buildpath($picture))) $ret .= '<input type="image" src="'.dol_buildpath($picture,1).'" alt="'.$langs->trans($buttontext).'"  />';
			else $ret .= '<input class="button" type="submit" value="'.$langs->trans($buttontext).'">';
		}
		else $ret .= '<input class="button" type="submit" value="'.$langs->trans($buttontext).'">';
		if (!$noform) $ret .= '</form>';

		return $ret;

}

/**
 *
 * @param unknown $url
 * @param unknown $langskey
 * @param unknown $picto
 * @param string $objectid
 * @param string $actionget
 * @return string
 */
function tia_buttonlink($url, $langskey, $picto='', $objectid='', $actionget='')
{
	global $langs;

	$urlparams = '';

	if ($actionget) $urlparams = 'action='.$actionget.'&amp;';
	if ($objectid != '') $urlparams .= 'id='.$objectid;
	if (empty($picto)) $picto = "/common.png";
	$picto = DOL_URL_ROOT.$picto;
	dol_syslog(__FUNCTION__.":: picto = $picto dolurlroot ".DOL_URL_ROOT, LOG_DEBUG);
	if ($urlparams) $urlparams = "?".trim($urlparams, "&amp;");
	$ret = '<a href="'.$url.$urlparams.'">'.img_picto_common($langs->trans($langskey), $picto, "", 1).'</a>';
	return $ret;
}

/**
 * Affiche un formulaire
 * @param string $action
 * @param string $buttontext
 * @param int $id
 * @param string $picture
 * @return string
 */
function tia_fieldbutton($fieldname, $fieldvalue, $action, $buttontext, $id=0, $picture='', $idname='id', $options='')
{
		global $langs;

		$ret = '';

		$ret .= '<form action="" method="POST" name="'.$action.'" '.$options.' >';
		$ret .= '<input type="hidden" name="faction" value="'.$action.'">';
		if ($id) $ret .= '<input type="hidden" name="'.$idname.'" value="'.$id.'">';
		$ret .= '<input type="text" name="'.$fieldname.'" value="'.$fieldvalue.'" />';
		$ret .= '<input class="button" type="submit" value="'.$langs->trans($buttontext).'">';
		if (!$noform)  $ret .= '</form>';

		return $ret;

}

function tia_headerrow($lib, $data, $form="")
{
	global $langs;

	$ret = '<tr><td>'.$langs->trans($lib);
	if ($form ) $ret .= "&nbsp;".$form;
	$ret .= '</td>';
	$ret .= '<td>'.$data.'</td>';
	//if ($form ) $ret .= '</td><td>'.$form;
	$ret .= '</tr>';

	return $ret;
}

/**
 * affiche une entete de tableau
 * @param unknown $listeentete liste de colonne:tri:classehtml
 * @param string $type
 * @param string $htmlclass class pour le tr
 * @return string
 */
function tia_tableheader($listeentete, $type ='', $htmlclass='')
{
	if (!is_array($listeentete) || ! count($listeentete)) return '';

	if (! empty($htmlclass)) $htmlclass = ' '.$htmlclass;

	$html = '<tr'.$htmlclass.'>';
	foreach ($listeentete as $colonne)
	{
		$col = explode(':', $colonne);
		$label = $col[0];
		// TODO $col[1] indique s'il faut mettre les tris
		// TODO $col[2] une balise html
		$balise = '';
		if (count($col) == 3 ) $balise = ' ' .$col[2];

		$html .= '<th'.$balise.'>'.$label.'</th>';
	}
	$html .= '</tr>';
	// TODO type indique les colonnes de recherche

	return $html;
}

function tia_form_select($liste, $action, $selectname, $buttonname, $page='', $idselected='')
{
	global $db, $langs, $conf;

	require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

	$ret = '<form method="post" action="'.$page.'">';
	$ret .= '<input type="hidden" name="action" value="'.$action.'">';
	$ret .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	$ret .= '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
	$ret .= '<tr><td>';
	$f = new Form($db);
	$ret .= $f->selectarray($selectname, $liste,$idselected, 1);
	$ret .= '</td>';
	$ret .= '<td align="left"><input type="submit" class="button" value="'.$langs->trans($buttonname).'"></td>';
	$ret .= '</tr></table></form>';

	return $ret;
}

function tia_updatebutton($actionget, $objectid='', $langskey, $obj='')
{
	global $langs;
	$urlparams = '?action='.$actionget;
	$id = ($obj)?$obj:'id';
	if ($objectid != '') $urlparams .= '&amp;'.$id.'='.$objectid;
	$ret = '<a href="'.$_SERVER["PHP_SELF"].$urlparams.'">'.img_edit($langs->trans($langskey),1).'</a>';
	return $ret;
}

function tia_form_selectdate($htmlname, $objectid='', $action, $datein)
{
	global $db, $langs;

	require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
	$form = new Form($db);
	$url = $_SERVER["PHP_SELF"] ;
	if ($objectid) $url .= '?id=' . $objectid;

	$ret = '<form name="setdate_'.$htmlname.'" action="' . $url . '" method="post">';
	$ret .= '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	$ret .= '<input type="hidden" name="action" value="'.$action.'">';
	$ret .= $form->select_date(($datein) ? $datein : -1, 'dat_', '', '', '', $htmlname, 1, 1,1, 0,1);
	$ret .= '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
	$ret .= '</form>';
	dol_syslog(__FUNCTION__."::".$ret, LOG_DEBUG);
	return $ret;
}


function tia_form_selectperiod($htmlname, $objectid='', $action, $datein)
{
	global $db, $langs;

	require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
	$form = new Form($db);
	$url = $_SERVER["PHP_SELF"] ;
	if ($objectid) $url .= '?id=' . $objectid;

	$ret = '';
	$ret .= '<form name="setperiode_'.$htmlname.'" action="' . $url . '" method="post">';
	$ret .='<div class="divsearchfield">';
	$ret .= $langs->trans('Period') .' : ' . $langs->trans('DateStart') . ' ';
	$ret .= $form->select_date($dt_start, 'start_dt', 0, 0, 1, "seardch_form", 1, 0, 1);
	$ret .= ' - ';
	$ret .= $langs->trans('DateEnd') . ' ' . $form->select_date($dt_end, 'end_dt', 0, 0, 1, "search_form", 1, 0, 1);
	$ret .= '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
	$ret .= '</div>';
	$ret .= '</form>';
	dol_syslog(__FUNCTION__."::".$ret, LOG_DEBUG);
	return $ret;
}

function tia_inputfield( $langskey, $formname, $typefield='text', $value='', $size='', $form=false)
{
	global $langs;
	$ret = '';
	if ($langskey) $ret .= '<td>'.$langs->trans($langskey).'</td>';
	$ret .= '<td><input type="'.$typefield.'" name="'.$formname.'" ';
	if ($size) $ret .= ' size="'.$size.'" ';
	if ($value) $ret .= ' value="'.$value.'" ';
	$ret .= ' /></td>';

	return $ret;
}

function tia_simpleinputfield(  $formname, $typefield='text', $value='', $size='', $selected = '')
{
	global $langs;
	$ret .= '<input type="'.$typefield.'" name="'.$formname.'" ';
	if ($size) $ret .= ' size="'.$size.'" ';
	if ($value) $ret .= ' value="'.$value.'" ';
	if ($selected) $ret .= " ".$selected;
	$ret .= ' />';

	return $ret;
}

/**
 * renvoie une chaîne html avec une liste de sélection
 * @param unknown $langskey
 * @param unknown $formname
 * @param unknown $liste
 * @param number $idselected
 * @param number $acceptempty
 * @return string
 */
function tia_inputselect($langskey, $formname, $liste, $idselected=0, $acceptempty=1 )
{
	global $langs, $db;
	require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

	$html = '<td>'.$langs->trans($langskey).'</td>';
	$f = new Form($db);
	$html .= '<td>'.$f->selectarray($formname, $liste,$idselected, $acceptempty).'</td>';

	return $html;
}

function tia_inputmultiselect($langskey, $formname, $liste, $selected = array() )
{
	global $langs;
	require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
	$form = new Form($db);

	$html = '<td>'.$langs->trans($langskey).'</td>';
	$html .= '<td>'.$form->multiselectarray($formname, $liste, $selected).'</td>';

	return $html;
}

function tik_setup_header($module,$params="")
{
	global $langs, $conf, $user;
	$langs->load("tikehau@tikehau");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/'.$module.'/admin/setup.php',1);
	$head[$h][1] = $langs->trans("TikSetup");
	$head[$h][2] = 'configuration';
	$h++;

	$head[$h][0] = dol_buildpath('/'.$module.'/admin/about.php',1);
	$head[$h][1] = $langs->trans("TikAbout");
	$head[$h][2] = 'about';
	$h++;

	return $head;
}
?>