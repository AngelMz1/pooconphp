<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = 'tests/TablaReferencia_CIE10__1.xlsx';

try {
    $spreadsheet = IOFactory::load($inputFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Get only the first row (headers)
    $row = $worksheet->getRowIterator()->current();
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(FALSE); 
    $headers = [];
    foreach ($cellIterator as $cell) {
        $headers[] = $cell->getValue();
    }
    
    echo "--- Excel Headers ---\n";
    print_r($headers);
    
} catch (Exception $e) {
    echo 'Error loading file: ', $e->getMessage();
}
