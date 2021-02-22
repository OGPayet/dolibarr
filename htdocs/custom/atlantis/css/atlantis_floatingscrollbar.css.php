<?php
/* Copyright (C) 2021 Alexis LAURIER <contact@alexislaurier.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    atlantis/css/atlantis_select2sortable.css.php
 * \ingroup atlantis
 * \brief   CSS file for module Atlantis.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1); // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

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
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

.spacious-container {
    overflow: auto;
    width: 100%;
}

.fl-scrolls{overflow:auto;position:fixed}.fl-scrolls div{overflow:hidden;pointer-events:none}.fl-scrolls div:before{content:"\A0"}.fl-scrolls,.fl-scrolls div{font-size:1px;line-height:0;margin:0;padding:0}.fl-scrolls-hidden div:before{content:"\A0\A0"}.fl-scrolls-viewport{position:relative}.fl-scrolls-body{overflow:auto}.fl-scrolls-viewport .fl-scrolls{position:absolute}.fl-scrolls-hoverable .fl-scrolls{opacity:0;transition:opacity .5s .3s}.fl-scrolls-hoverable:hover .fl-scrolls{opacity:1}.fl-scrolls:not([data-orientation]),.fl-scrolls[data-orientation=horizontal]{bottom:0;min-height:17px}.fl-scrolls:not([data-orientation]) div,.fl-scrolls[data-orientation=horizontal] div{height:1px}.fl-scrolls-hidden.fl-scrolls:not([data-orientation]),.fl-scrolls-hidden.fl-scrolls[data-orientation=horizontal]{bottom:9999px}.fl-scrolls-viewport .fl-scrolls:not([data-orientation]),.fl-scrolls-viewport .fl-scrolls[data-orientation=horizontal]{left:0}.fl-scrolls[data-orientation=vertical]{right:0;min-width:17px}.fl-scrolls[data-orientation=vertical] div{width:1px}.fl-scrolls-hidden.fl-scrolls[data-orientation=vertical]{right:9999px}.fl-scrolls-viewport .fl-scrolls[data-orientation=vertical]{top:0}

