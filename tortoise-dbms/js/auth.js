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

    // Mock Login Submission logic (This is where your DBMS API call will go)
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Prevents the page from refreshing
        
        const emailInput = document.getElementById('loginEmail').value;
        
        // In a real application, you would send this to your backend via fetch()
        // Here we simulate the database returning a role based on the username entered.
        
        console.log(`Authenticating user: ${emailInput}`);
        
        // Mock routing logic based on what the user typed in
        if (emailInput.includes('vet')) {
            window.location.href = 'dashboards/vet.html';
        } else if (emailInput.includes('super')) {
            window.location.href = 'dashboards/supervisor.html';
        } else if (emailInput.includes('breed')) {
            window.location.href = 'dashboards/breeding.html';
        } 
        // Add this inside your login logic
        else if (emailInput.includes('committee')) {
            window.location.href = 'dashboards/committee.html';
        }
        else {
            // Default redirect for staff/caretakers
            window.location.href = 'dashboards/staff.html';
        }
    });
});