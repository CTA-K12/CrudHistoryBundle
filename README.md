### MesdCrudHistoryBundle

Uses the database to keep a log of all inserts, updates, and deletes.

### Prerequisites

Symfony 2.3+


### Installation


#### Step 1: Download MesdCrudHistoryBundle using composer

Add the MesdCrudHistoryBundle to your composer.json file. You'll need to add the github url
under your "repositories" section, and add the bundle to your "require" section. Make
sure not to overwrite any existing repositories or requirements you already have in
place:


``` json
"repositories": [
    {
        "type" : "vcs",
        "url" : "https://github.com/MESD/CrudHistoryBundle.git"
    }
],
"require": {
        "mesd/crud-history-bundle": "dev-master"
    },
```

Now install the bundle with composer:

``` bash
$ composer update mesd/crud-history-bundle
```

Composer will install the bundle to your project's `vendor/Mesd` directory.


#### Step 2: Enable the bundle

Enable the bundle in the kernel:

```php
    public function registerBundles()
    {
        $bundles = [
          ...
          new Mesd\CrudHistoryBundle\MesdCrudHistoryBundle(),
          ...
        ];
    }
```

#### Step 3:  Update your schema

``` bash
$ app/console doctrine:schema:update
```

#### Step 3:  Specify app_name in parameters.yml (or similar place), either as a refelction of another parameter or raw text

```yaml
    parameters:
      app_name: %your_app_name_reference%
```

#### Step 4: Establish a whitelist for bundles.  This will use the bundle name as a default point for determining class
name of the acting controller/service/etc when the context is unclear.

```yaml

    config.yml

    mesd_crud_history:
        bundle_whitelist:
            - mesd_sia
```