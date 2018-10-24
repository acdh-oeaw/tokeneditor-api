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

* `GET /document` lists documents
* `POST /document` creates new document  
  Required parameters (encoded as multipart/form-data):
    * `schema` XML file containing document schema
    * `document` XML file containing document
    * `name` document name
* `GET /document/{documentId}` returns information on a  document
* `DELETE /document/{documentId}` deletes a document
* `GET /document/{documentId}/preference` lists user-defined properties for a given document
* `POST /document/{documentId}/preference` creates a new user-defined property for a given document  
  Required parameters:
    * `preference` property name
    * `value` property value
* `GET /document/{documentId}/preference/{preferenceId}` gets value of a given user-defined property for a given document
* `PUT /document/{documentId}/preference/{preferenceId}` sets value of a given user-defined property for a given document  
  Supported parameters:
    * `value` property value
* `DELETE /document/{documentId}/preference/{preferenceId}` deletes given user-defined property for a given document
* `GET /document/{documentId}/property` lists document properties
* `GET /document/{documentId}/property/{propertyName}` returns information on a given document's property
* `PATCH /document/{documentId}/property/{propertyName}` alters definition of a given document's property  
  Supported parameters (encoded as JSON):
    * `name` property name
    * `typeId` property type
    * `ord` property order
    * `readOnly` should property be read only?
    * `values` list of possible property values (valid only for certain property types)
* `GET /document/{documentId}/token` returns list of tokens for a given document  
  Supported parameters:
    * `_pageSize` maximum number of returned tokens
    * `_offset` returned tokens list offset
    * `_order` defines sorting (prepend property name with a `-` to set descending order, supports array of property names or a single property name)
    * `tokenId` token id filter
    * `{tokenPropertyName}` filter for a given token property (`%` in filter value is interpreted as *any nummber of any characters*)
    * `_tokensOnly` if present and have non-empty value, output is limited to tokenIds only (no property values are returned)
    * `_stats` if present, value frequency statistics are returned for a property indicated by the `stats` parameter
* `PUT /document/{documentId}/token/{tokenId}` updates token property value
  Required parameters:
    * `name` token property name
    * `value` token property value
```
