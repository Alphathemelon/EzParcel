window.onload = function() {
    const ctx = document.getElementById('amountChart');

    fetch("script4.php")
      .then(res => res.json())
      .then(data => {
        const labels = data.map(row => row.fld_parcel_date);
        const amounts = data.map(row => Number(row.total_amount));

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Parcel Amount',
                    data: amounts,
                    backgroundColor: [
                        '#4169E1',
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#8A2BE2',
                        '#00CED1',
                        '#FF7F50',
                        '#3CB371'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': RM ' + context.raw;
                            }
                        }
                    }
                }
            }
        });
      });
};
