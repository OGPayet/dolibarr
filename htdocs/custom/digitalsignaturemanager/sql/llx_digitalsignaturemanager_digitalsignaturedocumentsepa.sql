-- Copyright (C) ---Put here your own copyright and developer email---
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


CREATE TABLE llx_digitalsignaturemanager_digitalsignaturedocumentsepa(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref varchar(128) DEFAULT '(PROV)' NOT NULL,
	fk_soc integer,
	note_public text,
	date_creation datetime NOT NULL,
	tms timestamp,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	status smallint NOT NULL,
	fk_project integer,
	rum varchar(255),
	ics varchar(255),
	iban varchar(255),
	bic varchar(255),
	recurring boolean,
	debtor_name varchar(255),
	debtor_address varchar(1024),
	debtor_postal_code varchar(255),
	debtor_city varchar(255),
	debtor_country varchar(255),
	creditor_name varchar(255),
	creditor_address varchar(1024),
	creditor_postal_code varchar(255),
	creditor_city varchar(255),
	creditor_country varchar(255),
	fk_digitalsignaturerequest integer,
	fk_ecm_signed integer
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
