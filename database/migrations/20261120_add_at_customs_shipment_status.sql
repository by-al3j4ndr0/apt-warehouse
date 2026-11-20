-- Permite conservar el nuevo estado de tránsito aduanero sin alterar los estados existentes.
-- Ejecutar una sola vez sobre la base de datos apt_warehouse.
ALTER TABLE `shipments`
    MODIFY COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'warehouse';
