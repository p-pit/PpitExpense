-- --------------------------------------------------------

--
-- Structure de la table `expense_report_row`
--

CREATE TABLE IF NOT EXISTS `expense_report_row` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `org_unit_id` int(11) DEFAULT NULL,
  `approver_id` int(11) DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `quantity` float DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `including_vat_amount` float DEFAULT NULL,
  `vat_rate_1` float DEFAULT NULL,
  `amount_vat_1` float DEFAULT NULL,
  `vat_rate_2` float DEFAULT NULL,
  `amount_vat_2` float DEFAULT NULL,
  `capped_amount` float DEFAULT NULL,
  `audit` text,
  `creation_time` timestamp NULL DEFAULT NULL,
  `creation_user` int(11) DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL,
  `update_user` int(11) DEFAULT NULL,
  `report_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `expense_mileage_scale`
--

CREATE TABLE IF NOT EXISTS `expense_mileage_scale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) DEFAULT NULL,
  `year` YEAR DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL, 
  `horsepower` varchar(255) DEFAULT NULL,
  `annual_stage` float DEFAULT NULL,
  `fix_scale` float DEFAULT NULL,
  `variable_scale` float DEFAULT NULL,
  `creation_time` timestamp NULL DEFAULT NULL,
  `creation_user` int(11) DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL,
  `update_user` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
