.DEFAULT_GOAL := help

help:
	@echo "Usage: make [command]"
	@echo ""
	@echo "Available commands:"
	@echo "  install     Install dependencies"
	@echo "  lint        Lint PHP files"
	@echo "  clean       Clean up generated files"
	@echo ""

install:
	composer install

lint:
	@echo "Linting PHP files..."
	@find . -name '*.php' -exec php -l {} \; | grep -v "No syntax errors detected"

clean:
	@echo "Cleaning up..."
	rm -rf out/*

.PHONY: help install lint test clean