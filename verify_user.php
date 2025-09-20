<?php

/**
 * Quick verification script for development
 * Run this to verify your test user
 */

require_once __DIR__ . '/app/Config/database.php';
require_once __DIR__ . '/app/Repositories/CustomerRepository.php';

try {
    $customerRepo = new CustomerRepository();
    
    // Get your user ID (assuming you're user 1)
    $userId = 1;
    
    // Verify the user
    $success = $customerRepo->updateVerificationStatus($userId, 'Yes');
    
    if ($success) {
        echo "✅ User ID $userId has been verified successfully!\n";
        echo "You can now continue developing the Travel Buddy system.\n";
    } else {
        echo "❌ Failed to verify user ID $userId\n";
    }
    
    // Show current status
    $customers = $customerRepo->getCustomersByVerificationStatus('Pending');
    echo "\n📋 Pending verifications: " . count($customers) . "\n";
    
    if (!empty($customers)) {
        echo "Pending users:\n";
        foreach ($customers as $customer) {
            echo "- User ID: {$customer['user_id']}, Email: {$customer['email']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
