import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { CheckCircle, Film, ArrowLeft } from "lucide-react"
import Link from "next/link"

export default function ConfirmationPage() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 p-4">
      <div className="max-w-2xl mx-auto">
        <div className="text-center mb-8 pt-16">
          <div className="flex justify-center mb-6">
            <div className="bg-green-100 p-4 rounded-full">
              <CheckCircle className="h-12 w-12 text-green-600" />
            </div>
          </div>

          <h1 className="text-3xl font-bold text-gray-900 mb-4">Registration Successful!</h1>
          <p className="text-gray-600 text-lg mb-8">Thank you for registering for the WD Movie Night</p>
        </div>

        <Card className="border-green-200">
          <CardHeader className="text-center">
            <CardTitle className="flex items-center justify-center gap-2">
              <Film className="h-5 w-5 text-purple-600" />
              What's Next?
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="bg-blue-50 p-4 rounded-lg">
              <h3 className="font-medium text-blue-900 mb-2">Event Details</h3>
              <p className="text-blue-700 text-sm">
                You'll receive event details including the movie selection, venue, and timing closer to the date.
              </p>
            </div>

            <div className="bg-purple-50 p-4 rounded-lg">
              <h3 className="font-medium text-purple-900 mb-2">Need to Make Changes?</h3>
              <p className="text-purple-700 text-sm">
                Contact the WD team if you need to update your registration or cancel your attendance.
              </p>
            </div>

            <div className="pt-4">
              <Link href="/">
                <Button variant="outline" className="w-full">
                  <ArrowLeft className="h-4 w-4 mr-2" />
                  Back to Registration Form
                </Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
