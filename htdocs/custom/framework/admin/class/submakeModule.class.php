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

Class submakeModule
{

    public function __construct(PageConfigSubModule $Master)
    {

        $this->Master = $Master;
    }

    public function PrepareContext()
    {
        global $langs, $conf, $html;
    }

    /**
      @brief constructor
     */
    public function DisplayPage()
    {
        global $langs, $conf, $html, $list, $db;

        $module = GETPOST('module', 'alpha');
        $files  = array_keys(GETPOST('files', 'array'));

        $workingDir = DOL_DOCUMENT_ROOT.'/custom/';
        switch (TRUE) {
            case $files != array():
//                 error_reporting(E_ALL);
                include_once ODTPHP_PATHTOPCLZIP.'/pclzip.lib.php';
                //dol_include_once('/framework/class/PclZip/pclzip.lib.php');
                $version = '';

                //try to open class to read version
                $coreFiles = glob($workingDir.$module.'/core/modules/*');
                $coreFiles = preg_grep('/mod'.$module.'\.class\.php/i', $coreFiles); // case insensitive
                foreach ($coreFiles as $coreFile) {
                    $className = basename($coreFile, '.class.php');
                    try {
                        if (!class_exists($className)) {
                            include_once $coreFile;
                        }
                        $moduleClass  = new $className($db);
                        $version .= '-'.$moduleClass->getVersion();
                    } catch (Exception $ex) {
                        print 'WARNING, I WAS NOT ABLE TO RETRIEVE YOUR MODULE VERSION ....';
                    }
                }
                $outputFile = DOL_DOCUMENT_ROOT.'/module_'.$module.$version.'.zip';
                //cleanup & make zip
                unlink($outputFile);
                $archive = new PclZip($outputFile);
                foreach ($files as $file) {
                    $archive->add($file, PCLZIP_OPT_REMOVE_PATH, DOL_DOCUMENT_ROOT, PCLZIP_OPT_ADD_PATH, "htdocs");
                }
                print '<h1>'.$langs->trans('Step').' 3/3</h1>';
                print "<a class='butAction' href='".DOL_URL_ROOT."/module_$module$version.zip' target='_blank'>".$langs->trans('Download')." module_$module$version.zip</a>";
                break;
            case $module != '':
                if (!is_dir($workingDir.$module)) break;
                $buttons = '<div class="tabsAction"><input type="submit" value="'.$langs->trans('Packager').'" class="butAction" /></div>';
                print '<h1>'.$langs->trans('Step').' 2/3</h1>';
                print '<form action="'.$_SERVER["REQUEST_URI"].'" method="POST">'.$buttons;
                $files = $this->listFiles($workingDir.$module.'/*');
                foreach ($files as $file) {
                    $fileRelative = str_replace($workingDir, '', $file);
                    $checked      = 'checked';
                    if ($fileRelative == $module.'/langs/translate.php') $checked      = '';
                    print "<label><input name='files[$file]' type='checkbox' $checked />  $fileRelative </label><br/>";
                }
                print '<h2>'.$langs->trans('AddCoreFiles').'</h2>';
                print '<div style="height:300px;overflow:scroll; border:1px solid black;">';
                $allFiles=$this->listFiles(DOL_DOCUMENT_ROOT.'/*');
                foreach ($allFiles as $file) {
                    $fileRelative = str_replace(DOL_DOCUMENT_ROOT, '', $file);
                    print "<label><input name='files[$file]' type='checkbox' />  $fileRelative </label><br/>";
                }
                print '</div>'.$buttons.'</form>';
                break;
            default:
                print '<h1>'.$langs->trans('Step').' 1/3</h1>';
                print '<p>'.$langs->trans('FilesInCustom').'</p>';

                $files = glob($workingDir.'*');
                $i     = 0;
                foreach ($files as $file) {
                    if (is_dir($file)) {
                        $module = str_replace($workingDir, '', $file);
                        echo ' <a class="butAction" href="'.$_SERVER["REQUEST_URI"].'&module='.$module.'">'.$module.'</a>';
                    }
                }
                break;
        }
    }

    public function listFiles($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR) as $dir) {
            $files = array_merge($files, $this->listFiles($dir.'/'.basename($pattern), $flags));
        }
        $finalFiles = array();
        foreach ($files as $file) {
            if (!is_dir($file)) {
                $finalFiles[] = $file;
            }
        }
        asort($finalFiles);
        return $finalFiles;
    }
}
