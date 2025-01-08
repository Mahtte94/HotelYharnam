// Get the select elements
const roomTypeSelect = document.getElementById("rooms");
const arrivalDateInput = document.getElementById("arrival-date");
const departureDateInput = document.getElementById("departure-date");
const featureCheckboxes = document.querySelectorAll(".feature-checkbox");

// Get the element to display the total cost
const totalCostDisplay = document.getElementById("total-cost");

// Function to calculate the total cost
function calculateTotalCost() {
  const roomType = roomTypeSelect.value;
  const arrivalDate = new Date(arrivalDateInput.value);
  const departureDate = new Date(departureDateInput.value);

  if (isNaN(arrivalDate.getTime()) || isNaN(departureDate.getTime())) {
    totalCostDisplay.textContent = "Total Cost: $0";
    return;
  } else {
    // Calculate the total days
    const totalDays =
      Math.round((departureDate - arrivalDate) / (1000 * 3600 * 24)) + 1;

    // Calculate the room cost based on the room type and total days
    let roomCost;
    switch (roomType) {
      case "economy":
        roomCost = 1 * totalDays;
        break;
      case "standard":
        roomCost = 2 * totalDays;
        break;
      case "luxury":
        roomCost = 4 * totalDays;
        break;
      default:
        roomCost = 0;
    }

    // Calculate the feature cost
    let featureCost = 0;
    featureCheckboxes.forEach((checkbox) => {
      if (checkbox.checked) {
        featureCost += parseFloat(checkbox.dataset.cost);
      }
    });

    // Update the total cost display
    const totalCost = roomCost + featureCost;
    totalCostDisplay.textContent = `Total Cost: $${totalCost}`;
  }
}

// Add event listeners to the select elements
roomTypeSelect.addEventListener("change", calculateTotalCost);
arrivalDateInput.addEventListener("change", calculateTotalCost);
departureDateInput.addEventListener("change", calculateTotalCost);

// Add event listeners to the feature checkboxes
featureCheckboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", calculateTotalCost);
});

calculateTotalCost();

// Room display
const environments = document.querySelectorAll(".environment");
let currentEnvironmentIndex = 0;

function showNextEnvironment() {
  environments[currentEnvironmentIndex].classList.remove("active");
  currentEnvironmentIndex = (currentEnvironmentIndex + 1) % environments.length;
  environments[currentEnvironmentIndex].classList.add("active");
}

// Initial display
environments[currentEnvironmentIndex].classList.add("active");

// Change room every 5 seconds
setInterval(showNextEnvironment, 5000);
