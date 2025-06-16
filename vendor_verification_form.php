<?php
session_start();
require_once 'vendor_verification.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "mushket");
$verification = new VendorVerification($conn);
$userId = $_SESSION['user_id'];

// Check current verification status
$currentStatus = $verification->getVerificationStatus($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'business_name' => $_POST['business_name'],
        'business_license_number' => $_POST['business_license_number'],
        'tax_id' => $_POST['tax_id'],
        'business_address' => $_POST['business_address'],
        'business_phone' => $_POST['business_phone'],
        'business_email' => $_POST['business_email']
    ];
    
    $result = $verification->submitVerificationRequest($userId, $data, $_FILES);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendor Verification</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="tel"], textarea, input[type="file"] { 
            width: 100%; padding: 8px; margin-bottom: 5px; border: 1px solid #ddd; border-radius: 4px; 
        }
        button { padding: 12px 24px; background-color: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #005a87; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .status-badge { padding: 5px 10px; border-radius: 3px; color: white; font-weight: bold; }
        .pending { background-color: #ffc107; }
        .approved { background-color: #28a745; }
        .rejected { background-color: #dc3545; }
        .under-review { background-color: #17a2b8; }
        .required { color: red; }
        .file-info { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Vendor Verification</h1>
    
    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($currentStatus): ?>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Current Status: 
                <span class="status-badge <?php echo $currentStatus['verification_status']; ?>">
                    <?php echo strtoupper($currentStatus['verification_status']); ?>
                </span>
            </h3>
            
            <?php if ($currentStatus['verification_status'] === 'approved'): ?>
                <p>✅ Your vendor account has been approved! You can now list products.</p>
                <a href="farmer.php">Go to Farmer Dashboard</a>
            <?php elseif ($currentStatus['verification_status'] === 'rejected'): ?>
                <p>❌ Your verification was rejected.</p>
                <p><strong>Reason:</strong> <?php echo htmlspecialchars($currentStatus['rejection_reason']); ?></p>
                <p>You can resubmit with corrected information below.</p>
            <?php elseif ($currentStatus['verification_status'] === 'pending'): ?>
                <p>⏳ Your verification request is pending review. We'll notify you once it's processed.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$currentStatus || $currentStatus['verification_status'] === 'rejected'): ?>
    <form method="POST" enctype="multipart/form-data">
        <h2>Business Information</h2>
        
        <div class="form-group">
            <label for="business_name">Business Name <span class="required">*</span></label>
            <input type="text" name="business_name" id="business_name" required 
                   value="<?php echo htmlspecialchars($currentStatus['business_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="business_license_number">Business License Number</label>
            <input type="text" name="business_license_number" id="business_license_number"
                   value="<?php echo htmlspecialchars($currentStatus['business_license_number'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="tax_id">Tax ID / TIN</label>
            <input type="text" name="tax_id" id="tax_id"
                   value="<?php echo htmlspecialchars($currentStatus['tax_id'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="business_address">Business Address <span class="required">*</span></label>
            <textarea name="business_address" id="business_address" rows="3" required><?php echo htmlspecialchars($currentStatus['business_address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="business_phone">Business Phone <span class="required">*</span></label>
            <input type="tel" name="business_phone" id="business_phone" required
                   value="<?php echo htmlspecialchars($currentStatus['business_phone'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="business_email">Business Email <span class="required">*</span></label>
            <input type="email" name="business_email" id="business_email" required
                   value="<?php echo htmlspecialchars($currentStatus['business_email'] ?? ''); ?>">
        </div>
        
        <h2>Required Documents</h2>
        <p>Please upload clear, readable copies of the following documents (JPEG, PNG, or PDF format, max 5MB each):</p>
        
        <div class="form-group">
            <label for="business_license_doc">Business License Document</label>
            <input type="file" name="business_license_doc" id="business_license_doc" accept=".jpg,.jpeg,.png,.pdf">
            <div class="file-info">Upload your business registration or license document</div>
        </div>
        
        <div class="form-group">
            <label for="tax_certificate_doc">Tax Certificate</label>
            <input type="file" name="tax_certificate_doc" id="tax_certificate_doc" accept=".jpg,.jpeg,.png,.pdf">
            <div class="file-info">Tax registration certificate or similar document</div>
        </div>
        
        <div class="form-group">
            <label for="id_document">Government-issued ID <span class="required">*</span></label>
            <input type="file" name="id_document" id="id_document" accept=".jpg,.jpeg,.png,.pdf" required>
            <div class="file-info">Driver's license, passport, or national ID</div>
        </div>
        
        <div class="form-group">
            <label for="address_proof">Proof of Address</label>
            <input type="file" name="address_proof" id="address_proof" accept=".jpg,.jpeg,.png,.pdf">
            <div class="file-info">Utility bill, bank statement, or lease agreement</div>
        </div>
        
        <div class="form-group">
            <label for="bank_statement">Bank Statement</label>
            <input type="file" name="bank_statement" id="bank_statement" accept=".jpg,.jpeg,.png,.pdf">
            <div class="file-info">Recent bank statement for business account verification</div>
        </div>
        
        <button type="submit">Submit Verification Request</button>
    </form>
    <?php endif; ?>
    
    <p><a href="consumer.php">← Back to Dashboard</a></p>
</body>
</html>