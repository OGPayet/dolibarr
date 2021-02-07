<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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

class ActionsSirene
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param array() $parameters Hook metadatas (context, etc...)
     * @param CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string &$action Current action (if set). Generally create or edit or null
     * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $context = explode(':', $parameters['context']);
        $confirm = GETPOST('confirm', 'alpha');
        if (in_array('thirdpartycard', $context)) {
            $langs->load('sirene@sirene');

            if (GETPOST('sirene_search') != '') {
                global $sirene;
                dol_include_once('/sirene/class/sirene.class.php');
                $sirene = new Sirene($this->db);

                $result = $sirene->connection();
                if ($result < 0) {
                    $this->error = $sirene->error;
                    $this->errors = $sirene->errors;
                    return -1;
                }

                $company_name = GETPOST('sirene_company_name', 'alpha');
                $siren = GETPOST('sirene_siren', 'alpha');
                $siret = GETPOST('sirene_siret', 'alpha');
                $naf = GETPOST('sirene_naf', 'alpha');
                $zipcode = GETPOST('sirene_zipcode', 'alpha');
                //$country_code = GETPOST('sirene_country_code', 'alpha');
                //$country = GETPOST('sirene_country', 'alpha');
                //$country=GETPOST('country', 'int');
                //Recuperation number
                $number = GETPOST('sirene_number', 'int');
                $only_open = GETPOST('sirene_only_open', 'int');

                //Condition number
                $number = $number <= 0 ? 20 : ($number > 100 ? 100 : $number);

                $result = $sirene->getCompanies($company_name, $siren, $siret, $naf, $zipcode, $number, $only_open);
                if ($result < 0) {
                    setEventMessages($langs->trans('SireneErrorWhileGetCompanies'), $sirene->errors, 'errors');
                } elseif (empty($sirene->companies_results)) {
                    setEventMessage($langs->trans('SireneCompaniesNotFound'), 'warnings');
                } else  $_GET['sirene_action'] = 'sirene_set';

                if ($action == 'add') $action = 'create';
                else $action = 'edit';


            } elseif ($action == 'confirm_sirene_set') {
                if (!empty($_SESSION['sirene_save_post'])) {
                    foreach ($_SESSION['sirene_save_post'] as $key => $value) {
                        $_POST[$key] = $value;
                    }
                    $_SESSION['sirene_save_post'] = array();
                }

                $confirm = GETPOST('confirm', 'alpha');
                if ($confirm == 'yes') {
                    dol_include_once('/sirene/class/sirene.class.php');
                    $sirene = new Sirene($this->db);

                    $result = $sirene->connection();
                    if ($result < 0) {
                        $this->error = $sirene->error;
                        $this->errors = $sirene->errors;
                        return -1;
                    }

                    $siret = GETPOST('sirene_choice', 'alpha');
                    $result = $sirene->getCompanies('', '', $siret, '', '', '', 0);
                    if ($result < 0) {
                        setEventMessages($langs->trans('SireneErrorWhileGetCompanies'), $sirene->errors, 'errors');
                    } elseif (empty($sirene->companies_results)) {
                        setEventMessage($langs->trans('SireneCompaniesNotFound'), 'warnings');
                    } else {
                        $company_infos = array_values($sirene->companies_results);
                        $company_infos = $company_infos[0];

                        // intra-community vat number calculation
                        $coef = 97;
                        $vatintracalc = fmod($company_infos['siren'], $coef);
                        $vatintracalc2 = fmod((12 + 3 * $vatintracalc), $coef);
                        $company_infos['tva_intra'] = 'FR' . str_pad($vatintracalc2, 2, 0, STR_PAD_LEFT) . $company_infos['siren'];
                        $company_infos['country'] = GETPOST('country', 'int') . $company_infos['siren'];


                        $_GET['civility_id'] = $company_infos['civility'];
                        $_GET['name'] = $company_infos['company_name'];
                        $_GET['firstname'] = $company_infos['firstname'];
                        $_GET['private'] = $company_infos['private'];
                        $_GET['address'] = $company_infos['address'];
                        $_GET['zipcode'] = $company_infos['zipcode'];
                        $_GET['town'] = $company_infos['town'];
                        $_GET['state_id'] = $company_infos['state_id'];
                        $_GET['idprof1'] = $company_infos['siren'];
                        $_GET['idprof2'] = $company_infos['siret'];
                        $_GET['idprof3'] = $company_infos['codenaf_san'];
                        $_GET['tva_intra'] = $company_infos['tva_intra'];
                        $_GET['country'] = $company_infos['country'];
                        $_GET['country_code'] = $company_infos['country_code'];
                        $_GET['country_san'] = $company_infos['country_san'];

                        //modif

                    }
                }

                $old_action = GETPOST('old_action', 'alpha');
                if ($old_action == 'create') $action = 'create';
                else $action = 'edit';


            } elseif ($action == 'confirm_sirene_import') {
                if (!empty($_SESSION['sirene_save_post'])) {
                    foreach ($_SESSION['sirene_save_post'] as $key => $value) {
                        $_POST[$key] = $value;
                    }
                    $_SESSION['sirene_save_post'] = array();
                }

                $confirm = GETPOST('confirm', 'alpha');
                if ($confirm == 'yes') {
                    dol_include_once('/sirene/class/sirene.class.php');
                    $sirene = new Sirene($this->db);

                    $result = $sirene->connection();
                    if ($result < 0) {
                        $this->error = $sirene->error;
                        $this->errors = $sirene->errors;
                        return -1;
                    }
                    $object->oldcopy = clone $object;

                    $siret = GETPOST('sirene_choice', 'alpha');
                    $result = $sirene->getCompanies('', '', $siret, '', '', '', 0);
                    if ($result < 0) {
                        setEventMessages($langs->trans('SireneErrorWhileGetCompanies'), $sirene->errors, 'errors');
                    } elseif (empty($sirene->companies_results)) {
                        setEventMessage($langs->trans('SireneCompaniesNotFound'), 'warnings');
                    } else {
                        $company_infos = array_values($sirene->companies_results);
                        $company_infos = $company_infos[0];

                        // intra-community vat number calculation
                        $coef = 97;
                        $vatintracalc = fmod($company_infos['siren'], $coef);
                        $vatintracalc2 = fmod((12 + 3 * $vatintracalc), $coef);
                        $company_infos['tva_intra'] = 'FR' . str_pad($vatintracalc2, 2, 0, STR_PAD_LEFT) . $company_infos['siren'];
                        $company_infos['country'] = GETPOST('country', 'int') . $company_infos['siren'];

                        $_GET['civility_id'] = $company_infos['civility'];
                        $_GET['name'] = $company_infos['company_name'];
                        $_GET['firstname'] = $company_infos['firstname'];
                        $_GET['private'] = $company_infos['private'];
                        $_GET['address'] = $company_infos['address'];
                        $_GET['zipcode'] = $company_infos['zipcode'];
                        $_GET['town'] = $company_infos['town'];
                        $_GET['state_id'] = $company_infos['state_id'];
                        $_GET['idprof1'] = $company_infos['siren'];
                        $_GET['idprof2'] = $company_infos['siret'];
                        $_GET['idprof3'] = $company_infos['codenaf_san'];
                        $_GET['tva_intra'] = $company_infos['tva_intra'];
                        $_GET['country'] = $company_infos['country'];
                        $_GET['country_code'] = $company_infos['country_code'];
                        $_GET['country_san'] = $company_infos['country_san'];

                        //modif

                    }
                }

                $old_action = GETPOST('old_action', 'alpha');
                if ($old_action == 'create') $action = 'create';
                else $action = 'edit';

            } elseif ($action == 'confirm_sir_data' && $confirm == 'yes' && $user->rights->societe->creer && $user->rights->cron->execute) {
                //maniere d'inclure un fichier propre à dolibarr
                dol_include_once('/sirene/class/sirene.class.php');
                $sirene = new Sirene($this->db);

                // Connection to sirene API
                $result = $sirene->connection();
                if ($result < 0) {
                    setEventMessages($langs->trans('SireneErrorWhileConnect'), $sirene->errors, 'errors');
                    return -1;
                }

                //gestion des erreurs si le result de geCompany est vide
                //recherche par rapport au nom du tier et du siren
                $result = $sirene->getCompanies('', '', $object->idprof2, '', '', '');

                if ($result < 0) {
                    setEventMessages($langs->trans('SireneErrorWhileGetCompanies'), $sirene->errors, 'errors');
                    return -1;
                } elseif (empty($sirene->companies_results)) {
                    setEventMessage($langs->trans('SireneCompaniesNotFound'), 'warnings');
                    return 0;
                }

                $company_infos = $sirene->companies_results[0];

                // Definition des lignes
                $correspondance = array(
                    'company_name' => 'name',
                    'address' => 'address',
                    'zipcode' => 'zip',
                    'town' => 'town',
                    'state_id' => 'state_id',
                    'siren' => 'idprof1',
                    'siret' => 'idprof2',
                    'codenaf_san' => 'idprof3',
                    'sirene_tva_intra' => 'tva_intra',
                    'country' => 'country',
                    'country_id' => 'country_id',
                    'country_code' => 'country_code',
                    'country_san' => 'country_san',

                );

                $object->oldcopy = clone $object;

                $modified = false;
                foreach ($correspondance as $key => $property_name) {
                    $choice = GETPOST($key, 'int');
                    if (!empty($choice)) {
                        $object->$property_name = $company_infos[$key];
                        $modified = true;
                    }
                }

                if ($modified) {
                    $object->array_options['options_sirene_status'] = 0;
                    $object->array_options['options_sirene_update_date'] = dol_now();
                    $result = $object->update($object->id, $user);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        return -1;
                    }
                }
            }
            elseif ($action == 'confirm_sirene_update' && $confirm == 'yes' && $user->rights->societe->creer && $user->rights->cron->execute) {
                // Definition des lignes
                $correspondance = array(
                    'company_name' => 'name',
                    'address' => 'address',
                    'zipcode' => 'zip',
                    'town' => 'town',
                    'state_id' => 'state_id',
                    'siren' => 'idprof1',
                    'siret' => 'idprof2',
                    'codenaf_san' => 'idprof3',
                    'sirene_tva_intra' => 'tva_intra',
                    'country' => 'country',
                    'country_id' => 'country_id',
                    'country_code' => 'country_code',
                    'country_san' => 'country_san',

                );
                $object->oldcopy = clone $object;

                $modified = false;
                foreach ($correspondance as $key => $property_name) {
                    $choice = GETPOST('choice_' . $key, 'int');
                    if (!empty($choice)) {
                        $company_info = GETPOST($key, 'alpha');
                        $object->$property_name = $company_info;
                        $modified = true;
                    }
                }

                if ($modified) {
                    $object->array_options['options_sirene_status'] = 0;
                    $object->array_options['options_sirene_update_date'] = dol_now();
                    $result = $object->update($object->id, $user);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        return -1;
                    }
                }
            }
            elseif (GETPOST('sirene_search') != '') {
                global $sirene;
                dol_include_once('/sirene/class/sirene.class.php');
                $sirene = new Sirene($this->db);

                $result = $sirene->connection();
                if ($result < 0) {
                    $this->error = $sirene->error;
                    $this->errors = $sirene->errors;
                    return -1;
                }

                $company_name = GETPOST('sirene_company_name', 'alpha');
                $siren = GETPOST('sirene_siren', 'alpha');
                $siret = GETPOST('sirene_siret', 'alpha');
                $naf = GETPOST('sirene_naf', 'alpha');
                $zipcode = GETPOST('sirene_zipcode', 'alpha');

                //Recuperation number
                $number = GETPOST('sirene_number', 'int');

                $only_open = GETPOST('sirene_only_open', 'int');

                //Condition number
                $number = $number <= 0 ? 20 : ($number > 100 ? 100 : $number);

                $result = $sirene->getCompanies($company_name, $siren, $siret, $naf, $zipcode, $number, $only_open);
                if ($result < 0) {
                    setEventMessages($langs->trans('SireneErrorWhileGetCompanies'), $sirene->errors, 'errors');
                } elseif (empty($sirene->companies_results)) {
                    setEventMessage($langs->trans('SireneCompaniesNotFound'), 'warnings');
                } else  $_GET['sirene_action'] = 'sirene_set';

                if ($action == 'add') $action = 'create';
                else $action = 'edit';
            }
        }

        return 0;
    }
    /**
     * Overloading the formObjectOptions function : replacing the parent's function with the one below
     *
     * @param array() $parameters Hook metadatas (context, etc...)
     * @param CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string &$action Current action (if set). Generally create or edit or null
     * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $form, $user;
        $context = explode(':', $parameters['context']);

        if (in_array('thirdpartycard', $context)) {
            if ($action != 'create' && $action != 'edit') {
                if (strtoupper($object->country_code) == 'FR') {
                    $codenaf = trim(strtoupper($object->idprof3));
                    if (isset($codenaf) && !empty($codenaf)) {
                        $langs->load('sirene@sirene');

                        $sql = "SELECT label FROM " . MAIN_DB_PREFIX . "c_codenaf WHERE code = '" . $this->db->escape($codenaf) . "'";
                        $result = $this->db->query($sql);
                        if ($result) {
                            if ($this->db->num_rows($result) == 1) {
                                $obj = $this->db->fetch_object($result);
                                $codenaf = $codenaf . ' - ' . $langs->trans($obj->label);
                            } else {
                                $codenaf = '<span style="color:red">' . $codenaf . ' - ' . $langs->trans('SireneErrorCodeNafNotFound', dol_buildpath('/admin/dict.php', 1)/*.'?id='.$dict_id*/) . '</span>';
                            }

                            $idprof3 = $langs->transcountry('ProfId3', $object->country_code);
                            print "\n" . '<script type="text/javascript" language="javascript">';
                            print '$(document).ready(function () {
                			$("div.fichehalfleft td:contains(\'' . str_replace('\'', '\\\'', $idprof3) . '\')").next().html(\'' . str_replace('\'', '\\\'', $codenaf) . '\');
                        })';
                            print '</script>' . "\n";
                        }
                    }
                }
                //print '<tr><td class="titlefield">'.$langs->trans('etatsirene').'</td><td>'.'a ete mis a jpursirene_set'.'</td></tr>';
            } elseif ($action == 'create' || $action == '') {
                $langs->load('sirene@sirene');
                $sirene_action = GETPOST('sirene_action', 'alpha');

                print '<tr id="sirene_infos"><td colspan="4">' . "\n";
                print '<div id="sirene_block_infos">' . "\n";

                print '<table class="noborder" width="100%">' . "\n";
                print '<tr>' . "\n";
                print '<td colspan="6"><u>' . $langs->trans("SireneSirene") . '</u> : ' . $form->textwithpicto('', $langs->trans("SireneSearchHelp"), 2) . "\n";

                $only_open = (isset($_POST['sirene_number']) ? (GETPOST('sirene_only_open', 'int') ? 1 : 0) : 1);

                print '<br>' . $langs->trans("SireneOnlyOpen") . ' : <input type="checkbox" name="sirene_only_open" value="1"' . ($only_open ? ('checked="checked"') : '') . '></br>' . "\n";
                print '</tr>' . "\n";
                print '<tr>' . "\n";
                print '<td>' . $langs->trans("SireneCompanyName") . ' : <input type="text" name="sirene_company_name" value="' . GETPOST('sirene_company_name', 'alpha') . '"></td>' . "\n";
                print '<td>' . $langs->trans("SireneSiren") . ' : <input type="text" name="sirene_siren" value="' . GETPOST('sirene_siren', 'alpha') . '"></td>' . "\n";
                print '<td>' . $langs->trans("SireneSiret") . ' : <input type="text" name="sirene_siret" value="' . GETPOST('sirene_siret', 'alpha') . '"></td>' . "\n";
                print '<td>' . $langs->trans("SireneNaf") . ' ' . $form->textwithpicto('', $langs->trans("SireneSearchCodeNafHelp"), 2) . ' : <input type="text" name="sirene_naf" value="' . GETPOST('sirene_naf', 'alpha') . '"></td>' . "\n";
                print '<td>' . $langs->trans("CompanyZip") . ' : <input type="text" name="sirene_zipcode" value="' . GETPOST('sirene_zipcode', 'alpha') . '"></td>' . "\n";
                print '<td>' . $langs->trans("SireneNumber") . ' : <input type="number" name="sirene_number" min="20" max="100" size="3" value="' . GETPOST('sirene_number', 'alpha') . '"></td>' . "\n";
                print '</tr>' . "\n";
                print '<tr>' . "\n";
                print '<td colspan="6" class="center"><input type="submit" name="sirene_search" class="button" value="' . $langs->trans("Search") . '"></td>' . "\n";
                print '</tr>' . "\n";
                print '</table>' . "\n";

                if ($sirene_action == 'sirene_set') {
                    global $sirene;
                    $table_choices = '<table id="sirene_table" class="noborder" width="100%">' . "\n";
                    $table_choices .= '<tr class="liste_titre">' . "\n";
                    $table_choices .= '<td width="20px"><td>' . "\n";
                    $table_choices .= '<td>' . $langs->trans("SireneCompanyName") . '<td>' . "\n";
                    $table_choices .= '<td>' . $langs->trans("Address") . '<td>' . "\n";
                    $table_choices .= '<td>' . $langs->trans("SireneCreateDate") . '<td>' . "\n";
                    $table_choices .= '<td>' . $langs->trans("SireneCloseDate") . '<td>' . "\n";
                    $table_choices .= '<td>' . $langs->trans("SireneNaf") . '<td>' . "\n";
                    $table_choices .= '<td>' . $langs->trans("SireneSiren") . '<td>' . "\n";
                    $table_choices .= '<td>' . $langs->trans("SireneSiret") . '<td>' . "\n";
                    $table_choices .= '</tr>' . "\n";

                    $checked = false;
                    foreach ($sirene->companies_results as $comapiny_infos) {
                        $table_choices .= '<tr class="oddeven">' . "\n";
                        $table_choices .= '<td><input type="radio" id="sirene_choice" name="sirene_choice" ';//checked="checked"
                        if ($comapiny_infos['status'] == "A" and !$checked) {
                            $table_choices .= ' checked';
                            $checked = true;
                        } elseif ($comapiny_infos['status'] == "F" and $checked) {
                            $table_choices .= ' unchecked';
                            $checked = true;
                        }
                        $table_choices .= ' value="' . $comapiny_infos['siret'] . '"><td>' . "\n";
                        $table_choices .= '<td>' . $comapiny_infos['company_name_all'] . '<td>' . "\n";
                        $table_choices .= '<td>' . $comapiny_infos['address_all'] . '<td>' . "\n";
                        $table_choices .= '<td>' . $comapiny_infos['date_creation'] . '<td>' . "\n";
                        $table_choices .= '<td>' . $comapiny_infos['status_all'] . '<td>' . "\n";
                        $table_choices .= '<td>' . $comapiny_infos['codenaf_all'] . '<td>' . "\n";
                        $table_choices .= '<td>' . $comapiny_infos['siren'] . '<td>' . "\n";
                        $table_choices .= '<td>' . $comapiny_infos['siret'] . '<td>' . "\n";
                        $table_choices .= '</tr>' . "\n";
                    }
                    $table_choices .= '</table>' . "\n";

                    $_SESSION['sirene_save_post'] = array();
                    $properties_bypassed = array('token', 'sirene_search', 'action');
                    foreach ($_POST as $key => $value) {
                        if (in_array($key, $properties_bypassed)) continue;

                        $_SESSION['sirene_save_post'][$key] = $value;
                    }

                    $formquestion = array(
                        array(
                            'type' => 'hidden',
                            'name' => 'old_action',
                            'value' => $action
                        ),
                        array(
                            'name' => 'sirene_choice',
                            'type' => 'onecolumn',
                            'value' => $table_choices
                        )
                    );

                    print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("PopUpSelectionTier"), $langs->trans("PopUpSelectionTierMsg"), "confirm_sirene_set", $formquestion, 'no', 1, 400, '75%');
                }

                print '<br>' . "\n";
                print '</div>' . "\n";
                print
                    <<<SCRIPT
    <script type="text/javascript" language="javascript">
        $(document).ready(function () {
            var sirene_tr = $("tr#sirene_infos");
            var sirene_block = $("div#sirene_block_infos");
            var sirene_anchor = sirene_tr.closest('table');

            sirene_block.detach().insertBefore(sirene_anchor);
            sirene_tr.remove();
            
            $('table#sirene_table tr').click(function(event) {
                if (event.target.type !== 'radio') {
                    $(':radio', this).trigger('click');
                }
            });
        });
    </script>
SCRIPT;
                print '</td></tr>' . "\n";
            }
        }
        return 0;
    }

    /**
     * Overloading the getIdProfUrl function : replacing the parent's function with the one below
     *
     * @param array() $parameters Hook metadatas (context, etc...)
     * @param CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string &$action Current action (if set). Generally create or edit or null
     * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function getIdProfUrl($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        $url = '';
        if (empty($conf->global->MAIN_DISABLEPROFIDRULES)) {
            $idprof = $parameters['idprof'];
            $thirdparty = $parameters['company'];

            // TODO Move links to validate professional ID into a dictionary table "country" + "link"
            $strippedIdProf1 = str_replace(' ', '', $thirdparty->idprof1);
            if ($idprof == 1 && $thirdparty->country_code == 'FR') {
                if (!empty($conf->global->SIRENE_VERIFICATION_SIRET_URL)) {
                    $url = $conf->global->SIRENE_VERIFICATION_SIRET_URL . $strippedIdProf1;
                } else {
                    $url = 'http://www.societe.com/cgi-bin/search?champs=' . $strippedIdProf1;
                }
            }
            if ($idprof == 1 && ($thirdparty->country_code == 'GB' || $thirdparty->country_code == 'UK')) {
                $url = 'https://beta.companieshouse.gov.uk/company/' . $strippedIdProf1;
            }
            if ($idprof == 1 && $thirdparty->country_code == 'ES') {
                $url = 'http://www.e-informa.es/servlet/app/portal/ENTP/screen/SProducto/prod/ETIQUETA_EMPRESA/nif/' . $strippedIdProf1;
            }
            if ($idprof == 1 && $thirdparty->country_code == 'IN') {
                $url = 'http://www.tinxsys.com/TinxsysInternetWeb/dealerControllerServlet?tinNumber=' . $strippedIdProf1 . ';&searchBy=TIN&backPage=searchByTin_Inter.jsp';
            }
            if ($idprof == 1 && $thirdparty->country_code == 'PT') {
                $url = 'http://www.nif.pt/' . $strippedIdProf1;
            }

            if (!empty($url)) {
                $hookmanager->resPrint = '<a target="_blank" href="' . $url . '">' . $langs->trans('Check') . ' ' . img_picto($langs->trans("SIRENIntraCheckDesc"), 'info') . '</a>';
                return 1;
            }
        }

        return 0;
    }


    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param array() $parameters Hook metadatas (context, etc...)
     * @param CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string &$action Current action (if set). Generally create or edit or null
     * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        //chargement des variables globales
        global $user, $langs, $form, $sirene, $conf;
        global $colorbackhmenu1;
        //require '../main.inc.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
        //require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        //chargement du fichier de langues
        $langs->load('sirene@sirene');
        //le contexte est utilisé comme indicateur de situations ou executer notre code -> utilisé dans les hooks
        $context = explode(':', $parameters['context']);

        //on se situe dans la fiche des tiers (thirdpartycard)
        if (in_array('thirdpartycard', $context)) {
            if ($user->rights->societe->creer) {
                //SI LE TIERS A UN SIRET
                //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);


                if (!empty($object->idprof2)) {
                    //affichage bouton SIRENE
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?socid=' . $object->id . '&amp;action=sir_data">' . $langs->trans('SireneButton') . '</a></div>';
                    $act = GETPOST('action', 'aZ09');
                    //choix de l'action et verif des droits
                    //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);

                    //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);

                    if ($act == 'sir_data' && $user->rights->societe->creer && !empty($object->idprof2)) {
                        //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);
                        //print $conf->global->SIRENE_PROCESSING_TOKEN;
                        //$resultat   =   $conf->global->SIRENE_PROCESSING_TOKEN;
                        if ($conf->global->SIRENE_PROCESSING_TOKEN == 1) {
                            //$conf->global->SIRENE_PROCESSING_TOKEN;
                            //token free
                            //dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);

                            //if (dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN",0, 'chaine', 0, '', $conf->entity) == 0) {

                            dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);
                            //print $conf->global->SIRENE_PROCESSING_TOKEN;
                            //dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0

                            //maniere d'inclure un fichier propre à dolibarr
                            dol_include_once('/sirene/class/sirene.class.php');
                            $sirene = new Sirene($this->db);
                            // Connection to sirene API
                            $result = $sirene->connection();
                            if ($result < 0) {
                                setEventMessages($langs->trans('SireneErrorWhileConnect'), $sirene->errors, 'errors');
                                return -1;
                            }
                            $result = $sirene->getCompanies('', '', $object->idprof2, '', '', '');
                            if ($result < 0) {

                                setEventMessages($langs->trans('SireneErrorWhileGetCompanies'), $sirene->errors, 'errors');
                                return -1;
                            } elseif (empty($sirene->companies_results)) {
                                setEventMessage($langs->trans('SireneCompaniesNotFound'), 'warnings');
                                return 0;
                            }

                            $formquestion = array();
                            $company_infos = $sirene->companies_results[0];
                            $correspondance = array(
                                'company_name' => array('title' => $langs->trans("SireneCompanyName"), 'dolibarr' => $object->name, 'sirene' => $company_infos['company_name']),
                                'address' => array('title' => $langs->trans("Address"), 'dolibarr' => $object->address, 'sirene' => $company_infos['address']),
                                'zipcode' => array('title' => $langs->trans("CompanyZip"), 'dolibarr' => $object->zip, 'sirene' => $company_infos['zipcode']),
                                'town' => array('title' => $langs->trans("SireneTown"), 'dolibarr' => $object->town, 'sirene' => $company_infos['town']),
                                //'state_id' => array('title' => $langs->trans("SireneSateID"), 'dolibarr' => $object->state_id, 'sirene' => $company_infos['state_id']),
                                'siren' => array('title' => $langs->trans("SireneSiren"), 'dolibarr' => $object->idprof1, 'sirene' => $company_infos['siren']),
                                'siret' => array('title' => $langs->trans("SireneSiret"), 'dolibarr' => $object->idprof2, 'sirene' => $company_infos['siret']),
                                'codenaf_san' => array('title' => $langs->trans("SireneNaf"), 'dolibarr' => $object->idprof3, 'sirene' => $company_infos['codenaf_san']),
                                'sirene_tva_intra' => array('title' => $langs->trans("SireneTvaIntra"), 'dolibarr' => $object->tva_intra, 'sirene' => $company_infos['sirene_tva_intra']),
                                'country' => array('title' => $langs->trans("SireneCountry"), 'dolibarr' => $object->country, 'sirene' => $company_infos['country']),
                                //c'est le country ID qui change le pays
                                'country_id' => array('title' => $langs->trans("SireneCountryISO"), 'dolibarr' => $object->country_id, 'sirene' => $company_infos['country_id']),
                                //'country_code' => array('title' => $langs->trans("SireneCodeIso2"), 'dolibarr' => $object->country_code, 'sirene' => $company_infos['country_code']),
                                //'country_san' => array('title' => $langs->trans("SireneCodeNumber"), 'dolibarr' => $object->code_sirene, 'sirene' => $company_infos['country_san']),
                            );
                            $table_choices = '<div class="div-table-responsive">';
                            //$table_choices = '<span class="fas fa-caret-left marginleftonlyshort valignmiddle" style="  max-width: 20px" title="Gauche"></span>';
                            $table_choices .= '<table class="noborder centpercent">' . "\n";
                            $table_choices .= '<tr class="liste_titre">' . "\n";
                            //font color=#0a0a64
                            //font color=rgb(60,70,100);
                            $table_choices .= '<td>' . '<span class="liste_titre" style="font-size: 12px; font-size: large; title="Nom des champs à mettre à jour">Nom du champs</span>' . '<td>' . "\n";
                            $table_choices .= '<td>' . '<span class="liste_titre" style="font-size: 12px; font-size: large;" title="Données dans la base de données Dolibarr">Données existantes dans Dolibarr</span>' . '<td>' . "\n";
                            //$table_choices .= '<td width="50px">' . $langs->trans("<-") . '<td>' . "\n";
                            $table_choices .= '<td>' . '<span class="fas fa-caret-left marginleftonlyshort valignmiddle"  style="font-size: 28px" title="Cocher les différentes cases pour mettre à jour les informations du tiers"></span>' . '<td>' . "\n";
                            $table_choices .= '<td><input type="checkbox" id="checkallactions" value="1"><td>' . "\n";
                            $table_choices .= '<td>' . '<span class="liste_titre" style="font-size: 12px; font-size: large;" title="Données récupéré via Sirene">Résultat extrait de la base Sirene</span>' . '<td>' . "\n";
                            $table_choices .= '</tr>' . "\n";

                            require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                            $path = '';        // This value may be used in future for external module to overwrite theme
                            $theme = 'eldy';    // Value of theme
                            if (!empty($conf->global->MAIN_OVERWRITE_THEME_RES)) {
                                $path = '/' . $conf->global->MAIN_OVERWRITE_THEME_RES;
                                $theme = $conf->global->MAIN_OVERWRITE_THEME_RES;
                            }

                            include dol_buildpath($path . '/theme/' . $theme . '/theme_vars.inc.php');
                            // Case of option availables only if THEME_ELDY_ENABLE_PERSONALIZED is on
                            $colorbackhmenu1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);

                            // Set text color to black or white
                            $colorbackhmenu1 = join(',', colorStringToArray($colorbackhmenu1));    // Normalize value to 'x,y,z'
                            $tmppart = explode(',', $colorbackhmenu1);
                            $tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
                            if ($tmpval <= 460) $colortextbackhmenu = 'FFFFFF';
                            else $colortextbackhmenu = '000000';

                            //Script to Select All Checkbox
                            $outJS = <<<SCRIPT
                <script type="text/javascript">

            $(document).ready(function() {
                //console.log("Test");
            	$("#checkallactions").click(function() {
                    if($(this).is(':checked')){
                        //console.log("We check all");
                		$(".checkforselect:not(:disabled)").prop('checked', true).trigger('change');
                    }
                    else
                    {
                        console.log("We uncheck all");
                		$(".checkforselect").prop('checked', false).trigger('change');
                    }
if (typeof initCheckForSelect == 'function') { initCheckForSelect(0); } else { console.log("No function initCheckForSelect found. Call won't be done."); }});

        	$(".checkforselect").change(function() {
				$(this).closest("tr").toggleClass("highlight", this.checked);
			});

        	$('#dialog-confirm').on( "dialogopen", function( event, ui ) {
        	    $('#dialog-confirm').parent().find('.ui-dialog-titlebar').addClass('sirene_title');
        	});
        	
            $('#dialog-confirm').on("dialogopen", function( event, ui ) {
            $('#dialog-confirm').parent().find('.ui-dialog-buttonset').addClass('sirene_remove');
            });
            
            $('#dialog-confirm').on("dialogopen", function( event, ui ) {
            $('#dialog-confirm').parent().find('.ui-dialog-buttonpane ui-widget-content ui-helper-clearfix').addClass('sirene_remove_1');
            });
            $('#dialog-confirm').on("dialogopen", function( event, ui ) {
            $('#dialog-confirm').parent().find('.liste_titre').addClass('sirene_colortitre');
            });
            
 	});
    

        </script>
        <style>
            .sirene_title {
                background-color: rgb($colorbackhmenu1) !important;
                color: #$colortextbackhmenu !important;
            }
            .sirene_colortitre{
            background-color: rgb($colorbackhmenu1) !important;
                color: #$colortextbackhmenu !important;
            }
            
        </style>
SCRIPT;

                            $table_choices .= $outJS;
                            foreach ($correspondance as $key => $infos) {
                                if ($key == 'country_id') {
                                    $table_choices .= '<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $infos['sirene'] . '">';
                                } else {
                                    $table_choices .= '<tr>';
                                    $table_choices .= '<td>' . $infos['title'] . '<td>';
                                    $table_choices .= '<td>' . $infos['dolibarr'] . '<td>';
                                    $table_choices .= '<td>' . $infos['<-'] . '<td>';
                                    //select all checkbox
                                    $table_choices .= '<td>' . '<input type="checkbox" id="' . $key . '" name="' . $key . '" class="checkforselect" value="1" ' . ($infos['dolibarr'] == $infos['sirene'] ? 'disabled="disabled"' : '') . '>' . '<td>';
                                    $table_choices .= '<td>' . $infos['sirene'] . '<td>';
                                    $table_choices .= '</tr>' . "\n";
                                }
                                $formquestion[] = array('name' => $key);
                            }
                            $table_choices .= '</table>' . "\n";
                            $table_choices .= '</div>' . "\n";
                            $formquestion = array_merge($formquestion,
                                array(
                                    array('type' => 'onecolumn', 'name' => 'company_name', 'value' => $table_choices),
                                )
                            );
                            print $form->formconfirm($_SERVER["PHP_SELF"] . '?socid=' . $object->id, $langs->trans('PopUpChangeBoxSirene'), $langs->trans(""), "confirm_sir_data", $formquestion, 'no', 1, 600, '80%');


                        }
                        else{
                            setEventMessage($langs->trans('SireneTokenAlreadyUsed'), 'warnings');
                            return 0;
                        }
                        dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 1, 'chaine', 0, '', $conf->entity);
                        //print $conf->global->SIRENE_PROCESSING_TOKEN;
                    }


                }
                //si le SIRET n'est pas renseigné
                else {
                    global $conf, $langs, $user;
                    if ($user->rights->societe->creer) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?socid=' . $object->id . '&amp;action=sir_import">' . $langs->trans('SireneButton') . '</a></div>';
                        //Traitement du bouton import tiers
                        $act = GETPOST('action', 'aZ09');

                        if ($act == 'sir_import' && $user->rights->societe->creer) {
                            //print $conf->global->SIRENE_PROCESSING_TOKEN;
                            //$resultat   =   $conf->global->SIRENE_PROCESSING_TOKEN;
                            if ($conf->global->SIRENE_PROCESSING_TOKEN == 1) {
                                //$conf->global->SIRENE_PROCESSING_TOKEN;
                                //token free
                                // if ($conf->global->SIRENE_PROCESSING_TOKEN = 0) {
                                //     dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 1, 'chaine', 0, '', $conf->entity);

                                //-----------Changement couleur title bar menu-----------------


                                require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                                $path = '';        // This value may be used in future for external module to overwrite theme
                                $theme = 'eldy';    // Value of theme
                                if (!empty($conf->global->MAIN_OVERWRITE_THEME_RES)) {
                                    $path = '/' . $conf->global->MAIN_OVERWRITE_THEME_RES;
                                    $theme = $conf->global->MAIN_OVERWRITE_THEME_RES;
                                }

                                include dol_buildpath($path . '/theme/' . $theme . '/theme_vars.inc.php');
                                // Case of option availables only if THEME_ELDY_ENABLE_PERSONALIZED is on
                                $colorbackhmenu1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);

                                // Set text color to black or white
                                $colorbackhmenu1 = join(',', colorStringToArray($colorbackhmenu1));    // Normalize value to 'x,y,z'
                                $tmppart = explode(',', $colorbackhmenu1);
                                $tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
                                if ($tmpval <= 460) $colortextbackhmenu = 'FFFFFF';
                                else $colortextbackhmenu = '000000';


                                $langs->load('sirene@sirene');
                                //$sirene_action = GETPOST('sirene_action', 'alpha');
                                $table_choices = '';
                                $table_choices .= '<tr id="sirene_infos"><td colspan="4">' . "\n";
                                $table_choices .= '<div id="sirene_block_infos">' . "\n";
                                $table_choices .= '<table class="noborder" width="100%">' . "\n";
                                $table_choices .= '<tr>' . "\n";
                                //$table_choices .= '<td colspan="6"><u>' . $langs->trans("SireneSirene") . '</u> : ' . $form->textwithpicto('', $langs->trans("SireneSearchHelp"), 2) . "\n";
                                $only_open = (isset($_POST['sirene_number']) ? (GETPOST('sirene_only_open', 'int') ? 1 : 0) : 1);

                                $table_choices .= '<br>' . $langs->trans("SireneOnlyOpen") . ' : <input type="checkbox" id="sirene_only_open" name="sirene_only_open" value="1"' . ($only_open ? ('checked="checked"') : '') . '></br>' . "\n";
                                $table_choices .= '</tr>' . "\n";
                                $table_choices .= '<tr>' . "\n";
                                $table_choices .= '<td>' . $langs->trans("SireneCompanyName") . ' : <input type="text" id="sirene_company_name" name="sirene_company_name" value="' . $object->name . '"></td>' . "\n";
                                $table_choices .= '<td>' . $langs->trans("SireneSiren") . ' : <input type="text" id="sirene_siren" name="sirene_siren" value=""></td>' . "\n";
                                $table_choices .= '<td>' . $langs->trans("SireneSiret") . ' : <input type="text" id="sirene_siret" name="sirene_siret" value=""></td>' . "\n";
                                $table_choices .= '<td>' . $langs->trans("SireneNaf") . ' ' . $form->textwithpicto('', $langs->trans("SireneSearchCodeNafHelp"), 2) . ' : <input type="text" id="sirene_naf" name="sirene_naf" value=""></td>' . "\n";
                                $table_choices .= '<td>' . $langs->trans("CompanyZip") . ' : <input type="text" id="sirene_zipcode" name="sirene_zipcode" value=""></td>' . "\n";
                                //$table_choices .= '<td>' . $langs->trans("SireneNumber") . ' : <input type="number" name="sirene_number" min="20" max="100" size="3" value="' . GETPOST('sirene_number', 'alpha') . '"></td>' . "\n";
                                $table_choices .= '</tr>' . "\n";
                                $table_choices .= '</table>' . "\n";
                                $table_choices .= '</div>' . "\n";
                                $table_choices .= '<div class="center">' . "\n";
                                $table_choices .= '<input type="submit" id="sirene_search" name="sirene_search" class="button" value="' . $langs->trans("Search") . '">' . "\n";

                                $table_choices .= '</div>' . "\n";

                                $table_choices .= '<div id="sirene_search_result">' . "\n";
                                $table_choices .= '</div>' . "\n";

                                $outJSAjax = '';
                                $outJSAjax .= '     $("input#sirene_search").addClass("butActionRefused").val("' . $langs->trans("SireneWaitingMessage") . '");';
                                $outJSAjax .= '     jQuery("#sirene_search_result").html("");';
                                $outJSAjax .= '     jQuery.ajax({';
                                $outJSAjax .= '         method: "post",';
                                $outJSAjax .= '         dataType: "json",';
                                $outJSAjax .= '         data: {';
                                $outJSAjax .= '             socid: "' . dol_escape_js($object->id) . '",';
                                $outJSAjax .= '             sirene_only_open: jQuery("#sirene_only_open").val(),';
                                $outJSAjax .= '             sirene_company_name: jQuery("#sirene_company_name").val(),';
                                $outJSAjax .= '             sirene_siren: jQuery("#sirene_siren").val(),';
                                $outJSAjax .= '             sirene_siret: jQuery("#sirene_siret").val(),';
                                $outJSAjax .= '             sirene_naf: jQuery("#sirene_naf").val(),';
                                $outJSAjax .= '             sirene_zipcode: jQuery("#sirene_zipcode").val(),';
                                $outJSAjax .= '         },';
                                $outJSAjax .= '         url: "' . dol_buildpath('/sirene/ajax/search_result.php', 1) . '",';
                                $outJSAjax .= '         error: function(response){';
                                $outJSAjax .= '             $("input#sirene_search").removeClass("butActionRefused");';
                                $outJSAjax .= '         },';
                                $outJSAjax .= '         success: function(response){';
                                $outJSAjax .= '             if (response.error > 0) {';
                                $outJSAjax .= '                 jQuery("#sirene_search_result").html(response.html)';
                                $outJSAjax .= '             } else {';
                                $outJSAjax .= '                 jQuery("#sirene_search_result").html(response.html)';
                                $outJSAjax .= '             }';
                                $outJSAjax .= '             $("input#sirene_search").removeClass("butActionRefused").val("' . $langs->trans("Search") . '");';
                                $outJSAjax .= '         }';
                                $outJSAjax .= '     });';


                                $outJS = '<script type="text/javascript">';
                                $outJS .= 'jQuery(document).ready(function(){';//2
                                // on dom ready
                                $outJS .= $outJSAjax;
                                // button search
                                $outJS .= ' jQuery("#sirene_search").click(function(){';//3
                                $outJS .= $outJSAjax;
                                $outJS .= ' });';
                                $outJS .= '});';
                                $outJS .= '</script>';
                                $outJS .= <<<SCRIPT
                        <script type="text/javascript">
                            $('#dialog-confirm').on( "dialogopen", function( event, ui ) {
                                $('#dialog-confirm').parent().find('.ui-dialog-titlebar').addClass('sirene_title');
                            });
                                    	
                            $('#dialog-confirm').on("dialogopen", function( event, ui ) {
                            $('#dialog-confirm').parent().find('.ui-dialog-buttonset').addClass('sirene_remove');
                            });
                             $('#dialog-confirm').on("dialogopen", function( event, ui ) {
                             $('#dialog-confirm').parent().find('.ui-dialog-buttonpane').addClass('sirene_remove');

                            });   
                            
                             $('#dialog-confirm').on("dialogopen", function( event, ui ) {
                             $('#dialog-confirm').parent().find('.border-bottom-color').addClass('sirene_background');

                            });  
                             $('#dialog-confirm').on("dialogopen", function( event, ui ) {
                             $('#dialog-confirm').parent().find('.noborder centpercent').addClass('sirene_colortitre');
                             $('#dialog-confirm').parent().find('.liste_titre').addClass('sirene_colortitre');
                             });

                                           
                        </script>
                            <style>
                                .sirene_title {
                                    background-color: rgb($colorbackhmenu1) !important;
                                    color: #$colortextbackhmenu !important;
                                }
                                .sirene_remove{
                                        display: none !important;
                                }
                                .sirene_background{
                                   background-color: white !important;
                                    
                                }
                                .sirene_colortitre{
                                background-color: rgb($colorbackhmenu1) !important;
                                color: #$colortextbackhmenu !important;
                                }
                                
                            </style>
SCRIPT;

                                $table_choices .= $outJS;
                                $formquestion = array(
                                    array(
                                        'type' => 'hidden',
                                        'name' => 'socid',
                                        'value' => GETPOST('socid', 'int')
                                    ),
                                    array(
                                        'type' => 'hidden',
                                        'name' => 'old_action',
                                        'value' => $action
                                    ),
                                    array(
                                        'name' => 'sirene_choice',
                                        'type' => 'onecolumn',
                                        'value' => $table_choices
                                    )
                                );
                                print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("PopUpSelectionTier"), $langs->trans("PopUpSelectionTierMsg"), "sir_import", $formquestion, '1', 1, 750, '75%');
                                // dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 0, 'chaine', 0, '', $conf->entity);
                                //}
                                //else{
                                //    setEventMessage($langs->trans('SireneTokenAlreadyUsed'), 'warnings');
                                //    return 0;
                                //}
                            } else {
                                setEventMessage($langs->trans('SireneTokenAlreadyUsed'), 'warnings');
                                return 0;
                            }
                            dolibarr_set_const($this->db, "SIRENE_PROCESSING_TOKEN", 1, 'chaine', 0, '', $conf->entity);
                            //print $conf->global->SIRENE_PROCESSING_TOKEN;
                        }
                    }
                }

            }
            //token is already used
            //}
        }
    }
}
