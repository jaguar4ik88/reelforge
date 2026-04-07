/** Visual width grows left→right like the product mock (9:16 … 16:9). Labels are universal (9:16, 3:4, …). */
const OPTIONS = [
  { value: '9:16', wClass: 'min-w-[2.5rem] max-w-[2.75rem]' },
  { value: '3:4', wClass: 'min-w-[2.75rem] max-w-[3.25rem]' },
  { value: '1:1', wClass: 'min-w-[3rem] max-w-[3.25rem]' },
  { value: '4:3', wClass: 'min-w-[3.25rem] max-w-[3.75rem]' },
  { value: '16:9', wClass: 'min-w-[3.75rem] max-w-[4.5rem]' },
]

/**
 * @param {object} props
 * @param {string} props.value  One of 9:16, 3:4, 1:1, 4:3, 16:9
 * @param {(v: string) => void} props.onChange
 * @param {string} [props.labelId]  id for the visible label (aria-labelledby on group)
 */
export default function AspectRatioSelector({ value, onChange, labelId }) {
  return (
    <div role="group" aria-labelledby={labelId} className="w-full">
      <div className="flex flex-wrap items-stretch gap-2 sm:gap-2.5">
        {OPTIONS.map(({ value: v, wClass }) => {
          const selected = value === v
          return (
            <button
              key={v}
              type="button"
              onClick={() => onChange(v)}
              className={`
                h-10 shrink-0 rounded-xl border text-xs font-semibold transition-colors
                flex items-center justify-center px-1.5
                ${wClass}
                ${
                  selected
                    ? 'bg-white text-gray-950 border-white shadow-sm'
                    : 'border-white/20 bg-gray-900/40 text-gray-400 hover:border-white/35 hover:text-gray-200'
                }
              `}
              aria-pressed={selected}
            >
              {v}
            </button>
          )
        })}
      </div>
    </div>
  )
}
