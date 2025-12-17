document.addEventListener('DOMContentLoaded', function() {
    function updateDashboard() {
        fetch('script3.php')
            .then(response => response.json())
            .then(data => {
                const displayElement = document.getElementById('displayAmount');
               // const lastUpdated = document.getElementById('lastUpdated');

                if (displayElement && lastUpdated) {
                    let amount = parseFloat(data.total_today).toFixed(2);
                    displayElement.innerText = 'RM ' + amount;

                    // Set masa update
                    const now = new Date();
                    lastUpdated.innerText = now.toLocaleTimeString();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('displayAmount').innerText = 'RM Error';
            });
    }

    //updateDashboard(); // panggil sekali masa load
    //setInterval(updateDashboard, 60000); // auto refresh setiap 60 saat
});