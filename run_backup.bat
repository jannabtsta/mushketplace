@echo off
cd /d "C:\xampp\htdocs\Mushketplace_IAS\mushketplace"
C:\xampp\php\php.exe cron_backup.php >> backup_log.txt 2>&1