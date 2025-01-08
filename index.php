<?php
require __DIR__ . "/functions.php";
require __DIR__ . "/booking.php";

$database = new PDO('sqlite:hotel.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require __DIR__ . "/database.php";
  exit;
}


require_once __DIR__ . "/header.php";
?>



<main>
  <header class="nav-header">
    <nav class="navbar">
      <h1>Hotel Yharnam</h1>
    </nav>
  </header>

  <section class="hero">
    <img src="images/HotelYharnam.webp" alt="Hotel Yharnam">
    <div class="fog">
      <h2>Welcome traveller to the "safest" place in Yharnam!</h2>
    </div>
  </section>


  <article class="rooms-container">
    <div class="room">
      <img src="images/budget.jpeg" alt="Budget Room">
      <div class="room-content">
        <h3>Economy Room</h3>
        <p>Bare essentials and creeping dread. Survival is not guaranteed, but the key is yours.</p>
      </div>
    </div>
    <div class="room">
      <img src="images/standard.jpeg" alt="Standard Room">
      <div class="room-content">
        <h3>Standard Room</h3>
        <p>Modest comfort with a touch of unease. The walls may whisper, but rest is possible—if you’re brave enough.</p>
      </div>
    </div>
    <div class="room">
      <img src="images/luxury.jpeg" alt="Luxury Room">
      <div class="room-content">
        <h3>Luxury Room</h3>
        <p>Lavish and perilous. Shadows covet your comfort as much as your sanity.</p>
      </div>
    </div>
  </article>

  <?php
  // Generate and display calendars
  $calendars = generateAllCalendars($database);
  ?>

  <div class="calendar-container">
    <?php foreach ($calendars as $roomType => $calendar): ?>
      <div class="calendar">
        <h2><?php echo ucfirst($roomType); ?> Room Calendar</h2>
        <table>
          <thead>
            <tr>
              <?php foreach ($calendar['header']['weekdays'] as $weekday): ?>
                <th><?php echo $weekday; ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <tr>
              <?php
              $counter = 0;
              foreach ($calendar['days'] as $day):
                if ($counter % 7 == 0 && $counter != 0): ?>
            </tr>
            <tr>
            <?php endif; ?>
            <td class="<?php
                        echo $day['isToday'] ? 'today' : '';
                        echo $day['isBooked'] ? ' booked' : '';
                        ?>">
              <?php echo $day['day']; ?>
            </td>
            <?php $counter++; ?>
          <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
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
        <label for="features-container" class="features-container">
          <input type="checkbox" class="feature-checkbox" data-cost="2" id="guns" name="features[]" value="guns:2">
          <label for="guns">Guns ($2)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="3" id="rifle" name="features[]" value="rifle:3">
          <label for="rifle">Rifle ($3)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="1" id="yatzy" name="features[]" value="yatzy:1">
          <label for="yatzy">Yatzy($1)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="3" id="waterboiler" name="features[]" value="waterboiler:3">
          <label for="waterboiler">Waterboiler ($3)</label>

          <input type="checkbox" class="feature-checkbox" data-cost="8" id="unicycle" name="features[]" value="unicycle:8">
          <label for="unicycle">Unicycle ($8)</label>

        </label>
      </div>

      <h4>Transfer Code</h4>
      <input type="text" id="transfer-code" name="transfer-code" required>

      <div class="display-cost">
        <span id="total-cost"></span>
      </div>
      <span id="discount-message" class="discount" style="display: none">You have activated the 30% discount</span>
      <button type="submit">Book Now</button>
    </div>

    <div class="environment-container">
      <div class="environment" id="first-environment">
      </div>
      <div class="environment" id="second-environment">
      </div>
      <div class="environment" id="third-environment">
      </div>
    </div>
  </form>
</main>

<?php
require_once __DIR__ . "/footer.php";
?>