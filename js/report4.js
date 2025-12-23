// Guna fungsi ini untuk pastikan DOM benar-benar sedia
function loadDonutChart() {
    // 1. Double check ID (mesti 'amountChart' ikut HTML anda)
    const canvas = document.getElementById('amountChart');
    
    if (!canvas) {
        console.error("DEBUG: Element 'amountChart' tidak dijumpai dalam DOM!");
        return;
    }

    const ctx = canvas.getContext('2d');
    console.log("DEBUG: Canvas dijumpai, memulakan fetch...");

    fetch('script4.php')
        .then(res => {
            if (!res.ok) {
                console.error('DEBUG: Network response not ok', res.status, res.statusText);
                return res.text().then(t => { throw new Error('Network response not ok: ' + res.status + ' - ' + t); });
            }
            return res.text();
        })
        .then(text => {
            let data;
            try {
                data = JSON.parse(text || '[]');
            } catch (e) {
                console.error('DEBUG: Failed to parse JSON from script4.php:', e, 'responseText:', text);
                throw e;
            }
            console.log("DEBUG: Data diterima:", data);

            // Jika ada chart lama, kita musnahkan dulu untuk elak error bertindih
            let chartStatus = Chart.getChart("amountChart"); 
            if (chartStatus != undefined) {
                chartStatus.destroy();
            }

            try {
                new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.fld_parcel_date),
                    datasets: [{
                        label: 'Total Amount',
                        data: data.map(d => `RM${parseFloat(d.total_amount).toFixed(2)}`),
                        backgroundColor: [
                            '#4169E1', '#FF6384', '#36A2EB',
                            '#FFCE56', '#8A2BE2', '#00CED1',
                            '#32CD32', '#FF8C00', '#40E0D0'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
                });
                console.log("DEBUG: Chart berjaya dibina!");
            } catch (chartErr) {
                console.error('DEBUG: Chart creation error:', chartErr);
                throw chartErr;
            }
        })
        .catch(err => console.error('DEBUG: Fetch/error:', err));
}

// Jalankan fungsi apabila page siap load
if (document.readyState === 'complete') {
    loadDonutChart();
} else {
    window.addEventListener('load', loadDonutChart);
}