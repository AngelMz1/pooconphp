<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = 'tests/TablaReferencia_CIE10__1.xlsx';

try {
    $spreadsheet = IOFactory::load($inputFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    echo "Rows: " . $worksheet->getHighestRow() . "\n";
    echo "Columns: " . $worksheet->getHighestColumn() . "\n";
    
    echo "--- First 5 Rows ---\n";
    $rows = [];
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE); 
        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = $cell->getValue();
        }
        $rows[] = $cells;
        if (count($rows) >= 5) break;
    }
    print_r($rows);
    
} catch (Exception $e) {
    echo 'Error loading file: ', $e->getMessage();
}
