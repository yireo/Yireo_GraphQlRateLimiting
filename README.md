# Yireo GraphQlRateLimiting

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-graph-ql-rate-limiting)
<!-- badges.specs.end -->

**Magento 2 module to add rate limiting to GraphQL resources**

This module checks how many GraphQL mutations and/or GraphQL queries are sent from a specific client to a Magento instance. If the number of these requests exceeds a configured maximum within a timeframe, a GraphQL error is returned with the HTTP status `429 Too Many Requests` and a `Retry-After` header.

This module is specifically recommended for limiting mutations, so that your Magento shop is not flooded with fake requests to create sessions, customers or other data. Usually, in a headless environment, the amount of mutations is limited.

## Usage
Install this extension:

    composer require yireo/magento2-graph-ql-rate-limiting
    bin/magento module:enable Yireo_GraphQlRateLimiting
    bin/magento setup:upgrade

Next, login to the Magento Admin Panel, navigate to **Store Configuration** and then **Yireo > Yireo GraphQL Rate Limiting > Settings** and modify the settings to your needs:

- **Enabled**: Enable rate limiting.
- **Limit Mutations** and **Limit Queries**: Whether mutations and queries are limited at all.
- **Maximum Mutations** and **Maximum Queries**: The maximum number of calls per root field, client and timeframe. A value of `0` or lower disables limiting.
- **Timeframe**: The length of the window in seconds. The window starts with the first call and is not extended by later calls.
- **Identify by User Agent**: Count clients with a different user agent separately. Not recommended, because a user agent is easily spoofed.

## How requests are counted
- Counting happens per root field of the operation that is executed (like `generateCustomerToken` or `createEmptyCart`). Every root field, including every alias of the same field, counts as one call. Fragments are resolved, so fragment names and the order of definitions do not matter.
- A request that would exceed the maximum is denied as a whole and is not counted.
- A client is identified by its IP address. IPv6 addresses are grouped by their `/64` network.
- Counters are stored in the Magento cache (Redis is recommended) with the tag `GRAPHQL_RATE_LIMITING`. Disabling the cache type **GraphQL Rate Limiting** does not disable rate limiting, but cleaning or flushing the cache resets all counters.

## Running behind a proxy, load balancer or CDN
Behind a proxy, all visitors share the IP address of that proxy, unless Magento is configured to read the client IP from a header like `X-Forwarded-For`. When doing so, always configure the trusted proxies as well. Otherwise, Magento uses the first IP of the header, which is set by the client itself, and rate limiting can be bypassed by sending a random IP address with every request. Both are DI arguments of `Magento\Framework\HTTP\PhpEnvironment\RemoteAddress`:

```xml
<type name="Magento\Framework\HTTP\PhpEnvironment\RemoteAddress">
    <arguments>
        <argument name="alternativeHeaders" xsi:type="array">
            <item name="x-forwarded-for" xsi:type="string">HTTP_X_FORWARDED_FOR</item>
        </argument>
        <argument name="trustedProxies" xsi:type="array">
            <item name="load-balancer" xsi:type="string">10.0.0.1</item>
        </argument>
    </arguments>
</type>
```

GraphQL GET queries that are served from the Magento Full Page Cache or from Varnish never reach Magento, and are therefore not counted.

## Testing to see if this works
Open up GraphiQL or some other client and create a simple request like the following:

```graphql
query {
  products(filter: {name: {match: "jacket"}}) {
    items {
      sku
    }
  }
}
```
Configure the following settings in this Magento module (under the **Store Configuration**):

- **Enabled**: *Yes*
- **Limit Queries**: *Yes*
- **Maximum Queries**: *3*

After running the same query three times, the next request returns HTTP status 429 with the following error:
```json
{
  "errors": [
    {
      "message": "Too many requests, please try again later",
      "extensions": {
        "category": "graphql"
      }
    }
  ]
}
```

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_GraphQlRateLimiting/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_GraphQlRateLimiting/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_GraphQlRateLimiting/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_GraphQlRateLimiting/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_GraphQlRateLimiting/compile.yml?label=compile)
<!-- badges.test.end -->
