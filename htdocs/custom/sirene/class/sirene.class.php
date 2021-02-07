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

/**
 * \file    htdocs/sirene/class/sirene.class.php
 * \ingroup sirene
 * \brief
 */

if (!class_exists('ComposerAutoloaderInite5f8183b6b110d1bbf5388358e7ebc94', false)) dol_include_once('/sirene/vendor/autoload.php');
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Sirene
 *
 * Put here description of your class
 */
class Sirene
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
     * @var Client      Client REST handler
     */
    public $client;

    /**
     * @var array Result of the request to get companies with the Sirene API
     */
    public $companies_results = array();

    /**
     * Constructor
     *
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Connect to the sirene API
     *
     * @param   string      $url        Url for the connection to the Sirene API
     * @return	int		                <0 if KO, >0 if OK
     */
    public function connection()
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $this->errors = array();

        try {
            $this->client = new Client([
                // Base URI is used with relative requests
                'base_uri' => $conf->global->SIRENE_API_URL,
                // You can set any number of default request options.
                'timeout' => $conf->global->SIRENE_API_TIMEOUT,
            ]);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     *  Get all companies found when search with the sirene API
     * @param   string          $company_name   Search by company name
     * @param   string          $siren          Search by siren
     * @param   string|array    $siret          Search by siret or a list of siret
     * @param   string          $naf            Search by naf
     * @param   string          $zipcode        Search by zip code
     * @param   string          $nombre         Maximum number of results
     * @param   int             $only_open      Search only open
     * @return  int                             <0 if KO, >0 if OK
     */
    function getCompanies($company_name, $siren, $siret, $naf, $zipcode, $nombre, $only_open=0)
    {
        global $conf, $langs;

        dol_include_once('/sirene/lib/sirene.lib.php');
        $langs->load('sirene@sirene');
        $dict_id = sirene_codenaf_dict_id();

        $siret = is_array($siret) ? $siret : (!empty($siret) ? array($siret) : array());

        $this->companies_results = array();
        $filter = array();
        if ($company_name !== '') $filter[] = 'raisonSociale:"' . str_replace('"', '\\"', $company_name) . '"';
        if ($siren !== '') $filter[] = 'siren:' . $siren;
        if (!empty($siret)) $filter[] = '(siret:' . implode(' OR siret:', $siret) . ')';
        if ($naf !== '') $filter[] = 'activitePrincipaleUniteLegale:' . $naf;
        if ($zipcode !== '') $filter[] = 'codePostalEtablissement:' . $zipcode;
        if ($only_open) $filter[] = '-periode(etatAdministratifEtablissement:F)';
        //AND -periode(etatAdministratifEtablissement:F)
        //if ($only_open) $filter [ ]

        try {
            $response = $this->client->get('siret', [
                'headers' => ['Authorization' => 'Bearer ' . $conf->global->SIRENE_API_BEARER_KEY],
                GuzzleHttp\RequestOptions::QUERY => ['q' => implode(' AND ', $filter), 'nombre'=> $nombre]
            ]);
            $results = json_decode($response->getBody()->getContents(), true);
            $results = isset($results['etablissements']) ? $results['etablissements'] : array();
            foreach ($results as $company_infos) {
                // Morale
                $company_name = $company_name_all = $company_infos['uniteLegale']['denominationUniteLegale'];
                if (!empty($company_name)) {
                    $company_name_alias = $company_infos['uniteLegale']['denominationUsuelle1UniteLegale'];
                    if (empty($company_name_alias)) $company_name_alias = $company_infos['uniteLegale']['denominationUsuelle2UniteLegale'];
                    if (empty($company_name_alias)) $company_name_alias = $company_infos['uniteLegale']['denominationUsuelle3UniteLegale'];
                    if ($company_name == $company_name_alias) $company_name_alias = '';

                    $private = 0;
                } else { // Physique
                    $company_name = $company_infos['uniteLegale']['nomUniteLegale'];
                    if (empty($company_name)) $company_name = $company_infos['uniteLegale']['nomUsageUniteLegale'];

                    $company_name_alias = '';
                    $firstname = $company_infos['uniteLegale']['prenomUsuelUniteLegale'];
                    if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom1UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom1UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom1UniteLegale'];
                    if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom2UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom2UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom2UniteLegale'];
                    if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom3UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom3UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom3UniteLegale'];
                    if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom4UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom4UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom4UniteLegale'];
                    $company_name_alias = trim($company_name_alias);

                    $civility = $company_infos['uniteLegale']['sexeUniteLegale'] == 'M' ? 'MR' : 'MME';
                    $civility_all = $company_infos['uniteLegale']['sexeUniteLegale'] == 'M' ? 'M.' : 'Mme';

                    $company_name_all = $civility_all . ' ' . $firstname . ' ' . $company_name;
                    $private = 1;
                }
                if (!empty($company_name_alias)) $company_name_all .= ' (' . $company_name_alias . ')';

                $date_creation = $company_infos['dateCreationEtablissement'];
                if (!empty($date_creation)) {
                    $date_tmp = strtotime($date_creation);
                    $date_creation = dol_print_date($date_tmp, 'day');
                } else {
                    $date_creation = 'N/A';
                }
                $status = '';
                $status_all = '';
                $firstPeriodeEtablissementInfo = array_values($company_infos['periodesEtablissement']);
                if (!empty($firstPeriodeEtablissementInfo)){
                    $firstPeriodeEtablissementInfo = $firstPeriodeEtablissementInfo[0];
                    $status = $firstPeriodeEtablissementInfo['etatAdministratifEtablissement'];

                    if ($status != "A") {
                        $status_all = '<span style="color:red;">'.$langs->trans('SireneEstablishmentClosed').'</span>';
                    } else {
                        $status_all = '<span style="color:green;">'.$langs->trans('SireneEstablishmentOpened').'</span>';
                    }
                }
                $address_all = $company_infos['adresseEtablissement']['numeroVoieEtablissement'];
                $address_all .= $company_infos['adresseEtablissement']['indiceRepetitionEtablissement'];
                $address_all .= ' ' . $company_infos['adresseEtablissement']['typeVoieEtablissement'];
                $address_all .= ' ' . $company_infos['adresseEtablissement']['libelleVoieEtablissement'];
                $address = $address_all;
                $address_all .= ' ' . $company_infos['adresseEtablissement']['codePostalEtablissement'];
                $zip_code = $company_infos['adresseEtablissement']['codePostalEtablissement'];
                $address_all .= ' ' . $company_infos['adresseEtablissement']['libelleCommuneEtablissement'];
                $town = $company_infos['adresseEtablissement']['libelleCommuneEtablissement'];
                $address_all .= ' ' . $company_infos['adresseEtablissement']['libelleCommuneEtrangerEtablissement'];
                $address_all .= ' ' . $company_infos['adresseEtablissement']['distributionSpecialeEtablissement'];
                $address_all .= ' ' . $company_infos['adresseEtablissement']['libelleCedexEtablissement'];
                $address_all .= ' ' . $company_infos['adresseEtablissement']['libellePaysEtrangerEtablissement'];

                $siren = $company_infos['siren'];
                $siret = $company_infos['siret'];

                $codenaf_all = $codenaf = $company_infos['uniteLegale']['activitePrincipaleUniteLegale'];
                $codenaf_san = preg_replace('[\W]', '', $company_infos['uniteLegale']['activitePrincipaleUniteLegale']);
                $codenaf_san = str_pad($codenaf_san, 5, "0", STR_PAD_LEFT);

                $sql = "SELECT label FROM " . MAIN_DB_PREFIX . "c_codenaf WHERE code = '" . $this->db->escape($codenaf_san) . "'";
                $resql = $this->db->query($sql);
                if ($resql) {
                    if ($obj = $this->db->fetch_object($resql)) {
                        $codenaf_all .= ' - ' . $obj->label;
                    } else {
                        $codenaf_all .= ' - ' . $langs->trans('CodeNafNotFound', dol_buildpath('/admin/dict.php', 1) . '?id=' . $dict_id);
                    }
                }
                $state_all = $state = $company_infos['adresseEtablissement']['codePostalEtablissement'];
                $state_san = preg_replace('[\W]', '', $company_infos['adresseEtablissement']['codePostalEtablissement']);
                $state_san = substr(str_pad($state_san, 5, "0", STR_PAD_LEFT), 0, 3);
                if($state_san < 970) $state_san = substr($state_san, 0, 2); // Compatibilité DOM-TOM
                $state_id = dol_getIdFromCode($this->db, $state_san, 'c_departements', 'code_departement', 'rowid', 0);
                $country_san = $company_infos['adresseEtablissement']['codePaysEtrangerEtablissement'];
                if (empty($country_san)) {
                    $country_san = -1;
                }
                $country_code = dol_getIdFromCode($this->db, $country_san, 'c_sirene_country', 'code_sirene', 'country_code', 0);
                if ($country_code !== -1) {
                    require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
                    $country = getCountry($country_code);
                    $country_id = dol_getIdFromCode($this->db, $country_code, 'c_sirene_country', 'country_code', 'rowid', 0);
                } else {
                    $country_code = '';
                    $country = $langs->trans('Unknown') . ' - ID: ' . $country_san . " - Name: " . $company_infos['adresseEtablissement']['libellePaysEtrangerEtablissement'];
                    $country_id = 0;
                }

                //$company_infos['tva_intra'] = '';
                //unset($company_infos['tva_intra']);
                //unset($tva_intra);
                $tva_intra = '';
                if ($country_code == 'FR') {
                    // intra-community vat number calculation
                    $coef = 97;
                    $vatintracalc = fmod($company_infos['siren'], $coef);
                    $vatintracalc2 = fmod((12 + 3 * $vatintracalc), $coef);
                    $tva_intra = 'FR' . str_pad($vatintracalc2, 2, 0, STR_PAD_LEFT) . $company_infos['siren'];
                }

                $this->companies_results[] = array(
                    'company_name' => $company_name,
                    'firstname' => $firstname,
                    'civility' => $civility,
                    'company_name_alias' => $company_name_alias,
                    'private' => $private,
                    'company_name_all' => $company_name_all,
                    'date_creation' => $date_creation,
                    'status' => $status,
                    'status_all' => $status_all,
                    'address' => $address,
                    'zipcode' => $zip_code,
                    'town' => $town,
                    'state' => $state,
                    'state_id' => $state_id,
                    'state_san' => $state_san,
                    'state_all' => $state_all,
                    'address_all' => $address_all,
                    'siren' => $siren,
                    'siret' => $siret,
                    'codenaf' => $codenaf,
                    'codenaf_san' => $codenaf_san,
                    'codenaf_all' => $codenaf_all,
                    'country' => $country,
                    'country_san' => $country_san,
                    'country_id' => $country_id,
                    'country_code' => $country_code,
                    'sirene_tva_intra' => $tva_intra,
                );
            }

            return 1;
        } catch (RequestException $e) {
            $request = $e->getRequest();
            $response = $e->getResponse();

            if (isset($response)) {
                if ($response->getStatusCode() == 404) return 1;

                if (!empty($conf->global->SIRENE_DEBUG)) {
                    if (isset($request)) $this->errors[] = $this->_requestToString($request);
                    if (isset($response)) $this->errors[] = $this->_responseToString($response);
                    else $this->errors[] = '<pre>' . dol_nl2br((string)$e) . '</pre>';
                } else {
                    $this->errors[] = '<b>' . $langs->trans('SireneResponseCode') . ': </b>' . $response->getStatusCode() . '<br>' .
                        '<b>' . $langs->trans('SireneResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() . '<br>';
                }
            } else {
                if (!empty($conf->global->SIRENE_DEBUG)) {
                    $this->errors[] = (string)$e;
                } else {
                    $this->errors[] = $e->getMessage();
                }
            }

            dol_syslog(__METHOD__ . " Error: " . dol_htmlentitiesbr_decode($this->errorsToString('<br>')), LOG_ERR);
            return -1;
        } catch (Exception $e) {
            if (!empty($conf->global->SIRENE_DEBUG)) {
                $this->errors[] = (string)$e;
            } else {
                $this->errors[] = $e->getMessage();
            }

            dol_syslog(__METHOD__ . " Error: " . $e, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Format the request to a string
     *
     * @param   RequestInterface    $request    Request handler
     * @return	string		                    Formatted string of the request
     */
    protected function _requestToString(RequestInterface $request)
    {
        global $langs;

        $out = '';
        $out .= '<b>' . $langs->trans('SireneRequestData') . ': </b><br><hr>';
        $out .= '<b>' . $langs->trans('SireneRequestProtocolVersion') . ': </b>' . $request->getProtocolVersion() . '<br>';
        $out .= '<b>' . $langs->trans('SireneRequestUri') . ': </b>' . $request->getUri() . '<br>';
        $out .= '<b>' . $langs->trans('SireneRequestTarget') . ': </b>' . $request->getRequestTarget() . '<br>';
        $out .= '<b>' . $langs->trans('SireneRequestMethod') . ': </b>' . $request->getMethod() . '<br>';
        $out .= '<b>' . $langs->trans('SireneRequestHeaders') . ':</b><ul>';
        foreach ($request->getHeaders() as $name => $values) {
            $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
        }
        $out .= '</ul>';
        $out .= '<b>' . $langs->trans('SireneRequestBody') . ': </b>';
        $out .= '<br><em>' . $request->getBody() . '</em><br>';
        return $out;
    }

    /**
     *  Format the response to a string
     *
     * @param   ResponseInterface   $response   Response handler
     * @return	string		                    Formatted string of the response
     */
    protected function _responseToString(ResponseInterface $response)
    {
        global $langs;

        $out = '';
        $out .= '<b>' . $langs->trans('SireneResponseData') . ': </b><br><hr>';
        $out .= '<b>' . $langs->trans('SireneResponseProtocolVersion') . ': </b>' . $response->getProtocolVersion() . '<br>';
        $out .= '<b>' . $langs->trans('SireneResponseCode') . ': </b>' . $response->getStatusCode() . '<br>';
        $out .= '<b>' . $langs->trans('SireneResponseReasonPhrase') . ': </b>' . $response->getReasonPhrase() . '<br>';
        $out .= '<b>' . $langs->trans('SireneResponseHeaders') . ':</b><ul>';
        foreach ($response->getHeaders() as $name => $values) {
            $out .= '<li><b>' . $name . ': </b>' . implode(', ', $values) . '</li>';
        }
        $out .= '</ul>';
        $out .= '<b>' . $langs->trans('SireneResponseBody') . ': </b>';
        $body = json_decode($response->getBody(), true);
        if (is_array($body)) {
            $out .= '<ul>';
            foreach ($body as $name => $values) {
                $out .= '<li><b>' . $name . ': </b>' . (is_array($values) || is_object($values) ? json_encode($values) : $values) . '</li>';
            }
            $out .= '</ul>';
        } else {
            $out .= '<br><em>' . $response->getBody() . '</em><br>';
        }
        return $out;
    }

    /**
     * Method to output saved errors
     *
     * @param   string      $separator      Separator between each error
     * @return	string		                String with errors
     */
    public function errorsToString($separator = ', ')
    {
        return (is_array($this->errors) ? join($separator, $this->errors) : '');
    }


    /**
     *  conditionDate
     *
     *  @param 	string	$Field		Field operand 1
     *  @param 	string	$Value		Value operand 2
     *  @param 	string	$Sens		Comparison operator
     *  @return string
     */
    public function conditionDate($Field, $Value, $Sens)
    {
        // TODO date_format is forbidden, not performant and not portable. Use instead BETWEEN
        if (strlen($Value)==4) $Condition=" date_format(".$Field.",'%Y') ".$Sens." '".$Value."'";
        elseif (strlen($Value)==6) $Condition=" date_format(".$Field.",'%Y%m') ".$Sens." '".$Value."'";
        else  $Condition=" date_format(".$Field.",'%Y%m%d') ".$Sens." ".$Value;
        return $Condition;
    }

    //tache planifiée pour limiter les requetes
    public function cronSirene()
    {
        global $user, $langs, $sirene, $conf;
        $langs->load('sirene@sirene');
        $error = 0;
        $output = '';
        $country_san_sirene=$this->country_san;

        if (empty($conf->global->SIRENE_PROCESSING_SYNCHRONIZATION)) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
            dolibarr_set_const($this->db, 'SIRENE_PROCESSING_SYNCHRONIZATION', dol_print_date(dol_now(), 'dayhour'), 'chaine', 1, 'Token the processing of the synchronization of the third parties', 0);

            // Get all companies to check with sirene
            $sql = "SELECT s.rowid";
            $sql .= ", " . $this->db->ifsql("cte.id IS NOT NULL AND cte.code = 'TE_PRIVATE'", 1, 0). " as private";
            $sql .= ", s.nom as name, s.name_alias, s.address, s.zip, s.town";
            $sql .= ", s.fk_departement as state_id, s.fk_pays as country_id, s.siren as idprof1";
            $sql .= ", s.siret as idprof2, s.ape as idprof3, s.tva_intra";
            $sql .= " FROM " . MAIN_DB_PREFIX . "societe AS s";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields AS sef ON sef.fk_object = s.rowid";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_typent AS cte ON cte.id = s.fk_typent";
            $sql .= " WHERE s.status = 1";
            $sql .= " AND (sef.sirene_status = 0 OR sef.sirene_status IS NULL)";
            $sql .= " AND s.siret != ''";
            $sql .= " ORDER BY rowid ASC";

            $resql = $this->db->query($sql);
            if (!$resql) {
                dolibarr_del_const($this->db, 'SIRENE_PROCESSING_SYNCHRONIZATION', 0);
                $this->error = 'Error ' . $this->db->lasterror();
                $this->errors = array();
                dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
                return -1;
            }

            dol_include_once('/sirene/class/sirene.class.php');
            $sirene = new Sirene($this->db);

            // Connection to sirene API
            $result = $sirene->connection();
            if ($result < 0) {
                dolibarr_del_const($this->db, 'SIRENE_PROCESSING_SYNCHRONIZATION', 0);
                $this->error = $langs->trans('SireneErrorWhileConnect') . ': ' . $sirene->errorsToString();
                $this->errors = array();
                dol_syslog(__METHOD__ . " Error: " . $this->error, LOG_ERR);
                return -1;
            }

            $correspondance = array(
                'private' => 'private',
                'company_name' => 'name',
                'company_name_alias' => 'name_alias',
                'address' => 'address',
                'zipcode' => 'zip',
                'town' => 'town',
                'state_id' => 'state_id',
                'country_id' => 'country_id',
                'siren' => 'idprof1',
                'siret' => 'idprof2',
                'codenaf_san' => 'idprof3',
                'tva_intra' => 'tva_intra',
            );

            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $nb_request_by_group = !empty($conf->global->SIRENE_NB_REQUEST_BY_GROUP) ? $conf->global->SIRENE_NB_REQUEST_BY_GROUP : 100;
            $nb_request_by_time_limit = !empty($conf->global->SIRENE_NB_REQUEST_BY_TIME_LIMIT) ? $conf->global->SIRENE_NB_REQUEST_BY_TIME_LIMIT : 25;
            $time_limit_all_request = !empty($conf->global->SIRENE_TIME_LIMIT_ALL_REQUEST) ? $conf->global->SIRENE_TIME_LIMIT_ALL_REQUEST : 60;
            $idx = 0;
            $date_start = time(); // seconds


            $table_choices = array();

            while (true) {
                $idx++;

                // Make a group of companies to check
                $group_size = 0;
                $companies_to_check = array();
                while ($group_size < $nb_request_by_group && $obj = $this->db->fetch_object($resql)) {
                    if (isset($companies_to_check[$obj->idprof2])) {
                        dol_syslog(__METHOD__ . " Warning: Same siret {$obj->idprof2} for company {$companies_to_check[$obj->idprof2]->name} (ID : {$companies_to_check[$obj->idprof2]->rowid}) and company {$obj->name} (ID : {$obj->rowid})", LOG_WARNING);
                        $output .= '<span style="color: orangered;">' . " Warning: Same siret {$obj->idprof2} for company {$companies_to_check[$obj->idprof2]->name} (ID : {$companies_to_check[$obj->idprof2]->rowid}) and company {$obj->name} (ID : {$obj->rowid})" . '</span>' . "<br>";
                    } else {
                        $companies_to_check[$obj->idprof2] = $obj;
                        $group_size++;
                    }
                }
                if (empty($companies_to_check)) break;

                // Get companies infos from sirene by siret
                $siret_to_check = array_keys($companies_to_check);
                $result = $sirene->getCompanies('', '', $siret_to_check, '', '', '');
                if ($result < 0) {
                    dol_syslog(__METHOD__ . " Error: " . $langs->trans('SireneErrorWhileGetCompaniesBySiret', implode(', ', $siret_to_check)) . ': ' . $sirene->errorsToString(), LOG_ERR);
                    $output .= '<span style="color: red;">' . $langs->trans('SireneErrorWhileGetCompaniesBySiret', implode(', ', $siret_to_check)) . '<br>' . $sirene->errorsToString('<br>') . '</span>' . "<br>";
                    $error++;
                } elseif (!empty($sirene->companies_results)) {
                    foreach ($sirene->companies_results as $company_infos) {
                        $company_sql_infos = $companies_to_check[$company_infos['siret']];

                        // Check if has properties to update
                        $modified = false;
                        foreach ($correspondance as $sirene_key => $property_name) {
                            $siren_value = $company_infos[$sirene_key];
                            $property_value = $company_sql_infos->$property_name;

                            if ($siren_value != $property_value) {
                                $modified = true;
                                break;
                            }
                        }

                        if ($company_infos['status'] == "F") {
                            //tiers fermé

                            $table_choice = '<p>' . $langs->trans('CompanySireneMail') . ' : ' . $company_infos['company_name_all'] . '</p>';
                            $table_choice .= '<p>' . $langs->trans('AdressSireneMail') . ' : ' . $company_infos['address_all'] . '</p>';
                            $table_choice .= '<p>' . $langs->trans('DateCreationSireneMail') . ' : ' . $company_infos['date_creation'] . '</p>';
                            $table_choice .= '<p>' . $langs->trans('StatusSireneMail') . ' : ' . $company_infos['status_all'] . '</p>';
                            $table_choice .= '<p>' . $langs->trans('CodenafSireneMail') . ' : ' . $company_infos['codenaf_all'] . '</p>';
                            $table_choice .= '<p>' . $langs->trans('SirenSireneMail') . ' : ' . $company_infos['siren'] . '</p>';
                            $table_choice .= '<p>' . $langs->trans('SiretSireneMail') . ' : ' . $company_infos['siret'] . '</p>' . "\n";
                            $table_choice .= '<p>' . $langs->trans('MailLinkClosedCompany') . ' : </p><br />';
                            $table_choice .= '<li><a href="' . dol_buildpath('/societe/card.php', 2) . '?socid=' . $company_sql_infos->rowid . '">' . $company_infos['company_name_all'] . '</a></li>';//$langs->trans('SireneMessageClosed')
                            $table_choices[] = $table_choice;

                            // update status to close
                            $company = new Societe($this->db);
                            $company->fetch($company_sql_infos->rowid);
                            $company->status = 0;
                            $result = $company->update();
                            if ($result < 0) {
                                dol_syslog(__METHOD__ . " Error: " . $langs->trans('SireneErrorWhileUpdateSireneStatus', $obj->name, $obj->rowid) . ': ' . $sirene->errorsToString(), LOG_ERR);
                                $output .= '<span style="color: red;">' . $langs->trans('SireneErrorWhileUpdateSireneStatus', $obj->name, $obj->rowid) . '<br>' . $sirene->errorsToString('<br>') . '</span>' . "<br>";
                                $error++;
                            }
                        }

                        // Has properties to update
                        if ($modified) {
                            $company = new Societe($this->db);
                            $company->id = $company_sql_infos->rowid;
                            $company->array_options['options_sirene_status'] = 1;
                            $result = $company->updateExtraField('sirene_status');
                            if ($result < 0) {
                                dol_syslog(__METHOD__ . " Error: " . $langs->trans('SireneErrorWhileUpdateSireneStatus', $obj->name, $obj->rowid) . ': ' . $sirene->errorsToString(), LOG_ERR);
                                $output .= '<span style="color: red;">' . $langs->trans('SireneErrorWhileUpdateSireneStatus', $obj->name, $obj->rowid) . '<br>' . $sirene->errorsToString('<br>') . '</span>' . "<br>";
                                $error++;
                            }
                        }
                    }
                }

                // Sleep when more request than x by y second
                $elapsed_time = time() - $date_start;
                if ($elapsed_time > $time_limit_all_request) {
                    $idx = 0;
                    $date_start = time(); // seconds
                } elseif ($idx >= $nb_request_by_time_limit && $elapsed_time <= $time_limit_all_request) {
                    sleep($time_limit_all_request - $elapsed_time);

                    $idx = 0;
                    $date_start = time(); // seconds
                }
            }
            $this->db->free($resql);

            // envoi du mail que si au moins une societe a ferme
            if (!empty($conf->global->SIRENE_MAIL_TO_SEND) && !empty($table_choices)) {
                // Init to avoid errors
                $filepath = array();
                $filename = array();
                $mimetype = array();

                // Send email to assigned user
                $subject = '[' . $conf->global->MAIN_INFO_SOCIETE_NOM . '] ' . $langs->transnoentities('SireneMailCompaniesClosed');
                $message = '<p>' . $langs->trans('SireneMailCompaniesClosedBody') . '</p>' . "\n";

                //ALERTE SI TIERS FERMEE


                //$table_choices1 .= ' value="'.$langs->trans('SiretSireneMail')         .' : '  . $comapiny_infos['siret'] . '"></td>' . "\n";


                //$table_choices1 .= '</tr>' . "\n";

                $message .= implode('<br /><br />', $table_choices);

                // // Extrafields
                // if (is_array($object->array_options) && count($object->array_options) > 0) {
                //     foreach ($object->array_options as $key => $value) {
                //         $message .= '<li>' . $langs->trans($key) . ' : ' . $value . '</li>';
                //     }
                // }


                $mainstmpid=(!empty($conf->global->MAIN_MAIL_SMTPS_ID_EMAILING)?$conf->global->MAIN_MAIL_SMTPS_ID_EMAILING:'');
                $sendto = dol_escape_htmltag($conf->global->SIRENE_MAIL_TO_SEND);
                $from = dolGetFirstLastname($user->firstname, $user->lastname) . '<' . $conf->global->MAIN_MAIL_EMAIL_FROM  . '>';// $user->email

                $message = dol_nl2br($message);

                //if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
                //    $old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
                //    $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
                //}
                include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, '', '', 0, -1);
                if ($mailfile->error) {
                    setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                } else {
                    $result = $mailfile->sendfile();
                }
                //if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
                //    $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
                //}
            }

            dolibarr_del_const($this->db, 'SIRENE_PROCESSING_SYNCHRONIZATION', 0);

            if ($error) {
                $this->error = $output;
                $this->errors = array();
                return -1;
            } else {
                $output .= $langs->trans('SireneCheckInfosSuccess');
            }




        } else {
            $output .= $langs->trans('SireneAlreadyProcessingCheckInfos') . ' (' . $langs->trans('SireneSince') . ' : ' . $conf->global->SIRENE_PROCESSING_SYNCHRONIZATION . ')';
        }

        $this->error = "";
        $this->errors = array();
        $this->output = $output;
        $this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => "");

        return 0;
    }
}