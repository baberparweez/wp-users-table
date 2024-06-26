document.addEventListener("DOMContentLoaded", function () {
    const userLinks = document.querySelectorAll(".users__table--user");
    const detailsContainer = document.querySelector(".users__table--details");

    userLinks.forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();

            // Display a loading message while fetching user details
            detailsContainer.innerHTML = `<svg width="40" height="40" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_aj0A{transform-origin:center;animation:spinner_KYSC .75s infinite linear}@keyframes spinner_KYSC{100%{transform:rotate(360deg)}}</style><path d="M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z" class="spinner_aj0A"/></svg>`;

            const userId = this.getAttribute("data-user-id");

            fetch(
                `${myUsersTable.ajax_url}?action=fetch_user_details&user_id=${userId}&nonce=${myUsersTable.nonce}`,
                {
                    method: "GET",
                    credentials: "same-origin", // Needed for WordPress to accept the request
                }
            )
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.json();
                })
                .then((data) => {
                    data = data.data;
                    // Update the DOM with user details
                    detailsContainer.innerHTML = `
                        <div class="users__table--details_inner">
                            <div>
                                <h3>${data.name}</h3>
                                <p>${data.phone}</p>
                                <p>${data.email}</p>
                                <p>${data.website}</p>
                            </div>
                            <div>
                                <h3>Address</h3>
                                <p>${data.address.city}</p>
                                <p>${data.address.street}</p>
                                <p>${data.address.suite}</p>
                                <p>${data.address.zipcode}</p>
                            </div>
                            <div>
                                <h3>Company</h3>
                                <p>${data.company.name}</p>
                                <p>${data.company.catchPhrase}</p>
                                <p>${data.company.bs}</p>
                            </div>
                        </div>
                    `;
                })
                .catch((error) => {
                    console.error("Error fetching user details:", error);
                    // Display an error message to the user
                    detailsContainer.innerHTML = `<p>Error loading user details.</p>`;
                });
        });
    });
});
