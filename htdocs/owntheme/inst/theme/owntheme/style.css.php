<?php
/* Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2006       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2011       Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2012       Juanjo Menent           <jmenent@2byte.es>
 *
 * Copyright (C) 2013-2014  Nicolas Rivera          <theme@creajutsu.com>
 * Copyright (C) 2015       Serge Azout             <contact@msmobile.fr>
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
 *      \file       htdocs/theme/owntheme/style.css.php
 *      \brief      File for CSS style sheet OwnTheme
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');  // Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');    // Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');  // Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

$res=@include("../../main.inc.php");             // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");      // For "custom" directory

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
if (empty($user->id) && ! empty($_SESSION['dol_login'])) $user->fetch('',$_SESSION['dol_login']);


// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

// On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) { ob_start("ob_gzhandler"); }

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));   // If language was forced on URL
if (GETPOST('theme')) $conf->theme=GETPOST('theme');  // If theme was forced on URL
$langs->load("main",0,1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');

$path='';       // This value may be used in future for external module to overwrite theme
$theme='owntheme';   // Value of theme
if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path='/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme=$conf->global->MAIN_OVERWRITE_THEME_RES; }



$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_optimize_smallscreen=$conf->dol_optimize_smallscreen;
$dol_no_mouse_hover=$conf->dol_no_mouse_hover;
$dol_use_jmobile=$conf->dol_use_jmobile;


// Define reference colors
// Example: Light grey: $colred=235;$colgreen=235;$colblue=235;
// Example: Pink:       $colred=230;$colgreen=210;$colblue=230;
// Example: Green:      $colred=210;$colgreen=230;$colblue=210;
// Example: Ocean:      $colred=220;$colgreen=220;$colblue=240;
//$conf->global->THEME_ELDY_ENABLE_PERSONALIZED=0;
//$user->conf->THEME_ELDY_ENABLE_PERSONALIZED=0;
//var_dump($user->conf->THEME_ELDY_RGB);
$colred  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_RGB)?235:hexdec(substr($conf->global->THEME_ELDY_RGB,0,2))):(empty($user->conf->THEME_ELDY_RGB)?235:hexdec(substr($user->conf->THEME_ELDY_RGB,0,2)));
$colgreen=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_RGB)?235:hexdec(substr($conf->global->THEME_ELDY_RGB,2,2))):(empty($user->conf->THEME_ELDY_RGB)?235:hexdec(substr($user->conf->THEME_ELDY_RGB,2,2)));
$colblue =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_RGB)?235:hexdec(substr($conf->global->THEME_ELDY_RGB,4,2))):(empty($user->conf->THEME_ELDY_RGB)?235:hexdec(substr($user->conf->THEME_ELDY_RGB,4,2)));

// Colors
$isred=max(0,(2*$colred-$colgreen-$colblue)/2);        // 0 - 255
$isgreen=max(0,(2*$colgreen-$colred-$colblue)/2);      // 0 - 255
$isblue=max(0,(2*$colblue-$colred-$colgreen)/2);       // 0 - 255
$colorbackhmenu1=($colred-3).','.($colgreen-3).','.($colblue-3);         // topmenu
$colorbackhmenu2=($colred+5).','.($colgreen+5).','.($colblue+5);
$colorbackvmenu1=($colred+15).','.($colgreen+16).','.($colblue+17);      // vmenu
$colorbackvmenu1b=($colred+5).','.($colgreen+6).','.($colblue+7);        // vmenu (not menu)
$colorbackvmenu2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktitle1=($colred-5).','.($colgreen-5).','.($colblue-5);    // title of array
$colorbacktitle2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktabcard1=($colred+15).','.($colgreen+16).','.($colblue+17);  // card
$colorbacktabcard2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktabactive=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacklineimpair1=(244+round($isred/3)).','.(244+round($isgreen/3)).','.(244+round($isblue/3));    // line impair
$colorbacklineimpair2=(250+round($isred/3)).','.(250+round($isgreen/3)).','.(250+round($isblue/3));    // line impair
$colorbacklineimpairhover=(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9));    // line impair
$colorbacklinepair1='255,255,255';    // line pair
$colorbacklinepair2='255,255,255';    // line pair
$colorbacklinepairhover=(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9));
//$colorbackbody='#ffffff url('.$img_head.') 0 0 no-repeat;';
$colorbackbody='#fcfcfc';
$colortext='40,40,40';
$colortexttopmenu='#ffffff';
$fontsize=empty($conf->dol_optimize_smallscreen)?'12':'14';
$fontsizesmaller=empty($conf->dol_optimize_smallscreen)?'11':'14';

// Eldy colors
if (empty($conf->global->THEME_ELDY_ENABLE_PERSONALIZED)) {
    $conf->global->THEME_ELDY_TOPMENU_BACK1='140,160,185';    // topmenu
    $conf->global->THEME_ELDY_TOPMENU_BACK2='236,236,236';
    $conf->global->THEME_ELDY_VERMENU_BACK1='255,255,255';    // vmenu
    $conf->global->THEME_ELDY_VERMENU_BACK1b='230,232,232';   // vmenu (not menu)
    $conf->global->THEME_ELDY_VERMENU_BACK2='240,240,240';
    $conf->global->THEME_ELDY_BACKTITLE1='140,160,185';       // title of arrays
    $conf->global->THEME_ELDY_BACKTITLE2='230,230,230';
    $conf->global->THEME_ELDY_BACKTABCARD2='210,210,210';     // card
    $conf->global->THEME_ELDY_BACKTABCARD1='234,234,234';
    $conf->global->THEME_ELDY_BACKTABACTIVE='234,234,234';
    //$conf->global->THEME_ELDY_BACKBODY='#ffffff url('.$img_head.') 0 0 no-repeat;';
    $conf->global->THEME_ELDY_BACKBODY='#fcfcfc;';
    $conf->global->THEME_ELDY_LINEIMPAIR1='242,242,242';
    $conf->global->THEME_ELDY_LINEIMPAIR2='248,248,248';
    $conf->global->THEME_ELDY_LINEIMPAIRHOVER='238,246,252';
    $conf->global->THEME_ELDY_LINEPAIR1='255,255,255';
    $conf->global->THEME_ELDY_LINEPAIR2='255,255,255';
    $conf->global->THEME_ELDY_LINEPAIRHOVER='238,246,252';
    $conf->global->THEME_ELDY_TEXT='50,50,130';
    if ($dol_use_jmobile) {
        $conf->global->THEME_ELDY_BACKTABCARD1='245,245,245';    // topmenu
        $conf->global->THEME_ELDY_BACKTABCARD2='245,245,245';
        $conf->global->THEME_ELDY_BACKTABACTIVE='245,245,245';
    }
}

$colorbackhmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$conf->global->THEME_ELDY_TOPMENU_BACK1)   :(empty($user->conf->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorbackhmenu2     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK2)?$colorbackhmenu2:$conf->global->THEME_ELDY_TOPMENU_BACK2)   :(empty($user->conf->THEME_ELDY_TOPMENU_BACK2)?$colorbackhmenu2:$user->conf->THEME_ELDY_TOPMENU_BACK2);
$colorbackvmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$conf->global->THEME_ELDY_VERMENU_BACK1)   :(empty($user->conf->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$user->conf->THEME_ELDY_VERMENU_BACK1);
$colorbackvmenu1b    =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1b)?$colorbackvmenu1:$conf->global->THEME_ELDY_VERMENU_BACK1b) :(empty($user->conf->THEME_ELDY_VERMENU_BACK1b)?$colorbackvmenu1b:$user->conf->THEME_ELDY_VERMENU_BACK1b);
$colorbackvmenu2     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK2)?$colorbackvmenu2:$conf->global->THEME_ELDY_VERMENU_BACK2)   :(empty($user->conf->THEME_ELDY_VERMENU_BACK2)?$colorbackvmenu2:$user->conf->THEME_ELDY_VERMENU_BACK2);
$colorbacktitle1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTITLE1)   ?$colorbacktitle1:$conf->global->THEME_ELDY_BACKTITLE1)      :(empty($user->conf->THEME_ELDY_BACKTITLE1)?$colorbacktitle1:$user->conf->THEME_ELDY_BACKTITLE1);
$colorbacktitle2     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTITLE2)   ?$colorbacktitle2:$conf->global->THEME_ELDY_BACKTITLE2)      :(empty($user->conf->THEME_ELDY_BACKTITLE2)?$colorbacktitle2:$user->conf->THEME_ELDY_BACKTITLE2);
$colorbacktabcard1   =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABCARD1) ?$colorbacktabcard1:$conf->global->THEME_ELDY_BACKTABCARD1)  :(empty($user->conf->THEME_ELDY_BACKTABCARD1)?$colorbacktabcard1:$user->conf->THEME_ELDY_BACKTABCARD1);
$colorbacktabcard2   =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABCARD2) ?$colorbacktabcard2:$conf->global->THEME_ELDY_BACKTABCARD2)  :(empty($user->conf->THEME_ELDY_BACKTABCARD2)?$colorbacktabcard2:$user->conf->THEME_ELDY_BACKTABCARD2);
$colorbacktabactive  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$conf->global->THEME_ELDY_BACKTABACTIVE):(empty($user->conf->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$user->conf->THEME_ELDY_BACKTABACTIVE);
$colorbacklineimpair1=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR1)  ?$colorbacklineimpair1:$conf->global->THEME_ELDY_LINEIMPAIR1):(empty($user->conf->THEME_ELDY_LINEIMPAIR1)?$colorbacklineimpair1:$user->conf->THEME_ELDY_LINEIMPAIR1);
$colorbacklineimpair2=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR2)  ?$colorbacklineimpair2:$conf->global->THEME_ELDY_LINEIMPAIR2):(empty($user->conf->THEME_ELDY_LINEIMPAIR2)?$colorbacklineimpair2:$user->conf->THEME_ELDY_LINEIMPAIR2);
$colorbacklineimpairhover=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIRHOVER)  ?$colorbacklineimpairhover:$conf->global->THEME_ELDY_LINEIMPAIRHOVER):(empty($user->conf->THEME_ELDY_LINEIMPAIRHOVER)?$colorbacklineimpairhover:$user->conf->THEME_ELDY_LINEIMPAIRHOVER);
$colorbacklinepair1  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR1)    ?$colorbacklinepair1:$conf->global->THEME_ELDY_LINEPAIR1)    :(empty($user->conf->THEME_ELDY_LINEPAIR1)?$colorbacklinepair1:$user->conf->THEME_ELDY_LINEPAIR1);
$colorbacklinepair2  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR2)    ?$colorbacklinepair2:$conf->global->THEME_ELDY_LINEPAIR2)    :(empty($user->conf->THEME_ELDY_LINEPAIR2)?$colorbacklinepair2:$user->conf->THEME_ELDY_LINEPAIR2);
$colorbacklinepairhover  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIRHOVER)    ?$colorbacklinepairhover:$conf->global->THEME_ELDY_LINEPAIRHOVER)    :(empty($user->conf->THEME_ELDY_LINEPAIRHOVER)?$colorbacklinepairhover:$user->conf->THEME_ELDY_LINEPAIRHOVER);
$colorbackbody       =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKBODY)     ?$colorbackbody:$conf->global->THEME_ELDY_BACKBODY)          :(empty($user->conf->THEME_ELDY_BACKBODY)?$colorbackbody:$user->conf->THEME_ELDY_BACKBODY);
$colortext           =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TEXT)         ?$colortext:$conf->global->THEME_ELDY_TEXT)                  :(empty($user->conf->THEME_ELDY_TEXT)?$colortext:$user->conf->THEME_ELDY_TEXT);
$fontsize            =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE1)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE1)             :(empty($user->conf->THEME_ELDY_FONT_SIZE1)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE1);
$fontsizesmaller     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE2)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE2)             :(empty($user->conf->THEME_ELDY_FONT_SIZE2)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE2);
// No hover by default, we keep only if we set var THEME_ELDY_USE_HOVER
if ((! empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) && empty($user->conf->THEME_ELDY_USE_HOVER))
    || (empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) && empty($conf->global->THEME_ELDY_USE_HOVER)))
{
    $colorbacklineimpairhover='';
    $colorbacklinepairhover='';
}

// Set text color to black or white
$tmppart=explode(',',$colorbackhmenu1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) $colortextbackhmenu='FFF';
else $colortextbackhmenu='444';
$tmppart=explode(',',$colorbackvmenu1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) { $colortextbackvmenu='FFF'; }
else { $colortextbackvmenu='444'; }
$tmppart=explode(',',$colorbacktitle1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) { $colortexttitle='FFF'; $colorshadowtitle='000'; }
else { $colortexttitle='444'; $colorshadowtitle='FFF'; }
$tmppart=explode(',',$colorbacktabcard1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 340) { $colortextbacktab='FFF'; }
else { $colortextbacktab='444'; }


$usecss3=true;
if ($conf->browser->name == 'ie' && round($conf->browser->version,2) < 10) $usecss3=false;
elseif ($conf->browser->name == 'iceweasel') $usecss3=false;
elseif ($conf->browser->name == 'epiphany')  $usecss3=false;

foreach($conf->modules as $val) {
    $mainmenuused.=','.(isset($moduletomainmenu[$val])?$moduletomainmenu[$val]:$val);
}

$mainmenuusedarray=array_unique(explode(',',$mainmenuused));

$generic=1;
$divalreadydefined=array('home','companies','products','commercial','accountancy','project','tools','members','shop','agenda','holiday','bookmark','cashdesk','ecm','geoipmaxmind','gravatar','clicktodial','paypal','webservices','owntheme');

foreach($mainmenuusedarray as $val) {
    print "/* ------" . $val ."-------- */\n";
    if (empty($val) || in_array($val,$divalreadydefined)) continue;

    // Search img file in module dir
    $found=0; $url='';
    foreach($conf->file->dol_document_root as $dirroot) {
        if (file_exists($dirroot."/".$val."/img/".$val.".png")) {
            $url=dol_buildpath('/'.$val.'/img/'.$val.'.png', 1);
            $found=1;
            break;
        }
    }

    if (!$found) {
        $url=dol_buildpath($path.'/theme/'.$theme.'/img/menus/generic'.$generic.".png",1);
        $found=1;
        if ($generic < 4) $generic++;
        print "/* A mainmenu entry but img file ".$val.".png not found (check /".$val."/img/".$val.".png), so we use a generic one */\n";
    }

    if ($found) {
        print "div.mainmenu.".$val." {\n";
        print " background-image: url(".$url.");\n";
        print " height:1em;\n";
        print "}\n";
    }
}

function col_brightness( $hex, $steps ) {

        $steps = max( -255, min( 255, $steps ) );

        $hex = str_replace( '#', '', $hex );
        if ( strlen( $hex ) == 3 ) {
            $hex = str_repeat( substr( $hex, 0, 1 ), 2 ) . str_repeat( substr( $hex, 1, 1 ), 2 ) . str_repeat( substr( $hex, 2, 1), 2 );
        }

        $color_parts = str_split( $hex, 2 );
        $return = '#';

        foreach ( $color_parts as $color ) {
            $color = hexdec( $color );
            $color = max( 0, min( 255, $color + $steps ) );
            $return .= str_pad( dechex( $color ), 2, '0', STR_PAD_LEFT );
        }


        return sanitize_hex_color( $return );
}

function sanitize_hex_color( $color ) {

        if ( '' === $color ) {
            return '';
        }

        // make sure the color starts with a hash
        $color = '#' . ltrim( $color, '#' );

        // 3 or 6 hex digits, or the empty string.
        if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
            return $color;
        }

        return null;

}

$col2 = $conf->global->OWNTHEME_COL2;
$col2_lighter = col_brightness($col2,20);
$btn_txt = "#FFFFFF";

?>

body { background-color: <?=$conf->global->OWNTHEME_COL_BODY_BCKGRD?>; }
body .company_logo{ background-color: <?=$conf->global->OWNTHEME_COL_LOGO_BCKGRD?>; }
body div.vmenu .blockvmenulogo{ background-color: <?=$conf->global->OWNTHEME_COL_LOGO_BCKGRD?>; }
#id-right { background-color: <?=$conf->global->OWNTHEME_COL_BODY_BCKGRD?>; }

/* ------------------------------------------------------ */
#id-container { background-color: <?=$conf->global->OWNTHEME_COL_LOGO_BCKGRD?>; }
div#id-left {
    background-color: <?=$conf->global->OWNTHEME_COL_HEADER_BCKGRD?>;
}
.dropdown dd ul {
    background-color: <?=$conf->global->OWNTHEME_COL_HEADER_BCKGRD?>;
    border: 1px solid #888;
    display:none;
    right:0px;
    padding: 2px 15px 2px 5px;
    position:absolute;
    top:2px;
    list-style:none;
    max-height: 264px;
    overflow: auto;
}
#id-left #inner-content-div .select2-container--default .select2-selection--single{
    border: 1px solid <?=$conf->global->OWNTHEME_COL_HEADER_BCKGRD?>;
    background-color: #fff;
}
body #tmenu_tooltip .tmenudiv li.tmenusel{
    background-color: <?=$conf->global->OWNTHEME_COL_HEADER_BCKGRD?>;
}
.ui-state-active, .ui-widget-content .ui-state-active {
    background: <?=$conf->global->OWNTHEME_COL_HEADER_BCKGRD?>;
}
body.body.bodylogin{ background-color: <?=$conf->global->OWNTHEME_COL_HEADER_BCKGRD?> !important; }
#ob_loadding{background:#474c80;}
/* ------------------------------------------------------ */
#id-left a:active, #id-left a:link, #id-left a:visited{
    color: <?=$conf->global->OWNTHEME_COL_TXT_MENU?>;
}
#tmenu_tooltip {
    background-color: <?=$conf->global->OWNTHEME_COL_HEADER_BCKGRD?>;
    font-size: <?=$conf->global->OWNTHEME_D_HEADER_FONT_SIZE?>;
}
#id-left { font-size: <?=$conf->global->OWNTHEME_D_VMENU_FONT_SIZE?>; }
body th.liste_titre, body tr.liste_titre, body .liste_titre_filter, body tr.liste_titre tr, body tr.box_titre, body div.liste_titre, body tr.box_titre * {
    background-color: <?=$conf->global->OWNTHEME_COL1?>;
    background: <?=$conf->global->OWNTHEME_COL1?> !important;
    color: #ffffff;
}
.fiche>form>table.notopnoleftnoright{
    background: <?=$conf->global->OWNTHEME_COL_BODY_BCKGRD?>;
}
#id-left div.vmenu a[data-actif="nc_actif_element"]:hover{
    background-color: <?=$conf->global->OWNTHEME_COL1?> !important;
}
div.tabBar table.noborder tr.liste_titre{
    border-bottom:1px solid <?=$conf->global->OWNTHEME_COL1?> !important;
}
a[data-actif="nc_actif_element"] {
    background: <?=$conf->global->OWNTHEME_COL1?>;
    color:#fff !important;
}
.tabBar table tr:nth-child(odd) {
    background-color: <?=$conf->global->OWNTHEME_COL1?>21;
}
.tabBar table tr:nth-child(odd) tr {
    background-color: <?=$conf->global->OWNTHEME_COL1?>21;
}
table.boxtable tr:nth-child(odd):not(.box_titre) {
    background-color: <?=$conf->global->OWNTHEME_COL1?>21;
}
body table.liste tr:nth-child(odd) {
    background-color: <?=$conf->global->OWNTHEME_COL1?>21;
}
body table tr.pair,table.noborder tr.oddeven:nth-child(odd){
    background-color: <?=$conf->global->OWNTHEME_COL1?>21;
}
.centpercent {
    width: 100%;
}
.quatrevingtpercent, .inputsearch {
    width: 80%;
}
.soixantepercent {
    width: 60%;
}
textarea.centpercent {
    width: 96%;
}

#upbuttons-nav ul li{
    padding: .5em 1em;
    white-space: nowrap;
}



/* For table into table into card */
div.ficheaddleft tr.liste_titre:first-child td table.nobordernopadding td {
    padding: 0 0 0 0;
}
div.nopadding {
    padding: 0 !important;
}

.containercenter {
    display : table;
    margin : 0px auto;
}

#pictotitle {
    margin-right: 8px;
    margin-bottom: 4px;
}
.pictoobjectwidth {
    width: 14px;
}
.pictosubstatus {
    padding-left: 2px;
    padding-right: 2px;
}
.pictostatus {
    width: 15px;
    vertical-align: middle;
    margin-top: -3px
}
.pictowarning, .pictopreview {
    padding-left: 3px;
}
.pictoedit, .pictowarning, .pictodelete {
    vertical-align: text-bottom;
}
.fiche img.pictoedit {
    opacity: 0.7;
}
.colorthumb {
    padding-left: 1px !important;
    padding-right: 1px;
    padding-top: 1px;
    padding-bottom: 1px;
    width: 44px;
    text-align:center;
}
div.attacharea {
    padding-top: 18px;
    padding-bottom: 10px;
}
div.attachareaformuserfileecm {
    padding-top: 0;
    padding-bottom: 0;
}

div.arearef {
    padding-top: 2px;
    margin-bottom: 10px;
    padding-bottom: 7px;
}
div.arearefnobottom {
    padding-top: 2px;
    padding-bottom: 4px;
}
div.heightref {
    min-height: 80px;
}
div.divphotoref {
    padding-right: 20px;
}
div.paginationref {
    padding-bottom: 10px;
}
div.statusref {
    float: right;
    padding-left: 12px;
    margin-top: 8px;
    margin-bottom: 10px;
    clear: both;
}
div.statusref img {
    padding-left: 8px;
    padding-right: 9px;
    vertical-align: text-bottom;
}
div.statusrefbis {
    padding-left: 8px;
    padding-right: 9px;
    vertical-align: text-bottom;
}
img.photoref, div.photoref {
    border: 1px solid #CCC;
    -webkit-box-shadow: 2px 2px 4px #ccc;
    box-shadow: 2px 2px 4px #ccc;
    padding: 4px;
    height: 80px;
    width: 80px;
    object-fit: contain;
}
img.fitcontain {
    object-fit: contain;
}
div.photoref {
    display:table-cell;
    vertical-align:middle;
    text-align:center;
}
img.photorefnoborder {
    padding: 2px;
    height: 48px;
    width: 48px;
    object-fit: contain;
    border: 1px solid #AAA;
    border-radius: 100px;
}
.underrefbanner {
}
.underbanner {
    border-bottom: 2px solid rgb(120,120,120);
}
.tdhrthin {
    margin: 0;
    padding-bottom: 0 !important;
}
/* END For table into table into card */

.span-icon-multicompany {
    width: auto !important;
}
div#s2id_receivercc,div#s2id_receiver{
    min-width: 400px;
    width: auto;
}
div.tabsAction.upbuttonsdiv {
    position: initial !important;
    bottom: initial !important;
    right: initial !important;
    background-color: initial !important;
    padding: .5em 0 !important;
    border: initial !important;
    border-radius: initial !important;
    margin: initial !important;
    opacity: initial !important;
    display: block !important;
}
div.tabsAction.upbuttonsdiv .divButAction a{
    padding: .5em 1em !important;
}









#id-container > .side-nav{
    width: 210px;
    float: left;
}
div[data-actif="nc_actif_element"] {
    background: rgba(0, 0, 0, 0.28);
}
body.body.bodylogin .login_table { background-color: #fff; }
body.body.bodylogin .login_table_title { color: #fff; }
#id-container > #id-right{
    height: 90vh;
    overflow: auto;
    width: calc(100vw - 210px);
    display: block;
}
body {
    overflow: hidden !important;
}
body.body.bodylogin {
    overflow: initial !important;
}
#containerlayout .ecm-layout-pane {
    background: #FFF;
    border: 1px solid #BBB;
    padding: 0px;
    overflow: auto;
}
.slimScrollBar{
    width: 3px !important;
    display:none !important;
}
@media only screen and (max-width: 64em), only screen and (-webkit-min-device-pixel-ratio: 1.3) and (max-device-width: 1280px), not all, only screen and (max-device-width: 1280px) and (min-resolution: 120dpi){

    #id-container > #id-right{
        height: initial !important;
        overflow: hidden;
        width: 100%;
        display: inline-block;
    }
    body {
        overflow: auto !important;
    }
}

.search_icons_container > input[type="image"] {
    position: absolute;
    left: -9px;
    top: -2px;
    padding: 0px !important;
}
.fichecenter table.boxtable,
.fichecenter table.noborder,
table#table-1,
.fiche table.noborder,
table.liste,
table.border,
div.tabBar{
    border: 0px solid #dbe1e8 !important;
    width:100%;
}

.ui-datepicker select.ui-datepicker-month,.ui-datepicker select.ui-datepicker-year{
    width: 43%;
    margin: 0 1%;
}
div.tabBar{
    display: inline-block;
    width: 100% !important;
}
body .fiche div.tabBar table{
    border: 1px solid transparent !important;
}
.fichecenter table.boxtable,
.fichecenter table.noborder{
    border-collapse: initial;
}
.opacitytransp {
    opacity: 0;
}
div.liste_titre {
    border-bottom: 1px solid #4b6382;
}

body th.liste_titre select, body tr.liste_titre select, body .liste_titre_filter select, body tr.liste_titre tr select, body tr.box_titre select, body div.liste_titre select, body tr.box_titre select {
    background: #ffffff !important;
    color: #333;
}
body th.liste_titre *, body tr.liste_titre *, body tr.box_titre *, body div.liste_titre * {
    color:#ffffff !important;
}
body th.liste_titre span.fa.fa-list{
    color:#444  !important;
}
body div.liste_titre input {
    color: #080808;
}
.pictowarning, .pictopreview{
    padding-left: 4px;
}
.paddingright{
    padding-right: 4px;
}
.icon-plus-filter, .icon-plus-filter-cancel{
    margin:0 1px;
}
body .select2-container .select2-choice > .select2-chosen,body tr.liste_titre input, textarea{
    color: #080808 !important;
}
body table tr th {
    font-weight: bold;
}
#id-left form[action*="list.php"] {
    border: none;
}
body div.tabBar table.noborder[summary=list_of_modules] tr.liste_titre td{
    padding: .3em .5em;
}
body div.tabBar table{
    border-left:none !important;
    border-right:none !important;
}
li.tmenu a.tmenudisabled {
    color: #a9a9a9;
    padding: 0 6px;
    font-size: .6em;
}
body .table-border,
body .table-border-col,
body .table-key-border-col,
body .table-val-border-col,
body div.border,
body div.border div div.tagtd,
body table.border,
body table.border td,
body table.dataTable{
    border: 1px solid #d4dbe9;
}
.boxstats{
    background:#ffffff;
}

.minwidth100{
    max-width:100% !important;
}
.tabBar table tr:nth-child(even) {
    background-color: #ffffff;
}
.tabBar table tr:nth-child(even) tr{
    background-color: #ffffff;
}
body div.tabs .tabsElem a.tabactive{
    color: #ffffff;
    background: #526b8c;
}
#otherboxes tr td{
    padding-top: 8px;
}
body table tr.liste_titre td.liste_titre input[name*=button_search],
body table tr.liste_titre td.liste_titre input[name*=button_removefilter] {
    position: absolute;
    z-index: 2;
    width: 24px;
    margin: -3px 0 0 0px;
    left: 0;
    padding: 0;
    top: 0;
    background:none !important;

}
.div-table-responsive-no-min>table{
    width: 99.99% !important;
}
.div-table-responsive-no-min{
    overflow: auto;
}
div.fiche>form>div.div-table-responsive, div.fiche>form>div.div-table-responsive-no-min {
    overflow-x: auto;
}
body .icon-plus-filter, body .icon-plus-filter-cancel{
    color: #393e70 !important;
}
.icon-plus-filter-cancel:before, .icon-plus-filter:before {
    border-radius: 50%;
    float:left;
}
table tr.liste_titre td.liste_titre .icon-plus-filter, table tr.liste_titre td.liste_titre .icon-plus-filter-cancel{
    font-size: 1.9em !important;
}

div#tmenu_tooltip .tmenudiv li {
    border-right: 1px solid rgba(0, 0, 0, 0.18);
    border-left: 1px solid rgba(0, 0, 0, 0.13);
    border-bottom: 1px solid rgba(0, 0, 0, 0.13);
}
div#tmenu_tooltip .tmenudiv li a.tmenuimage:hover,div#tmenu_tooltip .tmenudiv li:hover {
    background-color: #608FBE;
}
div#tmenu_tooltip .tmenudiv li:hover {
    background-color: #608FBE;
    border-right: 1px solid #608FBE;
    border-left: 1px solid #608FBE;
    border-bottom: 1px solid #608FBE;
}
div#tmenu_tooltip .tmenudiv {
    border-left: 1px solid transparent;
}
div#id-left div.vmenu {
    background-color: transparent;
}
div#id-left ::placeholder {
    color: #c0c0c0;
    opacity: 1; /* Firefox */
}

div#id-left :-ms-input-placeholder { /* Internet Explorer 10-11 */
   color: #c0c0c0;
}

div#id-left ::-ms-input-placeholder { /* Microsoft Edge */
   color: #c0c0c0;
}
div#id-left div.vmenu .company_logo, div#id-left div.vmenu .blockvmenulogo {
    border-bottom: 1px solid rgba(0, 0, 0, 0.13);
    padding: .5em;
}
div#id-left .vmenu>nav {
    border-bottom: 1px solid rgba(0, 0, 0, 0.13);
}
div#blockvmenusearch {
    border-top: 1px solid transparent;
    border-bottom: 1px solid rgba(0, 0, 0, 0.13);
    display: grid;
}
div#blockvmenuhelp *{
    background-color:transparent;
}
div#blockvmenuhelp{
    background-color:transparent;
}
div div#blockvmenubookmarks {
    background-color: transparent;
    border-top: 1px solid #393e70;
    border-bottom: 1px solid #474d84;
    padding: 5px;
    float: initial;
}
div div#blockvmenubookmarks table td:first-child{
    text-align:center;
}
div#blockvmenuhelp *,div#blockvmenubookmarks * {
    color: #ffffff;
}
div#tmenu_tooltip .tmenudiv li a.tmenuimage .mainmenuaspan,
div#id-left div.vmenu a.vsmenu, div#id-left div.vmenu .mainmenuhspan,
div#id-top div.login_block * {
    color: #fbfbfb;
}
div.info{
    color: #333;
}
div.blockvmenusearch input[type=text] {
    width: 75%;
    background: transparent;
    border: 1px solid #606e7e;
    color: #f5f5f5;
}

div.blockvmenusearch input[type=submit] {
    width: 18%;
}

@media screen and (max-width: 1280px){
.login_block .login {
    font-size: 1em !important;
}
}














div#blockvmenubookmarks span.select2 *{
    color: #999;
}
div#blockvmenubookmarks span.select2.select2-container {
    width: 197px !important;
}
#filetreeauto ul.ecmjqft{
    position:relative;
}
#filetree ul.ecmjqft{
    position:relative;
}
body table.liste tr:nth-child(even) {
    background-color: #FFFFFF;
}
.attacharea input[type=file]{
    width:auto;
}
.fiche.modules div.divsearchfield {
    float: left;
    margin: 4px 12px 4px 2px;
    padding-left: 2px;
}
img.photouserphoto {
    height: 14px;
}
div.login_block img.photouserphoto {
    height: 16px;
}

.inline-block.login_block_elem.login_block_elem_name {
    float: right;
    font-size: 10px;
}

div.login_block .login_block_other {
    line-height: 13px;
}

.arearef .pagination li.pagination span {
    background-color: #ffffff;
}
dl.dropdown {
    margin:0px;
    margin-left: 2px;
    margin-right: 2px;
    padding:0px;
    vertical-align: middle;
    display: inline-block;
    position: initial;
    background: #ffffff;
}
.dropdown dd, .dropdown dt {
    margin:0px;
    padding:0px;
}
.dropdown ul {
    margin: -1px 0 0 0;
    text-align: left;
}
.dropdown dd {
    position:relative;
    z-index: 2;
}
.dropdown dt a {
    display:block;
    overflow: hidden;
    border:0;
}
.dropdown dt a span, .multiSel span {
    cursor:pointer;
    display:inline-block;
    padding: 0 3px 2px 0;
}

.dropdown span.value {
    display:none;
}
.dropdown dd ul li {
    white-space: nowrap;
    font-weight: normal;
    padding: 2px;
}
.dropdown dd ul li input[type="checkbox"] {
    margin-right: 3px;
}
.dropdown dd ul li a, .dropdown dd ul li span {
    padding: 3px;
    display: block;
}
.dropdown dd ul li span {
    color: #888;
}
.open>.dropdown-search, .open>.dropdown-bookmark, .open>.dropdown-menu, .dropdown dd ul.open {
    display: block;
}
.dropdown dd ul li a:hover {
    background-color:#fff;
}
dl.dropdown:after{
    content: none;
}
table.tagtable.liste[summary="list_of_modules"] {
    width: 100%;
}
.boxtable td.tdboxstats div.boxstatsindicator .boxstatsborder {
    display: inline-block;
    margin: .2em;
    border: 1px solid #608FBE;
    text-align: center;
    -moz-border-radius: 5px!important;
    -webkit-border-radius: 5px!important;
    border-radius: 5px!important;
    padding: .5em;
}
.boxtable td.tdboxstats{
    background:#fff;
}
.boxtable div.boxstatsindicator {
    display: inline-block;
}
.boxclose.right.nowraponall{
    white-space:nowrap;
}
.boxclose.right.nowraponall .linkobject.boxfilter{
    margin-right:10px;
}
.nographyet {
    content:url(<?php echo dol_buildpath($path.'/theme/owntheme/img/nographyet.svg',1) ?>);
    display: inline-block;
    opacity: 0.1;
    background-repeat: no-repeat;
}
#id-right>.fiche>table.notopnoleftnoright .pagination .paginationafterarrows form[name="projectform"] select,#id-right .notopnoleftnoright .pagination .paginationafterarrows form[name="projectform"] input {
    font-size:14px;
}
#id-right>.fiche table.notopnoleftnoright div.pagination>ul{
    list-style: none;
}
.fiche>form>table.notopnoleftnoright[summary]{
    display:inline-block;
}
.fiche>form>table.notopnoleftnoright tr td:first-child{
    border: none;
    color: #608FBE;
    /* font-size: 2em; */
    text-transform: none;
    white-space: nowrap;
}
.fiche>form>table.notopnoleftnoright{
    margin-bottom:0 !important;
}
#id-right>.fiche>table.notopnoleftnoright tr td:first-child,
#id-right>.fiche>table.notopnoleftnoright tr td:first-child .titre{
    white-space: nowrap;
}

font.vsmenudisabled.vsmenudisabledmargin, font.vmenudisabled.vmenudisabledmargin {
    color: #929292;
}


/* ============================================================================== */
/*  jFileTree                                                                     */
/* ============================================================================== */

.ecmfiletree {
    width: 99%;
    height: 99%;
    background: #FFF;
    padding-left: 2px;
    font-weight: normal;
}

.fileview {
    width: 99%;
    height: 99%;
    background: #FFF;
    padding-left: 2px;
    padding-top: 4px;
    font-weight: normal;
}

div.filedirelem {
    position: relative;
    display: block;
    text-decoration: none;
}

ul.filedirelem {
    padding: 2px;
    margin: 0 5px 5px 5px;
}
ul.filedirelem li {
    list-style: none;
    padding: 2px;
    margin: 0 10px 20px 10px;
    width: 160px;
    height: 120px;
    text-align: center;
    display: block;
    float: left;
    border: solid 1px #DDDDDD;
}

ul.ecmjqft {
    line-height: 16px;
    padding: 0px;
    margin: 0px;
    font-weight: normal;
}

ul.ecmjqft li {
    list-style: none;
    padding: 0px;
    padding-left: 20px;
    margin: 0px;
    white-space: nowrap;
    display: block;
}

ul.ecmjqft a {
    line-height: 24px;
    vertical-align: middle;
    color: #333;
    padding: 0px 0px;
    font-weight:normal;
    display: inline-block !important;
}
ul.ecmjqft a:active {
    font-weight: bold !important;
}
ul.ecmjqft a:hover {
    text-decoration: underline;
}
div.ecmjqft {
    vertical-align: middle;
    display: inline-block !important;
    text-align: right;
    float: right;
    right:4px;
    clear: both;
}
div#ecm-layout-west {
    width: 380px;
    vertical-align: top;
}
div#ecm-layout-center {
    width: calc(100% - 390px);
    vertical-align: top;
    float: right;
}

.ecmjqft LI.directory { font-weight:normal; background: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/folder2.png',1) ?>) left top no-repeat; }
.ecmjqft LI.expanded { font-weight:normal; background: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/folder2-expanded.png) left top no-repeat;',1) ?> }
.ecmjqft LI.wait { font-weight:normal; background: url(<?php echo dol_buildpath($path.'/theme/eldy/img/working.gif) left top no-repeat;',1) ?> }


.clearboth{
    clear: both;
}
img.userphotosmall{
    border-radius: 6px;
    width: 12px;
    height: 12px;
    background-size: contain;
    vertical-align: middle;
    background-color: #FFF;
}































#id-left div.vmenu a.vsmenu:hover{
    background-color: transparent !important;
}

#id-left div.vmenu ul.vmenu li>div:hover{
    background-color: #465b7a !important;
}

#id-left div.vmenu a[data-actif="nc_actif_element"]:hover{
    background-color: #36C6D3 !important;
}

div[data-actif="nc_actif_element"] a{
    color: #fff !important;
}
body #id-left div.vmenu li.menu_titre>div {
    padding: 3px 1.8em;
}
body #id-left div.vmenu li.menu_titre>div.menu_contenu2 {
    padding: 3px 2.8em;
}
body #id-left div.vmenu li.menu_titre>div.menu_contenu3 {
    padding: 3px 3.8em;
}
#tmenu_tooltip .tmenudiv,#tmenu_tooltip .tmenu{
    /*float:left;*/
}
div#id-top div.login_block .login_block_other{
    font-size: 12px;
}
div#id-top div.login_block .login_block_user>div{
    float:right;
}
body #id-left div.vmenu li.menu_titre a * {
    line-height: 18px;
}
body #id-left div.vmenu li.menu_titre>a {
    padding: 7px 0 7px 7px;
}
body select option:disabled{
    color: #e3e3e3 !important;
}
div div#blockvmenubookmarks select {
    width: 100%;
}
body #id-left div.vmenu li.menu_titre a {
    display:inline-block;
    width:100%;
}
body #id-left div.vmenu li.menu_titre {
    padding:3px 0;
}
body #tmenu_tooltip .tmenudiv li a.tmenuimage .mainmenu{
    color: #f5f5f5;
}


body #tmenu_tooltip .tmenudiv li.tmenusel a.tmenuimage .mainmenuaspan{
    color: #ffffff;
}
body #tmenu_tooltip .tmenudiv li.tmenusel div.mainmenu {
    color: #ffffff;
}


#id-left div.vmenu{
    width: 210px;
}
.fixed-menu #id-right{
    width: 100%;
}
#tiptip_holder #tiptip_content, .conteneur, body.fixed-menu #id-right{
    font-size: 1.2rem;
}
div.icon-engin_chantier:before {
    content: "\e603";
}
div.mainvmenu.icon-cmpProd:before {
    content: "\e60e";
}
div.mainvmenu.icon-arvAchat:before {
    content: "\e62e";
}
.icon-transferorders:before{
  content: '\e615';
}
div.mainvmenu.icon-lrh:before {
    content: "\e611";
}

div.mainvmenu.icon-email_templates:before, div.mainvmenu.icon-blockedlogbrowser:before, div.mainvmenu.icon-resource:before {
    content: "\e61a";
}

.minwidth100 { min-width: 100px; }
.minwidth200 { min-width: 200px; }
.minwidth300 { min-width: 300px; }
.maxwidth100 { max-width: 100px; }
.maxwidth200 { max-width: 200px; }
.maxwidth300 { max-width: 300px; }

input[type=submit],
button,
.button,
.butAction,
.butActionDelete,
.butActionRefused,
div.tabs .tabsElem a {
    background: <?=$col2?>;
    color: <?=$btn_txt?>;
}

input[type=submit]:link,
button:link,
.button:link,
.butAction:link,
.butActionDelete:link,
.butActionRefused:link,
div.tabs .tabsElem a:link {
    background: <?=$col2?>;
    color: <?=$btn_txt?>;
    font-size: 12px;
}

input[type=submit]:visited,
button:visited,
.button:visited,
.butAction:visited,
.butActionDelete:visited,
.butActionRefused:visited,
div.tabs .tabsElem a:visited {
    background: <?=$col2?>;
    color: <?=$btn_txt?>;
}

input[type=submit]:hover,
button:hover,
.button:hover,
.butAction:hover,
.butActionDelete:hover,
.butActionRefused:hover,
div.tabs .tabsElem a:hover {
    background: <?=$col2_lighter?>;
    color: <?=$btn_txt?>;
}

input[type=submit]:active,
button:active,
.button:active,
.butAction:active,
.butActionDelete:active,
.butActionRefused:active,
div.tabs .tabsElem a:active {
    background: <?=$col2?>;
    color: <?=$btn_txt?>;
}


.icon-plus-filter,
.icon-plus-filter-cancel,
.dpInvisibleButtons {
    background: transparent;
    color: <?=$col2?>;
}

.icon-plus-filter:link,
.icon-plus-filter-cancel:link,
.dpInvisibleButtons:link {
    background: transparent;
    color: <?=$col2?>;
}

.icon-plus-filter:visited,
.icon-plus-filter-cancel:visited,
.dpInvisibleButtons:visited {
    background: transparent;
    color: <?=$col2?>;
}

.icon-plus-filter:hover,
.icon-plus-filter-cancel:hover,
.dpInvisibleButtons:hover {
    background: transparent;
    color: <?=$col2_lighter?>;
}

.icon-plus-filter:active,
.icon-plus-filter-cancel:active,
.dpInvisibleButtons:active {
    background: transparent;
    color: <?=$col2_lighter?>;
}

@media
only screen and (max-width: 64em),
only screen and (-webkit-min-device-pixel-ratio: 1.3) and (max-device-width: 1280px),
not all,
only screen and (max-device-width: 1280px) and (min-resolution: 120dpi) {
    #tmenu_tooltip {
        font-size: <?=$conf->global->OWNTHEME_S_HEADER_FONT_SIZE?>;
    }
    #id-left {
        font-size: <?=$conf->global->OWNTHEME_S_VMENU_FONT_SIZE?>;
    }
}















































/* NEW */

.centpercent {
    width: 100%;
}
.quatrevingtpercent, .inputsearch {
    width: 80%;
}
.soixantepercent {
    width: 60%;
}
textarea.centpercent {
    width: 96%;
}

#upbuttons-nav ul li{
    padding: .5em 1em;
    white-space: nowrap;
}

/* For table into table into card */
div.ficheaddleft tr.liste_titre:first-child td table.nobordernopadding td {
    padding: 0 0 0 0;
}
div.nopadding {
    padding: 0 !important;
}

.containercenter {
    display : table;
    margin : 0px auto;
}

#pictotitle {
    margin-right: 8px;
    margin-bottom: 4px;
}
.pictoobjectwidth {
    width: 14px;
}
.pictosubstatus {
    padding-left: 2px;
    padding-right: 2px;
}
.pictostatus {
    width: 15px;
    vertical-align: middle;
    margin-top: -3px
}
.pictowarning, .pictopreview {
    padding-left: 3px;
}
.pictoedit, .pictowarning, .pictodelete {
    vertical-align: text-bottom;
}
.fiche img.pictoedit {
    opacity: 0.7;
}
.colorthumb {
    padding-left: 1px !important;
    padding-right: 1px;
    padding-top: 1px;
    padding-bottom: 1px;
    width: 44px;
    text-align:center;
}
div.attacharea {
    padding-top: 18px;
    padding-bottom: 10px;
}
div.attachareaformuserfileecm {
    padding-top: 0;
    padding-bottom: 0;
}

div.arearef {
    padding-top: 2px;
    margin-bottom: 10px;
    padding-bottom: 7px;
}
div.arearefnobottom {
    padding-top: 2px;
    padding-bottom: 4px;
}
div.heightref {
    min-height: 80px;
}
div.divphotoref {
    padding-right: 20px;
}
div.paginationref {
    padding-bottom: 10px;
}
div.statusref {
    float: right;
    padding-left: 12px;
    margin-top: 8px;
    margin-bottom: 10px;
    clear: both;
}
div.statusref img {
    padding-left: 8px;
    padding-right: 9px;
    vertical-align: text-bottom;
}
div.statusrefbis {
    padding-left: 8px;
    padding-right: 9px;
    vertical-align: text-bottom;
}
img.photoref, div.photoref {
    border: 1px solid #CCC;
    -webkit-box-shadow: 2px 2px 4px #ccc;
    box-shadow: 2px 2px 4px #ccc;
    padding: 4px;
    height: 80px;
    width: 80px;
    object-fit: contain;
}
img.fitcontain {
    object-fit: contain;
}
div.photoref {
    display:table-cell;
    vertical-align:middle;
    text-align:center;
}
img.photorefnoborder {
    padding: 2px;
    height: 48px;
    width: 48px;
    object-fit: contain;
    border: 1px solid #AAA;
    border-radius: 100px;
}
.underrefbanner {
}
.underbanner {
    border-bottom: 2px solid rgb(120,120,120);
}
.tdhrthin {
    margin: 0;
    padding-bottom: 0 !important;
}
/* END For table into table into card */
body.onlinepaymentbody div.fiche {  /* For online payment page */
    margin: 20px !important;
}
div.fiche>table:first-child {
    margin-bottom: 15px !important;
}
div.fichecenter {
    /* margin-top: 10px; */
    width: 100%;
    clear: both;    /* This is to have div fichecenter that are true rectangles */
}
div.fichecenterbis {
    margin-top: 8px;
}
div.fichethirdleft {
    float: left;
    width: 50%;
    }
div.fichetwothirdright {
    float: right;
    width: 50%;
    }
div.fichehalfleft {
    float: left;
    width: 50%;
}
div.fichehalfright {
    float: right;
    width: 50%;
}
div.ficheaddleft {
    padding-left: 16px;
}
div.firstcolumn div.box {
    padding-right: 10px;
}
div.secondcolumn div.box {
    padding-left: 10px;
}
.butActionNew, .butActionNewRefused, .butActionNew:link, .butActionNew:visited, .butActionNew:hover, .butActionNew:active {
    text-decoration: none;
    text-transform: uppercase;
    font-weight: normal;

    margin: 0em 0.3em 0 0.3em !important;
    padding: 0.2em 0.7em 0.3em;
    font-family: roboto,arial,tahoma,verdana,helvetica;
    display: inline-block;
    /* text-align: center; New button are on right of screen */
    cursor: pointer;
    /*color: #fff !important;
    background: rgb(60,70,100);
    border: 1px solid rgb(60,70,100);*/
    border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25);

    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;

    padding-top: 0 !important;
}
a.butActionNew>span.fa-plus-circle, a.butActionNew>span.fa-plus-circle:hover { padding-left: 6px; font-size: 1.5em; border: none; box-shadow: none; webkit-box-shadow: none; }
a.butActionNewRefused>span.fa-plus-circle, a.butActionNewRefused>span.fa-plus-circle:hover { padding-left: 6px; font-size: 1.5em; border: none; box-shadow: none; webkit-box-shadow: none; }
.butActionNew *, .butActionNewRefused *, .butActionNew *:link, .butActionNew *:visited, .butActionNew *:hover, .butActionNew *:active{
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
    padding-top: 0 !important;
}
.span-icon-multicompany {
    width: auto !important;
}
div#s2id_receivercc,div#s2id_receiver{
    min-width: 400px;
    width: auto;
}
div.tabsAction.upbuttonsdiv {
    position: initial !important;
    bottom: initial !important;
    right: initial !important;
    background-color: initial !important;
    padding: .5em 0 !important;
    border: initial !important;
    border-radius: initial !important;
    margin: initial !important;
    opacity: initial !important;
    display: block !important;
}
div.tabsAction.upbuttonsdiv .divButAction a{
    padding: .5em 1em !important;
}
.tabBar .tagtd tr:nth-child(odd),body tr td tr:nth-child(odd) {
    background-color: transparent !important;
}
#blockvmenusearch .select2.select2-container{
    width:100% !important;
}
body #id-left div.vmenu li.menu_titre>div>span.vsmenu {
    color: #ededed;
}
.login_table .span-icon-multicompany {
    width: auto !important;
}
.dashboardlinelatecoin {
    float: right;
    position: relative;
    text-align: right;
    top: -24px;
    padding: 1px 2px 1px 2px;
    border-radius: .25em;
    background-color: #9f4705;
    padding: 0px 5px 0px 5px;
    /* top: -26px; */
}
span.dashboardlineko {
    color: #FFF;
    font-size: 80%;
}
.boxstats130 {
    width: 158px;
    height: 48px;
    padding: 3px;
}
.boxstatscontent {
    padding: 3px;
}
.boxstats, .boxstats130, .boxstatscontent {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.boxstats {
    padding: 3px;
    width: 103px;
}
body #id-left div.vmenu li.menu_titre>div.menu_top{
    padding: 0 !important;
}
body #id-left div.vmenu li.menu_titre>div.menu_end{
    padding: 0 !important;
}
.tmenuend {
    display: none;
}
select.flat.selectlimit {
    max-width: 62px;
}
.selectlimit, .marginrightonly {
    margin-right: 10px !important;
}
.marginleftonly {
    margin-left: 10px !important;
}
.nomarginleft {
    margin-left: 0px !important;
}
.selectlimit, .selectlimit:focus {
    border-left: none !important;
    border-top: none !important;
    border-right: none !important;
    outline: none;
}
.strikefordisabled {
    text-decoration: line-through;
}
.widthdate {
    width: 130px;
}
.cursorpointer {
    cursor: pointer;
}
.cursormove {
    cursor: move;
}


/*---------------------------------------------*/
.cke_reset {
    min-width: 250px;
}
.flexcontainer {
    display: inline-flex;
    flex-flow: row wrap;
    justify-content: flex-start;
}
.thumbstat {
    flex: 1 1 116px;
}
.thumbstat150 {
    flex: 1 1 170px;
}
.thumbstat, .thumbstat150 {
    /* flex-grow: 1; */
    /* flex-shrink: 1; */
    /* flex-basis: 140px; */
    display: inline;
    width: 100%;
    justify-content: flex-start;
    align-self: flex-start;
}
/*
 *  Boxes
 */

.ficheaddleft div.boxstats {
    border: none;
}
.boxstatsborder {
    border: 1px solid #CCC !important;
}
.boxstats, .boxstats130 {
    display: inline-block;
    margin: 3px;
    border: 1px solid #CCC;
    text-align: center;
    border-radius: 2px;
}
.boxstats, .boxstats130, .boxstatscontent {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.boxstats {
    padding: 3px;
    width: 103px;
}
.boxstats130 {
    width: 158px;
    height: 48px;
    padding: 3px
}
.boxstatscontent {
    padding: 3px;
}
div.fichecenter{
    display:block !important;
}
#upbuttons-nav ul{
    top: -36px !important;
}
#upbuttons-nav ul li>a, #upbuttons-nav ul li>span{
    box-shadow: 2px 2px 4px #565656;
}
/*---------------------------------------------*/

/*----------------------08/09-----------------------*/
.select2-container *,.select2-results * {
    font-size: <?=$conf->global->OWNTHEME_S_VMENU_FONT_SIZE?>;
}
/*--------------------END 08/09---------------------*/


/*----------------------17/10-----------------------*/
body .jnotify-container{
    top: 0px !important;
    right: 0 !important;
}
/*--------------------END 17/10---------------------*/

/*----------------------30/11-----------------------*/
.select2-container-multi-dolibarr .select2-choices-dolibarr .select2-search-choice-dolibarr {
  padding: 2px 5px 1px 5px;
  margin: 0 0 2px 3px;
  position: relative;
  line-height: 13px;
  color: #333;
  cursor: default;
  border: 1px solid #aaaaaa;
  border-radius: 3px;
  -webkit-box-shadow: 0 0 2px #fff inset, 0 1px 0 rgba(0, 0, 0, 0.05);
  box-shadow: 0 0 2px #fff inset, 0 1px 0 rgba(0, 0, 0, 0.05);
  background-clip: padding-box;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  background-color: #e4e4e4;
  background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, color-stop(20%, #f4f4f4), color-stop(50%, #f0f0f0), color-stop(52%, #e8e8e8), color-stop(100%, #eee));
  background-image: -webkit-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #eee 100%);
  background-image: -moz-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #eee 100%);
  background-image: linear-gradient(to bottom, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #eee 100%);
}
.select2-container-multi-dolibarr .select2-choices-dolibarr .select2-search-choice-dolibarr a {
    font-weight: normal;
}
.select2-container-multi-dolibarr .select2-choices-dolibarr li {
  float: left;
  list-style: none;
}
.select2-container-multi-dolibarr .select2-choices-dolibarr {
  height: auto !important;
  height: 1%;
  margin: 0;
  padding: 0 5px 0 0;
  position: relative;
  cursor: text;
  overflow: hidden;
}
/*----------------------END 30/11-----------------------*/
/*----------------------31/01/2019-----------------------*/
div.mainmenu.tmenudisabled {
    display: none;
}
@media only screen and (max-width: 64em), only screen and (-webkit-min-device-pixel-ratio: 1.3) and (max-device-width: 1280px), not all, only screen and (max-device-width: 1280px) and (min-resolution: 120dpi)
{
#tmenu_tooltip .tmenudiv li {
    width: auto !important;
}
}
/*----------------------END 31/01-----------------------*/


/*----------------------    12/02/19-----------------------*/
.flexcontainer {
    display: inline-flex;
    flex-flow: row wrap;
    justify-content: flex-start;
}
.thumbstat {
    min-width: 150px;
}
.thumbstat150 {
    min-width: 168px;
    max-width: 169px;
}
.thumbstat, .thumbstat150 {
    flex-grow: 1;
    flex-shrink: 0;
}
.butAction, .butActionDelete, .butActionRefused, .button, button, input[type=image], input[type=submit],select{
    font-size: 12px;
}
/*----------------------END 31/02/19-----------------------*/





/*---------------------- 23/04/19 -----------------------*/
dl.dropdown *{
    color:#FFFFFF !important;
}
table .dropdown dt a span, .multiSel span{
    padding: 0px 3px 2px 3px;
}
.dropdown dd ul li a:hover,.dropdown dt a {
    color:#000 !important;
}
.jnotify-container{
    font-size: 11px;
}
.jnotify-container .jnotify-notification a.jnotify-close{
    font-size: initial;
}
/*---------------------- END 23/04/19 -----------------------*/



<?php
$ardolv = DOL_VERSION;
$ardolv = explode(".", $ardolv);
$dolvs = $ardolv[0];

// if(DOL_VERSION <= '9.9.9' && DOL_VERSION != '10.0.0'){
if($dolvs < 10){
?>
    body{
        margin: 0;
    }
<?php
}else{
?>

    /*---------------------- css version 10 05/08/19  // by Imane -----------------------*/


        @media only screen and (max-width: 962px){
            body .login_block .dropdown-menu{
                left:0;
            }
        }

        .open>.dropdown-menu{ /*, #topmenu-login-dropdown:hover .dropdown-menu*/
            display: block;
        }

        .dropdown-menu {
            box-shadow: none;
            border-color: #eee;
        }
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            display: none;
            float: left;
            min-width: 160px;
            padding: 5px 0;
            margin: 2px 0 0;
            font-size: 14px;
            text-align: left;
            list-style: none;
            background-color: #fff;
            -webkit-background-clip: padding-box;
            background-clip: padding-box;
            border: 1px solid #ccc;
            border: 1px solid rgba(0,0,0,.15);
            border-radius: 4px;
            -webkit-box-shadow: 0 6px 12px rgba(0,0,0,.175);
            box-shadow: 0 6px 12px rgba(0,0,0,.175);
        }



        /*
        * MENU Dropdown
        */
        .login_block.usedropdown .logout-btn{
            display: none;
        }

        .tmenu .open.dropdown, .login_block .open.dropdown, .tmenu .open.dropdown, .login_block .dropdown:hover{
            background: rgba(0, 0, 0, 0.1);
        }
        .tmenu .dropdown-menu, .login_block .dropdown-menu {
            position: absolute;
            right: 0;
            <?php echo $left; ?>: auto;
            line-height:1.3em;
        }
        .tmenu .dropdown-menu, .login_block  .dropdown-menu .user-body {
            border-bottom-right-radius: 4px;
            border-bottom-left-radius: 4px;
        }
        .user-body {
            color: #333;
        }
        .side-nav-vert .user-menu .dropdown-menu {
            border-top-right-radius: 0;
            border-top-left-radius: 0;
            padding: 1px 0 0 0;
            border-top-width: 0;
            width: 300px;
        }
        .side-nav-vert .user-menu .dropdown-menu {
            margin-top: 0;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .side-nav-vert .user-menu .dropdown-menu > .user-header {
            height: 175px;
            padding: 10px;
            text-align: center;
            white-space: normal;
        }

        .dropdown-user-image {
            border-radius: 50%;
            vertical-align: middle;
            z-index: 5;
            height: 90px !important;
            width: 90px !important;
            border: 3px solid;
            border-color: transparent;
            border-color: rgba(255, 255, 255, 0.2);
            max-width: 100%;
            max-height :100%;
        }

        .dropdown-menu > .user-header{
            background: rgb(<?php echo $colorbackhmenu1 ?>);
            background:#474c80;
        }

        .dropdown-menu > .user-footer {
            background-color: #f9f9f9;
            padding: 10px;
        }

        .user-footer:after {
            clear: both;
        }

        .dropdown-menu > .user-body {
            padding: 15px;
            border-bottom: 1px solid #f4f4f4;
            border-top: 1px solid #dddddd;
            white-space: normal;
        }

        #topmenu-login-dropdown{
            padding: 0 5px 0 5px;
        }
        #topmenu-login-dropdown a:hover{
            text-decoration: none;
        }

        #topmenuloginmoreinfo-btn{
            display: block;
            text-aling: right;
            color:#666;
            cursor: pointer;
        }

        #topmenuloginmoreinfo{
            display: none;
            clear: both;
            font-size: 0.95em;
        }

        .button-top-menu-dropdown {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-image: none;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .user-footer .button-top-menu-dropdown {
            color: #666666;
            border-radius: 0;
            -webkit-box-shadow: none;
            -moz-box-shadow: none;
            box-shadow: none;
            border-width: 1px;
            background-color: #f4f4f4;
            border-color: #ddd;
        }

        .dropdown:after {
            content: '' !important;
        }

        span#dropdown-icon-up{
            display: none;
        }

        div#topmenu-login-dropdown {
            border: none !important;
        }


        .inline-block a {
             /*color: #ededed !important;*/
        }

        a.button-top-menu-dropdown {
            color: #2b3643 !important;
        }

        .bodylogin {
            width: 100% !important;
            height: 100% !important;
            position: absolute;
            display: table;
        }

        .login_center {
            display: table-cell;
            vertical-align: middle;
        }

        span.fa.fa-user {
            margin-right: 4px;
        }

        .trinputlogin {
            margin-left: 40px;
        }
        .menuhider {
            display: none !important;
        }
        li.menuhider:hover {
            background-image: none !important;
        }

        @media only screen and (max-width: 962px){
            .menuhider {
                display: block !important;
            }
            body.sidebar-collapse .login_block {
                display: none;
            }
            .side-nav {
                z-index: 200;
                padding-top: 70px;
                border-bottom: 1px solid #BBB;
                background: rgb(71, 76, 128);
                padding-left: 20px;
                padding-right: 20px;
                position: fixed;
                z-index: 90;
            }
            body.sidebar-collapse .side-nav {
                display: none;
            }
            div.login_block_other {
                clear: both;
                min-width: 0;
                width: 100%;
                display: inline-block;
            }

            div.login_block {
               padding-top: 10px;
                padding-left: 20px;
                padding-right: 20px !important;
                padding-bottom: 16px;
                top: inherit !important;
                left: 0 !important;
                text-align: center !important;
                vertical-align: middle;
                background: rgb(71, 76, 128);
                height: 50px;
                z-index: 202;
                min-width: 200px;
                max-width: 200px;
                width: 200px;
                margin-top: 40px;
            }

            div.login_block_user,div.login_block_other {
                display: inline-block !important;
            }
            .fixed-menu #id-left {
                display: block !important;
                position: initial !important;
                width: auto !important;
                transform: none !important;
            }
        }
        div.mainmenu.menu::before {
            content: "\f0c9";
        }
        div.login_block {
            position: absolute;
            text-align: right;
            right: 0;
            top: 0;
            line-height: 10px;
        }

        div.login_block_other {
            display: inline-block;
            clear: both;
        }

        .menuhider .mainmenu::before {
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 26px;
            font-size: 1.2em;
            -webkit-font-smoothing: antialiased;
            text-align: center;
            text-decoration: none;
            color: #FFFFFF;
        }

    /*---------------------- END 05/08/19 -----------------------*/


<?php
}
?>

/*------------------------- 06/09/19 ------------------------*/
div.menu_contenu#menu_contenu_logo{
    padding:0 0 11px
}
@media only screen and (max-width: 962px){
    #id-left div.vmenu{
        width: 191px;
    }
    #tmenu_tooltip{
        padding-right: initial;
    }
    .fixed-menu #id-left{
        padding-bottom:50px;
    }
}
#tmenu_tooltip{
    padding-right: 77px;
}
div.error {
    border-left: solid 5px #f28787;
    padding-top: 8px;
    padding-left: 10px;
    padding-right: 4px;
    padding-bottom: 8px;
    margin: 0.5em 0em 0.5em 0em;
    background: #EFCFCF;
    color: #550000 !important;
}
.login_block .dropdown-menu .user-body *,.login_block .dropdown-menu .user-footer * {
    color: #666 !important;
}
/*---------------------- END 06/09/19 -----------------------*/


/*---------------------- BEGIN 26/09/19 -----------------------*/
<?php if($dolvs < 12){ ?>
    .widthpictotitle {
        width: 32px;
    }
<?php } ?>

div.login_block a {
    color: #608FBE !important;
}
.center {
    text-align: center;
    margin: 0px auto;
}
/*---------------------- END 06/09/19 -----------------------*/



/* * * * * * * * * * * * * * * * * * * 30/09/2019 * * * */
.center {
    text-align: center;
    margin: 0px auto;
}

.notopnoleftnoright td.titre_right .select2-container{
    max-width: 200px;
}

div#login_left, div#login_right {
    display: inline-block !important;
    min-width: 245px;
    padding-top: 10px;
    padding-left: 16px;
    padding-right: 16px;
    text-align: center;
    vertical-align: middle;
}
.login_table {
    margin: 0px auto;
    padding-left: 6px;
    padding-right: 6px;
    padding-top: 16px;
    padding-bottom: 12px;
    max-width: 560px;
    background-color: #FFFFFF;
    -webkit-box-shadow: 0 2px 23px 2px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(60,60,60,0.15);
    box-shadow: 0 2px 23px 2px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(60,60,60,0.15);
    border-radius: 5px;
}
.login_table .trinputlogin {
    font-size: 1.2em;
    margin: 8px;
}
.login_table_title {
    max-width: 530px;
    color: #eee !important;
    padding-bottom: 20px;
    text-shadow: 1px 1px #444;
}
@media only screen and (max-width: 1000px){
    div.secondcolumn div.box {
        padding-left: 0px;
    }
    div.firstcolumn div.box {
        padding-right: 0px;
    }
}
div#moretabsListaction,div#moretabsListaction,div.tabsElem>div {
    z-index: 5;
}
div#id-left div.vmenu{
    padding-bottom: 60px;
}

@media only screen and (max-width: 962px){
    .side-nav {
        padding-left: 0;
        padding-right: 0;
    }
}

#id-container #id-left div.vmenu{
    width: 210px !important;
}
div.login_block{
    max-width: 210px;
    width: 210px;
}
@media only screen and (max-width: 962px){
    div.login_block {
        /*padding-bottom: 52px;*/
        padding-top: 16px;
    }
}
@media only screen and (max-width: 1074px){
    .side-nav-vert .user-menu .dropdown-menu {
        width: 234px !important;
    }
}
@media only screen and (max-width: 64em), only screen and (-webkit-min-device-pixel-ratio: 1.3) and (max-device-width: 1280px), not all, only screen and (max-device-width: 1280px) and (min-resolution: 120dpi){
    div.fiche.agenda .tabBar table td {
        float: initial !important;
    }
}

body th.liste_titre span.select2 * , body tr.liste_titre span.select2 *, body div.liste_titre  span.select2 * , body body tr.box_titre span.select2 * {
    color: #444 !important;
}

#id-container #id-left{
    margin-left:0;
}

body .butActionDelete, body .butActionDelete:link, body .butActionDelete:visited, body .butActionDelete:hover, body .butActionDelete:active, body .buttonDelete {
    background: #e29595;
    border: 1px solid #633;
    color: #633;
    font-weight: 900;
}
body .butActionRefused {
    cursor: not-allowed;
    color: #999 !important;
    border: 1px solid #ccc !important;
    -moz-box-sizing: border-box;
    background: #7d7d7d21 !important;
}
/* * * * * * * * * * * * * * * * * * * END 30/09/2019 * */





/* * * * * * * * * * * * * * * * * * * END 14/02/2020 * */

/*
 * Component: Info Box
 * -------------------
 */
.info-box {
    display: block;
    position: relative;
    min-height: 90px;
    background: #fff;
    width: 100%;
    box-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2), 0px 0px 2px rgba(0, 0, 0, 0.1);
    border-radius: 2px;
    margin-bottom: 15px;
}
.info-box.info-box-sm{
    min-height: 80px;
    margin-bottom: 10px;
}

.info-box small {
    font-size: 14px;
}
.info-box .progress {
    background: rgba(0, 0, 0, 0.2);
    margin: 5px -10px 5px -10px;
    height: 2px;
}
.info-box .progress,
.info-box .progress .progress-bar {
    border-radius: 0;
}

.info-box .progress .progress-bar {
        float: left;
        width: 0;
        height: 100%;
        font-size: 12px;
        line-height: 20px;
        color: #fff;
        text-align: center;
        background-color: #337ab7;
        -webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
        -webkit-transition: width .6s ease;
        -o-transition: width .6s ease;
        transition: width .6s ease;
}
.info-box-icon {
    border-top-left-radius: 2px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 2px;
    display: block;
    overflow: hidden;
    float: left;
    height: 90px;
    width: 90px;
    text-align: center;
    font-size: 45px;
    line-height: 90px;
    background: rgba(0, 0, 0, 0.2);
}
.info-box-sm .info-box-icon{
    height: 80px;
    width: 80px;
    font-size: 25px;
    line-height: 80px;
}
.info-box-icon > img {
    max-width: 100%;
}
.info-box-icon-text{
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 90px;
    bottom: 0px;
    color: #ffffff;
    background-color: rgba(0,0,0,0.1);
    cursor: default;

    font-size: 10px;
    line-height: 15px;
    padding: 0px 3px;
    text-align: center;
    opacity: 0;
    -webkit-transition: opacity 0.5s, visibility 0s 0.5s;
    transition: opacity 0.5s, visibility 0s 0.5s;
}

<?php if(empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS) && !empty($conf->global->MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD)){ ?>
.info-box-icon-text{
    opacity: 1;
}
<?php } ?>

.info-box-sm .info-box-icon-text{
    overflow: hidden;
    width: 80px;
}
.info-box:hover .info-box-icon-text{
    opacity: 1;
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 90px;
}

.info-box-sm .info-box-content{
    margin-left: 80px;
}
.info-box-number {
    display: block;
    font-weight: bold;
    font-size: 18px;
}
.progress-description,
.info-box-text,
.info-box-title{
    display: block;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.info-box-title{
    text-transform: uppercase;
    font-weight: bold;
}
.info-box-text{
    font-size: 0.92em;
}
.info-box-text:first-letter{text-transform: uppercase}
a.info-box-text{ text-decoration: none;}


.info-box-more {
    display: block;
}
.progress-description {
    margin: 0;
}



/* ICONS INFO BOX */
<?php
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$prefix='';
//$prefix = 'background-';
if (! empty($conf->global->THEME_INFOBOX_COLOR_ON_BACKGROUND)) $prefix = 'background-';

if (! isset($conf->global->THEME_AGRESSIVENESS_RATIO) && $prefix) $conf->global->THEME_AGRESSIVENESS_RATIO=-50;
if (GETPOSTISSET('THEME_AGRESSIVENESS_RATIO')) $conf->global->THEME_AGRESSIVENESS_RATIO=GETPOST('THEME_AGRESSIVENESS_RATIO', 'int');
//var_dump($conf->global->THEME_AGRESSIVENESS_RATIO);
?>
.info-box-icon {
    background-color: #eee !important;
    opacity: 0.95;
}

.bg-infoxbox-project{
    color: #6c6a98 !important;
}
.bg-infoxbox-action{
    color: #b46080  !important;
}
.bg-infoxbox-propal,
.bg-infoxbox-facture,
.bg-infoxbox-commande{
    color: #99a17d  !important;
}
.bg-infoxbox-supplier_proposal,
.bg-infoxbox-invoice_supplier,
.bg-infoxbox-order_supplier{
    color: #599caf  !important;
}
.bg-infoxbox-contrat{
    color: #469686  !important;
}
.bg-infoxbox-bank_account{
    color: #c5903e  !important;
}
.bg-infoxbox-adherent{
    color: #79633f  !important;
}
.bg-infoxbox-expensereport{
    color: #79633f  !important;
}
.bg-infoxbox-holiday{
    color: #755114  !important;
}


.fa-dol-action:before {
    content: "\f073";
}
.fa-dol-propal:before,
.fa-dol-supplier_proposal:before {
    content: "\f2b5";
}
.fa-dol-facture:before,
.fa-dol-invoice_supplier:before {
    content: "\f571";
}
.fa-dol-project:before {
    content: "\f0e8";
}
.fa-dol-commande:before,
.fa-dol-order_supplier:before {
    content: "\f570";
}
.fa-dol-contrat:before {
    content: "\f1e6";
}
.fa-dol-bank_account:before {
    content: "\f19c";
}
.fa-dol-adherent:before {
    content: "\f0c0";
}
.fa-dol-expensereport:before {
    content: "\f555";
}
.fa-dol-holiday:before {
    content: "\f5ca";
}


/* USING FONTAWESOME FOR WEATHER */
.info-box-weather .info-box-icon{
    background: rgba(0, 0, 0, 0.08) !important;
}
.fa-weather-level0:before{
    content: "\f185";
    color : #cccccc;
}
.fa-weather-level1:before{
    content: "\f6c4";
    color : #cccccc;
}
.fa-weather-level2:before{
    content: "\f0c2";
    color : #cccccc;
}
.fa-weather-level3:before{
    content: "\f740";
    color : #cccccc;
}
.fa-weather-level4:before{
    content: "\f0e7";
    color : #b91f1f;
}

/* USING IMAGES FOR WEATHER INTEAD OF FONT AWESOME */
/* For other themes just uncomment this part */
/*.info-box-weather-level0,
.info-box-weather-level1,
.info-box-weather-level2,
.info-box-weather-level3,
.info-box-weather-level4 {
    background-position: 15px 50%;
    background-repeat: no-repeat;
}

.info-box-weather .info-box-icon{
    display: none !important;
}
.info-box-weather-level0 {
    background-image: url("img/weather/weather-clear.png");
}
.info-box-weather-level1 {
    background-image: url("img/weather/weather-few-clouds.png");
}
.info-box-weather-level2 {
    background-image: url("img/weather/weather-clouds.png");
}
.info-box-weather-level3 {
    background-image: url("img/weather/weather-many-clouds.png");
}
.info-box-weather-level4 {
    background-image: url("img/weather/weather-storm.png");
}*/



.box-flex-container{
    display: flex; /* or inline-flex */
    flex-direction: row;
    flex-wrap: wrap;
    width: 100%;
    margin: 0 0 0 -15px;
    /*justify-content: space-between;*/
}

.box-flex-item{
    flex-grow : 1;
    flex-shrink: 1;
    flex-basis: auto;

    width: 280px;
    margin: 5px 0px 0px 15px;
}
.box-flex-item.filler{
    margin: 0px 0px 0px 15px !important;
    height: 0;
}

.pictomodule {
    width: 14px;
}




/*
 * Component: Progress Bar
 * -----------------------
 */

.progress * {
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}

.progress {
    height: 20px;
    overflow: hidden;
    background-color: #f5f5f5;
    background-color: rgba(128, 128, 128, 0.1);
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.progress.spaced{
    margin-bottom: 20px;
}

.progress-bar {
    float: left;
    width: 0;
    height: 100%;
    font-size: 12px;
    line-height: 20px;
    color: #fff;
    text-align: center;
    background-color: #337ab7;
    -webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
    box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
    -webkit-transition: width .6s ease;
    -o-transition: width .6s ease;
    transition: width .6s ease;
}



.progress-group > .progress{
    clear: both;
}

.progress,
.progress > .progress-bar {
    -webkit-box-shadow: none;
    box-shadow: none;
}
.progress,
.progress > .progress-bar,
.progress .progress-bar,
.progress > .progress-bar .progress-bar {
    border-radius: 1px;
}
/* size variation */
.progress.sm,
.progress-sm {
    height: 10px;
}
.progress.sm,
.progress-sm,
.progress.sm .progress-bar,
.progress-sm .progress-bar {
    border-radius: 1px;
}
.progress.xs,
.progress-xs {
    height: 7px;
}
.progress.xs,
.progress-xs,
.progress.xs .progress-bar,
.progress-xs .progress-bar {
    border-radius: 1px;
}
.progress.xxs,
.progress-xxs {
    height: 3px;
}
.progress.xxs,
.progress-xxs,
.progress.xxs .progress-bar,
.progress-xxs .progress-bar {
    border-radius: 1px;
}


/* Vertical bars */
.progress.vertical {
    position: relative;
    width: 30px;
    height: 200px;
    display: inline-block;
    margin-right: 10px;
}
.progress.vertical > .progress-bar {
    width: 100%;
    position: absolute;
    bottom: 0;
}
.progress.vertical.sm,
.progress.vertical.progress-sm {
    width: 20px;
}
.progress.vertical.xs,
.progress.vertical.progress-xs {
    width: 10px;
}
.progress.vertical.xxs,
.progress.vertical.progress-xxs {
    width: 3px;
}
.progress-group .progress-text {
    font-weight: 600;
}
.progress-group .progress-number {
    float: right;
}



/* Remove margins from progress bars when put in a table */
.table tr > td .progress {
    margin: 0;
}
.progress-bar-light-blue,
.progress-bar-primary {
    background-color: #3c8dbc;
}
.progress-striped .progress-bar-light-blue,
.progress-striped .progress-bar-primary {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-green,
.progress-bar-success {
    background-color: <?php echo $badgeSuccess ?>;
}
.progress-striped .progress-bar-green,
.progress-striped .progress-bar-success {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-aqua,
.progress-bar-info {
    background-color: #00c0ef;
}
.progress-striped .progress-bar-aqua,
.progress-striped .progress-bar-info {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-yellow,
.progress-bar-warning {
    background-color: <?php echo $badgeWarning ?>;
}
.progress-striped .progress-bar-yellow,
.progress-striped .progress-bar-warning {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-red,
.progress-bar-danger {
    background-color: <?php echo $badgeDanger ?>;
}
.progress-striped .progress-bar-red,
.progress-striped .progress-bar-danger {
    background-image: -webkit-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: -o-linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
    background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
}
.progress-bar-consumed {
    background-color: rgb(0, 0, 0, 0.15);
}


.fa-window-close:before {
    content: "\f410" !important;
}

/* * * * * * * * * * * * * * * * * * * END 14/02/2020 * */













/* * * * * * * * * * * * * * * * * * * Version 11 of Dolibarr * */
/* <style type="text/css" > */
    body div.tabs .tabsElem a.tabactive {
    color: #ffffff !important;
    background: #245d8f !important;
}
.badge {
    display: inline-block;
    padding: .1em .35em;
    font-size: 80%;
    font-weight: 700 !important;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: .25rem;
    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    border-width: 2px;
    border-style: solid;
    border-color: rgba(255,255,255,0);
    box-sizing: border-box;
}
.badge-secondary, .tabs .badge {
    color: #fff !important;
    background-color: #3b8cd2;
}
.badge-pill, .tabs .badge {
    padding-right: .5em;
    padding-left: .5em;
    border-radius: 0.25rem;
}
.marginleftonlyshort {
    margin-left: 4px !important;
}
input:disabled, textarea:disabled, select[disabled='disabled']
{
    background:#eee;
}
div#moretabsList, div#moretabsListaction {
    z-index: 5;
}
.h1 .small, .h1 small, .h2 .small, .h2 small, .h3 .small, .h3 small, h1 .small, h1 small, h2 .small, h2 small, h3 .small, h3 small {
    font-size: 65%;
}
.h1 .small, .h1 small, .h2 .small, .h2 small, .h3 .small, .h3 small, .h4 .small, .h4 small, .h5 .small, .h5 small, .h6 .small, .h6 small, h1 .small, h1 small, h2 .small, h2 small, h3 .small, h3 small, h4 .small, h4 small, h5 .small, h5 small, h6 .small, h6 small {
    font-weight: 400;
    line-height: 1;
    color: #777;
}
.wordbreakimp {
    word-break: break-word;
}
.marginleft2 {
    margin-left: 2px;
}
.marginright2 {
    margin-right: 2px;
}
.nobackground, .nobackground tr {
    background: unset !important;
}
.text-warning{
    color : #a37c0d}
body[class*="colorblind-"] .text-warning{
    color : #a37c0d}
.text-success{
    color : #28a745}
body[class*="colorblind-"] .text-success{
    color : #37de5d}
.text-danger{
    color : #9f4705}
.editfielda span.fa-pencil-alt, .editfielda span.fa-trash {
    color: #ccc !important;
}
.editfielda span.fa-pencil-alt:hover, .editfielda span.fa-trash:hover {
    color: rgb(0,0,0) !important;
}
.fa-toggle-on, .fa-toggle-off { font-size: 2em; }
.websiteselectionsection .fa-toggle-on, .websiteselectionsection .fa-toggle-off,
.asetresetmodule .fa-toggle-on, .asetresetmodule .fa-toggle-off {
    font-size: 1.5em; vertical-align: text-bottom;
}
.badge-status {
    font-size: 1em;
    padding: .19em .35em;           /* more than 0.19 generate a change into heigth of lines */
}
/* WARNING colorblind */
body[class^="colorblind-"] .badge-warning {
    background-color: #e4e411;
}
body[class^="colorblind-"] a.badge-warning.focus,body[class^="colorblind-"] a.badge-warning:focus {
    box-shadow: 0 0 0 0.2rem rgba(228,228,17,0.5);
}
body[class^="colorblind-"] a.badge-warning:focus, a.badge-warning:hover {
    background-color: #cbcb00;
}
.font-status0 {
        color: #fff !important;
}
.font-status1 {
        color: #bc9526 !important;
}
/* COLORBLIND STATUS1 */
body[class*="colorblind-"] .badge-status1 {
        color: #000 !important;
        background-color: #e4e411;
}
body[class*="colorblind-"] .font-status1 {
        color: #e4e411 !important;
}
body[class*="colorblind-"] .badge-status1.focus, body[class*="colorblind-"] .badge-status1:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(228,228,17,0.5);
}
body[class*="colorblind-"] .badge-status1:focus, body[class*="colorblind-"] .badge-status1:hover {
    color: #000 !important;
}
.font-status2 {
        color: #e6f0f0 !important;
}
.font-status3 {
        color: #fff !important;
}
.font-status4 {
        color: #55a580 !important;
}
/* COLORBLIND STATUS4 */
body[class*="colorblind-"] .badge-status4 {
        color: #000 !important;
        background-color: #37de5d;
}
body[class*="colorblind-"] .font-status4 {
        color: #37de5d !important;
}
body[class*="colorblind-"] .badge-status4.focus, body[class*="colorblind-"] .badge-status4:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(55,222,93,0.5);
}
body[class*="colorblind-"] .badge-status4:focus, body[class*="colorblind-"] .badge-status4:hover {
    color: #000 !important;
}
.font-status5 {
        color: #fff !important;
}
.font-status6 {
        color: #cad2d2 !important;
}
.font-status7 {
        color: #fff !important;
}
/* COLORBLIND STATUS7 */
body[class*="colorblind-"] .badge-status7 {
        color: #212529 !important;
        border-color: #37de5d;
        background-color: #fff;
}
body[class*="colorblind-"] .font-status7 {
        color: #fff !important;
}
body[class*="colorblind-"] .badge-status7.focus, body[class*="colorblind-"] .badge-status7:focus {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.5);
}
body[class*="colorblind-"] .badge-status7:focus, body[class*="colorblind-"] .badge-status7:hover {
    color: #212529 !important;
        border-color: #1ec544;
}
.font-status9 {
        color: #e7f0f0 !important;
}
.divintdwithtwolinesmax {
    width: 75px;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
}
.twolinesmax {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
}
table[summary="list_of_modules"] .fa-cog {
    font-size: 1.5em;
}
.linkedcol-element {
    min-width: 100px;
}
.img-skinthumb {
    width: 160px;
    height: 100px;
}
.pictowarning {
    /* vertical-align: text-bottom; */
    /* color: #a37c0d; */
}
.pictoerror {
    color: #9f4705;
}
.pictomodule {
    width: 14px;
}
/*
 * BTN LINK
 */
.btn-link{
    margin-right: 5px;
    border: 1px solid #ddd;
    color: #333;
    padding: 5px 10px;
    border-radius:1em;
    text-decoration: none !important;
}
.btn-link:hover{
    background-color: #ddd;
    border: 1px solid #ddd;
}
/* rule to reduce top menu - 2nd reduction: Reduce width of top menu icons again */
@media only screen and (max-width: 751px)   /* reduction 2 */
{
    .btnTitle, a.btnTitle {
        display: inline-block;
        padding: 4px 4px 4px 4px;
        min-width: unset;
    }
}
.imgforviewmode {
    color: #aaa;
}
div.pagination li:first-child a.btnTitle{
    margin-left: 10px;
}
.noborderspacing {
    border-spacing: 0;
}
.confirmquestions .tagtr .tagtd:not(:first-child)  { padding-left: 10px; }
.confirmquestions { margin-top: 5px; }
.trforbreak td {
    font-weight: bold;
    border-bottom: 1pt solid black !important;
    /* background-color: #e9e4e6 !important; */
}
div.liste_titre {
    padding-left: 3px;
}
.shadow {
    -webkit-box-shadow: 2px 2px 5px #CCC !important;
    box-shadow: 2px 2px 5px #CCC !important;
}
.opened-dash-board-wrap {
    margin-bottom: 25px;
}
div.divphotoref > a > .photowithmargin {        /* Margin right for photo not inside a div.photoref frame only */
    margin-right: 15px;
}
table.table-fiche-title .col-title div.titre{
    line-height: 40px;
}
table.table-fiche-title {
    margin-bottom: 5px;
}
div.backgreypublicpayment { background-color: #f0f0f0; padding: 20px; border-bottom: 1px solid #ddd; }
.backgreypublicpayment a { color: #222 !important; }
.poweredbypublicpayment {
    float: right;
    top: 8px;
    right: 8px;
    position: absolute;
    font-size: 0.8em;
    color: #222;
    opacity: 0.3;
}
.bordertransp {
    background-color: transparent;
    background-image: none;
    border: none;
    font-weight: normal;
}
.websitebar input#previewpageurl {
    line-height: 1em;
}
.treeview .hover { color: rgb(10, 20, 100) !important; text-decoration: underline !important; }
#comment .comment-edit {
    width: 100px;
    text-align:center;
    vertical-align:middle;
}
#comment .comment-edit:hover {
    background:rgba(0,184,148,0.8);
}
dd.dropdowndd ul li {
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}
.searchpage .tagtr .tagtd {
    padding-bottom: 3px;
}
.searchpage .tagtr .tagtd .button {
    background: unset;
    border: unset;
}
    .dropdown-toggle{
    text-decoration: none !important;
}
.dropdown-toggle::after {
    /* font part */
    font-family: "Font Awesome 5 Free";
    font-size: 0.7em;
    font-weight: 900;
    font-style: normal;
    font-variant: normal;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
    text-align:center;
    text-decoration:none;
    margin:  auto 3px;
    display: inline-block;
    content: "\f078";
    -webkit-transition: -webkit-transform .2s ease-in-out;
    -ms-transition: -ms-transform .2s ease-in-out;
    transition: transform .2s ease-in-out;
}
.open>.dropdown-toggle::after {
    transform: rotate(180deg);
}
#topmenu-global-search-dropdown .dropdown-menu{
    width: 300px;
    max-width: 100%;
}
div#topmenu-global-search-dropdown, div#topmenu-bookmark-dropdown {
    line-height: 46px;
}
a.top-menu-dropdown-link {
    padding: 8px;
}
.dropdown-menu a.top-menu-dropdown-link {
    color: rgb(10, 20, 100) !important;
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
    box-shadow: none;
    display: block;
    margin: 5px 0px;
}
.dropdown-item {
    display: block !important;
    box-sizing: border-box;
    width: 100%;
    padding: .25rem 1.5rem .25rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529  !important;
    text-align: inherit;
    background-color: transparent;
    border: 0;
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
    box-shadow: none;
}
.dropdown-item::before {
    /* font part */
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    font-style: normal;
    font-variant: normal;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
    text-align:center;
    text-decoration:none;
    margin-right: 5px;
    display: inline-block;
    content: "\f0da";
    color: rgba(0,0,0,0.3);
}
.dropdown-item.active, .dropdown-item:hover, .dropdown-item:focus  {
    color: #FFFFFF !important;
    text-decoration: none;
    background: rgb(68,68,90);
}
/*
* SEARCH
*/
.dropdown-search-input {
    width: 100%;
    padding: 10px 35px 10px 20px;
    background-color: transparent;
    font-size: 14px;
    line-height: 16px;
    box-sizing: border-box;
    color: #575756;
    background-color: transparent;
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath d='M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3Cpath d='M0 0h24v24H0z' fill='none'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-size: 16px 16px;
    background-position: 95% center;
    border-radius: 50px;
    border: 1px solid #c4c4c2 !important;
    transition: all 250ms ease-in-out;
    backface-visibility: hidden;
    transform-style: preserve-3d;
}
.dropdown-search-input::placeholder {
    color: color(#575756 a(0.8));
    letter-spacing: 1.5px;
}
.hidden-search-result{
    display: none !important;
}
/*
* Component: Timeline
* -------------------
*/
.timeline {
    position: relative;
    margin: 0 0 30px 0;
    padding: 0;
    list-style: none;
}
.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #ddd;
    left: 31px;
    margin: 0;
    border-radius: 2px;
}
.timeline > li {
    position: relative;
    margin-right: 0;
    margin-bottom: 15px;
}
.timeline > li:before,
.timeline > li:after {
    content: " ";
    display: table;
}
.timeline > li:after {
    clear: both;
}
.timeline > li > .timeline-item {
    -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    box-shadow:  0 1px 3px rgba(0, 0, 0, 0.1);
    border:1px solid #d2d2d2;
    border-radius: 3px;
    margin-top: 0;
    background: #fff;
    color: #444;
    margin-left: 60px;
    margin-right: 0px;
    padding: 0;
    position: relative;
}
.timeline > li.timeline-code-ticket_msg_private  > .timeline-item {
        background: #fffbe5;
        border-color: #d0cfc0;
}
.timeline > li > .timeline-item > .time{
    color: #6f6f6f;
    float: right;
    padding: 10px;
    font-size: 12px;
}
.timeline > li > .timeline-item > .timeline-header-action{
    color: #6f6f6f;
    float: right;
    padding: 7px;
    font-size: 12px;
}
a.timeline-btn:link,
a.timeline-btn:visited,
a.timeline-btn:hover,
a.timeline-btn:active
{
    display: inline-block;
    margin-bottom: 0;
    font-weight: 400;
    border-radius: 0;
    box-shadow: none;
    padding: 1px 5px;
    font-size: 12px;
    line-height: 1.5;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    touch-action: manipulation;
    cursor: pointer;
    user-select: none;
    background-image: none;
    text-decoration: none;
    background-color: #f4f4f4;
    color: #444;
    border: 1px solid #ddd;
}
a.timeline-btn:hover
{
    background-color: #e7e7e7;
    color: #333;
    border-color: #adadad;;
}
.timeline > li > .timeline-item > .timeline-header {
    margin: 0;
    color: #333;
    border-bottom: 1px solid #f4f4f4;
    padding: 10px;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.1;
}
.timeline > li > .timeline-item > .timeline-footer {
    border-top: 1px solid #f4f4f4;
}
.timeline > li.timeline-code-ticket_msg_private  > .timeline-item > .timeline-header, .timeline > li.timeline-code-ticket_msg_private  > .timeline-item > .timeline-footer {
    border-color: #ecebda;
}
.timeline > li > .timeline-item > .timeline-header > a {
    font-weight: 600;
}
.timeline > li > .timeline-item > .timeline-body,
.timeline > li > .timeline-item > .timeline-footer {
    padding: 10px;
}
.timeline > li > .fa,
.timeline > li > .glyphicon,
.timeline > li > .ion {
    width: 30px;
    height: 30px;
    font-size: 15px;
    line-height: 30px;
    position: absolute;
    color: #666;
    background: #d2d6de;
    border-radius: 50%;
    text-align: center;
    left: 18px;
    top: 0;
}
.timeline > .time-label > span {
    font-weight: 600;
    padding: 5px;
    display: inline-block;
    background-color: #fff;
    border-radius: 4px;
}
.timeline-inverse > li > .timeline-item {
    background: #f0f0f0;
    border: 1px solid #ddd;
    -webkit-box-shadow: none;
    box-shadow: none;
}
.timeline-inverse > li > .timeline-item > .timeline-header {
    border-bottom-color: #ddd;
}
.timeline-icon-todo,
.timeline-icon-in-progress,
.timeline-icon-done{
    color: #fff !important;
}
.timeline-icon-not-applicble{
    color: #000;
    background-color: #f7f7f7;
}
.timeline-icon-todo{
    background-color: #dd4b39 !important;
}
.timeline-icon-in-progress{
    background-color: #00c0ef !important;
}
.timeline-icon-done{
    background-color: #00a65a !important;
}
.timeline-badge-date{
    background-color: #0073b7 !important;
    color: #fff !important;
}
.timeline-documents-container{
}
.timeline-documents{
    margin-right: 5px;
}
div.pagination li:last-child a *:hover {
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
    padding-top:0 !important;
}
div.pagination li a,div.pagination li a:hover{
    padding: 4px !important;
}
div.pagination li a span.btnTitle-icon{
    padding-right: 4px !important;
}
.pull-right {
    float: right!important;
}
.pull-left {
    float: left!important;
}
/* Force values for small screen 767 */
@media only screen and (max-width: 767px)
{
    div.refidno {
        font-size: 0.86em !important;
    }
}
/* Force values for small screen 570 */
@media only screen and (max-width: 570px)
{
    div.refidno {
        font-size: 0.86em !important;
    }
}
div.refidpadding  {
    padding-top: 3px;
}
div.refid  {
    font-weight: bold;
    color: rgb(0,113,121);
    font-size: 1.2em;
}
div.refidno  {
    padding-top: 3px;
    font-weight: normal;
    color: #444;
    font-size: 0.86em;
    line-height: 21px;
}
div.refidno form {
    display: inline-block;
}
div.pagination li a:hover, div.pagination li span:hover, div.pagination li a:focus, div.pagination li span:focus{
    padding-top: 0px !important;
}
/* * * * * * * * * * * * * * * * * * * END Version 11 of Dolibarr * */

/* * * * * * * * * * * * * * * * * * * CSS FOR A CLIENT USE TAKEPOS MODULE * */
.container > .row1 button.actionbutton, .container > .row1 button.calcbutton, .container > .row1 button.calcbutton2 {
    margin: initial;
    border-width: 1px;
    border-style: outset;
    border-color: buttonface;
    border-image: initial;
}
.container > .row1 .div3 input[onkeyup="Search2();"] {
    font-size: 1em !important;
}
/* * * * * * * * * * * * * * * * * * * END CSS FOR A CLIENT USE TAKEPOS MODULE * */


/* css version 11 by Imane*/

.menulogocontainer{
    margin-left: 11px;
    margin-right: 9px;
    padding: 0;
    height: 32px;
    /* width: 100px; */
    max-width: 100px;
    vertical-align: middle;
}
.menulogocontainer img.mycompany{
    object-fit: contain;
    width: inherit;
    height: inherit;
}

/* End css */

/*02/06/2020*/
div#tmenu_tooltip .tmenudiv li#mainmenutd_companylogo img{
     max-height: 26px;
}

.cke_chrome {
    visibility: visible !important;
}

img.userphoto {
    border-radius: 0.72em;
    width: 1.4em;
    height: 1.4em;
    background-size: contain;
    vertical-align: middle;
}
<?php

if($dolvs >= 12){
    require __DIR__.'/style_12-0-0.css.php';
}


if (is_object($db)) $db->close();
?>
