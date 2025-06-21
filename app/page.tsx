"use client"

import { useState } from "react"
import { useActionState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Film, Users, Calendar } from "lucide-react"
import { submitRegistration } from "./actions/registration"
import { isDatabaseAvailable } from "@/lib/db"

// Add this right after the existing imports and before the component
async function DatabaseStatus() {
  const dbAvailable = await isDatabaseAvailable()

  if (!dbAvailable) {
    return (
      <div className="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-6">
        <p className="text-yellow-800 text-sm">
          <strong>Development Mode:</strong> Database not connected. Registrations will be stored temporarily for
          testing.
        </p>
      </div>
    )
  }

  return null
}

const initialState = {
  error: null,
}

// Then update the main component to include the DatabaseStatus
export default function RegistrationPage() {
  const [state, formAction, pending] = useActionState(submitRegistration, initialState)
  const [paxCount, setPaxCount] = useState("1")

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 to-blue-50 p-4">
      <div className="max-w-2xl mx-auto">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="flex justify-center mb-4">
            <div className="bg-purple-100 p-3 rounded-full">
              <Film className="h-8 w-8 text-purple-600" />
            </div>
          </div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">WD Movie Night Registration</h1>
          <p className="text-gray-600">Register for our upcoming movie night event</p>
        </div>

        {/* Add Database Status */}
        <DatabaseStatus />

        {/* Rest of the component remains the same */}
        {/* Event Details Card */}
        <Card className="mb-6 border-purple-200">
          <CardContent className="pt-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="flex items-center gap-3">
                <Calendar className="h-5 w-5 text-purple-600" />
                <div>
                  <p className="font-medium">Date & Time</p>
                  <p className="text-sm text-gray-600">Coming Soon</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Users className="h-5 w-5 text-purple-600" />
                <div>
                  <p className="font-medium">Max Attendees</p>
                  <p className="text-sm text-gray-600">4 people per registration</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Registration Form */}
        <Card>
          <CardHeader>
            <CardTitle>Registration Form</CardTitle>
            <CardDescription>Please fill in your details to register for the movie night</CardDescription>
          </CardHeader>
          <CardContent>
            <form action={formAction} className="space-y-6">
              {/* Staff Name */}
              <div className="space-y-2">
                <Label htmlFor="staff_name">Staff Name *</Label>
                <Input
                  id="staff_name"
                  name="staff_name"
                  type="text"
                  placeholder="Enter your full name"
                  required
                  className="w-full"
                />
              </div>

              {/* Number of Pax */}
              <div className="space-y-2">
                <Label htmlFor="number_of_pax">Number of Attendees (Including Yourself) *</Label>
                <Select name="number_of_pax" value={paxCount} onValueChange={setPaxCount} required>
                  <SelectTrigger>
                    <SelectValue placeholder="Select number of attendees" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">1 person</SelectItem>
                    <SelectItem value="2">2 people</SelectItem>
                    <SelectItem value="3">3 people</SelectItem>
                    <SelectItem value="4">4 people</SelectItem>
                  </SelectContent>
                </Select>
                <p className="text-sm text-gray-500">Maximum 4 people per registration</p>
              </div>

              {/* Error Message */}
              {state?.error && (
                <div className="bg-red-50 border border-red-200 rounded-md p-3">
                  <p className="text-red-600 text-sm">{state.error}</p>
                </div>
              )}

              {/* Submit Button */}
              <Button type="submit" className="w-full bg-purple-600 hover:bg-purple-700" disabled={pending}>
                {pending ? "Submitting..." : "Register for Movie Night"}
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Footer */}
        <div className="text-center mt-8 text-sm text-gray-500">
          <p>Questions? Contact the WD team for assistance.</p>
        </div>
      </div>
    </div>
  )
}
