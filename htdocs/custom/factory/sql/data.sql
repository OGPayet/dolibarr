-- Copyright (C) 2014-2017	Charlie Benke       <charlie@patas-monkey.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- email template
INSERT INTO llx_c_email_templates (rowid, entity, module, type_template, lang, private, fk_user, datec,  label, `position`, active, topic, content)
VALUES(1, 1, 'factory', 'factory_send', NULL, 0, NULL, NULL,  NULL, NULL, 1, NULL, ' __SIGNATURE__');


--
-- entrepot contact
--

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (230, 'stock', 'internal', 'WAREHOUSERESP',  'Responsable Entrepot', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (231, 'stock', 'internal', 'FACTORYRESP', 'Responsable Production', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (232, 'stock', 'internal', 'INTERVENING', 'Intervenant', 1);

insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (240, 'stock', 'external', 'WAREHOUSERESP', 'Responsable Entrepot', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (241, 'stock', 'external', 'FACTORYRESP', 'Responsable Production', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (242, 'stock', 'exterrnal', 'INTERVENING', 'Intervenant', 1);


--- Factory contact
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (251, 'Factory', 'internal', 'FACTORYRESP', 'Responsable Production', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (252, 'Factory', 'internal', 'INTERVENING', 'Intervenant', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (253, 'Factory', 'external', 'FACTORYRESP', 'Responsable Production', 1);
insert into llx_c_type_contact(rowid, element, source, code, libelle, active ) values (254, 'Factory', 'exterrnal', 'INTERVENING', 'Intervenant', 1);
