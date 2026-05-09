# ══════════════════════════════════════════════════════════════════════════════
# Aksam Assurance — Makefile
# Usage : make <commande>
# ══════════════════════════════════════════════════════════════════════════════

.PHONY: help build up down restart logs shell migrate cache admin

# Couleurs
GREEN  = \033[0;32m
YELLOW = \033[0;33m
BLUE   = \033[0;34m
NC     = \033[0m

help: ## Afficher cette aide
	@echo ""
	@echo "$(BLUE)Aksam Assurance — Commandes Docker$(NC)"
	@echo "────────────────────────────────────────"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ── Docker ────────────────────────────────────────────────────────────────────

build: ## Construire les images Docker
	docker compose build --no-cache

up: ## Démarrer tous les services (production)
	docker compose up -d
	@echo "$(GREEN)✅ Application démarrée sur http://localhost:$${APP_PORT:-8080}$(NC)"

dev: ## Démarrer en mode développement (avec Mailpit + live reload)
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
	@echo "$(GREEN)✅ Dev démarré sur http://localhost:8000$(NC)"
	@echo "$(YELLOW)📧 Mailpit UI : http://localhost:8025$(NC)"

down: ## Arrêter tous les services
	docker compose down

restart: ## Redémarrer tous les services
	docker compose restart

logs: ## Afficher les logs en temps réel
	docker compose logs -f

logs-app: ## Logs du container PHP
	docker compose logs -f app

logs-nginx: ## Logs du container Nginx
	docker compose logs -f nginx

# ── Application ───────────────────────────────────────────────────────────────

shell: ## Ouvrir un shell dans le container PHP
	docker compose exec app sh

migrate: ## Exécuter les migrations Doctrine
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✅ Migrations exécutées$(NC)"

cache: ## Vider et réchauffer le cache Symfony
	docker compose exec app php bin/console cache:clear
	docker compose exec app php bin/console cache:warmup
	@echo "$(GREEN)✅ Cache nettoyé$(NC)"

admin: ## Créer un compte administrateur (usage: make admin EMAIL=x NAME="x" PASS=x)
	docker compose exec app php bin/console app:create-admin "$(EMAIL)" "$(NAME)" "$(PASS)"

fix-estimations: ## Corriger les estimations manquantes
	docker compose exec app php bin/console app:fix-missing-estimations

composer: ## Installer les dépendances Composer
	docker compose exec app composer install

routes: ## Afficher toutes les routes
	docker compose exec app php bin/console debug:router

# ── Base de données ───────────────────────────────────────────────────────────

db-shell: ## Ouvrir un shell MariaDB
	docker compose exec db mariadb -u$${DB_USER:-aksam} -p$${DB_PASSWORD:-aksam_pass} $${DB_NAME:-aksam_assurance}

db-dump: ## Exporter la base de données
	docker compose exec db mariadb-dump -u$${DB_USER:-aksam} -p$${DB_PASSWORD:-aksam_pass} $${DB_NAME:-aksam_assurance} > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Dump créé$(NC)"

db-reset: ## Réinitialiser la base de données (⚠️ supprime toutes les données)
	docker compose exec app php bin/console doctrine:schema:drop --force
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(YELLOW)⚠️  Base réinitialisée$(NC)"

# ── Setup complet ─────────────────────────────────────────────────────────────

install: ## Installation complète (build + up + migrate + cache)
	@echo "$(BLUE)🚀 Installation d'Aksam Assurance...$(NC)"
	cp -n .env.docker .env.local || true
	docker compose build
	docker compose up -d
	@echo "$(YELLOW)⏳ Attente de MariaDB...$(NC)"
	sleep 15
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec app php bin/console cache:clear
	@echo ""
	@echo "$(GREEN)✅ Installation terminée !$(NC)"
	@echo "$(BLUE)   Application : http://localhost:$${APP_PORT:-8080}$(NC)"
	@echo "$(BLUE)   Admin       : http://localhost:$${APP_PORT:-8080}/admin$(NC)"
	@echo ""
	@echo "$(YELLOW)Créez votre compte admin :$(NC)"
	@echo "   make admin EMAIL=admin@aksam.ma NAME=\"Votre Nom\" PASS=votre_mot_de_passe"
