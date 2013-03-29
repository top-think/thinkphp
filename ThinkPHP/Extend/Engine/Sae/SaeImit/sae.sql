-- ----------------------------
-- sae_counter表，用于模拟SaeCounter
-- ----------------------------
CREATE TABLE IF NOT EXISTS `sae_counter` (
  `name` char(100) NOT NULL DEFAULT '',
  `val` mediumint(8) NOT NULL DEFAULT '0',
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;
-- ----------------------------
-- sae_kv表，用于模拟KVDB
-- ----------------------------
CREATE TABLE IF NOT EXISTS `sae_kv` (
 `k` char(30) NOT NULL DEFAULT '',
  `v` text NOT NULL,
  `isobj` smallint(1) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `k` (`k`)
) TYPE=MyISAM;
-- ----------------------------
-- sae_rank表，用于模拟SaeRank
-- ----------------------------
CREATE TABLE IF NOT EXISTS `sae_rank` (
  `namespace` char(30) NOT NULL,
  `num` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `expire` int(11) unsigned NOT NULL DEFAULT '0',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0',
  KEY `namespace` (`namespace`)
) TYPE=MyISAM;
-- ----------------------------
-- sae_rank_list表，用于模拟SaeRank
-- ----------------------------
CREATE TABLE IF NOT EXISTS `sae_rank_list` (
  `namespace` char(30) NOT NULL,
  `k` char(30) NOT NULL,
  `v` int(11) NOT NULL DEFAULT '0',
  KEY `namespace` (`namespace`,`k`)
) TYPE=MyISAM;
