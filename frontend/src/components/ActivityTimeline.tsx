import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import { useToast } from './Toast'
import { Spinner } from './Spinner'
import type { Activity } from '@/types/entities'
import type { ApiSuccess } from '@/types/api'
import type { RelatableType, ActivityType } from '@shared/constants'
import { ACTIVITY_TYPES } from '@shared/constants'

interface ActivityTimelineProps {
  relatedToType: RelatableType
  relatedToId: number
  /** Company Detail's "Notes" tab reuses this component filtered to NOTE (§22.3/§22.8). */
  typeFilter?: ActivityType
}

function groupByDay(activities: Activity[]): [string, Activity[]][] {
  const groups = new Map<string, Activity[]>()
  for (const activity of activities) {
    const day = activity.occurredAt.slice(0, 10)
    groups.set(day, [...(groups.get(day) ?? []), activity])
  }
  return [...groups.entries()]
}

export function ActivityTimeline({ relatedToType, relatedToId, typeFilter }: ActivityTimelineProps) {
  const { notify } = useToast()
  const queryClient = useQueryClient()
  const [isLogging, setIsLogging] = useState(false)
  const [type, setType] = useState<ActivityType>(typeFilter ?? 'NOTE')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')

  const queryKey = ['activities', relatedToType, relatedToId, typeFilter]

  const { data, isLoading } = useQuery({
    queryKey,
    queryFn: async () => {
      const params: Record<string, string | number> = { relatedToType, relatedToId }
      if (typeFilter) params.type = typeFilter
      const res = await apiClient.get<ApiSuccess<Activity[]>>('/activities', { params })
      return res.data.data
    },
  })

  const logMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post('/activities', { type, subject: subject || undefined, body, relatedToType, relatedToId })
    },
    onSuccess: () => {
      notify(typeFilter === 'NOTE' ? 'Note added' : 'Activity logged')
      setBody('')
      setSubject('')
      setIsLogging(false)
      void queryClient.invalidateQueries({ queryKey })
    },
    onError: () => notify('Could not log this activity.', 'error'),
  })

  const activities = data ?? []
  const days = groupByDay(activities)

  return (
    <div>
      <div className="mb-3 flex items-center justify-between">
        <h3 className="text-sm font-semibold text-gray-700">{typeFilter === 'NOTE' ? 'Notes' : 'Activity'}</h3>
        <button
          type="button"
          onClick={() => setIsLogging((v) => !v)}
          className="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
        >
          {typeFilter === 'NOTE' ? '+ Note' : '+ Log activity'}
        </button>
      </div>

      {isLogging && (
        <form
          onSubmit={(e) => {
            e.preventDefault()
            logMutation.mutate()
          }}
          className="mb-4 space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3"
        >
          {!typeFilter && (
            <select
              value={type}
              onChange={(e) => setType(e.target.value as ActivityType)}
              className="rounded-md border border-gray-300 px-2 py-1 text-sm"
            >
              {ACTIVITY_TYPES.map((t) => (
                <option key={t} value={t}>
                  {t}
                </option>
              ))}
            </select>
          )}
          <input
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            placeholder="Subject (optional)"
            className="w-full rounded-md border border-gray-300 px-2 py-1 text-sm"
          />
          <textarea
            value={body}
            onChange={(e) => setBody(e.target.value)}
            required
            rows={3}
            placeholder="What happened?"
            className="w-full rounded-md border border-gray-300 px-2 py-1 text-sm"
          />
          <button
            type="submit"
            disabled={logMutation.isPending || body.trim() === ''}
            className="rounded-md bg-gray-900 px-3 py-1 text-xs font-semibold text-white disabled:opacity-50"
          >
            Save
          </button>
        </form>
      )}

      {isLoading && <Spinner className="py-3" />}

      {!isLoading && activities.length === 0 && (
        <p className="rounded-md border border-dashed border-gray-300 p-4 text-center text-sm text-gray-400">
          Nothing logged yet.
        </p>
      )}

      <div className="space-y-4">
        {days.map(([day, items]) => (
          <div key={day}>
            <div className="mb-1 text-xs font-semibold text-gray-400">{day}</div>
            <ul className="space-y-2">
              {items.map((activity) => (
                <li key={activity.id} className="rounded-md border border-gray-100 bg-white p-2 text-sm">
                  <div className="flex items-center gap-2">
                    <span className="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-gray-500">
                      {activity.type}
                    </span>
                    {activity.subject && <span className="font-medium text-gray-800">{activity.subject}</span>}
                  </div>
                  <p className="mt-1 text-gray-600">{activity.body}</p>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
    </div>
  )
}
