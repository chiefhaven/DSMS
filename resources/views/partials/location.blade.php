<div id="vehicleLocation">
</div>

  <script>
  const { createApp, ref, onMounted } = Vue;

  const app = createApp({
    setup() {
      const latitude = ref(null);
      const longitude = ref(null);
      const error = ref(null);

      onMounted(() => {
        if ("geolocation" in navigator) {
          navigator.geolocation.watchPosition(
            position => {
              latitude.value = position.coords.latitude;
              longitude.value = position.coords.longitude;

              axios.post('/api/save-vehicle-location', {
                latitude: latitude.value,
                longitude: longitude.value
              }).then(res => {

              }).catch(err => {

            });
            },
            err => {
              error.value = err.message;
            },
            {
              enableHighAccuracy: true,
              timeout: 5000
            }
          );
        } else {
          error.value = "Geolocation is not supported by this browser.";
        }
      });

      return {
        latitude,
        longitude,
        error
      };
    }
  }).mount('#vehicleLocation');
  </script>
