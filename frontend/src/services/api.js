const BASE = '/api'

async function request(method, url, body = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  }
  if (body) opts.body = JSON.stringify(body)
  const res = await fetch(url, opts)
  const data = await res.json().catch(() => ({}))
  if (!res.ok) {
    const err = new Error(data.message || `HTTP ${res.status}`)
    err.errors = data.errors || {}
    err.status = res.status
    throw err
  }
  return data
}

/* ── Quote CRUD ─────────────────────────────────────────────────────── */

export async function createQuote(payload) {
  return request('POST', `${BASE}/quotes`, payload)
}

export async function getQuote(uuid, token) {
  return request('GET', `${BASE}/quotes/${uuid}?token=${token}`)
}

/* ── Offers ──────────────────────────────────────────────────────────── */

export async function getOffers(uuid, token, company = '') {
  const qs = new URLSearchParams({ token })
  if (company) qs.set('company', company)
  return request('GET', `${BASE}/quotes/${uuid}/offers?${qs}`)
}

export async function selectOffer(uuid, token, offerCode, company) {
  return request('POST', `${BASE}/quotes/${uuid}/select-offer`, {
    token,
    offer_code: offerCode,
    company,
  })
}

/* ── Email ───────────────────────────────────────────────────────────── */

export async function sendEmail(uuid, token, email) {
  return request('POST', `${BASE}/quotes/${uuid}/send-email`, { token, email })
}

/* ── Config (cities, brands, fuels) ─────────────────────────────────── */

export async function getConfig() {
  return request('GET', `${BASE}/config`)
}
