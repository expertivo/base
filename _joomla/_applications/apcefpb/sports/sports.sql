--
-- Estrutura da tabela `cms_apcefpb_sports`
--

CREATE TABLE `cms_apcefpb_sports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) NOT NULL,
  `alter_date` datetime NOT NULL,
  `alter_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cms_apcefpb_sports_files`
--

CREATE TABLE IF NOT EXISTS `cms_apcefpb_sports_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_parent` int(11) NOT NULL,
  `index` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `originalName` varchar(255) NOT NULL,
  `filesize` int(11) NOT NULL,
  `mimetype` varchar(50) NOT NULL,
  `extension` varchar(5) NOT NULL,
  `group` varchar(50) NOT NULL,
  `groupType` varchar(5) NOT NULL,
  `class` varchar(50) NOT NULL,
  `label` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`),
  KEY `id_parent` (`id_parent`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- migração

INSERT INTO `cms_apcefpb_sports` (
	`id`,
	`name`,
	`state`,
	`created_date`,
	`created_by`
) SELECT
	`id`,
	`name`,
	`state`,
	`created_date`,
	`created_by`
FROM `migracao_sports`
ORDER BY `id`

INSERT INTO `cms_apcefpb_sports_files` (
	`id`,
    `id_parent`,
    `index`,
    `filename`,
    `originalName`,
    `filesize`,
    `mimetype`,
    `extension`,
    `created_by`,
    `date_created`
) SELECT
	`id`,
	`id_parent`,
	`index`,
	`filename`,
	`originalName`,
	`filesize`,
	`mimetype`,
	`extension`,
	`created_by`,
	`date_created`
FROM `migracao_sports_files`
ORDER BY `id`
