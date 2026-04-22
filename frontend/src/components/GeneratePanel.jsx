import { useState } from 'react'
import { Camera, FileImage, Eye, Sparkles, Download, RefreshCw } from 'lucide-react'
import { useImageGeneration } from '../hooks/useImageGeneration'

const CONTENT_TYPES = [
  { id: 'photo',   label: 'Улучшенное фото',   icon: Camera },
  { id: 'card',    label: 'Карточка товара',    icon: FileImage },
  { id: 'preview', label: 'Быстрый черновик',   icon: Eye },
]

const STYLES = [
  { id: 'studio',    emoji: '🎬', label: 'Студия' },
  { id: 'lifestyle', emoji: '🌿', label: 'Лайфстайл' },
  { id: 'minimal',   emoji: '⬜', label: 'Минимализм' },
  { id: 'outdoor',   emoji: '☀️', label: 'На улице' },
]

export default function GeneratePanel({ productDescription = '' }) {
  const [contentType, setContentType] = useState('photo')
  const [style,       setStyle]       = useState('studio')

  const { status, images, progress, generate, reset } = useImageGeneration()

  const isGenerating = status === 'loading' || status === 'polling'
  const isDone       = status === 'done'
  const canGenerate  = status === 'idle' || status === 'error'

  const handleGenerate = () => {
    generate({ style, description: productDescription || 'product', contentType })
  }

  return (
    <div className="card space-y-5">
      <h2 className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
        AI-генерация изображений
      </h2>

      {/* Тип контента */}
      <div>
        <p className="text-sm text-gray-400 mb-2">Тип контента</p>
        <div className="flex gap-2 flex-wrap">
          {CONTENT_TYPES.map(({ id, label, icon: Icon }) => (
            <button
              key={id}
              onClick={() => setContentType(id)}
              className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border transition-colors
                ${contentType === id
                  ? 'bg-brand-600 border-brand-600 text-white'
                  : 'border-gray-700 text-gray-400 hover:border-gray-500 hover:text-gray-200'
                }`}
            >
              <Icon className="w-4 h-4" />
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Стиль сцены */}
      <div>
        <p className="text-sm text-gray-400 mb-2">Стиль сцены</p>
        <div className="grid grid-cols-2 gap-2">
          {STYLES.map(({ id, emoji, label }) => (
            <button
              key={id}
              onClick={() => setStyle(id)}
              className={`flex items-center gap-2 p-3 rounded-xl text-sm font-medium border transition-colors
                ${style === id
                  ? 'border-brand-500 bg-brand-600/10 text-brand-300'
                  : 'border-gray-700 text-gray-400 hover:border-gray-600 hover:text-gray-200'
                }`}
            >
              <span>{emoji}</span>
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Кнопка генерации */}
      {canGenerate && (
        <button
          onClick={handleGenerate}
          disabled={!productDescription}
          className="btn-primary w-full flex items-center justify-center gap-2"
        >
          <Sparkles className="w-4 h-4" />
          Сгенерировать
        </button>
      )}

      {/* Прогресс-бар */}
      {isGenerating && (
        <div className="space-y-2">
          <div className="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
            <div
              className="h-2 bg-brand-500 rounded-full transition-all duration-500"
              style={{ width: `${progress}%` }}
            />
          </div>
          <p className="text-xs text-gray-500 text-center">
            {status === 'loading' ? 'Отправляем задачу...' : 'Генерируем изображение...'}
          </p>
        </div>
      )}

      {/* Результат */}
      {isDone && images.length > 0 && (
        <div className="space-y-3">
          <div className="grid grid-cols-1 gap-3">
            {images.map((url, i) => (
              <div key={i} className="relative group rounded-xl overflow-hidden bg-gray-900">
                <img src={url} alt={`Generated ${i + 1}`} className="w-full object-cover rounded-xl" />
                <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <a
                    href={url}
                    download={`export-${i + 1}.png`}
                    target="_blank"
                    rel="noreferrer"
                    className="btn-primary flex items-center gap-2 text-sm"
                  >
                    <Download className="w-4 h-4" />
                    Скачать
                  </a>
                </div>
              </div>
            ))}
          </div>
          <button
            onClick={reset}
            className="btn-secondary w-full flex items-center justify-center gap-2 text-sm"
          >
            <RefreshCw className="w-4 h-4" />
            Сгенерировать ещё
          </button>
        </div>
      )}
    </div>
  )
}
