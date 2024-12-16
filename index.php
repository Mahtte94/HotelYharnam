<?php


$database = new PDO("sqlite:../sql/hotel.db");

if (isset($_POST['transfer_code'])) {
  $transferCode = htmlspecialchars(trim($_POST['transfer_code']));
  $gunsCost =  isset($_POST['guns']) ? 2 : 0;
  $rifleCost = isset($_POST['rifle']) ? 3 : 0;
  $totalCost = $gunsCost + $rifleCost;
  $user = 'Mahtias';

  function isValidUuid(string $uuid): bool
  {
    if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
      return false;
    }
    return true;
  }

  function sendTransferRequest(string $transferCode, int $totalCost): string
  {
    $url = "https://www.yrgopelago.se/centralbank/transferCode";

    $data = json_encode([
      'transferCode' => $transferCode,
      'totalcost' => $totalCost
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
      $error = curl_error($ch);
      curl_close($ch);
      return 'Error: ' . $error;
    }

    curl_close($ch);

    return $response;
  }
  

  function depositTransfer(string $user, string $transferCode): string
  {
    $url = "https://www.yrgopelago.se/centralbank/deposit";

     $data = [
            'user' => $user,
            'transferCode' => $transferCode
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'application/x-www-form-urlencoded',
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
      $error = curl_error($ch);
      curl_close($ch);
      return 'Error: ' . $error;
    }

    curl_close($ch);

    return $response;
  }

  $response = depositTransfer($user, $transferCode);

  echo 'Response: ' . $response;
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
  </form>
</body>

</html>