.PHONY: help cs-fix cs-check psalm-check psalm-fix all-check all-fix

# Default target
help:
	@echo "Available commands:"
	@echo "  cs-check    - Run PHP CodeSniffer to check code style"
	@echo "  cs-fix      - Run PHP CS Fixer to fix code style"
	@echo "  psalm-check - Run Psalm to check code quality"
	@echo "  psalm-fix   - Run Psalm to fix code quality issues"
	@echo "  all-check   - Run all code quality checks"
	@echo "  all-fix     - Run all code quality fixes"

# PHP CodeSniffer - check code style
cs-check:
	@echo "Running PHP CodeSniffer..."
	./vendor/bin/phpcs --standard=phpcs.xml

# PHP CS Fixer - fix code style
cs-fix:
	@echo "Running PHP CS Fixer..."
	./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php

# Psalm - check code quality
psalm-check:
	@echo "Running Psalm..."
	./vendor/bin/psalm --config=psalm.xml

# Psalm - fix code quality issues
psalm-fix:
	@echo "Running Psalm with auto-fixes..."
	./vendor/bin/psalm --config=psalm.xml --alter --issues=all

# Run all checks
all-check: cs-check psalm-check
	@echo "All code quality checks completed!"

# Run all fixes
all-fix: cs-fix psalm-fix
	@echo "All code quality fixes completed!" 