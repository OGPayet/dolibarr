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
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturesignatoryfield ADD INDEX idx_digitalsignaturemanager_digitalsignaturesignatoryfield_rowid (rowid);
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturesignatoryfield ADD INDEX idx_digitalsignaturemanager_digitalsignaturesignatoryfield_fk_digitalsignaturepeople (fk_chosen_digitalsignaturepeople);
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturesignatoryfield ADD INDEX idx_digitalsignaturemanager_digitalsignaturesignatoryfield_fk_digitalsignaturedocument (fk_chosen_digitalsignaturedocument);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_digitalsignaturemanager_signaturedocumentsignatoryfield ADD UNIQUE INDEX uk_digitalsignaturemanager_signaturedocumentsignatoryfield_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_digitalsignaturemanager_signaturedocumentsignatoryfield ADD CONSTRAINT llx_digitalsignaturemanager_signaturedocumentsignatoryfield_fk_field FOREIGN KEY (fk_field) REFERENCES llx_digitalsignaturemanager_myotherobject(rowid);
