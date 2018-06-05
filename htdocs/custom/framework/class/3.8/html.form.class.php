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



namespace CORE\FRAMEWORK;

dol_include_once('/core/class/html.form.class.php');


use \CORE\FRAMEWORK\Form as Form ;


class Form
	extends \Form{


	public $OSE_loaded_version = DOL_VERSION;

	public $OSE_loaded_path = '3.8';


    /**
     *    	Return HTML code to output a photo
     *
     *    	@param	string		$modulepart			Key to define module concerned ('societe', 'userphoto', 'memberphoto')
     *     	@param  object		$object				Object containing data to retrieve file name
     * 		@param	int			$width				Width of photo
     * 		@param	int			$height				Height of photo (auto if 0)
     * 		@param	int			$caneditfield		Add edit fields
     * 		@param	string		$cssclass			CSS name to use on img for photo
     * 		@param	string		$imagesize		    'mini', 'small' or '' (original)
     *      @param  int         $addlinktofullsize  Add link to fullsize image
     *      @param  int         $cache              1=Accept to use image in cache
     * 	  	@return string    						HTML code to output photo
     */
    static function showphoto($modulepart, $object, $width=100, $height=0, $caneditfield=0, $cssclass='photowithmargin', $imagesize='', $addlinktofullsize=1, $cache=0)
    {
        global $conf,$langs;

        $entity = (! empty($object->entity) ? $object->entity : $conf->entity);
        $id = (! empty($object->id) ? $object->id : $object->rowid);

        $ret='';$dir='';$file='';$originalfile='';$altfile='';$email='';
        if ($modulepart=='societe')
        {
            $dir=$conf->societe->multidir_output[$entity];
            if (! empty($object->logo))
            {
                if ((string) $imagesize == 'mini') $file=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.getImageFileNameForSize($object->logo, '_mini');             // getImageFileNameForSize include the thumbs
                else if ((string) $imagesize == 'small') $file=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.getImageFileNameForSize($object->logo, '_small');
                else $file=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.$object->logo;
                $originalfile=get_exdir(0, 0, 0, 0, $object, 'thirdparty').'/logos/'.$object->logo;
            }
            $email=$object->email;
        }
        else if ($modulepart=='contact')
        {
            $dir=$conf->societe->multidir_output[$entity].'/contact';
            if (! empty($object->photo))
            {
                if ((string) $imagesize == 'mini') $file=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.getImageFileNameForSize($object->photo, '_mini');
                else if ((string) $imagesize == 'small') $file=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.getImageFileNameForSize($object->photo, '_small');
                else $file=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.$object->photo;
                $originalfile=get_exdir(0, 0, 0, 0, $object, 'contact').'/photos/'.$object->photo;
            }
            $email=$object->email;
        }
        else if ($modulepart=='userphoto')
        {
            $dir=$conf->user->dir_output;
            if (! empty($object->photo))
            {
                if ((string) $imagesize == 'mini') $file=get_exdir($id, 2, 0, 0, $object, 'user').getImageFileNameForSize($object->photo, '_mini');
                else if ((string) $imagesize == 'small') $file=get_exdir($id, 2, 0, 0, $object, 'user').getImageFileNameForSize($object->photo, '_small');
                else $file=get_exdir($id, 2, 0, 0, $object, 'user').$object->photo;
                $originalfile=get_exdir($id, 2, 0, 0, $object, 'user').$object->photo;
            }
            if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }
        else if ($modulepart=='memberphoto')
        {
            $dir=$conf->adherent->dir_output;
            if (! empty($object->photo))
            {
                if ((string) $imagesize == 'mini') $file=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.getImageFileNameForSize($object->photo, '_mini');
                else if ((string) $imagesize == 'small') $file=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.getImageFileNameForSize($object->photo, '_small');
                else $file=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.$object->photo;
                $originalfile=get_exdir(0, 0, 0, 0, $object, 'member').'photos/'.$object->photo;
            }
            if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }
        else
        {
            // Generic case to show photos
		$dir=$conf->$modulepart->dir_output;
		if (! empty($object->photo))
		{
                if ((string) $imagesize == 'mini') $file=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.getImageFileNameForSize($object->photo, '_mini');
                else if ((string) $imagesize == 'small') $file=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.getImageFileNameForSize($object->photo, '_small');
		    else $file=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.$object->photo;
		    $originalfile=get_exdir($id, 2, 0, 0, $object, $modulepart).'photos/'.$object->photo;
		}
		if (! empty($conf->global->MAIN_OLD_IMAGE_LINKS)) $altfile=$object->id.".jpg";	// For backward compatibility
		$email=$object->email;
        }

        if ($dir)
        {
            if ($file && file_exists($dir."/".$file))
            {
                if ($addlinktofullsize)
                {
                    $urladvanced=getAdvancedPreviewUrl($modulepart, $originalfile, 0, '&entity='.$entity);
                    if ($urladvanced) $ret.='<a href="'.$urladvanced.'">';
                    else $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($originalfile).'&cache='.$cache.'">';
                }
                $ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="Photo" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($file).'&cache='.$cache.'">';
                if ($addlinktofullsize) $ret.='</a>';
            }
            else if ($altfile && file_exists($dir."/".$altfile))
            {
                if ($addlinktofullsize)
                {
                    $urladvanced=getAdvancedPreviewUrl($modulepart, $originalfile, 0, '&entity='.$entity);
                    if ($urladvanced) $ret.='<a href="'.$urladvanced.'">';
                    else $ret.='<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($originalfile).'&cache='.$cache.'">';
                }
                $ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="Photo alt" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" class="'.$cssclass.'" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$entity.'&file='.urlencode($altfile).'&cache='.$cache.'">';
                if ($addlinktofullsize) $ret.='</a>';
            }
            else
			{
                $nophoto='/public/theme/common/nophoto.png';
				if (in_array($modulepart,array('userphoto','contact')))	// For module that are "physical" users
				{
					$nophoto='/public/theme/common/user_anonymous.png';
					if ($object->gender == 'man') $nophoto='/public/theme/common/user_man.png';
					if ($object->gender == 'woman') $nophoto='/public/theme/common/user_woman.png';
				}

				if (! empty($conf->gravatar->enabled) && $email)
                {
	                /**
	                 * @see https://gravatar.com/site/implement/images/php/
	                 */
                    global $dolibarr_main_url_root;
                    $ret.='<!-- Put link to gravatar -->';
                    //$defaultimg=urlencode(dol_buildpath($nophoto,3));
                    $defaultimg='mm';
                    $ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="Gravatar avatar" title="'.$email.' Gravatar avatar" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="https://www.gravatar.com/avatar/'.dol_hash(strtolower(trim($email)),3).'?s='.$width.'&d='.$defaultimg.'">';	// gravatar need md5 hash
                }
                else
				{
                    $ret.='<img class="photo'.$modulepart.($cssclass?' '.$cssclass:'').'" alt="No photo" '.($width?' width="'.$width.'"':'').($height?' height="'.$height.'"':'').' src="'.DOL_URL_ROOT.$nophoto.'">';
                }
            }

            if ($caneditfield)
            {
                if ($object->photo) $ret.="<br>\n";
                $ret.='<table class="nobordernopadding centpercent">';
                if ($object->photo) $ret.='<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
                $ret.='<tr><td class="tdoverflow"><input type="file" class="flat maxwidth200onsmartphone" name="photo" id="photoinput"></td></tr>';
                $ret.='</table>';
            }

        }
        else dol_print_error('','Call of showphoto with wrong parameters modulepart='.$modulepart);

        return $ret;
    }

    /**
     *	Return HTML to show the search and clear seach button
     *
     *  @param  string  $cssclass                  CSS class
     *  @param  int     $calljsfunction            0=default. 1=call function initCheckForSelect() after changing status of checkboxes
     *  @return	string
     */
    function showCheckAddButtons($cssclass='checkforaction', $calljsfunction=0)
    {
        global $conf, $langs;

        $out='';
        if (! empty($conf->use_javascript_ajax)) $out.='<div class="inline-block checkallactions"><input type="checkbox" id="checkallactions" name="checkallactions" class="checkallactions"></div>';
        $out.='<script type="text/javascript">
            $(document).ready(function() {
		$("#checkallactions").click(function() {
                    if($(this).is(\':checked\')){
                        console.log("We check all");
				$(".'.$cssclass.'").prop(\'checked\', true);
                    }
                    else
                    {
                        console.log("We uncheck all");
				$(".'.$cssclass.'").prop(\'checked\', false);
                    }'."\n";
        if ($calljsfunction) $out.='if (typeof initCheckForSelect == \'function\') { initCheckForSelect(); } else { console.log("No function initCheckForSelect found. Call won\'t be done."); }';
        $out.='         });
                });
            </script>';

        return $out;
    }

    /**
     *	Show a multiselect form from an array.
     *
     *	@param	string	$htmlname		Name of select
     *	@param	array	$array			Array with array of fields we could show. This array may be modified according to setup of user.
     *  @param  string  $varpage        Id of context for page. Can be set with $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
     *	@return	string					HTML multiselect string
     *  @see selectarray
     */
    static function multiSelectArrayWithCheckbox($htmlname, &$array, $varpage)
    {
        global $conf,$langs,$user;

        if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) return '';

        $tmpvar="MAIN_SELECTEDFIELDS_".$varpage;
        if (! empty($user->conf->$tmpvar))
        {
            $tmparray=explode(',', $user->conf->$tmpvar);
            foreach($array as $key => $val)
            {
                //var_dump($key);
                //var_dump($tmparray);
                if (in_array($key, $tmparray)) $array[$key]['checked']=1;
                else $array[$key]['checked']=0;
            }
        }
        //var_dump($array);

        $lis='';
        $listcheckedstring='';

        foreach($array as $key => $val)
        {
           /* var_dump($val);
            var_dump(array_key_exists('enabled', $val));
            var_dump(!$val['enabled']);*/
           if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! $val['enabled'])
           {
               unset($array[$key]);     // We don't want this field
               continue;
           }
           if ($val['label'])
	       {
	           $lis.='<li><input type="checkbox" value="'.$key.'"'.(empty($val['checked'])?'':' checked="checked"').'/>'.dol_escape_htmltag($langs->trans($val['label'])).'</li>';
	           $listcheckedstring.=(empty($val['checked'])?'':$key.',');
	       }
        }

        $out ='<!-- Component multiSelectArrayWithCheckbox '.$htmlname.' -->

        <dl class="dropdown">
            <dt>
            <a href="#">
              '.img_picto('','list').'
            </a>
            <input type="hidden" class="'.$htmlname.'" name="'.$htmlname.'" value="'.$listcheckedstring.'">
            </dt>
            <dd class="dropowndd">
                <div class="multiselectcheckbox'.$htmlname.'">
                    <ul class="ul'.$htmlname.'">
                    '.$lis.'
                    </ul>
                </div>
            </dd>
        </dl>

        <script type="text/javascript">
          jQuery(document).ready(function () {
              $(\'.multiselectcheckbox'.$htmlname.' input[type="checkbox"]\').on(\'click\', function () {
                  console.log("A new field was added/removed")
                  $("input:hidden[name=formfilteraction]").val(\'listafterchangingselectedfields\')
                  var title = $(this).val() + ",";
                  if ($(this).is(\':checked\')) {
                      $(\'.'.$htmlname.'\').val(title + $(\'.'.$htmlname.'\').val());
                  }
                  else {
                      $(\'.'.$htmlname.'\').val( $(\'.'.$htmlname.'\').val().replace(title, \'\') )
                  }
                  // Now, we submit page
                  $(this).parents(\'form:first\').submit();
              });
           });
        </script>

        ';
        return $out;
    }



}
