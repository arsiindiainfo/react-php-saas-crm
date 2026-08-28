// Access/refresh tokens live in localStorage — acceptable for this portfolio
// scope (no XSS-sensitive third-party scripts run on this SPA). Centralized
// here so apiClient and the auth feature share one source of truth.

const ACCESS_KEY = 'crm.accessToken'
const REFRESH_KEY = 'crm.refreshToken'

export const tokenStore = {
  getAccessToken: () => localStorage.getItem(ACCESS_KEY),
  getRefreshToken: () => localStorage.getItem(REFRESH_KEY),
  setTokens(accessToken: string, refreshToken: string) {
    localStorage.setItem(ACCESS_KEY, accessToken)
    localStorage.setItem(REFRESH_KEY, refreshToken)
  },
  clear() {
    localStorage.removeItem(ACCESS_KEY)
    localStorage.removeItem(REFRESH_KEY)
  },
}
