To contribute to Contacts BMLT, fork, make your changes and send a pull request to the main branch.

Take a look at the issues for bugs that you might be able to help fix.

Once your pull request is merged it will be released in the next version.

We are using [bmlt-wordpress-deploy](https://github.com/bmlt-enabled/bmlt-wordpress-deploy/blob/master/README.md) to deploy the plugin to SVN.

To get things going in your local environment.

`docker-compose up`

Get your wordpress installation going.  Remember your admin password.  Once it's up, login to admin and activate the "Contacts BMLT" plugin.

Now you can make edits to the contacts-bmlt.php file and it will instantly take effect.

Please make note of the .editorconfig file and adhere to it as this will minimise the amount of formatting errors.  If you are using PHPStorm you will need to install the EditorConfig plugin.

## Linting and tests

`make lint` runs PHP CodeSniffer.

`make test` runs the PHPUnit tests in Docker. It builds `Dockerfile.test`, starts a throwaway MariaDB, downloads WordPress core and runs `vendor/bin/phpunit`, so no local database is needed. `make test-clean` removes the test containers and images.

Tests live in `tests/`. Root server responses are mocked with the `pre_http_request` filter, so tests never hit a real BMLT server. If you add behavior, add a test.
