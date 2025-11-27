<?php
// Configuration
$owner_whatsapp_number = "917470712404"; // Updated Number
$rates = [
    'sedan' => ['name' => 'Sedan (Dzire/Etios)', 'rate_per_km' => 12, 'driver_allowance' => 300, 'night_charge' => 250],
    'suv' => ['name' => 'SUV (Ertiga/Kia)', 'rate_per_km' => 16, 'driver_allowance' => 400, 'night_charge' => 300],
    'premium' => ['name' => 'Premium (Innova Crysta)', 'rate_per_km' => 22, 'driver_allowance' => 500, 'night_charge' => 400],
    'hatchback' => ['name' => 'Hatchback', 'rate_per_km' => 10, 'driver_allowance' => 250, 'night_charge' => 200],
];

$show_thanks = false;
$wa_link = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_type = $_POST['selected_car'] ?? '';
    $pickup = $_POST['pickup'] ?? 'Map Location';
    $drop = $_POST['drop'] ?? 'Map Location';
    $distance = floatval($_POST['distance'] ?? 0);
    $trip_type = $_POST['trip_type'] ?? 'one_way';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $night_halt = isset($_POST['night_halt']);

    if (isset($rates[$car_type])) {
        $rate_info = $rates[$car_type];

        $total_distance = ($trip_type === 'round_trip') ? $distance * 2 : $distance;
        $base_fare = $total_distance * $rate_info['rate_per_km'];
        $driver_allowance = $rate_info['driver_allowance'];
        $night_charge = ($night_halt) ? $rate_info['night_charge'] : 0;
        $total_fare = $base_fare + $driver_allowance + $night_charge;

        // WhatsApp Message Construction
        $wa_message = "New Booking Request! 🚗\n\n";
        $wa_message .= "*Car Type:* " . $rate_info['name'] . "\n";
        $wa_message .= "*Trip:* " . ucfirst(str_replace('_', ' ', $trip_type)) . "\n";
        $wa_message .= "*From:* $pickup\n";
        $wa_message .= "*To:* $drop\n";
        $wa_message .= "*Date/Time:* $date at $time\n";
        $wa_message .= "*Distance:* " . $total_distance . " km (est)\n";
        if ($night_halt)
            $wa_message .= "*Night Halt:* Yes\n";
        $wa_message .= "----------------\n";
        $wa_message .= "*Total Estimated Fare: ₹" . number_format($total_fare) . "*";

        $wa_link = "https://wa.me/$owner_whatsapp_number?text=" . urlencode($wa_message);
        $show_thanks = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
// Configuration
$owner_whatsapp_number = "917470712404"; // Updated Number
$rates = [
    'sedan' => ['name' => 'Sedan (Dzire/Etios)', 'rate_per_km' => 12, 'driver_allowance' => 300, 'night_charge' => 250],
    'suv' => ['name' => 'SUV (Ertiga/Kia)', 'rate_per_km' => 16, 'driver_allowance' => 400, 'night_charge' => 300],
    'premium' => ['name' => 'Premium (Innova Crysta)', 'rate_per_km' => 22, 'driver_allowance' => 500, 'night_charge' => 400],
    'hatchback' => ['name' => 'Hatchback', 'rate_per_km' => 10, 'driver_allowance' => 250, 'night_charge' => 200],
];

$show_thanks = false;
$wa_link = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_type = $_POST['selected_car'] ?? '';
    $pickup = $_POST['pickup'] ?? 'Map Location';
    $drop = $_POST['drop'] ?? 'Map Location';
    $distance = floatval($_POST['distance'] ?? 0);
    $trip_type = $_POST['trip_type'] ?? 'one_way';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $night_halt = isset($_POST['night_halt']);

    if (isset($rates[$car_type])) {
        $rate_info = $rates[$car_type];

        $total_distance = ($trip_type === 'round_trip') ? $distance * 2 : $distance;
        $base_fare = $total_distance * $rate_info['rate_per_km'];
        $driver_allowance = $rate_info['driver_allowance'];
        $night_charge = ($night_halt) ? $rate_info['night_charge'] : 0;
        $total_fare = $base_fare + $driver_allowance + $night_charge;

        // WhatsApp Message Construction
        $wa_message = "New Booking Request! 🚗\n\n";
        $wa_message .= "*Car Type:* " . $rate_info['name'] . "\n";
        $wa_message .= "*Trip:* " . ucfirst(str_replace('_', ' ', $trip_type)) . "\n";
        $wa_message .= "*From:* $pickup\n";
        $wa_message .= "*To:* $drop\n";
        $wa_message .= "*Date/Time:* $date at $time\n";
        $wa_message .= "*Distance:* " . $total_distance . " km (est)\n";
        if ($night_halt)
            $wa_message .= "*Night Halt:* Yes\n";
        $wa_message .= "----------------\n";
        $wa_message .= "*Total Estimated Fare: ₹" . number_format($total_fare) . "*";

        $wa_link = "https://wa.me/$owner_whatsapp_number?text=" . urlencode($wa_message);
        $show_thanks = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Cab Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Leaflet Control Geocoder CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <div class="container">
        <header>
            <h1>🚖 City Cabs</h1>
            <p class="subtitle">Premium rides, transparent pricing.</p>
        </header>

        <?php if ($show_thanks): ?>
            <div class="result-card" style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
                <div class="result-header" style="border: none;">Booking Request Sent!</div>
                <p style="margin-bottom: 20px; color: var(--text-light);">Redirecting you to WhatsApp to finalize details
                    with the owner...</p>

                <div class="result-total" style="justify-content: center;">
                    <span>Total: ₹<?php echo number_format($total_fare); ?></span>
                </div>

                <p style="margin-top: 20px; font-size: 0.9rem;">
                    If WhatsApp doesn't open automatically, <a href="<?php echo $wa_link; ?>">click here</a>.
                </p>

                <div style="margin-top: 30px;">
                    <a href="index.php" class="btn-submit"
                        style="text-decoration: none; display: inline-block; width: auto; padding: 10px 20px;">Book Another
                        Ride</a>
                </div>
            </div>
            <script>
                // Auto-redirect to WhatsApp
                setTimeout(function () {
                    window.location.href = "<?php echo $wa_link; ?>";
                }, 1500);
            </script>
        <?php else: ?>

            <form action="" method="POST">
                <div class="section-title">Select Car Type</div>
                <div class="car-grid">
                    <div class="car-option" data-car="hatchback">
                        <span class="car-icon">🚗</span>
                        <span class="car-name">Hatchback</span>
                        <span class="car-rate">₹10/km</span>
                    </div>
                    <div class="car-option" data-car="sedan">
                        <span class="car-icon">🚘</span>
                        <span class="car-name">Sedan</span>
                        <span class="car-rate">₹12/km</span>
                    </div>
                    <div class="car-option" data-car="suv">
                        <span class="car-icon">🚙</span>
                        <span class="car-name">SUV</span>
                        <span class="car-rate">₹16/km</span>
                    </div>
                    <div class="car-option" data-car="premium">
                        <span class="car-icon">🚐</span>
                        <span class="car-name">Innova</span>
                        <span class="car-rate">₹22/km</span>
                    </div>
                </div>
                <input type="hidden" name="selected_car" id="selected_car" required>

                <div class="section-title">Trip Details</div>

                <div class="trip-type-selector">
                    <div class="trip-type-option active" data-value="one_way">One Way</div>
                    <div class="trip-type-option" data-value="round_trip">Round Trip</div>
                </div>
                <input type="hidden" name="trip_type" id="trip_type" value="one_way">

                <!-- Map Section -->
                <div class="form-group">
                    <label>Select Route on Map</label>
                    <div id="map" style="height: 300px; border-radius: 12px; z-index: 1;"></div>
                    <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 5px;">
                        Drag markers to adjust location.
                    </p>
                </div>

                <div class="form-group">
                    <label>Pickup Location</label>
                    <div class="input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="pickup" id="pickup_input" placeholder="Type city or area..."
                            autocomplete="off" required>
                        <div id="pickup_results" class="autocomplete-results"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Destination</label>
                    <div class="input-wrapper">
                        <span class="search-icon">📍</span>
                        <input type="text" name="drop" id="drop_input" placeholder="Type destination..." autocomplete="off"
                            required>
                        <div id="drop_results" class="autocomplete-results"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Estimated Distance (km)</label>
                    <input type="number" name="distance" id="distance_input" placeholder="Auto-calculated" readonly
                        required>
                </div>

                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label>Date</label>
                        <input type="date" name="date" required>
                    </div>
                    <div>
                        <label>Time</label>
                        <input type="time" name="time" required>
                    </div>
                </div>

                <div class="form-group hidden" id="night_halt_group">
                    <label class="checkbox-group">
                        <input type="checkbox" name="night_halt" id="night_halt">
                        <div>
                            <div style="font-weight: 600;">Night Halt Required?</div>
                            <div style="font-size: 0.85rem; color: var(--text-light);">Additional night charges apply</div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn-submit">Calculate & Book</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Leaflet Control Geocoder JS -->
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>