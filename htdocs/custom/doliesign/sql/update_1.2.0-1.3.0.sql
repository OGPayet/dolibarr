-- Copyright (C) 2018 SuperAdmin
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
-- along with this program.  If not, see http://www.gnu.org/licenses/.
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_contrat',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email demande signature contrat',
        1,
        1,
        "Demande de signature du contrat de l'entreprise __MYCOMPANY_NAME__",
        "<center>__LOGO__<h2>Demande de Signature de __MYCOMPANY_NAME__.</h2>\r\n\r\nBonjour,\r\n\r\nVoulez vous signer le contrat suivant {yousignUrl}.</center>"
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_contrat',
        'fr_FR',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation signature contrat',
        2,
        1,
        "Merci d'avoir signer le contrat de l'entreprise __MYCOMPANY_NAME__.",
        "<center>__LOGO__\r\n<h1>Merci d'avoir signer le contrat de __MYCOMPANY_NAME__.</h1>\r\n\r\n<p>Bonjour,</p>\r\n\r\n<p>Merci davoir signer le contrat suivant {yousignUrl}.</p>\r\n</center>"
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_init_contrat',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email for asking to sign a contract',
        5,
        1,
        'Request to sign the contract of __MYCOMPANY_NAME__',
        '<center>__LOGO__<h2>Request for signing from __MYCOMPANY_NAME__.</h1>\r\n\r\nHello,\r\n\r\nWe kindly request to sign the following contract {yousignUrl}.</center>'
    );
INSERT INTO `llx_c_email_templates` (`entity`,`module`,`type_template`,`lang`,`private`,`fk_user`,`datec`,`label`,`position`,`active`,`topic`,`content`)
    VALUES (1,
        'doliesign',
        'doliesign_end_contrat',
        'en_US',
        0,
        NULL,
        NULL,
        'DoliEsign Email confirmation of signing a contrat',
        6,
        1,
        'Thanks for signing the contract of __MYCOMPANY_NAME__.',
        '<center>__LOGO__\r\n<h2>Thank you for signing the contract of __MYCOMPANY_NAME__.</h2>\r\n\r\n<p>Hello,</p>\r\n\r\n<p>Thank you for signing the contract {yousignUrl}.</p>\r\n</center>'
    );
INSERT INTO `llx_doliesign_config` (`entity`,`label`,`date_creation`,`fk_user_creat`,`fk_user_modif`,`import_key`,`status`,`module`,`fk_c_type_contact`,`sign_coordinate`)
    VALUES (1,'Customer signature','2018-09-13 16:06:05',12,12,NULL,1,'contrat',22,'28,119,284,176');
    INSERT INTO `llx_doliesign_config` (`entity`,`label`,`date_creation`,`fk_user_creat`,`fk_user_modif`,`import_key`,`status`,`module`,`fk_c_type_contact`,`sign_coordinate`)
    VALUES (1,'Company signature','2018-09-13 19:06:05',12,12,NULL,1,'contrat',10,'311,118,566,175');