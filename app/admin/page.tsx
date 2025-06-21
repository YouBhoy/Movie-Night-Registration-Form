"use client"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Users, Download, UserCheck } from "lucide-react"
import { getAllRegistrations, getRegistrationStats } from "../actions/registration"

export default async function AdminPage() {
  const registrations = await getAllRegistrations()
  const stats = await getRegistrationStats()

  const exportToCSV = () => {
    const headers = ["Staff Name", "Number of Attendees", "Registration Date"]
    const csvContent = [
      headers.join(","),
      ...registrations.map((reg) =>
        [`"${reg.staff_name}"`, reg.number_of_pax, new Date(reg.created_at).toLocaleDateString()].join(","),
      ),
    ].join("\n")

    const blob = new Blob([csvContent], { type: "text/csv" })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = `movie-night-registrations-${new Date().toISOString().split("T")[0]}.csv`
    a.click()
    window.URL.revokeObjectURL(url)
  }

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Movie Night Admin Dashboard</h1>
          <p className="text-gray-600">Manage and view all movie night registrations</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Registrations</CardTitle>
              <UserCheck className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.total_registrations || 0}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Attendees</CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.total_attendees || 0}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Export Data</CardTitle>
              <Download className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <Button onClick={exportToCSV} variant="outline" size="sm" className="w-full">
                Download CSV
              </Button>
            </CardContent>
          </Card>
        </div>

        {/* Registrations Table */}
        <Card>
          <CardHeader>
            <CardTitle>All Registrations</CardTitle>
            <CardDescription>Complete list of staff who have registered for the movie night</CardDescription>
          </CardHeader>
          <CardContent>
            {registrations.length === 0 ? (
              <div className="text-center py-8">
                <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-500">No registrations yet</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-3 px-4 font-medium">Staff Name</th>
                      <th className="text-left py-3 px-4 font-medium">Attendees</th>
                      <th className="text-left py-3 px-4 font-medium">Registration Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {registrations.map((registration) => (
                      <tr key={registration.id} className="border-b hover:bg-gray-50">
                        <td className="py-3 px-4 font-medium">{registration.staff_name}</td>
                        <td className="py-3 px-4">
                          <Badge variant="secondary">
                            {registration.number_of_pax} {registration.number_of_pax === 1 ? "person" : "people"}
                          </Badge>
                        </td>
                        <td className="py-3 px-4 text-gray-600">
                          {new Date(registration.created_at).toLocaleDateString("en-US", {
                            year: "numeric",
                            month: "short",
                            day: "numeric",
                            hour: "2-digit",
                            minute: "2-digit",
                          })}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
