# Aksam Assurance — React Frontend

React SPA to replace the Symfony Twig frontend, while keeping Symfony as the backend (REST API, PDF generation, email, admin).

## Project Structure

```
frontend/              ← This Vite/React project
  src/
    pages/
      HomePage.jsx         ← Landing page
      QuoteWizardPage.jsx  ← 5-step quote form
      OffersPage.jsx       ← Insurance offers list
      QuoteShowPage.jsx    ← Quote recap + PDF/email
    components/
      Layout.jsx           ← Header + footer wrapper
    services/
      api.js               ← All Symfony API calls
    styles/
      global.css           ← Design tokens + shared styles
  index.html
  vite.config.js           ← Dev proxy + build output
  package.json
SYMFONY_API_ADDITIONS.php  ← New Symfony routes to add
nelmio_cors.yaml           ← CORS config
```

## Setup

### 1. Install dependencies

```bash
cd frontend
npm install
```

### 2. Start dev server

```bash
npm run dev
# → http://localhost:3000
# API calls proxy to http://localhost:8000 (your Symfony app)
```

### 3. Build for production

```bash
npm run build
# Output → ../public/react/
```

---

## Symfony Backend Changes Required

### A. Install CORS bundle

```bash
composer require nelmio/cors-bundle
```

Copy `nelmio_cors.yaml` → `config/packages/nelmio_cors.yaml`.

### B. Add new API endpoints

Open `SYMFONY_API_ADDITIONS.php` and follow the commented instructions. You need to add:

| Method | Route | Purpose |
|--------|-------|---------|
| GET    | `/api/config` | Return cities, companies, fuels, brands |
| GET    | `/api/quotes/{uuid}/offers` | Get offers (with optional `?company=` filter) |
| POST   | `/api/quotes/{uuid}/select-offer` | Select an offer |
| POST   | `/api/quotes/{uuid}/send-email` | Send recap email |
| GET    | `/api/quotes/{uuid}?token=` | Get quote by UUID with token auth |

**The simplest way:** copy the uncommented code blocks from `SYMFONY_API_ADDITIONS.php` into your existing `QuoteApiController.php`.

### C. Keep the PDF route as-is

The Twig PDF route `/devis/{uuid}/pdf` stays unchanged. React links to it directly — the browser handles the download.

### D. SPA fallback route (optional — for direct URL access)

If you want direct navigation to React URLs to work when accessed via Symfony (without the Vite dev server), add a catch-all in your controller:

```php
// src/Controller/Web/SpaController.php
#[Route('/{reactRoute}', name: 'react_spa', requirements: ['reactRoute' => '^(?!api|admin).*'], priority: -10)]
public function spa(): Response
{
    return $this->render('spa.html.twig');
}
```

```twig
{# templates/spa.html.twig #}
<!DOCTYPE html>
<html><head>
  <meta charset="UTF-8">
  <script type="module" src="/react/index.js"></script>
  <link rel="stylesheet" href="/react/index.css">
</head><body><div id="root"></div></body></html>
```

---

## API Contract

All requests include `?token=<accessToken>` (or `token` in POST body) for quote access.

### GET `/api/config`
```json
{
  "cities": [{"name": "Casablanca"}, ...],
  "companies": [{"name": "Wafa Assurance"}, ...],
  "fuelTypes": ["essence", "diesel", ...],
  "vehicleBrands": ["Toyota", "BMW", ...]
}
```

### GET `/api/quotes/{uuid}?token=`
```json
{ "data": { "uuid": "...", "firstName": "...", ... } }
```

### GET `/api/quotes/{uuid}/offers?token=&company=`
```json
{
  "quote": { ... },
  "offers": [{ "code": "tiers", "title": "...", "annual_price": 1200, "monthly_price": 100 }],
  "companies": [{"name": "..."}]
}
```

### POST `/api/quotes/{uuid}/select-offer`
Body: `{ "token": "...", "offer_code": "intermediaire", "company": "Wafa Assurance" }`

### POST `/api/quotes/{uuid}/send-email`
Body: `{ "token": "...", "email": "user@example.com" }`

---

## Tech Stack

- **React 18** + React Router v6
- **Vite** (dev server with API proxy, production build)
- **Fonts:** Syne (headings) + DM Sans (body)
- **Icons:** Font Awesome 6
- Zero external UI libraries — custom CSS design system

## Design Tokens

All colors, spacing, typography in `src/styles/global.css` as CSS variables (`--primary`, `--surface`, etc.). The palette mirrors the existing Symfony design for a consistent transition.
