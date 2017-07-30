# Tools

We use a number of additional tools that are useful to development. These can be
found in the `/vendor/bin` directory.

- [`phpcpd`](https://github.com/sebastianbergmann/phpcpd) is a Copy/Paste
  detector (CPD) for PHP code. We have not resolved all errors it identifies yet
  but are making progress towards doing so. We use it like this:

  `php vendor/bin/phpcpd . --exclude=templates --exclude=vendor`

- [`phpcs`](https://github.com/squizlabs/PHP_CodeSniffer) is PHP Code Sniffer.
  PHP_CodeSniffer tokenizes PHP, JavaScript and CSS files and detects violations
  of a defined set of coding standards. We use it like this:

  `php vendor/bin/phpcs --standard=vendor/bin/phpcs.xml . --ignore=templates,vendor`

- [`phpmd`](https://phpmd.org/) is PHP Mess Detector. It takes a given PHP
  source code base and looks for several potential problems within that source.
  We use it like this:

  `php vendor/bin/phpmd . text vendor/bin/phpmd.xml --exclude vendor/,templates/`

- [`phpstan`](https://github.com/phpstan/phpstan/) PHPStan focuses on finding
  errors in your code without actually running it. It catches whole classes of
  bugs even before you write tests for the code. Currently it errors on adodb,
  in classes/Db, which is acceptable until we eliminate adodb. We use it like this:

  `php vendor/bin/phpstan analyze --no-progress --no-ansi -l 5 -c vendor/bin/phpstan.neon -vvv .`

- [`tki-code-check.sh`] is a short bash script which is located in vendor/bin.
  It runs phpmd, phpcs, and phpstan with our project coding standards defined.
  **We now test all code against these three tools.**

- [`php-cs-fixer`](http://cs.sensiolabs.org/) strives to automatically correct
  code for specific items in the PSR standard. We do not currently use it, but
  may in the future use it as a pre-commit hook.

There are other command-line options for these tools (try them!), with varying
levels of usefulness to our project. We have bundled phpcbf - do NOT run phpcbf,
as it will reformat the entire codebase. We also have included phpunit, which we
intend to use heavily.
