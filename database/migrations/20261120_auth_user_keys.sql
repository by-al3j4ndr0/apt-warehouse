-- Migration: normalize auth_user identifiers and enforce unique usernames
-- Target: MySQL / MariaDB with InnoDB
-- Run once, after taking a full database backup.
--
-- This script does NOT create, modify, or delete user records.
-- It aborts before ALTER TABLE if duplicate IDs or usernames exist.
-- Username uniqueness follows the table collation (currently case-insensitive).

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_auth_user_keys $$

CREATE PROCEDURE migrate_auth_user_keys()
BEGIN
    DECLARE duplicate_ids BIGINT DEFAULT 0;
    DECLARE duplicate_usernames BIGINT DEFAULT 0;

    SELECT COUNT(*) INTO duplicate_ids
    FROM (
        SELECT id
        FROM auth_user
        GROUP BY id
        HAVING COUNT(*) > 1
    ) AS duplicated_id_values;

    IF duplicate_ids > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migration aborted: auth_user contains duplicate id values. Resolve them before retrying.';
    END IF;

    SELECT COUNT(*) INTO duplicate_usernames
    FROM (
        SELECT username
        FROM auth_user
        GROUP BY username
        HAVING COUNT(*) > 1
    ) AS duplicated_username_values;

    IF duplicate_usernames > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migration aborted: auth_user contains duplicate usernames (according to the column collation). Resolve them before retrying.';
    END IF;

    ALTER TABLE auth_user
        MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT,
        ADD PRIMARY KEY (id),
        ADD UNIQUE KEY uq_auth_user_username (username);
END $$

CALL migrate_auth_user_keys() $$
DROP PROCEDURE migrate_auth_user_keys $$

DELIMITER ;

-- Verify after successful execution:
-- SHOW CREATE TABLE auth_user;
-- SHOW INDEX FROM auth_user;
