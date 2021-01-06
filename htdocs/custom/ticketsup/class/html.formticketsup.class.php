<?php
/* Copyright (C) - 2013-2015    Jean-François FERRY    <jfefe@aternatik.fr>
 *                    2016            Christophe Battarel <christophe@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       ticketsup/class/html.ticketsup.class.php
 *       \ingroup    core
 *       \brief      Fichier de la classe permettant la generation du formulaire html d'envoi de mail unitaire
 */
require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formmail.class.php";

if (!class_exists('FormCompany')) {
    include DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
}

/**
 * Classe permettant la generation du formulaire d'un nouveau ticket
 *
 * @package ticketsup

 * \remarks Utilisation: $formticketsup = new FormTicketsup($db)
 * \remarks $formticketsup->proprietes=1 ou chaine ou tableau de valeurs
 * \remarks $formticketsup->show_form() affiche le formulaire
 */
class FormTicketsup
{
    public $db;

    public $track_id;
    public $fk_user_create;

    public $message;
    public $topic_title;

    public $action;

    public $withtopic;
    public $withemail;
    /**
     *
     * @var int $withsubstit Show substitution array
     */
    public $withsubstit;

    public $withfile;

    public $ispublic; // To show information or not into public form

    public $withtitletopic;
    public $withcompany; // affiche liste déroulante company
    public $withfromsocid;
    public $withusercreate; // Show name of creating user in form
    public $withcreatereadonly;

    public $withref; // Show ref field

    public $withcancel;

    /**
     *
     * @var array $substit Substitutions
     */
    public $substit = array();
    public $param = array();

    public $error;


    /**
     * Constructor
     *
     * @param DoliDB $DB Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->action = 'add_ticket';

        $this->withcompany = 1;
        $this->withfromsocid = 0;
        //$this->withthreadid=0;
        //$this->withtitletopic='';
        $this->withusercreate = 1;
        $this->withcreatereadonly = 1;
        $this->withemail = 0;
        $this->withref = 0;
        $this->withextrafields = 0;         // Show extrafields or not
        //$this->withtopicreadonly=0;

        return 1;
    }

    /**
     * Show the form to input ticket
     *
     * @param  string $width Width of form
     * @return void
     */
    public function showForm($width = '100%')
    {
        global $conf, $langs, $user, $hookmanager;

        $langs->load("other");
        $langs->load("mails");
        $langs->load("ticketsup@ticketsup");

        $form = new Form($this->db);
        $formcompany = new FormCompany($this->db);
        $ticketstatic = new Ticketsup($this->db);

        $soc = new Societe($this->db);
        if (!empty($this->withfromsocid) && $this->withfromsocid > 0) {
            $soc->fetch($this->withfromsocid);
        }

        $ticketstat = new TicketSup($this->db);

        $extrafields = new ExtraFields($this->db);
        $extralabels = $extrafields->fetch_name_optionals_label($ticketstat->table_element);

        print "\n<!-- Begin form TICKETSUP -->\n";

        print '<form method="post" name="ticketsup" id="form_create_ticket" enctype="multipart/form-data" action="' . $this->param["returnurl"] . '">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="action" value="' . $this->action . '">';
        foreach ($this->param as $key => $value) {
		print '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        print '<input type="hidden" name="fk_user_create" value="' . $this->fk_user_create . '">';

        print '<table class="border"  width="' . $width . '">';


        if ($this->withref) {
            // Ref
            $defaultref = $ticketstat->getDefaultRef();
            print '<tr><td><span class="fieldrequired">' . $langs->trans("Ref") . '</span></td><td><input size="18" type="text" name="ref" value="' . (GETPOST("ref", 'alpha') ? GETPOST("ref", 'alpha') : $defaultref) . '"></td></tr>';
        }

        // FK_USER_CREATE
        if ($this->withusercreate > 0 && $this->fk_user_create) {
            print '<tr><td width="35%">' . $langs->trans("CreatedBy") . '</td><td>';
            $langs->load("users");
            $fuser = new User($this->db);

            if ($this->withcreatereadonly) {
                if ($res = $fuser->fetch($this->fk_user_create)) {
                    print $fuser->getNomUrl(1);
                }
            }
            print ' &nbsp; ';
            print "</td></tr>\n";
        }

        // Customer
        if ($this->withcompany) {
            // altairis: force company and contact id for external user
            if (empty($user->socid)) {
                // Company
                print '<tr><td>' . $langs->trans("Customer") . '</td><td>';
                $events = array();
                $events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php', 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
                print $form->select_company($this->withfromsocid, 'socid', '', 1, 1, '', $events);
                print '</td></tr>';

                // Contact and type
                print '<tr><td>' . $langs->trans("Contact") . '</td><td>';
                $nbofcontacts = $form->select_contacts($this->withfromsocid, '', 'contactid', 1);

                $formcompany->selectTypeContact($ticketstatic, '', 'type', 'external');
                print '</td></tr>';
            } else {
                print '<tr><td><input type="hidden" name="socid" value="' . $user->socid . '"/></td>';
                print '<td><input type="hidden" name="contactid" value="' . $user->contactid . '"/></td>';
                print '<td><input type="hidden" name="type" value="Z"/></td></tr>';
            }
        }

        // TITLE
        if ($this->withemail) {
            print '<tr><td width="35%"><label for="email"><span class="fieldrequired">' . $langs->trans("Email") . '</span></label></td><td>';
            print '<input  class="text" size="40" id="email" name="email" value="' . (GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : $subject) . '" />';
            print '</td></tr>';
        }

        // Si origin du ticket
        if (isset($this->param['origin']) && $this->param['originid'] > 0) {
            // Parse element/subelement (ex: project_task)
            $element = $subelement = $this->param['origin'];
            if (preg_match('/^([^_]+)_([^_]+)/i', $this->param['origin'], $regs)) {
                $element = $regs[1];
                $subelement = $regs[2];
            }

            dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
            $classname = ucfirst($subelement);
            $objectsrc = new $classname($this->db);
            $objectsrc->fetch(GETPOST('originid'));

            if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
                $objectsrc->fetch_lines();
            }

            $objectsrc->fetch_thirdparty();
            $newclassname = $classname;
            print '<tr><td>' . $langs->trans($newclassname) . '</td><td colspan="2"><input name="' . $subelement . 'id" value="' . GETPOST('originid') . '" type="hidden" />' . $objectsrc->getNomUrl(1) . '</td></tr>';
        }

        // Type
        print '<tr><td><span class="fieldrequired"><label for="selecttype_code">' . $langs->trans("TicketTypeRequest") . '</span></label></td><td>';
        print $this->selectTypesTickets((GETPOST('type_code') ? GETPOST('type_code') : $this->type_code), 'type_code', '', '2');
        print '</td></tr>';

        // Category
        print '<tr><td><span class="fieldrequired"><label for="selectcategory_code">' . $langs->trans("TicketCategory") . '</span></label></td><td>';
        print $this->selectCategoriesTickets((GETPOST('category_code') ? GETPOST('category_code') : $this->category_code), 'category_code', '', '2');
        print '</td></tr>';

        // Severity
        print '<tr><td><span class="fieldrequired"><label for="selectseverity_code">' . $langs->trans("TicketSeverity") . '</span></label></td><td>';
        print $this->selectSeveritiesTickets((GETPOST('severity_code') ? GETPOST('severity_code') : $this->severity_code), 'severity_code', '', '2');
        print '</td></tr>';

        // TITLE
        if ($this->withtitletopic) {
            print '<tr><td><label for="subject"><span class="fieldrequired">' . $langs->trans("Subject") . '</span></label></td><td>';

            // Réponse à un ticket : affichage du titre du thread en readonly
            if ($this->withtopicreadonly) {
                print $langs->trans('SubjectAnswerToTicket') . ' ' . $this->topic_title;
                print '</td></tr>';
            } else {
                if ($this->withthreadid > 0) {
                    $subject = $langs->trans('SubjectAnswerToTicket') . ' ' . $this->withthreadid . ' : ' . $this->topic_title . '';
                }
                print '<input class="text" size="50" id="subject" name="subject" value="' . (GETPOST('subject', 'alpha') ? GETPOST('subject', 'alpha') : $subject) . '" />';
                print '</td></tr>';
            }
        }

        // MESSAGE
        $msg = GETPOST('message', 'alpha') ? GETPOST('message', 'alpha') : '';
        print '<tr><td><label for="message"><span class="fieldrequired">' . $langs->trans("Message") . '</span></label></td><td>';

        // If public form, display more information
        if ($this->ispublic) {
            print '<div class="warning">' . ($conf->global->TICKETS_PUBLIC_TEXT_HELP_MESSAGE ? $conf->global->TICKETS_PUBLIC_TEXT_HELP_MESSAGE : $langs->trans('TicketPublicPleaseBeAccuratelyDescribe')) . '</div>';
        }
        include_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        $uselocalbrowser = true;
        $doleditor = new DolEditor('message', GETPOST('message', 'alpha'), '100%', 250, 'dolibarr_details', 'In', true, $uselocalbrowser);
        $doleditor->Create();
        print '</td></tr>';

        // Attached files
        if (!empty($this->withfile)) {
            // Define list of attached files
            $listofpaths = array();
            $listofnames = array();
            $listofmimes = array();
            if (!empty($_SESSION["listofpaths"])) {
                $listofpaths = explode(';', $_SESSION["listofpaths"]);
            }

            if (!empty($_SESSION["listofnames"])) {
                $listofnames = explode(';', $_SESSION["listofnames"]);
            }

            if (!empty($_SESSION["listofmimes"])) {
                $listofmimes = explode(';', $_SESSION["listofmimes"]);
            }

            $out .= '<tr>';
            $out .= '<td width="180">' . $langs->trans("MailFile") . '</td>';
            $out .= '<td colspan="2">';
            // TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
            $out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">' . "\n";
            $out .= '<script type="text/javascript" language="javascript">';
            $out .= 'jQuery(document).ready(function () {';
            $out .= '    jQuery(".removedfile").click(function() {';
            $out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
            $out .= '    });';
            $out .= '})';
            $out .= '</script>' . "\n";
            if (count($listofpaths)) {
                foreach ($listofpaths as $key => $val) {
                    $out .= '<div id="attachfile_' . $key . '">';
                    $out .= img_mime($listofnames[$key]) . ' ' . $listofnames[$key];
                    if (!$this->withfilereadonly) {
                        $out .= ' <input type="image" style="border: 0px;" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/delete.png" value="' . ($key + 1) . '" class="removedfile" id="removedfile_' . $key . '" name="removedfile_' . $key . '" />';
                    }
                    $out .= '<br></div>';
                }
            } else {
                $out .= $langs->trans("NoAttachedFiles") . '<br>';
            }
            if ($this->withfile == 2) { // Can add other files
                $out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="' . $langs->trans("Upload") . '" />';
                $out .= ' ';
                $out .= '<input type="submit" class="button" id="addfile" name="addfile" value="' . $langs->trans("MailingAddFile") . '" />';
            }
            $out .= "</td></tr>\n";

            print $out;
        }

        // Other attributes
        if ($this->withextrafields == 1) {
            $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $ticketstat, $action); // Note that $action and $object may have been modified by hook
            if (empty($reshook) && !empty($extrafields->attribute_label)) {
                print $ticketstat->showOptionals($extrafields, 'edit');
            }
        }

        print '</table>';

        print '<center>';
        print '<input class="button" type="submit" name="add_ticket" value="' . $langs->trans(($this->withthreadid > 0 ? "SendResponse" : "NewTicket")) . '" />';

        if ($this->withcancel) {
            print " &nbsp; &nbsp; ";
            print "<input class=\"button\" type=\"submit\" name=\"cancel\" value=\"" . $langs->trans("Cancel") . "\">";
        }
        print "</center>\n";

        print "</form>\n";
        print "<!-- End form TICKET -->\n";
    }

    /**
     *      Return html list of tickets type
     *
     *      @param  string $selected    Id du type pre-selectionne
     *      @param  string $htmlname    Nom de la zone select
     *      @param  string $filtertype  To filter on field type in llx_c_ticketsup_type (array('code'=>xx,'label'=>zz))
     *      @param  int    $format      0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
     *      @param  int    $empty       1=peut etre vide, 0 sinon
     *         @param  int    $noadmininfo 0=Add admin info, 1=Disable admin info
     *      @param  int    $maxlength   Max length of label
     *         @return void
     */
    public function selectTypesTickets($selected = '', $htmlname = 'tickettype', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0)
    {
        global $langs, $user;

        $ticketstat = new Ticketsup($this->db);

        dol_syslog(get_class($this) . "::select_types_tickets " . $selected . ", " . $htmlname . ", " . $filtertype . ", " . $format, LOG_DEBUG);

        $filterarray = array();

        if ($filtertype != '' && $filtertype != '-1') {
            $filterarray = explode(',', $filtertype);
        }

        $ticketstat->load_cache_types_tickets();

        print '<select id="select' . $htmlname . '" class="flat select_tickettype" name="' . $htmlname . '">';
        if ($empty) {
            print '<option value="">&nbsp;</option>';
        }

        if (is_array($ticketstat->cache_types_tickets) && count($ticketstat->cache_types_tickets)) {
            foreach ($ticketstat->cache_types_tickets as $id => $arraytypes) {
                // On passe si on a demande de filtrer sur des modes de paiments particuliers
                if (count($filterarray) && !in_array($arraytypes['type'], $filterarray)) {
                    continue;
                }

                // We discard empty line if showempty is on because an empty line has already been output.
                if ($empty && empty($arraytypes['code'])) {
                    continue;
                }

                if ($format == 0) {
                    print '<option value="' . $id . '"';
                }

                if ($format == 1) {
                    print '<option value="' . $arraytypes['code'] . '"';
                }

                if ($format == 2) {
                    print '<option value="' . $arraytypes['code'] . '"';
                }

                if ($format == 3) {
                    print '<option value="' . $id . '"';
                }

                // Si selected est text, on compare avec code, sinon avec id
                if (preg_match('/[a-z]/i', $selected) && $selected == $arraytypes['code']) {
                    print ' selected="selected"';
                } elseif ($selected == $id) {
                    print ' selected="selected"';
                } elseif ($arraytypes['use_default'] == "1" && !$empty) {
                    print ' selected="selected"';
                }

                print '>';
                if ($format == 0) {
                    $value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
                }

                if ($format == 1) {
                    $value = $arraytypes['code'];
                }

                if ($format == 2) {
                    $value = ($maxlength ? dol_trunc($arraytypes['label'], $maxlength) : $arraytypes['label']);
                }

                if ($format == 3) {
                    $value = $arraytypes['code'];
                }

                print $value ? $value : '&nbsp;';
                print '</option>';
            }
        }
        print '</select>';
        if ($user->admin && !$noadmininfo) {
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"), 1);
        }
    }

    /**
     *      Return html list of ticket categories
     *
     *      @param  string $selected    Id categorie pre-selectionnée
     *      @param  string $htmlname    Nom de la zone select
     *      @param  string $filtertype  To filter on field type in llx_c_ticketsup_category (array('code'=>xx,'label'=>zz))
     *      @param  int    $format      0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
     *      @param  int    $empty       1=peut etre vide, 0 sinon
     *         @param  int    $noadmininfo 0=Add admin info, 1=Disable admin info
     *      @param  int    $maxlength   Max length of label
     *         @return void
     */
    public function selectCategoriesTickets($selected = '', $htmlname = 'ticketcategory', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0)
    {
        global $langs, $user;

        $ticketstat = new Ticketsup($this->db);

        dol_syslog(get_class($this) . "::selectCategoryTickets " . $selected . ", " . $htmlname . ", " . $filtertype . ", " . $format, LOG_DEBUG);

        $filterarray = array();

        if ($filtertype != '' && $filtertype != '-1') {
            $filterarray = explode(',', $filtertype);
        }

        $ticketstat->load_cache_categories_tickets();

        print '<select id="select' . $htmlname . '" class="flat select_ticketcategory" name="' . $htmlname . '">';
        if ($empty) {
            print '<option value="">&nbsp;</option>';
        }

        if (is_array($ticketstat->cache_category_tickets) && count($ticketstat->cache_category_tickets)) {
            foreach ($ticketstat->cache_category_tickets as $id => $arraycategories) {
                // On passe si on a demande de filtrer sur des modes de paiments particuliers
                if (count($filterarray) && !in_array($arraycategories['type'], $filterarray)) {
                    continue;
                }

                // We discard empty line if showempty is on because an empty line has already been output.
                if ($empty && empty($arraycategories['code'])) {
                    continue;
                }

                if ($format == 0) {
                    print '<option value="' . $id . '"';
                }

                if ($format == 1) {
                    print '<option value="' . $arraycategories['code'] . '"';
                }

                if ($format == 2) {
                    print '<option value="' . $arraycategories['code'] . '"';
                }

                if ($format == 3) {
                    print '<option value="' . $id . '"';
                }

                // Si selected est text, on compare avec code, sinon avec id
                if (preg_match('/[a-z]/i', $selected) && $selected == $arraycategories['code']) {
                    print ' selected="selected"';
                } elseif ($selected == $id) {
                    print ' selected="selected"';
                } elseif ($arraycategories['use_default'] == "1" && !$empty) {
                    print ' selected="selected"';
                }

                print '>';

                if ($format == 0) {
                    $value = ($maxlength ? dol_trunc($arraycategories['label'], $maxlength) : $arraycategories['label']);
                }

                if ($format == 1) {
                    $value = $arraycategories['code'];
                }

                if ($format == 2) {
                    $value = ($maxlength ? dol_trunc($arraycategories['label'], $maxlength) : $arraycategories['label']);
                }

                if ($format == 3) {
                    $value = $arraycategories['code'];
                }

                print $value ? $value : '&nbsp;';
                print '</option>';
            }
        }
        print '</select>';
        if ($user->admin && !$noadmininfo) {
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"), 1);
        }
    }

    /**
     *      Return html list of ticket severitys
     *
     *      @param  string $selected    Id severity pre-selectionnée
     *      @param  string $htmlname    Nom de la zone select
     *      @param  string $filtertype  To filter on field type in llx_c_ticketsup_severity (array('code'=>xx,'label'=>zz))
     *      @param  int    $format      0=id+libelle, 1=code+code, 2=code+libelle, 3=id+code
     *      @param  int    $empty       1=peut etre vide, 0 sinon
     *         @param  int    $noadmininfo 0=Add admin info, 1=Disable admin info
     *      @param  int    $maxlength   Max length of label
     *         @return void
     */
    public function selectSeveritiesTickets($selected = '', $htmlname = 'ticketseverity', $filtertype = '', $format = 0, $empty = 0, $noadmininfo = 0, $maxlength = 0)
    {
        global $langs, $user;

        $ticketstat = new Ticketsup($this->db);

        dol_syslog(get_class($this) . "::selectSeveritiesTickets " . $selected . ", " . $htmlname . ", " . $filtertype . ", " . $format, LOG_DEBUG);

        $filterarray = array();

        if ($filtertype != '' && $filtertype != '-1') {
            $filterarray = explode(',', $filtertype);
        }

        $ticketstat->load_cache_severities_tickets();

        print '<select id="select' . $htmlname . '" class="flat select_ticketseverity" name="' . $htmlname . '">';
        if ($empty) {
            print '<option value="">&nbsp;</option>';
        }

        if (is_array($ticketstat->cache_severity_tickets) && count($ticketstat->cache_severity_tickets)) {
            foreach ($ticketstat->cache_severity_tickets as $id => $arrayseverities) {
                // On passe si on a demande de filtrer sur des modes de paiments particuliers
                if (count($filterarray) && !in_array($arrayseverities['type'], $filterarray)) {
                    continue;
                }

                // We discard empty line if showempty is on because an empty line has already been output.
                if ($empty && empty($arrayseverities['code'])) {
                    continue;
                }

                if ($format == 0) {
                    print '<option value="' . $id . '"';
                }

                if ($format == 1) {
                    print '<option value="' . $arrayseverities['code'] . '"';
                }

                if ($format == 2) {
                    print '<option value="' . $arrayseverities['code'] . '"';
                }

                if ($format == 3) {
                    print '<option value="' . $id . '"';
                }

                // Si selected est text, on compare avec code, sinon avec id
                if (preg_match('/[a-z]/i', $selected) && $selected == $arrayseverities['code']) {
                    print ' selected="selected"';
                } elseif ($selected == $id) {
                    print ' selected="selected"';
                } elseif ($arrayseverities['use_default'] == "1" && !$empty) {
                    print ' selected="selected"';
                }

                print '>';
                if ($format == 0) {
                    $value = ($maxlength ? dol_trunc($arrayseverities['label'], $maxlength) : $arrayseverities['label']);
                }

                if ($format == 1) {
                    $value = $arrayseverities['code'];
                }

                if ($format == 2) {
                    $value = ($maxlength ? dol_trunc($arrayseverities['label'], $maxlength) : $arrayseverities['label']);
                }

                if ($format == 3) {
                    $value = $arrayseverities['code'];
                }

                print $value ? $value : '&nbsp;';
                print '</option>';
            }
        }
        print '</select>';
        if ($user->admin && !$noadmininfo) {
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"), 1);
        }
    }

    /**
     * Show the form to add message on ticket
     *
     * @param  string $width Width of form
     * @return void
     */
    public function showMessageForm($width = '40%')
    {
        global $conf, $langs, $user, $mysoc;

        $langs->load("other");
        $langs->load("mails");

        $addfileaction = 'addfile';

        $form = new Form($this->db);
        $formmail = new FormMail($this->db);


        // Define list of attached files
        $listofpaths = array();
        $listofnames = array();
        $listofmimes = array();
        if (!empty($_SESSION["listofpaths"])) {
            $listofpaths = explode(';', $_SESSION["listofpaths"]);
        }

        if (!empty($_SESSION["listofnames"])) {
            $listofnames = explode(';', $_SESSION["listofnames"]);
        }

        if (!empty($_SESSION["listofmimes"])) {
            $listofmimes = explode(';', $_SESSION["listofmimes"]);
        }

        // Define output language
        $outputlangs = $langs;
        $newlang = '';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
            $newlang = $this->param['langsmodels'];
        }
        if (! empty($newlang)) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($newlang);
            $outputlangs->load('other');
        }

        print "\n<!-- Begin message_form TICKETSUP -->\n";

        $send_email = GETPOST('send_email', 'int') ? GETPOST('send_email', 'int') : 0;

        // Example 1 : Adding jquery code
        print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			send_email=' . $send_email . ';
			if (send_email) {
				jQuery(".email_line").show();
			} else {
				jQuery(".email_line").hide();
			}

			jQuery("#send_msg_email").click(function() {
				if(jQuery(this).is(":checked")) {
					jQuery(".email_line").show();
				}
				else {
					jQuery(".email_line").hide();
				}
            });';
        print '});
		</script>';

        print '<form method="post" name="ticketsup" enctype="multipart/form-data" action="' . $this->param["returnurl"] . '">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="action" value="' . $this->action . '">';
        foreach ($this->param as $key => $value) {
            print '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }

        // Get message template
        $model_id=0;
        if (array_key_exists('models_id', $this->param)) {
            $model_id=$this->param["models_id"];
            $arraydefaultmessage=$this->getEMailTemplate($this->db, $this->param["models"], $user, $outputlangs, $model_id);
        }

        $result = $formmail->fetchAllEMailTemplate($this->param["models"], $user, $outputlangs);
        if ($result<0) {
            setEventMessages($this->error, $this->errors, 'errors');
        }
        $modelmail_array=array();
        foreach ($formmail->lines_model as $line) {
            $modelmail_array[$line->id]=$line->label;
        }



        print '<table class="border"  width="' . $width . '">';


        // External users can't send message email
        if ($user->rights->ticketsup->write && !$user->societe_id) {
            print '<tr><td width="30%"></td><td colspan="2">';
            $checkbox_selected = ( GETPOST('send_email') == "1" ? ' checked' : '');
            print '<input type="checkbox" name="send_email" value="1" id="send_msg_email" '.$checkbox_selected.'/> ';
            print '<label for="send_msg_email">' . $langs->trans('SendMessageByEmail') . '</label>';
            print '</td></tr>';

            // Zone to select its email template
            if (count($modelmail_array)>0) {
                print '<tr class="email_line"><td></td><td colspan="2"><div style="padding: 3px 0 3px 0">'."\n";
                print $langs->trans('SelectMailModel').': '.$formmail->selectarray('modelmailselected', $modelmail_array, $this->param['models_id'], 1);
                if ($user->admin) {
                    print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
                }
                print ' &nbsp; ';
                print '<input class="button" type="submit" value="'.$langs->trans('Use').'" name="modelselected" id="modelselected">';
                print ' &nbsp; ';
                print '</div></td>';
            }

            // Substitution array
            if ($this->withsubstit) {
                print '<tr class="email_line"><td></td><td colspan="2">';
                $help="";
                foreach ($this->substit as $key => $val) {
                    $help.=$key.' -> '.$langs->trans($val).'<br>';
                }
                print $form->textwithpicto($langs->trans("TicketMessageSubstitutionReplacedByGenericValues"), $help);
                print "</td></tr>";
            }

            if (!$user->societe_id) {
                print '<tr><td width="30%"></td><td>';
                $checkbox_selected = ( GETPOST('private_message') == "1" ? ' checked' : '');
                print '<input type="checkbox" name="private_message" value="1" id="private_message" '.$checkbox_selected.'/> ';
                print '<label for="private_message">' . $langs->trans('MarkMessageAsPrivate') . '</label>';
                print '</td><td align="center">';
                print $form->textwithpicto('', $langs->trans("TicketMessagePrivateHelp"), 1, 'help');
                print '</td></tr>';
            }


            print '<tr class="email_line"><td width=20%">' . $langs->trans('Subject') . '</td>';
            $label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
            print '<td colspan="2"><input type="text" class="text" size="80" name="subject" value="[' . $label_title . ' - ticket #' . $this->track_id . '] ' . $langs->trans('TicketNewMessage') . '" />';
            print '</td></tr>';

            // Destinataires
            print '<tr class="email_line"><td>' . $langs->trans('MailRecipients') . '</td><td colspan="2">';
            $ticketstat = new Ticketsup($this->db);
            $res = $ticketstat->fetch('', $this->track_id);
            if ($res) {
                // Retrieve email of all contacts (internal and external)
                $contacts = $ticketstat->getInfosTicketInternalContact();
                $contacts = array_merge($contacts, $ticketstat->getInfosTicketExternalContact());

                // Build array to display recipient list
                if (is_array($contacts) && count($contacts) > 0) {
                    foreach ($contacts as $key => $info_sendto) {
                        if ($info_sendto['email'] != '') {
                            $sendto[] = dol_escape_htmltag(trim($info_sendto['firstname'] . " " . $info_sendto['lastname']) . " <" . $info_sendto['email'] . "> (" . $info_sendto['libelle'] . ")");
                        }
                    }
                }

                if ($ticketstat->origin_email && !in_array($this->dao->origin_email, $sendto)) {
                    $sendto[] = $ticketstat->origin_email . "(origin)";
                }

                if ($ticketstat->fk_soc > 0) {
                    $ticketstat->socid = $ticketstat->fk_soc;
                    $ticketstat->fetch_thirdparty();

                    if (!in_array($ticketstat->thirdparty->email, $sendto)) {
                        $sendto[] = $ticketstat->thirdparty->email . '(' . $langs->trans('Customer') . ')';
                    }
                }

                if ($conf->global->TICKETS_NOTIFICATION_ALSO_MAIN_ADDRESS) {
                    $sendto[] = $conf->global->TICKETS_NOTIFICATION_EMAIL_TO . '(generic email)';
                }

                // Print recipient list
                if (is_array($sendto) && count($sendto) > 0) {
                    print implode(', ', $sendto);
                } else {
                    print '<div class="warning">' . $langs->trans('WarningNoEMailsAdded') . ' ' . $langs->trans('TicketGoIntoContactTab') . '</div>';
                }
            }
            print '</td></tr>';
        }

        // Intro
        // External users can't send message email
        if ($user->rights->ticketsup->write && !$user->societe_id) {
            $mail_intro = GETPOST('mail_intro') ? GETPOST('mail_intro') : $conf->global->TICKETS_MESSAGE_MAIL_INTRO;
            print '<tr class="email_line"><td><label for="mail_intro">' . $langs->trans("TicketMessageMailIntro") . '</label>';

            print '</td><td>';
            include_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
            $uselocalbrowser = true;

            $doleditor = new DolEditor('mail_intro', $mail_intro, '100%', 140, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);

            $doleditor->Create();
            print '</td><td align="center">';
            print $form->textwithpicto('', $langs->trans("TicketMessageMailIntroHelp"), 1, 'help');
            print '</td></tr>';
        }

        // MESSAGE
        $defaultmessage="";
        if (count($arraydefaultmessage) > 0 && $arraydefaultmessage['content']) {
            $defaultmessage=$arraydefaultmessage['content'];
        }
        $defaultmessage=str_replace('\n', "\n", $defaultmessage);

        // Deal with format differences between message and signature (text / HTML)
        if (dol_textishtml($defaultmessage) && !dol_textishtml($this->substit['__SIGNATURE__'])) {
            $this->substit['__SIGNATURE__'] = dol_nl2br($this->substit['__SIGNATURE__']);
        } elseif (!dol_textishtml($defaultmessage) && dol_textishtml($this->substit['__SIGNATURE__'])) {
            $defaultmessage = dol_nl2br($defaultmessage);
        }
        if (isset($_POST["message"]) &&  ! $_POST['modelselected']) {
            $defaultmessage=GETPOST('message');
        } else {
            $defaultmessage=make_substitutions($defaultmessage, $this->substit);
            // Clean first \n and br (to avoid empty line when CONTACTCIVNAME is empty)
            $defaultmessage=preg_replace("/^(<br>)+/", "", $defaultmessage);
            $defaultmessage=preg_replace("/^\n+/", "", $defaultmessage);
        }

        print '<tr><td><label for="message"><span class="fieldrequired">' . $langs->trans("Message") . '</span></label></td><td>';
        include_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        $doleditor = new DolEditor('message', $defaultmessage, '100%', 350, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);
        $doleditor->Create();
        print '</td><td align="center">';
        if ($user->rights->ticketsup->write && !$user->societe_id) {
            print $form->textwithpicto('', $langs->trans("TicketMessageHelp"), 1, 'help');
        }

        print '</td></tr>';

        // Signature
        // External users can't send message email
        if ($user->rights->ticketsup->write && !$user->societe_id) {
            $mail_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE;
            print '<tr class="email_line"><td><label for="mail_intro">' . $langs->trans("TicketMessageMailSignature") . '</label>';

            print '</td><td>';
            include_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
            $doleditor = new DolEditor('mail_signature', $mail_signature, '100%', 150, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);
            $doleditor->Create();
            print '</td><td align="center">';
            print $form->textwithpicto('', $langs->trans("TicketMessageMailSignatureHelp"), 1, 'help');
            print '</td></tr>';
        }

        // Attached files
        if (!empty($this->withfile)) {
            $out .= '<tr>';
            $out .= '<td width="180">' . $langs->trans("MailFile") . '</td>';
            $out .= '<td colspan="2">';
            // TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
            $out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">' . "\n";
            $out .= '<script type="text/javascript" language="javascript">';
            $out .= 'jQuery(document).ready(function () {';
            $out .= '    jQuery(".removedfile").click(function() {';
            $out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
            $out .= '    });';
            $out .= '})';
            $out .= '</script>' . "\n";
            if (count($listofpaths)) {
                foreach ($listofpaths as $key => $val) {
                    $out .= '<div id="attachfile_' . $key . '">';
                    $out .= img_mime($listofnames[$key]) . ' ' . $listofnames[$key];
                    if (!$this->withfilereadonly) {
                        $out .= ' <input type="image" style="border: 0px;" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/delete.png" value="' . ($key + 1) . '" class="removedfile" id="removedfile_' . $key . '" name="removedfile_' . $key . '" />';
                    }
                    $out .= '<br></div>';
                }
            } else {
                $out .= $langs->trans("NoAttachedFiles") . '<br>';
            }
            if ($this->withfile == 2) { // Can add other files
                $out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="' . $langs->trans("Upload") . '" />';
                $out .= ' ';
                $out .= '<input type="submit" class="button" id="' . $addfileaction . '" name="' . $addfileaction . '" value="' . $langs->trans("MailingAddFile") . '" />';
            }
            $out .= "</td></tr>\n";

            print $out;
        }

        print '<tr><td colspan="3">';
        print '<center>';
        print '<input class="button" type="submit" name="btn_add_message" value="' . $langs->trans("AddMessage") . '" />';

        if ($this->withcancel) {
            print " &nbsp; &nbsp; ";
            print "<input class=\"button\" type=\"submit\" name=\"cancel\" value=\"" . $langs->trans("Cancel") . "\">";
        }
        print "</center>\n";
        print '</td></tr>';
        print '</table>';

        print "</form>\n";
        print "<!-- End form TICKET -->\n";
    }

    /**
     *      Return template of email
     *      Search into table c_email_templates
     *
     *         @param  DoliDB    $db            Database handler
     *         @param  string    $type_template Get message for key module
     *      @param  string    $user          Use template public or limited to this user
     *      @param  Translate $outputlangs   Output lang object
     *      @param  int       $id            Id template to find
     *      @param  int       $active        1=Only active template, 0=Only disabled, -1=All
     *      @return array                        array('topic'=>,'content'=>,..)
     */
    private function getEMailTemplate($db, $type_template, $user, $outputlangs, $id = 0, $active = 1)
    {
        $ret=array();

        $sql = "SELECT label, topic, content, lang";
        $sql.= " FROM ".MAIN_DB_PREFIX.'c_email_templates';
        $sql.= " WHERE type_template='".$db->escape($type_template)."'";
        $sql.= " AND entity IN (".getEntity("c_email_templates").")";
        $sql.= " AND (fk_user is NULL or fk_user = 0 or fk_user = ".$user->id.")";
        if ($active >= 0) {
            $sql.=" AND active = ".$active;
        }
        if (is_object($outputlangs)) {
            $sql.= " AND (lang = '".$outputlangs->defaultlang."' OR lang IS NULL OR lang = '')";
        }
        if (!empty($id)) {
            $sql.= " AND rowid=".$id;
        }
        $sql.= $db->order("lang,label", "ASC");
        //print $sql;

        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);    // Get first found
            if ($obj) {
                $ret['label']=$obj->label;
                $ret['topic']=$obj->topic;
                $ret['content']=$obj->content;
                $ret['lang']=$obj->lang;
            } else {
                $defaultmessage='';
                if ($type_template=='facture_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoice");
                } elseif ($type_template=='facture_relance') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendInvoiceReminder");
                } elseif ($type_template=='propal_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendProposal");
                } elseif ($type_template=='supplier_proposal_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierProposal");
                } elseif ($type_template=='order_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendOrder");
                } elseif ($type_template=='order_supplier_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierOrder");
                } elseif ($type_template=='invoice_supplier_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendSupplierInvoice");
                } elseif ($type_template=='shipping_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendShipping");
                } elseif ($type_template=='fichinter_send') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentSendFichInter");
                } elseif ($type_template=='thirdparty') {
                    $defaultmessage=$outputlangs->transnoentities("PredefinedMailContentThirdparty");
                }

                $ret['label']='default';
                $ret['topic']='';
                $ret['content']=$defaultmessage;
                $ret['lang']=$outputlangs->defaultlang;
            }

            $db->free($resql);
            return $ret;
        } else {
            dol_print_error($db);
            return -1;
        }
    }
}
