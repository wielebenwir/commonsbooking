# Installation

## Using The WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'commonsbooking'
3. Click 'Install Now'
4. Activate the plugin in the plugins dashboard

## Uploading in WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `commonsbooking.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the plugins dashboard

## Using FTP

1. Download `commonsbooking.zip`
2. Extract the `commonsbooking` directory to your computer
3. Upload the `commonsbooking` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the plugins dashboard

## Using GitHub (developers only)

1. Make sure that composer is installed on your system
2. Navigate into your wp-content/plugins directory
3. Open a terminal and run `git clone https://github.com/wielebenwir/commonsbooking`
4. cd into the directory commonsbooking and run `npm start`
> This might fail, if you don't have the PHP extension [uopz](https://www.php.net/manual/en/book.uopz.php) installed. Try running `composer install --no-dev && npm install && npm run dist`  if you just quickly want to test a specific branch without installing the extension.
5. Activate the plugin in the plugins dashboard
