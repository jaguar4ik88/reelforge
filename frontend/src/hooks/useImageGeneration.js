import { useRef, useState } from 'react'
import toast from 'react-hot-toast'
import { generationApi } from '../services/api'

const POLL_INTERVAL_MS = 2500
const MAX_POLLS        = 40  // ~100 секунд таймаут

export function useImageGeneration() {
  const [status,   setStatus]   = useState('idle')    // idle | loading | polling | done | error
  const [images,   setImages]   = useState([])
  const [progress, setProgress] = useState(0)

  const pollTimerRef   = useRef(null)
  const pollCountRef   = useRef(0)
  const predictionRef  = useRef(null)

  const stopPolling = () => {
    if (pollTimerRef.current) {
      clearInterval(pollTimerRef.current)
      pollTimerRef.current = null
    }
  }

  const reset = () => {
    stopPolling()
    setStatus('idle')
    setImages([])
    setProgress(0)
    pollCountRef.current  = 0
    predictionRef.current = null
  }

  const startPolling = (predictionId) => {
    pollCountRef.current = 0

    pollTimerRef.current = setInterval(async () => {
      pollCountRef.current += 1

      // Прогресс 0–90% в процессе, 100% при success
      setProgress(Math.min(90, Math.round(pollCountRef.current * (90 / MAX_POLLS))))

      // Таймаут
      if (pollCountRef.current > MAX_POLLS) {
        stopPolling()
        setStatus('error')
        toast.error('Генерация заняла слишком долго. Попробуй ещё раз.')
        return
      }

      try {
        const { data } = await generationApi.status(predictionId)

        if (data.status === 'succeeded') {
          stopPolling()
          setProgress(100)
          setImages(Array.isArray(data.output) ? data.output : [data.output])
          setStatus('done')
          toast.success('Изображение готово!')
          return
        }

        if (data.status === 'failed') {
          stopPolling()
          setStatus('error')
          toast.error(data.error || 'Ошибка генерации')
        }

      } catch {
        stopPolling()
        setStatus('error')
        toast.error('Ошибка при получении статуса генерации')
      }
    }, POLL_INTERVAL_MS)
  }

  const generate = async ({ style, description, contentType }) => {
    reset()
    setStatus('loading')
    setProgress(0)

    try {
      const { data } = await generationApi.start({ style, description, contentType })
      predictionRef.current = data.predictionId
      setStatus('polling')
      startPolling(data.predictionId)
    } catch (err) {
      setStatus('error')
      toast.error(err.response?.data?.message ?? 'Не удалось запустить генерацию')
    }
  }

  return { status, images, progress, generate, reset }
}
