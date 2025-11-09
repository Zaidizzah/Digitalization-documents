(() => {
    "use strict";

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

            const ENDPOINT = `${location.origin}/api${location.pathname.replace(
                /\/(documents\/[^\/]+\/user-guides|userguides)\/.+/,
                "/$1"
            )}/content/${userGuideContentWrapper.dataset.userguideId}`;

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
