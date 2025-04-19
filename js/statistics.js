document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById("taskGraph");
    if (!canvas || typeof Chart === "undefined") return;
  
    const ctx = canvas.getContext("2d");
    const stats = window.taskStats || { todo: 0, in_progress: 0, completed: 0, expired: 0 };
  
    // build the doughnut
    const chart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["To-Do", "In Progress", "Completed", "Missed"],
        datasets: [{
          data: [
            stats.todo,
            stats.in_progress,
            stats.completed,
            stats.expired
          ],
          backgroundColor: ["#EA2E2E", "#5BA4E5", "#54D376", "#999999"]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }  // we’ll render our own
        }
      }
    });
  
    // now render our custom legend
        // after you create `chart`
        const legendEl = document.querySelector(".chart-legend");
        legendEl.innerHTML = "";            // clear any old HTML

        chart.data.labels.forEach((label, i) => {
        const value = chart.data.datasets[0].data[i];
        const color = chart.data.datasets[0].backgroundColor[i];

        // create the outer container
        const item = document.createElement("div");
        item.className = "legend-item";

        // inject the LEFT side (color + label) and RIGHT side (value)
        item.innerHTML = `
            <span class="legend-left">
            <span class="legend-color" style="background:${color}"></span>
            <span class="legend-label">${label}</span>
            </span>
            <span class="legend-value">${value}</span>
        `;

        legendEl.appendChild(item);
        });

  
    // …and your code to update those little cards above the chart:
    document.getElementById("todoCount").textContent = stats.todo;
    document.getElementById("inProgressCount").textContent = stats.in_progress;
    document.getElementById("completedCount").textContent = stats.completed;
    document.getElementById("expiredCount").textContent = stats.expired;
  });
  