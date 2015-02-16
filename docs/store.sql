create table OAuth2 (
  id string primary key,
  value string not null,
  expire int not null
);

create index expire_index on OAuth2(expire);