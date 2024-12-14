<?php

$baseUrl = "https://www.yrgopelago.se/centralbank";
$endPoint = "/transferCode";

if (isset($_POST['transfer_code'])) {
    $transferCode = htmlspecialchars(trim($_POST['transfer_code']));

   
  
  function isValidUuid(string $uuid): bool
{
    if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
        return false;
    }
    return true;
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
    <label for="transfer_code">transferCode</label>
    <input type="text" id="transfer_code" name="transfer_code" required>
    <button type="submit">Book Now</button>
  </form>
  
  <script>
      document.querySelector('form').addEventListener('submit', function(e) {
       e.preventDefault();
    
    const transferCode = document.getElementById('transfer_code').value;
    
    fetch('https://www.yrgopelago.se/centralbank/transferCode', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            transferCode: transferCode,
            totalcost: 11,
        }),
    })
    .then(response => response.json())
    .then(data => console.log(data))
    .catch(error => console.error('Error:', error));
});
  </script>
</body>
</html>