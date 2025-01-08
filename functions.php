<?php

declare(strict_types=1);

function isValidUuid(string $uuid): bool
{
  if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
    return false;
  }
  return true;
}

function sendTransferRequest(string $transferCode, int $totalCost): int
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

  $decodedResponse = json_decode($response, true);

  return $decodedResponse['totalCost'] ?? 0;
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

function calculateRoomCost(string $roomType, float $totalDays): float {
  if ($totalDays > 3) {
      switch ($roomType) {
          case 'economy':
              return round((1 / 0.30) * $totalDays);
          case 'standard':
              return round((2 / 0.30) * $totalDays);
          case 'luxury':
              return round((4 / 0.30) * $totalDays);
          default:
              return 0;
      }
  } else {
      switch ($roomType) {
          case 'economy':
              return 1 * $totalDays;
          case 'standard':
              return 2 * $totalDays;
          case 'luxury':
              return 4 * $totalDays;
          default:
              return 0;
      }
  }
}
