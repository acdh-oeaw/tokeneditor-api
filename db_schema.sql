-- This file contains only schema elements added by the tokeneditor-api module
-- For the base tokeneditor-model schema please look at https://github.com/acdh-oeaw/tokeneditor-model/blob/master/db_schema.sql

CREATE TABLE documents_users_preferences (
	document_id int not null,
	user_id text not null,
	key text not null,
	value text not null,
	primary key (document_id, user_id, key),
	foreign key (document_id, user_id) references documents_users (document_id, user_id) ON UPDATE CASCADE ON DELETE CASCADE
);

ALTER TABLE users ADD data text NOT NULL DEFAULT '{}';
