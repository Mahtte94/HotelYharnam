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
  $stars = 4;
  $greeting = "Thank you for choosing Hotel Yharnam";
  $img = "https://www.well-played.com.au/wp-content/uploads/2021/01/Bloodborne-keyart1.jpg";
  $featureCost = 0;

  $jsonOutput = '';
  $successMessage = '';

  $arrivalDate = $_POST['arrival-date'];
  $departureDate = $_POST['departure-date'];
  $start = new DateTime($arrivalDate);
  $end = new DateTime($departureDate);
  $totalDays = $start->diff($end)->days + 1;

 if (!$arrivalDate || !$departureDate) {
    $jsonResponse = json_encode(['error' => 'Both arrival and departure dates are required.']);
    header('Content-Type: application/json');
    echo $jsonResponse;
    exit;
  }
  if ($end <= $start) {
    $jsonResponse = json_encode(['error' => 'Departure date must be after arrival date.']);
    header('Content-Type: application/json');
    echo $jsonResponse;
    exit;
    }
  

  $roomType = $_POST['rooms'];
  $roomCost = calculateRoomCost($roomType, $totalDays);

  if (isset($features)) {
    foreach ($features as $feature) {
        list($featureName, $featurePrice) = explode(':', $feature);
        $featureCost += $featurePrice;
    }
}
$totalCost = $roomCost + $featureCost + $totalDays;


  $data = [
    'island' => $island,
    'hotel' => $hotel,
    'arrival-date' => $arrivalDate,
    'departure-date' => $departureDate,
    'total-cost' => $totalCost,
    'stars' =>  $stars,
    'features' => $features,
    'additional-info' => [
      'greeting' => $greeting,
      'imageUrl' => $img
    ]
  ];


  $data["total-cost"] = $totalCost;
  $json = json_encode($data, JSON_PRETTY_PRINT);


  

  if (isRoomAvailable($database, $roomType, $arrivalDate, $departureDate)) {

    if (!isValidUuid($transferCode)) {

      $jsonResponse = json_encode(['error' => 'Not valid transfercode']);
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
          
          

          $stmt = $database->prepare("SELECT id FROM Rooms WHERE name = :name");
          $stmt->execute([':name' => $roomType]);
          $roomId = $stmt->fetchColumn();
          
          

          // Insert Booking-Rooms relation
          $stmt = $database->prepare("INSERT INTO Booking_Rooms (bookingId, roomId) VALUES (:bookingId, :roomId)");
          $stmt->execute([
            ':bookingId' => $bookingId,
            ':roomId' => $roomId
          ]);


          
            
            foreach ($features as $feature) {
              list($featureName, $featurePrice) = explode(':', $feature);

              // Fetch featureId based on featureName
              $stmt = $database->prepare("SELECT id FROM Features WHERE name = :name");
              $stmt->execute([':name' => $featureName]);
              $featureId = $stmt->fetchColumn();

              // Insert Booking-Features relation
              $stmt = $database->prepare("INSERT INTO Booking_Features (bookingId, featureId) VALUES (:bookingId, :featureId)");
              $stmt->execute([
                ':bookingId' => $bookingId,
                ':featureId' => $featureId
              ]);
            
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
