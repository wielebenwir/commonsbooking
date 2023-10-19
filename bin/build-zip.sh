#!/bin/sh
# Borrows from https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/bin/build-zip.sh

PLUGIN_SLUG="commonsbooking"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"
SKIP_ZIP=0


# Parse command line options
while [ "$#" -gt 0 ]; do
  case "$1" in
    --skip-zip)
      SKIP_ZIP=1
      shift
      ;;
    *)
      echo "Invalid option: $1"
      exit 1
      ;;
  esac
done

echo "Generating build directory..."
rm -rf "$BUILD_PATH"
mkdir -p "$DEST_PATH"

echo "Installing PHP and JS dependencies..."
npm ci
echo "Running JS Build..."
grunt dist || exit "$?"
echo "Cleaning up PHP dependencies..."
composer install --no-dev --ignore-platform-reqs || exit "$?"
echo "Syncing files..."
rsync -rc --exclude-from="$PROJECT_PATH/.distignore" "$PROJECT_PATH/" "$DEST_PATH/" --delete --delete-excluded

if [ "$SKIP_ZIP" -eq 1 ]; then
  echo "Build done! (Skipped zip file generation)"
  exit 0
fi

echo "Generating zip file..."
cd "$BUILD_PATH" || exit
zip -q -r "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG/"

cd "$PROJECT_PATH" || exit
mv "$BUILD_PATH/${PLUGIN_SLUG}.zip" "$PROJECT_PATH"
echo "${PLUGIN_SLUG}.zip file generated!"

echo "Build done!"
