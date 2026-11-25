-- Warehouse-to-warehouse transfer workflow.
-- Run once after taking a full database backup. Does not alter existing shipment rows.\n-- Ensure the status column can store the in_transfer state.\nALTER TABLE shipments MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'warehouse';
CREATE TABLE warehouse_locations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_warehouse_locations_name (name),
    KEY idx_warehouse_locations_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warehouse_transfers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    transfer_uuid CHAR(36) NOT NULL,
    transfer_date DATETIME NOT NULL,
    username VARCHAR(150) NOT NULL,
    origin_name VARCHAR(150) NOT NULL DEFAULT 'Almacén Arceo & Padrón',
    destination_id INT UNSIGNED NOT NULL,
    destination_name VARCHAR(150) NOT NULL,
    driver VARCHAR(180) NOT NULL,
    vehicle_plate VARCHAR(40) NOT NULL,
    total_shipments INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_warehouse_transfers_uuid (transfer_uuid),
    KEY idx_warehouse_transfers_date (transfer_date),
    KEY idx_warehouse_transfers_destination (destination_id),
    CONSTRAINT fk_warehouse_transfers_destination
        FOREIGN KEY (destination_id) REFERENCES warehouse_locations(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warehouse_transfer_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    transfer_id BIGINT UNSIGNED NOT NULL,
    shipment_hbl VARCHAR(180) NOT NULL,
    manifest_name VARCHAR(255) NOT NULL DEFAULT '',
    received_at DATETIME DEFAULT NULL,
    received_by VARCHAR(150) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_transfer_shipment (transfer_id, shipment_hbl),
    KEY idx_transfer_items_hbl (shipment_hbl),
    KEY idx_transfer_items_received (received_at),
    CONSTRAINT fk_transfer_items_transfer
        FOREIGN KEY (transfer_id) REFERENCES warehouse_transfers(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
