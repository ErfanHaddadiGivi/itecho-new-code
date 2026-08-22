-- =====================================================================
--  Migration: add the `sync_logs` table
--  (Google Sheet product-sync run reports)
--
--  When to run this:
--  If your database was installed before the Google Sheet sync feature was
--  added, import this file once in phpMyAdmin -> Import so the endpoint can
--  store its reports.
--
--  Running it is safe: `CREATE TABLE IF NOT EXISTS` never touches existing
--  data and does nothing if the table already exists.
-- =====================================================================

CREATE TABLE IF NOT EXISTS sync_logs (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  source        VARCHAR(40) NOT NULL DEFAULT 'google_sheet' COMMENT 'where the batch came from',
  received      INT NOT NULL DEFAULT 0 COMMENT 'rows received in the payload',
  inserted      INT NOT NULL DEFAULT 0,
  updated       INT NOT NULL DEFAULT 0,
  deactivated   INT NOT NULL DEFAULT 0,
  rejected      INT NOT NULL DEFAULT 0,
  rejected_rows TEXT DEFAULT NULL COMMENT 'JSON array of {sku, reason} for rejected rows',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sync_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Google Sheet product-sync run reports';
