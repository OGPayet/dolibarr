-- ===================================================================
-- Copyright (C) 2012-2014 Charles-Fr Benke <charles.fr@benke.fr>
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
-- ===================================================================

CREATE TABLE llx_retourproduits (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ref varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  entity int(11) NOT NULL DEFAULT '1',
  fk_soc int(11) NOT NULL,
  fk_projet int(11) DEFAULT NULL,
  ref_ext varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  ref_int varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  ref_customer varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  date_creation datetime DEFAULT NULL,
  fk_user_author int(11) DEFAULT NULL,
  fk_user_modif int(11) DEFAULT NULL,
  date_valid datetime DEFAULT NULL,
  fk_user_valid int(11) DEFAULT NULL,
  date_delivery datetime DEFAULT NULL,
  date_expedition datetime DEFAULT NULL,
  fk_address int(11) DEFAULT NULL,
  fk_shipping_method int(11) DEFAULT NULL,
  tracking_number varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  fk_statut smallint(6) DEFAULT '0',
  billed smallint(6) DEFAULT '0',
  height float DEFAULT NULL,
  width float DEFAULT NULL,
  size_units int(11) DEFAULT NULL,
  size float DEFAULT NULL,
  weight_units int(11) DEFAULT NULL,
  weight float DEFAULT NULL,
  note_private text COLLATE utf8_unicode_ci,
  note_public text COLLATE utf8_unicode_ci,
  model_pdf varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  fk_incoterms int(11) DEFAULT NULL,
  location_incoterms varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  import_key varchar(14) COLLATE utf8_unicode_ci DEFAULT NULL,
  extraparams varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
)ENGINE=InnoDB;
