<?php
require_once 'backup_manager.php';

$backupManager = new BackupManager();
$backupManager->scheduleAutomaticBackups();
