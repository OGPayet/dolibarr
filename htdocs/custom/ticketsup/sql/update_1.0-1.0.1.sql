ALTER TABLE llx_ticketsup ADD fk_soc INT NOT NULL DEFAULT 0 AFTER track_id , ADD INDEX (fk_soc) ;
ALTER TABLE llx_ticketsup CHANGE status fk_statut INT(11) NULL DEFAULT NULL ;
