import { useState, useEffect, useCallback, useRef } from 'react'
import { projectsApi } from '../services/api'

const GALLERY_PER_PAGE = 20

/**
 * GET /api/projects returns { success, data: { data: [...], items: [...], meta, links } }.
 * `items` is an optional alias of `data` (see ProjectController). Prefer either array.
 */
function listAndMetaFromApiBody(body) {
  if (!body || typeof body !== 'object') {
    return { list: [], meta: null }
  }
  if (Array.isArray(body)) {
    return { list: body, meta: null }
  }
  const p = body.data
  if (p == null) {
    return { list: [], meta: null }
  }
  if (Array.isArray(p)) {
    return { list: p, meta: null }
  }
  if (typeof p !== 'object') {
    return { list: [], meta: null }
  }
  const list = p.items ?? p.data
  return {
    list: Array.isArray(list) ? list : [],
    meta: p.meta ?? null,
  }
}

/**
 * @param {object} [options]
 * @param {number} [options.perPage]
 * @param {string} [options.projectFilter]  'all' | project id string
 * @param {string} [options.typeFilter]     'all' | 'video' | 'draft' | 'processing'
 */
export function useProjects({
  perPage = GALLERY_PER_PAGE,
  projectFilter = 'all',
  typeFilter = 'all',
} = {}) {
  const [projects, setProjects]     = useState([])
  const [meta, setMeta]             = useState(null)
  const [loading, setLoading]       = useState(true)
  const [error, setError]            = useState(null)
  const [page, setPage]              = useState(1)
  const filtersFirstMount = useRef(true)

  // Reset to page 1 when filters change (skip initial mount to avoid double fetch)
  useEffect(() => {
    if (filtersFirstMount.current) {
      filtersFirstMount.current = false
      return
    }
    setPage(1)
  }, [projectFilter, typeFilter])

  const fetch = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const { data: body } = await projectsApi.list(page, perPage, projectFilter, typeFilter)
      if (body && body.success === false) {
        setProjects([])
        setMeta(null)
        setError(typeof body.message === 'string' ? body.message : 'Request failed')
        return
      }
      const { list, meta: m } = listAndMetaFromApiBody(body)
      setProjects(list)
      setMeta(m)
    } catch (e) {
      setProjects([])
      setMeta(null)
      setError(
        e?.response?.data?.message ||
          e?.message ||
          'Network error'
      )
    } finally {
      setLoading(false)
    }
  }, [page, perPage, projectFilter, typeFilter])

  useEffect(() => {
    fetch()
  }, [fetch])

  return { projects, meta, loading, error, page, setPage, refresh: fetch, perPage }
}

export function useProjectFilterOptions() {
  const [options, setOptions] = useState([])

  useEffect(() => {
    let cancelled = false
    projectsApi
      .listCompact()
      .then(({ data: body }) => {
        const rows = body?.data
        if (!cancelled && Array.isArray(rows)) {
          setOptions(rows)
        }
      })
      .catch(() => {
        if (!cancelled) setOptions([])
      })
    return () => {
      cancelled = true
    }
  }, [])

  return options
}

export function useProject(id) {
  const [project, setProject] = useState(null)
  const [loading, setLoading] = useState(true)

  const fetch = useCallback(async () => {
    if (!id) return
    setLoading(true)
    try {
      const { data: body } = await projectsApi.get(id)
      setProject(body.data)
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => { fetch() }, [fetch])

  return { project, loading, refresh: fetch }
}
