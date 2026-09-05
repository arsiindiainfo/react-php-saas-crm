import { Loader2 } from 'lucide-react'

interface SpinnerProps {
  label?: string
  size?: number
  className?: string
}

export function Spinner({ label = 'Loading…', size = 20, className = '' }: SpinnerProps) {
  return (
    <div className={`flex items-center gap-2 text-sm text-gray-400 ${className}`}>
      <Loader2 size={size} className="animate-spin text-indigo-500" />
      {label && <span>{label}</span>}
    </div>
  )
}

/** Full-height centered spinner for a page/section that's still loading its data. */
export function PageSpinner({ label }: { label?: string }) {
  return (
    <div className="flex min-h-[200px] items-center justify-center">
      <Spinner label={label} size={24} />
    </div>
  )
}
