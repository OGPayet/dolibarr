<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *		\file       htdocs/theme/eldy/style.css.php
 *		\brief      File for CSS style sheet Eldy
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

$search_img_url = img_picto('', 'searchicon.png@quicklist', '', false, 1);
?>

.quicklist-dropdown {
  display: inline-block;
}

.quicklist-dropbtn {
  outline: none;
}

.quicklist-dropbtn.ql_owntheme_fix {
  margin: 3px 0 3px 10px !important;
  padding: 0 !important;
}

.quicklist-dropdown-content {
  margin-right: 24px;
  display: none;
  position: absolute;
  background-color: #f6f6f6;
  min-width: 230px;
  overflow: auto;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  right: 0;
  z-index: 1;
}
.quicklist-dropdown-content.show {display:block;}

.quicklist-action {
  border: 0;
  padding: 12px 16px;
  width: 50%;
  cursor: pointer;
  margin: 0 !important;
}
.quicklist-action:hover {background-color: #ddd}
#quicklistInput {
  display: block;
  box-sizing: border-box;
  background-image: url('<?php echo $search_img_url ?>');
  background-position: 14px 12px;
  background-repeat: no-repeat;
  font-size: 16px;
  padding: 14px 20px 12px 45px;
  border: none;
}
#quicklistElements {
  color: black;
  text-align: left;
}
#quicklistElements .item {
  padding: 12px 16px;
  display: block;
}
#quicklistElements .item.category {
  background-color: #ccc;
}
#quicklistElements .item.filter, #quicklistElements .item.filter span.right {
  text-decoration: none;
}
#quicklistElements .item.filter:hover {background-color: #ddd}
#quicklistElements .item.filter span {
  display: inline-block;
}
#quicklistElements .item.filter span.right {
  float: right;
}
#quicklistElements .item.filter span.right img {
  margin: 0 0 0 4px;
  vertical-align: middle;
}

.quicklist-filter-list {
  display: inline-block;
  margin: 0;
  padding: 0 0 10px 0;
  text-align: left;
}
.quicklist-filter-list .button {
  margin-left: 5px !important;
  margin-right: 0 !important;
}
