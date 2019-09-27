<?php

/**
 * \file    infoextranet/lib/output.lib.php
 * \ingroup infoextranet
 * \brief   Library files with common functions for InfoExtranet
 */

require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

/**
 *      Generate Header for documentation tab
 *
 *      @return     array                       Header to send to dol_fiche_head()
 */
function generateDocumentationHeader()
{
    global $langs;

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/infoextranet/user_doc.php', 1);
    $head[$h][1] = $langs->trans("Documentation Utilisateur");
    $head[$h][2] = 'user_doc';

    $h++;

    $head[$h][0] = dol_buildpath('/infoextranet/technical_doc.php', 1);
    $head[$h][1] = $langs->trans("Documentation Technique");
    $head[$h][2] = 'technical_doc';

    $h++;

    $head[$h][0] = dol_buildpath('/infoextranet/changelog.php', 1);
    $head[$h][1] = $langs->trans("Changelog");
    $head[$h][2] = 'changelog';

    $h++;

    $head[$h][0] = dol_buildpath("/infoextranet/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    return $head;
}


/**
 * Search function that get the id of an object and change it into an other key.
 *
 * @param string        $search                       Object searched
 * @param string        $table_name_use               Table name of the current object
 * @param string        $need_use                     Key of Object searched
 * @param string        $table_name_search            Table name of the object searched
 * @param string        $need_search                  The other key
 * @return array|null|string                          Null on error
 *
 */
function transformSearchToNeeded($search, $table_name_use, $need_use, $table_name_search, $need_search)
{
    global $db;

    if ($search == null)
        return null;
    $sql = 'SELECT t.'.$need_use.' FROM '.MAIN_DB_PREFIX.'infoextranet_'.$table_name_use.' AS t WHERE 1 = 1 AND t.'.$need_use.' IN (';
    $sql.= 'SELECT * FROM (';
    $sql.= 'SELECT s.rowid FROM llx_'.$table_name_search.' AS s ';
    $sql.= 'WHERE s.'.$need_search.' LIKE \'%'.$search.'%\' ) AS subquery)';

    $arr = array();
    $resql = $db->query($sql);
    if ($resql)
    {
        foreach ($resql as $key => $field)
            $arr[] = $field[$need_use];
    }
    $arr = implode(',', $arr);
    return $arr;
}

/**
 * Return HTML string to put an output field into a page
 * Duplicate function of Extrafields class because this function doesn't display output
 * if an extrafield is hidden and our extrafields are hidden
 *
 * @param   string	$key            		Key of attribute
 * @param   string	$value          		Value to show
 * @param   Object  $extrafields            Extrafield class
 * @param   boolean $icon                   Display icon instead of checkbox for boolean
 * @param	string	$moreparam				To add more parameters on html input tag (only checkbox use html input for output rendering)
 * @param	string	$extrafieldsobjectkey	If defined, use the new method to get extrafields data
 * @return	string							Formated value
 */
function mShowOutputField($key, $value, $extrafields, $icon = false, $moreparam='', $extrafieldsobjectkey='')
{
    global $conf,$langs;

    if (! empty($extrafieldsobjectkey))
    {
        $elementtype=$extrafields->attributes[$extrafieldsobjectkey]['elementtype'][$key];	// seems not used
        $label=$extrafields->attributes[$extrafieldsobjectkey]['label'][$key];
        $type=$extrafields->attributes[$extrafieldsobjectkey]['type'][$key];
        $size=$extrafields->attributes[$extrafieldsobjectkey]['size'][$key];
        $default=$extrafields->attributes[$extrafieldsobjectkey]['default'][$key];
        $computed=$extrafields->attributes[$extrafieldsobjectkey]['computed'][$key];
        $unique=$extrafields->attributes[$extrafieldsobjectkey]['unique'][$key];
        $required=$extrafields->attributes[$extrafieldsobjectkey]['required'][$key];
        $param=$extrafields->attributes[$extrafieldsobjectkey]['param'][$key];
        $perms=$extrafields->attributes[$extrafieldsobjectkey]['perms'][$key];
        $langfile=$extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key];
        $list=$extrafields->attributes[$extrafieldsobjectkey]['list'][$key];
        $hidden=(($list == 0) ? 1 : 0);		// If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
    }
    else
    {
        $elementtype=$extrafields->attribute_elementtype[$key];	// seems not used
        $label=$extrafields->attribute_label[$key];
        $type=$extrafields->attribute_type[$key];
        $size=$extrafields->attribute_size[$key];
        $default=$extrafields->attribute_default[$key];
        $computed=$extrafields->attribute_computed[$key];
        $unique=$extrafields->attribute_unique[$key];
        $required=$extrafields->attribute_required[$key];
        $param=$extrafields->attribute_param[$key];
        $perms=$extrafields->attribute_perms[$key];
        $langfile=$extrafields->attribute_langfile[$key];
        $list=$extrafields->attribute_list[$key];
        $hidden=(($list == 0) ? 1 : 0);		// If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
    }

    //if ($hidden) return '';		// This is a protection. If field is hidden, we should just not call this method.

    // If field is a computed field, value must become result of compute
    if ($computed)
    {
        // Make the eval of compute string
        //var_dump($computed);
        $value = dol_eval($computed, 1, 0);
    }

    $showsize=0;
    if ($type == 'date')
    {
        $showsize=10;
        $value=dol_print_date($value, 'day');
    }
    elseif ($type == 'datetime')
    {
        $showsize=19;
        $value=dol_print_date($value, 'dayhour');
    }
    elseif ($type == 'int')
    {
        $showsize=10;
    }
    elseif ($type == 'double')
    {
        if (!empty($value)) {
            $value=price($value);
        }
    }
    elseif ($type == 'boolean')
    {
        $checked='';
        if (!empty($value)) {
            $checked=' checked ';
        }
        if (! $icon)
            $value='<input type="checkbox" '.$checked.' '.($moreparam?$moreparam:'').' readonly disabled>';
        else
        {
            if (!empty($value))
                $value='<i class="fa fa-check"></i>';
            else
                $value='<i class="fa fa-times" style="opacity: 0.3"></i>';
        }
    }
    elseif ($type == 'mail')
    {
        $value=dol_print_email($value, 0, 0, 0, 64, 1, 1);
    }
    elseif ($type == 'url')
    {
        $value=dol_print_url($value,'_blank',32,1);
    }
    elseif ($type == 'phone')
    {
        $value=dol_print_phone($value, '', 0, 0, '', '&nbsp;', 1);
    }
    elseif ($type == 'price')
    {
        $value=price($value, 0, $langs, 0, 0, -1, $conf->currency);
    }
    elseif ($type == 'select')
    {
        $value=$param['options'][$value];
    }
    elseif ($type == 'sellist')
    {
        $param_list=array_keys($param['options']);
        $InfoFieldList = explode(":", $param_list[0]);

        $selectkey="rowid";
        $keyList='rowid';

        if (count($InfoFieldList)>=3)
        {
            $selectkey = $InfoFieldList[2];
            $keyList=$InfoFieldList[2].' as rowid';
        }

        $fields_label = explode('|',$InfoFieldList[1]);
        if(is_array($fields_label)) {
            $keyList .=', ';
            $keyList .= implode(', ', $fields_label);
        }

        $sql = 'SELECT '.$keyList;
        $sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
        if (strpos($InfoFieldList[4], 'extra')!==false)
        {
            $sql.= ' as main';
        }
        if ($selectkey=='rowid' && empty($value)) {
            $sql.= " WHERE ".$selectkey."=0";
        } elseif ($selectkey=='rowid') {
            $sql.= " WHERE ".$selectkey."=".$extrafields->db->escape($value);
        }else {
            $sql.= " WHERE ".$selectkey."='".$extrafields->db->escape($value)."'";
        }

        //$sql.= ' AND entity = '.$conf->entity;

        dol_syslog(get_class($extrafields).':showOutputField:$type=sellist', LOG_DEBUG);
        $resql = $extrafields->db->query($sql);
        if ($resql)
        {
            $value='';	// value was used, so now we reste it to use it to build final output

            $obj = $extrafields->db->fetch_object($resql);

            // Several field into label (eq table:code|libelle:rowid)
            $fields_label = explode('|',$InfoFieldList[1]);

            if(is_array($fields_label) && count($fields_label)>1)
            {
                foreach ($fields_label as $field_toshow)
                {
                    $translabel='';
                    if (!empty($obj->$field_toshow)) {
                        $translabel=$langs->trans($obj->$field_toshow);
                    }
                    if ($translabel!=$field_toshow) {
                        $value.=dol_trunc($translabel,18).' ';
                    }else {
                        $value.=$obj->$field_toshow.' ';
                    }
                }
            }
            else
            {
                $translabel='';
                if (!empty($obj->{$InfoFieldList[1]})) {
                    $translabel=$langs->trans($obj->{$InfoFieldList[1]});
                }
                if ($translabel!=$obj->{$InfoFieldList[1]}) {
                    $value=dol_trunc($translabel,18);
                }else {
                    $value=$obj->{$InfoFieldList[1]};
                }
            }
        }
        else dol_syslog(get_class($extrafields).'::showOutputField error '.$extrafields->db->lasterror(), LOG_WARNING);
    }
    elseif ($type == 'radio')
    {
        $value=$param['options'][$value];
    }
    elseif ($type == 'checkbox')
    {
        $value_arr=explode(',',$value);
        $value='';
        $toprint=array();
        if (is_array($value_arr))
        {
            foreach ($value_arr as $keyval=>$valueval) {
                $toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$param['options'][$valueval].'</li>';
            }
        }
        $value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
    }
    elseif ($type == 'chkbxlst')
    {
        $value_arr = explode(',', $value);

        $param_list = array_keys($param['options']);
        $InfoFieldList = explode(":", $param_list[0]);

        $selectkey = "rowid";
        $keyList = 'rowid';

        if (count($InfoFieldList) >= 3) {
            $selectkey = $InfoFieldList[2];
            $keyList = $InfoFieldList[2] . ' as rowid';
        }

        $fields_label = explode('|', $InfoFieldList[1]);
        if (is_array($fields_label)) {
            $keyList .= ', ';
            $keyList .= implode(', ', $fields_label);
        }

        $sql = 'SELECT ' . $keyList;
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
        if (strpos($InfoFieldList[4], 'extra') !== false) {
            $sql .= ' as main';
        }
        // $sql.= " WHERE ".$selectkey."='".$extrafields->db->escape($value)."'";
        // $sql.= ' AND entity = '.$conf->entity;

        dol_syslog(get_class($extrafields) . ':showOutputField:$type=chkbxlst',LOG_DEBUG);
        $resql = $extrafields->db->query($sql);
        if ($resql) {
            $value = ''; // value was used, so now we reste it to use it to build final output
            $toprint=array();
            while ( $obj = $extrafields->db->fetch_object($resql) ) {

                // Several field into label (eq table:code|libelle:rowid)
                $fields_label = explode('|', $InfoFieldList[1]);
                if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
                    if (is_array($fields_label) && count($fields_label) > 1) {
                        foreach ( $fields_label as $field_toshow ) {
                            $translabel = '';
                            if (! empty($obj->$field_toshow)) {
                                $translabel = $langs->trans($obj->$field_toshow);
                            }
                            if ($translabel != $field_toshow) {
                                $toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
                            } else {
                                $toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->$field_toshow.'</li>';
                            }
                        }
                    } else {
                        $translabel = '';
                        if (! empty($obj->{$InfoFieldList[1]})) {
                            $translabel = $langs->trans($obj->{$InfoFieldList[1]});
                        }
                        if ($translabel != $obj->{$InfoFieldList[1]}) {
                            $toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
                        } else {
                            $toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->{$InfoFieldList[1]}.'</li>';
                        }
                    }
                }
            }
            $value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';

        } else {
            dol_syslog(get_class($extrafields) . '::showOutputField error ' . $extrafields->db->lasterror(), LOG_WARNING);
        }
    }
    elseif ($type == 'link')
    {
        $out='';

        // Only if something to display (perf)
        if ($value)		// If we have -1 here, pb is into sert, not into ouptu
        {
            $param_list=array_keys($param['options']);				// $param_list='ObjectName:classPath'

            $InfoFieldList = explode(":", $param_list[0]);
            $classname=$InfoFieldList[0];
            $classpath=$InfoFieldList[1];
            if (! empty($classpath))
            {
                dol_include_once($InfoFieldList[1]);
                if ($classname && class_exists($classname))
                {
                    $object = new $classname($extrafields->db);
                    $object->fetch($value);
                    $value=$object->getNomUrl(3);
                }
            }
            else
            {
                dol_syslog('Error bad setup of extrafield', LOG_WARNING);
                return 'Error bad setup of extrafield';
            }
        }
    }
    elseif ($type == 'text')
    {
        $value=dol_htmlentitiesbr($value);
    }
    elseif ($type == 'password')
    {
        $value=preg_replace('/./i','*',$value);
    }
    else
    {
        $showsize=round($size);
        if ($showsize > 48) $showsize=48;
    }

    //print $type.'-'.$size;
    $out=$value;

    return $out;
}

/**
 * Return HTML string to put an output field into a page
 *
 * @param   string	$name             		Name of attribute
 * @param   string	$content          		Value to show
 * @param   Object  $extrafields            Extrafield class
 * @return	string							Formated value
 */
function mShowOutput($name, $content, $extrafields)
{
    global $db, $user;
    $arrcontract = array('c42M_contract', 'c42R_contract', 'c42P_contract', 'c42H_contract', 'c42SI_contract');

    $ret = '';
    if (in_array($name, $arrcontract))
    {
        $contract = new Contrat($db);
        if ($contract->fetch($content) > 0)
        {
            if ($user->rights->contrat->lire && $content != 0)
            {
                $ret.= '<td>';
                $ret.= '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$content.'"><i class="fa fa-file"></i> '.$content.'</a>';
                $ret.= '</td>';
            }
            else
            {
                $ret.= '<td><i class="fa fa-file-o"></i> '.$content.'</td>';
            }
        }
        else
            $ret.= '<td>'.mShowOutputField($name, $content, $extrafields, true).'</td>'; // Using array to access attribute with a variable

    }
    else
        $ret.= '<td>'.mShowOutputField($name, $content, $extrafields, true).'</td>'; // Using array to access attribute with a variable

    return $ret;
}


/**
 *  Show tab footer of a card.
 *  Note: $object->next_prev_filter can be set to restrict select to find next or previous record by $form->showrefnav.
 *
 *  @param	Object	$object			Object to show
 *  @param	string	$paramid   		Name of parameter to use to name the id into the URL next/previous link
 *  @param	string	$morehtml  		More html content to output just before the nav bar
 *  @param	int		$shownav	  	Show Condition (navigation is shown if value is 1)
 *  @param	string	$fieldid   		Nom du champ en base a utiliser pour select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 *  @param	string	$fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
 *  @param	string	$morehtmlref  	More html to show after ref
 *  @param	string	$moreparam  	More param to add in nav link url.
 *	@param	int		$nodbprefix		Do not include DB prefix to forge table name
 *	@param	string	$morehtmlleft	More html code to show before ref
 *	@param	string	$morehtmlstatus	More html code to show under navigation arrows
 *  @param  int     $onlybanner     Put this to 1, if the card will contains only a banner (this add css 'arearefnobottom' on div)
 *	@param	string	$morehtmlright	More html code to show before navigation arrows
 *  @return	void
 */
function dol_banner_tab_card($object, $paramid, $morehtml='', $shownav=1, $fieldid='rowid', $fieldref='ref', $morehtmlref='', $moreparam='', $nodbprefix=0, $morehtmlleft='', $morehtmlstatus='', $onlybanner=0, $morehtmlright='')
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
            //elseif ($conf->browser->layout != 'phone') {    // Show no photo link
            $nophoto='/public/theme/common/nophoto.png';
            $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').' src="'.DOL_URL_ROOT.$nophoto.'"></div>';
            //}
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
                        $subdir = get_exdir($object->id, 2, 0, 0, $object, $modulepart).$objectref;		// the objectref dir is not include into get_exdir when used with level=2, so we add it here
                    }
                    else
                    {
                        $subdir = get_exdir($object->id, 0, 0, 0, $object, $modulepart);
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
                            if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS))		// If you experienc trouble with pdf thumb generation and imagick, you can disable here.
                            {
                                $ret = dol_convert_file($file, 'png', $fileimage);
                                if ($ret < 0) $error++;
                            }
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

            if (! $phototoshow)      // Show No photo link (picto of pbject)
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
                $morehtmlleft.='<!-- No photo to show -->';
                $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" border="0"'.($width?' width="'.$width.'"':'').' src="'.$nophoto.'"></div></div>';

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
        else {
            $morehtmlstatus.=$object->getLibStatut(6);
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
        if ($object->statut == 0) $morehtmlstatus.=$object->getLibStatut(5);
        else $morehtmlstatus.=$object->getLibStatut(4);
    }
    elseif ($object->element == 'facturerec')
    {
        if ($object->frequency == 0) $morehtmlstatus.=$object->getLibStatut(2);
        else $morehtmlstatus.=$object->getLibStatut(5);
    }
    elseif ($object->element == 'project_task')
    {
        $object->fk_statut = 1;
        if ($object->progress > 0) $object->fk_statut = 2;
        if ($object->progress >= 100) $object->fk_statut = 3;
        $tmptxt=$object->getLibStatut(5);
        $morehtmlstatus.=$tmptxt;		// No status on task
    }
    else { // Generic case
//		$tmptxt=$object->getLibStatut(6);
//		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3) || $conf->browser->layout=='phone') $tmptxt=$object->getLibStatut(5);
//		$morehtmlstatus.=$tmptxt;
    }

    // Add if object was dispatched "into accountancy"
    if (! empty($conf->accounting->enabled) && in_array($object->element, array('bank', 'facture', 'invoice', 'invoice_supplier', 'expensereport')))
    {
        if (method_exists($object, 'getVentilExportCompta'))
        {
            $accounted = $object->getVentilExportCompta();
            $langs->load("accountancy");
            $morehtmlstatus.='</div><div class="statusref statusrefbis">'.($accounted > 0 ? $langs->trans("Accounted") : $langs->trans("NotYetAccounted"));
        }
    }

    // Add alias for thirdparty
    if (! empty($object->name_alias)) $morehtmlref.='<div class="refidno">'.$object->name_alias.'</div>';

    // Add label
    if ($object->element == 'product' || $object->element == 'bank_account' || $object->element == 'project_task')
    {
        if (! empty($object->label)) $morehtmlref.='<div class="refidno">'.$object->label.'</div>';
    }

    if (method_exists($object, 'getBannerAddress') && $object->element != 'product' && $object->element != 'bookmark' && $object->element != 'ecm_directories' && $object->element != 'ecm_files')
    {
        $morehtmlref.='<div class="refidno">';
        $morehtmlref.=$object->getBannerAddress('refaddress',$object);
        $morehtmlref.='</div>';
    }
    if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && in_array($object->element, array('societe', 'contact', 'member', 'product')))
    {
        $morehtmlref.='<div style="clear: both;"></div><div class="refidno">';
        $morehtmlref.=$langs->trans("TechnicalID").': '.$object->id;
        $morehtmlref.='</div>';
    }

    print '<div class="'.($onlybanner?'arearefnobottom ':'arearef ').'heightref valignmiddle" width="100%">';
    print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
    print '</div>';
    print '<div class="underrefbanner clearboth"></div>';
}

/**
 * Get table name of a dictionary associated to a name of an extrafield
 *
 *  Information : We need to parse this
 *      a:1:{s:7:"options";a:1:{s:41:"infoextranet_switch:label:rowid::active=1";N;}}
 *
 *  First step  : Unserialize the array
 *      array('options' => array('infoextranet_switch:label:rowid::active=1' => 0))
 *
 *  Second step : Get the first key of unserialized array in param
 *      infoextranet_switch:label:rowid::active=1
 *
 *  Third step  : Get the first word before ':' delimiter
 *      infoextranet_switch
 *
 *
 * @param   string      $name       Name of extrafields
 * @return  string                  Name of table
 */
function getDictionnaryTableOf($name)
{
    global $db;
    $tablename = '';

    $sql = "SELECT param FROM ".MAIN_DB_PREFIX."extrafields WHERE name = '".$name."'";
    $resql = $db->query($sql);

    if ($resql)
    {
        $tab = $resql->fetch_assoc();
        $ret = unserialize($tab['param']);   // Unserialize array in 'param' column of table extrafields
        $str = array_keys($ret['options'])[0];                  // Get the first key of unserialized array in param
        $tablename = explode(":", $str, 2)[0];   // Get the first word before ':' delimiter
    }

    return $tablename;
}

/**
 * Parse string like array('options' => array('infoextranet_dnshost:label:rowid::active=1' => NULL))
 *
 * @param $name
 * @return string
 */
function parseTableName($name)
{
    $tablename = '';

    $str = array_keys($name['options'])[0];                  // Get the first key of unserialized array in param
    $tablename = explode(":", $str, 2)[0];   // Get the first word before ':' delimiter

    return MAIN_DB_PREFIX.$tablename;
}

/**
 * Create a sellist of a dictionary
 *
 * @param   Form        $form           Form object
 * @param   string      $name           Name of extrafiled
 * @param   Mixed       $value          Value of the object's extrafield
 * @param   string      $table_name     Name of table of dictionnary location
 * @return  string                      HTML formated sellist
 */
function createSellist($form, $name, $value, $table_name = '')
{
    global $db;

    $arr = array('' => '');
    // If not empty table_name, we get on db the table name
    if (empty($table_name))
        $table_name = MAIN_DB_PREFIX.getDictionnaryTableOf($name);
    else
        $table_name = parseTableName($table_name);

    $sql = "SELECT rowid, label";
    $sql .= " FROM " . $table_name;
    $sql .= $db->order("label","ASC");
    dol_syslog($sql, LOG_DEBUG);

    $resql = $db->query($sql);
    if ($resql)
    {
        foreach($resql as $key => $res)
        {
            $arr[$res['rowid']] = $res['label'];
        }
    }

    return $form->selectarray('search_'.$name, $arr, $value);
}

/**
 * Display an input according to type of the extrafield
 *
 * @param   Form        $form           Form object
 * @param   string      $name           Name of extrafield
 * @param   string      $type           Type of extrafiled
 * @param   Mixed       $value          Value of the object's extrafields
 * @return  string                      HTML formated input
 */
function printSearchField($form, $name, $type, $value)
{
    $ret = '';
    $check = '';

    switch ($type)
    {
        case 'int':
            $ret.= '<input class="flat searchstring" size="4" type="text" name="search_'.$name.'" value="'.$value.'">';
            break;
        case 'boolean':
            if (!empty($value))
                $check = " checked";

            $ret.= '<input type="checkbox" name="search_'.$name.'" '.$check.'>';
            break;
        case 'varchar':
            $ret.= '<input class="flat searchstring" size="15" type="text" name="search_'.$name.'" value="'.$value.'">';
            break;
        case 'url':
            $ret.= '<input class="flat searchstring" size="15" type="text" name="search_'.$name.'" value="'.$value.'">';
            break;
        case 'text':
            $ret.= '<input class="flat searchstring" size="20" type="text" name="search_'.$name.'" value="'.$value.'">';
            break;
        case 'sellist':
            $ret.= createSellist($form, $name, $value);
            break;
        case 'select':
            break;
    }

    return $ret;
}

/**
 *    	Return a link on thirdparty (with picto)
 *
 *		@param	int		$withpicto		          Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
 *		@param	string	$option			          Target of link ('', 'customer', 'prospect', 'supplier', 'project')
 *		@param	int		$maxlen			          Max length of name
 *      @param	int  	$notooltip		          1=Disable tooltip
 *      @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
 *		@return	string					          String with URL
 */
function mGetNomUrl($societe, $withpicto=0, $option='', $maxlen=0, $notooltip=0, $save_lastsearch_value=-1)
{
    global $conf, $langs, $hookmanager;

    if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

    $name=$societe->name?$societe->name:$societe->nom;

    if (! empty($conf->global->SOCIETE_ADD_REF_IN_LIST) && (!empty($withpicto)))
    {
        if (($societe->client) && (! empty ( $societe->code_client ))
            && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1
                || $conf->global->SOCIETE_ADD_REF_IN_LIST == 2
            )
        )
            $code = $societe->code_client . ' - ';

        if (($societe->fournisseur) && (! empty ( $societe->code_fournisseur ))
            && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1
                || $conf->global->SOCIETE_ADD_REF_IN_LIST == 3
            )
        )
            $code .= $societe->code_fournisseur . ' - ';

        if ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1)
            $name =$code.' '.$name;
        else
            $name =$code;
    }

    if (!empty($societe->name_alias)) $name .= ' ('.$societe->name_alias.')';

    $result=''; $label='';
    $linkstart=''; $linkend='';

    if (! empty($societe->logo) && class_exists('Form'))
    {
        $label.= '<div class="photointooltip">';
        $label.= Form::showphoto('societe', $societe, 0, 40, 0, 'photowithmargin', 'mini', 0);	// Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
        $label.= '</div><div style="clear: both;"></div>';
    }

    $label.= '<div class="centpercent">';

    if ($option == 'customer' || $option == 'compta' || $option == 'category' || $option == 'category_supplier')
    {
        $label.= '<u>' . $langs->trans("ShowCustomer") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$societe->id;
    }
    else if ($option == 'prospect' && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
    {
        $label.= '<u>' . $langs->trans("ShowProspect") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$societe->id;
    }
    else if ($option == 'supplier')
    {
        $label.= '<u>' . $langs->trans("ShowSupplier") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$societe->id;
    }
    else if ($option == 'agenda')
    {
        $label.= '<u>' . $langs->trans("ShowAgenda") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/societe/agenda.php?socid='.$societe->id;
    }
    else if ($option == 'project')
    {
        $label.= '<u>' . $langs->trans("ShowProject") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/societe/project.php?socid='.$societe->id;
    }
    else if ($option == 'margin')
    {
        $label.= '<u>' . $langs->trans("ShowMargin") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/margin/tabs/thirdpartyMargins.php?socid='.$societe->id.'&type=1';
    }
    else if ($option == 'contact')
    {
        $label.= '<u>' . $langs->trans("ShowContacts") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/societe/contact.php?socid='.$societe->id;
    }
    else if ($option == 'ban')
    {
        $label.= '<u>' . $langs->trans("ShowBan") . '</u>';
        $linkstart = '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$societe->id;
    }

    // By default
    // Code 42 modif
    if (empty($linkstart))
    {
        $label.= '<u>' . $langs->trans("ShowCompany") . '</u>';
        if (is_file(DOL_DOCUMENT_ROOT.'/infoextranet/index.php'))
            $url = DOL_URL_ROOT.'/infoextranet/index.php';
        else if (is_file(DOL_DOCUMENT_ROOT.'/custom/infoextranet/index.php'))
            $url = DOL_URL_ROOT.'/custom/infoextranet/index.php';
        else
            $url = DOL_URL_ROOT.'/custom/infoextranet/index.php';
        $linkstart = '<a href="'.$url.'?socid='.$societe->id;
    }

    if (! empty($societe->name))
    {
        $label.= '<br><b>' . $langs->trans('Name') . ':</b> '. $societe->name;
        if (! empty($societe->name_alias)) $label.=' ('.$societe->name_alias.')';
        $label.= '<br><b>' . $langs->trans('Email') . ':</b> '. $societe->email;
    }
    if (! empty($societe->country_code))
        $label.= '<br><b>' . $langs->trans('Country') . ':</b> '. $societe->country_code;
    if (! empty($societe->tva_intra))
        $label.= '<br><b>' . $langs->trans('VATIntra') . ':</b> '. $societe->tva_intra;
    if (! empty($societe->code_client) && $societe->client)
        $label.= '<br><b>' . $langs->trans('CustomerCode') . ':</b> '. $societe->code_client;
    if (! empty($societe->code_fournisseur) && $societe->fournisseur)
        $label.= '<br><b>' . $langs->trans('SupplierCode') . ':</b> '. $societe->code_fournisseur;
    if (! empty($conf->accounting->enabled) && $societe->client)
        $label.= '<br><b>' . $langs->trans('CustomerAccountancyCode') . ':</b> '. ($societe->code_compta ? $societe->code_compta : $societe->code_compta_client);
    if (! empty($conf->accounting->enabled) && $societe->fournisseur)
        $label.= '<br><b>' . $langs->trans('SupplierAccountancyCode') . ':</b> '. $societe->code_compta_fournisseur;

    $label.= '</div>';

    // Add type of canvas
    $linkstart.=(!empty($societe->canvas)?'&canvas='.$societe->canvas:'');
    // Add param to save lastsearch_values or not
    $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
    if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
    if ($add_save_lastsearch_values) $linkstart.='&save_lastsearch_values=1';
    $linkstart.='"';

    $linkclose='';
    if (empty($notooltip))
    {
        if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
        {
            $label=$langs->trans("ShowCompany");
            $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
        }
        $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
        $linkclose.=' class="classfortooltip refurl"';

        if (! is_object($hookmanager))
        {
            include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
            $hookmanager=new HookManager($societe->db);
        }
        $hookmanager->initHooks(array('societedao'));
        $parameters=array('id'=>$societe->id);
        $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$societe,'');    // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) $linkclose = $hookmanager->resPrint;
    }
    $linkstart.=$linkclose.'>';
    $linkend='</a>';

    global $user;
    if (! $user->rights->societe->client->voir && $user->societe_id > 0 && $societe->id != $user->societe_id)
    {
        $linkstart='';
        $linkend='';
    }

    $result.=$linkstart;
    if ($withpicto) $result.=img_object(($notooltip?'':$label), ($societe->picto?$societe->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip valigntextbottom"'), 0, 0, $notooltip?0:1);
    if ($withpicto != 2) $result.=($maxlen?dol_trunc($name,$maxlen):$name);
    $result.=$linkend;

    return $result;
}


/**
 *  Return clicable Thirdparty name to the correct tab (with picto eventually)
 *
 *	@param	int		$withpicto					Include picto into link
 *  @param  string	$mode           			''=Link to card, 'transactions'=Link to transactions card
 *  @param  string  $option         			''=Show ref, 'reflabel'=Show ref+label
 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
 *  @param	int  	$notooltip		 			1=Disable tooltip
 *  @param  int     $socid                      Id of Thirdparty
 *  @param  string  $name                       Name of Thirdparty
 *  @param  boolean $picto                      With picto or not
 *  @param  string  $where                      device/ app : redirect to the correct Thirdparty tab Applications or Devices
 *	@return	string								Chaine avec URL
 */
function goToThirdparty($withpicto=0, $option='', $notooltip=0, $morecss='', $save_lastsearch_value=-1, $socid, $name, $picto, $where)
{
    global $db, $conf, $langs;
    global $dolibarr_main_authentication, $dolibarr_main_demo;
    global $menumanager;

    if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

    $result = '';
    $companylink = '';

    $label = '<u>' . $langs->trans("Thirdparty") . '</u>';
    $label.= '<br>';
    $label.= '<b>' . $langs->trans('name') . ':</b> ' . $name;

    //Redirect to the correct tab
    if ($where == 'device') {
        $url = dol_buildpath('/infoextranet/device.php', 1) . '?socid=' . $socid;
    } elseif ($where == 'app') {
        $url = dol_buildpath('/infoextranet/application.php', 1) . '?socid=' . $socid;
    } else {
        $url = dol_buildpath('/infoextranet/index.php', 1) . '?socid=' . $socid;
    }

    if ($option != 'nolink')
    {
        // Add param to save lastsearch_values or not
        $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
    }

    $linkclose='';
    if (empty($notooltip))
    {
        if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
        {
            $label=$langs->trans("ShowApplication");
            $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
        }
        $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
        $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
    }
    else $linkclose = ($morecss?' class="'.$morecss.'"':'');

    $linkstart = '<a href="'.$url.'"';
    $linkstart.=$linkclose.'>';
    $linkend='</a>';

    $result .= $linkstart;
    if ($withpicto) $result.=img_object(($notooltip?'':$label), ($picto?$picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
    if ($withpicto != 2) $result.= $name;
    $result .= $linkend;
    //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

    return $result;
}