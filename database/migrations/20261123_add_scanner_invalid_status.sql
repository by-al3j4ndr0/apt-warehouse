-- Permite diferenciar envíos existentes que no están en estado at_customs
-- de los códigos HBL que no existen en shipments.
ALTER TABLE scanner_events
    MODIFY COLUMN result ENUM('FOUND', 'NOT_FOUND', 'DUPLICATE', 'INVALID_STATUS') NOT NULL;
