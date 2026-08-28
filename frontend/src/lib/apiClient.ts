import axios, { AxiosError, type AxiosRequestConfig } from 'axios'
import { ApiError, type ApiErrorBody } from '@/types/api'
import { tokenStore } from './tokenStore'

export const apiClient = axios.create({
  baseURL: '/api/v1',
  headers: { 'Content-Type': 'application/json' },
})

apiClient.interceptors.request.use((config) => {
  const token = tokenStore.getAccessToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

let refreshPromise: Promise<string> | null = null

async function refreshAccessToken(): Promise<string> {
  const refreshToken = tokenStore.getRefreshToken()
  if (!refreshToken) {
    throw new Error('No refresh token available.')
  }

  const response = await axios.post('/api/v1/auth/refresh', { refreshToken })
  const { accessToken, refreshToken: newRefreshToken } = response.data.data
  tokenStore.setTokens(accessToken, newRefreshToken)
  return accessToken
}

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiErrorBody>) => {
    const original = error.config as (AxiosRequestConfig & { _retried?: boolean }) | undefined

    const isAuthRoute = original?.url?.includes('/auth/login') || original?.url?.includes('/auth/refresh')

    if (error.response?.status === 401 && original && !original._retried && !isAuthRoute) {
      original._retried = true
      try {
        refreshPromise ??= refreshAccessToken()
        const accessToken = await refreshPromise
        refreshPromise = null
        original.headers = { ...original.headers, Authorization: `Bearer ${accessToken}` }
        return apiClient.request(original)
      } catch {
        refreshPromise = null
        tokenStore.clear()
        window.location.assign('/login')
        return Promise.reject(error)
      }
    }

    if (error.response?.data?.error) {
      return Promise.reject(new ApiError(error.response.data.error))
    }

    return Promise.reject(error)
  },
)
