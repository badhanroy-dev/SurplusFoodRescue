// Client-Side Signup Form Validation
function validateSignupForm() {
    var role = document.getElementById('role').value;
    var name = document.getElementById('full_name').value.trim();
    var email = document.getElementById('email').value.trim();
    var phone = document.getElementById('phone').value.trim();
    var address = document.getElementById('address').value.trim();
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    var terms = document.getElementById('terms').checked;

    if (role === "") {
        alert("Please select an Account Type.");
        return false;
    }
    if (name === "") {
        alert("Please enter your Organization or Full Name.");
        return false;
    }
    if (email === "") {
        alert("Please enter your Email Address.");
        return false;
    }
    var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }
    if (phone === "") {
        alert("Please enter your Phone Number.");
        return false;
    }
    if (address === "") {
        alert("Please enter your Address / Location.");
        return false;
    }
    if (password === "") {
        alert("Please enter a Password.");
        return false;
    }
    if (password.length < 6) {
        alert("Password must be at least 6 characters long.");
        return false;
    }
    if (password !== confirmPassword) {
        alert("Password and Confirm Password do not match.");
        return false;
    }
    if (!terms) {
        alert("You must agree to the Terms of Service and Privacy Policy.");
        return false;
    }
    return true;
}
