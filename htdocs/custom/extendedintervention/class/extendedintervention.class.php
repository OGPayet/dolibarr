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
 * \file    htdocs/extendedintervention/class/extendedintervention.class.php
 * \ingroup extendedintervention
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';

/**
 *	Sort by position
 */
function ei_sort_question_bloc_position($a, $b)
{
    if (isset($a) && isset($b) && $a->position_question_bloc != $b->position_question_bloc) return ($a->position_question_bloc < $b->position_question_bloc) ? -1 : 1;
    if (isset($a) && isset($b) && $a->label_question_bloc != $b->label_question_bloc) return ($a->label_question_bloc < $b->label_question_bloc) ? -1 : 1;
    return 0;
}

/**
 * Class ExtendedIntervention
 *
 * Put here description of your class
 * @see Fichinter
 */
class ExtendedIntervention extends Fichinter
{
    /**
     * Array of whitelist of properties keys for this object used for the API
     * @var  array
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES = array(
        "id" => '', "ref" => '', "description" => '', "socid" => '', "statut" => '', "duration" => '', "datec" => '',
        "datee" => '', "dateo" => '', "datet" => '', "datev" => '', "datem" => '', "fk_project" => '', "note_public" => '',
        "trueWidth" => '', "width_units" => '', "trueHeight" => '', "height_units" => '', "trueDepth" => '', "depth_units" => '',
        "fk_contrat" => '', "user_creation" => '', "brouillon" => '', "thirdparty" => '', "array_options" => '',
        "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '',
    );

    /**
     * Array of whitelist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      if empty array then equal at $api_whitelist_of_properties
     *      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static public $API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
     * Array of blacklist of properties keys for this object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES = array(
    );

    /**
     * Array of blacklist of properties keys for this object when is a linked object used for the API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT = array(
    );

    /**
	 * @var EIQuestionBloc[]
	 */
    public $survey = array();

    /**
     *  Cache of the list of question bloc information
     * @var DictionaryLine[]
     */
	static public $question_bloc_cached = array();

    /**
     * Status
     */
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_INVOICED = 2;
    const STATUS_DONE = 3;

    /**
     *  Fetch all the question bloc information (cached)
     * @return  int                 <0 if not ok, > 0 if ok
     */
    public function fetchQuestionBlocInfo() {
        if (empty($this->array_options['options_ei_type'])) {
            $this->fetch_optionals();
        }

        if (empty(self::$question_bloc_cached[$this->array_options['ei_type']])) {
            if (!empty($this->array_options['options_ei_type'])) {
                dol_include_once('/advancedictionaries/class/dictionary.class.php');
                $dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventionquestionbloc');
                if ($dictionary->fetch_lines(1, array('types_intervention' => array($this->array_options['options_ei_type']))) > 0) {
                    self::$question_bloc_cached[$this->array_options['options_ei_type']] = $dictionary->lines;
                } else {
                    $this->error = $dictionary->error;
                    $this->errors = array_merge($this->errors, $dictionary->errors);
                    return -1;
                }
            }
        }

        return 1;
    }

    /**
     *  Load the survey
     *
     * @param   int     $all_data   1=Load all data of the dictionaries (all status, all answer and all predefined text)
     * @return  int                 <0 if KO, >0 if OK
     */
    public function fetch_survey($all_data=0)
    {
        $this->survey = array();

        if ($this->id > 0 && $this->statut > self::STATUS_DRAFT) {
            dol_include_once('/extendedintervention/class/extendedinterventionquestionbloc.class.php');
            if ($this->statut == self::STATUS_DONE) $all_data = 0;

            $sql = "SELECT t.fk_c_question_bloc";
            $sql .= " FROM " . MAIN_DB_PREFIX . "extendedintervention_question_bloc AS t";
            $sql .= " WHERE t.fk_fichinter=" . $this->id;

            dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $bloc = new EIQuestionBloc($this->db, $this);
                    if ($bloc->fetch(0, $this->id, $obj->fk_c_question_bloc, $all_data, 0) < 0) {
                        $this->error = $bloc->error;
                        $this->errors = $bloc->errors;
                        return -1;
                    }
                    $bloc->read_only = 1;
                    $this->survey[$obj->fk_c_question_bloc] = $bloc;
                }
            } else {
                $this->error = $this->db->lasterror();
                dol_syslog(__METHOD__ . " SQL: " . $sql . '; Errors: ' . $this->errorsToString(), LOG_ERR);
                return -1;
            }

            if ($this->statut != self::STATUS_DONE) {
                if (empty($this->array_options['options_ei_type'])) {
                    $this->fetch_optionals();
                }

                if ($this->fetchQuestionBlocInfo() < 0)
                    return -1;

                if ($this->fetchObjectLinked() < 0)
                    return -1;
                $linked_equipments = is_array($this->linkedObjectsIds['equipement']) ? $this->linkedObjectsIds['equipement'] : array();

                // Filter question bloc for this intervention type and categories of the linked equipments
                $equipements_categories = array();
                $nb_equipements_categories = 0;
                if (count($linked_equipments)) {
                    dol_include_once('/equipement/class/equipement.class.php');
                    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
                    $equipment_static = new Equipement($this->db);
                    $category_static = new Categorie($this->db);
                    $full_categories = $category_static->get_full_arbo('product');
                    $parents_categories = array();
                    foreach ($full_categories as $category) {
                        $parents_categories[$category['id']] = array_filter(explode('_', $category['fullpath']), "strlen");
                    }
                    foreach ($linked_equipments as $equipment_id) {
                        if ($equipment_id > 0 && $equipment_static->fetch($equipment_id) > 0) {
                            if ($equipment_static->fk_product > 0) {
                                $categories = $category_static->containing($equipment_static->fk_product, 'product', 'id');
                                foreach ($categories as $category_id) {
                                    if (isset($parents_categories[$category_id])) {
                                        $equipements_categories = array_merge($equipements_categories, $parents_categories[$category_id]);
                                    }
                                }
                            }
                        }
                    }
                    $equipements_categories = array_flip(array_flip($equipements_categories));
                    $nb_equipements_categories = count($equipements_categories);
                }

                if (is_array(self::$question_bloc_cached[$this->array_options['options_ei_type']])) {
                    foreach (self::$question_bloc_cached[$this->array_options['options_ei_type']] as $question_bloc_id => $question_bloc) {
                        if (empty($question_bloc->fields['categories']) ||
                            count(array_diff($equipements_categories, explode(',', $question_bloc->fields['categories']))) != $nb_equipements_categories
                        ) {
                            if (!isset($this->survey[$question_bloc->id])) {
                                $bloc = new EIQuestionBloc($this->db, $this);
                                if ($bloc->fetch(0, $this->id, $question_bloc->id, $all_data, 0) < 0) {
                                    $this->error = $bloc->error;
                                    $this->errors = $bloc->errors;
                                    return -1;
                                }
                                $this->survey[$question_bloc->id] = $bloc;
                            }
                            if (isset($this->survey[$question_bloc->id])) $this->survey[$question_bloc->id]->read_only = 0;
                        }
                    }
                }
            }

            // Sort by position
            uasort($this->survey, 'ei_sort_question_bloc_position');
        }

        return 1;
    }
}