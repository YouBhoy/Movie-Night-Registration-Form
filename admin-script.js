// Settings form handling
document.getElementById("settingsForm").addEventListener("submit", async (e) => {
  e.preventDefault()

  const formData = new FormData(e.target)
  const submitBtn = e.target.querySelector('button[type="submit"]')

  submitBtn.disabled = true
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...'

  try {
    const response = await fetch("update-settings.php", {
      method: "POST",
      body: formData,
    })

    const result = await response.json()

    if (result.success) {
      showNotification("Settings updated successfully!", "success")
    } else {
      showNotification(result.message || "Failed to update settings", "error")
    }
  } catch (error) {
    showNotification("An error occurred while updating settings", "error")
  } finally {
    submitBtn.disabled = false
    submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Settings'
  }
})

// Export to CSV functionality
function exportToCSV() {
  const table = document.getElementById("registrationsTable")
  if (!table) return

  let csv = "Staff Name,Number of Attendees,Shift,Selected Seats,Registration Date\n"

  const rows = table.querySelectorAll("tbody tr")
  rows.forEach((row) => {
    const cells = row.querySelectorAll("td")
    const staffName = cells[0].textContent.trim()
    const attendees = cells[1].textContent.trim().split(" ")[0]
    const shift = cells[2].textContent.trim()
    const seats = cells[3].textContent.trim()
    const date = cells[5].textContent.trim()

    csv += `"${staffName}",${attendees},"${shift}","${seats}","${date}"\n`
  })

  const blob = new Blob([csv], { type: "text/csv" })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement("a")
  a.href = url
  a.download = `wd-movie-night-registrations-${new Date().toISOString().split("T")[0]}.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

// Notification function
function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `fixed top-5 right-5 p-4 rounded-lg text-white font-medium z-50 transition-all duration-300 ${
    type === "success" ? "bg-green-500" : type === "error" ? "bg-red-500" : "bg-blue-500"
  }`
  notification.textContent = message

  document.body.appendChild(notification)

  // Animate in
  setTimeout(() => {
    notification.style.transform = "translateX(0)"
    notification.style.opacity = "1"
  }, 100)

  // Remove after 5 seconds
  setTimeout(() => {
    notification.style.transform = "translateX(100%)"
    notification.style.opacity = "0"
    setTimeout(() => {
      notification.remove()
    }, 300)
  }, 5000)
}

// Search functionality
document.getElementById("searchInput").addEventListener("input", function () {
  const searchTerm = this.value.toLowerCase()
  const tableRows = document.querySelectorAll("#registrationsTable tbody tr")

  tableRows.forEach((row) => {
    const staffName = row.querySelector(".staff-name").textContent.toLowerCase()
    if (staffName.includes(searchTerm)) {
      row.style.display = ""
    } else {
      row.style.display = "none"
    }
  })
})

// Delete registration function
async function deleteRegistration(registrationId, staffName) {
  if (
    !confirm(
      `Are you sure you want to delete the registration for "${staffName}"? This will also free up their selected seats.`,
    )
  ) {
    return
  }

  try {
    const formData = new FormData()
    formData.append("registration_id", registrationId)

    const response = await fetch("delete-registration.php", {
      method: "POST",
      body: formData,
    })

    const result = await response.json()

    if (result.success) {
      // Remove the row from the table
      const row = document.querySelector(`tr[data-registration-id="${registrationId}"]`)
      if (row) {
        row.remove()
      }

      showNotification("Registration deleted successfully!", "success")

      // Reload page to update stats
      setTimeout(() => {
        window.location.reload()
      }, 1500)
    } else {
      showNotification(result.message || "Failed to delete registration", "error")
    }
  } catch (error) {
    showNotification("An error occurred while deleting the registration", "error")
  }
}
