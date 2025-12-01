(() => {
    "use strict";

    const sidebar = document.getElementById("sidebar");
    const mobileToggle = document.getElementById("mobileToggle");
    const closeBtn = document.getElementById("closeBtn");
    const overlay = document.getElementById("overlay");

    function toggleSubmenu(element) {
        event.stopPropagation(); // Prevent event bubbling

        const submenu = element.nextElementSibling;
        const arrow = element.querySelector(".arrow");

        const isCurrentlyOpen = submenu.classList.contains("show");

        // Tutup submenu yang satu level dengan element ini (sibling)
        const parent = element.parentElement.parentElement;
        const siblings = parent.querySelectorAll(
            ":scope > .menu-item > .submenu, :scope > .submenu-item > .submenu"
        );

        siblings.forEach((sub) => {
            if (sub !== submenu && !submenu.contains(sub)) {
                sub.classList.remove("show");
                const siblingArrow =
                    sub.previousElementSibling.querySelector(".arrow");
                if (siblingArrow) siblingArrow.classList.remove("rotate");
            }
        });

        // Toggle submenu saat ini
        if (isCurrentlyOpen) {
            submenu.classList.remove("show");
            arrow.classList.remove("rotate");
            // Tutup semua child submenu
            submenu.querySelectorAll(".submenu").forEach((child) => {
                child.classList.remove("show");
                const childArrow =
                    child.previousElementSibling.querySelector(".arrow");
                if (childArrow) childArrow.classList.remove("rotate");
            });
        } else {
            submenu.classList.add("show");
            arrow.classList.add("rotate");
        }
    }

    mobileToggle.addEventListener("click", () => {
        sidebar.classList.add("open");
        overlay.classList.add("show");
        mobileToggle.style.display = "none";
    });

    closeBtn.addEventListener("click", closeSidebar);
    overlay.addEventListener("click", closeSidebar);

    function closeSidebar() {
        sidebar.classList.remove("open");
        overlay.classList.remove("show");
        mobileToggle.style.display = "block";
    }

    // Active state untuk submenu (hanya untuk link, bukan yang punya child)
    document
        .querySelectorAll(".submenu-link:not(.has-child)")
        .forEach((link) => {
            link.addEventListener("click", function (e) {
                document
                    .querySelectorAll(".submenu-link")
                    .forEach((l) => l.classList.remove("active"));
                this.classList.add("active");

                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

    // Active state untuk menu utama
    document.querySelectorAll(".menu-link[href]").forEach((link) => {
        link.addEventListener("click", function (e) {
            document
                .querySelectorAll(".menu-link")
                .forEach((l) => l.classList.remove("active"));
            this.classList.add("active");

            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    const userGuideSidebarMenuTogglers = document.querySelectorAll(
            ".app-userguide-sidebar__menu button.app-userguide-sidebar__menu-toggle"
        ),
        userGuideContentWrapper = document.querySelector(
            "#app-userguide-content"
        );

    if (userGuideSidebarMenuTogglers) {
        userGuideSidebarMenuTogglers.forEach((btn) => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();

                const controlElement =
                    btn.getAttribute("aria-controls") !== null
                        ? document.querySelector(
                              `#${btn.getAttribute("aria-controls")}`
                          )
                        : null;

                if (controlElement) {
                    if (controlElement.classList.contains("expanded")) {
                        controlElement.classList.remove("expanded");
                        controlElement.ariaExpanded = false;
                        btn.innerHTML = '<i class="bi bi-chevron-down"></i>';
                    } else {
                        controlElement.classList.add("expanded");
                        controlElement.ariaExpanded = true;
                        btn.innerHTML = '<i class="bi bi-chevron-up"></i>';
                    }
                }
            });
        });
    }

    if (userGuideContentWrapper) {
        // Check if dataset userguideId is NULL or NOT defined
        if (
            userGuideContentWrapper.dataset.userguideId === null ||
            userGuideContentWrapper.dataset.userguideId === undefined
        ) {
            toast(
                "Failed to get user guide content. Please try again.",
                "error"
            );
            return;
        }

        try {
            LOADER.show(true);

            const ENDPOINT = `${location.origin}/api${location.pathname
                .replace(
                    /\/(documents\/[^\/]+\/user-guides|userguides)\/.+/,
                    "/$1"
                )
                .replace(/\/userguides\/[^\/]+/, "/get/content/")}`;

            console.log(ENDPOINT);

            // const response = fetch(
            //     `${location.origin}/api/user-guides/content/${userGuideContentWrapper.dataset.userguideId}`,
            //     {
            //         method: "GET",
            //         headers: {
            //             "Content-Type": "application/json",
            //             "X-CSRF-TOKEN": CSRF_TOKEN,
            //             "XSRF-TOKEN": XSRF_TOKEN,
            //             Accept: "application/json",
            //         },
            //         credentials: "include",
            //     }
            // );

            // if (!response.ok) {
            //     throw new Error(
            //         "Failed to get current user guide content. Please try again."
            //     );
            // }

            // const RESULT_RESPONSE = response.json();

            // if (
            //     RESULT_RESPONSE.hasOwnProperty("success") &&
            //     RESULT_RESPONSE.success !== true
            // ) {
            //     throw new Error(RESULT_RESPONSE.message);
            // }

            // if (
            //     RESULT_RESPONSE.hasOwnProperty("content") &&
            //     SHOWDOWN instanceof showdown.Converter
            // ) {
            //     userGuideContentWrapper.innerHTML = SHOWDOWN.makeHtml(
            //         RESULT_RESPONSE.content.replace(/\r\n/g, "\n").trim()
            //     );
            // } else {
            //     throw new Error(
            //         "Failed to get current user guide content. Maybe SHOWDOWN JS is not loaded or user guide content is not valid/corrupted. Please try again."
            //     );
            // }

            // toast(data.message, data.success ? "success" : "error");
        } catch (error) {
            console.error(error);

            // Display an error message
            toast(error.message, "error");
        } finally {
            LOADER.hide();
        }
    }
})(document, TextEditorHTML, SHOWDOWN);
