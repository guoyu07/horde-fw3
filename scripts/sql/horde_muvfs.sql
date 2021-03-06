-- $Horde: horde/scripts/sql/horde_muvfs.sql,v 1.1.10.1 2008-07-20 14:25:23 chuck Exp $

CREATE TABLE horde_muvfs (
    vfs_id        INT UNSIGNED NOT NULL,
    vfs_type      SMALLINT UNSIGNED NOT NULL,
    vfs_path      VARCHAR(255) NOT NULL,
    vfs_name      VARCHAR(255) NOT NULL,
    vfs_modified  BIGINT NOT NULL,
    vfs_owner     VARCHAR(255) NOT NULL,
    vfs_perms     SMALLINT UNSIGNED NOT NULL,
    vfs_data      LONGBLOB,
-- Or, on some DBMS systems:
--  vfs_data      IMAGE,
    PRIMARY KEY   (vfs_id)
);

CREATE INDEX vfs_path_idx ON horde_muvfs (vfs_path);
CREATE INDEX vfs_name_idx ON horde_muvfs (vfs_name);
