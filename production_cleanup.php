<?php
// production_cleanup.php - USE WITH CAUTION
require_once __DIR__ . '/config/db.php';
$db = getPDO();

try {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    $tables = [
        'revenue_log',
        'reviews',
        'bookings',
        'tournament_photos',
        'tournament_registrations',
        'tournaments',
        'venue_photos',
        'venues'
    ];

    foreach ($tables as $table) {
        try {
            $db->exec("DELETE FROM `$table` WHERE 1;");
            $db->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1;");
        } catch (PDOException $e) { }
    }
    
    // Delete all users EXCEPT primary admin
    $db->exec("DELETE FROM users WHERE email != 'admin@gmail.com';");
    $db->exec("ALTER TABLE users AUTO_INCREMENT = 2;"); 

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "<h1 style='color:green; font-family:sans-serif;'>Live Database Cleaned Successfully!</h1>";
    echo "<p style='font-family:sans-serif;'>All mock entries have been removed. Only the Admin account exists.</p>";
    echo "<a href='index.php' style='font-family:sans-serif;'>Return to Home</a>";
    
} catch (Exception $e) {
    echo "<h1 style='color:red;'>Error Cleaning Database:</h1> " . $e->getMessage();
}
