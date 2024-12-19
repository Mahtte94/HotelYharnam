<?php

require __DIR__ . "/functions.php";

// $database = new PDO("sqlite: hotel.db");

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