
# Pré-compilação
compile:
	php bin/compile_images.php
	php bin/compile_templates.php
	php bin/compile_schema.php

# Empacotamento (depende de compile)
build: compile
	python bin/build.py

# Testes (depende de build)
test: build
	php bin/tests.php

# Coverage (depende de test)
coverage: test
	php bin/coverage.php /tmp/coverage.json > coverage.html

# Servidor de testes
serve: build
	php -S 0.0.0.0:8090 index.php

# Atalho para rodar tudo até coverage
all: coverage
