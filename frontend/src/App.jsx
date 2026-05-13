import React from 'react'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Layout from './components/Layout.jsx'
import HomePage from './pages/HomePage.jsx'
import QuoteWizardPage from './pages/QuoteWizardPage.jsx'
import OffersPage from './pages/OffersPage.jsx'
import QuoteShowPage from './pages/QuoteShowPage.jsx'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/devis/nouveau" element={<QuoteWizardPage />} />
          <Route path="/devis/:uuid/offres" element={<OffersPage />} />
          <Route path="/devis/:uuid" element={<QuoteShowPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
