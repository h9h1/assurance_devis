const BASE = '/api/admin'

async function request(method, url, body = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    credentials: 'include', // sends the Symfony session cookie
  }
  if (body) opts.body = JSON.stringify(body)
  const res = await fetch(url, opts)
  if (res.status === 401 || res.status === 403) {
    // Not logged in — redirect to EasyAdmin login
    window.location.href = '/admin/login'
    return
  }
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`)
  return data
}

export const getStats  = ()                         => request('GET', `${BASE}/stats`)
export const getQuotes = (params = {})              => request('GET', `${BASE}/quotes?${new URLSearchParams(params)}`)
export const getQuote  = (id)                       => request('GET', `${BASE}/quotes/${id}`)
