# Yireo GraphQlRateLimiting
**Magento 2 module to add rate limiting to GraphQL resources**

This module implements the [sunspikes/php-ratelimiter](https://packagist.org/packages/sunspikes/php-ratelimiter) in Magento 2. It checks how many GraphQL mutations and/or GraphQL queries are sent from a specific client to a Magento instance and if the number of these requests exceeds a configured maximum, a GraphQL error is generated.

This module is specifically recommended for limiting mutations, so that your Magento shop is not flooded with fake requests to create sessions, customers or other data. Usually, in a headless environment, the amount of mutations is limited.

## Usage
Install this extension:

    composer require yireo/magento2-graph-ql-rate-limiting
    bin/magento module:enable Yireo_GraphQlRateLimiting
    bin/magento setup:upgrade

Next, login to the Magento Admin Panel, navigate to **Store Configuration** and then **Yireo > Yireo GraphQlRateLimiting > Settings** and modify the settings to your needs. The default might be fine though. The settings **Enabled** and **Limit Mutations** are definitely to be enabled, otherwise this extension is kind of pointless. Whether **Limit Queries** is useful up to you. The settings **Maximum Queries** and **Maximum Mutations** refer to the maximum amount of queries or mutations to be made within a certain timeframe (**Timeframe**) before a connection is denied for the remainder of that timeframe.

Finally, navigate to **Cache Management** and enable the cache **GraphQL Rate Limiting**: 

    bin/magento cache:enable graphql_rate_limiting

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

After running the same query three-times an error should popup up:
```json
{
  "errors": [
    {
      "message": "A maximum of 3 queries has been reached.",
      "extensions": {
        "category": "graphql"
      }
    }
  ]
}
```


## Testing of the cache type
This extension adds a Cache Type `GRAPHQL_RATE_LIMITING` to the Magento cache frontends. To test whether the Cache Type is working, you can run the following Functional Test:

```bash
bin/magento cache:status
bin/magento cache:enable graphql_rate_limiting
vendor/bin/phpunit --bootstrap=app/bootstrap.php app/code/Yireo/GraphQlRateLimiting/Test/Functional/
```

## Todo
- Add integation tests
- Add `MovingWindowSettings` and `FixedWindowSettings`
- Create a little video about this
- Allow for specific endpoints to be limited
