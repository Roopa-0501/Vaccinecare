// Show signup modal
document.getElementById('signup')?.addEventListener('click', () => {
    var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
    signupModal.show();
});

// Show login modal
document.getElementById('login')?.addEventListener('click', () => {
    var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
});

// Switch between login/signup
document.getElementById('opensignup')?.addEventListener('click', () => {
    var loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
    loginModal.hide();
    var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
    signupModal.show();
});

document.getElementById('openlogin')?.addEventListener('click', () => {
    var signupModal = bootstrap.Modal.getInstance(document.getElementById('signupModal'));
    signupModal.hide();
    var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
});


