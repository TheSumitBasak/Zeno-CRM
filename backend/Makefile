CONTAINER  = zeno-backend
PHP        = php
PHPUNIT    = $(PHP) vendor/bin/phpunit
COMPOSER   = composer

# Base URL used inside the container (backend serves on port 80 internally)
INTERNAL_URL = http://localhost

.PHONY: test test-filter test-coverage install help

help:
	@echo "Zeno CRM Backend — Test commands"
	@echo ""
	@echo "  make test                  Run full test suite (inside Docker container)"
	@echo "  make test-filter F=Auth    Run tests matching a name/class (e.g. F=AccountTest)"
	@echo "  make test-coverage         Run tests with text coverage report"
	@echo "  make install               Install Composer dependencies inside the container"
	@echo ""
	@echo "The backend container must be running: docker compose up -d backend db"

## Install Composer dependencies inside the backend container
install:
	docker exec $(CONTAINER) bash -c "cd /var/www/html && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && composer install"

## Run full test suite inside the running backend container
test:
	docker exec -e TEST_BASE_URL=$(INTERNAL_URL) $(CONTAINER) \
		bash -c "cd /var/www/html && $(PHPUNIT) --colors=always"

## Run tests matching a class or method name
## Usage: make test-filter F=AccountTest
test-filter:
	docker exec -e TEST_BASE_URL=$(INTERNAL_URL) $(CONTAINER) \
		bash -c "cd /var/www/html && $(PHPUNIT) --colors=always --filter=$(F)"

## Run tests with coverage report (requires Xdebug or PCOV in the container)
test-coverage:
	docker exec -e TEST_BASE_URL=$(INTERNAL_URL) $(CONTAINER) \
		bash -c "cd /var/www/html && $(PHPUNIT) --coverage-text"
