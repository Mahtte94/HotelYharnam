<?php

function isRoomAvailable($database, $roomId, $arrivalDate, $departureDate)
{
  $stmt = $database->prepare("
      SELECT *
      FROM Bookings
      INNER JOIN Booking_Rooms ON Bookings.id = Booking_Rooms.bookingId
      WHERE Booking_Rooms.roomId = :roomId
        AND (Bookings.arrival < :departureDate AND Bookings.departure > :arrivalDate)
  ");
  $stmt->execute([
    ':roomId' => $roomId,
    ':arrivalDate' => $arrivalDate,
    ':departureDate' => $departureDate,
  ]);
  return $stmt->fetch() === false;
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


function generateRoomCalendar($roomName, $bookings, $year)
{
  // Create an array to track booked dates
  $bookedDates = [];
  foreach ($bookings as $booking) {
    $start = new DateTime($booking['arrival']);
    $end = new DateTime($booking['departure']);
    while ($start <= $end) {
      $bookedDates[] = $start->format('Y-m-d');
      $start->modify('+1 day');
    }
  }

  // Get today's date for comparison
  $today = (new DateTime())->format('Y-m-d');

  echo "<article class='calendar-container'>";
  echo "<header class='calendar-title'>Room: $roomName - January $year</header>";
  echo "<section class='calendar-grid'>";

  // Weekday headers
  $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ];
  foreach ($weekdays as $day) {
    echo "<header class='calendar-header'>$day</header>";
  }

  // Start generating the calendar for January
  $date = new DateTime("$year-01-01");
  $startDayOfWeek = (int) $date->format('w');
  $currentDay = 1;
  $totalDays = 31;

  // Fill empty cells before January 1st
  for ($i = 0; $i < $startDayOfWeek; $i++) {
    echo "<div class='calendar-day day-empty' aria-hidden='true'></div>";
  }

  // Fill days of January
  while ($currentDay <= $totalDays) {
    $currentDate = $date->format('Y-m-d');
    $isBooked = in_array($currentDate, $bookedDates);

    // Determine if today is the current date
    $isToday = ($currentDate === $today);

    if ($isBooked) {
      echo "<div class='calendar-day day-booked" . ($isToday ? " today" : "") . "' role='gridcell'>$currentDay</div>";
    } else {
      echo "<div class='calendar-day day-available" . ($isToday ? " today" : "") . "' role='gridcell'>$currentDay</div>";
    }

    $currentDay++;
    $date->modify('+1 day');
  }

  // Fill empty cells after January 31st
  for ($i = (int) $date->format('w'); $i < 7 && (int) $date->format('w') > 0; $i++) {
    echo "<div class='calendar-day day-empty' aria-hidden='true'></div>";
  }

  echo "</section>"; // Close calendar grid
  echo "</article>"; // Close calendar container
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

  foreach (ROOM_TYPES as $roomName) {
    $bookings = getRoomBookingsForJanuary($database, $roomName, $year);
    generateRoomCalendar($roomName, $bookings, $year);
  }
}
