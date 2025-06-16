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
            business_license_number VARCHAR(100),
            tax_id VARCHAR(50),
            business_license_doc VARCHAR(255),
            tax_certificate_doc VARCHAR(255),
            id_document VARCHAR(255),
            address_proof VARCHAR(255),
            bank_statement VARCHAR(255),
            verification_status ENUM('pending', 'approved', 'rejected', 'under_review') DEFAULT 'pending',
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            reviewed_by INT NULL,
            rejection_reason TEXT NULL,
            approval_notes TEXT NULL,
            business_address TEXT,
            business_phone VARCHAR(20),
            business_email VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (reviewed_by) REFERENCES users(id)
        )";
        $this->conn->query($sql);
    }
    
    public function uploadDocument($file, $documentType, $userId) {
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
    
    public function submitVerificationRequest($userId, $data, $files) {
        try {
            // Check if user already has a pending/approved verification
            $stmt = $this->conn->prepare("SELECT id, verification_status FROM vendor_verification WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($existing = $result->fetch_assoc()) {
                if ($existing['verification_status'] === 'approved') {
                    return ['success' => false, 'message' => 'Vendor already verified'];
                }
                if ($existing['verification_status'] === 'pending' || $existing['verification_status'] === 'under_review') {
                    return ['success' => false, 'message' => 'Verification request already pending'];
                }
            }
            
            $uploadedDocs = [];
            $documentTypes = ['business_license_doc', 'tax_certificate_doc', 'id_document', 'address_proof', 'bank_statement'];
            
            // Upload documents
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
            
            // Insert or update verification request
            if ($existing) {
                $stmt = $this->conn->prepare("
                    UPDATE vendor_verification SET 
                    business_name = ?, business_license_number = ?, tax_id = ?,
                    business_address = ?, business_phone = ?, business_email = ?,
                    business_license_doc = COALESCE(?, business_license_doc),
                    tax_certificate_doc = COALESCE(?, tax_certificate_doc),
                    id_document = COALESCE(?, id_document),
                    address_proof = COALESCE(?, address_proof),
                    bank_statement = COALESCE(?, bank_statement),
                    verification_status = 'pending',
                    submitted_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");
                $stmt->bind_param("sssssssssssi",
                    $data['business_name'], $data['business_license_number'], $data['tax_id'],
                    $data['business_address'], $data['business_phone'], $data['business_email'],
                    $uploadedDocs['business_license_doc'] ?? null,
                    $uploadedDocs['tax_certificate_doc'] ?? null,
                    $uploadedDocs['id_document'] ?? null,
                    $uploadedDocs['address_proof'] ?? null,
                    $uploadedDocs['bank_statement'] ?? null,
                    $userId
                );
            } else {
                $stmt = $this->conn->prepare("
                    INSERT INTO vendor_verification 
                    (user_id, business_name, business_license_number, tax_id, business_address, 
                     business_phone, business_email, business_license_doc, tax_certificate_doc, 
                     id_document, address_proof, bank_statement) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssssssssss",
                    $userId, $data['business_name'], $data['business_license_number'], $data['tax_id'],
                    $data['business_address'], $data['business_phone'], $data['business_email'],
                    $uploadedDocs['business_license_doc'] ?? null,
                    $uploadedDocs['tax_certificate_doc'] ?? null,
                    $uploadedDocs['id_document'] ?? null,
                    $uploadedDocs['address_proof'] ?? null,
                    $uploadedDocs['bank_statement'] ?? null
                );
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Verification request submitted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to save verification request'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function getVerificationStatus($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM vendor_verification WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function approveVerification($verificationId, $reviewerId, $notes = '') {
        $stmt = $this->conn->prepare("
            UPDATE vendor_verification SET 
            verification_status = 'approved', 
            reviewed_at = CURRENT_TIMESTAMP, 
            reviewed_by = ?,
            approval_notes = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $reviewerId, $notes, $verificationId);
        
        if ($stmt->execute()) {
            // Update user role to farmer if approved
            $stmt2 = $this->conn->prepare("SELECT user_id FROM vendor_verification WHERE id = ?");
            $stmt2->bind_param("i", $verificationId);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $verification = $result->fetch_assoc();
            
            if ($verification) {
                $stmt3 = $this->conn->prepare("UPDATE users SET role = 'farmer' WHERE id = ?");
                $stmt3->bind_param("i", $verification['user_id']);
                $stmt3->execute();
            }
            
            return true;
        }
        return false;
    }
    
    public function rejectVerification($verificationId, $reviewerId, $reason) {
        $stmt = $this->conn->prepare("
            UPDATE vendor_verification SET 
            verification_status = 'rejected', 
            reviewed_at = CURRENT_TIMESTAMP, 
            reviewed_by = ?,
            rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $reviewerId, $reason, $verificationId);
        return $stmt->execute();
    }
}
?>