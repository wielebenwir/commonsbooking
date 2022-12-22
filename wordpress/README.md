1. First build a custom wordpress image with composer included

```bash
docker build -t cbdev -f Dockerfile .
```

2. Use docker compose to start up network with database and wordpress webserver

```bash
docker-compose -f stack.yml up
```

3. Deploy plugin sources to wp plugin folder with docker cp

`wordpress_wordpress_1` is the name of container. 
You can call it also via script at `sh ./wordpress/deploy-plugin.sh`.

```bash
docker cp . wordpress_wordpress_1:/var/www/html/wp-content/plugins/commonsbooking
```

For an initial `composer install` in the plugin directory, login into the container with e.g. `docker exec -it $CONTAINERNAME /bin/bash`.

# Alternatively

Skip the custom image from step one and start the compose script using `wordpress:php7.4` image, instead of `cbdev`.
And install composer by yourself, e.g. via login into the container.