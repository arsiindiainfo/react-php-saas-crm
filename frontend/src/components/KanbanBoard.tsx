import { DndContext, useDraggable, useDroppable, type DragEndEvent } from '@dnd-kit/core'
import type { ReactNode } from 'react'

export interface KanbanColumn {
  key: string
  title: string
  headerRight?: ReactNode
  /** Tailwind classes for the column's tinted header pill, e.g. "bg-violet-50 text-violet-700". */
  headerClassName?: string
  /** Tailwind class for the small status dot next to the title, e.g. "bg-violet-500". */
  dotClassName?: string
}

interface KanbanBoardProps<T> {
  columns: KanbanColumn[]
  itemsByColumn: Record<string, T[]>
  cardKey: (item: T) => string | number
  renderCard: (item: T) => ReactNode
  onCardMoved: (item: T, toColumn: string) => void
  findItem: (id: string | number) => T | undefined
}

function DroppableColumn({ column, children }: { column: KanbanColumn; children: ReactNode }) {
  const { setNodeRef, isOver } = useDroppable({ id: column.key })

  return (
    <div
      ref={setNodeRef}
      className={`flex min-h-[200px] w-72 shrink-0 flex-col rounded-lg border bg-gray-50/50 p-2 transition-colors ${
        isOver ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200'
      }`}
    >
      <div className={`mb-2 flex items-center justify-between rounded-md px-2.5 py-2 ${column.headerClassName ?? 'bg-gray-100 text-gray-600'}`}>
        <h3 className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide">
          <span className={`h-2 w-2 shrink-0 rounded-full ${column.dotClassName ?? 'bg-gray-400'}`} />
          {column.title}
        </h3>
        {column.headerRight}
      </div>
      <div className="flex flex-1 flex-col gap-2">{children}</div>
    </div>
  )
}

function DraggableCard({ id, children }: { id: string | number; children: ReactNode }) {
  const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({ id })

  return (
    <div
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      style={{
        transform: transform ? `translate3d(${transform.x}px, ${transform.y}px, 0)` : undefined,
        opacity: isDragging ? 0.5 : 1,
      }}
      className="cursor-grab rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
    >
      {children}
    </div>
  )
}

/** Backs the Leads (§22.5) and Deals (§22.6) pipeline boards. */
export function KanbanBoard<T>({ columns, itemsByColumn, cardKey, renderCard, onCardMoved, findItem }: KanbanBoardProps<T>) {
  function handleDragEnd(event: DragEndEvent) {
    const { active, over } = event
    if (!over) return

    const item = findItem(active.id)
    if (item) {
      onCardMoved(item, String(over.id))
    }
  }

  return (
    <DndContext onDragEnd={handleDragEnd}>
      <div className="flex gap-3 overflow-x-auto pb-4">
        {columns.map((column) => (
          <DroppableColumn key={column.key} column={column}>
            {(itemsByColumn[column.key] ?? []).map((item) => (
              <DraggableCard key={cardKey(item)} id={cardKey(item)}>
                {renderCard(item)}
              </DraggableCard>
            ))}
          </DroppableColumn>
        ))}
      </div>
    </DndContext>
  )
}
