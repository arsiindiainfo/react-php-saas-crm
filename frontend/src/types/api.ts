// Mirrors the backend §13 response envelope.

export interface ApiSuccess<T> {
  success: true
  data: T
}

export interface ApiPaginated<T> {
  success: true
  data: T[]
  meta: { page: number; limit: number; total: number; totalPages: number }
}

export interface ApiErrorBody {
  success: false
  error: { code: string; message: string; fields?: Record<string, string> }
}

export class ApiError extends Error {
  code: string
  fields?: Record<string, string>

  constructor(body: ApiErrorBody['error']) {
    super(body.message)
    this.code = body.code
    this.fields = body.fields
  }
}
