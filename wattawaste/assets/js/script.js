document.addEventListener("DOMContentLoaded", function () {
  const toggle = document.getElementById("toggle");

  // 🔄 Fetch latest data every 3 seconds
  async function fetchLatest() {
    try {
      const res = await fetch("api/get_latest.php");
      const data = await res.json();

      if (data.latest) {
        document.getElementById("temp").innerText = data.latest.temperature + " °C";
        document.getElementById("hum").innerText = data.latest.humidity + " %";
        document.getElementById("gas").innerText = data.latest.gas_ppm + " ppm";
        document.getElementById("ph").innerText = data.latest.ph;
        // update toggle visual from database
        toggle.classList.toggle("on", data.mixer == 1);
      }
    } catch (err) {
      console.log("Error fetching data:", err);
    }
  }

  // 🧠 Toggle mixer manually
  async function toggleMixer() {
    const newState = toggle.classList.contains("on") ? 0 : 1;

    // update visually
    toggle.classList.toggle("on", newState === 1);

    try {
      await fetch("api/control_mixer.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ state: newState }),
      });
    } catch (err) {
      console.log("Error sending mixer state:", err);
    }
  }

  // 🎛 Add event listener to toggle
  toggle.addEventListener("click", toggleMixer);

  // 📊 Create chart
  const ctx = document.getElementById("progressChart").getContext("2d");
  new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: ["Ready", "Remaining"],
      datasets: [
        {
          data: [80, 20],
          backgroundColor: ["#5b9bd5", "#f4b183"], // same as legend
          borderWidth: 0,
        },
      ],
    },
    options: {
      cutout: "70%",
      plugins: {
        legend: { display: false },
      },
    },
  });

  // 🕒 Auto refresh
  fetchLatest();
  setInterval(fetchLatest, 3000);
});
