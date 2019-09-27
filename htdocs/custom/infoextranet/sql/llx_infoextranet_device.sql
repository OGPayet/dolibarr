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
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_infoextranet_device(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY,
	entity integer DEFAULT 1,
	types INTEGER NOT NULL ,
	name varchar(255),
	mark varchar(255),
	model varchar(255),
	serial_number varchar(255),
	fk_soc_maintenance integer,
	garantee_time integer,
	imp_time DATE,
	os_type varchar(255),
	id_role integer,
	save_t boolean,
	under_contract integer,
	fk_con_maintenance integer,
	id_ip integer,
	id_oc integer,
	public_note text,
	date_creation datetime,
	tms timestamp,
	fk_user_creat integer,
	fk_user_modif integer,
	mac_adress varchar(255),
	ip varchar(255),
	login varchar(255),
	password varchar(255),
	device_type varchar(255),
	owner int
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;