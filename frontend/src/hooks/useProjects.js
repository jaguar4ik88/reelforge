import { useState, useEffect, useCallback } from 'react'
import { projectsApi } from '../services/api'

export function useProjects() {
  const [projects, setProjects] = useState([])
  const [meta, setMeta]         = useState(null)
  const [loading, setLoading]   = useState(true)
  const [page, setPage]         = useState(1)

  const fetch = useCallback(async (p = page) => {
    setLoading(true)
    try {
      const { data } = await projectsApi.list(p)
      setProjects(data.data.data)
      setMeta(data.data.meta)
    } finally {
      setLoading(false)
    }
  }, [page])

  useEffect(() => { fetch() }, [fetch])

  return { projects, meta, loading, page, setPage, refresh: fetch }
}

export function useProject(id) {
  const [project, setProject] = useState(null)
  const [loading, setLoading] = useState(true)

  const fetch = useCallback(async () => {
    if (!id) return
    setLoading(true)
    try {
      const { data } = await projectsApi.get(id)
      setProject(data.data)
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => { fetch() }, [fetch])

  return { project, loading, refresh: fetch }
}
