create table OAuth2 (
  id varchar(64) primary key,
  value varchar(1024) not null,
  expire int not null
);

create index expire_index on OAuth2(expire);