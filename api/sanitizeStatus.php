<?php

function getStatusList() {
    $statusList = [
        'warehouse' => 'Almacen',
        'at_customs' => 'En Aduana',
        'in_transfer' => 'En transferencia',
        'draft' => 'Borrador',
        'delivering' => 'Entregando',
        'finished' => 'Terminado',
        'detained' => 'Detenido'
    ];

    return $statusList;
}

?>