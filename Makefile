BUILD_DIR ?= build
PLUGIN_NAME ?= commonsbooking
PLUGIN_ZIP = $(BUILD_DIR)/$(PLUGIN_NAME).zip
PLUGIN_DIR = $(basename $(PLUGIN_ZIP))
PLUGIN_VERSION = $(shell git describe --tags --always --dirty || echo "unknown")

COMPOSER ?= composer
COMPOSER_INSTALL_ARGS ?= --no-dev --optimize-autoloader
NPM ?= npm
NPX ?= npx

DIST_FILES_ASSETS = $(shell find assets -type f -not -path "*/sass/*")
# These files are used in src/View/View.php, but are excluded by statement above (-not -path "*/sass/*").
DIST_FILES_ASSETS_OVERRIDE = $(shell find assets/global/sass/partials/_variables.scss assets/public/sass/partials/ -type f)
DIST_FILES_TRANSLATIONS = $(shell find languages -type f -name "*.mo")
DIST_FILES_OTHER = $(shell find includes/ screenshots/ templates/ src/ vendor/ commonsbooking.php index.php LICENSE.txt readme.txt -type f)
# These files are included in the distribution zip file.
DIST_FILES = $(DIST_FILES_ASSETS) $(DIST_FILES_ASSETS_OVERRIDE) $(DIST_FILES_TRANSLATIONS) $(DIST_FILES_OTHER)

# Any changes to any of these files not excluded by the patterns below
# will trigger a rebuild of the assets.
DEPS_ASSETS = $(shell find assets -type f -not \( \
	   -path 'assets/admin/css/*.css*' \
	-o -path 'assets/public/css/*.css*' \
	-o -path 'assets/public/css/themes/*.css*' \
	-o -path 'assets/public/js/public.*' \
	-o -path 'assets/admin/js/admin.*' \
	-o -path 'assets/global/js/vendor.*' \
	-o -path 'assets/packaged/*' \
\))

.PHONY: default-target
default-target: build

.PHONY: build
build: $(PLUGIN_ZIP)

node_modules: package.json package-lock.json
	ADBLOCK=true $(NPM) ci
	touch --no-create "$@"

vendor: composer.json composer.lock
	$(COMPOSER) install $(COMPOSER_INSTALL_ARGS)
	touch --no-create "$@"

assets: node_modules $(DEPS_ASSETS)
	$(NPX) grunt dist
	touch --no-create "$@"

languages/%.mo: languages/%.po
	msgfmt -o "$@" "$<"

.PHONY: translations
translations: $(patsubst languages/%.po,languages/%.mo,$(wildcard languages/*.po))

$(PLUGIN_ZIP): assets vendor translations $(DIST_FILES)
	mkdir -p "$(PLUGIN_DIR)"
	for file in $(DIST_FILES); do install -D -m 644 "$$file" "$(PLUGIN_DIR)/$$file"; done
	sed -Ei \
		-e "s/^ \* Version: .*/ * Version:             $(PLUGIN_VERSION)/" \
		-e "s/define\('COMMONSBOOKING_VERSION', '[^']+'\);/define('COMMONSBOOKING_VERSION', '$(PLUGIN_VERSION)');/" \
		"$(PLUGIN_DIR)/commonsbooking.php"
	(cd "$(dir $(PLUGIN_DIR))"; zip -r "$(notdir $(PLUGIN_ZIP))" "$(notdir $(PLUGIN_DIR))")
	rm -rf "$(PLUGIN_DIR)"

.PHONY: clean
clean:
	rm -rf \
		node_modules/ \
		vendor/ \
		languages/*.mo \
		assets/admin/css/*.css* \
		assets/public/css/*.css* \
		assets/public/css/themes/*.css* \
		assets/public/js/public.* \
		assets/admin/js/admin.* \
		assets/global/js/vendor.* \
		$$(find assets/packaged/ -mindepth 1 -not -name ".gitignore") \
		"$(PLUGIN_ZIP)" \
		"$(BUILD_DIR)"

.PHONY: build-in-docker
build-in-docker:
	docker build . \
		--file docker/build.Dockerfile \
		--output="$(BUILD_DIR)" \
		--target=dist \
		--build-arg="uid=$$(id -u)" \
		--build-arg="gid=$$(id -g)" \
		--build-arg="dist_name=$(notdir $(PLUGIN_ZIP))"