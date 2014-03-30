create table AuthorizationCode (
  id string primary key,
  value string not null,
  expire int not null
);
