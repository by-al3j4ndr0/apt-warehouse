<?php

function getStatusList() {
    $statusList = [
        'warehouse' => 'Almacen',
        'in_transfer' => 'En transferencia',
        'draft' => 'Borrador',
        'delivering' => 'Entregando',
        'finished' => 'Terminado',
        'detained' => 'Detenido'
    ];

    return $statusList;
}

?>