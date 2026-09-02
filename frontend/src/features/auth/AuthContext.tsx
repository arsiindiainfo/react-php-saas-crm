import { createContext, use, useCallback, useEffect, useState, type ReactNode } from 'react'
import { apiClient } from '@/lib/apiClient'
import { tokenStore } from '@/lib/tokenStore'
import type { User } from '@/types/entities'

interface AuthContextValue {
  user: User | null
  isLoading: boolean
  login: (email: string, password: string, recaptchaToken: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    if (!tokenStore.getAccessToken()) {
      setIsLoading(false)
      return
    }

    apiClient
      .get<{ data: User }>('/users/me')
      .then((res) => setUser(res.data.data))
      .catch(() => tokenStore.clear())
      .finally(() => setIsLoading(false))
  }, [])

  const login = useCallback(async (email: string, password: string, recaptchaToken: string) => {
    const res = await apiClient.post('/auth/login', { email, password, recaptchaToken })
    const { accessToken, refreshToken, user: loggedInUser } = res.data.data
    tokenStore.setTokens(accessToken, refreshToken)
    setUser(loggedInUser)
  }, [])

  const logout = useCallback(async () => {
    const refreshToken = tokenStore.getRefreshToken()
    if (refreshToken) {
      await apiClient.post('/auth/logout', { refreshToken }).catch(() => undefined)
    }
    tokenStore.clear()
    setUser(null)
  }, [])

  return <AuthContext value={{ user, isLoading, login, logout }}>{children}</AuthContext>
}

export function useAuth(): AuthContextValue {
  const ctx = use(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return ctx
}
