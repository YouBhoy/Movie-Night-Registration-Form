// Global variables
let selectedSeats = []
let maxSeats = 0
let currentShift = ""
let seatData = {}
let eventSettings = {}

// Security functions
function sanitizeInput(input) {
  if (typeof input !== "string") return input
  return input.replace(/[<>'"&]/g, (match) => {
    const escapeMap = {
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#x27;",
      "&": "&amp;",
    }
    return escapeMap[match]
  })
}

function validateName(name) {
  if (!name || name.length < 2 || name.length > 255) {
    return "Name must be between 2 and 255 characters"
  }
  if (!/^[a-zA-Z\s\-.']+$/.test(name)) {
    return "Name can only contain letters, spaces, hyphens, dots, and apostrophes"
  }
  return null
}

function validateSeats(seats, pax, shift) {
  if (seats.length !== pax) {
    return "Number of selected seats must match number of attendees"
  }

  for (const seat of seats) {
    if (!/^[A-L](1[01]|[1-9])$/.test(seat)) {
      return "Invalid seat format"
    }

    const seatNum = Number.parseInt(seat.substring(1))
    if (shift === "normal" && (seatNum < 1 || seatNum > 6)) {
      return "Normal shift seats must be 1-6"
    }
    if (shift === "crew_c" && (seatNum < 7 || seatNum > 11)) {
      return "Crew C shift seats must be 7-11"
    }
  }

  return null
}

// Rate limiting for client-side
let lastSubmission = 0
const SUBMISSION_COOLDOWN = 5000 // 5 seconds

// Registration form handling
document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM loaded, initializing...")

  // Load event settings first
  loadEventSettings()

  const form = document.getElementById("registrationForm")
  const submitBtn = document.getElementById("submitBtn")
  const submitText = document.getElementById("submitText")
  const loadingIcon = document.getElementById("loadingIcon")
  const errorMessage = document.getElementById("errorMessage")
  const paxSelect = document.getElementById("number_of_pax")
  const shiftSelect = document.getElementById("shift_preference")
  const seatSelectionGroup = document.getElementById("seatSelectionGroup")
  const staffNameInput = document.getElementById("staff_name")

  // Load seat data when page loads
  loadSeatData()

  // Enhanced input validation
  if (staffNameInput) {
    staffNameInput.addEventListener("input", function () {
      const error = validateName(this.value)
      if (error) {
        this.setCustomValidity(error)
      } else {
        this.setCustomValidity("")
      }
    })

    // Prevent XSS in real-time
    staffNameInput.addEventListener("input", function () {
      this.value = sanitizeInput(this.value)
    })
  }

  // Handle number of pax change
  if (paxSelect) {
    paxSelect.addEventListener("change", function () {
      console.log("Pax changed to:", this.value)
      maxSeats = Number.parseInt(this.value) || 0
      selectedSeats = []
      updateSelectedSeatsDisplay()

      if (maxSeats > 0 && currentShift) {
        console.log("Showing seat selection")
        seatSelectionGroup.classList.remove("hidden")
        renderSeats()
      }
    })
  }

  // Handle shift preference change
  if (shiftSelect) {
    shiftSelect.addEventListener("change", function () {
      console.log("Shift changed to:", this.value)
      currentShift = this.value
      selectedSeats = []
      updateSelectedSeatsDisplay()

      if (maxSeats > 0 && currentShift) {
        console.log("Showing seat selection for shift:", currentShift)
        seatSelectionGroup.classList.remove("hidden")
        if (Object.keys(seatData).length === 0) {
          loadSeatData().then(() => {
            renderSeats()
          })
        } else {
          renderSeats()
        }
      } else {
        seatSelectionGroup.classList.add("hidden")
      }
    })
  }

  // Form submission
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault()

      // Rate limiting check
      const now = Date.now()
      if (now - lastSubmission < SUBMISSION_COOLDOWN) {
        showError("Please wait before submitting again")
        return
      }

      // Enhanced validation
      const staffName = staffNameInput.value.trim()
      const nameError = validateName(staffName)
      if (nameError) {
        showError(nameError)
        return
      }

      // Validate seat selection
      if (maxSeats > 0) {
        const seatError = validateSeats(selectedSeats, maxSeats, currentShift)
        if (seatError) {
          showError(seatError)
          return
        }
      }

      // Show loading state
      submitBtn.disabled = true
      submitText.style.display = "none"
      loadingIcon.style.display = "inline-block"
      errorMessage.classList.add("hidden")

      try {
        const formData = new FormData(form)
        formData.set("selected_seats", selectedSeats.join(","))

        const response = await fetch("register.php", {
          method: "POST",
          body: formData,
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const result = await response.json()

        if (result.success) {
          lastSubmission = now

          // Store registration data for confirmation page
          if (result.registration_data) {
            localStorage.setItem("registrationData", JSON.stringify(result.registration_data))
          }

          // Redirect to confirmation page
          window.location.href = "confirmation.html"
        } else {
          showError(result.message || "Registration failed")
        }
      } catch (error) {
        console.error("Error:", error)
        showError("An error occurred. Please try again.")
      } finally {
        // Reset loading state
        submitBtn.disabled = false
        submitText.style.display = "inline"
        loadingIcon.style.display = "none"
      }
    })
  }
})

// Load event settings from server
async function loadEventSettings() {
  try {
    console.log("Loading event settings...")
    const response = await fetch("get-settings.php")

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const result = await response.json()

    if (result.success) {
      eventSettings = result.settings
      updatePageContent()
      console.log("Event settings loaded:", eventSettings)
    } else {
      console.error("Failed to load event settings:", result)
    }
  } catch (error) {
    console.error("Error loading event settings:", error)
  }
}

// Update page content with event settings
function updatePageContent() {
  // Update page title
  const pageTitle = document.getElementById("pageTitle")
  if (pageTitle) {
    pageTitle.textContent = eventSettings.event_title || "WD Movie Night Registration"
  }

  // Update header content
  const eventTitle = document.getElementById("eventTitle")
  if (eventTitle) {
    eventTitle.textContent = eventSettings.event_title || "WD Movie Night"
  }

  const movieTitle = document.getElementById("movieTitle")
  if (movieTitle) {
    movieTitle.textContent = eventSettings.movie_title || "MOVIE NIGHT"
  }

  // Update event details
  const eventDetails = document.getElementById("eventDetails")
  if (eventDetails) {
    let details = ""
    if (eventSettings.event_date && eventSettings.event_date !== "Coming Soon") {
      details += eventSettings.event_date
    }
    if (eventSettings.event_time && eventSettings.event_time !== "TBA") {
      details += (details ? " | " : "") + eventSettings.event_time
    }
    if (eventSettings.venue) {
      details += (details ? " | " : "") + eventSettings.venue
    }

    eventDetails.textContent = details || "Event details will be updated soon"
  }

  // Update event description
  const eventDescription = document.getElementById("eventDescription")
  if (eventDescription) {
    eventDescription.textContent = eventSettings.event_description || "Questions? Contact the WD team for assistance."
  }

  // Update max attendees note
  const maxAttendeesNote = document.getElementById("maxAttendeesNote")
  if (maxAttendeesNote) {
    const maxAttendees = eventSettings.max_attendees_per_registration || 4
    maxAttendeesNote.textContent = `Maximum ${maxAttendees} people per registration`

    // Update select options
    updateMaxAttendeesOptions(maxAttendees)
  }

  // Update shift labels
  const normalShiftOption = document.getElementById("normalShiftOption")
  if (normalShiftOption) {
    const normalLabel = eventSettings.normal_shift_label || "Normal Shift"
    const normalSeats = eventSettings.normal_shift_seats || "1-6"
    normalShiftOption.textContent = `${normalLabel} (Seats ${normalSeats})`
  }

  const crewShiftOption = document.getElementById("crewShiftOption")
  if (crewShiftOption) {
    const crewLabel = eventSettings.crew_shift_label || "Crew C - Day Shift"
    const crewSeats = eventSettings.crew_shift_seats || "7-11"
    crewShiftOption.textContent = `${crewLabel} (Seats ${crewSeats})`
  }

  // Update venue title
  const venueTitle = document.getElementById("venueTitle")
  if (venueTitle) {
    venueTitle.textContent = (eventSettings.venue || "CINEMA HALL 1").toUpperCase()
  }

  // Update shift headers
  const normalShiftHeader = document.getElementById("normalShiftHeader")
  if (normalShiftHeader) {
    normalShiftHeader.textContent = (eventSettings.normal_shift_label || "NORMAL SHIFT").toUpperCase()
  }

  const crewShiftHeader = document.getElementById("crewShiftHeader")
  if (crewShiftHeader) {
    crewShiftHeader.textContent = (eventSettings.crew_shift_label || "CREW C (DAY SHIFT)").toUpperCase()
  }
}

// Update max attendees select options
function updateMaxAttendeesOptions(maxAttendees) {
  const paxSelect = document.getElementById("number_of_pax")
  if (!paxSelect) return

  // Clear existing options except the first one
  while (paxSelect.children.length > 1) {
    paxSelect.removeChild(paxSelect.lastChild)
  }

  // Add new options
  for (let i = 1; i <= maxAttendees; i++) {
    const option = document.createElement("option")
    option.value = i
    option.textContent = i.toString()
    paxSelect.appendChild(option)
  }
}

// Load seat data from server
async function loadSeatData() {
  try {
    console.log("Loading seat data...")
    const response = await fetch("get-seats.php")

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const result = await response.json()

    console.log("Seat data response:", result)

    if (result.success) {
      seatData = result.seats
      console.log("Seat data loaded successfully:", seatData)
      return true
    } else {
      console.error("Failed to load seat data:", result)
      return false
    }
  } catch (error) {
    console.error("Error loading seat data:", error)
    return false
  }
}

// Render seats based on current shift
function renderSeats() {
  console.log("Rendering seats for shift:", currentShift)
  console.log("Available seat data:", seatData)

  if (!currentShift || !seatData[currentShift]) {
    console.error("No seat data for shift:", currentShift)
    return
  }

  const container =
    currentShift === "normal" ? document.getElementById("normalSeats") : document.getElementById("crewSeats")

  if (!container) {
    console.error("Container not found for shift:", currentShift)
    return
  }

  console.log("Rendering in container:", container.id)
  container.innerHTML = ""

  const rows = ["A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L"]

  rows.forEach((row) => {
    if (!seatData[currentShift][row]) {
      console.log("No seats for row:", row)
      return
    }

    console.log(`Rendering row ${row} with ${seatData[currentShift][row].length} seats`)

    const rowDiv = document.createElement("div")
    rowDiv.className = "flex items-center mb-2 gap-2"

    // Row label
    const rowLabel = document.createElement("div")
    rowLabel.className = "font-semibold text-cinema-brown w-5 text-center"
    rowLabel.textContent = row
    rowDiv.appendChild(rowLabel)

    // Seats in this row
    seatData[currentShift][row].forEach((seatInfo) => {
      const seat = document.createElement("div")
      seat.className =
        "w-8 h-8 border-2 rounded flex items-center justify-center text-xs font-semibold cursor-pointer transition-all duration-200"
      seat.textContent = seatInfo.number
      seat.dataset.seat = row + seatInfo.number

      if (seatInfo.occupied) {
        seat.className += " bg-red-500 border-red-500 text-white cursor-not-allowed"
      } else {
        seat.className +=
          " bg-white border-amber-300 text-cinema-brown hover:border-cinema-brown hover:bg-cinema-light hover:scale-105"
        seat.addEventListener("click", () => toggleSeat(row + seatInfo.number))
      }

      rowDiv.appendChild(seat)
    })

    container.appendChild(rowDiv)
  })

  // Show/hide sections based on shift
  const normalSection = document.getElementById("normalSection")
  const crewSection = document.getElementById("crewSection")

  if (currentShift === "normal") {
    normalSection.classList.remove("hidden")
    crewSection.classList.add("hidden")
    console.log("Showing normal section")
  } else if (currentShift === "crew_c") {
    normalSection.classList.add("hidden")
    crewSection.classList.remove("hidden")
    console.log("Showing crew section")
  }
}

// Update seat selection with Tailwind classes
function updateSeatSelection() {
  const seats = document.querySelectorAll("[data-seat]")

  seats.forEach((seat) => {
    const seatId = seat.dataset.seat

    // Skip occupied seats
    if (seat.classList.contains("bg-red-500")) {
      return
    }

    // Reset classes
    seat.className =
      "w-8 h-8 border-2 rounded flex items-center justify-center text-xs font-semibold cursor-pointer transition-all duration-200"

    if (selectedSeats.includes(seatId)) {
      seat.className += " bg-cinema-brown border-cinema-brown text-cinema-light scale-105"
    } else {
      seat.className +=
        " bg-white border-amber-300 text-cinema-brown hover:border-cinema-brown hover:bg-cinema-light hover:scale-105"

      // Disable seats if max reached and not selected
      if (selectedSeats.length >= maxSeats) {
        seat.className += " opacity-50 cursor-not-allowed"
      }
    }
  })
}

// Validate seat gaps
function validateSeatGaps(newSeatId) {
  if (selectedSeats.length === 0) return true

  const newRow = newSeatId.charAt(0)
  const newSeatNum = Number.parseInt(newSeatId.substring(1))

  // Check for single gaps
  const adjacentSeats = [newSeatNum - 1, newSeatNum + 1]
  let hasGap = false

  adjacentSeats.forEach((adjSeatNum) => {
    const adjSeatId = newRow + adjSeatNum
    const adjSeat = document.querySelector(`[data-seat="${adjSeatId}"]`)

    if (adjSeat && !adjSeat.classList.contains("bg-red-500") && !selectedSeats.includes(adjSeatId)) {
      // Check if there's a seat next to this adjacent seat that's selected
      const nextSeats = [adjSeatNum - 1, adjSeatNum + 1]
      nextSeats.forEach((nextSeatNum) => {
        if (nextSeatNum !== newSeatNum) {
          const nextSeatId = newRow + nextSeatNum
          if (selectedSeats.includes(nextSeatId)) {
            hasGap = true
          }
        }
      })
    }
  })

  return !hasGap
}

// Auto-select adjacent seats
function autoSelectAdjacentSeats(seatId) {
  const row = seatId.charAt(0)
  const seatNum = Number.parseInt(seatId.substring(1))

  // Find the best adjacent seat to fill the gap
  const adjacentOptions = [seatNum - 1, seatNum + 1]

  for (const adjNum of adjacentOptions) {
    const adjSeatId = row + adjNum
    const adjSeat = document.querySelector(`[data-seat="${adjSeatId}"]`)

    if (adjSeat && !adjSeat.classList.contains("bg-red-500") && !selectedSeats.includes(adjSeatId)) {
      if (selectedSeats.length < maxSeats - 1) {
        selectedSeats.push(adjSeatId)
        break
      }
    }
  }

  // Add the original seat
  selectedSeats.push(seatId)
  updateSeatSelection()
  updateSelectedSeatsDisplay()
}

// Toggle seat selection
function toggleSeat(seatId) {
  console.log("Toggling seat:", seatId)
  const seatIndex = selectedSeats.indexOf(seatId)

  if (seatIndex > -1) {
    // Deselect seat
    selectedSeats.splice(seatIndex, 1)
    console.log("Deselected seat:", seatId)
  } else {
    // Check if we're at max capacity
    if (selectedSeats.length >= maxSeats) {
      showError(`You can only select ${maxSeats} seat(s)`)
      return
    }

    // Validate seat gaps
    if (!validateSeatGaps(seatId)) {
      if (confirm("Please occupy the seat next to you. Would you like to automatically select adjacent seats?")) {
        autoSelectAdjacentSeats(seatId)
        return
      } else {
        showError("Please select seats without leaving single gaps")
        return
      }
    }

    // Select seat
    selectedSeats.push(seatId)
    console.log("Selected seat:", seatId)
  }

  updateSeatSelection()
  updateSelectedSeatsDisplay()
}

// Update selected seats display
function updateSelectedSeatsDisplay() {
  const display = document.getElementById("selectedSeatsDisplay")
  if (display) {
    display.textContent = selectedSeats.length > 0 ? selectedSeats.join(", ") : "None"
  }

  // Update hidden input
  const hiddenInput = document.getElementById("selected_seats")
  if (hiddenInput) {
    hiddenInput.value = selectedSeats.join(",")
  }
}

// Show error message
function showError(message) {
  const errorMessage = document.getElementById("errorMessage")
  if (errorMessage) {
    errorMessage.textContent = sanitizeInput(message)
    errorMessage.classList.remove("hidden")

    // Scroll to error message
    errorMessage.scrollIntoView({ behavior: "smooth", block: "center" })

    // Hide after 5 seconds
    setTimeout(() => {
      errorMessage.classList.add("hidden")
    }, 5000)
  }
}
