document.addEventListener('DOMContentLoaded', () => {
    // Get form elements
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const forgotForm = document.getElementById('forgotForm');

    // Get link elements
    const linkSignup = document.getElementById('linkSignup');
    const linkForgot = document.getElementById('linkForgot');
    const linkLoginFromSignup = document.getElementById('linkLoginFromSignup');
    const linkLoginFromForgot = document.getElementById('linkLoginFromForgot');

    // Helper function to switch forms
    function showForm(formToShow) {
        // Hide all forms
        loginForm.classList.remove('active');
        signupForm.classList.remove('active');
        forgotForm.classList.remove('active');
        
        // Ensure hidden class is applied for CSS flexbox hiding
        loginForm.classList.add('hidden');
        signupForm.classList.add('hidden');
        forgotForm.classList.add('hidden');

        // Show requested form
        formToShow.classList.remove('hidden');
        formToShow.classList.add('active');
    }

    // Event Listeners for toggling
    linkSignup.addEventListener('click', (e) => {
        e.preventDefault();
        showForm(signupForm);
    });

    linkForgot.addEventListener('click', (e) => {
        e.preventDefault();
        showForm(forgotForm);
    });

    linkLoginFromSignup.addEventListener('click', (e) => {
        e.preventDefault();
        showForm(loginForm);
    });

    linkLoginFromForgot.addEventListener('click', (e) => {
        e.preventDefault();
        showForm(loginForm);
    });

    // Mock Login Submission logic
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Prevents the page from refreshing
        
        // Convert to lowercase so "Vet" and "vet" both work
        const emailInput = document.getElementById('loginEmail').value.toLowerCase(); 
        
        console.log(`Authenticating user: ${emailInput}`);
        
        // Mock routing logic based on user role
        if (emailInput.includes('vet')) {
            window.location.href = 'dashboards/vet.html';
        } 
        else if (emailInput.includes('super') || emailInput.includes('admin')) {
            window.location.href = 'dashboards/supervisor.html';
        } 
        else if (emailInput.includes('breed')) {
            window.location.href = 'dashboards/breeding.html';
        } 
        else if (emailInput.includes('committee') || emailInput.includes('collect')) {
            window.location.href = 'dashboards/committee.html';
        }
        else if (emailInput.includes('care') || emailInput.includes('staff')) {
            // Updated to point to your new caretaker dashboard
            window.location.href = 'dashboards/caretaker.html'; 
        }
        
        else if (emailInput.includes('env') || emailInput.includes('tech')) {
            window.location.href = 'dashboards/environmental.html';
        }
        else {
            // If they type something random, show an error instead of breaking
            alert('User role not found. Please use an email containing: vet, super, breed, committee, or care.');
        }
    });
});