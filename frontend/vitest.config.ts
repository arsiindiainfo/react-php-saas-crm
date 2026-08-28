import { defineConfig, mergeConfig } from 'vitest/config'
import viteConfig from './vite.config.ts'

export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      globals: true,
      setupFiles: ['./src/test/setup.ts'],
      css: true,
      // This dev machine can't reliably spawn multiple worker forks at once
      // (observed as spurious "Timeout waiting for worker to respond"
      // failures) — run test files sequentially instead.
      fileParallelism: false,
    },
  }),
)
