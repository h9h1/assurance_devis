#!/bin/bash
# =============================================================================
# Patch — Ajout bouton email + modal dans templates/quote/show.html.twig
# Usage : bash apply_email_modal.sh /chemin/vers/assur_quote_symfony
# =============================================================================

set -e

PROJECT="${1:-.}"
SHOW="$PROJECT/templates/quote/show.html.twig"

if [ ! -f "$SHOW" ]; then
    echo "❌  Fichier introuvable : $SHOW"
    exit 1
fi

if grep -q "quote_send_email" "$SHOW"; then
    echo "✅  Bouton email déjà présent, rien à faire."
    exit 0
fi

echo "🔧  Patch de $SHOW"

python3 - "$SHOW" << 'PYEOF'
import sys

path = sys.argv[1]
src  = open(path).read()

# ── 1. Insérer le bouton email dans actions-container ───────────────────────
email_btn = """\t\t<button type="button" class="btn btn-email" id="openEmailModal">
\t\t\t<i class="fas fa-envelope"></i>
\t\t\tRecevoir par email
\t\t</button>
"""

# Insérer avant le bouton "Voir les offres"
target = '\t\t<a href="{{ path(\'quote_offers\', {id: quote.id}) }}" class="btn btn-primary">'
if target in src:
    src = src.replace(target, email_btn + target)
    print("  → Bouton email inséré dans actions-container")
else:
    print("  ⚠️  Marqueur actions-container non trouvé — insertion avant </style>{% endblock %}")
    src = src.replace('</style>\n{% endblock %}', email_btn + '\n\t</style>\n{% endblock %}')

# ── 2. Insérer modal + styles + JS juste avant {% endblock %} ───────────────
modal_and_js = """
{# ── Modal Email ────────────────────────────────────────────────────────── #}
<div id="emailModal" class="modal-overlay" style="display:none;">
\t<div class="modal-card">

\t\t<button class="modal-close" id="closeEmailModal" aria-label="Fermer">
\t\t\t<i class="fas fa-times"></i>
\t\t</button>

\t\t<div class="modal-icon">
\t\t\t<i class="fas fa-envelope"></i>
\t\t</div>

\t\t<h3 class="modal-title">Recevoir le récapitulatif</h3>
\t\t<p class="modal-desc">
\t\t\tEntrez votre adresse email pour recevoir le récapitulatif complet de votre devis <strong>#{{ quote.id }}</strong>.
\t\t</p>

\t\t<form method="POST" action="{{ path('quote_send_email', {id: quote.id}) }}">

\t\t\t<div class="modal-field">
\t\t\t\t<label for="modalEmail">Adresse email</label>
\t\t\t\t<div class="modal-input-wrap">
\t\t\t\t\t<i class="fas fa-at modal-input-icon"></i>
\t\t\t\t\t<input
\t\t\t\t\t\ttype="email"
\t\t\t\t\t\tid="modalEmail"
\t\t\t\t\t\tname="email"
\t\t\t\t\t\tvalue="{{ quote.email ?? '' }}"
\t\t\t\t\t\trequired
\t\t\t\t\t\tautocomplete="email"
\t\t\t\t\t\tplaceholder="votre@email.com"
\t\t\t\t\t>
\t\t\t\t</div>
\t\t\t</div>

\t\t\t<div class="modal-actions">
\t\t\t\t<button type="submit" class="btn btn-primary">
\t\t\t\t\t<i class="fas fa-paper-plane"></i>
\t\t\t\t\tEnvoyer
\t\t\t\t</button>
\t\t\t\t<button type="button" class="btn btn-secondary" id="cancelEmailModal">
\t\t\t\t\tAnnuler
\t\t\t\t</button>
\t\t\t</div>

\t\t</form>
\t</div>
</div>

<style>
\t/* ── Email button ── */
\t.btn-email {
\t\tbackground: linear-gradient(135deg, #12805c, #0d6e4f);
\t\tcolor: white;
\t\tborder: none;
\t\tborder-radius: 10px;
\t\tpadding: 12px 20px;
\t\tfont-size: .9rem;
\t\tfont-weight: 600;
\t\tcursor: pointer;
\t\tdisplay: inline-flex;
\t\talign-items: center;
\t\tgap: 8px;
\t\ttransition: all .3s ease;
\t\ttext-decoration: none;
\t}
\t.btn-email:hover {
\t\ttransform: translateY(-2px);
\t\tbox-shadow: 0 8px 20px rgba(18,128,92,.3);
\t}

\t/* ── Modal overlay ── */
\t.modal-overlay {
\t\tposition: fixed;
\t\tinset: 0;
\t\tbackground: rgba(18,32,51,.55);
\t\tz-index: 9999;
\t\talign-items: center;
\t\tjustify-content: center;
\t\tpadding: 24px;
\t\tbackdrop-filter: blur(4px);
\t}

\t/* ── Modal card ── */
\t.modal-card {
\t\tbackground: #ffffff;
\t\tborder-radius: 18px;
\t\tbox-shadow: 0 32px 64px rgba(15,48,87,.18);
\t\tpadding: 40px;
\t\tmax-width: 460px;
\t\twidth: 100%;
\t\tposition: relative;
\t\tanimation: modalIn .25s ease;
\t}

\t@keyframes modalIn {
\t\tfrom { opacity:0; transform: scale(.95) translateY(8px); }
\t\tto   { opacity:1; transform: scale(1)   translateY(0);   }
\t}

\t.modal-close {
\t\tposition: absolute;
\t\ttop: 16px; right: 16px;
\t\tbackground: #f4f7fb;
\t\tborder: 1px solid #dbe4ee;
\t\tborder-radius: 8px;
\t\twidth: 32px; height: 32px;
\t\tdisplay: flex; align-items: center; justify-content: center;
\t\tcursor: pointer;
\t\tcolor: #607086;
\t\tfont-size: .85rem;
\t\ttransition: all .2s;
\t}
\t.modal-close:hover { background: #dbe4ee; color: #122033; }

\t.modal-icon {
\t\twidth: 56px; height: 56px;
\t\tborder-radius: 14px;
\t\tbackground: linear-gradient(135deg, #e3f2fd, #e0e7ff);
\t\tcolor: #0f62fe;
\t\tdisplay: flex; align-items: center; justify-content: center;
\t\tfont-size: 1.4rem;
\t\tmargin-bottom: 20px;
\t}

\t.modal-title {
\t\tfont-size: 1.3rem;
\t\tfont-weight: 700;
\t\tcolor: #122033;
\t\tmargin: 0 0 8px;
\t}

\t.modal-desc {
\t\tcolor: #607086;
\t\tfont-size: .9rem;
\t\tline-height: 1.6;
\t\tmargin: 0 0 24px;
\t}

\t.modal-field {
\t\tmargin-bottom: 20px;
\t}

\t.modal-field label {
\t\tdisplay: block;
\t\tfont-size: .875rem;
\t\tfont-weight: 600;
\t\tcolor: #122033;
\t\tmargin-bottom: 8px;
\t}

\t.modal-input-wrap {
\t\tposition: relative;
\t}

\t.modal-input-icon {
\t\tposition: absolute;
\t\tleft: 14px;
\t\ttop: 50%;
\t\ttransform: translateY(-50%);
\t\tcolor: #607086;
\t\tpointer-events: none;
\t}

\t.modal-input-wrap input {
\t\twidth: 100%;
\t\tpadding: 13px 16px 13px 42px;
\t\tborder: 2px solid #dbe4ee;
\t\tborder-radius: 12px;
\t\tfont-size: 1rem;
\t\tcolor: #122033;
\t\tbackground: #ffffff;
\t\toutline: none;
\t\tfont-family: inherit;
\t\tbox-sizing: border-box;
\t\ttransition: border-color .2s, box-shadow .2s;
\t}

\t.modal-input-wrap input:focus {
\t\tborder-color: #0f62fe;
\t\tbox-shadow: 0 0 0 4px #e3f2fd;
\t}

\t.modal-actions {
\t\tdisplay: flex;
\t\tgap: 12px;
\t}

\t.modal-actions .btn {
\t\tflex: 1;
\t\tjustify-content: center;
\t}
</style>

<script>
(function () {
\tvar modal   = document.getElementById('emailModal');
\tvar openBtn = document.getElementById('openEmailModal');
\tvar closeBtn = document.getElementById('closeEmailModal');
\tvar cancelBtn = document.getElementById('cancelEmailModal');
\tvar emailInput = document.getElementById('modalEmail');

\tfunction openModal() {
\t\tmodal.style.display = 'flex';
\t\tsetTimeout(function() { emailInput.focus(); }, 100);
\t}

\tfunction closeModal() {
\t\tmodal.style.display = 'none';
\t}

\tif (openBtn)   openBtn.addEventListener('click', openModal);
\tif (closeBtn)  closeBtn.addEventListener('click', closeModal);
\tif (cancelBtn) cancelBtn.addEventListener('click', closeModal);

\t// Fermer en cliquant sur l'overlay
\tmodal.addEventListener('click', function(e) {
\t\tif (e.target === modal) closeModal();
\t});

\t// Fermer avec Escape
\tdocument.addEventListener('keydown', function(e) {
\t\tif (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
\t});
})();
</script>
"""

# Insérer juste avant le dernier {% endblock %}
last_endblock = src.rfind('{% endblock %}')
if last_endblock != -1:
    src = src[:last_endblock] + modal_and_js + '\n{% endblock %}'
    print("  → Modal + styles + JS insérés avant {% endblock %}")
else:
    src += modal_and_js
    print("  → Modal ajouté en fin de fichier")

open(path, 'w').write(src)
print("  ✅ Patch terminé avec succès")
PYEOF

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅  Patch terminé."
echo "   php bin/console cache:clear"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
