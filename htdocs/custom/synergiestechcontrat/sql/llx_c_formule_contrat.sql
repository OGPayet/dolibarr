-- ========================================================================
-- Copyright (C) 2015  Alexandre Spangaro  <aspangaro@zendsi.com>
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
--
-- ========================================================================
--DROP TABLE llx_c_formule_contrat;
create table if not exists llx_c_formule_contrat
(
rowid      integer PRIMARY KEY AUTO_INCREMENT,
entity INTEGER DEFAULT 1 NOT NULL,
status  tinyint DEFAULT 1  NOT NULL,
formule varchar(255),
frequence_de_facturation INT(4) NOT NULL,
echu_a_echoir ENUM('echu', 'aechoir'),
revalorisation ENUM('syntec', 'insee', 'mixte'),
duree_possibles INT(4),
reconduction_tacite  INT(4),
delai_de_resiliation_jours INT(4),
Interventions_type_1 INT(4),
Interventions_type_2 INT(4),
Interventions_type_3 INT(4),
Interventions_type_4 INT(4),
Interventions_type_5 INT(4),
categories_produit_prises_en_charge INT(4)
date_creation DATETIME NOT NULL,
tms TIMESTAMP NOT NULL,
import_key VARCHAR(14)
)ENGINE=innodb;
