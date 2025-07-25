(() => {
    "use strict";

    const formDeleteAttribute = document.querySelectorAll(
        "form.form-delete-attribute"
    );

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

    if (formDeleteAttribute) {
        formDeleteAttribute.forEach((form) => {
            form.addEventListener("submit", (e) => {
                e.preventDefault();

                const name = form.dataset.name;

                const confirmation = confirm(
                    `Are you sure you want to delete attribute '${name}'?`
                );

                if (confirmation) {
                    form.submit();
                }
            });
        });
    }
})();
