document.addEventListener('DOMContentLoaded', () => {
    // Check if Chart.js is loaded
    if (document.getElementById('healthChart')) {
        const ctx = document.getElementById('healthChart').getContext('2d');
        
        // Create a Mock Chart (Pie Chart for Health Status)
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Under Treatment', 'Quarantine'],
                datasets: [{
                    data: [120, 15, 7],
                    backgroundColor: ['#2e7d32', '#ffa000', '#d32f2f'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
});