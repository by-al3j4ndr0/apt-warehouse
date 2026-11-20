CREATE TABLE IF NOT EXISTS scanner_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id VARCHAR(80) NOT NULL,
    manifest_name VARCHAR(120) NOT NULL,
    shipment_code VARCHAR(180) NOT NULL,
    result ENUM('FOUND', 'NOT_FOUND', 'DUPLICATE') NOT NULL,
    recipient VARCHAR(255) NOT NULL DEFAULT '',
    municipality VARCHAR(160) NOT NULL DEFAULT '',
    province VARCHAR(160) NOT NULL DEFAULT '',
    scanned_at DATETIME NOT NULL,
    username VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_scanner_event_id (event_id),
    KEY idx_scanner_manifest_code (manifest_name, shipment_code),
    KEY idx_scanner_scanned_at (scanned_at),
    KEY idx_scanner_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;