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
    VALUES (
        1,
        'intervention_survey',
        'fichinter_send',
        'fr_FR',
        0,
        NULL,
        NULL,
        "Email de notification de fin d'intervention avec PDF en pièce jointe",
        13,
        1,
        "L'intervention __REF__ est terminée",
        "Bonjour,\r\n\r\n\r\nSuite à notre visite sur site, veuillez trouver ci-joint le rapport de l'intervention __REF__ effectué pour __THIRDPARTY_NAME__.\r\n\r\n\r\nNous vous prions de bien vouloir vérifier les informations et de nous contacter par courrier, sous huitaine, pour toute éventuelle remarque.\r\n\r\n\r\nBonne réception.\r\n\r\n\r\nCordialement,\r\n\r\n\r\nL’équipe SYNERGIES-TECH\r\n\r\n\r\n\r\n\r\nSi vous n'êtes pas le bon destinataire ou si vous souhaitez ne plus recevoir de communication, merci de le signaler par retour de mail."
    );