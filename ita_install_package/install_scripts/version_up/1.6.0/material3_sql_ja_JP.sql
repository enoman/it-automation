ALTER TABLE F_MATERIAL_LINKAGE_OPENSTACK ADD COLUMN ACCESS_AUTH TEXT AFTER OPENST_ENVIRONMENT_CHK;

ALTER TABLE F_MATERIAL_LINKAGE_OPENSTACK_JNL ADD COLUMN ACCESS_AUTH TEXT AFTER OPENST_ENVIRONMENT_CHK;



UPDATE A_SEQUENCE SET MENU_ID=2100150007, DISP_SEQ=2100530001, NOTE=NULL, LAST_UPDATE_TIMESTAMP=STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f') WHERE NAME='F_MATERIAL_LINKAGE_OPENSTACK_RIC';
UPDATE A_SEQUENCE SET MENU_ID=2100150007, DISP_SEQ=2100530002, NOTE='履歴テーブル用', LAST_UPDATE_TIMESTAMP=STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f') WHERE NAME='F_MATERIAL_LINKAGE_OPENSTACK_JSQ';
