-- $Horde: turba/scripts/upgrades/2.1_to_2.2_add_sql_share_tables.oci8.sql,v 1.1.2.3 2009/10/19 10:54:38 jan Exp $

CREATE TABLE turba_shares (
    share_id NUMBER(16) NOT NULL,
    share_name VARCHAR2(255) NOT NULL,
    share_owner VARCHAR2(32) NOT NULL,
    share_flags NUMBER(8) NOT NULL DEFAULT 0,
    perm_creator NUMBER(8) NOT NULL DEFAULT 0,
    perm_default NUMBER(8) NOT NULL DEFAULT 0,
    perm_guest NUMBER(8) NOT NULL DEFAULT 0,
    attribute_name VARCHAR2(255) NOT NULL,
    attribute_desc VARCHAR2(255),
    attribute_params VARCHAR2(4000),
    PRIMARY KEY (share_id)
);

CREATE INDEX turba_shares_share_name_idx ON turba_shares (share_name);
CREATE INDEX turba_shares_share_owner_idx ON turba_shares (share_owner);
CREATE INDEX turba_shares_perm_creator_idx ON turba_shares (perm_creator);
CREATE INDEX turba_shares_perm_default_idx ON turba_shares (perm_default);
CREATE INDEX turba_shares_perm_guest_idx ON turba_shares (perm_guest);

CREATE TABLE turba_shares_groups (
    share_id NUMBER(16) NOT NULL,
    group_uid NUMBER(16) NOT NULL,
    perm NUMBER(8) NOT NULL
);

CREATE INDEX turba_shares_groups_share_id_idx ON turba_shares_groups (share_id);
CREATE INDEX turba_shares_groups_group_uid_idx ON turba_shares_groups (group_uid);
CREATE INDEX turba_shares_groups_perm_idx ON turba_shares_groups (perm);

CREATE TABLE turba_shares_users (
    share_id NUMBER(16) NOT NULL,
    user_uid VARCHAR2(32) NOT NULL,
    perm NUMBER(8) NOT NULL
);

CREATE INDEX turba_shares_users_share_id_idx ON turba_shares_users (share_id);
CREATE INDEX turba_shares_users_user_uid_idx ON turba_shares_users (user_uid);
CREATE INDEX turba_shares_users_perm_idx ON turba_shares_users (perm);
