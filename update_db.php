<?php
// update_db.php
// A utility to automatically update the DB schema for cities, states, pincodes
require_once __DIR__ . '/config/db.php';

try {
    $db = getPDO();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update Venues table
    $db->exec("ALTER TABLE venues ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER location;");
    $db->exec("ALTER TABLE venues ADD COLUMN state VARCHAR(100) DEFAULT NULL AFTER city;");
    $db->exec("ALTER TABLE venues ADD COLUMN pincode VARCHAR(20) DEFAULT NULL AFTER state;");

    // Update Tournaments table
    $db->exec("ALTER TABLE tournaments ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER location;");
    $db->exec("ALTER TABLE tournaments ADD COLUMN state VARCHAR(100) DEFAULT NULL AFTER city;");
    $db->exec("ALTER TABLE tournaments ADD COLUMN pincode VARCHAR(20) DEFAULT NULL AFTER state;");

    // Also fix the slot_duration ENUM directly so we don't have 1265 warnings if they ever use '01:00:00' again
    $db->exec("ALTER TABLE venues MODIFY COLUMN slot_duration VARCHAR(10) NOT NULL DEFAULT '60';");

    echo "Database migrations executed successfully!";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
         echo "Columns already exist. Schema is up to date!";
    } else {
         echo "Error updating DB: " . $e->getMessage();
    }
}
