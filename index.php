<?php

require __DIR__ . "/functions.php";

$database = new PDO('sqlite:hotel.db');



if (isset($_POST['transfer_code'])) {
  $transferCode = htmlspecialchars(trim($_POST['transfer_code']));
  $gunsCost =  isset($_POST['guns']) ? 2 : 0;
  $rifleCost = isset($_POST['rifle']) ? 3 : 0;
  $featureCost = $gunsCost + $rifleCost;
  $user = 'Mahtias';

  $arrivalDate = $_POST['arrival_date'];
  $departureDate = $_POST['departure_date'];


  if (!$arrivalDate || !$departureDate) {
    echo "Error: Both arrival and departure dates are required.";
    exit;
  }

  if ($departureDate <= $arrivalDate) {
    echo "Error: Departure date must be after arrival date.";
    exit;
  }

  $start = new DateTime($arrivalDate);
  $end = new DateTime($departureDate);
  $totalDays = $start->diff($end)->days;

  $roomType = $_POST['rooms'];
  $roomCost = 0;

  switch ($roomType) {
    case 'economy':
      $roomCost = 1 * $totalDays;
      break;
    case 'standard':
      $roomCost = 2 * $totalDays;
      break;
    case 'luxury':
      $roomCost = 4 * $totalDays;
      break;
    default:
      $roomCost = 0;
  }



  $totalCost = $featureCost + $roomCost;
  echo "Room Type Selected: " . $roomType . "<br>";
  echo "Room Cost: " . $roomCost . "<br>";
  echo "Feature Cost: " . $featureCost . "<br>";
  echo "Total Cost: " . $totalCost . "<br>";


  if (!isValidUuid($transferCode)) {

    echo "Not valid transfercode or not enough balance";
  } else {

    $balance = sendTransferRequest($transferCode, $totalCost);

    if ($balance >= $totalCost && $totalCost != 0) {

      $deposit = depositTransfer($user, $transferCode);

      echo "Deposit succesful: $deposit";

      try {
        // Insert customer
        $stmt = $database->prepare("INSERT INTO Customers (transferCode) VALUES (:transferCode)");
        $stmt->execute([':transferCode' => $transferCode]);
        $customerId = $database->lastInsertId();
    
        // Insert booking
        $stmt = $database->prepare("INSERT INTO Bookings (arrival, departure, customerId) VALUES (:arrivalDate, :departureDate, :customerId)");
        $stmt->execute([
            ':arrivalDate' => $arrivalDate,
            ':departureDate' => $departureDate,
            ':customerId' => $customerId,
        ]);
        $bookingId = $database->lastInsertId();

        // Insert room
    $stmt = $database->prepare("INSERT INTO Rooms (name, price) VALUES (:name, :price)");
    $roomPrice = 0;
    switch ($roomType) {
        case 'economy':
            $roomPrice = 1;
            break;
        case 'standard':
            $roomPrice = 2;
            break;
        case 'luxury':
            $roomPrice = 4;
            break;
    }
    $stmt->execute([
        ':name' => $roomType,
        ':price' => $roomPrice
    ]);
    $roomId = $database->lastInsertId();

    // Insert Booking_Rooms relation
    $stmt = $database->prepare("INSERT INTO Booking_Rooms (bookingId, roomId, price) VALUES (:bookingId, :roomId, :price)");
    $stmt->execute([
        ':bookingId' => $bookingId,
        ':roomId' => $roomId,
        ':price' => $roomCost
    ]);

    
    if ($gunsCost > 0) {
        $stmt = $database->prepare("INSERT INTO Features (name, price) VALUES (:name, :price)");
        $stmt->execute([
            ':name' => 'Guns',
            ':price' => 2
        ]);
        $featureId = $database->lastInsertId();

        // Insert Booking_Features relation
        $stmt = $database->prepare("INSERT INTO Booking_Features (bookingId, featureId, price) VALUES (:bookingId, :featureId, :price)");
        $stmt->execute([
            ':bookingId' => $bookingId,
            ':featureId' => $featureId,
            ':price' => $gunsCost
        ]);
    }

    if ($rifleCost > 0) {
        $stmt = $database->prepare("INSERT INTO Features (name, price) VALUES (:name, :price)");
        $stmt->execute([
            ':name' => 'Rifle',
            ':price' => 3
        ]);
        $featureId = $database->lastInsertId();

        // Insert Booking_Features relation
        $stmt = $database->prepare("INSERT INTO Booking_Features (bookingId, featureId, price) VALUES (:bookingId, :featureId, :price)");
        $stmt->execute([
            ':bookingId' => $bookingId,
            ':featureId' => $featureId,
            ':price' => $rifleCost
        ]);
    }

    echo "Booking completed successfully!";
  
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    
    } else {

      echo "Not enough currency. Required: $totalCost";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hotel Yharnam</title>
</head>

<body>
  <form method="POST">
    <label for="transfer_code"">transferCode</label>
    <input type=" text" id="transfer_code" name="transfer_code" required>
      <button type="submit">Book Now</button>

      <label for="guns">Guns ($2)</label>
      <input type="checkbox" id="guns" name="guns">
      <label for="rifle">Rifle ($3)</label>
      <input type="checkbox" id="rifle" name="rifle">

      <select name="rooms" id="rooms">
        <option value="economy">Economy</option>
        <option value="standard">Standard</option>
        <option value="luxury">Luxury</option>
      </select>

      <label for="arrival_date">Arrival Date</label>
      <input type="date" id="arrival_date" name="arrival_date" min="2025-01-10" max="2025-01-31" required>

      <label for="departure_date">Departure Date</label>
      <input type="date" id="departure_date" name="departure_date" min="2025-01-11" max="2025-01-31" required>


  </form>

</body>

</html>