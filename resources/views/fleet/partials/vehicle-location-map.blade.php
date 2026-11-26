@php
    $mapId = 'vehicle-location-map-'.$fleet->getKey();
    $periodId = 'vehicle-location-period-'.$fleet->getKey();
    $fromId = 'vehicle-location-from-'.$fleet->getKey();
    $toId = 'vehicle-location-to-'.$fleet->getKey();
    $locationMapConfig = [
        'mapId' => $mapId,
        'locationsUrl' => $locationsUrl,
        'vehicleName' => $fleet->car_brand_model.' ('.$fleet->car_registration_number.')',
        'hasTracker' => (bool) $fleet->tracker_device_id,
        'fuelRate' => $fleet->estimatedFuelLitresPer100Km(),
    ];
@endphp

<div class="block block-rounded block-bordered h-100 mb-0">
    <div class="block-header block-header-default">
        <h2 class="block-title">Location &amp; distance</h2>
        <span class="badge bg-info-light text-info">Tracking</span>
    </div>
    <div class="block-content block-content-full">
        <form class="row g-3 align-items-end mb-4" data-location-filter>
            <div class="col-12 col-sm-4">
                <label class="form-label" for="{{ $periodId }}">Period</label>
                <select id="{{ $periodId }}" class="form-select" data-location-period>
                    <option value="hour">Last hour</option>
                    <option value="today" selected>Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div class="col-12 col-sm-4 d-none" data-location-custom>
                <label class="form-label" for="{{ $fromId }}">From</label>
                <input id="{{ $fromId }}" type="datetime-local" class="form-control" data-location-from>
            </div>
            <div class="col-12 col-sm-4 d-none" data-location-custom>
                <label class="form-label" for="{{ $toId }}">To</label>
                <input id="{{ $toId }}" type="datetime-local" class="form-control" data-location-to>
            </div>
            <div class="col-12 text-end d-none" data-location-custom>
                <button type="submit" class="btn btn-primary px-4">Apply</button>
            </div>
        </form>
        <div class="alert alert-danger d-none" role="alert" data-location-error></div>
        <p class="fs-sm text-muted" role="status" data-location-status>Loading tracking data...</p>
        <div class="mb-3">
            <div id="{{ $mapId }}" style="height: 340px;" class="rounded-3 bg-body-light"></div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="bg-body-light rounded-3 p-3 h-100">
                    <div class="text-muted fs-sm mb-1">Speed</div>
                    <p class="h4 mb-0 fw-bold"><span data-location-speed>0</span> <small class="fs-sm">km/h</small></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-body-light rounded-3 p-3 h-100">
                    <div class="text-muted fs-sm mb-1">Distance</div>
                    <p class="h4 mb-0 fw-bold"><span data-location-distance>0</span> <small class="fs-sm">km</small></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-body-light rounded-3 p-3 h-100">
                    <div class="text-muted fs-sm mb-1">Fuel usage</div>
                    <p class="h4 mb-0 fw-bold" data-location-fuel>Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const config = @json($locationMapConfig);
        const mapElement = document.getElementById(config.mapId);
        const root = mapElement.closest('.block');
        const periodSelect = root.querySelector('[data-location-period]');
        const customFields = root.querySelectorAll('[data-location-custom]');
        const fromInput = root.querySelector('[data-location-from]');
        const toInput = root.querySelector('[data-location-to]');
        const filterForm = root.querySelector('[data-location-filter]');
        const errorElement = root.querySelector('[data-location-error]');
        const statusElement = root.querySelector('[data-location-status]');
        const speedElement = root.querySelector('[data-location-speed]');
        const distanceElement = root.querySelector('[data-location-distance]');
        const fuelElement = root.querySelector('[data-location-fuel]');
        const map = L.map(mapElement).setView([-13.9626, 33.7741], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let marker = null;
        let polyline = null;
        let requestVersion = 0;
        let activeFilter = { period: 'today' };
        let refreshTimer = null;

        const setError = (message) => {
            errorElement.textContent = message;
            errorElement.classList.toggle('d-none', !message);
        };
        const distanceBetween = (first, second) => {
            const radians = (value) => value * Math.PI / 180;
            const latitudeDelta = radians(second.lat - first.lat);
            const longitudeDelta = radians(second.lng - first.lng);
            const value = Math.sin(latitudeDelta / 2) ** 2
                + Math.cos(radians(first.lat)) * Math.cos(radians(second.lat))
                * Math.sin(longitudeDelta / 2) ** 2;

            return 6371 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
        };
        const clearRoute = () => {
            if (polyline) {
                polyline.remove();
                polyline = null;
            }
            if (marker) {
                marker.remove();
                marker = null;
            }
        };
        const updateRoute = async () => {
            const version = ++requestVersion;
            setError('');

            if (!config.hasTracker) {
                statusElement.textContent = 'No tracking device assigned.';
                fuelElement.textContent = 'No tracking';
                return;
            }

            statusElement.textContent = 'Loading tracking data...';

            try {
                const response = await axios.get(config.locationsUrl, { params: activeFilter });
                if (version !== requestVersion) return;
                if (!Array.isArray(response.data)) {
                    throw new Error('Invalid tracking data.');
                }
                if (response.data.some((point) => !point || point.latitude === null || point.longitude === null
                    || point.latitude === '' || point.longitude === '')) {
                    throw new Error('Missing tracking coordinates.');
                }

                const points = response.data.map((point) => ({
                    lat: Number(point.latitude),
                    lng: Number(point.longitude),
                    time: new Date(point.created_at),
                }));
                if (points.some((point) => !Number.isFinite(point.lat) || !Number.isFinite(point.lng)
                    || Math.abs(point.lat) > 90 || Math.abs(point.lng) > 180
                    || !Number.isFinite(point.time.getTime()))) {
                    throw new Error('Invalid tracking coordinates or timestamp.');
                }
                if (points.some((point, index) => index > 0 && point.time < points[index - 1].time)) {
                    throw new Error('Tracking timestamps are out of order.');
                }

                clearRoute();
                speedElement.textContent = '0';
                distanceElement.textContent = '0.00';
                fuelElement.textContent = config.fuelRate === null ? 'Unavailable' : '0.00 L';

                if (points.length === 0) {
                    statusElement.textContent = 'No tracking points for this period.';
                    return;
                }

                const coordinates = points.map((point) => [point.lat, point.lng]);
                let totalDistance = 0;
                for (let index = 1; index < points.length; index += 1) {
                    totalDistance += distanceBetween(points[index - 1], points[index]);
                }

                if (points.length > 1) {
                    polyline = L.polyline(coordinates, { color: 'blue', weight: 6 }).addTo(map);
                    map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
                    const previousPoint = points[points.length - 2];
                    const lastPoint = points[points.length - 1];
                    const elapsedSeconds = (lastPoint.time - previousPoint.time) / 1000;
                    const speed = elapsedSeconds > 0
                        ? distanceBetween(previousPoint, lastPoint) / elapsedSeconds * 3600
                        : 0;
                    speedElement.textContent = speed.toFixed(2);
                } else {
                    map.setView(coordinates[0], 15);
                }

                const lastPoint = points[points.length - 1];
                const popup = document.createElement('div');
                const name = document.createElement('strong');
                name.textContent = config.vehicleName;
                popup.appendChild(name);
                marker = L.marker(coordinates[coordinates.length - 1]).addTo(map).bindPopup(popup);
                distanceElement.textContent = totalDistance.toFixed(2);
                if (config.fuelRate !== null) {
                    fuelElement.textContent = `${(totalDistance * config.fuelRate / 100).toFixed(2)} L`;
                }
                statusElement.textContent = `Last location: ${lastPoint.time.toLocaleString('en-GB')}`;
            } catch (error) {
                if (version !== requestVersion) return;
                clearRoute();
                speedElement.textContent = '0';
                distanceElement.textContent = '0.00';
                statusElement.textContent = 'Tracking could not be loaded.';
                fuelElement.textContent = 'Tracking unavailable';
                setError(error.response?.status === 422
                    ? Object.values(error.response.data.errors || {}).flat().join(' ')
                    : '');
                console.error('Assigned vehicle tracking failed:', error);
            }
        };

        periodSelect.addEventListener('change', () => {
            const isCustom = periodSelect.value === 'custom';
            customFields.forEach((element) => element.classList.toggle('d-none', !isCustom));
            if (!isCustom) {
                activeFilter = { period: periodSelect.value };
                updateRoute();
            }
        });
        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!fromInput.value || !toInput.value || toInput.value <= fromInput.value) {
                setError('Choose an end time after the start.');
                return;
            }
            activeFilter = { period: 'custom', from: fromInput.value, to: toInput.value };
            updateRoute();
        });

        updateRoute();
        if (config.hasTracker) {
            refreshTimer = window.setInterval(updateRoute, 30000);
        }
        window.addEventListener('pagehide', () => {
            ++requestVersion;
            window.clearInterval(refreshTimer);
            map.remove();
        }, { once: true });
    })();
</script>
