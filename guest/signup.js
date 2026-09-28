// Client-Side Signup Form Validation
function validateSignupForm() {
    const role = document.getElementById('role').value;
    const name = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const address = document.getElementById('address').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const nid_number = document.getElementById('reg_nid_number').value.trim();
    const terms = document.getElementById('terms').checked;

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
    if (nid_number === "") {
        alert("Please enter NID/Registration number.");
        return false;
    }
    alert("Registration Successful! Please wait for admin approval.");
    return true;
}
