# Copilot Instructions for CommonsBooking

## Big picture first
- This is a WordPress plugin with a hybrid structure: runtime bootstrap starts in `commonsbooking.php`, hook wiring is in `includes/Plugin.php`, domain/application code is PSR-4 in `src/` (`CommonsBooking\\...`).
- Treat `src/Plugin.php` as the central composition root (CPT registration, scheduler, shortcodes, REST routes, cache invalidation, i18n).
- Main domain entities are custom post types (`cb_item`, `cb_location`, `cb_timeframe`, `cb_booking`, `cb_restriction`, `cb_map`) managed via classes in `src/Wordpress/CustomPostType/`.
- Data access pattern is typically `Repository/*` -> `Model/*` wrappers -> `View/*` or API output.

## Build, test, and local workflows
- Initial setup: `npm run start`
- Local WP environment: `npm run env:start` (wp-env), stop with `npm run env:stop`.
- PHP unit tests: `vendor/bin/phpunit` (uses `tests/php/bootstrap.php`, config in `phpunit.xml.dist`).
- E2E tests: `npm run cypress:setup` then `npm run cypress:run` (config in `tests/cypress/cypress.config.js`).
- Static checks: `vendor/bin/phpstan analyse -c phpstan.neon`.
- Benchmarking: `vendor/bin/phpbench run tests/benchmark --report=aggregate` Benchmarks are run without caching, this behaviour is intentional, since the cache is supposed to be disabled in the future.

# Benchmark reference on master:

Subjects: 1, Assertions: 0, Failures: 0, Errors: 0
+---------------+------------------+-----+------+-----+----------+--------+--------+
| benchmark     | subject          | set | revs | its | mem_peak | mode   | rstdev |
+---------------+------------------+-----+------+-----+----------+--------+--------+
| CalendarBench | benchRenderTable |     | 1    | 1   | 72.014mb | 5.597s | ±0.00% |
+---------------+------------------+-----+------+-----+----------+--------+--------+

This is the current worst behaviour, aim to improve it.


## Project-specific coding conventions
- Follow WPCS rules from `.phpcs.xml.dist` (text domain must be `commonsbooking`; custom escaping helpers like `commonsbooking_sanitizeHTML` are allowed).
- Do not modify anything regarding the Cache. Performance optimizations should pretend, that the cache does not exist.
