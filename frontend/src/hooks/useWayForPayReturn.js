import { useEffect, useRef } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import toast from 'react-hot-toast'
import { paymentsApi } from '../services/api'
import { useAuthContext } from '../context/AuthContext'

const ORDER_REF_KEY = 'app_wfp_order_ref'
const ORDER_REF_LEGACY = 'reelforge_wfp_order_ref'

function getWayForPayOrderRef() {
  return sessionStorage.getItem(ORDER_REF_KEY) || sessionStorage.getItem(ORDER_REF_LEGACY)
}

function clearWayForPayOrderRef() {
  sessionStorage.removeItem(ORDER_REF_KEY)
  sessionStorage.removeItem(ORDER_REF_LEGACY)
}

/** Call after invoice API returns, before POST to WayForPay. */
export function setWayForPayOrderReference(orderReference) {
  if (typeof orderReference === 'string' && orderReference !== '') {
    sessionStorage.setItem(ORDER_REF_KEY, orderReference)
  }
}

const handledRefs = new Set()

/**
 * After WayForPay returnUrl (?payment=return), polls backend for PaymentOrder status.
 * Credits are granted in serviceUrl callback — this only reflects DB state for UX.
 *
 * @param {{ onSettled?: (payload: { status: string | null }) => void }} [options]
 */
export function useWayForPayReturn(options = {}) {
  const { onSettled } = options
  const onSettledRef = useRef(onSettled)
  onSettledRef.current = onSettled

  const { t } = useTranslation()
  const { refreshUser } = useAuthContext()
  const [searchParams, setSearchParams] = useSearchParams()

  useEffect(() => {
    if (searchParams.get('payment') !== 'return') return

    const ref = getWayForPayOrderRef()
    setSearchParams({}, { replace: true })

    if (!ref) {
      ;(async () => {
        await refreshUser?.()
        toast(t('credits.wayforpayReturnUnknown'))
        onSettledRef.current?.({ status: null })
      })()
      return
    }

    if (handledRefs.has(ref)) return
    handledRefs.add(ref)

    ;(async () => {
      let status = null
      try {
        for (let attempt = 0; attempt < 6; attempt++) {
          const res = await paymentsApi.wayforpayOrderStatus(ref)
          status = res.data?.data?.status ?? null
          if (status === 'completed' || status === 'failed') {
            break
          }
          if (attempt < 5) {
            await new Promise((r) => setTimeout(r, 1500))
          }
        }

        await refreshUser?.()

        if (status === 'completed') {
          toast.success(t('credits.wayforpayReturnSuccess'))
        } else if (status === 'failed') {
          toast.error(t('credits.wayforpayReturnFailed'))
        } else {
          toast(t('credits.wayforpayReturnPending'), { duration: 6500 })
        }
      } catch {
        await refreshUser?.()
        toast.error(t('credits.wayforpayReturnCheckFailed'))
      } finally {
        clearWayForPayOrderRef()
        onSettledRef.current?.({ status })
      }
    })()
  }, [searchParams, setSearchParams, refreshUser, t])
}
