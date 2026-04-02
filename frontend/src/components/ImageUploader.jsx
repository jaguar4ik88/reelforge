import { useCallback } from 'react'
import { useDropzone } from 'react-dropzone'
import { useTranslation } from 'react-i18next'
import { Upload, X, ImageIcon } from 'lucide-react'

export default function ImageUploader({
  files,
  onChange,
  maxFiles = 5,
  minRequiredForHint = 3,
}) {
  const { t } = useTranslation()

  const onDrop = useCallback((accepted) => {
    onChange([...files, ...accepted].slice(0, maxFiles))
  }, [files, onChange, maxFiles])

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    accept:   { 'image/jpeg': [], 'image/png': [], 'image/webp': [] },
    maxFiles: maxFiles - files.length,
    disabled: files.length >= maxFiles,
  })

  const removeFile = (index) => onChange(files.filter((_, i) => i !== index))

  return (
    <div className="space-y-4">
      {files.length < maxFiles && (
        <div
          {...getRootProps()}
          className={`border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all duration-200
            ${isDragActive
              ? 'border-brand-400 bg-brand-900/20'
              : 'border-white/20 hover:border-brand-500/50 hover:bg-white/[0.02]'
            }`}
        >
          <input {...getInputProps()} />
          <Upload className="w-10 h-10 text-gray-500 mx-auto mb-3" />
          <p className="text-sm font-medium text-gray-300">
            {isDragActive ? t('uploader.dropHere') : t('uploader.dropHere')}
          </p>
          <p className="text-xs text-gray-500 mt-1">{t('uploader.orClick')}</p>
          <p className="text-xs text-brand-400 mt-2 font-medium">
            {t('uploader.addedMax', { count: files.length, max: maxFiles })}
            {files.length < minRequiredForHint &&
              ` ${t('uploader.needMore', { count: minRequiredForHint - files.length })}`}
          </p>
        </div>
      )}

      {files.length > 0 && (
        <div className="grid grid-cols-3 sm:grid-cols-5 gap-3">
          {files.map((file, i) => (
            <div key={i} className="relative group aspect-[9/16] rounded-xl overflow-hidden bg-gray-800">
              <img src={URL.createObjectURL(file)} alt={`slide ${i + 1}`} className="w-full h-full object-cover" />
              <div className="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-200" />
              <button
                onClick={() => removeFile(i)}
                className="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-500 text-white
                           flex items-center justify-center opacity-0 group-hover:opacity-100
                           transition-opacity duration-200 hover:bg-red-400"
              >
                <X className="w-3 h-3" />
              </button>
              <span className="absolute bottom-1.5 left-1.5 w-5 h-5 rounded-full bg-black/60 text-white text-xs flex items-center justify-center font-bold">
                {i + 1}
              </span>
            </div>
          ))}

          {files.length < maxFiles && (
            <div
              {...getRootProps()}
              className="aspect-[9/16] rounded-xl border-2 border-dashed border-white/20
                         flex flex-col items-center justify-center cursor-pointer
                         hover:border-brand-500/50 hover:bg-white/[0.02] transition-all duration-200"
            >
              <input {...getInputProps()} />
              <ImageIcon className="w-6 h-6 text-gray-600 mb-1" />
              <span className="text-xs text-gray-600">{t('uploader.add')}</span>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
