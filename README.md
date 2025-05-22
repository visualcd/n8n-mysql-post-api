# n8n-mysql-post-api
Acesta este un copilot vibecode pentru conectarea nodului HTTP Request din n8n community edition la un o baza de date MySQL / MariaDB ce se afla pe un shared hosting cu portul 3306 inchis.
# Se creaza o cheia API din setari - Setting - n8n API - Create Api Key - denumeste cheia api (se poate schimba ulterior) si noteaza cheia (nu o poti recupera, doar poti sa o stergi si sa creezi alta cheie api)

## Se creaza un nod HTTP Request cu metopda POST

### se adauga url catre n8napi.php?nocache=123 sau n8napi.php

### Send Body = ON

### Body Content Type = JSON

### Specify Body = JSON

## Exemplu (cheia API generata este obligatorie pentru ca scriptul sa functioneze)
{
  "api_key": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxcccccccccccccccccccccccccccccccccccccccccxxxxxxxxxxxxxxxxx",
  "action": "query",
  "query": "SHOW TABLES"
}

Scriptul este inca in testare si dezoltare dar funtional la testul nodului HTTP Request
