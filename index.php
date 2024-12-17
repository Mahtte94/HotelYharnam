<?php

require __DIR__ . "/functions.php";

$database = new PDO("sqlite:../sql/hotel.db");

if (isset($_POST['transfer_code'])) {
  $transferCode = htmlspecialchars(trim($_POST['transfer_code']));
  $gunsCost =  isset($_POST['guns']) ? 2 : 0;
  $rifleCost = isset($_POST['rifle']) ? 3 : 0;
  $totalCost = $gunsCost + $rifleCost;
  $user = 'Mahtias';


  if (!isValidUuid($transferCode)) {

    echo "Not valid";

  } else {

    $balance = sendTransferRequest($transferCode, $totalCost);

    if ($balance >= $totalCost && $totalCost != 0) {

      $deposit = depositTransfer($user, $transferCode);

      echo "Deposit succesful:$deposit";

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
  </form>
</body>

</html>