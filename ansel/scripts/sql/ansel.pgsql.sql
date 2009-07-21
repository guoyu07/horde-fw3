-- $Horde: ansel/scripts/sql/ansel.pgsql.sql,v 1.5.2.7 2009/07/16 22:59:35 mrubinsk Exp $

CREATE TABLE ansel_images (
    image_id             INT NOT NULL,
    gallery_id           INT NOT NULL,
    image_filename       VARCHAR(255) NOT NULL,
    image_type           VARCHAR(100) NOT NULL,
    image_caption        TEXT,
    image_uploaded_date  INT NOT NULL,
    image_sort           INT NOT NULL,
    image_faces          INT NOT NULL,
    image_original_date  INT NOT NULL,
    image_latitude VARCHAR(32),
    image_longitude VARCHAR(32),
    image_location VARCHAR(256),
    image_geotag_date   INT NOT NULL DEFAULT 0,
--
    PRIMARY KEY (image_id)
);
CREATE INDEX ansel_images_gallery_idx ON ansel_images (gallery_id);
CREATE INDEX ansel_images_gallery_image_idx ON ansel_images (image_id, gallery_id);
CREATE INDEX ansel_images_uploaded_idx ON ansel_images (image_uploaded_date);
CREATE INDEX ansel_images_taken_idx ON ansel_images (image_uploaded_date);

CREATE TABLE ansel_image_attributes (
    image_id             INT NOT NULL,
    attr_name            VARCHAR(50) NOT NULL,
    attr_value           VARCHAR(255),
--
    PRIMARY KEY (image_id, attr_name)
);
CREATE INDEX ansel_image_attributes_image_idx ON ansel_image_attributes (image_id);

CREATE TABLE ansel_faces (
    face_id              INT NOT NULL,
    image_id             INT NOT NULL,
    gallery_id           INT NOT NULL,
    face_name            VARCHAR(255) NOT NULL,
    face_x1              INT NOT NULL,
    face_y1              INT NOT NULL,
    face_x2              INT NOT NULL,
    face_y2              INT NOT NULL,
    face_signature       BYTEA,
--
    PRIMARY KEY  (face_id)
);

CREATE TABLE ansel_faces_index (
    face_id INT NOT NULL,
    index_position INT NOT NULL,
    index_part BYTEA
);
CREATE INDEX ansel_faces_index_face_id_idx ON ansel_faces_index (face_id);
CREATE INDEX ansel_faces_index_index_part_idx ON ansel_faces_index (index_part);
CREATE INDEX ansel_faces_index_index_position_idx ON ansel_faces_index (index_position);

CREATE TABLE ansel_shares (
    share_id INT NOT NULL,
    share_owner VARCHAR(255) NOT NULL,
    share_parents VARCHAR(255) NULL,
    perm_creator SMALLINT NOT NULL,
    perm_default SMALLINT NOT NULL,
    perm_guest SMALLINT NOT NULL,
    share_flags SMALLINT DEFAULT 0 NOT NULL,
    attribute_name VARCHAR(255) NOT NULL,
    attribute_desc TEXT,
    attribute_default INT,
    attribute_default_type VARCHAR(6),
    attribute_default_prettythumb TEXT,
    attribute_style VARCHAR(255),
    attribute_category VARCHAR(255) DEFAULT '' NOT NULL,
    attribute_last_modified INT,
    attribute_date_created INT,
    attribute_images INT DEFAULT 0 NOT NULL,
    attribute_has_subgalleries INT DEFAULT 0 NOT NULL,
    attribute_slug VARCHAR(255),
    attribute_age INT DEFAULT 0 NOT NULL,
    attribute_download VARCHAR(255),
    attribute_passwd VARCHAR(255),
    attribute_faces INT DEFAULT 0 NOT NULL,
    attribute_view_mode VARCHAR(255) DEFAULT 'Normal' NOT NULL,
--
    PRIMARY KEY (share_id)
);
CREATE INDEX ansel_shares_share_owner_idx ON ansel_shares (share_owner);
CREATE INDEX ansel_shares_perm_creator_idx ON ansel_shares (perm_creator);
CREATE INDEX ansel_shares_perm_default_idx ON ansel_shares (perm_default);
CREATE INDEX ansel_shares_perm_guest_idx ON ansel_shares (perm_guest);
CREATE INDEX ansel_shares_attribute_category_idx ON ansel_shares (attribute_category);
CREATE INDEX ansel_shares_share_parents_idx ON ansel_shares (share_parents);

CREATE TABLE ansel_shares_groups (
    share_id INT NOT NULL,
    group_uid VARCHAR(255) NOT NULL,
    perm SMALLINT NOT NULL
);
CREATE INDEX ansel_shares_groups_share_id_idx ON ansel_shares_groups (share_id);
CREATE INDEX ansel_shares_groups_group_uid_idx ON ansel_shares_groups (group_uid);
CREATE INDEX ansel_shares_groups_perm_idx ON ansel_shares_groups (perm);


CREATE TABLE ansel_shares_users (
    share_id INT NOT NULL,
    user_uid VARCHAR(255) NOT NULL,
    perm SMALLINT NOT NULL
);
CREATE INDEX ansel_shares_users_share_id_idx ON ansel_shares_users (share_id);
CREATE INDEX ansel_shares_users_user_uid_idx ON ansel_shares_users (user_uid);
CREATE INDEX ansel_shares_users_perm_idx ON ansel_shares_users (perm);
CREATE TABLE ansel_tags (
    tag_id               INT NOT NULL,
    tag_name             VARCHAR(255) NOT NULL,

    PRIMARY KEY (tag_id)
);

CREATE TABLE ansel_galleries_tags (
    gallery_id           INT NOT NULL,
    tag_id               INT NOT NULL,
--
    PRIMARY KEY (gallery_id, tag_id)
);

CREATE TABLE ansel_images_tags (
    image_id             INT NOT NULL,
    tag_id               INT NOT NULL,
--
    PRIMARY KEY (image_id, tag_id)
);
