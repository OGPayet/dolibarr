-- Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturedocument ADD INDEX idx_digitalsignaturemanager_digitalsignaturedocument_rowid (rowid);
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturedocument ADD INDEX idx_digitalsignaturemanager_digitalsignaturedocument_fk_digitalsignaturerequest (fk_digitalsignaturerequest);
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturedocument ADD INDEX idx_digitalsignaturemanager_digitalsignaturedocument_fk_ecm (fk_ecm);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_digitalsignaturemanager_digitalsignaturedocument ADD UNIQUE INDEX uk_digitalsignaturemanager_digitalsignaturedocument_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_digitalsignaturemanager_digitalsignaturedocument ADD CONSTRAINT llx_digitalsignaturemanager_digitalsignaturedocument_fk_field FOREIGN KEY (fk_field) REFERENCES llx_digitalsignaturemanager_myotherobject(rowid);
