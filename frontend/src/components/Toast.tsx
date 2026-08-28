import { createContext, use, useCallback, useState, type ReactNode } from 'react'

interface Toast {
  id: number
  message: string
  variant: 'success' | 'error'
}

interface ToastContextValue {
  notify: (message: string, variant?: Toast['variant']) => void
}

const ToastContext = createContext<ToastContextValue | null>(null)

let nextId = 1

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  const notify = useCallback((message: string, variant: Toast['variant'] = 'success') => {
    const id = nextId++
    setToasts((prev) => [...prev, { id, message, variant }])
    setTimeout(() => setToasts((prev) => prev.filter((t) => t.id !== id)), 4000)
  }, [])

  return (
    <ToastContext value={{ notify }}>
      {children}
      <div className="fixed bottom-4 right-4 z-50 flex flex-col gap-2">
        {toasts.map((t) => (
          <div
            key={t.id}
            className={
              'rounded-md px-4 py-2 text-sm shadow-lg text-white ' +
              (t.variant === 'success' ? 'bg-gray-900' : 'bg-red-600')
            }
          >
            {t.message}
          </div>
        ))}
      </div>
    </ToastContext>
  )
}

export function useToast(): ToastContextValue {
  const ctx = use(ToastContext)
  if (!ctx) {
    throw new Error('useToast must be used within ToastProvider')
  }
  return ctx
}
