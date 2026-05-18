import React from 'react'
import { Link } from 'react-router-dom'
import './HomePage.css'

const FEATURES = [
  { icon: 'fa-bolt', title: 'Rapide', desc: 'Obtenez votre estimation en moins de 5 minutes.' },
  { icon: 'fa-shield-halved', title: 'Fiable', desc: 'Des offres adaptées aux meilleures compagnies marocaines.' },
  { icon: 'fa-file-pdf', title: 'Récap PDF', desc: 'Téléchargez votre devis détaillé en un clic.' },
  { icon: 'fa-envelope', title: 'Email', desc: 'Recevez votre récapitulatif directement par email.' },
]

export default function HomePage() {
  return (
    <div className="home">
      {/* Hero */}
      <section className="home-hero fade-up">
        <div className="hero-copy">
          <span className="badge badge-blue">Assurance Auto &amp; Moto</span>
          <h1>Votre devis d'assurance<br /><em>en quelques minutes</em></h1>
          <p className="hero-sub">
            Comparez les offres des meilleures compagnies d'assurance marocaines
            et choisissez la couverture idéale pour votre véhicule.
          </p>
          <Link to="/devis/nouveau" className="btn btn-primary btn-lg">
            <i className="fas fa-file-contract" />
            Obtenir mon devis gratuit
          </Link>
        </div>
        <div className="hero-visual" aria-hidden="true">
          <div className="hero-blob">
            <i className="fas fa-car" />
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="home-features">
        {FEATURES.map((f) => (
          <div key={f.title} className="feature-card fade-up">
            <span className="feature-icon">
              <i className={`fas ${f.icon}`} />
            </span>
            <h3>{f.title}</h3>
            <p>{f.desc}</p>
          </div>
        ))}
      </section>

      {/* CTA */}
      <section className="home-cta fade-up">
        <h2>Prêt à comparer ?</h2>
        <p>Remplissez le formulaire en 5 étapes et obtenez vos offres instantanément.</p>
        <Link to="/devis/nouveau" className="btn btn-primary btn-lg">
          Commencer maintenant <i className="fas fa-arrow-right" />
        </Link>
      </section>
    </div>
  )
}
