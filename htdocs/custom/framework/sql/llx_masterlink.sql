--
-- Table structure for table `llx_masterlink`
--

CREATE TABLE IF NOT EXISTS `llx_masterlink` (
  `rowid` int(11) NOT NULL,
  `original` varchar(256) NOT NULL,
  `custom` varchar(256) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  `entity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `llx_masterlink`
--
ALTER TABLE `llx_masterlink`
  ADD PRIMARY KEY (`rowid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `llx_masterlink`
--
ALTER TABLE `llx_masterlink`
  MODIFY `rowid` int(11) NOT NULL AUTO_INCREMENT;