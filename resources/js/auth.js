document.addEventListener("DOMContentLoaded", () => {

    const eyes = document.querySelectorAll(".eye");

    eyes.forEach((eye) => {
        const password = eye.closest(".input-group")?.querySelector(
            'input[type="password"]'
        );

        if (!password) return;

        eye.addEventListener("click", () => {
            const isPassword = password.type === "password";

            password.type = isPassword ? "text" : "password";
            eye.classList.toggle("fa-eye", !isPassword);
            eye.classList.toggle("fa-eye-slash", isPassword);
        });
    });

});