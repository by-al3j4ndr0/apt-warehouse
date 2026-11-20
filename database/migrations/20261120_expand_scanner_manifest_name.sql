-- Keep the full existing shipment manifest name in scanner event history.
ALTER TABLE scanner_events
    MODIFY COLUMN manifest_name VARCHAR(255) NOT NULL;
