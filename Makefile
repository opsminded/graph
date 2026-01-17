
# Pré-compilação
compile:
	php www/images/compile.php

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
serve:
	php -S 0.0.0.0:8090

# Atalho para rodar tudo até coverage
all: coverage
