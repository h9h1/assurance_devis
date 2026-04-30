#!/bin/bash
# =============================================================================
# Script de patch — Feature : Envoi du devis par email
# Usage : bash apply_email_feature.sh /chemin/vers/assur_quote_symfony
# =============================================================================

set -e

PROJECT="${1:-.}"

if [ ! -f "$PROJECT/src/Kernel.php" ]; then
    echo "❌  Dossier projet introuvable : $PROJECT"
    exit 1
fi

echo "📁  Projet : $PROJECT"
echo ""

# ─── 1. Installer symfony/mailer ──────────────────────────────────────────────
echo "📦  [1/7] Installation de symfony/mailer..."
cd "$PROJECT" && composer require symfony/mailer --no-interaction -q
echo "  → symfony/mailer installé"

# ─── 2. Ajouter le champ email dans l'entité Quote ────────────────────────────
echo "🔧  [2/7] Ajout du champ email dans Quote.php"

python3 - "$PROJECT/src/Entity/Quote.php" << 'PYEOF'
import sys, re

path = sys.argv[1]
src  = open(path).read()

if 'private ?string $email' in src:
    print("  → Champ email déjà présent, ignoré")
    sys.exit(0)

# Ajouter la propriété après phoneNumber
prop = """
    #[ORM\\Column(length: 180, nullable: true)]
    private ?string $email = null;
"""
src = src.replace(
    'private string $phoneNumber;',
    'private string $phoneNumber;\n' + prop
)

# Ajouter getter/setter après getPhoneNumber/setPhoneNumber
methods = """
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
"""
# Insérer après le setter de phoneNumber
src = re.sub(
    r'(public function setPhoneNumber[^}]+\})',
    r'\1\n' + methods,
    src,
    flags=re.DOTALL
)

open(path, 'w').write(src)
print("  → Champ email ajouté à Quote.php")
PYEOF

# ─── 3. Migration ─────────────────────────────────────────────────────────────
echo "✅  [3/7] Création de la migration Version20260430000000.php"
cat > "$PROJECT/migrations/Version20260430000000.php" << 'PHPEOF'
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430000000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add email column to quotes table'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes ADD email VARCHAR(180) NULL AFTER phone_number');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes DROP COLUMN email');
    }
}
PHPEOF

# ─── 4. Service QuoteMailerService ────────────────────────────────────────────
echo "✅  [4/7] Création de src/Service/QuoteMailerService.php"
cat > "$PROJECT/src/Service/QuoteMailerService.php" << 'PHPEOF'
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class QuoteMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderEmail = 'noreply@aksam-assurance.ma',
        private readonly string $senderName  = 'Aksam Assurance',
    ) {}

    public function sendRecap(Quote $quote): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($quote->getEmail(), $quote->getFirstName() . ' ' . $quote->getLastName()))
            ->replyTo(new Address('contact@aksam-assurance.ma', $this->senderName))
            ->subject('Votre devis #' . $quote->getId() . ' — Aksam Assurance')
            ->htmlTemplate('emails/quote_recap.html.twig')
            ->context([
                'quote'       => $quote,
                'companyName' => $this->resolveCompanyName($quote),
                'cityName'    => $this->resolveCityName($quote),
                'estimation'  => $quote->getCustomEstimation()
                    ? number_format((float) $quote->getCustomEstimation(), 2, ',', ' ') . ' MAD / an'
                    : null,
                'monthly'     => $quote->getCustomEstimation()
                    ? number_format((float) $quote->getCustomEstimation() / 12, 2, ',', ' ') . ' MAD / mois'
                    : null,
            ]);

        $this->mailer->send($email);
    }

    private function resolveCompanyName(Quote $quote): string
    {
        if ($quote->getCompanyEntity()) return $quote->getCompanyEntity()->getName();
        if ($quote->getCompany())       return $quote->getCompany()->value;
        return '';
    }

    private function resolveCityName(Quote $quote): string
    {
        if ($quote->getCityEntity()) return $quote->getCityEntity()->getName();
        if ($quote->getCity())       return $quote->getCity()->value;
        return '';
    }
}
PHPEOF

# ─── 5. Ajouter la route sendEmail dans QuoteWizardController ─────────────────
echo "🔧  [5/7] Ajout de la route sendEmail dans QuoteWizardController"

python3 - "$PROJECT/src/Controller/Web/QuoteWizardController.php" << 'PYEOF'
import sys

path = sys.argv[1]
src  = open(path).read()

if 'sendEmail' in src:
    print("  → Route sendEmail déjà présente, ignorée")
    sys.exit(0)

# Ajouter l'import QuoteMailerService si absent
if 'QuoteMailerService' not in src:
    src = src.replace(
        'use App\\Service\\QuoteMapper;',
        'use App\\Service\\QuoteMapper;\nuse App\\Service\\QuoteMailerService;'
    )

if 'EntityManagerInterface' not in src:
    src = src.replace(
        'use Doctrine\\ORM\\EntityManagerInterface;',
        'use Doctrine\\ORM\\EntityManagerInterface;\n'
    )

# Ajouter la méthode juste avant la dernière accolade du fichier
new_method = """
    #[Route('/devis/{id}/envoyer-email', name: 'quote_send_email', requirements: ['id' => '\\\\d+'], methods: ['POST'])]
    public function sendEmail(
        Quote $quote,
        Request $request,
        EntityManagerInterface $em,
        QuoteMailerService $mailer,
    ): Response {
        $email = trim($request->request->get('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide.');
            return $this->redirectToRoute('quote_show', ['id' => $quote->getId()]);
        }

        $quote->setEmail($email);
        $em->flush();

        try {
            $mailer->sendRecap($quote);
            $this->addFlash('success', 'Le récapitulatif a été envoyé à ' . $email . '.');
        } catch (\\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('quote_show', ['id' => $quote->getId()]);
    }
"""

# Insérer avant la dernière accolade du fichier
last_brace = src.rfind('}')
src = src[:last_brace] + new_method + '\n' + src[last_brace:]

open(path, 'w').write(src)
print("  → Méthode sendEmail ajoutée")
PYEOF

# ─── 6. Template email ────────────────────────────────────────────────────────
echo "✅  [6/7] Création de templates/emails/quote_recap.html.twig"
mkdir -p "$PROJECT/templates/emails"

if [ -f "quote_recap.html.twig" ]; then
    cp quote_recap.html.twig "$PROJECT/templates/emails/quote_recap.html.twig"
    echo "  → Template copié depuis quote_recap.html.twig"
else
    echo "  ⚠️  Copie manuelle requise :"
    echo "      cp quote_recap.html.twig $PROJECT/templates/emails/"
fi

# ─── 7. Ajouter le bouton + modal dans show.html.twig ─────────────────────────
echo "🔧  [7/7] Ajout du bouton email dans templates/quote/show.html.twig"

python3 - "$PROJECT/templates/quote/show.html.twig" << 'PYEOF'
import sys

path = sys.argv[1]
src  = open(path).read()

if 'quote_send_email' in src:
    print("  → Bouton email déjà présent, ignoré")
    sys.exit(0)

email_btn = """
        <button type="button" class="btn btn-email" onclick="document.getElementById('emailModal').style.display='flex'">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,12 2,6"></polyline></svg>
            Recevoir par email
        </button>"""

email_modal = """
{# ── Email Modal ───────────────────────────────────────────────────────────── #}
<div id="emailModal" style="display:none;position:fixed;inset:0;background:rgba(18,32,51,.5);z-index:9999;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(4px);">
    <div style="background:#fff;border-radius:18px;box-shadow:0 32px 64px rgba(15,48,87,.18);padding:40px;max-width:460px;width:100%;position:relative;animation:modalIn .25s ease;">
        <button onclick="document.getElementById('emailModal').style.display='none'"
                style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;color:#607086;font-size:1.2rem;line-height:1;">✕</button>

        <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#e3f2fd,#e0e7ff);display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:20px;">📧</div>

        <h3 style="margin:0 0 8px;font-size:1.3rem;font-weight:700;color:#122033;">Recevoir le récapitulatif</h3>
        <p style="margin:0 0 24px;color:#607086;font-size:.9rem;line-height:1.6;">
            Entrez votre adresse email pour recevoir le récapitulatif complet de votre devis <strong>#{{ quote.id }}</strong>.
        </p>

        <form method="POST" action="{{ path('quote_send_email', {id: quote.id}) }}">
            <input type="hidden" name="_token" value="{{ csrf_token('send_email_' ~ quote.id) }}">

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:.875rem;font-weight:600;color:#122033;margin-bottom:8px;">Adresse email</label>
                <div style="position:relative;">
                    <input type="email" name="email"
                           value="{{ quote.email ?? '' }}"
                           required
                           placeholder="votre@email.com"
                           style="width:100%;padding:13px 16px 13px 42px;border:2px solid #dbe4ee;border-radius:12px;font-size:1rem;color:#122033;background:#fff;outline:none;font-family:inherit;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#0f62fe';this.style.boxShadow='0 0 0 4px #e3f2fd'"
                           onblur="this.style.borderColor='#dbe4ee';this.style.boxShadow='none'">
                    <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#607086;">✉️</span>
                </div>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit"
                        style="flex:1;background:linear-gradient(135deg,#0f62fe,#0043ce);color:white;border:none;border-radius:12px;padding:13px 20px;font-size:.95rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                    📨 Envoyer
                </button>
                <button type="button"
                        onclick="document.getElementById('emailModal').style.display='none'"
                        style="padding:13px 20px;background:#f8fafc;color:#607086;border:1px solid #dbe4ee;border-radius:12px;font-size:.95rem;font-weight:600;cursor:pointer;">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes modalIn {
    from { opacity:0; transform:scale(.95) translateY(8px); }
    to   { opacity:1; transform:scale(1)   translateY(0);   }
}
.btn-email {
    background: linear-gradient(135deg, #12805c, #0d6e4f);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 12px 20px;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all .3s ease;
    text-decoration: none;
}
.btn-email:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(18,128,92,.3); }
</style>
{% endblock %}"""

# Insérer le bouton dans actions-container avant la fermeture
src = src.replace(
    '<a href="{{ path(\'quote_offers\', {id: quote.id}) }}" class="btn btn-primary">',
    email_btn + '\n\t\t<a href="{{ path(\'quote_offers\', {id: quote.id}) }}" class="btn btn-primary">'
)

# Insérer le modal avant {% endblock %}
if '{% endblock %}' in src:
    src = src.replace('{% endblock %}', email_modal, 1)

open(path, 'w').write(src)
print("  → Bouton + modal ajoutés dans show.html.twig")
PYEOF

# ─── Fin ──────────────────────────────────────────────────────────────────────
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅  Patch terminé. Lance :"
echo ""
echo "   php bin/console doctrine:migrations:migrate"
echo "   php bin/console cache:clear"
echo ""
echo "📌  Config SMTP dans .env :"
echo "   MAILER_DSN=smtp://user:pass@smtp.exemple.com:587"
echo ""
echo "   Pour tester en local sans vrai SMTP :"
echo "   MAILER_DSN=null://null   (log only, pas d'envoi)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
