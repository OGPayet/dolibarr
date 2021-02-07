-- Copyright (C) 2020-2021 Alexis LAURIER <contact@alexislaurier.fr>
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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_sepamandatmanager_sepamandat(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
	fk_soc integer,
	note_public text,
	note_private text,
	date_creation datetime NOT NULL,
	tms timestamp,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	rum varchar(255),
	ics varchar(255),
	iban varchar(255),
	bic varchar(255),
	status integer NOT NULL,
	type integer,
	date_rum date,
	fk_companybankaccount integer,
	fk_generated_ecm integer
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;

ALTER TABLE `llx_societe_rib` CHANGE `label` `label` VARCHAR(1000);
