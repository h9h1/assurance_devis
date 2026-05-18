import React, { useState, useEffect } from 'react'
import { useParams, useSearchParams } from 'react-router-dom'
import { getQuote, sendEmail } from '../services/api.js'
import './QuoteShowPage.css'

function InfoRow({ label, value }) {
  if (!value && value !== 0) return null
  return (
    <div className="info-row">
      <span className="info-label">{label}</span>
      <span className="info-value">{value}</span>
    </div>
  )
}

export default function QuoteShowPage() {
  const { uuid }  = useParams()
  const [params]  = useSearchParams()
  const token     = params.get('token') || ''

  const [quote, setQuote]     = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState('')
  const [email, setEmail]     = useState('')
  const [sending, setSending] = useState(false)
  const [mailMsg, setMailMsg] = useState(null) // { type: 'success'|'error', text }

  useEffect(() => {
    getQuote(uuid, token)
      .then(res => { setQuote(res.data); setEmail(res.data?.email || '') })
      .catch(e  => setError(e.message || 'Devis introuvable.'))
      .finally(() => setLoading(false))
  }, [uuid, token])

  async function handleSendEmail(e) {
    e.preventDefault()
    setSending(true)
    setMailMsg(null)
    try {
      await sendEmail(uuid, token, email)
      setMailMsg({ type: 'success', text: `Récapitulatif envoyé à ${email} !` })
    } catch (err) {
      setMailMsg({ type: 'error', text: err.message || 'Erreur lors de l\'envoi.' })
    } finally {
      setSending(false)
    }
  }

  if (loading) return <div className="page-wrap"><div className="spinner" /></div>
  if (error)   return <div className="page-wrap"><div className="alert alert-error"><i className="fas fa-circle-xmark" />{error}</div></div>

  const q = quote

  return (
    <div className="page-wrap">
      {/* Success banner */}
      <div className="show-banner fade-up">
        <div className="banner-icon"><i className="fas fa-circle-check" /></div>
        <div>
          <h1>Devis enregistré avec succès</h1>
          <p>Votre demande #{q.id} a bien été reçue{q.selectedOffer ? ` — offre "${q.selectedOffer}" sélectionnée` : ''}.</p>
        </div>
      </div>

      {/* Summary grid */}
      <div className="show-grid fade-up">
        {/* Personal */}
        <div className="show-section card">
          <div className="section-head">
            <i className="fas fa-user" /><h2>Informations personnelles</h2>
          </div>
          <div className="section-body">
            <InfoRow label="Nom"       value={q.lastName} />
            <InfoRow label="Prénom"    value={q.firstName} />
            <InfoRow label="Ville"     value={q.city} />
            <InfoRow label="Téléphone" value={q.phoneNumber} />
            <InfoRow label="Email"     value={q.email} />
          </div>
        </div>

        {/* Driver */}
        <div className="show-section card">
          <div className="section-head">
            <i className="fas fa-id-card" /><h2>Conducteur</h2>
          </div>
          <div className="section-body">
            <InfoRow label="Date de naissance"  value={q.birthDate} />
            <InfoRow label="Date du permis"     value={q.licenseDate} />
          </div>
        </div>

        {/* Vehicle */}
        <div className="show-section card">
          <div className="section-head">
            <i className="fas fa-car" /><h2>Véhicule</h2>
          </div>
          <div className="section-body">
            <InfoRow label="Type"         value={q.insuranceType === 'auto' ? 'Automobile' : 'Moto'} />
            <InfoRow label="Marque"       value={q.vehicleBrand} />
            <InfoRow label="Carburant"    value={q.fuelType} />
            <InfoRow label="Immatriculation" value={q.registrationNumber} />
            <InfoRow label="1ère mise en circulation" value={q.firstRegistrationDate} />
            <InfoRow label="Places"       value={q.seatCount} />
            <InfoRow label="Valeur à neuf" value={q.newValue ? `${Number(q.newValue).toLocaleString('fr-FR')} MAD` : null} />
            <InfoRow label="Valeur vénale" value={q.marketValue ? `${Number(q.marketValue).toLocaleString('fr-FR')} MAD` : null} />
            {q.fiscalPower && <InfoRow label="Puissance fiscale" value={`${q.fiscalPower} CV`} />}
            {q.engineCapacity && <InfoRow label="Cylindrée" value={`${q.engineCapacity} cc`} />}
          </div>
        </div>

        {/* Offer selected */}
        {q.selectedOffer && (
          <div className="show-section card">
            <div className="section-head">
              <i className="fas fa-shield-halved" /><h2>Offre sélectionnée</h2>
            </div>
            <div className="section-body">
              <InfoRow label="Offre"     value={q.selectedOffer.charAt(0).toUpperCase() + q.selectedOffer.slice(1)} />
              {q.customEstimation && <InfoRow label="Prime annuelle" value={`${Number(q.customEstimation).toLocaleString('fr-FR')} MAD`} />}
              {q.company && <InfoRow label="Compagnie" value={q.company} />}
            </div>
          </div>
        )}
      </div>

      {/* Actions */}
      <div className="show-actions card fade-up">
        {/* PDF */}
        <div className="action-block">
          <div className="action-info">
            <i className="fas fa-file-pdf" />
            <div>
              <h3>Télécharger en PDF</h3>
              <p>Obtenez votre récapitulatif de devis au format PDF.</p>
            </div>
          </div>
          <a
            href={`/devis/${uuid}/pdf?token=${token}`}
            target="_blank"
            rel="noreferrer"
            className="btn btn-secondary"
          >
            <i className="fas fa-download" /> Télécharger PDF
          </a>
        </div>

        <div className="action-divider" />

        {/* Email */}
        <div className="action-block">
          <div className="action-info">
            <i className="fas fa-envelope" />
            <div>
              <h3>Recevoir par email</h3>
              <p>Envoyez le récapitulatif à votre adresse email.</p>
            </div>
          </div>
          <form className="email-form" onSubmit={handleSendEmail}>
            <input
              className="form-input"
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              placeholder="votre@email.com"
              required
            />
            <button type="submit" className="btn btn-primary" disabled={sending}>
              {sending ? <><span className="btn-spinner" /> Envoi…</> : <><i className="fas fa-paper-plane" /> Envoyer</>}
            </button>
          </form>
        </div>

        {mailMsg && (
          <div className={`alert alert-${mailMsg.type}`} style={{marginTop:12}}>
            <i className={`fas ${mailMsg.type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'}`} />
            {mailMsg.text}
          </div>
        )}
      </div>
    </div>
  )
}
