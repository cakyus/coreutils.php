SOURCES = $(wildcard src/*.php)
TESTS = $(subst src/,.build/,$(SOURCES))

.PHONY:test
test: .build/.gitignore $(TESTS)

.build/%.php: src/%.php
	cp $^ $@
	php -l $@

.build/.gitignore:
	mkdir -p .build
	echo '*' > .build/.gitignore

.PHONY:clean
clean:
	rm -rf .build

