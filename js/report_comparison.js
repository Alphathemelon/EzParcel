document.addEventListener('DOMContentLoaded', function () {
    function updateComparisonCards() {
        fetch('script_comparison.php')
            .then(response => response.json())
            .then(data => {
                // 1. Earning Comparison
                const earningEl = document.getElementById('compEarning');
                const earningDescEl = document.getElementById('compEarningDesc');

                if (earningEl && earningDescEl) {
                    const earningDiff = data.earning.diff;
                    const earningPercent = data.earning.percent;
                    const earningTrend = data.earning.trend;
                    const earningYesterday = data.earning.yesterday;

                    let trendSymbol = earningTrend === 'up' ? '+' : '';
                    let trendColor = earningTrend === 'up' ? 'green' : 'red';

                    // If no difference, use neutral color
                    if (earningDiff === 0) {
                        trendColor = 'gray';
                        trendSymbol = '';
                    }

                    earningEl.innerText = `RM ${data.earning.today.toFixed(2)}`;
                    earningEl.style.color = trendColor;

                    earningDescEl.innerHTML = `Yesterday: RM ${earningYesterday.toFixed(2)} <span style="color:${trendColor}; font-weight:bold;">(${trendSymbol}${earningPercent}%)</span>`;
                }

                // 2. Parcel Comparison
                const parcelEl = document.getElementById('compParcel');
                const parcelDescEl = document.getElementById('compParcelDesc');

                if (parcelEl && parcelDescEl) {
                    const parcelDiff = data.parcel.diff;
                    const parcelTrend = data.parcel.trend;
                    const parcelYesterday = data.parcel.yesterday;

                    let trendSymbol = parcelTrend === 'up' ? '+' : '';
                    let trendColor = parcelTrend === 'up' ? 'green' : 'red';

                    if (parcelDiff === 0) {
                        trendColor = 'gray';
                        trendSymbol = '';
                    }

                    parcelEl.innerText = data.parcel.today;
                    parcelEl.style.color = trendColor;

                    parcelDescEl.innerHTML = `Yesterday: ${parcelYesterday} <span style="color:${trendColor}; font-weight:bold;">(${trendSymbol}${parcelDiff})</span>`;
                }
            })
            .catch(error => {
                console.error('Error fetching comparison data:', error);
                const earningEl = document.getElementById('compEarning');
                if (earningEl) earningEl.innerText = 'Error';
                const parcelEl = document.getElementById('compParcel');
                if (parcelEl) parcelEl.innerText = 'Error';
            });
    }

    updateComparisonCards();
    // Optional: Refresh every minute
    setInterval(updateComparisonCards, 60000);
});
