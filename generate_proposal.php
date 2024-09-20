<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'error.log');
}

$input = json_decode(file_get_contents('php://input'), true);
$resumeId = $input['resumeId'] ?? null;
$jobDescriptionId = $input['jobDescriptionId'] ?? null;

if (!$resumeId || !$jobDescriptionId) {
    echo json_encode(['success' => false, 'message' => 'Missing resumeId or jobDescriptionId.']);
    exit;
}

try {
    $command = escapeshellcmd("python3 process_files.py $resumeId $jobDescriptionId");
    $output = shell_exec($command);

    if ($output === null) {
        throw new Exception("Failed to execute Python script");
    }

    $result = json_decode($output, true);

    if ($result === null) {
        throw new Exception("Failed to decode Python script output");
    }

    echo $output;
} catch (Exception $e) {
    logError('Error generating proposal: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to generate proposal.']);
}
?>