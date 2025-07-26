<?php

require_once '../vendor/autoload.php';

use Exception as GlobalException;

try {
    ob_start();
    include 'template.php';
    $html = ob_get_clean();
    $mpdf = new \Mpdf\Mpdf(['orientation' => 'L',
                            'tempDir' => __DIR__ . '/tmp']);
    $mpdf->WriteHTML($html);
    $mpdf->Output();
} catch(GlobalException $e) {
    echo $e;
}

?>