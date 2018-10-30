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
 * \file    css/requestmanager_timeline.css.php
 * \ingroup requestmanager
 * \brief   CSS for the module Request Manager Time Line Messages.
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

header('Content-Type: text/css');

?>

#timeline-container {
  font-size: 14px;
  overflow-x: hidden;
}
#timeline-container #timeline-wrapper .timeline-top {
  text-align: center;
}
#timeline-container blockquote {
  font-size: 14px;
}
#timeline-container #timeline-wrapper .timeline-top .top-day {
  display: inline-block;
  padding: 10px;
  border-radius: 40px;
  background-color: #03a9f4;
  color: #fff;
  text-align: center;
  font-size: 2.8rem;
}
#timeline-container #timeline-wrapper .timeline-top .top-day > span {
  font-size: 3rem;
}
#timeline-container #timeline-wrapper .timeline-block {
  width: 100%;
  height: auto;
  position: relative;
}
#timeline-container #timeline-wrapper .timeline-block:after {
  content: "";
  position: absolute;
  width: 4px;
  height: 100%;
  background-color: #03a9f4;
  left: 50%;
  top: 0;
  margin-left: -2px;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .l-event {
  float: left;
  position: relative;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .l-event .thumb {
  right: 0;
  -webkit-transform: translate(50%, -50%);
          transform: translate(50%, -50%);
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .l-event:before, #timeline-container #timeline-wrapper .timeline-block .timeline-events .l-event:after {
  position: absolute;
  content: '';
  color: #333;
  width: 40px;
  height: 40px;
  background: #fff;
  -webkit-transform: translate(50%, -50%);
          transform: translate(50%, -50%);
  border-radius: 20px;
  z-index: 4;
  top: 50%;
  right: 0;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .l-event:after {
  width: 60px;
  height: 60px;
  z-index: 3;
  border-radius: 30px;
  background: #03a9f4;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .l-event .event-body:before {
  position: absolute;
  content: '';
  color: #333;
  width: 16px;
  height: 16px;
  background: #fff;
  border-top: 1px solid #eee;
  border-right: 1px solid #eee;
  -webkit-transform: rotate(45deg) translate(-50%, -50%);
          transform: rotate(45deg) translate(-50%, -50%);
  border-radius: 3px;
  top: 50%;
  right: -8px;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .r-event {
  float: right;
  position: relative;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .r-event .thumb {
  left: 0;
  -webkit-transform: translate(-50%, -50%);
          transform: translate(-50%, -50%);
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .r-event:before, #timeline-container #timeline-wrapper .timeline-block .timeline-events .r-event:after {
  position: absolute;
  content: '';
  color: #333;
  width: 40px;
  height: 40px;
  background: #fff;
  color: #333;
  -webkit-transform: translate(-50%, -50%);
          transform: translate(-50%, -50%);
  border-radius: 20px;
  top: 50%;
  left: 0px;
  z-index: 4;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .r-event:after {
  width: 60px;
  height: 60px;
  z-index: 3;
  border-radius: 30px;
  background: #03a9f4;
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .r-event .event-body {
  float: right;
  -webkit-transform: perspective(1000px) rotateY(-4deg);
          transform: perspective(1000px) rotateY(-4deg);
}
#timeline-container #timeline-wrapper .timeline-block .timeline-events .r-event .event-body:before {
  position: absolute;
  content: '';
  color: #333;
  width: 16px;
  height: 16px;
  background: #fff;
  border-bottom: 1px solid #eee;
  border-left: 1px solid #eee;
  -webkit-transform: rotate(45deg) translate(-50%, -50%);
          transform: rotate(45deg) translate(-50%, -50%);
  border-radius: 3px;
  top: 50%;
  left: -8px;
}
#timeline-container #timeline-wrapper .timeline-block .event {
  min-height: 100px;
  background: none;
  position: relative;
  box-shadow: 0px 40px 14px -35px #ccc,0px -40px 14px -35px #ccc;
  margin-top: 20px;
}
#timeline-container #timeline-wrapper .timeline-block .event .thumb {
  font-size: 30px;
  position: absolute;
  top: 50%;
  color: #333;
  z-index: 5;
}
#timeline-container #timeline-wrapper .timeline-block .event .event-body {
  background-color: #fff;
  min-height: 100px;
  border-radius: 5px;
  padding: 5px 10px;
  width: 90%;
  position: relative;
  border: 1px solid #eee;
}
#timeline-container #timeline-wrapper .timeline-block .event .event-body .person-image {
  display: inline-block;
  text-align: left;
  height: 100%;
  margin-right: 10px;
  padding-right: 10px;
  border-right: 1px dashed #eee;
}
#timeline-container #timeline-wrapper .timeline-block .event .event-body .person-image img {
  max-height: 97px;
  height: 100%;
}
@media (max-width: 768px) {
  #timeline-container #timeline-wrapper .timeline-top {
    text-align: right;
    padding-right: 20%;
  }
  #timeline-container .timeline-block {
    width: 100%;
    height: 100vh;
    position: relative;
    background-color: #eee;
  }
  #timeline-container .timeline-block:after {
    content: "";
    position: absolute;
    height: 100%;
    background-color: #333;
    left: 5%;
    top: 0;
    margin-left: -3px;
    display: none;
  }
  #timeline-container .timeline-block .r-event .event-body:after {
    content: '';
    position: absolute;
    top: 0;
    width: 6px;
    height: 100%;
    background-color: #03a9f4;
    left: -30%;
    border-radius: 3px;
    box-shadow: 1px 1px 1px #fff;
  }
  #timeline-container .timeline-block .l-event .event-body:after {
    content: '';
    position: absolute;
    top: 0;
    width: 6px;
    height: 100%;
    background-color: #03a9f4;
    right: -30%;
    border-radius: 3px;
    box-shadow: -1px 1px 1px #fff;
  }
}
@-webkit-keyframes light {
  to {
    color: #111;
  }
}
@keyframes light {
  to {
    color: #111;
  }
}
