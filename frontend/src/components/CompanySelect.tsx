import { useState } from 'react'
import { useListQuery } from '@/lib/useListQuery'
import type { Company } from '@/types/entities'

interface CompanySelectProps {
  id?: string
  value: number | null
  onChange: (companyId: number | null, company: Company | null) => void
  placeholder?: string
}

/** Searchable "company" select reused by Contacts, the Lead convert dialog, and Tasks' relatedTo (§22.4/§22.5/§22.7). */
export function CompanySelect({ id, value, onChange, placeholder = 'Search companies…' }: CompanySelectProps) {
  const [query, setQuery] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  const { data } = useListQuery<Company>('companies', { search: query, limit: 8 })
  const selected = value ? data?.data.find((c) => c.id === value) : null

  return (
    <div className="relative">
      <input
        id={id}
        value={isOpen ? query : (selected?.name ?? query)}
        onChange={(e) => {
          setQuery(e.target.value)
          setIsOpen(true)
        }}
        onFocus={() => setIsOpen(true)}
        onBlur={() => setTimeout(() => setIsOpen(false), 150)}
        placeholder={placeholder}
        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
      />
      {isOpen && data && data.data.length > 0 && (
        <ul className="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
          {data.data.map((company) => (
            <li key={company.id}>
              <button
                type="button"
                onClick={() => {
                  onChange(company.id, company)
                  setQuery(company.name)
                  setIsOpen(false)
                }}
                className="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50"
              >
                {company.name}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
