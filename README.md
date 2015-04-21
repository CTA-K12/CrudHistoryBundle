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
        "url" : "https://github.com/MESD/UserBundle.git"
    }
],
"require": {
        "mesd/user-bundle": "dev-master"
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
