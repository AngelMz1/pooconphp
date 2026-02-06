<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\DatabaseFactory;
use App\ReferenceData;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->safeLoad();
} catch (Exception $e) { }

$db = DatabaseFactory::create();
$refData = new ReferenceData($db);

echo "Testing ReferenceData retrieval...\n\n";

$tipos = $refData->getTiposDocumento();
echo "Tipos de Documento: " . count($tipos) . " items\n";
print_r($tipos);

echo "\n\nSexos: " . count($refData->getSexos()) . " items\n";
print_r($refData->getSexos());

echo "\n\nCiudades: " . count($refData->getCiudades()) . " items\n";
print_r($refData->getCiudades());

echo "\n\nForm Data (complete):\n";
$formData = $refData->getAllForPatientForm();
foreach ($formData as $key => $value) {
    echo "$key: " . (is_array($value) ? count($value) : 'N/A') . " items\n";
}
