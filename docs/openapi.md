# API Documentation (OpenAPI)

The full OpenAPI 3.0 contract is generated from PHP attributes on the
Controllers (§26) — not hand-maintained — so it can't drift from what's
actually implemented. Every `requestBody` schema mirrors the CI4
[`Validation` rule group](../backend/app/Config/Validation.php) enforced in
that same method, and every documented error response comes straight from
the [error catalog](#error-catalog-reference) (§14).

## Viewing it

With the backend running (`php spark serve`) in a **non-production**
environment (`CI_ENVIRONMENT=development` or `testing` — the default in
`.env.example`):

```
GET /api/docs
```

returns the generated spec as JSON. Paste that URL into
[Swagger Editor](https://editor.swagger.io) or any OpenAPI viewer to browse
it interactively. In production (`CI_ENVIRONMENT=production`) the route
returns a plain `404` — the spec isn't exposed publicly.

## How it's generated

- `App\OpenApi\Base` (`backend/app/OpenApi/Base.php`) declares the
  document-level metadata: `info`, the `/api/v1` server, the `bearerAuth`
  security scheme, and two shared component schemas (`ErrorEnvelope`,
  `PaginationMeta`) referenced by every endpoint.
- Every Controller in `backend/app/Controllers/` carries one PHP attribute
  per action (`#[OA\Get(...)]`, `#[OA\Post(...)]`, etc.) directly above the
  method it documents — 40 operations across 26 paths, one for every route
  in `Config\Routes.php`.
- `App\Controllers\OpenApiController::index()` runs
  `zircote/swagger-php`'s `Generator` over `app/Controllers` and
  `app/OpenApi` on every request and returns the result — always fresh,
  never a stale checked-in file.

## Error code reference

See §14 of the implementation plan for the full catalog; the same codes
appear verbatim in the generated spec's per-endpoint response descriptions
(`VALIDATION_ERROR`, `UNAUTHORIZED`, `FORBIDDEN_ROLE`, `*_NOT_FOUND`,
`DUPLICATE_NAME`, `ALREADY_CONVERTED`, `NOT_QUALIFIED`,
`INVALID_TRANSITION`, `LOST_REASON_REQUIRED`, `RATE_LIMITED`,
`RECAPTCHA_REQUIRED`, `RECAPTCHA_FAILED`, `RECAPTCHA_UNAVAILABLE`,
`INTERNAL_ERROR`).
