import { describe, expect, it } from 'vitest'
import { resolveStageMoveAction } from './stageMove'

describe('resolveStageMoveAction', () => {
  it('does nothing when dropped back on the same column', () => {
    expect(resolveStageMoveAction('PROPOSAL', 'PROPOSAL')).toEqual({ type: 'noop' })
  })

  it('blocks any move once the deal is already closed', () => {
    expect(resolveStageMoveAction('WON', 'PROPOSAL')).toEqual({
      type: 'blocked',
      reason: 'A closed deal cannot change stage.',
    })
    expect(resolveStageMoveAction('LOST', 'NEGOTIATION')).toMatchObject({ type: 'blocked' })
  })

  it('requires a reason when dragged to Lost', () => {
    expect(resolveStageMoveAction('PROSPECTING', 'LOST')).toEqual({
      type: 'confirm',
      stage: 'LOST',
      requireReason: true,
    })
  })

  it('confirms without a reason when dragged to Won', () => {
    expect(resolveStageMoveAction('NEGOTIATION', 'WON')).toEqual({
      type: 'confirm',
      stage: 'WON',
      requireReason: false,
    })
  })

  it('applies the move directly between open stages', () => {
    expect(resolveStageMoveAction('PROSPECTING', 'PROPOSAL')).toEqual({ type: 'apply', stage: 'PROPOSAL' })
  })
})
