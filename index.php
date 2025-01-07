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

if (isset($_POST['transfer-code'])) {
  $transferCode = htmlspecialchars(trim($_POST['transfer-code']));
  $features = $_POST['features'];

  $arrivalDate = $_POST['arrival-date'];
  $departureDate = $_POST['departure-date'];



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
    'arrival-date' => $arrivalDate,
    'departure-date' => $departureDate,
    'total-cost' => $totalCost,
    'stars' =>  $stars,
    'features' => [],
    'additional-info' => [
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

  $data["total-cost"] = $totalCost + $roomCost;
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


          // Insert Booking-Rooms relation
          $stmt = $database->prepare("INSERT INTO Booking-Rooms (bookingId, roomId, price) VALUES (:bookingId, :roomId, :price)");
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

              // Insert Booking-Features relation
              $stmt = $database->prepare("INSERT INTO Booking-Features (bookingId, featureId, price) VALUES (:bookingId, :featureId, :price)");
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

            $filename = "hotel-booking.json";
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


  <header class="navHeader">
    <nav class="navbar">
      <h1>Hotel Yharnam</h1>
    </nav>
  </header>

  <section class="hero">
    <img src="images/HotelYharnam.webp" alt="Hotel Yharnam">
    <div class="fog">

      <h2>Welcome traveller to the "safest" place in Yharnam!</h2>
    </div>

  </section>


  <article class="roomsContainer">
    <div class="room">
      <img src="images/budget.jpeg" alt="Budget Room">
      <div class="room-content">
        <h3>Economy Room</h3>
        <p>Bare essentials and creeping dread. Survival is not guaranteed, but the key is yours.</p>
      </div>
    </div>
    <div class="room">
      <img src="images/standard.jpeg" alt="Standard Room">
      <div class="room-content">
        <h3>Standard Room</h3>
        <p>Modest comfort with a touch of unease. The walls may whisper, but rest is possible—if you’re brave enough.</p>
      </div>
    </div>
    <div class="room">
      <img src="images/luxury.jpeg" alt="Luxury Room">
      <div class="room-content">
        <h3>Luxury Room</h3>
        <p>Lavish and perilous. Shadows covet your comfort as much as your sanity.</p>
      </div>
    </div>



  </article>

  <?php
  // Generate and display calendars
  $calendars = generateAllCalendars($database);
  ?>

  <div class="calendar-container">
    <?php foreach ($calendars as $roomType => $calendar): ?>
      <div class="calendar">
        <h2><?php echo ucfirst($roomType); ?> Room Calendar</h2>
        <table>
          <thead>
            <tr>
              <?php foreach ($calendar['header']['weekdays'] as $weekday): ?>
                <th><?php echo $weekday; ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <tr>
              <?php
              $counter = 0;
              foreach ($calendar['days'] as $day):
                if ($counter % 7 == 0 && $counter != 0): ?>
            </tr>
            <tr>
            <?php endif; ?>
            <td class="<?php
                        echo $day['isToday'] ? 'today' : '';
                        echo $day['isBooked'] ? ' booked' : '';
                        ?>">
              <?php echo $day['day']; ?>
            </td>
            <?php $counter++; ?>
          <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="POST">

    <div class="form-container">
      <label for="arrival-date" class="arrival-date">Arrival Date</label>
      <input type="date" id="arrival-date" name="arrival-date" min="2025-01-01" max="2025-01-31" required>

      <label for="departure-date" class="departure-date">Departure Date</label>
      <input type="date" id="departure-date" name="departure-date" min="2025-01-01" max="2025-01-31" required>

      <label for="rooms" class="rooms">Room Type</label>
      <select name="rooms" id="rooms">
        <option value="economy">Economy $1/Day</option>
        <option value="standard">Standard $2/Day</option>
        <option value="luxury">Luxury $4/Day</option>
      </select>

      <div class="border-features">
        <h3>Features</h3>
        <label for="features-container" class="features-container">
          <input type="checkbox" class="feature-checkbox" data-cost="2" id="guns" name="features[]" value="guns:2">
          <label for="guns">Guns ($2)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="3" id="rifle" name="features[]" value="rifle:3">
          <label for="rifle">Rifle ($3)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="1" id="yatzy" name="features[]" value="yatzy:1">
          <label for="yatzy">Yatzy($1)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="3" id="waterboiler" name="features[]" value="waterboiler:3">
          <label for="waterboiler">Waterboiler ($3)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="8" id="unicycle" name="features[]" value="unicycle:8">
          <label for="unicycle">Unicycle ($8)</label>

        </label>
      </div>

      <h4>Transfer Code</h4>
      <input type=" text" id="transfer-code" name="transfer-code" required>

      <div class="display-cost">
        <span id="total-cost"></span>
      </div>
      <button type="submit">Book Now</button>
    </div>




  </form>



  <script src="scripts.js"></script>
</body>

</html>