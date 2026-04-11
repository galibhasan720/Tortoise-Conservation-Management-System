document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Get the Tortoise ID from the URL (e.g., profile.html?id=101)
    const urlParams = new URLSearchParams(window.location.search);
    const tortoiseId = urlParams.get('id') || '101'; // Default to 101 if no ID

    // 2. Mock Data (In real life, fetch this from your database)
    const tortoiseData = {
        '101': {
            name: 'Shelly',
            species: 'Galapagos Giant',
            status: 'Healthy',
            age: 45,
            weight: 120,
            enclosure: 'Habitat A-North',
            nextCheckup: 'Mar 15, 2026',
            medicalHistory: [
                { date: '2025-12-10', type: 'Checkup', desc: 'Annual physical - All clear', vet: 'Dr. Smith' },
                { date: '2025-06-05', type: 'Diet', desc: 'Increased calcium intake', vet: 'Dr. Jones' }
            ],
            growthData: [110, 112, 115, 118, 120] // Weight over last 5 years
        },
        '102': {
            name: 'Speedy',
            species: 'Sulcata',
            status: 'Quarantine', // Different status
            age: 12,
            weight: 35,
            enclosure: 'Iso-Ward B',
            nextCheckup: 'TOMORROW',
            medicalHistory: [
                { date: '2026-02-28', type: 'Injury', desc: 'Shell crack repair', vet: 'Dr. Smith' }
            ],
            growthData: [20, 25, 28, 32, 35]
        }
    };

    // 3. Select the data for the requested ID
    const data = tortoiseData[tortoiseId] || tortoiseData['101'];

    // 4. Inject Data into HTML
    document.getElementById('tName').innerText = data.name;
    document.getElementById('tID').innerText = `#${tortoiseId}`;
    document.getElementById('tSpecies').innerText = data.species;
    document.getElementById('tStatus').innerText = data.status;
    document.getElementById('tAge').innerText = `${data.age} Yrs`;
    document.getElementById('tWeight').innerText = `${data.weight} kg`;
    document.getElementById('tEnclosure').innerText = data.enclosure;
    document.getElementById('tNextCheckup').innerText = data.nextCheckup;

    // Change Badge Color based on status
    const badge = document.getElementById('tStatus');
    if (data.status === 'Quarantine') {
        badge.style.backgroundColor = '#d32f2f'; // Red for warning
    } else {
        badge.style.backgroundColor = '#2e7d32'; // Green for healthy
    }

    // 5. Populate Medical Table
    const tableBody = document.getElementById('medicalTableBody');
    data.medicalHistory.forEach(record => {
        const row = `<tr>
            <td>${record.date}</td>
            <td><strong>${record.type}</strong></td>
            <td>${record.desc}</td>
            <td>${record.vet}</td>
        </tr>`;
        tableBody.innerHTML += row;
    });

    // 6. Initialize Growth Chart (using Chart.js)
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['2022', '2023', '2024', '2025', '2026'],
            datasets: [{
                label: 'Weight (kg)',
                data: data.growthData,
                borderColor: '#2e7d32',
                tension: 0.3,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

});

// Tab Switching Logic
function openTab(tabName) {
    // Hide all contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(c => c.classList.add('hidden'));
    contents.forEach(c => c.classList.remove('active'));

    // Deactivate all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(b => b.classList.remove('active'));

    // Show clicked content
    document.getElementById(tabName).classList.remove('hidden');
    document.getElementById(tabName).classList.add('active');
    
    // Activate clicked button
    event.currentTarget.classList.add('active');
}