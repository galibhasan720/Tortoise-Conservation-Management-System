document.addEventListener('DOMContentLoaded', () => {
    
    // Get all checkboxes
    const checkboxes = document.querySelectorAll('.task-checkbox');
    const pendingCountDisplay = document.getElementById('pendingCount');

    checkboxes.forEach(box => {
        box.addEventListener('change', (e) => {
            const row = e.target.closest('.task-row');
            
            if (e.target.checked) {
                row.classList.add('done');
                updateCount(-1);
            } else {
                row.classList.remove('done');
                updateCount(1);
            }
        });
    });

    function updateCount(change) {
        let current = parseInt(pendingCountDisplay.innerText);
        pendingCountDisplay.innerText = current + change;
        
        if (current + change === 0) {
            pendingCountDisplay.style.color = '#2e7d32';
            pendingCountDisplay.innerText = "All Done!";
        }
    }
});