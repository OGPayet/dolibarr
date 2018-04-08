ALTER TABLE llx_contratabonnement_massmail
ADD FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE CASCADE;