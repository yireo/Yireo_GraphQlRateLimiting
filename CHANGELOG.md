# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 25 November 2026
### Changed
- BREAKING: Rate limits are counted per executed root field (aliases included) in a fixed window, so fragment names, fragment order and aliases can no longer be used to bypass the limit
- Counters are stored directly in the cache backend under a per-client lock, so disabling the cache type no longer turns off rate limiting
- The Limit Queries and Limit Mutations settings are honoured; a value of 0 or lower disables limiting and numeric settings are validated
- IPv6 clients are grouped per /64 network and long user agents are truncated
- BREAKING: Removed the cache_ttl setting and the dependency on the abandoned sunspikes/php-ratelimiter
- BREAKING: Supports PHP 8.2 up to 8.5 (PHP 8.1 dropped)
- Documented counting, proxy setup and full page cache behaviour

### Fixed
- Rate-limited GraphQL requests return HTTP 429 with Retry-After instead of 500 (#3)
- Rate-limited requests receive a generic error message
- GraphQL queries broke on Magento 2.4.8, which passes a pre-parsed query document
- Restricted admin roles could not be granted access to the configuration section, because its ACL resource was undefined
- The query plugin is registered in the GraphQL area only instead of globally
- Composer and module.xml dependencies match actual usage

## [0.0.4] - 12 April 2024
### Added
- Limit to PHP 7.4 because sunspikes/php-ratelimiter is dead

## [0.0.3] - 29 July 2020
### Added
- Magento 2.4 compatibility

## [0.0.2] - Undocumented
