<?php
file_put_contents('debug.log', print_r($_FILES, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', print_r($_POST, true) . "\n", FILE_APPEND);
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'error.log');
}

try {
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    logError('Database connection failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

function uploadFile($file, $type) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    if ($type === 'resume') {
        $allowed_types = ['jpg', 'png', 'jpeg', 'gif', 'pdf'];
    } else {
        $allowed_types = ['jpg', 'png', 'jpeg', 'gif'];
    }
    
    if (!in_array($imageFileType, $allowed_types)) {
        return ['success' => false, 'message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed for job descriptions. Resumes can also be PDF.'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'file_path' => $target_file];
    } else {
        logError('File upload failed: ' . $file["name"]);
        return ['success' => false, 'message' => 'Sorry, there was an error uploading your file.'];
    }
}

function storeFileInfo($db, $file_path, $type) {
    $stmt = $db->prepare('INSERT INTO uploads (file_path, type) VALUES (:file_path, :type)');
    $stmt->bindValue(':file_path', $file_path, PDO::PARAM_STR);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);
    $stmt->execute();
    
    return $db->lastInsertId();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_FILES["resume"]) || !isset($_FILES["jobDescription"])) {
        echo json_encode(['success' => false, 'message' => 'Missing required files.']);
        exit;
    }

    $resume_result = uploadFile($_FILES["resume"], 'resume');
    $job_description_result = uploadFile($_FILES["jobDescription"], 'job_description');
    
    if ($resume_result['success'] && $job_description_result['success']) {
        try {
            $resume_id = storeFileInfo($db, $resume_result['file_path'], 'resume');
            $job_description_id = storeFileInfo($db, $job_description_result['file_path'], 'job_description');
            
            echo json_encode([
                'success' => true,
                'resumeId' => $resume_id,
                'jobDescriptionId' => $job_description_id
            ]);
        } catch (PDOException $e) {
            logError('Database operation failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to store file information in the database.']);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resume_result['success'] ? $job_description_result['message'] : $resume_result['message']
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
