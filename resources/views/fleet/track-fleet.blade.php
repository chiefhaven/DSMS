@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Fleet location</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <div class="dropdown d-inline-block">

            </div>
          </ol>
        </nav>
      </div>
    </div>
  </div>

<div class="content content-full">
    <div class="block block-rounded">
        <div id="vehicleLocation" style="height: 500px;"></div>
    </div>
</div>
<script setup>
const vehicleLocation = createApp({
  setup() {
    let map;
    const markers = {};
    let intervalId;
    let hasFittedBounds = false;

    // Create custom marker icon
    const createCustomIcon = (color = 'blue') => {
      return L.divIcon({
        className: 'custom-marker',
        html: `<div style="
          background-color: ${color};
          width: 24px;
          height: 24px;
          border-radius: 50% 50% 50% 0;
          transform: rotate(-45deg);
          border: 2px solid white;
          box-shadow: 0 0 10px rgba(0,0,0,0.3);
        "></div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
        popupAnchor: [0, -12]
      });
    };

    // Determine marker color based on vehicle state
    const getVehicleColor = (item) => {
      if (item.status === 'offline') return 'gray';
      if (item.speed && item.speed > 0) return 'green';
      return 'blue';
    };

    // Initialize map
    const initMap = () => {
      map = L.map('vehicleLocation').setView([-13.9626, 33.7741], 12);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
    };

    // Update vehicle locations
    const updateLocations = async () => {
      try {
        const res = await axios.get('/get-all-vehicle-locations');
        const data = res.data;

        const bounds = [];
        const currentVehicles = new Set();

        data.forEach(item => {
          if (!item.latitude || !item.longitude || !item.fleet) return;

          const fleetId = item.fleet.id;
          if (!fleetId) return;

          const latLng = [
            parseFloat(item.latitude),
            parseFloat(item.longitude)
          ];

          bounds.push(latLng);
          currentVehicles.add(fleetId);

          const displayName =
            item.registration_number ||
            item.fleet.car_brand_model ||
            'Unknown Vehicle';

          const regNumber =
            item.fleet.car_registration_number || '';

          const color = getVehicleColor(item);

          // Update existing marker
          if (markers[fleetId]) {
            markers[fleetId].setLatLng(latLng);
          } else {
            // Create new marker
            const marker = L.marker(latLng).addTo(map);

            marker.bindPopup(`
              <strong>${displayName}</strong><br>
              Reg: ${regNumber}<br>
              Speed: ${item.speed ?? 'N/A'} km/h<br>
            `);

            markers[fleetId] = marker;
          }
        });

        // Remove old markers
        Object.keys(markers).forEach(fleetId => {
          if (!currentVehicles.has(parseInt(fleetId))) {
            map.removeLayer(markers[fleetId]);
            delete markers[fleetId];
          }
        });

        // Fit bounds ONLY once
        if (bounds.length > 0 && !hasFittedBounds) {
          map.fitBounds(bounds, { padding: [50, 50] });
          hasFittedBounds = true;
        }

      } catch (err) {
        console.error('Error fetching vehicle locations:', err);
      }
    };

    onMounted(() => {
      initMap();
      updateLocations();

      // Poll every 5 seconds (better performance)
      intervalId = setInterval(updateLocations, 5000);
    });

    onUnmounted(() => {
      if (intervalId) clearInterval(intervalId);
      if (map) map.remove();
    });

    return {};
  }
});

vehicleLocation.mount('#vehicleLocation');
</script>
@endsection
