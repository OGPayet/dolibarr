DELETE FROM llx_frequence_repetition;
DELETE FROM llx_date_repetition;

INSERT INTO llx_frequence_repetition (rowid, coeffrepetition, nomfrequencerepetition) VALUES
(1, 1, 'AllMonths'),
(2, 3, 'AllTrimesters'),
(5, 4, 'AllQuadrimesters'),
(3, 6, 'AllSemesters'),
(4, 12, 'AllYears'),
(6, 12, 'AllThreeYears');

INSERT INTO llx_date_repetition (rowid, fk_frequence_repetition, moidebut, moifin , nbmois) VALUES
(1, 1, '1', '1','1'),
(2, 1, '2', '2','1'),
(3, 1, '3', '3','1'),
(4, 1, '4', '4','1'),
(5, 1, '5', '5','1'),
(6, 1, '6', '6','1'),
(7, 1, '7', '7','1'),
(8, 1, '8', '8','1'),
(9, 1, '9', '9','1'),
(11, 1, '10', '10','1'),
(12, 1, '11', '11','1'),
(13, 1, '12', '12','1'),
(14, 2, '1', '3' ,'3'),
(15, 2, '4', '6' ,'3'),
(16, 2, '7', '9' ,'3'),
(17, 2, '10', '12' ,'3'),
(18, 3, '1', '6' ,'6'),
(19, 3, '7', '12' ,'6'),
(20, 4, '1', '12' ,'12'),
(21, 5, '1', '4' ,'4'),
(22, 5, '5', '8' ,'4'),
(23, 5, '9', '12' ,'4'),
(24, 6, '1', '12' ,'36');
