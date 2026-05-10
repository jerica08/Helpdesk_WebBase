<?php
/**
 * Database Cleanup Script
 * Deletes all tickets and ticket notes while preserving user accounts and departments
 */

require_once 'config/config.php';
require_once 'config/Database.php';

echo "<h2>Database Cleanup</h2>";
echo "<pre>";

try {
    $db = new Database();
    
    // Step 1: Delete all ticket notes first (due to foreign key constraints)
    echo "Step 1: Deleting ticket notes...\n";
    $db->query("DELETE FROM ticket_notes");
    $notesDeleted = $db->execute();
    echo "  - Ticket notes deleted successfully\n\n";
    
    // Step 2: Delete all tickets
    echo "Step 2: Deleting tickets...\n";
    $db->query("DELETE FROM tickets");
    $ticketsDeleted = $db->execute();
    echo "  - All tickets deleted successfully\n\n";
    
    // Step 3: Show preserved data summary
    echo "Step 3: Verifying preserved data...\n";
    
    // Count users by role
    $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $users = $db->resultSet();
    echo "  - Users preserved:\n";
    foreach ($users as $user) {
        echo "    * " . ucfirst($user['role']) . ": " . $user['count'] . " account(s)\n";
    }
    
    // Count departments
    $db->query("SELECT COUNT(*) as count FROM departments");
    $deptCount = $db->single()['count'];
    echo "  - Departments preserved: " . $deptCount . "\n\n";
    
    // Verify tables are empty
    $db->query("SELECT COUNT(*) as count FROM tickets");
    $ticketCount = $db->single()['count'];
    
    $db->query("SELECT COUNT(*) as count FROM ticket_notes");
    $noteCount = $db->single()['count'];
    
    echo "Verification:\n";
    echo "  - Tickets remaining: " . $ticketCount . " (should be 0)\n";
    echo "  - Ticket notes remaining: " . $noteCount . " (should be 0)\n\n";
    
    if ($ticketCount == 0 && $noteCount == 0) {
        echo "<strong style='color: green;'>SUCCESS: Database cleaned successfully!</strong>\n";
        echo "All tickets and notes have been deleted.\n";
        echo "User accounts and departments have been preserved.\n";
    } else {
        echo "<strong style='color: orange;'>WARNING: Some data may still exist.</strong>\n";
    }
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</strong>\n";
}

echo "</pre>";
echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a></p>";
?>
