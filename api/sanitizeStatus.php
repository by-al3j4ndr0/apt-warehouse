<?php

function getStatusList() {
    $statusList = [
        'warehouse' => 'Almacen',
        'draft' => 'Borrador',
        'delivering' => 'Entregando',
        'finished' => 'Terminado',
        'detained' => 'Detenido'
    ];

    return $statusList;
}

?>