-- Zeno CRM — Incremental Migration
-- MySQL 8.0 compatible. Safe to re-run.
--
-- Usage (docker):
--   docker exec -i zeno-db mysql -uzeno_user -pzeno_pass zeno_crm < database/migrate.sql
-- Usage (local):
--   mysql -h127.0.0.1 -uzeno_user -pzeno_pass zeno_crm < database/migrate.sql

USE zeno_crm;

-- ============================================================
-- Helper: add a column only when it does not already exist.
-- Works on MySQL 8.0 (no ADD COLUMN IF NOT EXISTS support).
-- ============================================================
DROP PROCEDURE IF EXISTS _add_col;
DELIMITER //
CREATE PROCEDURE _add_col(
    IN p_table  VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_def    TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_table
          AND COLUMN_NAME  = p_column
    ) THEN
        SET @_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_def);
        PREPARE _s FROM @_sql;
        EXECUTE _s;
        DEALLOCATE PREPARE _s;
    END IF;
END //
DELIMITER ;

-- Helper: add an index only when it does not already exist.
DROP PROCEDURE IF EXISTS _add_idx;
DELIMITER //
CREATE PROCEDURE _add_idx(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_def   TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_table
          AND INDEX_NAME   = p_index
    ) THEN
        SET @_sql = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_def);
        PREPARE _s FROM @_sql;
        EXECUTE _s;
        DEALLOCATE PREPARE _s;
    END IF;
END //
DELIMITER ;

-- Helper: add a FK constraint only when it does not already exist.
DROP PROCEDURE IF EXISTS _add_fk;
DELIMITER //
CREATE PROCEDURE _add_fk(
    IN p_table      VARCHAR(64),
    IN p_constraint VARCHAR(64),
    IN p_def        TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME        = p_table
          AND CONSTRAINT_NAME   = p_constraint
          AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
    ) THEN
        SET @_sql = CONCAT('ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_constraint, '` ', p_def);
        PREPARE _s FROM @_sql;
        EXECUTE _s;
        DEALLOCATE PREPARE _s;
    END IF;
END //
DELIMITER ;

-- ============================================================
-- 1. users — page_permissions column
-- ============================================================
CALL _add_col('users', 'page_permissions', 'page_permissions JSON AFTER is_active');

-- ============================================================
-- 2. meeting_contacts — attendee junction table
-- ============================================================
CREATE TABLE IF NOT EXISTS meeting_contacts (
    meeting_id INT NOT NULL,
    contact_id INT NOT NULL,
    PRIMARY KEY (meeting_id, contact_id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id)  ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. support_tickets — new table
-- ============================================================
CREATE TABLE IF NOT EXISTS support_tickets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    subject     VARCHAR(200) NOT NULL,
    status      ENUM('open','in_progress','pending','resolved','closed') NOT NULL DEFAULT 'open',
    priority    ENUM('low','medium','high','urgent')                     NOT NULL DEFAULT 'medium',
    lead_id     INT,
    contact_id  INT,
    account_id  INT,
    assigned_to INT,
    description TEXT,
    resolution  TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status   (status),
    INDEX idx_priority (priority),
    FOREIGN KEY (lead_id)     REFERENCES leads(id)    ON DELETE SET NULL,
    FOREIGN KEY (contact_id)  REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (account_id)  REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. leads — conversion tracking columns
-- ============================================================
CALL _add_col('leads', 'converted_contact_id',
    'converted_contact_id INT AFTER description');
CALL _add_col('leads', 'converted_account_id',
    'converted_account_id INT AFTER converted_contact_id');
CALL _add_col('leads', 'converted_opportunity_id',
    'converted_opportunity_id INT AFTER converted_account_id');
CALL _add_col('leads', 'converted_support_id',
    'converted_support_id INT AFTER converted_opportunity_id');
CALL _add_col('leads', 'converted_at',
    'converted_at DATETIME AFTER converted_support_id');

-- ============================================================
-- 5. opportunities — lead_id column + FK
-- ============================================================
CALL _add_col('opportunities', 'lead_id', 'lead_id INT AFTER contact_id');
CALL _add_fk ('opportunities', 'fk_opp_lead',
    'FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL');

-- ============================================================
-- 6. meetings — meeting_link column
-- ============================================================
CALL _add_col('meetings', 'meeting_link', 'meeting_link VARCHAR(500) AFTER description');

-- ============================================================
-- 7. tasks — parent relationship columns + index
-- ============================================================
CALL _add_col('tasks', 'parent_type', 'parent_type VARCHAR(50) AFTER due_date');
CALL _add_col('tasks', 'parent_id',   'parent_id INT AFTER parent_type');
CALL _add_idx('tasks', 'idx_parent',  'INDEX idx_parent (parent_type, parent_id)');

-- ============================================================
-- 8. Backfill page_permissions for seed users
-- ============================================================
UPDATE users SET page_permissions = NULL
    WHERE email = 'admin@zenocrm.com'            AND page_permissions IS NULL;
UPDATE users SET page_permissions = '["accounts","contacts","leads","opportunities"]'
    WHERE email = 'sarah.connor@zenocrm.com'     AND page_permissions IS NULL;
UPDATE users SET page_permissions = '["leads","opportunities","meetings","tasks"]'
    WHERE email = 'tom.brady@zenocrm.com'        AND page_permissions IS NULL;
UPDATE users SET page_permissions = '["contacts","tasks"]'
    WHERE email = 'lisa.simpson@zenocrm.com'     AND page_permissions IS NULL;

-- ============================================================
-- Cleanup helper procedures
-- ============================================================
DROP PROCEDURE IF EXISTS _add_col;
DROP PROCEDURE IF EXISTS _add_idx;
DROP PROCEDURE IF EXISTS _add_fk;

SELECT 'Migration complete.' AS status;
