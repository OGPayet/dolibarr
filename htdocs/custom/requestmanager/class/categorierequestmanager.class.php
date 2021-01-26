<?php
/* Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 *	\file       requestmanager/class/categorierequestmanager.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class to manage requestmanager categories
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

/**
 * Class to manage requestmanager categories
 */
class CategorieRequestManager extends Categorie
{
    /**
     * Category type
     */
    const TYPE_REQUESTMANAGER = 'requestmanager';

    public $parent_table_elemement = 'categorie';
    public $table_element = 'categorie_requestmanager';


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }


    /**
     * Return list of categories (object instances or labels) linked to element of id $id and type $type
     * Should be named getListOfCategForObject
     *
     * @param   int    $id     Id of element
     * @param   string $type   Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
     * @param   string $mode   'id'=Get array of category ids, 'object'=Get array of fetched category instances, 'label'=Get array of category
     *                         labels, 'id'= Get array of category IDs
     * @return  mixed          Array of category objects or < 0 if KO
     */
    function containing($id, $type, $mode='object')
    {
        $cats = array();

        $sql  = "SELECT ct.fk_categorie, c.label, c.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as ct";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX .  $this->parent_table_elemement . " as c ON c.rowid = ct.fk_categorie";
        $sql .= " AND ct.fk_" . $type . "=" . $id;
        $sql .= " AND c.entity IN (" . getEntity( 'category', 1 ) . ")";

        $res = $this->db->query($sql);
        if ($res)
        {
            while ($obj = $this->db->fetch_object($res))
            {
                if ($mode == 'id') {
                    $cats[] = $obj->rowid;
                } else if ($mode == 'label') {
                    $cats[] = $obj->label;
                } else {
                    $cat = new Categorie($this->db);
                    $cat->fetch($obj->fk_categorie);
                    $cats[] = $cat;
                }
            }
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }

        return $cats;
    }


    /**
     * Check for the presence of an object in a category
     *
     * @param   string      $type        Type of category ('customer', 'supplier', 'contact', 'product', 'member', 'requestmanager')
     * @param   int         $object_id   Id of the object to search
     *
     * @return  int         Number of occurrences
     * @see getObjectsInCateg
     */
    function containsObject($type, $object_id)
    {
        $sql  = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE fk_categorie=" . $this->id . " AND fk_" . $type . "=" . $object_id;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            return $this->db->fetch_object($resql)->nb;
        } else {
            $this->error = $this->db->error() . ' sql=' . $sql;
            return -1;
        }
    }


    /**
     * Link an object to the category
     *
     * @param   CommonObject    $obj    Object to link to category
     * @param   string          $type   Type of category ('customer', 'supplier', 'contact', 'product', 'member')
     *
     * @return  int             1 : OK, -1 : erreur SQL, -2 : id not defined, -3 : Already linked
     */
    function add_type($obj, $type = '')
    {
        global $conf, $user;

        $error = 0;

        if ($this->id == -1) return -2;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " (fk_categorie, fk_" . $type . ")";
        $sql .= " VALUES (" . $this->id . ", " . $obj->id . ")";

        dol_syslog(__METHOD__, LOG_DEBUG);
        if ($this->db->query($sql))
        {
            if (! empty($conf->global->CATEGORIE_RECURSIV_ADD))
            {
                $sql  = "SELECT fk_parent FROM " . $this->parent_table_element;
                $sql .= " WHERE rowid = ".$this->id;

                dol_syslog(__METHOD__, LOG_DEBUG);
                $resql = $this->db->query($sql);
                if ($resql)
                {
                    if ($this->db->num_rows($resql) > 0)
                    {
                        $objparent = $this->db->fetch_object($resql);

                        if (!empty($objparent->fk_parent))
                        {
                            $cat = new CategorieRequestManager($this->db);
                            $cat->id = $objparent->fk_parent;
                            if (!$cat->containsObject($type, $obj->id)) {
                                $result = $cat->add_type($obj, $type);
                                if ($result < 0)
                                {
                                    $this->error = $cat->error;
                                    $error++;
                                }
                            }
                        }
                    }
                }
                else
                {
                    $error++;
                    $this->error = $this->db->lasterror();
                }

                if ($error)
                {
                    $this->db->rollback();
                    return -1;
                }
            }

            // Save object we want to link category to into category instance to provide information to trigger
            $this->linkto = $obj;

            // Call trigger
            $result = $this->call_trigger('CATEGORYREQUESTMANAGER_LINK', $user);
            if ($result < 0) { $error++; }
            // End call triggers

            if (! $error)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                $this->error = $this->db->lasterrno();
                return -3;
            }
            else
            {
                $this->error = $this->db->lasterror();
            }
            return -1;
        }
    }


    /**
     * Delete object from category
     *
     * @param   CommonObject    $obj    Object
     * @param   string          $type   Type of category ('customer', 'supplier', 'contact', 'product', 'member')
     *
     * @return  int             1 if OK, -1 if KO
     */
    function del_type($obj, $type)
    {
        global $user;

        $error = 0;

        $this->db->begin();

        $sql  = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE fk_categorie = " . $this->id;
        $sql .= " AND fk_" . $type . "=" . $obj->id;

        dol_syslog(__METHOD__, LOG_DEBUG);
        if ($this->db->query($sql))
        {
            // Save object we want to unlink category off into category instance to provide information to trigger
            $this->unlinkoff = $obj;

            // Call trigger
            $result=$this->call_trigger('CATEGORYREQUESTMANAGER_UNLINK', $user);
            if ($result < 0) { $error++; }
            // End call triggers

            if (! $error)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            return -1;
        }
    }


    /**
     *	Returns an array containing the list of parent categories
     *
     *	@return	int|array <0 KO, array OK
     */
    function get_meres()
    {
        $parents = array();

        $sql = "SELECT fk_parent FROM " . MAIN_DB_PREFIX . $this->parent_table_element;
        $sql.= " WHERE rowid = ".$this->id;

        $res  = $this->db->query($sql);

        if ($res)
        {
            while ($rec = $this->db->fetch_array($res))
            {
                if ($rec['fk_parent'] > 0)
                {
                    $cat = new Categorie($this->db);
                    $cat->fetch($rec['fk_parent']);
                    $parents[] = $cat;
                }
            }
            return $parents;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     * 	Returns in a table all possible paths to get to the category
     * 	starting with the major categories represented by Tables of categories
     *
     *	@return	array
     */
    function get_all_ways()
    {
        $ways = array();

        $parents = $this->get_meres();
        if (! empty($parents))
        {
            foreach ($parents as $parent)
            {
                $allways = $parent->get_all_ways();
                foreach ($allways as $way)
                {
                    $w		= $way;
                    $w[]	= $this;
                    $ways[]	= $w;
                }
            }
        }

        if (count($ways) == 0)
            $ways[0][0] = $this;

        return $ways;
    }


    /**
     * Returns the path of the category, with the names of the categories
     * separated by $sep (" >> " by default)
     *
     * @param	string	$sep	     Separator
     * @param	string	$url	     Url
     * @param   int     $nocolor     0
     * @return	array
     */
    function print_all_ways($sep = ' &gt;&gt; ', $url = '', $nocolor = 0, $addpicto = 0)
    {
        $ways = array();

        $allways = $this->get_all_ways(); // Load array of categories
        foreach ($allways as $way)
        {
            $w = array();
            $i = 0;
            foreach ($way as $cat)
            {
                $i++;

                if (empty($nocolor))
                {
                    $forced_color = 'toreplace';
                    if ($i == count($way))
                    {
                        // Check contrast with background and correct text color
                        $forced_color = 'categtextwhite';
                        if ($cat->color)
                        {
                            $hex=$cat->color;
                            $r = hexdec($hex[0].$hex[1]);
                            $g = hexdec($hex[2].$hex[3]);
                            $b = hexdec($hex[4].$hex[5]);
                            $bright = (max($r, $g, $b) + min($r, $g, $b)) / 510.0;    // HSL algorithm
                            if ($bright >= 0.5) $forced_color='categtextblack';       // Higher than 60%
                        }
                    }
                }

                if ($url == '')
                {
                    $link = '<a href="' . DOL_URL_ROOT . '/categories/viewcat.php?id=' . $cat->id . '&type=' . $cat->type . '" class="' . $forced_color . '">';
                    $linkend = '</a>';
                    $w[] = $link . $cat->label . $linkend;
                }
                else
                {
                    $w[] = "<a href='".DOL_URL_ROOT."/$url?catid=" . $cat->id . "'>" . $cat->label . "</a>";
                }
            }
            $newcategwithpath = preg_replace('/toreplace/', $forced_color, implode($sep, $w));

            $ways[] = $newcategwithpath;
        }

        return $ways;
    }
}
