@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Vehicle location</h1>
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

    const { createApp, ref, reactive, onMounted } = Vue

    const vehicleLocation = createApp({
      setup() {
        let map;
        const markers = {};

        const initMap = () => {
          // Use a neutral default center
          map = L.map('vehicleLocation').setView([-13.9626, 33.7741], 12);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        };

        const updateLocations = () => {
          axios.get('/api/get-all-vehicle-locations').then(res => {
            const data = res.data;
            const bounds = [];

            data.forEach(item => {
              const latLng = [item.latitude, item.longitude];
              bounds.push(latLng);

              if (markers[item.fleet]) {
                // Update existing marker
                markers[item.fleet].setLatLng(latLng);
              } else {
                markers[item.fleet] = L.marker(latLng)
                .addTo(map)
                .bindPopup(`<strong>${item.registration_number || item.fleet.car_brand_model}</strong><br>${item.registration_number || item.fleet.car_registration_number}`)
                .openPopup();
              }
            });


          }).catch(err => {
            console.error('Error fetching vehicle locations:', err);
          });
        };

        onMounted(() => {
          initMap();
          updateLocations();
          setInterval(updateLocations, 2000); // Update every 2 seconds
        });

        return {};
      }
    });

    vehicleLocation.mount('#vehicleLocation');
</script>
@endsection
