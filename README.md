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

The tries to authenticate requests in a following order (`varName` denote config vars in the config.ini):

* Using it's own session token provided in the `authTokenVar` cookie.  
  Corresponding cookie is automatically set after every successful authorization.
* Using HTTP basic authentication
* Using Google auth token provided in the `googleTokenVar` cookie
* Using a `shibUserHeader` HTTP header containing a federated login user id

Finally if all auth methods failed but the `guestUser` is set, the user is set to the `guestUser` config variable value.

# API

* `GET /editor/current` returns information on user whose credentials were used to authenticate the request
* `PUT /document/{documentId}/editor/{userId}` sets role and/or name of a given user on a given document  
  Supported parameters:
    * `role` users' role - one of `owner`, `editor`, `viewer` or `none`
    * `name` label to be used instead of the `userId`
* `GET /document` lists documents
* `POST /document` creates new document
  Two input formats are supported:
    * A pair of XML files (encoded as multipart/form-data), one containing schema description and other containing the data.
      Required parameters:
        * `schema` XML file containing document schema - see examples on https://github.com/acdh-oeaw/tokeneditor-model/tree/master/sample_data
        * `document` XML file containing document (can be compressed with zip)
        * `name` document name
    * A JSON object.
      Required properties:
        * `schema` an array of objects describing document schema. An object describing a single property is a simple JSON 
          serialization of an XML describing a single property just the `propertyXPath` property is not needed, e.g.
          `{"propertyName": "myProp", "propertyType": "free text"}`.
        * `tokens` an array of object describing tokens, e.g. `[{"myProp1": 1, "myProp2": "a"}, {"myProp1": 2, "myProp2": "b"}]`.
          All properties used in tokens have to be described in the shema.
        * `name` document name
* `GET /document/{documentId}` exports document in a desired format
  Supported parameters:
    * `_format` export format - one of `text/xml` (default), `application/xml`, `text/csv`
    * `inPlace` (for XML exports only, all other formats are exported *in place*) - 
      should export contain detailed information on all editions made (who, when, what) or only a final version of the document
* `DELETE /document/{documentId}` deletes a document
* `GET /document/{documentId}/schema` returns document schema as an XML file
* `GET /document/{documentId}/editor` lists all users having access to a given document
* `PUT /document/{documentId}/editor/{userId}` sets role and/or name of a given user on a given document  
  Supported parameters:
    * `role` users' role - one of `owner`, `editor`, `viewer` or `none`
    * `name` label to be used instead of the `userId`
* `DELETE /document/{documentId}/editor/{userId}` revokes all privilesges on given document from a given user  
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
* `PUT /document/{documentId}/token/{tokenId}` updates token property value
  Required parameters:
    * `name` token property name
    * `value` token property value
```

