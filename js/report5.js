document.addEventListener('DOMContentLoaded', function() {
    function updateCollectedDashboard() {
        fetch('script5.php')
            .then(response => response.json())
            .then(data => {
                const displayElement = document.getElementById('displayCollected');
                const lastUpdated = document.getElementById('lastUpdatedCollected');

                if (displayElement && lastUpdated) {
                    let total = parseInt(data.total_collected_today);
                    displayElement.innerText = total;

                    // Set masa update
                    const now = new Date();
                    lastUpdated.innerText = now.toLocaleTimeString();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('displayCollected').innerText = 'Error';
            });
    }

    updateCollectedDashboard(); // panggil sekali masa load
    setInterval(updateCollectedDashboard, 60000); // auto refresh setiap 60 saat
});
