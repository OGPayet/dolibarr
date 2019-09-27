<?php
/* Copyright (C) 2018 SuperAdmin
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
 * \file    infoextranet/css/infoextranet.css.php
 * \ingroup infoextranet
 * \brief   CSS file for module InfoExtranet.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/../main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

session_cache_limiter(FALSE);

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
    $user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

.mDiv {
	/*background-color: darkgoldenrod;*/
}

.mDivBegin {
    border-top: 1px solid;
}

.mDivRow {
    /*background-color: green;*/
    padding: 5px 2px 5px 0px;
    width: 100%;
    border-bottom: 1px solid #ddd;
}

.mDivTd {
    /*background-color: red;*/
    width: 32%;
    margin-right: 1%;
    display: inline-block;
}

.mDivLogoLink {
    width: 24%;
    margin-right: 1%;
    display: inline-block;
}

.mDivLogoLink .mTitle {
    display: block;
    text-align: center;
    width: 100%;
    margin-bottom: 2%;
    margin-top: 2%;
    font-weight: bold;
}

.mDivLogoLink .mInput {
    display: block;
}

div.disabled, img.disabled {
    opacity: 0.3;
}

a.disabled {
    pointer-events: none;
    cursor: not-allowed;
}

.mDivLeft, .mDivRight {
    width: 49%;
    margin-right: 1%;
}

.mDivLeft {
    float: left;
}

.mDivRight {
    float: right;
}

.mTitle {
    display: inline-block;
    margin-right: 5%;
    width: 50%;
}

.mInput {
    display: inline-block;
    text-align: center;
}

.mTextDivRow {
    text-align: center;
    width: 100%;
}

.mTextDivRow .mTitle {
    padding: 5px 0px 5px 0px;
    margin-right: 0px;
    font-weight: bold;
}

.mTextDivRow .mInput {
    text-align: left;
    display: block;
}

.mPair {
    background-color: #F6F6F6;
}

.badgeSoc {
    display: inline-block;
    min-width: 10px;
    padding: 2px 5px;
    font-size: 15px;
    font-weight: 700;
    line-height: 1em;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    background-color: #777;
}

.table-thirdparty {
    table-layout: fixed ;
    width: 100% ;
}





.select2-container {
    box-sizing: border-box;
    display: inline-block;
    margin: 0;
    position: relative;
    vertical-align: middle;
    width: 200px !important;
}
.select2-container .select2-selection--single {
    box-sizing: border-box;
    cursor: pointer;
    display: block;
    height: 28px;
    user-select: none;
    -webkit-user-select: none;
    width: 200px;
}
.select2-container .select2-selection--single .select2-selection__rendered {
    display: block;
    padding-left: 8px;
    padding-right: 20px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap; }
.select2-container .select2-selection--single .select2-selection__clear {
    position: relative; }
.select2-container[dir="rtl"] .select2-selection--single .select2-selection__rendered {
    padding-right: 8px;
    padding-left: 20px; }
.select2-container .select2-selection--multiple {
    box-sizing: border-box;
    cursor: pointer;
    display: block;
    min-height: 32px;
    user-select: none;
    -webkit-user-select: none; }
.select2-container .select2-selection--multiple .select2-selection__rendered {
    display: inline-block;
    overflow: hidden;
    padding-left: 8px;
    text-overflow: ellipsis;
    white-space: nowrap; }
.select2-container .select2-search--inline {
    float: left; }
.select2-container .select2-search--inline .select2-search__field {
    box-sizing: border-box;
    border: none;
    font-size: 100%;
    margin-top: 5px;
    padding: 0; }
.select2-container .select2-search--inline .select2-search__field::-webkit-search-cancel-button {
    -webkit-appearance: none; }

.select2-dropdown {
    background-color: white;
    border: 1px solid #aaa;
    border-radius: 4px;
    box-sizing: border-box;
    display: block;
    position: absolute;
    left: -100000px;
    width: 100%;
    z-index: 1051; }

.select2-results {
    display: block; }

.select2-results__options {
    list-style: none;
    margin: 0;
    padding: 0; }

.select2-results__option {
    padding: 6px;
    user-select: none;
    -webkit-user-select: none; }
.select2-results__option[aria-selected] {
    cursor: pointer; }

.select2-container--open .select2-dropdown {
    width: 200px;
    left: 0; }

.select2-container--open .select2-dropdown--above {
    border-bottom: none;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0; }

.select2-container--open .select2-dropdown--below {
    border-top: none;
    border-top-left-radius: 0;
    border-top-right-radius: 0; }

.select2-search--dropdown {
    display: block;
    padding: 4px; }
.select2-search--dropdown .select2-search__field {
    padding: 4px;
    width: 100%;
    box-sizing: border-box; }
.select2-search--dropdown .select2-search__field::-webkit-search-cancel-button {
    -webkit-appearance: none; }
.select2-search--dropdown.select2-search--hide {
    display: none; }

.select2-close-mask {
    border: 0;
    margin: 0;
    padding: 0;
    display: block;
    position: fixed;
    left: 0;
    top: 0;
    min-height: 100%;
    min-width: 100%;
    height: auto;
    width: auto;
    opacity: 0;
    z-index: 99;
    background-color: #fff;
    filter: alpha(opacity=0); }

.select2-hidden-accessible {
    border: 0 !important;
    clip: rect(0 0 0 0) !important;
    height: 1px !important;
    margin: -1px !important;
    overflow: hidden !important;
    padding: 0 !important;
    position: absolute !important;
    width: 200px !important;
}

.select2-container--default .select2-selection--single {
    background-color: #fff;
    border: 1px solid #aaa;
    border-radius: 4px;
    width: 200px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #444;
    line-height: 28px; }
.select2-container--default .select2-selection--single .select2-selection__clear {
    cursor: pointer;
    float: right;
    font-weight: bold; }
.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #999; }
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 26px;
    position: absolute;
    top: 1px;
    right: 1px;
    width: 20px; }
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #888 transparent transparent transparent;
    border-style: solid;
    border-width: 5px 4px 0 4px;
    height: 0;
    left: 50%;
    margin-left: -4px;
    margin-top: -2px;
    position: absolute;
    top: 50%;
    width: 0; }

.select2-container--default[dir="rtl"] .select2-selection--single .select2-selection__clear {
    float: left; }

.select2-container--default[dir="rtl"] .select2-selection--single .select2-selection__arrow {
    left: 1px;
    right: auto; }

.select2-container--default.select2-container--disabled .select2-selection--single {
    background-color: #eee;
    cursor: default; }
.select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__clear {
    display: none; }

.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
    border-color: transparent transparent #888 transparent;
    border-width: 0 4px 5px 4px; }

.select2-container--default .select2-selection--multiple {
    background-color: white;
    border: 1px solid #aaa;
    border-radius: 4px;
    cursor: text; }
.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    box-sizing: border-box;
    list-style: none;
    margin: 0;
    padding: 0 5px;
    width: 100%; }
.select2-container--default .select2-selection--multiple .select2-selection__rendered li {
    list-style: none; }
.select2-container--default .select2-selection--multiple .select2-selection__placeholder {
    color: #999;
    margin-top: 5px;
    float: left; }
.select2-container--default .select2-selection--multiple .select2-selection__clear {
    cursor: pointer;
    float: right;
    font-weight: bold;
    margin-top: 5px;
    margin-right: 10px; }
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #e4e4e4;
    border: 1px solid #aaa;
    border-radius: 4px;
    cursor: default;
    float: left;
    margin-right: 5px;
    margin-top: 5px;
    padding: 0 5px; }
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #999;
    cursor: pointer;
    display: inline-block;
    font-weight: bold;
    margin-right: 2px; }
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #333; }

.select2-container--default[dir="rtl"] .select2-selection--multiple .select2-selection__choice, .select2-container--default[dir="rtl"] .select2-selection--multiple .select2-selection__placeholder, .select2-container--default[dir="rtl"] .select2-selection--multiple .select2-search--inline {
    float: right; }

.select2-container--default[dir="rtl"] .select2-selection--multiple .select2-selection__choice {
    margin-left: 5px;
    margin-right: auto; }

.select2-container--default[dir="rtl"] .select2-selection--multiple .select2-selection__choice__remove {
    margin-left: 2px;
    margin-right: auto; }

.select2-container--default.select2-container--focus .select2-selection--multiple {
    width: 200px;
    border: solid black 1px;
    outline: 0; }

.select2-container--default.select2-container--disabled .select2-selection--multiple {
    background-color: #eee;
    cursor: default; }

.select2-container--default.select2-container--disabled .select2-selection__choice__remove {
    display: none; }

.select2-container--default.select2-container--open.select2-container--above .select2-selection--single, .select2-container--default.select2-container--open.select2-container--above .select2-selection--multiple {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    width: 200px;

}

.select2-container--default.select2-container--open.select2-container--below .select2-selection--single, .select2-container--default.select2-container--open.select2-container--below .select2-selection--multiple {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0; }

.select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1px solid #aaa; }

.select2-container--default .select2-search--inline .select2-search__field {
    background: transparent;
    border: none;
    outline: 0;
    box-shadow: none;
    -webkit-appearance: textfield; }

.select2-container--default .select2-results > .select2-results__options {
    max-height: 200px;
    overflow-y: auto; }

.select2-container--default .select2-results__option[role=group] {
    padding: 0; }

.select2-container--default .select2-results__option[aria-disabled=true] {
    color: #999; }

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #ddd; }

.select2-container--default .select2-results__option .select2-results__option {
    padding-left: 1em; }
.select2-container--default .select2-results__option .select2-results__option .select2-results__group {
    padding-left: 0; }
.select2-container--default .select2-results__option .select2-results__option .select2-results__option {
    margin-left: -1em;
    padding-left: 2em; }
.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option {
    margin-left: -2em;
    padding-left: 3em; }
.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option {
    margin-left: -3em;
    padding-left: 4em; }
.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option {
    margin-left: -4em;
    padding-left: 5em; }
.select2-container--default .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option .select2-results__option {
    margin-left: -5em;
    padding-left: 6em; }

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #5897fb;
    color: white; }

.select2-container--default .select2-results__group {
    cursor: default;
    display: block;
    padding: 6px; }

.select2-container--classic .select2-selection--single {
    background-color: #f7f7f7;
    border: 1px solid #aaa;
    border-radius: 4px;
    outline: 0;
    background-image: -webkit-linear-gradient(top, white 50%, #eeeeee 100%);
    background-image: -o-linear-gradient(top, white 50%, #eeeeee 100%);
    background-image: linear-gradient(to bottom, white 50%, #eeeeee 100%);
    background-repeat: repeat-x;
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFFFF', endColorstr='#FFEEEEEE', GradientType=0); }
.select2-container--classic .select2-selection--single:focus {
    border: 1px solid #5897fb; }
.select2-container--classic .select2-selection--single .select2-selection__rendered {
    color: #444;
    line-height: 28px; }
.select2-container--classic .select2-selection--single .select2-selection__clear {
    cursor: pointer;
    float: right;
    font-weight: bold;
    margin-right: 10px; }
.select2-container--classic .select2-selection--single .select2-selection__placeholder {
    color: #999; }
.select2-container--classic .select2-selection--single .select2-selection__arrow {
    background-color: #ddd;
    border: none;
    border-left: 1px solid #aaa;
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
    height: 26px;
    position: absolute;
    top: 1px;
    right: 1px;
    width: 20px;
    background-image: -webkit-linear-gradient(top, #eeeeee 50%, #cccccc 100%);
    background-image: -o-linear-gradient(top, #eeeeee 50%, #cccccc 100%);
    background-image: linear-gradient(to bottom, #eeeeee 50%, #cccccc 100%);
    background-repeat: repeat-x;
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFEEEEEE', endColorstr='#FFCCCCCC', GradientType=0); }
.select2-container--classic .select2-selection--single .select2-selection__arrow b {
    border-color: #888 transparent transparent transparent;
    border-style: solid;
    border-width: 5px 4px 0 4px;
    height: 0;
    left: 50%;
    margin-left: -4px;
    margin-top: -2px;
    position: absolute;
    top: 50%;
    width: 0; }

.select2-container--classic[dir="rtl"] .select2-selection--single .select2-selection__clear {
    float: left; }

.select2-container--classic[dir="rtl"] .select2-selection--single .select2-selection__arrow {
    border: none;
    border-right: 1px solid #aaa;
    border-radius: 0;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
    left: 1px;
    right: auto; }

.select2-container--classic.select2-container--open .select2-selection--single {
    border: 1px solid #5897fb; }
.select2-container--classic.select2-container--open .select2-selection--single .select2-selection__arrow {
    background: transparent;
    border: none; }
.select2-container--classic.select2-container--open .select2-selection--single .select2-selection__arrow b {
    border-color: transparent transparent #888 transparent;
    border-width: 0 4px 5px 4px; }

.select2-container--classic.select2-container--open.select2-container--above .select2-selection--single {
    border-top: none;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    background-image: -webkit-linear-gradient(top, white 0%, #eeeeee 50%);
    background-image: -o-linear-gradient(top, white 0%, #eeeeee 50%);
    background-image: linear-gradient(to bottom, white 0%, #eeeeee 50%);
    background-repeat: repeat-x;
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFFFF', endColorstr='#FFEEEEEE', GradientType=0); }

.select2-container--classic.select2-container--open.select2-container--below .select2-selection--single {
    border-bottom: none;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    background-image: -webkit-linear-gradient(top, #eeeeee 50%, white 100%);
    background-image: -o-linear-gradient(top, #eeeeee 50%, white 100%);
    background-image: linear-gradient(to bottom, #eeeeee 50%, white 100%);
    background-repeat: repeat-x;
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFEEEEEE', endColorstr='#FFFFFFFF', GradientType=0); }

.select2-container--classic .select2-selection--multiple {
    background-color: white;
    border: 1px solid #aaa;
    border-radius: 4px;
    cursor: text;
    outline: 0; }
.select2-container--classic .select2-selection--multiple:focus {
    border: 1px solid #5897fb; }
.select2-container--classic .select2-selection--multiple .select2-selection__rendered {
    list-style: none;
    margin: 0;
    padding: 0 5px;
    width: 200px
}
.select2-container--classic .select2-selection--multiple .select2-selection__clear {
    display: none; }
.select2-container--classic .select2-selection--multiple .select2-selection__choice {
    background-color: #e4e4e4;
    border: 1px solid #aaa;
    border-radius: 4px;
    cursor: default;
    float: left;
    margin-right: 5px;
    margin-top: 5px;
    padding: 0 5px; }
.select2-container--classic .select2-selection--multiple .select2-selection__choice__remove {
    color: #888;
    cursor: pointer;
    display: inline-block;
    font-weight: bold;
    margin-right: 2px; }
.select2-container--classic .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #555; }

.select2-container--classic[dir="rtl"] .select2-selection--multiple .select2-selection__choice {
    float: right; }

.select2-container--classic[dir="rtl"] .select2-selection--multiple .select2-selection__choice {
    margin-left: 5px;
    margin-right: auto; }

.select2-container--classic[dir="rtl"] .select2-selection--multiple .select2-selection__choice__remove {
    margin-left: 2px;
    margin-right: auto; }

.select2-container--classic.select2-container--open .select2-selection--multiple {
    border: 1px solid #5897fb; }

.select2-container--classic.select2-container--open.select2-container--above .select2-selection--multiple {
    border-top: none;
    border-top-left-radius: 0;
    border-top-right-radius: 0; }

.select2-container--classic.select2-container--open.select2-container--below .select2-selection--multiple {
    border-bottom: none;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0; }

.select2-container--classic .select2-search--dropdown .select2-search__field {
    border: 1px solid #aaa;
    outline: 0; }

.select2-container--classic .select2-search--inline .select2-search__field {
    outline: 0;
    box-shadow: none; }

.select2-container--classic .select2-dropdown {
    background-color: white;
    border: 1px solid transparent; }

.select2-container--classic .select2-dropdown--above {
    border-bottom: none; }

.select2-container--classic .select2-dropdown--below {
    border-top: none; }

.select2-container--classic .select2-results > .select2-results__options {
    max-height: 200px;
    overflow-y: auto; }

.select2-container--classic .select2-results__option[role=group] {
    padding: 0; }

.select2-container--classic .select2-results__option[aria-disabled=true] {
    color: grey; }

.select2-container--classic .select2-results__option--highlighted[aria-selected] {
    background-color: #3875d7;
    color: white; }

.select2-container--classic .select2-results__group {
    cursor: default;
    display: block;
    padding: 6px; }

.select2-container--classic.select2-container--open .select2-dropdown {
    border-color: #5897fb; }



.butList {
    display: inline-block;
    padding: 0.4em 0.7em;
    margin: 0em 0.9em;
    line-height: 20px;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
    color: #ffffff !important;
    border: 1px solid #bbbbbb;
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
    background-color: #006dcc;
    background-image: linear-gradient(to bottom, #0088cc, #0044cc);
    background-repeat: repeat-x;
    border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
    width: 150px;
}
.butList:hover {
    color: #8CC3BE;
}