# n8n-mysql-post-api
Acesta este un copilot vibecode pentru conectarea nodului HTTP Request din n8n community edition la un o baza de date MySQL / MariaDB ce se afla pe un shared hosting cu portul 3306 inchis.

1. Se creaza o cheia API din setari - Setting - n8n API - Create Api Key - denumeste cheia api (se poate schimba ulterior) si noteaza cheia (nu o poti recupera, doar poti sa o stergi si sa creezi alta cheie api)

2. Se creaza un nod HTTP Request cu metopda POST

3. Se adauga url catre n8napi.php?nocache=123 sau n8napi.php

4. Send Body = ON

5. Body Content Type = JSON

6. Specify Body = JSON

#### Exemplu (cheia API generata este obligatorie pentru ca scriptul sa functioneze)
{
  "api_key": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxcccccccccccccccccccccccccccccccccccccccccxxxxxxxxxxxxxxxxx",
  "action": "query",
  "query": "SHOW TABLES"
}

sau 
{
  "api_key": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxcccccccccccccccccccccccccccccccccccccccccxxxxxxxxxxxxxxxxx",
  "action": "query",
  "query": "SELECT * FROM nume_tabel WHERE id = '{{ $execution.id }}'"
}

#### Scriptul este inca in testare si dezoltare dar funtional la testul nodului HTTP Request
