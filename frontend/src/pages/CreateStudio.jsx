import { useState, useRef, useEffect } from 'react'
import { Send, Sparkles, Bot, User } from 'lucide-react'
import { useTranslation } from 'react-i18next'

const initialMessages = (t) => [
  {
    role: 'assistant',
    text: t('createStudio.welcome'),
  },
]

export default function CreateStudio() {
  const { t, i18n } = useTranslation()
  const [messages, setMessages] = useState(() => initialMessages(t))
  const [input, setInput] = useState('')
  const bottomRef = useRef(null)

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  useEffect(() => {
    setMessages(initialMessages(t))
  }, [i18n.language])

  const handleSend = (e) => {
    e.preventDefault()
    const text = input.trim()
    if (!text) return
    setMessages((m) => [...m, { role: 'user', text }])
    setInput('')
    setTimeout(() => {
      setMessages((m) => [
        ...m,
        {
          role: 'assistant',
          text: t('createStudio.placeholderReply'),
        },
      ])
    }, 600)
  }

  return (
    <div className="max-w-3xl mx-auto flex flex-col" style={{ minHeight: 'calc(100vh - 8rem)' }}>
      <div className="mb-6">
        <div className="inline-flex items-center gap-2 text-brand-400 mb-2">
          <Sparkles className="w-5 h-5" />
          <span className="text-sm font-semibold uppercase tracking-wide">{t('createStudio.badge')}</span>
        </div>
        <h1 className="text-3xl font-bold text-white">{t('createStudio.title')}</h1>
        <p className="text-gray-400 mt-2">{t('createStudio.subtitle')}</p>
      </div>

      <div className="flex-1 card flex flex-col min-h-[420px] border-white/10 p-0 overflow-hidden">
        <div className="flex-1 overflow-y-auto p-4 space-y-4">
          {messages.map((msg, i) => (
            <div
              key={i}
              className={`flex gap-3 ${msg.role === 'user' ? 'flex-row-reverse' : ''}`}
            >
              <div
                className={`w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 ${
                  msg.role === 'user'
                    ? 'bg-gradient-to-br from-brand-600 to-purple-600'
                    : 'bg-gray-800 border border-white/10'
                }`}
              >
                {msg.role === 'user' ? (
                  <User className="w-4 h-4 text-white" />
                ) : (
                  <Bot className="w-4 h-4 text-brand-400" />
                )}
              </div>
              <div
                className={`max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed ${
                  msg.role === 'user'
                    ? 'bg-brand-600/25 text-white border border-brand-500/20'
                    : 'bg-white/5 text-gray-300 border border-white/10'
                }`}
              >
                {msg.text}
              </div>
            </div>
          ))}
          <div ref={bottomRef} />
        </div>

        <form onSubmit={handleSend} className="p-4 border-t border-white/10 bg-gray-900/50">
          <div className="flex gap-2">
            <input
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder={t('createStudio.inputPlaceholder')}
              className="input-field flex-1 text-sm py-3"
            />
            <button type="submit" className="btn-primary px-4 flex items-center gap-2" disabled={!input.trim()}>
              <Send className="w-4 h-4" />
              <span className="hidden sm:inline">{t('createStudio.send')}</span>
            </button>
          </div>
          <p className="text-xs text-gray-600 mt-2">{t('createStudio.hint')}</p>
        </form>
      </div>
    </div>
  )
}
