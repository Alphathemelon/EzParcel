window.onload = function() {
    const ctx = document.getElementById('statusChart');

    fetch("script2.php")
      .then(res => res.json())
      .then(data => {

        const labels = data.map(row => row.fld_parcel_date);
        const collected = data.map(row => Number(row.collected));
        const uncollected = data.map(row => Number(row.uncollected));

        new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Collected',
                data: collected,
                borderColor: 'rgba(65,105,225,0.8)',
                borderWidth: 2,
                tension: 0.4,
                fill: false
              },
              {
                label: 'Uncollected',
                data: uncollected,
                borderColor: 'rgba(255,99,132,0.8)',
                borderWidth: 2,
                tension: 0.4,
                fill: false
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Bilangan Parcel'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Tarikh'
                }
              }
            }
          }
        });

      });
};