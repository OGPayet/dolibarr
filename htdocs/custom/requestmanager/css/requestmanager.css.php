<?php
/* Copyright (C) 2018  Open-Dsi <support@open-dsi.fr>
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
 * \file    css/requestmanager.css.php
 * \ingroup requestmanager
 * \brief   CSS for the module Request Manager.
 *
 * Put detailed description here.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

header('Content-Type: text/css');

$myRequestUpdatedBlinkColor = !empty($conf->global->REQUESTMANAGER_MY_REQUEST_UPDATED_BLINK_COLOR) ? $conf->global->REQUESTMANAGER_MY_REQUEST_UPDATED_BLINK_COLOR : 'yellow';
$myRequestUpdatedLineColor = !empty($conf->global->REQUESTMANAGER_MY_REQUEST_UPDATED_LINE_COLOR) ? $conf->global->REQUESTMANAGER_MY_REQUEST_UPDATED_LINE_COLOR : 'yellow';
$chronometerBlinkColor = !empty($conf->global->REQUESTMANAGER_CHRONOMETER_BLINK_COLOR) ? $conf->global->REQUESTMANAGER_CHRONOMETER_BLINK_COLOR : 'red';

?>

.mainmenu.requestmanager {
	background-image: url('../img/requestmanager.png' );
}

.noMarginBottom {
  margin-bottom: 0px !important;
}

.tabsStatusAction {
  margin: 20px 0em 0em 0em;
}

.tabsStatusActionPrevious {
  width: 50%;
  display: inline-block;
  text-align: left;
}

.tabsStatusActionNext {
  width: 50%;
  display: inline-block;
  text-align: right;
}

.rm_my_request_updated_blink_color {
  background: <?php print $myRequestUpdatedBlinkColor ?>;
}

.rm_chronometer_blink_color {
  background: <?php print $chronometerBlinkColor ?> !important;
}

.rm_my_request_updated_line_color {
  background: <?php print $myRequestUpdatedLineColor ?>;
}

#mainmenutd_requestmanager_my_request_updated a {
  -webkit-transition: background 0.8s ease-in-out;
  -ms-transition:     background 0.8s ease-in-out;
  transition:         background 0.8s ease-in-out;
}

#mainmenutd_requestmanager_chronometer a div.mainmenuaspan {
  font-size: 24px !important;
  font-weight: bolder !important;
}

div.event-content h5 span.right {
  text-align: right;
  float: right;
}