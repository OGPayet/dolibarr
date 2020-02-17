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


CREATE TABLE llx_interventionsurvey_surveyblocquestion(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	label text,
	date_creation datetime NOT NULL,
	tms timestamp,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	mandatory_status boolean,
	justification_text text,
	description text,
	attached_files text,
	position integer,
	fk_surveypart integer NOT NULL,
	fk_c_survey_bloc_question integer,
	fk_chosen_status integer,
	fk_chosen_answer_predefined_text text,
	label_editable boolean,
	description_editable boolean,
	deletable boolean,
	private boolean
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
