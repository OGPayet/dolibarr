-- Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
-- Copyright (C) 2018 		Alexis LAURIER			<contact@alexislaurier.fr>
-- Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_propal',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email demande signature propal',
        1,
        1,
        'Demande de signature devis de entreprise __MYCOMPANY_NAME__',
        '<center>__LOGO__<h2>Demande de Signature de __MYCOMPANY_NAME__.</h2>\r\n\r\nBonjour,\r\n\r\nVoulez vous signer le Devis suivant {yousignUrl}.</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_propal',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation signature propal',
        2,
        1,
        'Merci de signer le devis de __MYCOMPANY_NAME__.',
        '<center>__LOGO__\r\n<h1>Merci pour votre Signature de __MYCOMPANY_NAME__.</h1>\r\n\r\n<p>Bonjour,</p>\r\n\r\n<p>Merci de signer le Devis {yousignUrl}.</p>\r\n</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_propal',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email for asking to sign a proposal',
        5,
        1,
        'Request to sign the proposal of company __MYCOMPANY_NAME__',
        '<center>__LOGO__<h2>Request for signing from __MYCOMPANY_NAME__.</h1>\r\n\r\nHello,\r\n\r\nWe kindly request to sign the following proposal {yousignUrl}.</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_propal',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation of signing a proposal',
        6,
        1,
        'Thanks for signing the proposal of __MYCOMPANY_NAME__.',
        '<center>__LOGO__\r\n<h2>Thank you for signing for __MYCOMPANY_NAME__.</h2>\r\n\r\n<p>Hello,</p>\r\n\r\n<p>Thank you for signing the proposal {yousignUrl}.</p>\r\n</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_fichinter',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email demande signature intervention',
        3,
        1,
        'Demande de signature intervention de entreprise __MYCOMPANY_NAME__',
        '<center>__LOGO__<h2>Demande de Signature de __MYCOMPANY_NAME__.</h2>\r\n\r\nBonjour,\r\n\r\nVoulez vous signer le intervention suivant {yousignUrl}.</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_fichinter',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation signature intervention',
        4,
        1,
        'Merci de signer le intervention de __MYCOMPANY_NAME__.',
        '<center>__LOGO__\r\n<h1>Merci pour votre Signature de __MYCOMPANY_NAME__.</h1>\r\n\r\n<p>Bonjour,</p>\r\n\r\n<p>Merci de signer le intervention {yousignUrl}.</p>\r\n</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_fichinter',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email for asking to sign an intervention',
        7,
        1,
        'Request to sign the intervention of company __MYCOMPANY_NAME__',
        '<center>__LOGO__<h2>Request for signing from __MYCOMPANY_NAME__.</h1>\r\n\r\nHello,\r\n\r\nWe kindly request to sign the following intervention {yousignUrl}.</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_fichinter',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation of signing an intervention',
        8,
        1,
        'Thanks for signing the intervention of __MYCOMPANY_NAME__.',
        '<center>__LOGO__\r\n<h2>Thank you for signing for __MYCOMPANY_NAME__.</h2>\r\n\r\n<p>Hello,</p>\r\n\r\n<p>Thank you for signing the intervention {yousignUrl}.</p>\r\n</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_commande',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email demande signature commande',
        9,
        1,
        'Demande de signature commande de entreprise __MYCOMPANY_NAME__',
        '<center>__LOGO__<h2>Demande de Signature de __MYCOMPANY_NAME__.</h2>\r\n\r\nBonjour,\r\n\r\nVoulez vous signer la commande suivant {yousignUrl}.</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_commande',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation signature commande',
        10,
        1,
        'Merci de signer la commande de __MYCOMPANY_NAME__.',
        '<center>__LOGO__\r\n<h1>Merci pour votre Signature de __MYCOMPANY_NAME__.</h1>\r\n\r\n<p>Bonjour,</p>\r\n\r\n<p>Merci de signer la commande {yousignUrl}.</p>\r\n</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_commande',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email for asking to sign an order',
        11,
        1,
        'Request to sign the order of company __MYCOMPANY_NAME__',
        '<center>__LOGO__<h2>Request for signing from __MYCOMPANY_NAME__.</h1>\r\n\r\nHello,\r\n\r\nWe kindly request to sign the following order {yousignUrl}.</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_commande',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation of signing an order',
        12,
        1,
        'Thanks for signing the order of __MYCOMPANY_NAME__.',
        '<center>__LOGO__\r\n<h2>Thank you for signing for __MYCOMPANY_NAME__.</h2>\r\n\r\n<p>Hello,</p>\r\n\r\n<p>Thank you for signing the order {yousignUrl}.</p>\r\n</center>'
    );
INSERT INTO `llx_doliesign_config` (`entity`,`label`,`date_creation`,`fk_user_creat`,`fk_user_modif`,`import_key`,`status`,`module`,`fk_c_type_contact`,`sign_coordinate`)
    VALUES (1,'Customer signature','2018-01-22 19:44:05',12,12,NULL,1,'propal',41,'341,107,565,161');
INSERT INTO `llx_doliesign_config` (`entity`,`label`,`date_creation`,`fk_user_creat`,`fk_user_modif`,`import_key`,`status`,`module`,`fk_c_type_contact`,`sign_coordinate`)
    VALUES (1,'Customer signature','2018-04-19 19:44:05',12,12,NULL,0,'commande',101,'337,74,569,124');
INSERT INTO `llx_doliesign_config` (`entity`,`label`,`date_creation`,`fk_user_creat`,`fk_user_modif`,`import_key`,`status`,`module`,`fk_c_type_contact`,`sign_coordinate`)
    VALUES (1,'Customer signature','2018-01-22 19:44:05',12,12,NULL,0,'fichinter',131,'311,104,541,177');
INSERT INTO `llx_doliesign_config` (`entity`,`label`,`date_creation`,`fk_user_creat`,`fk_user_modif`,`import_key`,`status`,`module`,`fk_c_type_contact`,`sign_coordinate`)
    VALUES (1,'Company signature','2018-01-22 19:44:05',12,12,NULL,0,'fichinter',121,'55,103,285,176');
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `color`, `picto`, `position`) VALUES
(713080, 'AC_DOLIESIGN_AUTO', 'systemauto', 'DoliEsign (automatically inserted events)', 'doliesign', 1, NULL, NULL, NULL, 20);