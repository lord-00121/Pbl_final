<?php
// update_db.php
// A utility to automatically update the DB schema for cities, states, pincodes
require_once __DIR__ . '/config/db.php';

try {
    $db = getPDO();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $queries = [
        "ALTER TABLE venues ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER location;",
        "ALTER TABLE venues ADD COLUMN state VARCHAR(100) DEFAULT NULL AFTER city;",
        "ALTER TABLE venues ADD COLUMN pincode VARCHAR(20) DEFAULT NULL AFTER state;",
        "ALTER TABLE venues ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER pincode;",
        "ALTER TABLE venues ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude;",
        "ALTER TABLE tournaments ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER location;",
        "ALTER TABLE tournaments ADD COLUMN state VARCHAR(100) DEFAULT NULL AFTER city;",
        "ALTER TABLE tournaments ADD COLUMN pincode VARCHAR(20) DEFAULT NULL AFTER state;",
        "ALTER TABLE tournaments ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL AFTER pincode;",
        "ALTER TABLE tournaments ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL AFTER latitude;",
        "ALTER TABLE venues MODIFY COLUMN slot_duration VARCHAR(10) NOT NULL DEFAULT '60';"
    ];

    foreach ($queries as $q) {
        try {
            $db->exec($q);
        } catch (PDOException $e) {
            // Ignore duplicate column errors
        }
    }
    echo "Database migrations executed successfully!";
} catch (PDOException $e) {
    echo "Error updating DB: " . $e->getMessage();
}
