@extends('layouts.backend')

@section('css_after')
<style>
    #playback-position {
        background: var(--speed-gradient, #d8dce3) center / 100% .5rem no-repeat;
    }
    #playback-position::-webkit-slider-runnable-track {
        background: transparent;
    }
    #playback-position::-moz-range-track {
        background: transparent;
    }
    #playback-position:disabled {
        opacity: .5;
    }
</style>
@endsection

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex justify-content-between align-items-center gap-3">
      <div>
        <a class="fs-sm text-muted" href="{{ route('fleet.index') }}"><i class="fa fa-arrow-left me-1" aria-hidden="true"></i> Back to fleet</a>
        <h1 class="fs-3 fw-semibold mt-2 mb-1">{{ $fleet->car_brand_model }}</h1>
        <p class="text-muted mb-0">{{ $fleet->car_registration_number }}</p>
      </div>
      <div class="flex-shrink-0">
            <div class="dropdown d-inline-block">
                <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-angle-down me-1" aria-hidden="true"></i>
                  <span>Actions</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                  <!-- Edit Fleet -->
                  <form method="GET" action="{{ route('fleet.edit', $fleet->id) }}">
                    @csrf
                    <button class="dropdown-item d-flex align-items-center py-2" type="submit">
                      <i class="fa fa-edit me-2 text-primary"></i>
                      <span>Edit vehicle</span>
                    </button>
                  </form>
                  <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('fleet.edit', $fleet->id) }}#instructor">
                    <i class="fa fa-user-plus me-2 text-success"></i>
                    <span>Assign Instructor</span>
                  </a>
                </div>
            </div>
      </div>
    </div>
  </div>
</div>

<!-- Content -->
<div class="content content-full" id="vehicleRouteApp">
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-lg-4">
                  <div class="block block-rounded h-100 mb-0">
                    <div class="block-header block-header-default">
                        <h2 class="block-title">Vehicle details</h2>
                        <span class="badge bg-primary-light text-primary">{{ $fleet->car_registration_number }}</span>
                    </div>
                    <div class="block-content block-content-full">
                        <div class="mb-4">
                            <img src="{{ asset('media/fleet/'.($fleet->fleet_image ?: 'driving-school-car-default.png')) }}"
                                 alt="{{ $fleet->car_brand_model }}"
                                 class="img-fluid rounded-3 bg-body-light w-100 p-3"
                                 style="height: 220px; object-fit: contain;"
                                 onerror="this.onerror=null; this.src='{{ asset('media/fleet/driving-school-car-default.png') }}'">
                        </div>
                        @if ($fleet->car_description)
                            <p class="text-muted fs-sm">{{ $fleet->car_description }}</p>
                        @endif

                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between gap-3 px-0">
                                <span class="text-muted">Licence class</span>
                                <span class="fw-semibold">{{ $fleet->licenceClass->class ?? '-' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Instructor</span>
                                <span class="text-end">
                                    @if ($fleet->instructor)
                                        {{ $fleet->instructor->fname }} {{ $fleet->instructor->sname }}
                                    @else
                                        <span class="text-warning">Not assigned</span>
                                    @endif
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Active students</span>
                                <span class="badge bg-primary-light text-primary">
                                    {{ $fleet->student()->where('status', '!=', 'Finished')->count() }}
                                </span>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-8">
                  <div class="block block-rounded h-100 mb-0">
                    <div class="block-header block-header-default">
                        <h2 class="block-title">Location &amp; distance</h2>
                        <span class="badge bg-info-light text-info">Tracking</span>
                    </div>
                    <div class="block-content block-content-full">
                    <form class="row g-3 align-items-end mb-4" @submit.prevent="applyTimeFilter">
                        <div class="col-12 col-sm-4">
                            <select id="tracking-period" class="form-select" v-model="selectedPeriod" @change="changeTimeFilter">
                                <option value="hour">Last hour</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <template v-if="selectedPeriod === 'custom'">
                            <div class="col-12 col-sm-4">
                                <label class="form-label" for="tracking-from">From</label>
                                <input id="tracking-from" type="datetime-local" class="form-control" v-model="customFrom" required>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="form-label" for="tracking-to">To</label>
                                <input id="tracking-to" type="datetime-local" class="form-control" v-model="customTo" required>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary px-4">Apply</button>
                            </div>
                        </template>
                    </form>
                    <div class="alert alert-danger" role="alert" v-if="filterError">@{{ filterError }}</div>
                    <p class="fs-sm text-muted" role="status" v-if="trackingMessage">@{{ trackingMessage }}</p>
                    <div class="mb-3">
                        <div id="vehicleRouteMap" style="height: 340px;" class="rounded-3 bg-body-light"></div>
                    </div>
                    <div id="route-playback" class="bg-body-light rounded-3 p-3 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-primary px-3" @click="togglePlayback" :disabled="!canPlayback">@{{ isPlaying ? 'Pause' : 'Play' }}</button>
                            <button type="button" class="btn btn-sm btn-alt-secondary" @click="restartPlayback" :disabled="!canPlayback">Restart</button>
                            <button type="button" class="btn btn-sm btn-alt-secondary" @click="showFullRoute" :disabled="!playbackMode">Full route</button>
                            <label class="fs-sm ms-sm-auto mb-0" for="playback-speed">Playback speed</label>
                            <select id="playback-speed" class="form-select form-select-sm w-auto" v-model.number="playbackSpeed">
                                <option :value="1">1x</option>
                                <option :value="10">10x</option>
                                <option :value="60">60x</option>
                                <option :value="200">200x</option>
                                <option :value="300">300x</option>
                            </select>
                        </div>
                        <label class="visually-hidden" for="playback-position">Route timeline</label>
                        <input id="playback-position" type="range" class="form-range" min="0" :max="playbackDuration" step="1" :value="playbackPosition" :style="{ '--speed-gradient': playbackGradient }" @input="seekPlayback" :disabled="!canPlayback">
                        <div class="fs-sm text-muted" role="status" v-if="playbackTime">@{{ playbackTime }}</div>
                    </div>
                    <div class="row g-3" id="vehicle-metrics">
                        <div class="col-md-4">
                            <div class="bg-body-light rounded-3 p-3 h-100">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-tachometer-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <div class="text-muted fs-sm mb-1">Speed</div>
                                        <p class="h4 mb-0 fw-bold">@{{ liveSpeedKmh }} <small class="fs-sm">km/h</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-body-light rounded-3 p-3 h-100">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-road fa-2x text-success me-3"></i>
                                    <div>
                                        <div class="text-muted fs-sm mb-1">Distance</div>
                                        <p class="h4 mb-0 fw-bold">@{{ totalDistanceKm }} <small class="fs-sm">km</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
    <div class="bg-body-light rounded-3 p-3 h-100" id="fuel-estimate">
        <div class="d-flex align-items-center">
            <i class="fa fa-gas-pump fa-2x text-warning me-3" aria-hidden="true"></i>
            <div>
                <div class="text-muted fs-sm mb-1">Fuel usage</div>
                @php
                    $estimatedRate = $fleet->estimatedFuelLitresPer100Km();
                @endphp
                @if ($estimatedRate !== null)
                    <p class="h4 mb-0 fw-bold" v-if="estimatedFuelUsage !== null">@{{ estimatedFuelUsage.toFixed(2) }} <small class="fs-sm">L</small></p>
                    <p class="text-muted mb-0" role="status" v-else>@{{ fuelUsageStatus }}</p>
                @else
                    <p class="text-muted mb-0">Unavailable</p>
                @endif
            </div>
        </div>
    </div>
                        </div>
                    </div>
                    </div>
                  </div>
                </div>
            </div>
</div>

<div class="content content-full pt-0">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h2 class="block-title">Specifications &amp; maintenance</h2>
        </div>
        <div class="block-content block-content-full" id="vehicle-specifications">
            <div class="row g-3">
                @foreach ([
                    [
                        'title' => 'Vehicle', 'icon' => 'fa-car',
                        'fields' => [
                            'make' => 'Make', 'model' => 'Model', 'manufacture_year' => 'Year',
                            'vin' => 'VIN / chassis', 'colour' => 'Colour', 'seating_capacity' => 'Seats',
                        ],
                    ],
                    [
                        'title' => 'Engine & drive', 'icon' => 'fa-cogs',
                        'fields' => [
                            'engine_capacity_cc' => 'Engine (cc)', 'engine_number' => 'Engine number',
                            'fuel_type' => 'Fuel type', 'transmission' => 'Transmission',
                            'odometer_km' => 'Odometer (km)',
                        ],
                    ],
                    [
                        'title' => 'Insurance & service', 'icon' => 'fa-shield-alt',
                        'fields' => [
                            'insurance_provider' => 'Insurer', 'insurance_expiry' => 'Insurance expiry',
                            'roadworthiness_expiry' => 'Roadworthiness expiry', 'service_date' => 'Last service',
                            'next_service_km' => 'Next service (km)',
                        ],
                    ],
                ] as $group)
                    <div class="col-12 col-lg-4">
                        <section class="bg-body-light rounded-3 p-3 h-100" aria-labelledby="specification-group-{{ $loop->index }}">
                            <h3 class="fs-base fw-semibold mb-3" id="specification-group-{{ $loop->index }}">
                                <i class="fa {{ $group['icon'] }} text-primary me-2" aria-hidden="true"></i>{{ $group['title'] }}
                            </h3>
                            <dl class="mb-0">
                                @foreach ($group['fields'] as $field => $label)
                                    @php
                                        $value = $fleet->{$field};
                                        if ($value instanceof \DateTimeInterface) {
                                            $value = $value->format('d M Y');
                                        } elseif (in_array($field, ['engine_capacity_cc', 'odometer_km', 'next_service_km'], true) && $value !== null) {
                                            $value = number_format($value);
                                        }
                                    @endphp
                                    <div class="row g-2 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                        <dt class="col-6 text-muted fs-sm fw-normal">{{ $label }}</dt>
                                        <dd class="col-6 text-end text-break fs-sm fw-semibold mb-0 {{ $value === null || $value === '' ? 'text-muted' : '' }}">{{ $value === null || $value === '' ? '-' : $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    </div>
                @endforeach
            </div>
        </div>
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
          const trackedDistanceKm = ref(null);
          const fuelUsageStatus = ref('Loading...');
          const estimatedRate = @json($estimatedRate);
          const estimatedFuelUsage = Vue.computed(() =>
            estimatedRate !== null && trackedDistanceKm.value !== null
              ? trackedDistanceKm.value * estimatedRate / 100
              : null
          );
          const selectedPeriod = ref('today');
          const customFrom = ref('');
          const customTo = ref('');
          const filterError = ref('');
          const trackingMessage = ref('');
          let activeFilter = { period: 'today' };
          let requestVersion = 0;
          let routePoints = [];
          let cumulativeDistances = [];
          let playbackFrame = null;
          let lastFrameTime = null;
          let refreshTimer = null;
          const playbackMode = ref(false);
          const isPlaying = ref(false);
          const playbackSpeed = ref(200);
          const playbackPosition = ref(0);
          const playbackDuration = ref(0);
          const playbackTime = ref('');
          const playbackGradient = ref('#d8dce3');
          const canPlayback = Vue.computed(() => playbackDuration.value > 0 && routePoints.length >= 2);

          const buildSpeedGradient = (points, distances) => {
            const duration = points.at(-1).time - points[0].time;
            if (duration <= 0) return { background: '#d8dce3', peak: 0 };
            const segments = [];
            for (let i = 1; i < points.length; i++) {
              const elapsed = points[i].time - points[i - 1].time;
              if (elapsed <= 0) continue;
              segments.push({
                speed: (distances[i] - distances[i - 1]) / elapsed * 3600000,
                start: (points[i - 1].time - points[0].time) / duration * 100,
                end: (points[i].time - points[0].time) / duration * 100
              });
            }
            const peak = segments.reduce((max, segment) => Math.max(max, segment.speed), 0);
            const color = speed => `hsl(${speed > 40 ? 0 : 120 - Math.max(0, speed) / 40 * 60}, 75%, 42%)`;
            const stops = segments.flatMap(segment => [
              `${color(segment.speed)} ${segment.start}%`,
              `${color(segment.speed)} ${segment.end}%`
            ]);
            return { background: `linear-gradient(to right, ${stops.join(', ')})`, peak };
          };

          const pausePlayback = () => {
            isPlaying.value = false;
            if (playbackFrame !== null) cancelAnimationFrame(playbackFrame);
            playbackFrame = null;
            lastFrameTime = null;
          };
          const renderPlayback = () => {
            if (!canPlayback.value) return;
            const target = routePoints[0].time.getTime() + playbackPosition.value;
            let index = 0;
            while (index < routePoints.length - 1 && routePoints[index + 1].time.getTime() <= target) index++;
            const start = routePoints[index];
            const end = routePoints[index + 1];
            const duration = end ? end.time - start.time : 0;
            const fraction = duration > 0 ? (target - start.time.getTime()) / duration : 0;
            const location = end
              ? [start.lat + (end.lat - start.lat) * fraction, start.lng + (end.lng - start.lng) * fraction]
              : [start.lat, start.lng];
            const segmentDistance = end ? cumulativeDistances[index + 1] - cumulativeDistances[index] : 0;
            trackedDistanceKm.value = cumulativeDistances[index] + segmentDistance * fraction;
            totalDistanceKm.value = trackedDistanceKm.value.toFixed(2);
            liveSpeedKmh.value = duration > 0 ? (segmentDistance / duration * 3600000).toFixed(2) : 0;
            polyline.setLatLngs([...path.slice(0, index + 1), location]);
            marker.closePopup().setLatLng(location);
            playbackTime.value = new Date(target).toLocaleString('en-GB', { timeZone: 'Africa/Blantyre' });
          };
          const animatePlayback = timestamp => {
            if (!isPlaying.value) return;
            if (lastFrameTime !== null) {
              playbackPosition.value = Math.min(playbackDuration.value, playbackPosition.value + (timestamp - lastFrameTime) * playbackSpeed.value);
            }
            lastFrameTime = timestamp;
            renderPlayback();
            if (playbackPosition.value >= playbackDuration.value) {
              pausePlayback();
              return;
            }
            playbackFrame = requestAnimationFrame(animatePlayback);
          };
          const togglePlayback = () => {
            if (isPlaying.value) { pausePlayback(); return; }
            if (!canPlayback.value) return;
            if (!playbackMode.value || playbackPosition.value >= playbackDuration.value) playbackPosition.value = 0;
            playbackMode.value = true;
            ++requestVersion;
            isPlaying.value = true;
            renderPlayback();
            playbackFrame = requestAnimationFrame(animatePlayback);
          };
          const restartPlayback = () => {
            pausePlayback();
            if (!canPlayback.value) return;
            playbackMode.value = true;
            ++requestVersion;
            playbackPosition.value = 0;
            renderPlayback();
          };
          const seekPlayback = event => {
            pausePlayback();
            if (!canPlayback.value) return;
            playbackMode.value = true;
            ++requestVersion;
            playbackPosition.value = Math.max(0, Math.min(playbackDuration.value, Number(event.target.value)));
            renderPlayback();
          };
          const showFullRoute = () => {
            pausePlayback();
            playbackMode.value = false;
            updateRoute();
          };

          const clearRoute = () => {
            pausePlayback();
            playbackMode.value = false;
            routePoints = [];
            cumulativeDistances = [];
            playbackDuration.value = 0;
            playbackPosition.value = 0;
            playbackTime.value = '';
            playbackGradient.value = '#d8dce3';
            trackedDistanceKm.value = null;
            totalDistanceKm.value = 0;
            liveSpeedKmh.value = 0;
            if (polyline) { polyline.remove(); polyline = null; }
            if (marker) { marker.remove(); marker = null; }
            path = [];
          };
          const applyTimeFilter = () => {
            filterError.value = '';
            if (selectedPeriod.value === 'custom' && (!customFrom.value || !customTo.value || customTo.value <= customFrom.value)) {
              filterError.value = 'Choose an end time after the start.';
              return;
            }
            activeFilter = selectedPeriod.value === 'custom'
              ? { period: 'custom', from: customFrom.value, to: customTo.value }
              : { period: selectedPeriod.value };
            clearRoute();
            fuelUsageStatus.value = 'Loading...';
            updateRoute();
          };
          const changeTimeFilter = () => {
            if (selectedPeriod.value !== 'custom') applyTimeFilter();
          };

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
            if (playbackMode.value) return;
            const version = ++requestVersion;
            if (!@json((bool) $fleet->tracker_device_id)) {
              trackedDistanceKm.value = null;
              fuelUsageStatus.value = 'No tracking';
              trackingMessage.value = 'No tracking device assigned.';
              return;
            }
            axios.get(`/show-vehicle-geo-data/${vehicleId}`, { params: activeFilter })
              .then(res => {
                if (version !== requestVersion) return;
                if (!Array.isArray(res.data)) throw new Error('Invalid tracking data.');
                if (res.data.some(p => !p || p.latitude === null || p.longitude === null || p.latitude === '' || p.longitude === '')) {
                  throw new Error('Missing tracking coordinates.');
                }
                const points = res.data.map(p => ({
                  lat: Number(p.latitude),
                  lng: Number(p.longitude),
                  time: new Date(p.created_at)
                }));
                if (points.some(p => !Number.isFinite(p.lat) || !Number.isFinite(p.lng) || Math.abs(p.lat) > 90 || Math.abs(p.lng) > 180 || !Number.isFinite(p.time.getTime()))) {
                  throw new Error('Invalid tracking coordinates or timestamp.');
                }
                if (points.some((point, index) => index > 0 && point.time < points[index - 1].time)) {
                  throw new Error('Tracking timestamps are out of order.');
                }
                if (points.length < 2) {
                  clearRoute();
                  trackedDistanceKm.value = null;
                  fuelUsageStatus.value = 'No distance';
                  trackingMessage.value = 'Not enough tracking points for this period.';
                  totalDistanceKm.value = 0;
                  liveSpeedKmh.value = 0;
                  if (points.length === 0) return;
                }

                path = points.map(p => [p.lat, p.lng]);

                let dist = 0;
                cumulativeDistances = [0];
                for (let i = 1; i < points.length; i++) {
                  dist += haversine(points[i - 1].lat, points[i - 1].lng, points[i].lat, points[i].lng);
                  cumulativeDistances.push(dist);
                }
                routePoints = points;
                playbackDuration.value = points.at(-1).time - points[0].time;
                const gradient = buildSpeedGradient(points, cumulativeDistances);
                playbackGradient.value = gradient.background;
                playbackPosition.value = playbackDuration.value;
                playbackTime.value = '';
                totalDistanceKm.value = dist.toFixed(2);
                if (points.length >= 2) {
                  trackedDistanceKm.value = dist;
                  trackingMessage.value = '';
                }

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
              .catch(err => {
                if (version !== requestVersion) return;
                clearRoute();
                trackedDistanceKm.value = null;
                fuelUsageStatus.value = 'Tracking unavailable';
                trackingMessage.value = 'Tracking could not be loaded.';
                filterError.value = err.response?.status === 422
                  ? Object.values(err.response.data.errors || {}).flat().join(' ')
                  : '';
                console.error('Vehicle tracking failed:', err);
              });
          };

          onMounted(() => {
            initMap();
            updateRoute();
            refreshTimer = setInterval(updateRoute, 5000);
          });
          Vue.onUnmounted(() => {
            ++requestVersion;
            pausePlayback();
            clearInterval(refreshTimer);
            if (map) map.remove();
          });

          return {
            vehicleName,
            totalDistanceKm,
            liveSpeedKmh,
            estimatedFuelUsage,
            fuelUsageStatus,
            selectedPeriod,
            customFrom,
            customTo,
            filterError,
            trackingMessage,
            applyTimeFilter,
            changeTimeFilter,
            playbackMode, isPlaying, playbackSpeed, playbackPosition, playbackDuration,
            playbackTime, playbackGradient, canPlayback,
            togglePlayback, restartPlayback, seekPlayback, showFullRoute
          };
        }
      }); vehicleRouteApp.mount('#vehicleRouteApp');
</script>
@endsection
