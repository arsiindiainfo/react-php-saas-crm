import { Link } from 'react-router-dom'

export function NotFoundPage() {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
      <h1 className="text-2xl font-bold text-gray-900">Not found</h1>
      <p className="mt-2 text-sm text-gray-500">This record doesn't exist, or you don't have access to it.</p>
      <Link to="/dashboard" className="mt-4 text-sm text-indigo-600">
        Back to Dashboard
      </Link>
    </div>
  )
}
