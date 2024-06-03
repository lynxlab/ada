# ADA Rest Api #

This folder contains all needed files to implement the _ADA Restful api_. It can safely be deleted if no api access is needed in your _ADA_ installation.

## Needed external libraries ##

### Oauth2 ###

Library to implement OAuth2 server

Link                                                        | Type | Note
:-----------------------------------------------------------|:----:|:----
<http://bshaffer.github.io/oauth2-server-php-docs/>         |Docs|-
<https://github.com/bshaffer/oauth2-server-php/tree/v1.14.1>|Code  |v1.14.1

### Slim ###

Slim PHP micro framework was choosen for its routing, middleware and routing facilities

Link                                         | Type | Note
:--------------------------------------------|:----:|:----
<https://www.slimframework.com/docs/v4/>     |Docs  |-
<https://github.com/slimphp/Slim/tree/4.13.0>|Code  |v4.13.0 used

## How do I use the ADA Api? ##

In order to use the _ADA API_ you must have an up and running version of _ADA_ platform, updated to its latest version.
Then you must have the [apps module](https://github.com/lynxlab/ada/tree/master/modules/apps) installed and use it as an _ADA Switcher_ user to get a __client_id__ and __client_secret__.
Last, you may use these credentials to obtain an _access\_token_ using the provided __OAuth2__ endpoint `/token` or, you may want to check the
[ADA PHP SDK](https://github.com/lynxlab/ada-php-sdk) for easy _PHP_ development.
This is the preferred way and handles all the _access\_token_ pains for you.

## Techincal Details ##

### .htaccess url rewrites ###

1. Every url that __does not point to an existing file__ and __that does not have an extension__ (such as .php) will be redirected to `index.php` without passing the `format` in the _GET_ request, thus using the default that is `json`. Example:

    ```text
    api/v1/users is rewritten to: api/v1/index.php
    ```

2. Every url that __does not point to an existing file__ and __that has an extension__ (such as .php) will be redirected to `index.php` passing the `format` in the _GET_ request, thus using the extension guessed output format. Example:

    ```text
    api/v1/users.xml is rewritten to: api/v1/index.php?format=xml
    ```

Supported formats are: __json__, __xml__ and __php__(outputs a php serialized array). Passing an unsupported format will generate a __400 Bad Request__ _HTML_ header.

### Application middleware rewrties ###

The class `Lynxlab\ADA\API\Middleware\ResolveLatestVersion` is responsible of redirecting urls requesting the `latest` API version. Example:

```text
api/latest/users is redirected to: api/v1/users
```

With a 307 - Temporary Redirect http status code.

The only endpoint without a version url is `token` that must always be used like:

```text
api/token
```
