document.addEventListener('DOMContentLoaded', () => {
    
    // Initialize the IOT Temperature Chart
    const ctx = document.getElementById('tempChart').getContext('2d');
    
    const tempChart = new Chart(ctx, {
        type: 'line',
        data: {
            // Simulated time stamps (e.g., 10:00, 10:15...)
            labels: ['10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00'],
            datasets: [{
                label: 'Temperature (°C)',
                // Ideal temp is around 30°C for many tortoises
                data: [29.5, 29.8, 30.1, 30.0, 30.2, 29.9, 30.1], 
                borderColor: '#7b1fa2', // Purple line
                backgroundColor: 'rgba(123, 31, 162, 0.1)',
                borderWidth: 2,
                tension: 0.4, // Smooth curves
                fill: true
            },
            {
                label: 'Humidity (%)',
                data: [75, 76, 75, 74, 75, 76, 75],
                borderColor: '#0288d1', // Blue line
                borderWidth: 2,
                borderDash: [5, 5], // Dashed line for humidity
                tension: 0.4,
                yAxisID: 'y1' // Use right-side axis
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Fits inside the container we made
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Temp (°C)' },
                    min: 28,
                    max: 32
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Humidity (%)' },
                    grid: { drawOnChartArea: false }, // Don't show grid lines for this one
                    min: 60,
                    max: 90
                }
            }
        }
    });
});