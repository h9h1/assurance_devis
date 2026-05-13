import React, { useState, useEffect } from 'react'
import { useParams, useSearchParams, useNavigate } from 'react-router-dom'
import { getOffers, selectOffer } from '../services/api.js'
import './OffersPage.css'

const OFFER_META = {
  tiers:        { badge: 'Basique',  badgeClass: 'badge-blue',   highlight: false },
  intermediaire:{ badge: 'Populaire',badgeClass: 'badge-orange', highlight: true  },
  tous_risques: { badge: 'Premium',  badgeClass: 'badge-green',  highlight: false },
}

const FEATURES = {
  tiers:        ['Responsabilité civile', 'Défense et recours'],
  intermediaire:['Responsabilité civile', 'Vol', 'Incendie', 'Bris de glace'],
  tous_risques: ['Responsabilité civile', 'Vol', 'Incendie', 'Bris de glace', 'Dommages tous accidents', 'Assistance 24h/24'],
}

export default function OffersPage() {
  const { uuid } = useParams()
  const [params]  = useSearchParams()
  const token     = params.get('token') || ''
  const navigate  = useNavigate()

  const [quote, setQuote]         = useState(null)
  const [offers, setOffers]       = useState([])
  const [companies, setCompanies] = useState([])
  const [company, setCompany]     = useState('')
  const [loading, setLoading]     = useState(true)
  const [selecting, setSelecting] = useState('')
  const [error, setError]         = useState('')

  async function load(comp = company) {
    setLoading(true)
    setError('')
    try {
      const res = await getOffers(uuid, token, comp)
      if (res.quote)     setQuote(res.quote)
      if (res.offers)    setOffers(res.offers)
      if (res.companies) setCompanies(res.companies)
    } catch (e) {
      setError(e.message || 'Impossible de charger les offres.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { load() }, [uuid, token])

  function handleCompanyChange(e) {
    const c = e.target.value
    setCompany(c)
    load(c)
  }

  async function handleSelect(offer) {
    setSelecting(offer.code)
    try {
      const res = await selectOffer(uuid, token, offer.code, company)
      navigate(`/devis/${uuid}?token=${token}`)
    } catch (e) {
      setError(e.message || 'Erreur lors de la sélection.')
      setSelecting('')
    }
  }

  if (loading) return <div className="page-wrap"><div className="spinner" /></div>

  if (error) return (
    <div className="page-wrap">
      <div className="alert alert-error"><i className="fas fa-circle-xmark" />{error}</div>
    </div>
  )

  return (
    <div className="page-wrap--wide">
      {/* Header */}
      <div className="offers-header fade-up">
        <div>
          <h1>Vos offres personnalisées</h1>
          {quote && (
            <p>Bonjour <strong>{quote.firstName} {quote.lastName}</strong> — voici les offres calculées pour votre demande.</p>
          )}
        </div>
        {companies.length > 0 && (
          <div className="company-picker">
            <label><i className="fas fa-building" /> Compagnie</label>
            <select value={company} onChange={handleCompanyChange} className="form-input">
              <option value="">Toutes les compagnies</option>
              {companies.map(c => (
                <option key={c.name ?? c} value={c.name ?? c}>{c.name ?? c}</option>
              ))}
            </select>
          </div>
        )}
      </div>

      {/* Grid */}
      <div className="offers-grid">
        {offers.map((offer, i) => {
          const meta     = OFFER_META[offer.code] || {}
          const features = FEATURES[offer.code]   || []
          return (
            <div key={offer.code} className={`offer-card fade-up ${meta.highlight ? 'is-featured' : ''}`}
              style={{ animationDelay: `${i * 0.08}s` }}>
              {meta.highlight && <div className="featured-ribbon">Recommandé</div>}
              <div className="offer-head">
                <h2>{offer.title}</h2>
                {meta.badge && <span className={`badge ${meta.badgeClass}`}>{meta.badge}</span>}
              </div>

              <p className="offer-desc">{offer.description}</p>

              <div className="offer-price">
                <div className="price-annual">
                  <span className="price-amt">{Number(offer.annual_price).toLocaleString('fr-FR')}</span>
                  <span className="price-unit">DH/an</span>
                </div>
                <div className="price-monthly">
                  <i className="fas fa-calendar-days" />
                  {Number(offer.monthly_price).toLocaleString('fr-FR')} DH/mois
                </div>
              </div>

              <ul className="offer-features">
                {features.map(f => (
                  <li key={f}><i className="fas fa-check" />{f}</li>
                ))}
              </ul>

              <button
                className={`btn btn-block ${meta.highlight ? 'btn-primary' : 'btn-secondary'}`}
                onClick={() => handleSelect(offer)}
                disabled={!!selecting}
              >
                {selecting === offer.code
                  ? <><span className="btn-spinner" /> Sélection…</>
                  : <><i className="fas fa-check-circle" /> Choisir cette offre</>}
              </button>
            </div>
          )
        })}
      </div>
    </div>
  )
}
