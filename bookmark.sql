create table account (
       uid   integer primary key,
       tid   integer unique not null,
       name  text unique not null,
       pass  text not null,
       email text not null,
       blog  text,
       intro text
);

create table tags (
       tid      integer primary key,
       uid      integer not null,
       title    text not null,
       intro    text not null,
       words    text,
       l_parent integer default 0
);

insert into tags (tid, uid, title, intro, l_parent) values
       (1, 0, "Root", " ", 0);
insert into tags (tid, uid, title, intro, l_parent) values
       (2, 0, "Private", "Used to Protect from Global readable", 1);
insert into tags (tid, uid, title, intro, l_parent) values
       (3, 0, "Collect RSS", "Collect RSS (RSS link only)", 1);
insert into tags (tid, uid, title, intro, l_parent) values
       (4, 0, "Collect Puts", "Collect Puts", 1);

create table nodes (
       nid   integer primary key,
       uid   integer not null,
       title text not null,
       href  text not null,
       intro text not null,
       tag   text,
       words text
);

