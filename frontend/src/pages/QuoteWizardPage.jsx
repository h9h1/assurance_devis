import React, { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { createQuote, getConfig } from '../services/api.js'
import './QuoteWizardPage.css'

/* ── Static data (mirrors PHP enums) ─────────────────────────────────── */
const BRANDS = [
  'Toyota','Honda','Ford','BMW','Mercedes','Audi','Volkswagen','Nissan',
  'Hyundai','Kia','Peugeot','Citroën','Renault','Fiat','Seat','Skoda',
  'Opel','Volvo','Jeep','Subaru','Mazda','Lexus','Dacia',
]
const FUELS = ['essence','diesel','hybride','electrique','gpl']

const STEPS = [
  { label: 'Personnel',  icon: 'fa-user' },
  { label: 'Conducteur', icon: 'fa-id-card' },
  { label: 'Assurance',  icon: 'fa-shield-halved' },
  { label: 'Véhicule',   icon: 'fa-car' },
  { label: 'Résumé',     icon: 'fa-list-check' },
]

const INITIAL = {
  lastName:'', firstName:'', city:'', phoneNumber:'', email:'',
  birthDate:'', licenseDate:'',
  insuranceType:'auto',
  vehicleBrand:'', fuelType:'', firstRegistrationDate:'',
  seatCount:'', newValue:'', marketValue:'', registrationNumber:'',
  fiscalPower:'', engineCapacity:'',
}

/* ── Field component ─────────────────────────────────────────────────── */
function Field({ label, required, error, children }) {
  return (
    <div className="form-group">
      {label && (
        <label className="form-label">
          {label}{required && <span className="req"> *</span>}
        </label>
      )}
      {children}
      {error && <span className="field-error"><i className="fas fa-circle-exclamation" />{error}</span>}
    </div>
  )
}

/* ── Main component ──────────────────────────────────────────────────── */
export default function QuoteWizardPage() {
  const navigate = useNavigate()
  const [step, setStep]     = useState(1)
  const [data, setData]     = useState(INITIAL)
  const [errors, setErrors] = useState({})
  const [cities, setCities] = useState([])
  const [loading, setLoading] = useState(false)
  const [submitErr, setSubmitErr] = useState('')

  /* load cities from API */
  useEffect(() => {
    getConfig()
      .then(cfg => { if (cfg.cities) setCities(cfg.cities) })
      .catch(() => {})  // fallback: user types city name
  }, [])

  const set = useCallback((field, value) => {
    setData(prev => ({ ...prev, [field]: value }))
    setErrors(prev => { const n = {...prev}; delete n[field]; return n })
  }, [])

  /* ── Per-step validation ─────────────────────────────────────────────*/
  function validateStep(s) {
    const e = {}
    if (s === 1) {
      if (!data.lastName.trim())    e.lastName    = 'Le nom est obligatoire.'
      if (!data.firstName.trim())   e.firstName   = 'Le prénom est obligatoire.'
      if (!data.city)               e.city        = 'La ville est obligatoire.'
      if (!data.phoneNumber.trim()) e.phoneNumber = 'Le téléphone est obligatoire.'
      else if (!/^(\+212|0)[5-7][0-9]{8}$/.test(data.phoneNumber))
        e.phoneNumber = 'Format invalide (ex: 0612345678)'
    }
    if (s === 2) {
      if (!data.email.trim())       e.email       = 'L\'email est obligatoire.'
      else if (!/\S+@\S+\.\S+/.test(data.email)) e.email = 'Email invalide.'
      if (!data.birthDate)          e.birthDate   = 'Date de naissance obligatoire.'
      if (!data.licenseDate)        e.licenseDate = 'Date du permis obligatoire.'
    }
    if (s === 3) {
      if (!data.insuranceType)      e.insuranceType = 'Choisissez un type.'
    }
    if (s === 4) {
      if (!data.vehicleBrand)             e.vehicleBrand           = 'La marque est obligatoire.'
      if (!data.fuelType)                 e.fuelType               = 'Le carburant est obligatoire.'
      if (!data.firstRegistrationDate)    e.firstRegistrationDate  = 'Date de circulation obligatoire.'
      if (!data.seatCount)                e.seatCount              = 'Nombre de places obligatoire.'
      if (!data.newValue)                 e.newValue               = 'Valeur à neuf obligatoire.'
      if (!data.marketValue)              e.marketValue            = 'Valeur vénale obligatoire.'
      if (!data.registrationNumber.trim()) e.registrationNumber    = 'Immatriculation obligatoire.'
    }
    return e
  }

  function next() {
    const e = validateStep(step)
    if (Object.keys(e).length) { setErrors(e); return }
    setStep(s => s + 1)
  }
  function prev() { setStep(s => s - 1) }

  async function submit() {
    setLoading(true)
    setSubmitErr('')
    try {
      const payload = {
        ...data,
        seatCount:    data.seatCount    ? parseInt(data.seatCount)    : null,
        newValue:     data.newValue     ? parseFloat(data.newValue)   : null,
        marketValue:  data.marketValue  ? parseFloat(data.marketValue): null,
        fiscalPower:  data.fiscalPower  ? parseInt(data.fiscalPower)  : null,
        engineCapacity: data.engineCapacity ? parseInt(data.engineCapacity) : null,
      }
      const res = await createQuote(payload)
      const quote = res.data
      navigate(`/devis/${quote.uuid}/offres?token=${quote.accessToken}`)
    } catch (err) {
      if (err.errors && Object.keys(err.errors).length) {
        setErrors(err.errors)
        // find which step has the first error and go there
        const fields1 = ['lastName','firstName','city','phoneNumber']
        const fields2 = ['email','birthDate','licenseDate']
        const fields3 = ['insuranceType']
        const keys = Object.keys(err.errors)
        if (keys.some(k => fields1.includes(k))) setStep(1)
        else if (keys.some(k => fields2.includes(k))) setStep(2)
        else if (keys.some(k => fields3.includes(k))) setStep(3)
        else setStep(4)
      } else {
        setSubmitErr(err.message || 'Erreur lors de la soumission.')
      }
    } finally {
      setLoading(false)
    }
  }

  /* ── Summary display ─────────────────────────────────────────────────*/
  const SummaryRow = ({ label, value }) => value ? (
    <div className="summary-row">
      <span className="summary-label">{label}</span>
      <span className="summary-value">{value}</span>
    </div>
  ) : null

  return (
    <div className="page-wrap">
      {/* Hero */}
      <div className="wizard-hero fade-up">
        <div>
          <span className="badge badge-blue">Assurance Auto &amp; Moto</span>
          <h1>Demande de devis en ligne</h1>
          <p>Remplissez le formulaire étape par étape — estimation en quelques minutes.</p>
        </div>
        <div className="wizard-hero-icon" aria-hidden="true">
          <i className="fas fa-file-contract" />
        </div>
      </div>

      <div className="card wizard-card fade-up">
        {/* Progress */}
        <div className="wiz-progress">
          <div className="wiz-bar-track">
            <div className="wiz-bar-fill" style={{ width: `${((step - 1) / (STEPS.length - 1)) * 100}%` }} />
          </div>
          <div className="wiz-steps">
            {STEPS.map((s, i) => (
              <div key={s.label} className={`wiz-step ${step === i + 1 ? 'is-active' : ''} ${step > i + 1 ? 'is-done' : ''}`}>
                <div className="wiz-step-num">
                  {step > i + 1 ? <i className="fas fa-check" /> : i + 1}
                </div>
                <span className="wiz-step-label">{s.label}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Steps */}
        <div className="wiz-body">
          {/* Step 1 — Personal */}
          {step === 1 && (
            <div className="wiz-panel fade-up">
              <div className="step-heading">
                <h2>Informations personnelles</h2>
                <p>Commençons par vos coordonnées</p>
              </div>
              <div className="grid-2">
                <Field label="Nom" required error={errors.lastName}>
                  <input className={`form-input ${errors.lastName ? 'has-error' : ''}`}
                    value={data.lastName} onChange={e => set('lastName', e.target.value)}
                    maxLength={100} placeholder="Dupont" />
                </Field>
                <Field label="Prénom" required error={errors.firstName}>
                  <input className={`form-input ${errors.firstName ? 'has-error' : ''}`}
                    value={data.firstName} onChange={e => set('firstName', e.target.value)}
                    maxLength={100} placeholder="Jean" />
                </Field>
                <Field label="Ville" required error={errors.city}>
                  {cities.length > 0 ? (
                    <select className={`form-input ${errors.city ? 'has-error' : ''}`}
                      value={data.city} onChange={e => set('city', e.target.value)}>
                      <option value="">Sélectionner une ville</option>
                      {cities.map(c => <option key={c.name ?? c} value={c.name ?? c}>{c.name ?? c}</option>)}
                    </select>
                  ) : (
                    <input className={`form-input ${errors.city ? 'has-error' : ''}`}
                      value={data.city} onChange={e => set('city', e.target.value)}
                      placeholder="Casablanca" />
                  )}
                </Field>
                <Field label="Téléphone" required error={errors.phoneNumber}>
                  <input className={`form-input ${errors.phoneNumber ? 'has-error' : ''}`}
                    type="tel" value={data.phoneNumber} onChange={e => set('phoneNumber', e.target.value)}
                    placeholder="0612345678 ou +212612345678" />
                </Field>
              </div>
            </div>
          )}

          {/* Step 2 — Driver */}
          {step === 2 && (
            <div className="wiz-panel fade-up">
              <div className="step-heading">
                <h2>Informations du conducteur</h2>
                <p>Détails concernant votre permis de conduire</p>
              </div>
              <div className="grid-2">
                <Field label="Date de naissance" required error={errors.birthDate}>
                  <input className={`form-input ${errors.birthDate ? 'has-error' : ''}`}
                    type="date" value={data.birthDate} onChange={e => set('birthDate', e.target.value)} />
                </Field>
                <Field label="Date d'obtention du permis" required error={errors.licenseDate}>
                  <input className={`form-input ${errors.licenseDate ? 'has-error' : ''}`}
                    type="date" value={data.licenseDate} onChange={e => set('licenseDate', e.target.value)} />
                </Field>
                <Field label="Email" required error={errors.email}>
                  <input className={`form-input ${errors.email ? 'has-error' : ''}`}
                    type="email" value={data.email} onChange={e => set('email', e.target.value)}
                    placeholder="vous@exemple.com" />
                </Field>
              </div>
            </div>
          )}

          {/* Step 3 — Insurance type */}
          {step === 3 && (
            <div className="wiz-panel fade-up">
              <div className="step-heading">
                <h2>Type d'assurance</h2>
                <p>Sélectionnez le type de couverture désiré</p>
              </div>
              <div className="ins-cards">
                {[
                  { value: 'auto', icon: 'fa-car',        title: 'Assurance Auto', desc: 'Véhicule particulier' },
                  { value: 'moto', icon: 'fa-motorcycle', title: 'Assurance Moto', desc: 'Deux roues motorisés' },
                ].map(opt => (
                  <label key={opt.value} className={`ins-card ${data.insuranceType === opt.value ? 'is-selected' : ''}`}>
                    <input type="radio" name="insuranceType" value={opt.value}
                      checked={data.insuranceType === opt.value}
                      onChange={() => set('insuranceType', opt.value)} />
                    <i className={`fas ${opt.icon} ins-card-icon`} />
                    <span className="ins-card-title">{opt.title}</span>
                    <span className="ins-card-desc">{opt.desc}</span>
                  </label>
                ))}
              </div>
              {errors.insuranceType && <span className="field-error">{errors.insuranceType}</span>}
            </div>
          )}

          {/* Step 4 — Vehicle */}
          {step === 4 && (
            <div className="wiz-panel fade-up">
              <div className="step-heading">
                <h2>Informations du véhicule</h2>
                <p>Détails techniques de votre véhicule</p>
              </div>
              <div className="grid-2">
                <Field label="Marque du véhicule" required error={errors.vehicleBrand}>
                  <select className={`form-input ${errors.vehicleBrand ? 'has-error' : ''}`}
                    value={data.vehicleBrand} onChange={e => set('vehicleBrand', e.target.value)}>
                    <option value="">Sélectionner une marque</option>
                    {BRANDS.map(b => <option key={b} value={b}>{b}</option>)}
                  </select>
                </Field>
                <Field label="Type de carburant" required error={errors.fuelType}>
                  <select className={`form-input ${errors.fuelType ? 'has-error' : ''}`}
                    value={data.fuelType} onChange={e => set('fuelType', e.target.value)}>
                    <option value="">Sélectionner un carburant</option>
                    {FUELS.map(f => <option key={f} value={f}>{f.charAt(0).toUpperCase()+f.slice(1)}</option>)}
                  </select>
                </Field>
                <Field label="Date de mise en circulation" required error={errors.firstRegistrationDate}>
                  <input className={`form-input ${errors.firstRegistrationDate ? 'has-error' : ''}`}
                    type="date" value={data.firstRegistrationDate}
                    onChange={e => set('firstRegistrationDate', e.target.value)} />
                </Field>
                <Field label="Nombre de places" required error={errors.seatCount}>
                  <input className={`form-input ${errors.seatCount ? 'has-error' : ''}`}
                    type="number" min="1" value={data.seatCount}
                    onChange={e => set('seatCount', e.target.value)} />
                </Field>
                <Field label="Valeur à neuf (MAD)" required error={errors.newValue}>
                  <input className={`form-input ${errors.newValue ? 'has-error' : ''}`}
                    type="number" step="0.01" min="1" value={data.newValue}
                    onChange={e => set('newValue', e.target.value)} />
                </Field>
                <Field label="Valeur vénale (MAD)" required error={errors.marketValue}>
                  <input className={`form-input ${errors.marketValue ? 'has-error' : ''}`}
                    type="number" step="0.01" min="1" value={data.marketValue}
                    onChange={e => set('marketValue', e.target.value)} />
                </Field>
                <Field label="Immatriculation" required error={errors.registrationNumber}>
                  <input className={`form-input ${errors.registrationNumber ? 'has-error' : ''}`}
                    value={data.registrationNumber} onChange={e => set('registrationNumber', e.target.value.toUpperCase())}
                    placeholder="AB-1234-CD" />
                </Field>
                {data.insuranceType === 'auto' && (
                  <Field label="Puissance fiscale (CV)" error={errors.fiscalPower}>
                    <input className={`form-input ${errors.fiscalPower ? 'has-error' : ''}`}
                      type="number" min="1" value={data.fiscalPower}
                      onChange={e => set('fiscalPower', e.target.value)} />
                  </Field>
                )}
                {data.insuranceType === 'moto' && (
                  <Field label="Cylindrée (cc)" error={errors.engineCapacity}>
                    <input className={`form-input ${errors.engineCapacity ? 'has-error' : ''}`}
                      type="number" min="1" value={data.engineCapacity}
                      onChange={e => set('engineCapacity', e.target.value)} />
                  </Field>
                )}
              </div>
            </div>
          )}

          {/* Step 5 — Summary */}
          {step === 5 && (
            <div className="wiz-panel fade-up">
              <div className="step-heading">
                <h2>Résumé de votre demande</h2>
                <p>Vérifiez vos informations avant confirmation</p>
              </div>
              <div className="summary-grid">
                <div className="summary-block">
                  <h3><i className="fas fa-user" /> Informations personnelles</h3>
                  <SummaryRow label="Nom"        value={`${data.firstName} ${data.lastName}`} />
                  <SummaryRow label="Ville"      value={data.city} />
                  <SummaryRow label="Téléphone"  value={data.phoneNumber} />
                  <SummaryRow label="Email"      value={data.email} />
                </div>
                <div className="summary-block">
                  <h3><i className="fas fa-id-card" /> Conducteur</h3>
                  <SummaryRow label="Naissance"  value={data.birthDate} />
                  <SummaryRow label="Permis"     value={data.licenseDate} />
                </div>
                <div className="summary-block">
                  <h3><i className="fas fa-car" /> Véhicule</h3>
                  <SummaryRow label="Type"         value={data.insuranceType === 'auto' ? 'Automobile' : 'Moto'} />
                  <SummaryRow label="Marque"        value={data.vehicleBrand} />
                  <SummaryRow label="Carburant"     value={data.fuelType} />
                  <SummaryRow label="Immatriculation" value={data.registrationNumber} />
                  <SummaryRow label="Mise en circulation" value={data.firstRegistrationDate} />
                  <SummaryRow label="Places"        value={data.seatCount} />
                  <SummaryRow label="Valeur à neuf" value={data.newValue ? `${Number(data.newValue).toLocaleString('fr-FR')} MAD` : ''} />
                  <SummaryRow label="Valeur vénale" value={data.marketValue ? `${Number(data.marketValue).toLocaleString('fr-FR')} MAD` : ''} />
                </div>
              </div>
              <div className="alert alert-info">
                <i className="fas fa-circle-info" />
                Vous pouvez revenir en arrière pour modifier vos informations.
              </div>
              {submitErr && <div className="alert alert-error" style={{marginTop:12}}><i className="fas fa-circle-xmark" />{submitErr}</div>}
            </div>
          )}
        </div>

        {/* Navigation */}
        <div className="wiz-actions">
          <button className="btn btn-secondary" onClick={prev} style={{ visibility: step === 1 ? 'hidden' : 'visible' }}>
            <i className="fas fa-chevron-left" /> Précédent
          </button>
          {step < 5 ? (
            <button className="btn btn-primary" onClick={next}>
              Suivant <i className="fas fa-chevron-right" />
            </button>
          ) : (
            <button className="btn btn-success" onClick={submit} disabled={loading}>
              {loading ? <><span className="btn-spinner" /> Envoi en cours…</> : <><i className="fas fa-check" /> Confirmer le devis</>}
            </button>
          )}
        </div>
      </div>
    </div>
  )
}
