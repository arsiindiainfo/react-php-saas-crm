import { useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import ReCAPTCHA from 'react-google-recaptcha'
import { useAuth } from './AuthContext'
import { ApiError } from '@/types/api'
import arsiLogo from '@/assets/arsi-logo.png'

const RECAPTCHA_SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY as string | undefined

const schema = z.object({
  email: z.email('Enter a valid email address.'),
  password: z.string().min(1, 'Password is required.'),
})
type FormValues = z.infer<typeof schema>

const DEMO_PASSWORD = 'Passw0rd!'
const DEMO_ACCOUNTS = [
  { role: 'Admin', email: 'admin@brightfield.test' },
  { role: 'Sales Manager', email: 'priya.manager@brightfield.test' },
  { role: 'Sales Manager', email: 'rohan.manager@brightfield.test' },
  { role: 'Sales Rep', email: 'arjun.rep@brightfield.test' },
  { role: 'Sales Rep', email: 'meera.rep@brightfield.test' },
  { role: 'Sales Rep', email: 'karan.rep@brightfield.test' },
  { role: 'Sales Rep', email: 'sana.rep@brightfield.test' },
]

export function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [serverError, setServerError] = useState<string | null>(null)
  const [recaptchaToken, setRecaptchaToken] = useState<string | null>(null)
  const recaptchaRef = useRef<ReCAPTCHA>(null)

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  function fillDemoAccount(email: string) {
    setValue('email', email, { shouldValidate: true })
    setValue('password', DEMO_PASSWORD, { shouldValidate: true })
  }

  async function onSubmit(values: FormValues) {
    setServerError(null)
    try {
      await login(values.email, values.password, recaptchaToken ?? '')
      navigate('/dashboard', { replace: true })
    } catch (err) {
      setServerError(err instanceof ApiError ? err.message : 'Something went wrong. Please try again.')
      recaptchaRef.current?.reset()
      setRecaptchaToken(null)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-10">
      <div className="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <div className="flex flex-col items-center text-center">
          <img src={arsiLogo} alt="Arsi India Info" className="mb-4 h-8 w-auto" />
          <h1 className="mb-1 text-xl font-bold text-gray-900">Arsi CRM</h1>
          <p className="mb-6 text-sm text-gray-500">Sign in to Brightfield Business Solutions</p>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700" htmlFor="email">
              Email
            </label>
            <input
              id="email"
              type="email"
              autoComplete="username"
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
              {...register('email')}
            />
            {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email.message}</p>}
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700" htmlFor="password">
              Password
            </label>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
              {...register('password')}
            />
            {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password.message}</p>}
          </div>

          {RECAPTCHA_SITE_KEY && (
            <div className="flex justify-center">
              <ReCAPTCHA
                ref={recaptchaRef}
                sitekey={RECAPTCHA_SITE_KEY}
                onChange={(token) => setRecaptchaToken(token)}
                onExpired={() => setRecaptchaToken(null)}
              />
            </div>
          )}

          {serverError && (
            <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{serverError}</div>
          )}

          <button
            type="submit"
            disabled={isSubmitting || (!!RECAPTCHA_SITE_KEY && !recaptchaToken)}
            className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60"
          >
            {isSubmitting ? 'Signing in…' : 'Sign in'}
          </button>
        </form>

        <div className="mt-6 border-t border-gray-100 pt-4">
          <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
            Demo project — sign in as
          </p>
          <ul className="space-y-1">
            {DEMO_ACCOUNTS.map((account) => (
              <li key={account.email}>
                <button
                  type="button"
                  onClick={() => fillDemoAccount(account.email)}
                  className="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-xs hover:bg-gray-50"
                >
                  <span className="text-gray-500">{account.role}</span>
                  <span className="font-medium text-indigo-600">{account.email}</span>
                </button>
              </li>
            ))}
          </ul>
          <p className="mt-2 text-center text-xs text-gray-400">
            Password for every account: <span className="font-mono">{DEMO_PASSWORD}</span>
          </p>
        </div>
      </div>
    </div>
  )
}
