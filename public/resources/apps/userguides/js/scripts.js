(() => {
    "use strict";

    const btnExpands = document.querySelectorAll("button.btn.expand-btn");
    if (btnExpands) {
        btnExpands.forEach((element) => {
            element.addEventListener("click", function () {
                const ariaControlElement =
                    element.getAttribute("aria-controls");

                if (ariaControlElement) {
                    const childrenContainer = document.querySelector(
                        `#${ariaControlElement}`
                    );

                    if (childrenContainer) {
                        if (childrenContainer.classList.contains("expanded")) {
                            childrenContainer.classList.remove("expanded");

                            // Change chevro icon
                            element.innerHTML =
                                '<i class="bi bi-chevron-right"></i>';
                        } else {
                            childrenContainer.classList.add("expanded");

                            // Change chevron icon
                            element.innerHTML =
                                '<i class="bi bi-chevron-down"></i>';
                        }
                    }
                }
            });
        });
    }

    const formActivateUserGuideDatas = document.querySelectorAll(
        ".tree-item form.form-activate-user-guide-data"
    );
    if (formActivateUserGuideDatas) {
        formActivateUserGuideDatas.forEach((element) => {
            element.addEventListener("submit", function (event) {
                event.preventDefault();

                const title = element.dataset.title,
                    switchToStatus = element.dataset.switchTo,
                    confirmation = confirm(
                        `Are you sure you want to switch the status of '${title}' user guide data to ${switchToStatus.toLowerCase()}? that will make the childrens data invisible too.`
                    );

                if (confirmation) {
                    element.submit();
                }
            });
        });
    }

    const formDeleteUserGuideDatas = document.querySelectorAll(
        ".tree-item form.form-delete-user-guide-data"
    );
    if (formDeleteUserGuideDatas) {
        formDeleteUserGuideDatas.forEach((element) => {
            element.addEventListener("submit", function (event) {
                event.preventDefault();

                const title = element.dataset.title,
                    confirmation = confirm(
                        `Are your sure you want to delete '${title}' user guide data?`
                    );

                if (confirmation) {
                    element.submit();
                }
            });
        });
    }
})();
