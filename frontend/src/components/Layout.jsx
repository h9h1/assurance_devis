import React, { useState, useEffect } from 'react'
import { Outlet, Link, useLocation } from 'react-router-dom'
import './Layout.css'
import logo from '../assets/logo.png'

export default function Layout() {
  const location = useLocation()
  const [scrolled, setScrolled] = useState(false)

  useEffect(() => {
    const handler = () => setScrolled(window.scrollY > 8)
    window.addEventListener('scroll', handler)
    return () => window.removeEventListener('scroll', handler)
  }, [])

  return (
    <>
      <header className={`site-header ${scrolled ? 'is-scrolled' : ''}`}>
        <div className="header-inner">
          <Link to="/" className="header-logo">
            <img src={logo} alt="Aksam Assurance" />
          </Link>
          <nav className="header-nav">
            <Link
              to="/devis/nouveau"
              className={`nav-cta ${location.pathname === '/devis/nouveau' ? 'is-active' : ''}`}
            >
              <i className="fas fa-plus-circle" />
              Nouveau devis
            </Link>
          </nav>
        </div>
      </header>

      <main className="site-main">
        <Outlet />
      </main>

      <footer className="site-footer">
        <div className="footer-inner">
          <span>© {new Date().getFullYear()} Aksam Assurance</span>
          <span className="footer-sep">·</span>
          <span>Devis auto & moto en ligne</span>
        </div>
      </footer>
    </>
  )
}
