document.addEventListener('DOMContentLoaded', () => {
    
    const searchInput = document.getElementById('patientSearch');
    const searchBtn = document.getElementById('searchBtn');

    // Function to handle redirection
    function performSearch() {
        const query = searchInput.value.trim();
        
        if (query) {
            // Redirect to the Profile page with the ID in the URL
            // Note: We go up one folder (..) then into entities
            window.location.href = `../entities/profile.html?id=${query}`;
        } else {
            alert("Please enter a Tortoise ID!");
        }
    }

    // 1. Listen for Click on Search Button
    searchBtn.addEventListener('click', performSearch);

    // 2. Listen for "Enter" key inside the input box
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            performSearch();
        }
    });

});

// Global function for the "Open Profile" buttons in the table
// We attach this to the window object so the HTML onclick="" can see it
window.viewPatient = function(id) {
    window.location.href = `../entities/profile.html?id=${id}`;
};