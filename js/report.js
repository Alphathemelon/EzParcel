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
            backgroundColor: '#003f5c'
          },
          {
            label: 'Size M',
            data: sizeM,
            backgroundColor: '#bc5090'
          },
          {
            label: 'Size L',
            data: sizeL,
            backgroundColor: '#ffa600'
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
