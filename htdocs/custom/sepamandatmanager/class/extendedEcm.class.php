<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/digitalsignaturepeople.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       This file is a CRUD class file for DigitalSignaturePeople (Create/Read/Update/Delete)
 */

dol_include_once("/ecm/class/ecmfiles.class.php");

if (!class_exists("ExtendedEcm")) {
/**
 * Class for DigitalSignaturePeople
 */
    class ExtendedEcm extends EcmFiles
    {
        /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
        public static $extended_table_element = 'extended_ecm';

        /**
     * @var string Name of the model used to generate file
     */
        public $mask;

        /**
     * @var string Name of the element_type of the object which create this file
     */
        public $elementtype;

        /**
     * @var string Id of the object which create this file
     */
        public $fk_object;

        /**
     * @var string[] Errors
     */
        public $errors = array();

        /**
     * Load object in memory from the database
     *
     * @param  int    $id          Id object
     * @param  string $ref         Not used yet. Will contains a hash id from filename+filepath
     * @param  string $fullpath    Full path of file (relative path to document directory)
     * @return int                 <0 if KO, 0 if not found, >0 if OK
     */
        public function fetch($id, $ref = null, $fullpath = '', $hashoffile = '', $hashforshare = '', $src_object_type = '', $src_object_id = 0)
        {
            $result = parent::fetch($id, $ref, $fullpath);
            if ($result > 0) {
                $result = self::fetchAdditionalPropertyForInstance($this->db, $this);
            }
            return $result;
        }

        /**
     * Load object in memory from the database
     *
     * @param string $sortorder Sort Order
     * @param string $sortfield Sort field
     * @param int    $limit     offset limit
     * @param int    $offset    offset limit
     * @param array  $filter    filter array
     * @param string $filtermode filter mode (AND or OR)
     *
     * @return self[]|null
     */
        public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
        {
            dol_syslog(__METHOD__, LOG_DEBUG);

            $sql = 'SELECT';
            $sql .= ' t.rowid as rowid,';
            $sql .= " t.label as label,";
            $sql .= " t.entity as entity,";
            $sql .= " t.filename as filename,";
            $sql .= " t.filepath as filepath,";
            $sql .= " t.fullpath_orig as fullpath_orig,";
            $sql .= " t.description as description,";
            $sql .= " t.keywords as keywords,";
            $sql .= " t.cover as cover,";
            $sql .= " t.position as position,";
            $sql .= " t.gen_or_uploaded as gen_or_uploaded,";
            $sql .= " t.extraparams as extraparams,";
            $sql .= " t.date_c as date_c,";
            $sql .= " t.date_m as date_m,";
            $sql .= " t.fk_user_c as fk_user_c,";
            $sql .= " t.fk_user_m as fk_user_m,";
            $sql .= " t.acl as acl,";
            $sql .= " et.mask as mask,";
            $sql .= " et.elementtype as elementtype,";
            $sql .= " et.fk_object as fk_object";
            $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . self::$extended_table_element . ' as et ON (t.rowid = et.fk_ecm) ';

            // Manage filter
            $sqlwhere = array();
            if (count($filter) > 0) {
                foreach ($filter as $key => $value) {
                    $sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
                }
            }
            $sql .= ' WHERE 1 = 1';
            /* Fetching this table depends on filepath+filename, it must not depends on entity
            if (! empty($conf->multicompany->enabled)) {
            $sql .= " AND entity IN (" . getEntity('ecmfiles') . ")";
            }*/
            if (count($sqlwhere) > 0) {
                $sql .= ' AND ' . implode(' ' . $filtermode . ' ', $sqlwhere);
            }
            if (!empty($sortfield)) {
                $sql .= " ORDER BY " . $sortfield . " " . $sortorder;
            }
            if (!empty($limit)) {
                $sql .=  ' ' . $this->db->plimit($limit, $offset);
            }

            $result = array();

            $resql = $this->db->query($sql);
            if ($resql) {
                $num = $this->db->num_rows($resql);

                while ($obj = $this->db->fetch_object($resql)) {
                    $line = new self($this->db);

                    $line->id = $obj->rowid;

                    $line->label = $obj->label;
                    $line->entity = $obj->entity;
                    $line->filename = $obj->filename;
                    $line->filepath = $obj->filepath;
                    $line->fullpath_orig = $obj->fullpath_orig;
                    $line->description = $obj->description;
                    $line->keywords = $obj->keywords;
                    $line->cover = $obj->cover;
                    $line->position = $obj->position;
                    $line->gen_or_uploaded = $obj->gen_or_uploaded;
                    $line->extraparams = $obj->extraparams;
                    $line->date_c = $this->db->jdate($obj->date_c);
                    $line->date_m = $this->db->jdate($obj->date_m);
                    $line->fk_user_c = $obj->fk_user_c;
                    $line->fk_user_m = $obj->fk_user_m;
                    $line->acl = $obj->acl;
                    $line->mask = $obj->mask;
                    $line->elementtype = $obj->elementtype;
                    $line->fk_object = $obj->fk_object;
                    $result[] = $line;
                }
                $this->db->free($resql);

                return $result;
            } else {
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
                return -1;
            }
        }

        /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     *
     * @return int <0 if KO, Id of created object if OK
     */
        public function create(User $user, $notrigger = false)
        {
            $result = parent::create($user, $notrigger);
            if ($result > 0) {
                $result = self::saveAdditionalPropertyForInstance($this->db, $this);
            }
            return $result;
        }
        /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     *
     * @return int <0 if KO, >0 if OK
     */
        public function update(User $user, $notrigger = false)
        {
            $result =  parent::update($user, $notrigger);
            if ($result > 0) {
                $result = self::saveAdditionalPropertyForInstance($this->db, $this);
            }
            return $result;
        }
        /**
     * Delete object in database
     *
     * @param User $user      User that deletes
     * @param bool $notrigger false=launch triggers after, true=disable triggers
     *
     * @return int <0 if KO, >0 if OK
     */
        public function delete(User $user, $notrigger = false)
        {
            $result = parent::delete($user, $notrigger);
            if ($result > 0) {
                $result = self::deleteAdditionalProperty($this->db, $this->id);
            }
            return $result;
        }

        /**
     * Static function to fetch and add additional property to an instance
     * @param DoliDB $db Database instance to use
     * @param self|EcmfilesLine $instance instance on which add property values
     * @return int
     */
        public static function fetchAdditionalPropertyForInstance($db, &$instance)
        {
            $sql = "SELECT mask, elementtype, fk_object FROM " . MAIN_DB_PREFIX . self::$extended_table_element . " WHERE fk_ecm = " . $instance->id;
            $resql = $db->query($sql);
            if ($resql) {
                $result = 1;
                $numrows = $db->num_rows($resql);
                if ($numrows) {
                    $obj = $db->fetch_object($resql);
                    $instance->mask = $obj->mask;
                    $instance->fk_object = $obj->fk_object;
                    $instance->elementtype = $obj->elementtype;
                }
            } else {
                $result = -1;
                $instance->errors[] = $db->error();
            }
            return $result;
        }

        /**
     * Static function to save additional property
     * @param DoliDB $db Database instance to use
     * @param self|EcmfilesLines $instance instance on which save additionnal property
     * @return int
     */
        public static function saveAdditionalPropertyForInstance($db, &$instance)
        {
            self::deleteAdditionalProperty($db, $instance->id);
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . self::$extended_table_element . " (fk_ecm, mask, elementtype, fk_object) VALUES ";
            $values = array($instance->id, $instance->mask, $instance->elementtype, $instance->fk_object);
            $sqlValues = array();
            foreach ($values as $value) {
                $sqlValues[] = isset($value) ? '"' . $db->escape($value) . '"' : 'NULL';
            }
            $sql .= "(" . implode(',', $sqlValues) . ")";
            $resql = $instance->db->query($sql);
            if ($resql) {
                $result = 1;
            } else {
                $result = -1;
                $instance->errors[] = $instance->db->error();
            }
            return $result;
        }

        /**
     * Static function to delete additional property
     * @param DoliDB $db Database instance to use
     * @param int $id $id of the instance to delete
     * @return int
     */
        public static function deleteAdditionalProperty($db, $id)
        {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . self::$extended_table_element . " WHERE fk_ecm = " . $id;
            return !$db->query($sql) ? -1 : 1;
        }

        /**
     * Static function to clone additional property of ecm file
     * @param DoliDB $db Database instance to use
     * @param int $fromId id of metadata to clone
     * @param int $newId Id of metadata to be set
     * @return int
     */
        public static function cloneAdditionalProperty($db, $fromId, $newId)
        {
            $obj = new self($db);
            $obj->id = $fromId;
            self::fetchAdditionalPropertyForInstance($db, $obj);
            $obj->id = $newId;
            return self::saveAdditionalPropertyForInstance($db, $obj);
        }

        /**
     * Function to copy current file to a new location and update ecm database with original property
     * @param string $relativePath relative path of directory according to document folder
     * @param string $destinationFileName change filename if needed
     * @return self|null
     */
        public function copyFileTo($relativePath, $destinationFileName = null)
        {
            global $user;
            $copiedInstanceResult = null;
            if (!$destinationFileName) {
                $destinationFileName = $this->filename;
            }
            $absoluteDestinationDirectory = DOL_DATA_ROOT . '/' . $relativePath;
            $destinationFilePath = $absoluteDestinationDirectory . '/' . $destinationFileName;
            $sourceAbsoluteLinkedFilePath = $this->getFileFullPath();
            if (dol_copy($sourceAbsoluteLinkedFilePath, $destinationFilePath)  > 0) {
                //File have successfully been copied
                //We create ecm file instance of it
                $newEcm = new self($this->db);
                $newEcm->fetch(null, null, $relativePath . '/' . $destinationFileName);
                $newEcm->label = md5_file(dol_osencode($destinationFilePath));
                $newEcm->filepath = $relativePath;
                $newEcm->filename = $destinationFileName;
                $newEcm->fullpath_orig = $sourceAbsoluteLinkedFilePath;
                $newEcm->gen_or_uploaded = $this->gen_or_uploaded;
                $newEcm->mask = $this->mask;
                if ($newEcm->id > 0) {
                    $result = $newEcm->update($user);
                } else {
                    $result = $newEcm->create($user);
                }
                $this->errors = array_merge($this->errors, $newEcm->errors);
                $copiedInstanceResult = $result > 0 ? $newEcm : null;
            }
            return $copiedInstanceResult;
        }

        /**
     * Function to delete file
     * @return bool
     */
        public function deleteFile()
        {
            global $user;
            $result = $this->delete($user) > 0;
            if ($result) {
                $result = dol_delete_file($this->getFileFullPath());
            }
            return $result;
        }

        /**
     * Function to move file to a new directory
     * @param string $relativePath relative path of directory according to document folder
     * @param string $destinationFileName change filename if needed
     * @param User $user user requesting action
     * @return boolean
     */
        public function moveFileTo($relativePath, $destinationFileName, $user)
        {
            $result = false;
            if (!$destinationFileName) {
                $destinationFileName = $this->fileName;
            }
            if (dol_move($this->getFileFullPath(), DOL_DATA_ROOT . '/' . $relativePath . '/' . $destinationFileName, 0, 1, 0, 0)) {
                //we update database
                $this->filename = $destinationFileName;
                $this->filepath = $relativePath;
                $result = $this->update($user) > 0;
            }
            return $result;
        }

        /**
     * Function to rename file
     * @param string $newFileName new name for this file
     * @param User $user user requesting action
     * @return bool
     */
        public function renameFile($newFileName, $user)
        {
            if (!$newFileName) {
                return false;
            }
            return $this->moveFileTo($this->filepath, $newFileName, $user);
        }

        /**
     * Function to get relative directory according to document folder of a file, given its absolute path on system
     * @param string $absolutePathOfFile Absolute path on system of the file
     * @return string
     */
        public static function getRelativeDirectoryOfADirectory($absolutePathOfFile)
        {
            $rel_dir = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $absolutePathOfFile);
            $rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
            $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);
            return $rel_dir;
        }

        /**
     * Function to get relative directory according to document folder of a file, given its absolute path on system
     * @param string $absolutePathOfFile Absolute path on system of the file
     * @return string
     */
        public static function getRelativeDirectoryOfAFile($absolutePathOfFile)
        {
            $rel_filetorenameafter = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $absolutePathOfFile);
            $rel_dir = dirname($rel_filetorenameafter);
            $rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
            $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);
            return $rel_dir;
        }

        /**
     * Function to get proper full path of the linked file on disk
     * @return string|null
     */
        public function getFileFullPath()
        {
            $fileFullPath = DOL_DATA_ROOT . '/' . $this->getFullRelativePath();
            return file_exists($fileFullPath) ? $fileFullPath : null;
        }

        /**
     * Function to get relative path to DOL_DATA_ROOT of this file
     * @return string|null
     */
        public function getFullRelativePath()
        {
            if (!empty($this->filepath) && !empty($this->filename)) {
                return $this->filepath . '/' . $this->filename;
            }
            return null;
        }

        /**
     * Function to clean the ecm file database for a given directory
     * Dolibarr does not always succeed in removing ecm database entries when files are deleted from disk
     * @param DoliDB $db database instance
     * @param string $relativePathAccordingToDocumentFolder relative path to DOL_DATA_ROOT of the directory to clean
     * @param User $user calling clean of database
     * @return array $array of errors
     */
        public static function cleanEcmFileDatabase($db, $relativePathAccordingToDocumentFolder, $user)
        {
            $listOfFileOnDisk = dol_dir_list(DOL_DATA_ROOT . '/' . $relativePathAccordingToDocumentFolder, 'files', 0, null, null, null, null, 0, 1);
            $staticEcm = new self($db);
            $listOfFilesIntoDatabase = $staticEcm->fetchAll('ASC', 'rowid', null, null, array('filepath' => $relativePathAccordingToDocumentFolder));
            $errors = array();
            $listOfFilesIntoDatabase = is_array($listOfFilesIntoDatabase) ? $listOfFilesIntoDatabase : array();
            foreach ($listOfFilesIntoDatabase as $ecm) {
                $fileInDisk = findObjectInArrayByProperty($listOfFileOnDisk, 'name', $ecm->filename);
                if (!$fileInDisk) {
                    $properEcmObject = new self($db);
                    $properEcmObject->fetch($ecm->id);
                    $properEcmObject->delete($user);
                    $errors = array_merge($errors, $properEcmObject->errors);
                }
            }
            return $errors;
        }

        /**
     * Function to manage proper upload of file and return linked ecm instance
     * @param DoliDB $db database instance to use
     * @param string $moduleRelativePathToDocument Relative path to DOL_DATA_ROOT of the module base directory
     * @param string $relativePathInModuleWhereStoreFile Relative path to module where store file
     * @param string $postFieldContainingFilesName Post field containing files to be saved
     * @return self[]|null
     */
        public static function manageUploadOfFiles($db, $moduleRelativePathToDocument, $relativePathInModuleWhereStoreFile, $postFieldContainingFilesName)
        {
            //First we call core function
            $res = dol_add_file_process(DOL_DATA_ROOT . '/' . $moduleRelativePathToDocument . '/' . $relativePathInModuleWhereStoreFile, 0, 1, $postFieldContainingFilesName);

            //Now we will find these uploaded files ecm instance
            $staticEcm = new self($db);
            $arrayOfEcmInstance = $staticEcm->fetchAll('ASC', 'rowid', null, null, array('filepath' => $moduleRelativePathToDocument . '/' . $relativePathInModuleWhereStoreFile));
            $arrayOfEcmInstance = $arrayOfEcmInstance ?? array();
            $listOfUploadedFileNames = array();
            $TFile = $_FILES[$postFieldContainingFilesName];
            if (!is_array($TFile['name'])) {
                foreach ($TFile as $key => &$val) {
                    $val = array($val);
                }
            }
            $listOfUploadedFileNames = $TFile['name'];
            $justUploadedEcmInstance = array();
            foreach ($listOfUploadedFileNames as $fileName) {
                $linkedEcmInstance = findObjectInArrayByProperty($arrayOfEcmInstance, 'filename', $fileName);
                if ($linkedEcmInstance) {
                    $justUploadedEcmInstance[] = $linkedEcmInstance;
                }
            }
            return $res > 0 ? $justUploadedEcmInstance : null;
        }

        /**
     * Function to create file from a stream content
     * @param string $relativePathToDolDataRoot path relative to DOL_DATA_ROOT where write file
     * @param string $fileName Name to give to the file
     * @param mixed $streamContent Content of the file
     * @param User $user user requesting action
     * @return ExtendedEcm|null
     */
        public function writeEcmFileFromStreamContent($relativePathToDolDataRoot, $fileName, $streamContent, $user)
        {
            //File have successfully been copied
            $result = null;
            $destinationFilePath = DOL_DATA_ROOT . '/' . $relativePathToDolDataRoot . '/' . $fileName;
            if (file_put_contents($destinationFilePath, $streamContent)) {
                //We create ecm file instance of it
                $newEcm = new self($this->db);
                $newEcm->fetch(null, null, $relativePathToDolDataRoot . '/' . $fileName);
                $newEcm->label = md5_file(dol_osencode($destinationFilePath));
                $newEcm->filepath = $relativePathToDolDataRoot;
                $newEcm->filename = $fileName;
                $newEcm->gen_or_uploaded = 'generated';
                if ($newEcm->id > 0) {
                    $result = $newEcm->update($user);
                } else {
                    $result = $newEcm->create($user);
                }
            }
            return $result > 0 ? $newEcm : null;
        }

        /**
     * Function to get Extended Ecm of a file based on its absolute path
     * @param string $absolutePath absolute path of the file to find
     * @return ExtendedEcm|null
     */
        public function getInstanceFileFromItsAbsolutePath($absolutePath)
        {
            $relativeDirectory = $this->getRelativeDirectoryOfAFile($absolutePath);
            $fileName = basename($absolutePath);
            $ecmfile = new ExtendedEcm($this->db);
            $result = $ecmfile->fetch(0, '', $relativeDirectory . '/' . $fileName);
            if ($result > 0 && $ecmfile->id > 0) {
                return $ecmfile;
            } else {
                return null;
            }
        }

        /**
     * Function to get relative path to module of this file
     * @param string $modulePart name of the module of this file
     * @return string
     */
        public function getRelativePathToModule($modulePart)
        {
            $relativePathToDolDataRoot = $this->getFullRelativePath();
            $result = preg_replace('/^[\\/]/', '', $relativePathToDolDataRoot);
            $result = preg_replace('/^' . preg_quote($modulePart, '/') . '/', '', $result);
            $result = preg_replace('/^[\\/]/', '', $result);
            return $result;
        }
    }
}
