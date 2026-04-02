export default function Spinner({ size = 'md', className = '' }) {
  const sizes = { sm: 'w-4 h-4', md: 'w-6 h-6', lg: 'w-10 h-10' }
  return (
    <div
      className={`rounded-full border-2 border-brand-500/30 border-t-brand-500 animate-spin ${sizes[size]} ${className}`}
    />
  )
}
