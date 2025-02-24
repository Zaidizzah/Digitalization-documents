(() => {
    "use strict";

    // popover handler initialization
    const listPopover = document.querySelectorAll("[popovertarget]");
    if (listPopover) {
        listPopover.forEach((popover) => {
            const popoverTarget = document.querySelector(
                `${popover.getAttribute("popovertarget")}[popover]`
            );

            if (popoverTarget) {
                popover.addEventListener("click", function (e) {
                    if (
                        e.target.closest(
                            ".btn-edit-attribute, .btn-delete-attribute"
                        )
                    )
                        return;
                    popoverTarget.togglePopover();
                });

                popoverTarget.addEventListener("beforetoggle", function (e) {
                    if (e.newState === "open") {
                        popover.setAttribute("aria-expanded", "true");
                        popover.classList.add("table-active");
                    } else {
                        popover.setAttribute("aria-expanded", "false");
                        popover.classList.remove("table-active");
                    }
                });
            }
        });
    }
})();
