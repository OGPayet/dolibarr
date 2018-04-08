ALTER TABLE llx_contratabonnement_term
ADD FOREIGN KEY (fk_contratabonnement) REFERENCES llx_contratabonnement(rowid) ON DELETE CASCADE;
