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
                <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-cog me-1"></i>
                  <span class="d-none d-sm-inline-block">Actions</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                  <!-- Edit Fleet -->
                  <form method="GET" action="/editfleet/{{ $fleet->id }}">
                    @csrf
                    <button class="dropdown-item d-flex align-items-center py-2" type="submit">
                      <i class="fa fa-edit me-2 text-primary"></i>
                      <span>Edit Fleet</span>
                    </button>
                  </form>
                  <a class="dropdown-item d-flex align-items-center py-2" href="#">
                    <i class="fa fa-user-plus me-2 text-success"></i>
                    <span>Assign Instructor</span>
                  </a>
                </div>
            </div>
        </ol>
      </nav>
    </div>
  </div>
</div>

<!-- Content -->
<div class="content content-full" id="vehicleRouteApp">
    <div class="block block-rounded block-bordered shadow-sm">
        <div class="block-content p-4">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-4 pe-md-4 border-end-md">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0 text-primary">{{ $fleet->car_brand_model }}</h2>
                        <span class="badge bg-success">{{ $fleet->car_registration_number }}</span>

                    </div>
                    <p class="text-muted mb-3">Designated license class: {{ $fleet->licenceClass->class ?? 'Class not defined' }}</p>
                    <div class="mb-4">
                        <p class="text-muted mb-3">{{ $fleet->car_description }}</p>

                        <div class="mb-4">
                            <img src="{{ asset('public/media/fleet/'.$fleet->fleet_image) }}"
                                 alt="{{ $fleet->car_brand_model }}"
                                 class="img-fluid rounded-3 shadow-sm w-100"
                                 style="height: 220px; object-fit: cover; object-position: center;"
                                 onerror="this.onerror=null; this.src='{{ asset('path/to/default/car.jpg') }}'">
                        </div>

                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="fw-semibold">Instructor</span>
                                <span>
                                    @if ($fleet->instructor)
                                        {{ $fleet->instructor->fname }} {{ $fleet->instructor->sname }}
                                    @else
                                        <span class="text-warning">Not assigned</span>
                                    @endif
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="fw-semibold">Active Students</span>
                                <span class="badge bg-primary">
                                    {{ $fleet->student()->where('status', '!=', 'Finished')->count() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-8 ps-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Location and Distance Details</h4>
                        <div class="text-end">
                            <span class="badge bg-info">Live Tracking</span>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div id="vehicleRouteMap" style="height: 300px;" class="rounded-3"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light border-0 p-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-tachometer-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Current Speed</h6>
                                        <p class="h4 mb-0 fw-bold">@{{ liveSpeedKmh }} <small class="fs-sm">km/h</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border-0 p-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-road fa-2x text-success me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Todays Distance</h6>
                                        <p class="h4 mb-0 fw-bold">@{{ totalDistanceKm }} <small class="fs-sm">km</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong>Estimated fuel consumption:</strong> Calculated based on current driving patterns.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('fleet.index') }}" class="btn btn-outline-primary rounded-pill px-4">
            <i class="fa fa-arrow-left me-1"></i> Back to Fleet
        </a>
    </div>
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

          const vehicleData = {
            id: "{{ $fleet->id }}",
            brandModel: "{{ $fleet->car_brand_model }}",
            regNumber: "{{ $fleet->car_registration_number }}"
          };

          const vehicleId = vehicleData.id;
          const vehicleName = `${vehicleData.brandModel} (${vehicleData.regNumber})`;

          const totalDistanceKm = ref(0);
          const liveSpeedKmh = ref(0);

          const haversine = (lat1, lon1, lat2, lon2) => {
            const toRad = x => (x * Math.PI) / 180;
            const R = 6371;
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
            return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
          };

          const initMap = () => {
            map = L.map('vehicleRouteMap').setView([-13.9626, 33.7741], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
          };

          const updateRoute = () => {
            axios.get(`/api/show-vehicle-geo-data/${vehicleId}`)
              .then(res => {
                const points = res.data.map(p => ({
                  lat: p.latitude,
                  lng: p.longitude,
                  time: new Date(p.created_at)
                }));
                if (points.length < 1) return;

                path = points.map(p => [p.lat, p.lng]);

                let dist = 0;
                for (let i = 1; i < points.length; i++) {
                  dist += haversine(points[i - 1].lat, points[i - 1].lng, points[i].lat, points[i].lng);
                }
                totalDistanceKm.value = dist.toFixed(2);

                if (points.length >= 2) {
                  const last = points.at(-1);
                  const prev = points.at(-2);
                  const d = haversine(prev.lat, prev.lng, last.lat, last.lng);
                  const dt = (last.time - prev.time) / 1000;
                  liveSpeedKmh.value = dt > 0 ? (d / dt * 3600).toFixed(2) : 0;
                } else {
                  liveSpeedKmh.value = 0;
                }

                if (polyline) polyline.remove();
                polyline = L.polyline(path, { color: 'blue', weight: 6 }).addTo(map);
                map.fitBounds(polyline.getBounds(), { padding: [50, 50] });

                const lastLatLng = path.at(-1);
                const popupHtml = `
                  <strong>${vehicleName}</strong><br>
                  Speed: ${liveSpeedKmh.value} km/h<br>
                  Distance: ${totalDistanceKm.value} km
                `;

                if (marker) {
                  marker.setLatLng(lastLatLng)
                    .bindPopup(popupHtml)
                    .openPopup();
                } else {
                  marker = L.marker(lastLatLng).addTo(map)
                    .bindPopup(popupHtml)
                    .openPopup();
                }
              })
              .catch(err => console.error(err));
          };

          onMounted(() => {
            initMap();
            updateRoute();
            setInterval(updateRoute, 5000);
          });

          return {
            vehicleName,
            totalDistanceKm,
            liveSpeedKmh
          };
        }
      }); vehicleRouteApp.mount('#vehicleRouteApp');
</script>
@endsection
