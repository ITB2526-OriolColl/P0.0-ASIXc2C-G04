#!/bin/bash
# Reglas de firewall y NAT de R-N04
# enp1s0: WAN · enp3s0: Intranet · enp4s0: DMZ

# /etc/sysctl.conf -> net.ipv4.ip_forward = 1

iptables -F
iptables -t nat -F

# Por defecto se bloquea el tráfico entre redes
iptables -P FORWARD DROP

# NAT: las redes internas salen a Internet con la IP del router
iptables -t nat -A POSTROUTING -o enp1s0 -j MASQUERADE

# Respuestas de conexiones ya establecidas
iptables -A FORWARD -m state --state RELATED,ESTABLISHED -j ACCEPT

# La Intranet puede ir a Internet y a la DMZ
iptables -A FORWARD -i enp3s0 -j ACCEPT

# DMZ -> Intranet: solo el servidor web hacia MariaDB
iptables -A FORWARD -i enp4s0 -s 10.4.10.10 -d 10.4.20.10 -p tcp --dport 3306 -j ACCEPT
