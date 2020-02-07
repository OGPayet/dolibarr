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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_interventionsurvey_surveypart ADD INDEX idx_interventionsurvey_surveypart_rowid (rowid);
ALTER TABLE llx_interventionsurvey_surveypart ADD CONSTRAINT llx_interventionsurvey_surveypart_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_interventionsurvey_surveypart ADD INDEX idx_interventionsurvey_surveypart_fk_fichinter (fk_fichinter);
ALTER TABLE llx_interventionsurvey_surveypart ADD INDEX idx_interventionsurvey_surveypart_fk_identifier_type (fk_identifier_type);
ALTER TABLE llx_interventionsurvey_surveypart ADD INDEX idx_interventionsurvey_surveypart_fk_identifier_value (fk_identifier_value);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_interventionsurvey_surveypart ADD UNIQUE INDEX uk_interventionsurvey_surveypart_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_interventionsurvey_surveypart ADD CONSTRAINT llx_interventionsurvey_surveypart_fk_field FOREIGN KEY (fk_field) REFERENCES llx_interventionsurvey_myotherobject(rowid);
