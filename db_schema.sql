CREATE TABLE users (
	user_id text not null primary key
);

CREATE TABLE documents (
	document_id int primary key,
	token_xpath text not null
);

CREATE TABLE documents_users (
	document_id int not null references documents (document_id),
	user_id text not null references users (user_id),
	primary key (document_id, user_id)
);

CREATE TABLE tokens (
	document_id int not null references documents (document_id),
	token_id int not null,
	value text not null,
	primary key (document_id, token_id)
);

CREATE TABLE attribute_types (
	type_id text primary key
);
INSERT INTO attribute_types VALUES ('closed list'), ('free text');

CREATE TABLE attributes (
	document_id int not null references documents (document_id),
	attribute_xpath text not null,
	type_id text not null references attribute_types (type_id),
	primary key (document_id, attribute_xpath)
);

CREATE TABLE dict_attributes (
	document_id int not null,
	attribute_xpath text not null,
	value text not null,
	primary key (document_id, attribute_xpath, value),
	foreign key (document_id, attribute_xpath) references attributes (document_id, attribute_xpath)
);

CREATE TABLE values (
	document_id int not null,
	attribute_xpath text not null,
	user_id text not null references users (user_id),
	value text not null,
	foreign key (document_id, attribute_xpath) references attributes (document_id, attribute_xpath),
	primary key (document_id, attribute_xpath, user_id)
);

