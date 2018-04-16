ALTER TABLE `llx_myfield` CHANGE `formatfield` `formatfield` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE  `llx_myfield` ADD  `movefield` integer NULL DEFAULT NULL AFTER  `sizefield`;
-- 1.0.2 -> 1.1.0
ALTER TABLE  `llx_myfield` ADD  `typefield` integer NULL DEFAULT NULL AFTER  `active`;
-- 1.0.1 -> 1.0.2
ALTER TABLE  `llx_myfield` ADD  `formatfield` varchar(50) NULL DEFAULT NULL AFTER  `sizefield`;
-- 1.0.1 -> 1.0.2
ALTER TABLE  `llx_myfield` ADD  `sizefield` integer NULL DEFAULT NULL AFTER  `compulsory`;
-- 1.0.0 -> 1.0.1
ALTER TABLE  `llx_myfield` ADD  `compulsory` integer NULL DEFAULT NULL AFTER  `active`;