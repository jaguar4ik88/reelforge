export default function FormField({ label, error, children }) {
  return (
    <div className="space-y-1.5">
      {label && (
        <label className="block text-sm font-medium text-gray-300">
          {label}
        </label>
      )}
      {children}
      {error && (
        <p className="text-xs text-red-400 mt-1">{error}</p>
      )}
    </div>
  )
}
