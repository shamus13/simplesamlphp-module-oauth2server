create table AuthorizationCode (
  id string primary key,
  value string not null,
  expire int not null
);

create table AccessToken (
  id string primary key,
  value string not null,
  expire int not null
);
