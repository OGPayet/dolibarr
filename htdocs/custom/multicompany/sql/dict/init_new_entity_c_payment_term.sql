-- Copyright (C) 2009-2017 Regis Houssin  <regis.houssin@inodbox.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


--
-- llx_c_payment_term
--
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (1,'RECEP',       1,1, 'Due Upon Receipt','Due Upon Receipt',0,1,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (2,'30D',         2,1, '30 jours','Réglement à 30 jours',0,30,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (3,'30DENDMONTH', 3,1, '30 jours fin de mois','Réglement à 30 jours fin de mois',1,30,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (4,'60D',         4,1, '60 jours','Réglement à 60 jours',0,60,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (5,'60DENDMONTH', 5,1, '60 jours fin de mois','Réglement à 60 jours fin de mois',1,60,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (6,'PT_ORDER',    6,1, 'A réception de commande','A réception de commande',0,1,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (7,'PT_DELIVERY', 7,1, 'Livraison','Règlement à la livraison',0,1,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (8,'PT_5050',     8,1, '50 et 50','Règlement 50% à la commande, 50% à la livraison',0,1,__ENTITY__);

-- add additional payment terms often needed in Austria
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (9,'10D',         9,1, '10 jours','Réglement à 10 jours',0,10,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (10,'10DENDMONTH', 10,1, '10 jours fin de mois','Réglement à 10 jours fin de mois',1,10,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (11,'14D',         11,1, '14 jours','Réglement à 14 jours',0,14,__ENTITY__);
insert into llx_c_payment_term(rowid, code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, entity) values (12,'14DENDMONTH', 12,1, '14 jours fin de mois','Réglement à 14 jours fin de mois',1,14,__ENTITY__);
