# tokeneditor

[![Travis-CI Build Status](https://travis-ci.org/acdh-oeaw/tokeneditor.png?branch=master)](https://travis-ci.org/acdh-oeaw/tokeneditor)


Tokeneditor allows you manually revise your XML datafiles.

The common use case is to revise results of automated data enrichment, e.g. part of speech recognition.

## Data model

Tokeneditor assumes your data might be presented as a table. Each row in the table is called token and each column is called token property.

To map your XML data to tokens and properties XPath expressions are used. Token XPath is run on the whole XML document while properties XPath are run on each token's node (so property values have to defined inside token nodes).
These XPath expressions as well as some additional informations are provided in a separate XML file. It has a very simple and self-explanatory structure - see examples in the sample_data directory.

## Installation

- Create a Postgresql database and create schema by running db_schema.sql
- Copy content of the php directory on a server with PHP 
- Read carefully the next section
- Rename php/config-sample.inc.php to config.inc.php and adjust it according to your config

### Authentication

Tokeneditor doesn't provide any authentication mechanism but relies entirely on the web server.

It was mentioned to work with a federated identity provider (like Shibboleth) run by a web server and providing tokeneditor with a (already authenticated) user name.
Example Apache virtual host config for an Apache+Shibboleth config:
```
<VirtualHost *:80>
  ServerName myDomain
  DocumentRoot myTokeneditorPath
  AuthType shibboleth
  ShibRequireSession On
  Require valid-user
</VirtualHost>
```

It is also possible to make it work with an HTTP Digest authentication. In that case you should:
- set up the `$CONFIG['userid']` variable to `HTTP_AUTHORIZATION` in the php/config-sample.inc.php
- append php/config-sample.inc.php with

    ```
    $tmp = filter_input(INPUT_SERVER, $CONFIG['userid']);
    if(preg_match('/^Digest username/', $tmp)){
        $_SERVER[$CONFIG['userid']] = preg_replace('/.*username="([^"]+)".*/', '\1', $tmp);
    }
    ```

# API

TODO
