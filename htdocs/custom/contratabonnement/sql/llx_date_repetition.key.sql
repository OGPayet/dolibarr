ALTER TABLE llx_date_repetition
ADD FOREIGN KEY (fk_frequence_repetition) REFERENCES llx_frequence_repetition(rowid) ON DELETE CASCADE;
