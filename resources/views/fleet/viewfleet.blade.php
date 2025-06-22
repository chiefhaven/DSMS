@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
      <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{ $fleet->car_brand_model }}</h1>
      <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb">
          <div class="dropdown d-inline-block">
            <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="d-sm-inline-block">Action</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0">
              <div class="p-2">
                <!-- Edit Fleet Form -->
                <form method="GET" action="/editfleet/{{ $fleet->id }}">
                  @csrf
                  <button class="dropdown-item" type="submit">Edit Fleet</button>
                </form>
              </div>
            </div>
          </div>
        </ol>
      </nav>
    </div>
  </div>
</div>

<!-- Content -->
<div class="content content-full" id="vehicleRouteApp">
    <div class="block block-rounded block-bordered">
        <div class="block-content">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-4">
                <h2>{{ $fleet->car_brand_model }}</h2>
                <p><strong>Registration Number:</strong> {{ $fleet->car_registration_number }}</p>
                <p><strong>Description:</strong> {{ $fleet->car_description }}</p>

                <img src="{{ asset('public/media/fleet/'.$fleet->fleet_image) }}" alt="{{ $fleet->car_brand_model }}" class="img-fluid rounded">

                <hr>

                <p>
                    <strong>Instructor:</strong>
                    @if ($fleet->instructor)
                    {{ $fleet->instructor->fname }} {{ $fleet->instructor->sname }}
                    @else
                    <span class="text-warning">Not assigned</span>
                    @endif
                </p>

                <p>
                    <strong>Active Students:</strong>
                    {{ $fleet->student()->where('status', '!=', 'Finished')->count() }}
                </p>
            </div>

            <!-- Right Column -->
            <div class="col-md-8">
                <h4>Location and Distance Details</h4>
                <!-- Placeholder content -->
                <p class="text-muted">
                    Current location and today distance travelled, estimated fuel consumption
                    <div id="vehicleRouteMap" style="height: 600px;"></div>

                    <div class="mt-3">
                        <strong>Live Speed:</strong> @{{ liveSpeedKmh }} km/h<br>
                        <strong>Total Distance:</strong> @{{ totalDistanceKm }} km
                    </div>
                </p>
            </div>
        </div>
        </div>
    </div>
    <a href="{{ route('fleet.index') }}" class="btn btn-primary rounded-pill px-4">
        <i class="fa fa-arrow-left me-1"></i> Back to Fleet
    </a>
</div>


<!-- SweetAlert Toast -->
@if(Session::has('message'))
  <script>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: '{{ Session::get('message') }}',
      showConfirmButton: false,
      timer: 3000
    });
  </script>
@endif

<script setup>

    const vehicleRouteApp = createApp({
      setup() {
        let map;
        let marker = null;
        let polyline = null;
        let path = [];

        const vehicleId = "{{ $fleet->id }}";
        const totalDistanceKm = ref(0);
        const liveSpeedKmh = ref(0);

        // Utility to compute distance between two lat/lng (in km)
        const haversine = (lat1, lon1, lat2, lon2) => {
          const toRad = x => (x * Math.PI) / 180;
          const R = 6371; // Radius of Earth in km
          const dLat = toRad(lat2 - lat1);
          const dLon = toRad(lon2 - lon1);
          const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) *
              Math.cos(toRad(lat2)) *
              Math.sin(dLon / 2) *
              Math.sin(dLon / 2);
          const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
          return R * c;
        };

        const initMap = () => {
          map = L.map('vehicleRouteMap').setView([-13.9626, 33.7741], 12);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        };

        const updateRoute = () => {
          axios.get(`/api/show-vehicle-geo-data/${vehicleId}`).then(res => {
            const points = res.data.map(p => ({
              lat: p.latitude,
              lng: p.longitude,
              time: new Date(p.created_at)
            }));
            if (points.length === 0) return;

            console.log(points)

            // Update path
            path = points.map(p => [p.lat, p.lng]);

            // Compute total distance
            let dist = 0;
            for (let i = 1; i < points.length; i++) {
              dist += haversine(points[i - 1].lat, points[i - 1].lng, points[i].lat, points[i].lng);
            }
            totalDistanceKm.value = dist.toFixed(2);

            // Compute live speed (if at least 2 points)
            if (points.length >= 2) {
              const last = points[points.length - 1];
              const prev = points[points.length - 2];
              const d = haversine(prev.lat, prev.lng, last.lat, last.lng); // km
              const dt = (last.time - prev.time) / 1000; // seconds
              const speed = dt > 0 ? (d / dt) * 3600 : 0; // km/h
              liveSpeedKmh.value = speed.toFixed(2);
            } else {
              liveSpeedKmh.value = 0;
            }

            // Remove old polyline
            if (polyline) polyline.remove();
            polyline = L.polyline(path, {
              color: 'blue',
              weight: 4
            }).addTo(map);

            map.fitBounds(polyline.getBounds());

            // Update or create marker at last point
            const lastLatLng = path[path.length - 1];
            if (marker) {
              marker.setLatLng(lastLatLng)
                .bindPopup(`Vehicle ID: ${vehicleId}<br>Speed: ${liveSpeedKmh.value} km/h`)
                .openPopup();
            } else {
              marker = L.marker(lastLatLng)
                .addTo(map)
                .bindPopup(`Vehicle ID: ${vehicleId}<br>Speed: ${liveSpeedKmh.value} km/h`)
                .openPopup();
            }
          }).catch(err => {
            console.error('Error fetching vehicle route:', err);
          });
        };

        onMounted(() => {
          initMap();
          updateRoute();
          setInterval(updateRoute, 5000); // Update every 5 sec
        });

        return {
          totalDistanceKm,
          liveSpeedKmh
        };
      }
    });

    vehicleRouteApp.mount('#vehicleRouteApp');
</script>
@endsection
