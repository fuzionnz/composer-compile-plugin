# Composer Compile Plugin: Development

This documentation contains tips and information for doing development on
the `composer-compile-plugin` itself.

## Automated Tests

The `tests/` folder includes integration tests written with PHPUnit.  Each
integration-test generates a new folder/project with a plausible,
representative `composer.json` file and executes `composer install`.  It
checks that the output has the expected files.

To run the tests, you will need `composer` and `phpunit` in the `PATH`.

```
[~/src/composer-compile-plugin] which composer
/Users/myuser/bknix/civicrm-buildkit/bin/composer

[~/src/composer-compile-plugin] which phpunit6
/Users/myuser/bknix/civicrm-buildkit/bin/phpunit6

[~/src/composer-compile-plugin] phpunit6
PHPUnit 6.5.14 by Sebastian Bergmann and contributors.

...                                                                 3 / 3 (100%)

Time: 8.25 seconds, Memory: 12.00MB

OK (3 tests, 32 assertions)
```

The integration tests may have a lot going on under the hood.  To monitor
the tests more closesly, set the `DEBUG` variable, as in:

```
[~/src/composer-compile-plugin] env DEBUG=2 phpunit6
```

## Local Dev Sandbox

What if you want to produce an example project which uses the current plugin
code -- a place where you can manually experiment with running `composer`
while using your draft patches?

You may use any of the integration-tests to initialize a basic sandbox.

1. Initialize a sandbox project

   ```bash
   env USE_TEST_PROJECT=$HOME/src/sandbox DEBUG=2 phpunit tests/EventTest.php
   ```

2. Navigate into that project. If you inspect it, there should be
   `composer.json`, `composer.lock`, `vendor`, etc. Note that several items
   in `vendor` are symlinks back to our original `composer-compile-plugin`.

   ```bash
   cd $HOME/src/sandbox
   ```

3. Run whatever `composer` commands interest you.

   ```bash
   composer compile
   ```

4. If you would like to use an IDE with XDebug to investigate the running
   command, then this will require an extra option:

   ```bash
   COMPOSER_ALLOW_XDEBUG=1 php /path/to/composer.phar compile  -v
   ```

## Events

During the compilation process, the plugin emits various events. This is
used internally to provide some features/enhancements, and it may be used
for third party enhancements. These events are:

* `pre-compile-list`: Fires before parsing each package's task-list. This allows other plugins to inspect
  and modify the raw `taskDefinitions` (JSON data).
* `post-compile-list`: Fires after parsing each package's task-list. This allows other plugins to inspect
  and modify the parsed `Task` object.
* `pre-compile-task`: Fires before executing a specific `task`.
* `post-compile-task`: Fires after executing a specific `task`.

Note: The `compile` command supports a dry-run mode. All events will fire
normally during dry-run. This is important to support subscribers which
manipulate task definitions. However, if (hypothetically), a subscriber had
some significant side-effects (like creating files), then it would be
important to consult `$event->isDryRun()`.