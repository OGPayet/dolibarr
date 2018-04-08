ALTER TABLE llx_contratabonnement
ADD FOREIGN KEY (fk_contratdet) REFERENCES llx_contratdet(rowid) ON DELETE CASCADE;

ALTER TABLE llx_contratabonnement
ADD FOREIGN KEY (fk_frequencerepetition) REFERENCES llx_frequence_repetition(rowid) ON DELETE CASCADE;
