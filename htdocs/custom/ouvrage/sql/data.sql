INSERT IGNORE INTO llx_const (name, value, type, note, visible, entity)
VALUES ('OUVRAGE_TYPE', 'BTP_', 'chaine', 'Type ouvrage', 0, 1);
INSERT IGNORE INTO llx_const (name, value, type, note, visible, entity)
VALUES ('OUVRAGE_HIDE_PRODUCT_DETAIL', 0, 'chaine', 'Ouvrage masquer détail produit dans PDF', 0, 1);
INSERT IGNORE INTO llx_const (name, value, type, note, visible, entity)
VALUES ('OUVRAGE_HIDE_PRODUCT_DESCRIPTION', 0, 'chaine', 'Ouvrage masquer description produit dans PDF', 0, 1);
INSERT IGNORE INTO llx_const (name, value, type, note, visible, entity)
VALUES ('OUVRAGE_HIDE_MONTANT', 0, 'chaine', 'Ouvrage masquer montant dans PDF', 0, 1);
CREATE TABLE IF NOT EXISTS llx_works
(
    rowid    INT          NOT NULL AUTO_INCREMENT,
    ref      VARCHAR(255) NOT NULL,
    label    VARCHAR(255) NOT NULL,
    `entity` INT          NOT NULL,
    `desc`   TEXT         NULL,
    fk_tva   FLOAT        NULL,
    PRIMARY KEY (rowid)
);
CREATE TABLE llx_works_det
(
    rowid         INT NOT NULL AUTO_INCREMENT,
    fk_works      INT NOT NULL,
    fk_product    INT NOT NULL,
    `order`       INT NOT NULL,
    qty           INT NULL,
    unit          INT NULL,
    fk_workschild INT NULL,
    PRIMARY KEY (rowid)
);

ALTER TABLE `llx_works_det`
    MODIFY COLUMN `qty` double(11, 3);
ALTER TABLE `llx_works`
    ADD COLUMN `fk_product` int(11) NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `fk_unit` int(11) NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `note_public` text NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `note_private` text NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `date_creation` datetime NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `tms` timestamp NOT NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `fk_user_creat` int(11) NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `fk_user_modif` int(11) NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `import_key` varchar(14) NULL;
ALTER TABLE `llx_works`
    ADD COLUMN `status` INTEGER NULL;
ALTER TABLE `llx_works`
    CHANGE COLUMN `desc` `description` TEXT NULL;

ALTER TABLE `llx_works`
    CHANGE COLUMN `tms` `tms` TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE `llx_works`
    ADD UNIQUE (ref);
