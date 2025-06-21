import { neon } from "@neondatabase/serverless"

// Check if we're in development and DATABASE_URL is not set
const isDevelopment = process.env.NODE_ENV === "development"
const databaseUrl = process.env.DATABASE_URL

if (!databaseUrl && !isDevelopment) {
  throw new Error("DATABASE_URL environment variable is required in production")
}

// Use a mock database URL for development if not provided
const connectionString = databaseUrl || "postgresql://mock:mock@localhost:5432/mock"

export const sql = neon(connectionString)

export interface Registration {
  id: number
  staff_name: string
  number_of_pax: number
  created_at: string
}

// Helper function to check if database is available
export async function isDatabaseAvailable(): Promise<boolean> {
  if (!databaseUrl) {
    return false
  }

  try {
    await sql`SELECT 1`
    return true
  } catch (error) {
    console.error("Database connection failed:", error)
    return false
  }
}
