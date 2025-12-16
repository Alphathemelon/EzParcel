// File: js/report3.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Pastikan nama file PHP ini BETUL (report3.php atau dailyearning.php?)
    fetch('script3.php') 
        .then(response => response.json())
        .then(data => {
            // Cari H1 tadi
            const displayElement = document.getElementById('displayAmount');
            
            if (displayElement) {
                // Format duit cantik (2 titik perpuluhan)
                let amount = parseFloat(data.total_today).toFixed(2);
                
                // Masukkan ke dalam H1
                displayElement.innerText = 'RM ' + amount;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('displayAmount').innerText = 'RM Error';
        });
});