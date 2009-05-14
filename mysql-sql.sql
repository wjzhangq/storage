/* SQLEditor (MySQL)*/


CREATE TABLE `storage_data`
(
`id` INT(10) COMMENT '文件id',
`pos` INT(3) COMMENT '文件位置',
`data` BLOB COMMENT '数据存储',
PRIMARY KEY  (`id`,`pos`)
);



CREATE TABLE `storage`
(
`id` INT(10) unsigned  AUTO_INCREMENT  COMMENT '主键',
`size` INT(10) unsigned  DEFAULT 0 COMMENT '文件字节大小',
`hash` CHAR(32),
`num` INT(3) DEFAULT 1 COMMENT '重复存储次数',
`clip` INT(3) DEFAULT 1 COMMENT '文件被分割成块数量',
`filename` VARCHAR(255) COMMENT '原文件名称',
`add_time` INT(11) COMMENT '上次时间',
PRIMARY KEY (`id`)
) ENGINE=MyISAM;


CREATE INDEX `storage_size_idx`  ON `storage`(`size`);
CREATE INDEX `storage_hash_idx`  ON `storage`(`hash`(32));
