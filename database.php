<?php

declare(strict_types=1);

require __DIR__ . "/functions.php";
require __DIR__ . "/booking.php";


$database = new PDO('sqlite:hotel.db');

$jsonResponse = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $transferCode = htmlspecialchars(trim($_POST['transfer-code']));
  $features = $_POST['features'];
  $user = "Mahtias";
  $island = "Yharnam";
  $hotel = "Hotel Yharnam";
  $stars = 3;
  $greeting = "Thank you for choosing Hotel Yharnam";
  $img = "https://www.well-played.com.au/wp-content/uploads/2021/01/Bloodborne-keyart1.jpg";
  $totalCost = 0;

  $jsonOutput = '';
  $successMessage = '';

  $arrivalDate = $_POST['arrival-date'];
  $departureDate = $_POST['departure-date'];



  if (!$arrivalDate || !$departureDate) {
    $jsonResponse = json_encode(['error' => 'Both arrival and departure dates are required.']);
  } elseif ($departureDate <= $arrivalDate) {
    $jsonResponse = json_encode(['error' => 'Departure date must be after arrival date.']);
  }

  $start = new DateTime($arrivalDate);
  $end = new DateTime($departureDate);
  $totalDays = $start->diff($end)->days + 1;

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


  $stmt = $database->prepare("SELECT id FROM Rooms WHERE name = :name");
  $stmt->execute([':name' => $roomType]);
  $roomId = $stmt->fetchColumn();

  if (isRoomAvailable($database, $roomType, $arrivalDate, $departureDate)) {

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

              // Insert Booking-Features relation
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
          echo json_encode(['error' => $e->getMessage()]);
        }
      } else {
        $jsonResponse = json_encode(['error' => 'Not enough currency.']);
      }
    }
  } else {
    $jsonResponse = json_encode(['error' => 'The selected room is already booked for the chosen dates.']);
  }
}

if ($jsonResponse !== null) {
  header('Content-Type: application/json');
  echo $jsonResponse;
  exit;
}
