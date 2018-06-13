<?php
/*
 * ZenFusion Maps - A Google Maps module for Dolibarr
 * Copyright (C) 2013       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014-2016  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

/**
 * Class ActionsZenFusionMaps
 */
// @codingStandardsIgnoreStart Dolibarr can't import classes in a namespace yet.
class ActionsZenFusionMaps
// @codingStandardsIgnoreEnd
{
    /**
     * @var DoliDB Database handler
     */
    private $db;

    /**
     * @var string HTML displayed as a result of hook call
     */
    public $resprints;

    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get Google locale matching Dolibarr language
     *
     * @return string Google equivalent locale
     */
    public function getGoogleLocale()
    {
        global $langs;
        $lang = $langs->getDefaultLang();
        switch ($lang) {
            case 'ar_SA':
                $locale = 'ar';
                break;
            case 'bg_BG':
                $locale = 'bg';
                break;
            case 'ca_ES':
                $locale = 'ca';
                break;
            case 'da_DK':
                $locale = 'da';
                break;
            case 'de_AT':
            case 'de_DE':
                $locale = 'de';
                break;
            case 'el_GR':
                $locale = 'el';
                break;
            case 'en_AU':
            case 'en_IN':
            case 'en_NZ':
            case 'en_SA':
            case 'en_US':
                $locale = 'en';
                break;
            case 'en_GB':
                $locale = 'en-GB';
                break;
            case 'es_AR':
            case 'es_HN':
            case 'es_MX':
            case 'es_PE':
            case 'es_PR':
                $locale = 'es-419';
                break;
            case 'es_ES':
                $locale = 'es';
                break;
            case 'et_EE':
                $locale = 'et';
                break;
            case 'fa_IR':
                $locale = 'fa';
                break;
            case 'fi_FI':
                $locale = 'fi';
                break;
            case 'fr_BE':
            case 'fr_CH':
            case 'fr_FR':
                $locale = 'fr';
                break;
            case 'fr_CA':
                $locale = 'fr-CA';
                break;
            case 'he_IL':
                $locale = 'iw';
                break;
            case 'hu_HU':
                $locale = 'hu';
                break;
            case 'is_IS':
                $locale = 'is';
                break;
            case 'it_IT':
                $locale = 'it';
                break;
            case 'ja_JP':
                $locale = 'ja';
                break;
            case 'nb_NO':
                $locale = 'no';
                break;
            case 'nl_BE':
            case 'nl_NL':
                $locale = 'nl';
                break;
            case 'pl_PL':
                $locale = 'pl';
                break;
            case 'pt_BR':
                $locale = 'pt-BR';
                break;
            case 'pt_PT':
                $locale = 'pt-PT';
                break;
            case 'ro_RO':
                $locale = 'ro';
                break;
            case 'ru_UA':
            case 'ru_RU':
                $locale = 'ru';
                break;
            case 'sl_SI':
                $locale = 'sl';
                break;
            case 'sv_SE':
                $locale = 'sv';
                break;
            case 'tr_TR':
                $locale = 'tr';
                break;
            case 'zh_CN':
                $locale = 'zh-CN';
                break;
            case 'zh_TW':
                $locale = 'zh-tw';
                break;
            default:
                $locale = 'en';
        }

        return $locale;
    }

    /**
     * Hook replacing address output
     *
     * @param string $parameters Hook parameters
     * @param CommonObject $object Current object
     * @param string $action Current action
     *
     * @return int Status
     */
    public function printAddress($parameters, $object, &$action)
    {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

        /**
         * @var $object string The address
         */
        $element = $parameters['element'];
        $id = $parameters['id'];

        $staticobject = null;
        if ('thirdparty' == $element || 'societe' == $element) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $staticobject = new Societe($this->db);
        } elseif ($element == 'contact') {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            $staticobject = new Contact($this->db);
        } elseif ($element == 'member') {
            require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
            $staticobject = new Adherent($this->db);
        } else {
            // Not for us, bail out without error
            return 0;
        }

        // Get the full object so we can extract zip, town and country
        $staticobject->fetch($id);

        $dolibarr_version = versiondolibarrarray();

        $maps_address = $object;

        // Get rid of unwanted line returns
        $maps_address = str_replace('<br>', ' ', $maps_address);
        $maps_address = str_replace("\n", ' ', $maps_address);


        if (($dolibarr_version[0] == 3 && $dolibarr_version[1] <= 8) || $dolibarr_version[0] < 3) { // DOL_VERSION <= 3.8

            $maps_address .= ' ' . $staticobject->zip;

            // Filter out CEDEXes because Google can't process them
            $maps_address .= ' ' . preg_replace('/\sCEDEX\s.*$/i', '', $staticobject->town);
            $maps_address .= ' ' . $staticobject->country;

            // Build maps search query from the fields
            $maps_address = str_replace(' ', '+', $maps_address);
        } else { // DOL_VERSION > 3.8
            // Filter out CEDEXes because Google can't process them
            $maps_address = ' ' . preg_replace('/\sCEDEX\s.*$/i', '', $maps_address);

            // Build maps search query from the fields
            $maps_address = str_replace(', ', '+', $maps_address);
        }

        // Build localized Google Maps URL
        $googleurl = 'https://maps.google.com/maps?q=' . $maps_address . '&hl=' . $this->getGoogleLocale();

        // Print enhanced address with a link to Google Maps
        $this->resprints = '<a href="' . $googleurl . '" target="_blank">';

        if (($dolibarr_version[0] == 3 && $dolibarr_version[1] <= 8) || $dolibarr_version[0] < 3) { // DOL_VERSION <= 3.8
            // Add a nice Google Maps marker picto to let the user know the feature is there
            $picto = img_picto(
                'Google Maps',
                dol_buildpath('/zenfusionmaps/img/marker.png', 1),
                '',
                true
            );

            // FIXME: move style to a proper CSS file if possible
            $this->resprints .= '<div style="float: left; margin-right: 5px;">' . $picto . '</div>';
        }

        // Keep original adress formatting
        $this->resprints .= nl2br($object);

        $this->resprints .= '</a>';

        return 1; // Replace Dolibarr output
    }
}
