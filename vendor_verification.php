<?php
class VendorVerification {
    private $conn;
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB

    public function __construct($connection) {
        $this->conn = $connection;
        $this->uploadDir = __DIR__ . '/uploads/verification_docs/';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $this->createVerificationTable();
    }

    private function createVerificationTable() {
        $sql = "CREATE TABLE IF NOT EXISTS vendor_verification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            business_name VARCHAR(255) NOT NULL,
            id_document1 VARCHAR(255),
            id_document2 VARCHAR(255),
            business_address TEXT,
            business_phone VARCHAR(20),
            business_email VARCHAR(255),
            verification_status ENUM('pending', 'approved', 'rejected', 'under_review') DEFAULT 'pending',
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            reviewed_by INT NULL,
            rejection_reason TEXT NULL,
            approval_notes TEXT NULL,
            validation_notes TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (reviewed_by) REFERENCES users(id)
        )";
        $this->conn->query($sql);
    }

    public function submitVerificationRequest($userId, $data, $files) {
        try {
            $stmt = $this->conn->prepare("SELECT id, verification_status FROM vendor_verification WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();

            if ($existing && in_array($existing['verification_status'], ['approved', 'pending', 'under_review'])) {
                return ['success' => false, 'message' => 'Verification request already in process or completed.'];
            }

            $uploadedDocs = [];
            $documentTypes = ['id_document1', 'id_document2'];

            foreach ($documentTypes as $docType) {
                if (isset($files[$docType]) && $files[$docType]['error'] === UPLOAD_ERR_OK) {
                    $upload = $this->uploadDocument($files[$docType], $docType, $userId);
                    if ($upload['success']) {
                        $uploadedDocs[$docType] = $upload['filename'];
                    } else {
                        return ['success' => false, 'message' => "Failed to upload $docType: " . $upload['message']];
                    }
                }
            }

            // Mock validation logic
           $validation_notes = (strpos(strtolower($data['business_name']), 'invalid') !== false)
    ? '❌ Document failed mock validation: suspicious name.'
    : '✅ Simulated validation passed: name and documents appear valid.';

$id1 = $uploadedDocs['id_document1'] ?? null;
$id2 = $uploadedDocs['id_document2'] ?? null;
$address = $data['business_address'];
$phone = $data['business_phone'];
$email = $data['business_email'];

$stmt = $this->conn->prepare("INSERT INTO vendor_verification 
    (user_id, business_name, id_document1, id_document2, business_address, business_phone, business_email, validation_notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("isssssss",
    $userId, $data['business_name'],
    $id1, $id2,
    $address, $phone, $email,
    $validation_notes
);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Verification request submitted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to save verification request'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // (Other methods stay the same...)
    public function getVerificationStatus($userId) {
    $stmt = $this->conn->prepare("SELECT * FROM vendor_verification WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

private function uploadDocument($file, $documentType, $userId) {
    // Validate file
    if (!$this->validateFile($file)) {
        return ['success' => false, 'message' => 'Invalid file format or size'];
    }

    // Generate secure filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $userId . '_' . $documentType . '_' . time() . '.' . $extension;
    $filepath = $this->uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }

    return ['success' => false, 'message' => 'Failed to upload file'];
}

private function validateFile($file) {
    // Check file size
    if ($file['size'] > $this->maxFileSize) {
        return false;
    }

    // Check file type
    if (!in_array($file['type'], $this->allowedTypes)) {
        return false;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    return true;
}

}
?>