<?php
require __DIR__ . "/header.php";
require __DIR__ . "/functions.php";
require __DIR__ . "/booking.php";
require __DIR__ . "/db_connect.php";



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require __DIR__ . "/database.php";
  exit;
}


?>

<main>
  <header class="nav-header">
    <nav class="navbar">
      <h1>Hotel Yharnam</h1>
      <span class="star">★</span>
      <span class="star">★</span>
      <span class="star">★</span>
      <span class="star">★</span>
    </nav>
  </header>

  <section class="hero">
    <img src="images/HotelYharnam.webp" alt="Hotel Yharnam">
    <div class="fog">
      <h2>Welcome traveller to the "safest" place in Yharnam!</h2>
    </div>
  </section>



  <article class="rooms-container">
    <?php foreach ($rooms as $room): ?>
      <div class="room">
        <img src="images/<?= htmlspecialchars($room['name']) ?>.jpeg" alt="<?= htmlspecialchars($room['name']) ?> Room">
        <div class="room-content">
          <h3><?= htmlspecialchars($room['name']) ?></h3>
          
          <p><?= htmlspecialchars($room['description']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
</article>
    
  <?php
  // Generate and display calendars
  $calendars = generateAllCalendars($database);
  ?>

  <div class="calendar-container">
    <?php foreach ($calendars as $roomType => $calendar): ?>
      <div class="calendar">
        <h2><?php echo ucfirst($roomType); ?> Room Calendar</h2>
        <div class="calendar-grid">
          <?php
          $counter = 0;
          foreach ($calendar['header']['weekdays'] as $weekday): ?>
            <div class="weekday-header"><?php echo $weekday; ?></div>
          <?php endforeach;

          foreach ($calendar['days'] as $day):
            $classes = [];
            if ($day['isToday']) $classes[] = 'today';
            if ($day['isBooked']) $classes[] = 'booked';
          ?>
            <div class="calendar-day <?php echo implode(' ', $classes); ?>">
              <?php echo $day['day']; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>


  <form method="POST" action="database.php">

    <div class="form-container">
      <label for="arrival-date" class="arrival-date">Arrival Date</label>
      <input type="date" id="arrival-date" name="arrival-date" min="2025-01-01" max="2025-01-31" required>

      <label for="departure-date" class="departure-date">Departure Date</label>
      <input type="date" id="departure-date" name="departure-date" min="2025-01-01" max="2025-01-31" required>
      <br>
      <label for="rooms" class="rooms">Room Type</label>
      <select name="rooms" id="rooms">
        <option value="economy">Economy $1/Day</option>
        <option value="standard">Standard $2/Day</option>
        <option value="luxury">Luxury $4/Day</option>
      </select>

      <div class="border-features">
    <h3>Features</h3>
    <div class="features-container">
        <?php foreach ($features as $feature): ?>
            <input type="checkbox" class="feature-checkbox" 
                   data-cost="<?= htmlspecialchars($feature['price']) ?>" 
                   id="feature-<?= htmlspecialchars($feature['id']) ?>" 
                   name="features[]" 
                   value="<?= htmlspecialchars($feature['name']) . ':' . htmlspecialchars($feature['price']) ?>">
            <label for="feature-<?= htmlspecialchars($feature['id']) ?>">
                <?= htmlspecialchars($feature['name']) ?> ($<?= htmlspecialchars($feature['price']) ?>)
            </label>
        <?php endforeach; ?>
    </div>
</div>

      <h4>Transfer Code</h4>
      <input type="text" id="transfer-code" name="transfer-code" required>

      <div class="display-cost">
        <span id="total-cost"></span>
      </div>
      <span id="discount-message" class="discount" style="display: none">You have activated the 30% discount!</span>
      <button type="submit">Book Now</button>
    </div>

    <div class="environment-section">
      <div class="environment-container">
        <div class="environment active" id="first-environment"></div>
        <div class="environment" id="second-environment"></div>
        <div class="environment" id="third-environment"></div>
      </div>
      <p class="discount-text">Book more than 3 days to get 30%°!(features excluded)</p>
    </div>


  </form>
</main>

<?php
require_once __DIR__ . "/footer.php";
?>