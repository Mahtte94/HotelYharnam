<?php

require __DIR__ . "/functions.php";
require __DIR__ . "/booking.php";

$database = new PDO('sqlite:hotel.db');

$jsonResponse = null;
$jsonOutput = '';
$successMessage = '';
$user = "Mahtias";
$island = "Yharnam";
$hotel = "Hotel Yharnam";
$stars = 3;
$greeting = "Thank you for choosing Hotel Yharnam";
$img = "https://www.well-played.com.au/wp-content/uploads/2021/01/Bloodborne-keyart1.jpg";
$totalCost = 0;

if (isset($_POST['transfer_code'])) {
  $transferCode = htmlspecialchars(trim($_POST['transfer_code']));
  $features = $_POST['features'];

  $arrivalDate = $_POST['arrival_date'];
  $departureDate = $_POST['departure_date'];



  if (!$arrivalDate || !$departureDate) {
    $jsonResponse = json_encode(['error' => 'Both arrival and departure dates are required.']);
  } elseif ($departureDate <= $arrivalDate) {
    $jsonResponse = json_encode(['error' => 'Departure date must be after arrival date.']);
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

  $data = [
    'island' => $island,
    'hotel' => $hotel,
    'arrival_date' => $arrivalDate,
    'departure_date' => $departureDate,
    'total_cost' => $totalCost,
    'stars' =>  $stars,
    'features' => [],
    'additional_info' => [
      'greeting' => $greeting,
      'imageUrl' => $img
    ]
  ];


  if (isset($_POST['features'])) {
    foreach ($_POST['features'] as $feature) {
      list($name, $cost) = explode(":", $feature);
      $data['features'][] = ["name" => $name, "cost" => $cost];
      $totalCost += $cost;
    }
  }

  $data["total_cost"] = $totalCost + $roomCost;
  $json = json_encode($data, JSON_PRETTY_PRINT);
  header('Content-Type: application/json');


  $stmt = $database->prepare("SELECT id FROM Rooms WHERE name = :name");
  $stmt->execute([':name' => $roomType]);
  $roomId = $stmt->fetchColumn();

  if (isRoomAvailable($database, $roomId, $arrivalDate, $departureDate)) {

    if (!isValidUuid($transferCode)) {

      $jsonResponse = json_encode(['error' => 'Not valid transfercode or not enough balance']);
    } else {

      $balance = sendTransferRequest($transferCode, $totalCost);

      if ($balance >= $totalCost && $totalCost != 0) {

        $deposit = depositTransfer($user, $transferCode);

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


          if (isset($features)) {
            foreach ($features as $feature) {
              list($name, $cost) = explode(":", $feature);
              $stmt = $database->prepare("INSERT INTO Features (name, price) VALUES (:name, :price)");
              $stmt->execute([
                ':name' => $name,
                ':price' => $cost
              ]);
              $featureId = $database->lastInsertId();

              // Insert Booking_Features relation
              $stmt = $database->prepare("INSERT INTO Booking_Features (bookingId, featureId, price) VALUES (:bookingId, :featureId, :price)");
              $stmt->execute([
                ':bookingId' => $bookingId,
                ':featureId' => $featureId,
                ':price' => $cost
              ]);
            }
          }

          $jsonOutput = $json;
          $successMessage = "Booking completed successfully!";
          if (!empty($jsonOutput)) {
            header('Content-Type: application/json');
            echo $jsonOutput;

            $filename = "hotel_booking.json";
            $existingData = [];
            if (file_exists($filename)) {

              $existingJson = file_get_contents($filename);
              $existingData = json_decode($existingJson, true);
              $existingData[] = $data;
              $updatedJson = json_encode($existingData, JSON_PRETTY_PRINT);

              file_put_contents($filename, $updatedJson);
            }

            exit;
          }
        } catch (Exception $e) {
          echo "Error: " . $e->getMessage();
        }
      } else {
        $jsonResponse = json_encode(['error' => 'Not enough currency.']);
      }
    }
  } else {
    $jsonResponse = json_encode(['error' => 'The selected room is already booked for the chosen dates.']);
    exit;
  }
}

if ($jsonResponse !== null) {
  header('Content-Type: application/json');
  echo $jsonResponse;
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles/styles.css">
  <title>Hotel Yharnam</title>
</head>

<body>
  <header class="heroHeader">
    <section id="hero" class="hero">
      <h1 class="heroTitle"></h1>
      <img src="images/HotelYharnam.webp" alt="Hotel Yharnam" class="hotel">
    </section>
  </header>

  <main>
    <header class="navHeader">
      <nav class="navbar">
        <h2>Hotel Yharnam</h2>
      </nav>
    </header>

    <article class="roomsContainer">
      <div class="room1">
        <img src="images/budget.jpeg" alt="Budget Room">
      </div>
      <div class="room2">
        <img src="images/standard.jpeg" alt="Standard Room">
      </div>
      <div class="room3">
        <img src="images/luxury.jpeg" alt="Luxury Room">
      </div>

      
        <div class="calendar">
          <?php
          // Generate and display calendars
          generateAllCalendars($database);
          ?>
        </div>
    </article>
    <form method="POST">
      <label for="transfer_code"">transferCode</label>
    <input type=" text" id="transfer_code" name="transfer_code" required>
        <button type="submit">Book Now</button>

        <label for="features_container">
          <label for="guns">Guns ($2)</label>
          <input type="checkbox" id="guns" name="features[]" value="guns:2">
          <label for="rifle">Rifle ($3)</label>
          <input type="checkbox" id="rifle" name="features[]" value="rifle:3">
          <label for="yatzy">Yatzy($1)</label>
          <input type="checkbox" id="yatzy" name="features[]" value="yatzy:1">
          <label for="waterboiler">Waterboiler ($3)</label>
          <input type="checkbox" id="waterboiler" name="features[]" value="waterboiler:3">
          <label for="mixer">Mixer ($2)</label>
          <input type="checkbox" id="mixer" name="features[]" value="mixer:2">
          <label for="unicycle">Unicycle ($8)</label>
          <input type="checkbox" id="unicycle" name="features[]" value="unicycle:8">
        </label>

        <select name="rooms" id="rooms">
          <option value="economy">Economy</option>
          <option value="standard">Standard</option>
          <option value="luxury">Luxury</option>
        </select>

        <label for="arrival_date">Arrival Date</label>
        <input type="date" id="arrival_date" name="arrival_date" min="2025-01-01" max="2025-01-31" required>

        <label for="departure_date">Departure Date</label>
        <input type="date" id="departure_date" name="departure_date" min="2025-01-01" max="2025-01-31" required>


    </form>


  </main>
</body>

</html>