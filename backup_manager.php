<?php
class BackupManager {
    private $dbHost;
    private $dbUser;
    private $dbPass;
    private $dbName;
    private $backupDir;
    private $encryptionKey;
    
    public function __construct($host = 'localhost', $user = 'root', $pass = '', $dbname = 'mushket') {
        $this->dbHost = $host;
        $this->dbUser = $user;
        $this->dbPass = $pass;
        $this->dbName = $dbname;
        $this->backupDir = __DIR__ . '/backups/';
        $this->encryptionKey = $this->getEncryptionKey();
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    private function getEncryptionKey() {
        $keyFile = __DIR__ . '/backup_key.txt';
        
        if (!file_exists($keyFile)) {
            // Generate a new encryption key
            $key = bin2hex(random_bytes(32));
            file_put_contents($keyFile, $key);
            chmod($keyFile, 0600); // Restrict permissions
        }
        
        return file_get_contents($keyFile);
    }
    
    public function createDatabaseBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupDir . "mushket_backup_{$timestamp}.sql";
            
            // Use mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($this->dbHost),
                escapeshellarg($this->dbUser),
                escapeshellarg($this->dbPass),
                escapeshellarg($this->dbName),
                escapeshellarg($backupFile)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($backupFile)) {
                // Encrypt the backup file
                $encryptedFile = $this->encryptBackupFile($backupFile);
                
                // Remove unencrypted file
                unlink($backupFile);
                
                $this->logBackupActivity("Database backup created successfully: " . basename($encryptedFile));
                return $encryptedFile;
            } else {
                throw new Exception("mysqldump failed with return code: $returnCode");
            }
            
        } catch (Exception $e) {
            $this->logBackupActivity("Backup failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    private function encryptBackupFile($filePath) {
        $data = file_get_contents($filePath);
        $encryptedData = $this->encryptData($data);
        
        $encryptedFile = $filePath . '.encrypted';
        file_put_contents($encryptedFile, $encryptedData);
        
        return $encryptedFile;
    }
    
    public function encryptData($data) {
        $method = 'AES-256-CBC';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, $method, hex2bin($this->encryptionKey), 0, $iv);
        
        // Prepend IV to encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    public function decryptData($encryptedData) {
        $method = 'AES-256-CBC';
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, $method, hex2bin($this->encryptionKey), 0, $iv);
    }
    
    public function backupUserData($userId) {
        try {
            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);
            
            // Get user data
            $userData = [];
            
            // Users table
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userData['user'] = $stmt->get_result()->fetch_assoc();
            
            // Login attempts
            $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userData['login_attempts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Add other tables as needed (products, orders, etc.)
            
            $timestamp = date('Y-m-d_H-i-s');
            $userBackupFile = $this->backupDir . "user_{$userId}_backup_{$timestamp}.json";
            
            $jsonData = json_encode($userData, JSON_PRETTY_PRINT);
            $encryptedData = $this->encryptData($jsonData);
            
            file_put_contents($userBackupFile . '.encrypted', $encryptedData);
            
            $this->logBackupActivity("User data backup created for user ID: $userId");
            return $userBackupFile . '.encrypted';
            
        } catch (Exception $e) {
            $this->logBackupActivity("User backup failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function cleanOldBackups($daysToKeep = 30) {
        $files = glob($this->backupDir . '*.encrypted');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        $deletedCount = 0;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        $this->logBackupActivity("Cleaned $deletedCount old backup files");
        return $deletedCount;
    }
    
    public function getBackupList() {
        $files = glob($this->backupDir . '*.encrypted');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        return $backups;
    }
    
    private function logBackupActivity($message, $level = 'INFO') {
        $logFile = $this->backupDir . 'backup.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function scheduleAutomaticBackups() {
        // This would typically be called by a cron job
        $this->createDatabaseBackup();
        $this->cleanOldBackups();
        return true;
    }
}
?>