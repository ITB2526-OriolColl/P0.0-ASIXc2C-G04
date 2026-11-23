# Aplicació web

Aplicació PHP que mostra les taules de la base de dades `crud_db` (B-N04) amb un cercador. Per defecte mostra el llistat d'equipaments d'educació de Barcelona (Open Data BCN): **2709 registres**.

S'executa al servidor web **W-N04** (Apache + PHP 8.1) i es consulta a `http://W-N04/app/app/`.

## Fitxers

| Fitxer | Contingut |
|---|---|
| `index.php` | L'aplicació: es connecta amb PDO, llista les taules i mostra els registres (màxim 100) amb cercador |
| `config.example.php` | Plantilla de connexió, sense contrasenya |
| `config.php` | Dades reals de connexió. **No es puja al repositori** (és al `.gitignore`) |

## Instal·lació

```bash
cd /var/www/html
git clone git@github.com:ITB2526-OriolColl/P0.0-ASIXc2C-G04.git app
cd app
cp app/config.example.php app/config.php
nano app/config.php
```

| Variable | Valor |
|---|---|
| `$DB_HOST` | `10.4.20.10` (B-N04) |
| `$DB_NAME` | `crud_db` |
| `$DB_USER` | `usuario_app` |
| `$DB_PASS` | Contrasenya de `usuario_app` (no es publica) |

## Seguretat

- La contrasenya només és a `config.php`, fora del repositori.
- Només es poden consultar taules que existeixen a la base de dades (evita injecció SQL al nom de la taula).
- La cerca fa servir consultes preparades (PDO) i tot el que es mostra passa per `htmlspecialchars`.
- `usuario_app` només es pot connectar des de la IP de W-N04.

Documentació completa: [docs/04-servidor-web.md](../docs/04-servidor-web.md).
