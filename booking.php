<?php

declare(strict_types=1);

function isRoomAvailable($database, $roomType, $arrivalDate, $departureDate)
{
    $stmt = $database->prepare("
        SELECT COUNT(*) as count
        FROM Bookings b
        INNER JOIN Booking_Rooms br ON b.id = br.bookingId
        INNER JOIN Rooms r ON br.roomId = r.id
        WHERE r.name = :roomType
          AND (
              (:arrivalDate < b.departure AND :departureDate > b.arrival)
          )
    ");
    $stmt->execute([
        ':roomType' => $roomType,
        ':arrivalDate' => $arrivalDate,
        ':departureDate' => $departureDate
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] == 0;
  }

// Fetch all bookings for a specific room in January
function getRoomBookingsForJanuary($database, $roomName, $year)
{
  $stmt = $database->prepare("
      SELECT b.arrival, b.departure
      FROM Bookings b
      INNER JOIN Booking_Rooms br ON b.id = br.bookingId
      INNER JOIN Rooms r ON br.roomId = r.id
      WHERE r.name = :roomName
        AND (
            (b.arrival BETWEEN :start AND :end)
            OR (b.departure BETWEEN :start AND :end)
            OR (b.arrival <= :start AND b.departure >= :end)
        )
  ");
  $startOfJanuary = "$year-01-01";
  $endOfJanuary = "$year-01-31";
  $stmt->execute([
    ':roomName' => $roomName,
    ':start' => $startOfJanuary,
    ':end' => $endOfJanuary,
  ]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function generateRoomCalendar($bookings, $year)
{
  $monthName = 'January';
  $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  $bookedDates = [];

  foreach ($bookings as $booking) {
    $start = new DateTime($booking['arrival']);
    $end = new DateTime($booking['departure']);
    while ($start <= $end) {
      $bookedDates[] = $start->format('Y-m-d');
      $start->modify('+1 day');
    }
  }

  $today = (new DateTime())->format('Y-m-d');
  $date = new DateTime("$year-01-01");
  $startDayOfWeek = $date->format('w');
  $totalDays = 31;
  $calendar = [];

  // Add header
  $calendar['header'] = ['month' => $monthName, 'weekdays' => $weekdays];

  // Add empty cells before the 1st
  for ($i = 0; $i < $startDayOfWeek; $i++) {
    $calendar['days'][] = ['day' => '', 'isBooked' => false, 'isToday' => false];
  }

  // Add days of the month
  for ($day = 1; $day <= $totalDays; $day++) {
    $currentDate = $date->format('Y-m-d');
    $calendar['days'][] = [
      'day' => $day,
      'isBooked' => in_array($currentDate, $bookedDates),
      'isToday' => $currentDate === $today
    ];
    $date->modify('+1 day');
  }

  return $calendar;
}


// Fetch all unique rooms from the database for January bookings
function getUniqueRoomsForBookings($database, $year)
{
  // Fetch all distinct roomIds that have bookings in January
  $stmt = $database->prepare("
        SELECT DISTINCT  r.name 
        FROM Rooms r
        INNER JOIN Booking_Rooms br ON br.roomId = r.id
        INNER JOIN Bookings b ON br.bookingId = b.id
        WHERE b.arrival BETWEEN :start AND :end
           OR b.departure BETWEEN :start AND :end
           OR (b.arrival <= :start AND b.departure >= :end)
    ");
  $startOfJanuary = "$year-01-01";
  $endOfJanuary = "$year-01-31";
  $stmt->execute([
    ':start' => $startOfJanuary,
    ':end' => $endOfJanuary,
  ]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

const ROOM_TYPES = ['economy', 'standard', 'luxury'];

function generateAllCalendars($database)
{
  $year = 2025;
  $allCalendars = [];

  foreach (ROOM_TYPES as $roomName) {
    $bookings = getRoomBookingsForJanuary($database, $roomName, $year);
    $calendarData = generateRoomCalendar($bookings, $year);
    $allCalendars[$roomName] = $calendarData;
  }

  return $allCalendars;
}
