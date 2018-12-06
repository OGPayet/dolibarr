-- Copyright (C) 2014 	   Charles-Fr Benke       <charles.fr@benke.fr>
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
-- Ne pas placer de commentaire en fin de ligne, ce fichier est pars� lors
-- de l'install et tous les sigles '--' sont supprim�s.
--

--
-- llx_c_element_type
--
delete llx_c_element_type ;
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('subscription', 'Subscription', 'adherents/class', 'subscription', 'adherent', 'members', 'subscription', 'Subscription', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('propal', 'Proposal', 'comm/propal/class', 'propal', 'propal', 'propal', 'propal', 'Propal', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('commande', 'Order', 'commande/class', 'commande', 'commande', 'orders', 'commande', 'Commande', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('facture', 'Bill', 'compta/facture/class', 'facture', 'facture', 'bills', 'facture', 'Facture', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('fichinter', 'Intervention', 'fichinter/class', 'ficheinter', 'fichinter', 'fichinter', 'fichinter', 'Fichinter', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('contrat', 'Contrat', 'contrat/class', 'contrat', 'contrat', 'contrat', 'contrat', 'Contrat', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('order_supplier', 'SupplierOrder', 'fourn/class', 'fournisseur', 'fournisseur', 'orders', 'fournisseur.commande', 'CommandeFournisseur', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('invoice_supplier', 'SupplierInvoice', 'fourn/class', 'fournisseur', 'fournisseur', 'bills', 'fournisseur.facture', 'FactureFournisseur', 1);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
							('shipping', 'Shipping', 'expedition/class', 'expedition', 'expedition', 'expedition', 'expedition', 'Expedition', 1);

-- contact type of societe
insert into llx_c_type_contact ( rowid, element, source, code, libelle, active, module) values (110, 'societe', 'external', 'PROVIDER', 'Prestataire sur site', 1, 'CustomLink');
insert into llx_c_type_contact ( rowid, element, source, code, libelle, active, module) values (111, 'societe', 'external', 'RELATIONSHIP', 'Rapport/relation', 1, 'CustomLink');
insert into llx_c_type_contact ( rowid, element, source, code, libelle, active, module) values (112, 'societe', 'internal', 'PROVIDER', 'Prestataire sur site', 1, 'CustomLink');
insert into llx_c_type_contact ( rowid, element, source, code, libelle, active, module) values (113, 'societe', 'internal', 'RELATIONSHIP', 'Rapport/relation', 1, 'CustomLink');


insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
									('equipement', 'Equipement', 'equipement/class', 'equipement', 'equipement', 'equipement', 'equipement', 'Equipement', 0);
insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
									('factory', 'Factory', 'factory/class', 'factory', 'factory', 'factory', 'factory', 'Factory', 0);

insert into llx_c_element_type ( type, label, classpath, subelement, module, translatefile, classfile, className, incore) values
									('ticketsup', 'TicketSup', 'ticketsup/class', 'ticketsup', 'ticketsup', 'ticketsup', 'ticketsup', 'ticketsup', 0);