<?php
// Add this require statement at the top
require_once 'backup_manager.php';

// Press Win + R, type taskschd.msc
// Click "Create Basic Task"
// Name: "Mushketplace Auto Backup"
// Trigger: Daily (or whatever frequency you want)
// Time: Choose when to run (e.g., 2:00 AM)
// Action: "Start a program"
// Program: C:\xampp\htdocs\Mushketplace_IAS\mushketplace\run_backup.bat

class BackupScheduler {
    private $backupManager;
    private $scheduleFile;
    
    public function __construct() {
        $this->backupManager = new BackupManager();
        $this->scheduleFile = __DIR__ . '/backup_schedule.json';
    }
    
    public function checkAndRunScheduledBackups() {
        $schedule = $this->getSchedule();
        $currentTime = time();
        
        foreach ($schedule as $key => $backup) {
            if ($currentTime >= $backup['next_run']) {
                $this->runBackup($backup['type']);
                
                // Update next run time
                $schedule[$key]['next_run'] = $this->calculateNextRun($backup['frequency']);
                $schedule[$key]['last_run'] = $currentTime;
            }
        }
        
        $this->saveSchedule($schedule);
    }
    
    private function getSchedule() {
        if (!file_exists($this->scheduleFile)) {
            // Default schedule
            $defaultSchedule = [
                'daily_backup' => [
                    'type' => 'database',
                    'frequency' => 'daily',
                    'next_run' => strtotime('tomorrow 2:00 AM'),
                    'last_run' => 0
                ],
                'weekly_cleanup' => [
                    'type' => 'cleanup',
                    'frequency' => 'weekly',
                    'next_run' => strtotime('next Sunday 3:00 AM'),
                    'last_run' => 0
                ]
            ];
            $this->saveSchedule($defaultSchedule);
            return $defaultSchedule;
        }
        
        return json_decode(file_get_contents($this->scheduleFile), true);
    }
    
    private function saveSchedule($schedule) {
        file_put_contents($this->scheduleFile, json_encode($schedule, JSON_PRETTY_PRINT));
    }
    
    private function calculateNextRun($frequency) {
        switch ($frequency) {
            case 'daily':
                return strtotime('+1 day 2:00 AM');
            case 'weekly':
                return strtotime('+1 week Sunday 3:00 AM');
            case 'monthly':
                return strtotime('first day of next month 2:00 AM');
            default:
                return strtotime('+1 day');
        }
    }
    
    private function runBackup($type) {
        switch ($type) {
            case 'database':
                $this->backupManager->createDatabaseBackup();
                break;
            case 'cleanup':
                $this->backupManager->cleanOldBackups();
                break;
        }
    }
    
    public function getScheduleStatus() {
        $schedule = $this->getSchedule();
        $status = [];
        
        foreach ($schedule as $key => $backup) {
            $status[$key] = [
                'type' => $backup['type'],
                'frequency' => $backup['frequency'],
                'next_run' => date('Y-m-d H:i:s', $backup['next_run']),
                'last_run' => $backup['last_run'] > 0 ? date('Y-m-d H:i:s', $backup['last_run']) : 'Never'
            ];
        }
        
        return $status;
    }
}
?>