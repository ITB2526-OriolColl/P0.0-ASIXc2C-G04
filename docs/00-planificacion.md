# Planificación y seguimiento

El proyecto se organiza en **3 sprints quincenales** (6 semanas, unas 30 horas por persona) y se gestiona en **ProofHub**, en el proyecto `P0.0-ASIXc2C-G04`.

## Índice

1. [Equipo y responsabilidades](#1-equipo-y-responsabilidades)
2. [Organización en ProofHub](#2-organización-en-proofhub)
3. [Sprint 1: máquinas base y repositorio](#3-sprint-1-máquinas-base-y-repositorio)
4. [Sprint 2: redes, datos y conexión](#4-sprint-2-redes-datos-y-conexión)
5. [Sprint 3: FTP, clientes y cierre](#5-sprint-3-ftp-clientes-y-cierre)
6. [Cambios en la planificación](#6-cambios-en-la-planificación)

---

## 1. Equipo y responsabilidades

| Miembro | Iniciales en ProofHub | Responsable de |
|---|---|---|
| **Oriol Coll** | CO | Servidor web (W-N04), aplicación PHP, repositorio y despliegue con git, servidor FTP (F-N04), cliente Linux |
| **Mario Cabeza** | CM | Servidor de base de datos (B-N04), importación del CSV de Open Data, copia de seguridad |
| **Jordi** | BJ | Router (R-N04): interfaces, NAT, firewall, DHCP y DNS; cliente Windows |
| **Todos** | — | Planificación, arquitectura, pruebas finales, documentación y entrega |

Cada miembro documenta su parte en `docs/` (un archivo por servidor) y sube las capturas a `docs/img/`.

---

## 2. Organización en ProofHub

- **Una lista por sprint** (Sprint 1, 2 y 3).
- Dentro de cada sprint, **una tarea por bloque** (por ejemplo, *4. Servidor Web*) con su responsable, y **subtareas numeradas** (P4.1, P4.2…). Las del Sprint 2 llevan `(A)` porque amplían las del Sprint 1.
- Flujo de trabajo del centro: **Pendents backlog → Assignades sprint → En curs → Bloquejades → Pendents de revisió → Finalitzades**.

En cada sprint se hace una captura **al inicio** (tareas en el backlog) y otra **al final** (tareas finalizadas).

---

## 3. Sprint 1: máquinas base y repositorio

**Objetivo:** crear las máquinas virtuales con su servicio principal, el repositorio de GitHub y el borrador de la arquitectura.

| Bloque | Responsable | Contenido |
|---|---|---|
| 1. Planificación, gestión y documentación inicial | Todos | Proyecto en ProofHub, sprints, repositorio público, estructura de carpetas, borrador del diagrama y de las IPs |
| 2. Red e infraestructura base | Jordi | VM del router con `bchecker` y SSH |
| 3. Servidor de base de datos | Mario | VM, `bchecker`, MariaDB, `bind-address`, base de datos `crud_db`, `usuario_app`, `mysql_secure_installation` |
| 4. Servidor web | Oriol | VM, `bchecker`, Apache + PHP, verificación, clonado del repositorio, documentación |
| 7. Cierre, git y documentación | Todos | Subida de cambios con git, documentación en `docs/`, primera prueba web → BBDD |

**Inicio**

![Sprint 1: inicio](img/plan-s1-inicio-tablero.png)

<details>
<summary>Ver los bloques con sus subtareas</summary>

![Sprint 1 inicio, bloque 1](img/plan-s1-inicio-1.png)

![Sprint 1 inicio, bloque 2](img/plan-s1-inicio-2.png)

![Sprint 1 inicio, bloque 3](img/plan-s1-inicio-3.png)

![Sprint 1 inicio, bloque 4](img/plan-s1-inicio-4.png)

![Sprint 1 inicio, bloque 5](img/plan-s1-inicio-5.png)

</details>

**Final**

![Sprint 1: final](img/plan-s1-final-tablero.png)

<details>
<summary>Ver los bloques con sus subtareas</summary>

![Sprint 1 final, bloque 1](img/plan-s1-final-1.png)

![Sprint 1 final, bloque 2](img/plan-s1-final-2.png)

![Sprint 1 final, bloque 3](img/plan-s1-final-3.png)

![Sprint 1 final, bloque 4](img/plan-s1-final-4.png)

![Sprint 1 final, bloque 5](img/plan-s1-final-5.png)

</details>

---

## 4. Sprint 2: redes, datos y conexión

**Objetivo:** montar las redes DMZ e Intranet con el router, mover cada servidor a su zona, cargar los datos y conectar la web con la base de datos.

| Bloque | Responsable | Contenido |
|---|---|---|
| 2. Red e infraestructura base | Jordi | Interfaces WAN, DMZ e Intranet; DHCP; DNS; IP forwarding, NAT y firewall; diagrama final y tabla de IPs |
| 3. Servidor de base de datos | Mario | Migración a la Intranet, `usuario_app` solo desde W-N04, importación del CSV, script y copia de seguridad |
| 4. Servidor web | Oriol | Migración a la DMZ, prueba web → BBDD por el router, aplicación PHP, cliente Linux, documentación |
| Servicios (cierre del sprint) | Todos | SSH con `bchecker` en todos los servidores, subida de cambios |

**Inicio**

![Sprint 2: inicio](img/plan-s2-inicio-tablero.png)

<details>
<summary>Ver los bloques con sus subtareas</summary>

![Sprint 2 inicio, bloque 1](img/plan-s2-inicio-1.png)

![Sprint 2 inicio, bloque 2](img/plan-s2-inicio-2.png)

![Sprint 2 inicio, bloque 3](img/plan-s2-inicio-3.png)

![Sprint 2 inicio, bloque 4](img/plan-s2-inicio-4.png)

</details>

**Final**

![Sprint 2: final](img/plan-s2-final-tablero.png)

<details>
<summary>Ver los bloques con sus subtareas</summary>

![Sprint 2 final, bloque 1](img/plan-s2-final-1.png)

![Sprint 2 final, bloque 2](img/plan-s2-final-2.png)

![Sprint 2 final, bloque 3](img/plan-s2-final-3.png)

![Sprint 2 final, bloque 4](img/plan-s2-final-4.png)

</details>

---

## 5. Sprint 3: FTP, clientes y cierre

**Objetivo:** completar los servicios que faltan, probar todo desde los clientes y cerrar la documentación.

| Bloque | Responsable | Contenido |
|---|---|---|
| FTP | Oriol | Servidor vsftpd en F-N04, usuarios y permisos, documentación |
| Cliente Windows y red | Jordi | Cliente Windows con `bchecker`, pruebas de DNS y DHCP, documentación de arquitectura y router |
| Cierre | Todos | Validar FTP y SSH desde los clientes, revisión del repositorio y del README, entrega |

**Inicio**

![Sprint 3: inicio](img/plan-s3-inicio-tablero.png)

<details>
<summary>Ver los bloques con sus subtareas</summary>

![Sprint 3 inicio, bloque 1](img/plan-s3-inicio-1.png)

![Sprint 3 inicio, bloque 2](img/plan-s3-inicio-2.png)

![Sprint 3 inicio, bloque 3](img/plan-s3-inicio-3.png)

</details>

**Final**

![Sprint 3: final](img/plan-s3-final-tablero.png)

<details>
<summary>Ver los bloques con sus subtareas</summary>

![Sprint 3 final, bloque 1](img/plan-s3-final-1.png)

![Sprint 3 final, bloque 2](img/plan-s3-final-2.png)

![Sprint 3 final, bloque 3](img/plan-s3-final-3.png)

</details>

---

## 6. Cambios en la planificación

| Cambio | Motivo |
|---|---|
| El **servidor FTP** pasa de Mario a Oriol | Oriol había terminado su parte y Mario tenía pendiente la importación del CSV |
| La **aplicación PHP** se adelanta al Sprint 2 | Se desarrolló mientras se esperaba a la base de datos, y se probó en cuanto estuvo accesible |
| **P1.4 Claves SSH**: solo el servidor web usa *deploy key* | Es la única máquina que descarga código para desplegarlo |
| Tareas en **Bloquejades** durante el Sprint 2 | La prueba web → BBDD dependía de que B-N04 quitara su tarjeta Default y respondiera por el router |
