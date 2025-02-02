<?php

declare(strict_types=1);

$database = new PDO('sqlite:hotel.db');

try {
    // Fetch features from the database
    $stmt = $database->query("SELECT id, name, price FROM Features");
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch rooms from the database
    $roomStmt = $database->query("SELECT id, name, price, description FROM Rooms");
    $rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>