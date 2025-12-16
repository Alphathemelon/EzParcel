const ctx = document.getElementById('myChart');

fetch("script.php")
  .then(res => res.json())
  .then(data => {

    const labels = data.map(row => row.fld_parcel_date);

    const sizeS = data.map(row => Number(row.size_s));
    const sizeM = data.map(row => Number(row.size_m));
    const sizeL = data.map(row => Number(row.size_l));

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Size S',
            data: sizeS,
            backgroundColor: 'rgba(65,105,225,0.7)'
          },
          {
            label: 'Size M',
            data: sizeM,
            backgroundColor: 'rgba(255,165,0,0.7)'
          },
          {
            label: 'Size L',
            data: sizeL,
            backgroundColor: 'rgba(34,139,34,0.7)'
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
