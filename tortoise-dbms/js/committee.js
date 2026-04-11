document.addEventListener('DOMContentLoaded', () => {
    
    const form = document.getElementById('intakeForm');
    const recentList = document.getElementById('recentLogList');

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // 1. Get Values (Simulated)
        const name = form.querySelector('input[type="text"]').value;
        const species = form.querySelector('select').value;
        const newID = 143; // Mock next ID

        // 2. Simulate API Call to Database
        alert(`Success! Tortoise #${newID} (${name}) has been registered.`);

        // 3. Add to Recent List (Visual Feedback)
        const newItem = document.createElement('li');
        newItem.className = 'task-item pending';
        newItem.innerHTML = `
            <span>#${newID} (${species}) - ${name}</span>
            <span class="status">Processing</span>
        `;
        
        // Add to top of list
        recentList.prepend(newItem);

        // 4. Reset Form
        form.reset();
    });
});