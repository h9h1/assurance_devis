import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'

// Fonts — served locally, no CDN dependency
import '@fontsource/syne/400.css'
import '@fontsource/syne/500.css'
import '@fontsource/syne/600.css'
import '@fontsource/syne/700.css'
import '@fontsource/syne/800.css'
import '@fontsource/dm-sans/300.css'
import '@fontsource/dm-sans/400.css'
import '@fontsource/dm-sans/500.css'

// Icons — served locally, no CDN dependency
import '@fortawesome/fontawesome-free/css/all.min.css'

import './styles/global.css'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
)
