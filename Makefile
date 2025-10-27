.PHONY: help install dev-up dev-down dev-restart dev-logs dev-shell setup-db setup-rabbitmq test-data start-api start-workers stop-workers clean prod-up prod-down prod-logs restart r restart-api restart-workers restart-db restart-rabbitmq

help: ## Mostrar esta ajuda
	@echo "GrowHub Gateway - Comandos disponíveis:"
	@echo ""
	@echo "💡 ATALHOS RÁPIDOS:"
	@echo "  make r          - Reiniciar tudo (atalho para restart)"
	@echo "  make restart    - Reiniciar todos os containers"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Instalar dependências
	composer install

dev-up: ## Subir ambiente de desenvolvimento (Docker completo)
	docker-compose -f docker-compose.dev.yml up -d --build
	@echo "✓ Containers iniciados!"
	@echo "API disponível em: http://localhost:8000"

dev-down: ## Parar ambiente de desenvolvimento
	docker-compose -f docker-compose.dev.yml down

dev-restart: ## Reiniciar ambiente de desenvolvimento
	docker-compose -f docker-compose.dev.yml restart

dev-logs: ## Ver logs de todos os serviços
	docker-compose -f docker-compose.dev.yml logs -f

dev-logs-api: ## Ver logs da API (nginx + php-fpm)
	docker-compose -f docker-compose.dev.yml logs -f nginx php-fpm

dev-logs-workers: ## Ver logs dos workers
	docker-compose -f docker-compose.dev.yml logs -f worker-outbound worker-inbound worker-outbox worker-health

dev-logs-webhooks: ## Ver logs de webhooks recebidos
	@echo "Monitorando webhooks recebidos..."
	@tail -f logs/app-*.log | grep --line-buffered "webhook"

dev-shell: ## Acessar shell do container PHP
	docker-compose -f docker-compose.dev.yml exec php-fpm sh

dev-shell-db: ## Acessar shell do PostgreSQL
	docker-compose -f docker-compose.dev.yml exec postgres psql -U postgres -d growhub_gateway

dev-setup: ## Setup completo do ambiente Docker
	chmod +x scripts/docker-setup.sh
	./scripts/docker-setup.sh

setup-rabbitmq: ## Configurar RabbitMQ (via Docker)
	docker-compose -f docker-compose.dev.yml exec php-fpm php config/rabbitmq_setup.php

seed-admin: ## Criar superadmin (via Docker)
	docker-compose -f docker-compose.dev.yml exec php-fpm php scripts/seed-superadmin.php

test-data: ## Criar dados de teste (via Docker)
	docker-compose -f docker-compose.dev.yml exec php-fpm php scripts/create-test-data.php

dev-rebuild: ## Rebuild das imagens Docker
	docker-compose -f docker-compose.dev.yml build --no-cache

dev-ps: ## Ver status dos containers
	docker-compose -f docker-compose.dev.yml ps

clean: ## Limpar cache e logs
	rm -f logs/*.log
	rm -f tmp/pids/*.pid
	@echo "✓ Cache e logs limpos!"

prod-up: ## Subir ambiente de produção
	docker-compose -f docker-compose.prod.yml up -d

prod-down: ## Parar ambiente de produção
	docker-compose -f docker-compose.prod.yml down

prod-logs: ## Ver logs de produção
	docker-compose -f docker-compose.prod.yml logs -f

prod-shell: ## Acessar shell do container PHP (produção)
	docker-compose -f docker-compose.prod.yml exec php-fpm sh

quick-start: dev-setup ## Setup completo automatizado (Docker)
	@echo ""
	@echo "Ambiente pronto! 🎉"
	@echo ""

lint: ## Verificar erros de sintaxe PHP
	find src -name "*.php" -exec php -l {} \;
	find workers -name "*.php" -exec php -l {} \;
	find config -name "*.php" -exec php -l {} \;

postman: ## Gerar collection Postman combinada
	@echo "Gerando collection Postman..."
	@cd postman && bash combine-collections.sh
	@echo "✓ Collection gerada: postman/GrowHub-Gateway.postman_collection.json"

ps: ## Mostrar status dos containers
	docker-compose -f docker-compose.dev.yml ps

# ========================================
# ATALHOS RÁPIDOS DE RESTART
# ========================================

restart: dev-restart ## 🔄 Reiniciar todos os containers (atalho)
	@echo "✓ Todos os containers reiniciados!"

r: restart ## 🚀 Atalho super rápido para restart

restart-api: ## 🔄 Reiniciar apenas API (nginx + php-fpm)
	@echo "Reiniciando API..."
	docker-compose -f docker-compose.dev.yml restart nginx php-fpm
	@echo "✓ API reiniciada!"

restart-workers: ## 🔄 Reiniciar apenas workers
	@echo "Reiniciando workers..."
	docker-compose -f docker-compose.dev.yml restart worker-outbound worker-inbound worker-outbox worker-health
	@echo "✓ Workers reiniciados!"

restart-db: ## 🔄 Reiniciar apenas PostgreSQL
	@echo "Reiniciando banco de dados..."
	docker-compose -f docker-compose.dev.yml restart postgres
	@echo "✓ Banco de dados reiniciado!"

restart-rabbitmq: ## 🔄 Reiniciar apenas RabbitMQ
	@echo "Reiniciando RabbitMQ..."
	docker-compose -f docker-compose.dev.yml restart rabbitmq
	@echo "✓ RabbitMQ reiniciado!"

restart-redis: ## 🔄 Reiniciar apenas Redis
	@echo "Reiniciando Redis..."
	docker-compose -f docker-compose.dev.yml restart redis
	@echo "✓ Redis reiniciado!"

