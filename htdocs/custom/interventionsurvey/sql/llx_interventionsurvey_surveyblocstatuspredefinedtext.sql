-- Copyright (C) 2020	 Alexis LAURIER 	 <contact@alexislaurier.fr>
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


CREATE TABLE llx_interventionsurvey_surveyblocstatuspredefinedtext(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_c_survey_bloc_status_predefined_text integer,
	fk_surveyblocstatus integer NOT NULL,
	position integer,
	label text NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
