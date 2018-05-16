<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */



/**
*  Show tab footer of a card.
*  Note: $object->next_prev_filter can be set to restrict select to find next or previous record by $form->showrefnav.
*
*  @param	object	$object			Object to show
*  @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link
*  @param	string	$morehtml  		More html content to output just before the nav bar
*  @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
*  @param	string	$fieldid   		Nom du champ en base a utiliser pour select next et previous (we make the select max and min on this field)
*  @param	string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
*  @param	string	$morehtmlref  	More html to show after ref
*  @param	string	$moreparam  	More param to add in nav link url.
*	@param	int		$nodbprefix		Do not include DB prefix to forge table name
*	@param	string	$morehtmlleft	More html code to show before ref
*	@param	string	$morehtmlstatus	More html code to show under navigation arrows
*  @param  int     $onlybanner     Put this to 1, if the card will contains only a banner (add css 'arearefnobottom' on div)
*	@param	string	$morehtmlright	More html code to show before navigation arrows
*  @return	void
*/
if(!function_exists('dol_banner_tab') ){
function dol_banner_tab($object, $paramid, $morehtml='', $shownav=1, $fieldid='rowid', $fieldref='ref', $morehtmlref='', $moreparam='', $nodbprefix=0, $morehtmlleft='', $morehtmlstatus='', $onlybanner=0, $morehtmlright='')
{
	global $conf, $form, $user, $langs;

	$error = 0;

	$maxvisiblephotos=1;
	$showimage=1;
	$showbarcode=empty($conf->barcode->enabled)?0:($object->barcode?1:0);
	if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;
	$modulepart='unknown';

	if ($object->element == 'societe')         $modulepart='societe';
	if ($object->element == 'contact')         $modulepart='contact';
	if ($object->element == 'member')          $modulepart='memberphoto';
	if ($object->element == 'user')            $modulepart='userphoto';
	if ($object->element == 'product')         $modulepart='product';

	if (class_exists("Imagick"))
	{
			if ($object->element == 'propal')            $modulepart='propal';
		if ($object->element == 'commande')          $modulepart='commande';
		if ($object->element == 'facture')           $modulepart='facture';
		if ($object->element == 'fichinter')         $modulepart='ficheinter';
		if ($object->element == 'contrat')           $modulepart='contract';
			if ($object->element == 'supplier_proposal') $modulepart='supplier_proposal';
		if ($object->element == 'order_supplier')    $modulepart='supplier_order';
			if ($object->element == 'invoice_supplier')  $modulepart='supplier_invoice';
		if ($object->element == 'expensereport')     $modulepart='expensereport';
	}

	if ($object->element == 'product')
	{
			$width=80; $cssclass='photoref';
				$showimage=$object->is_photo_available($conf->product->multidir_output[$object->entity]);
			$maxvisiblephotos=(isset($conf->global->PRODUCT_MAX_VISIBLE_PHOTO)?$conf->global->PRODUCT_MAX_VISIBLE_PHOTO:5);
		if ($conf->browser->phone) $maxvisiblephotos=1;
		if ($showimage) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$object->show_photos($conf->product->multidir_output[$object->entity],'small',$maxvisiblephotos,0,0,0,$width,0).'</div>';
				else
				{
			if (!empty($conf->global->PRODUCT_NODISPLAYIFNOPHOTO)) {
				$nophoto='';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			}
			elseif ($conf->browser->layout != 'phone') {    // Show no photo link
				$nophoto='/public/theme/common/nophoto.png';
				$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
			}
				}
	}
	else
	{
		if ($showimage)
				{
						if ($modulepart != 'unknown')
						{
								$phototoshow='';
								// Check if a preview file is available
								if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick"))
								{
										$objectref = dol_sanitizeFileName($object->ref);
										$dir_output = $conf->$modulepart->dir_output . "/";
										if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice')))
										{
												$subdir = get_exdir($object->id, 2, 0, 0, $object, $modulepart).$objectref;
										}
										else
										{
												$subdir = get_exdir($object->id, 0, 0, 0, $object, $modulepart).$objectref;
										}
										$filepath = $dir_output . $subdir . "/";
										$file = $filepath . $objectref . ".pdf";
										$relativepath = $subdir.'/'.$objectref.'.pdf';

										// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
										$fileimage = $file.'_preview.png';              // If PDF has 1 page
										$fileimagebis = $file.'_preview-0.png';         // If PDF has more than one page
										$relativepathimage = $relativepath.'_preview.png';

										// Si fichier PDF existe
										if (file_exists($file))
										{
												$encfile = urlencode($file);
												// Conversion du PDF en image png si fichier png non existant
												if ( (! file_exists($fileimage) || (filemtime($fileimage) < filemtime($file)))
													&& (! file_exists($fileimagebis) || (filemtime($fileimagebis) < filemtime($file)))
													)
												{
														$ret = dol_convert_file($file, 'png', $fileimage);
														if ($ret < 0) $error++;
												}

												$heightforphotref=70;
												if (! empty($conf->dol_optimize_smallscreen)) $heightforphotref=60;
												// Si fichier png PDF d'1 page trouve
												if (file_exists($fileimage))
												{
														$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
														$phototoshow.= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
														$phototoshow.= '</div></div>';
												}
												// Si fichier png PDF de plus d'1 page trouve
												elseif (file_exists($fileimagebis))
												{
														$preview = preg_replace('/\.png/','',$relativepathimage) . "-0.png";
														$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
														$phototoshow.= '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($preview).'"><p>';
														$phototoshow.= '</div></div>';
												}
										}
								}
								else if (! $phototoshow)
								{
										$phototoshow = $form->showphoto($modulepart,$object,0,0,0,'photoref','small',1,0,$maxvisiblephotos);
								}

								if ($phototoshow)
								{
										$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
										$morehtmlleft.=$phototoshow;
										$morehtmlleft.='</div>';
								}
						}

						if (! $phototoshow && $conf->browser->layout != 'phone')      // Show No photo link (picto of pbject)
						{
								$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
								if ($object->element == 'action')
								{
										$width=80;
										$cssclass='photorefcenter';
										$nophoto=img_picto('', 'title_agenda', '', false, 1);
								}
								else
								{
										$width=14; $cssclass='photorefcenter';
										$picto = $object->picto;
										if ($object->element == 'project' && ! $object->public) $picto = 'project'; // instead of projectpub
						$nophoto=img_picto('', 'object_'.$picto, '', false, 1);
								}
								$morehtmlleft.='<!-- No photo to show --><div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').' src="'.$nophoto.'"></div></div>';
								$morehtmlleft.='</div>';
						}
				}
	}

	if ($showbarcode) $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">'.$form->showbarcode($object).'</div>';

	if ($object->element == 'societe')
	{
			if (! empty($conf->use_javascript_ajax) && $user->rights->societe->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE))
			{
					$morehtmlstatus.=ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
			}
	}
	elseif ($object->element == 'product')
	{
			//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Sell").') ';
				if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
						$morehtmlstatus.=ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
				} else {
						$morehtmlstatus.='<span class="statusrefsell">'.$object->getLibStatut(5,0).'</span>';
				}
				$morehtmlstatus.=' &nbsp; ';
				//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Buy").') ';
			if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
						$morehtmlstatus.=ajax_object_onoff($object, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
				} else {
						$morehtmlstatus.='<span class="statusrefbuy">'.$object->getLibStatut(5,1).'</span>';
				}
	}
	elseif (in_array($object->element, array('facture', 'invoice', 'invoice_supplier', 'chargesociales', 'loan')))
	{
			$tmptxt=$object->getLibStatut(6, $object->totalpaye);
			if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5, $object->totalpaye);
		$morehtmlstatus.=$tmptxt;
	}
	elseif ($object->element == 'contrat' || $object->element == 'contract')
	{
				if ($object->statut==0) $morehtmlstatus.=$object->getLibStatut(2);
				else $morehtmlstatus.=$object->getLibStatut(4);
	}
	else { // Generic case
			$tmptxt=$object->getLibStatut(6);
			if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5);
		$morehtmlstatus.=$tmptxt;
	}
	if (! empty($object->name_alias)) $morehtmlref.='<div class="refidno">'.$object->name_alias.'</div>';      // For thirdparty

	// Add label
	if ($object->element == 'product' || $object->element == 'bank_account' || $object->element == 'project_task')
	{
		if (! empty($object->label)) $morehtmlref.='<div class="refidno">'.$object->label.'</div>';
	}

	if ($object->element != 'product' && $object->element != 'bookmark' && $object->element != 'ecm_directories')
	{
			$morehtmlref.='<div class="refidno">';
// 			$morehtmlref.=$object->getBannerAddress('refaddress',$object);
			$morehtmlref.='</div>';
	}
	if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && in_array($object->element, array('societe', 'contact', 'member', 'product')))
	{
		$morehtmlref.='<div style="clear: both;"></div><div class="refidno">';
		$morehtmlref.=$langs->trans("TechnicalID").': '.$object->id;
		$morehtmlref.='</div>';
	}


	print '<div class="tabBar">';


// 	print '<div class="'.($onlybanner?'arearefnobottom ':'arearef ').'heightref valignmiddle" width="100%">';
// 	print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
// 	print '</div>';
//
   print '<table class="border" width="100%">';
	    // Reference
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
	print '</td>';
	print '</tr>';

    // Lastname
    print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur" colspan="3">'.$object->lastname.'&nbsp;</td>';
	print '</tr>';

    // Firstname
    print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" colspan="3">'.$object->firstname.'&nbsp;</td></tr>';

    // Login
    print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur" colspan="3">'.$object->login.'&nbsp;</td></tr>';
    print "</table><br />";

	print '<div class="underrefbanner clearboth"></div>';
}
}
