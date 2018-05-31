# tokeneditor-api

Tokeneditor-api is a REST backend used by the [tokeneditor](https://github.com/acdh-oeaw/tokeneditor).

## Installation

- Clone the repository and enter its directory
- Make sure you have [Composer](https://getcomposer.org/)
- Run `composer update`
- Create a Postgresql database and create schema by running both `vendor/acdh-oeaw/tokeneditor-model/db_schema.sql` and `db_schema.sql`
- Read carefully the next section
- Rename config.ini.sample to config.ini and adjust it according to your setup

### Authentication

Tokeneditor doesn't provide any authentication mechanism but relies entirely on the web server.

It was mentioned to work with a HTTP Digest authentication or federated identity provider (like Shibboleth). In both cases the authorization is done by a web server which either denies access or attaches authenticated user id (or the whole authorization token for HTTP Digest) as an HTTP request header. Use the `user_id` configuration variable in `config.ini` to define the HTTP request header providing the user name.

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

# API

```
GET /document
POST /document
GET /document/{documentId}
DELETE /document/{documentId}
GET /document/{documentId}/preference
POST /document/{documentId}/preference
GET /document/{documentId}/preference/{preferenceId}
PUT /document/{documentId}/preference/{preferenceId}
DELETE /document/{documentId}/preference/{preferenceId}
GET /document/{documentId}/token
PUT /document/{documentId}/token/{tokenId}
```
