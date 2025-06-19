<?php
class LoginTracker {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->createTrackingTable();
    }
    
    private function createTrackingTable() {
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            device_info TEXT,
            browser_info VARCHAR(255),
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_successful BOOLEAN DEFAULT TRUE,
            location_data TEXT,
            is_flagged BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $this->conn->query($sql);
    }
    
    public function getDeviceFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return [
            'user_agent' => $userAgent,
            'browser' => $this->getBrowserInfo($userAgent),
            'device' => $this->getDeviceInfo($userAgent),
            'language' => $acceptLanguage,
            'encoding' => $acceptEncoding,
            'fingerprint' => md5($userAgent . $acceptLanguage . $acceptEncoding)
        ];
    }
    
    private function getBrowserInfo($userAgent) {
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        return 'Unknown';
    }
    
    private function getDeviceInfo($userAgent) {
        if (strpos($userAgent, 'Mobile') !== false) return 'Mobile';
        if (strpos($userAgent, 'Tablet') !== false) return 'Tablet';
        return 'Desktop';
    }
    
    public function logLoginAttempt($userId, $isSuccessful = true) {
        $ipAddress = $this->getRealIpAddress();
        $deviceInfo = $this->getDeviceFingerprint();
        
        $stmt = $this->conn->prepare("
            INSERT INTO login_attempts 
            (user_id, ip_address, user_agent, device_info, browser_info, is_successful) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $deviceJson = json_encode($deviceInfo);
        $stmt->bind_param("issssi", 
            $userId, 
            $ipAddress, 
            $deviceInfo['user_agent'], 
            $deviceJson, 
            $deviceInfo['browser'], 
            $isSuccessful
        );
        
        return $stmt->execute();
    }
    
    public function checkForSuspiciousActivity($userId) {
        $ipAddress = $this->getRealIpAddress();
        $deviceInfo = $this->getDeviceFingerprint();
        

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM login_attempts 
            WHERE user_id = ? AND ip_address = ? AND is_successful = 1
            AND login_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("is", $userId, $ipAddress);
        $stmt->execute();
        $result = $stmt->get_result();
        $ipHistory = $result->fetch_assoc();
        
    
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM login_attempts 
            WHERE user_id = ? AND JSON_EXTRACT(device_info, '$.fingerprint') = ? 
            AND is_successful = 1 AND login_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("is", $userId, $deviceInfo['fingerprint']);
        $stmt->execute();
        $result = $stmt->get_result();
        $deviceHistory = $result->fetch_assoc();
        
        return [
            'is_new_ip' => $ipHistory['count'] == 0,
            'is_new_device' => $deviceHistory['count'] == 0,
            'should_alert' => $ipHistory['count'] == 0 || $deviceHistory['count'] == 0
        ];
    }
    
    public function getRealIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    public function sendSecurityAlert($userEmail, $ipAddress, $deviceInfo) {
        $subject = "Security Alert: New Device Login";
        $message = "
            A new login was detected on your account:
            
            IP Address: $ipAddress
            Device: {$deviceInfo['device']}
            Browser: {$deviceInfo['browser']}
            Time: " . date('Y-m-d H:i:s') . "
            
            If this wasn't you, please secure your account immediately.
        ";
        
        return mail($userEmail, $subject, $message);
    }
}
?>