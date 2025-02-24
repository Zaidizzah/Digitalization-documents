(() => {
    "use strict";

    const navLinks = document.querySelectorAll(
        "#document-menu .nav-menu .nav-link"
    );

    navLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            navLinks.forEach((l) => l.classList.remove("active"));
            e.target.closest(".nav-link").classList.add("active");
        });
    });
})();
