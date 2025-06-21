"use server"

import { sql, isDatabaseAvailable } from "@/lib/db"
import { redirect } from "next/navigation"
import { z } from "zod"

const registrationSchema = z.object({
  staff_name: z.string().min(1, "Staff name is required").max(255, "Name is too long"),
  number_of_pax: z.coerce.number().min(1, "At least 1 person required").max(4, "Maximum 4 people allowed"),
})

// In-memory storage for development when database is not available
const mockRegistrations: Array<{
  id: number
  staff_name: string
  number_of_pax: number
  created_at: string
}> = []

let nextId = 1

export async function submitRegistration(formData: FormData) {
  try {
    const validatedFields = registrationSchema.safeParse({
      staff_name: formData.get("staff_name"),
      number_of_pax: formData.get("number_of_pax"),
    })

    if (!validatedFields.success) {
      return {
        error: validatedFields.error.errors[0].message,
      }
    }

    const { staff_name, number_of_pax } = validatedFields.data

    // Check if database is available
    const dbAvailable = await isDatabaseAvailable()

    if (dbAvailable) {
      // Insert registration into database
      await sql`
        INSERT INTO registrations (staff_name, number_of_pax)
        VALUES (${staff_name}, ${number_of_pax})
      `
    } else {
      // Use mock storage for development
      mockRegistrations.push({
        id: nextId++,
        staff_name,
        number_of_pax,
        created_at: new Date().toISOString(),
      })
      console.log("Registration saved to mock storage:", { staff_name, number_of_pax })
    }

    // Redirect to confirmation page
    redirect("/confirmation")
  } catch (error) {
    console.error("Registration error:", error)
    return {
      error: "Failed to submit registration. Please try again.",
    }
  }
}

export async function getAllRegistrations() {
  try {
    const dbAvailable = await isDatabaseAvailable()

    if (dbAvailable) {
      const registrations = await sql`
        SELECT id, staff_name, number_of_pax, created_at
        FROM registrations
        ORDER BY created_at DESC
      `
      return registrations
    } else {
      // Return mock data for development
      return [...mockRegistrations].reverse()
    }
  } catch (error) {
    console.error("Error fetching registrations:", error)
    return mockRegistrations
  }
}

export async function getRegistrationStats() {
  try {
    const dbAvailable = await isDatabaseAvailable()

    if (dbAvailable) {
      const stats = await sql`
        SELECT 
          COUNT(*) as total_registrations,
          SUM(number_of_pax) as total_attendees
        FROM registrations
      `
      return stats[0]
    } else {
      // Calculate stats from mock data
      const totalRegistrations = mockRegistrations.length
      const totalAttendees = mockRegistrations.reduce((sum, reg) => sum + reg.number_of_pax, 0)
      return {
        total_registrations: totalRegistrations,
        total_attendees: totalAttendees,
      }
    }
  } catch (error) {
    console.error("Error fetching stats:", error)
    return { total_registrations: 0, total_attendees: 0 }
  }
}
