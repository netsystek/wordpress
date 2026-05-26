# Makerfile for easy launch

.PHONY: up down

.DEFAULT_GOAL := help

up: ## Start containers
	docker compose up -d

down: ## Stop and remove containers
	docker compose down

.DEFAULT_GOAL := help

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

#############################
# To avoid ${ARGS} errors
#############################

%::
	@: