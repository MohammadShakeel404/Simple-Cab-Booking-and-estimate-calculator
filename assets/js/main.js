document.addEventListener('DOMContentLoaded', function () {
    // UI Elements
    const tripTypeOptions = document.querySelectorAll('.trip-type-option');
    const tripTypeInput = document.getElementById('trip_type');
    const nightHaltGroup = document.getElementById('night_halt_group');
    const carOptions = document.querySelectorAll('.car-option');
    const selectedCarInput = document.getElementById('selected_car');

    // Map Variables
    let map, pickupMarker, dropMarker, routeLayer;
    let pickupCoords = null;
    let dropCoords = null;

    // Initialize Map
    if (document.getElementById('map')) {
        // CartoDB Voyager Tiles (Premium Look)
        map = L.map('map').setView([22.7196, 75.8577], 11); // Indore
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        map.on('click', function (e) {
            handleMapClick(e.latlng);
        });

        // Add Search Control to Map
        if (L.Control.Geocoder) {
            L.Control.geocoder({
                defaultMarkGeocode: false
            })
                .on('markgeocode', function (e) {
                    var bbox = e.geocode.bbox;
                    var poly = L.polygon([
                        bbox.getSouthEast(),
                        bbox.getNorthEast(),
                        bbox.getNorthWest(),
                        bbox.getSouthWest()
                    ]).addTo(map);
                    map.fitBounds(poly.getBounds());

                    // Optional: Set as pickup if not set
                    // handleMapClick(e.geocode.center); 
                })
                .addTo(map);
        }
    }

    // Autocomplete Logic
    setupAutocomplete('pickup_input', 'pickup_results', (lat, lon, name) => {
        const latlng = { lat: lat, lng: lon };
        setPickup(latlng, name);
        map.setView(latlng, 16);
    });

    setupAutocomplete('drop_input', 'drop_results', (lat, lon, name) => {
        const latlng = { lat: lat, lng: lon };
        setDrop(latlng, name);
        map.setView(latlng, 16);
    });

    function setupAutocomplete(inputId, resultsId, onSelect) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        let debounceTimer;

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value;

            if (query.length < 3) {
                results.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=5`)
                    .then(res => res.json())
                    .then(data => {
                        results.innerHTML = '';
                        if (data.features && data.features.length > 0) {
                            results.style.display = 'block';
                            data.features.forEach(feature => {
                                const div = document.createElement('div');
                                div.className = 'autocomplete-item';
                                // Construct readable address
                                const p = feature.properties;
                                const name = p.name;
                                const city = p.city || p.town || p.village || '';
                                const state = p.state || '';
                                const label = `${name}${city ? ', ' + city : ''}${state ? ', ' + state : ''}`;

                                div.textContent = label;
                                div.addEventListener('click', () => {
                                    input.value = label;
                                    results.style.display = 'none';
                                    onSelect(feature.geometry.coordinates[1], feature.geometry.coordinates[0], label);
                                });
                                results.appendChild(div);
                            });
                        } else {
                            results.style.display = 'none';
                        }
                    });
            }, 300);
        });

        // Hide on click outside
        document.addEventListener('click', function (e) {
            if (e.target !== input && e.target !== results) {
                results.style.display = 'none';
            }
        });
    }

    function handleMapClick(latlng) {
        if (!pickupCoords) {
            setPickup(latlng);
        } else if (!dropCoords) {
            setDrop(latlng);
        } else {
            // Reset
            pickupCoords = latlng;
            dropCoords = null;
            if (pickupMarker) map.removeLayer(pickupMarker);
            if (dropMarker) map.removeLayer(dropMarker);
            if (routeLayer) map.removeLayer(routeLayer);

            document.getElementById('drop_input').value = '';
            document.getElementById('distance_input').value = '';

            setPickup(latlng);
        }
    }

    function setPickup(latlng, address = null) {
        pickupCoords = latlng;
        if (pickupMarker) map.removeLayer(pickupMarker);
        pickupMarker = L.marker(latlng, { icon: createIcon('green'), draggable: true }).addTo(map);

        pickupMarker.on('dragend', function (e) {
            pickupCoords = e.target.getLatLng();
            reverseGeocode(pickupCoords, 'pickup_input');
            if (dropCoords) calculateRoute();
        });

        if (address) {
            document.getElementById('pickup_input').value = address;
        } else {
            document.getElementById('pickup_input').value = "Fetching...";
            reverseGeocode(latlng, 'pickup_input');
        }

        if (dropCoords) calculateRoute();
    }

    function setDrop(latlng, address = null) {
        dropCoords = latlng;
        if (dropMarker) map.removeLayer(dropMarker);
        dropMarker = L.marker(latlng, { icon: createIcon('red'), draggable: true }).addTo(map);

        dropMarker.on('dragend', function (e) {
            dropCoords = e.target.getLatLng();
            reverseGeocode(dropCoords, 'drop_input');
            if (pickupCoords) calculateRoute();
        });

        if (address) {
            document.getElementById('drop_input').value = address;
        } else {
            document.getElementById('drop_input').value = "Fetching...";
            reverseGeocode(latlng, 'drop_input');
        }

        calculateRoute();
    }

    function reverseGeocode(latlng, inputId) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latlng.lat}&lon=${latlng.lng}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById(inputId).value = data.display_name;
            });
    }

    function calculateRoute() {
        if (!pickupCoords || !dropCoords) return;

        const url = `https://router.project-osrm.org/route/v1/driving/${pickupCoords.lng},${pickupCoords.lat};${dropCoords.lng},${dropCoords.lat}?overview=full&geometries=geojson`;

        document.getElementById('distance_input').placeholder = "Calculating...";

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const distKm = (route.distance / 1000).toFixed(1);
                    document.getElementById('distance_input').value = distKm;

                    // Draw Route
                    if (routeLayer) map.removeLayer(routeLayer);
                    routeLayer = L.geoJSON(route.geometry, {
                        style: { color: '#2563eb', weight: 5, opacity: 0.7 }
                    }).addTo(map);

                    map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
                }
            });
    }

    function createIcon(color) {
        return new L.Icon({
            iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
    }

    // Form Interactions
    tripTypeOptions.forEach(option => {
        option.addEventListener('click', () => {
            tripTypeOptions.forEach(opt => opt.classList.remove('active'));
            option.classList.add('active');
            tripTypeInput.value = option.dataset.value;

            if (option.dataset.value === 'round_trip') {
                nightHaltGroup.classList.remove('hidden');
            } else {
                nightHaltGroup.classList.add('hidden');
                document.getElementById('night_halt').checked = false;
            }
        });
    });

    carOptions.forEach(option => {
        option.addEventListener('click', () => {
            carOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
            selectedCarInput.value = option.dataset.car;
        });
    });

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!selectedCarInput.value) {
                e.preventDefault();
                alert('Please select a car type.');
                return;
            }
            if (!document.getElementById('distance_input').value) {
                e.preventDefault();
                alert('Please select pickup and drop locations.');
                return;
            }
        });
    }
});
