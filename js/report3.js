// File: js/report3.js
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan nama file PHP ini betul (report3.php atau script3.php)
    fetch('script3.php')
        .then(response => response.json())
        .then(data => {
            // Cari H1 dengan id 'displayAmount'
            const displayElement = document.getElementById('displayAmount');
            if (displayElement) {
                // Format duit dengan 2 titik perpuluhan
                let amount = parseFloat(data.total_today).toFixed(2);
                // Masukkan ke dalam H1
                displayElement.innerText = 'RM ' + amount;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const displayElement = document.getElementById('displayAmount');
            if (displayElement) {
                displayElement.innerText = 'RM Error';
            }
        });
});
