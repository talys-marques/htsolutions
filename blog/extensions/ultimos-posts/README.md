# Extensão: Últimos posts em JSON

Opcional. Cria o endereço `/ultimos.json`, que devolve os posts mais recentes em
JSON. O site institucional da HT Solutions (fora do blog) usa para mostrar as
"Últimas notícias" e os "Cases" na página inicial e nas páginas Google.

```
/blog/ultimos.json              os 3 mais recentes
/blog/ultimos.json?n=6          os 6 mais recentes
/blog/ultimos.json?categoria=cases&n=3
```

Cada item tem `title`, `url`, `excerpt`, `cover`, `category` e `date` (já formatada).

**Não vai usar?** Apague esta pasta. O endereço deixa de existir e o site
institucional mostra só o link para o blog no lugar dos cartões.
