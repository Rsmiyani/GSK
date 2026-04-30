// document.addEventListener ensures that all the HTML has fully loaded on screen before we start running our JavaScript.
document.addEventListener("DOMContentLoaded", function() {

    // --- HERO IMAGE SLIDER LOGIC ---
    const slides = document.querySelectorAll(".hero .slide");
    let currentSlideIndex = 0;

    // Only run slider if we actually have slides on the page
    if (slides.length > 0) {
        // Change slide every 5 seconds (5000 milliseconds)
        setInterval(() => {
            // Remove 'active' class from the current visible slide
            slides[currentSlideIndex].classList.remove("active");
            
            // Move to the next slide. Wrap back to 0 if we hit the end.
            currentSlideIndex = (currentSlideIndex + 1) % slides.length;
            
            // Add 'active' class to the new target slide to fade it in
            slides[currentSlideIndex].classList.add("active");
        }, 5000);
    }

    // --- LOCATION DETECTION LOGIC ---
    // Target the button and paragraph tags by their IDs
    // We use these variables to interact with elements inside our index.php file
    const findStoresBtn = document.getElementById("findStoresBtn");
    const locationStatus = document.getElementById("locationStatus");

    // Listen for a 'click' event on the 'Find Nearby Stores' button
    findStoresBtn.addEventListener("click", function() {
        
        // 1. Check if the user's web browser actually supports Geolocation
        if (navigator.geolocation) {
            
            // Show a pending processing message while we wait for the user to allow location access
            locationStatus.textContent = "Locating you... Please allow location access when prompted.";
            locationStatus.style.color = "#ffd700"; // Yellow-ish pending color
            
            // 2. Call the browser API to get the user's current GPS position
            navigator.geolocation.getCurrentPosition(
                
                // --- SUCCESS CALLBACK ---
                // If the user clicks "Allow", this function runs and gives us position datav
                function(position) {
                    
                    // Extract the latitude and longitude from the result
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    // Show a success message on the screen
                    locationStatus.style.color = "#4CAF50"; // Green color for success
                    locationStatus.textContent = `Location found! Lat: ${latitude.toFixed(2)}, Lng: ${longitude.toFixed(2)}. Redirecting to nearby shops...`;
                    
                    // 3. (Future Phase) Here, we would redirect the customer to a shop_listing.php page
                    // We can pass the latitude and longitude so PHP and MySQL can use the Haversine formula to find close shops
                    // Example: 
                    // setTimeout(() => {
                    //     window.location.href = `shop_listing.php?lat=${latitude}&lng=${longitude}`;
                    // }, 2000);  // Wait 2 seconds before redirecting
                },
                
                // --- ERROR CALLBACK ---
                // If the user clicks "Deny" or something goes wrong, this function runs
                function(error) {
                    locationStatus.style.color = "#ff4c4c"; // Red color for error
                    
                    // Specific error handling messages based on the code we receive
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            locationStatus.textContent = "Location access denied. We cannot find nearby stores automatically. Please search manually.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            locationStatus.textContent = "Location information is unavailable at the moment.";
                            break;
                        case error.TIMEOUT:
                            locationStatus.textContent = "The request to get user location timed out. Please try again.";
                            break;
                        case error.UNKNOWN_ERROR:
                            locationStatus.textContent = "An unknown error occurred while trying to find your location.";
                            break;
                    }
                }
            );
        } else {
            // Error when the browser is too old or unsupported
            locationStatus.style.color = "#ff4c4c";
            locationStatus.textContent = "Geolocation is not supported by your browser.";
        }
    });

});