import { CLOSED_DEAL_STAGES, type DealStage } from '@shared/constants'

export type StageMoveAction =
  | { type: 'noop' }
  | { type: 'blocked'; reason: string }
  | { type: 'confirm'; stage: 'WON' | 'LOST'; requireReason: boolean }
  | { type: 'apply'; stage: DealStage }

/**
 * Pure decision logic behind the Deals board's drag handler (§22.6) —
 * extracted so the "drag-to-Lost requires a reason" / "drag-to-Won confirms"
 * rules are unit-testable without simulating a real @dnd-kit drag gesture.
 */
export function resolveStageMoveAction(currentStage: DealStage, targetColumn: string): StageMoveAction {
  const targetStage = targetColumn as DealStage

  if (targetStage === currentStage) {
    return { type: 'noop' }
  }

  if (CLOSED_DEAL_STAGES.includes(currentStage)) {
    return { type: 'blocked', reason: 'A closed deal cannot change stage.' }
  }

  if (targetStage === 'LOST') {
    return { type: 'confirm', stage: 'LOST', requireReason: true }
  }

  if (targetStage === 'WON') {
    return { type: 'confirm', stage: 'WON', requireReason: false }
  }

  return { type: 'apply', stage: targetStage }
}
