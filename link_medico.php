<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\SupabaseClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$supabase = new SupabaseClient($_ENV['SUPABASE_URL'], $_ENV['SUPABASE_KEY']);

echo "Searching for user 'medico1'...\n";
$users = $supabase->select('users', '*', "username=eq.medico1");

if (empty($users)) {
    die("Error: User 'medico1' not found.\n");
}

$user = $users[0];
echo "Found user: " . $user['username'] . " (ID: " . $user['id'] . ")\n";

echo "Searching for available doctors...\n";
// Find a doctor that doesn't have a user_id yet, or just pick the first one
$medicos = $supabase->select('medicos', '*');

if (empty($medicos)) {
    die("Error: No doctors found in 'medicos' table.\n");
}

$medico = $medicos[0];
echo "Found doctor: " . $medico['primer_nombre'] . " " . $medico['primer_apellido'] . " (ID: " . $medico['id'] . ")\n";

echo "Linking user to doctor...\n";
$updateData = ['user_id' => $user['id']];
$result = $supabase->update('medicos', $updateData, "id=eq." . $medico['id']);

if (isset($result['error'])) {
    echo "Error linking: " . json_encode($result) . "\n";
} else {
    echo "Success! Linked user 'medico1' to Dr. " . $medico['primer_nombre'] . ".\n";
    echo "You can now log in as 'medico1' and test the dashboard.\n";
}
?>
