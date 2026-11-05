# Configuração de infraestrutura

## Domínio (registro.br)

1. Registrei scriptamanent.com.br no registro.br.
2. Deixei os três registros de e-mail que o registro.br cria sozinho (MX nulo, SPF "-all" e DMARC p=reject).

## Cloudflare (DNS e HTTPS)

1. Adicionei o domínio na Cloudflare, plano Free, com a busca liberada na tela dos robôs. Não criei nenhum registro A.
2. No registro.br fui em DNS > Alterar servidores DNS, coloquei os dois que a Cloudflare deu (`carlos.ns.cloudflare.com` e `rayne.ns.cloudflare.com`) e salvei sem clicar em + DNSSEC. Domínio novo fica umas 2 horas em transição.
3. Com o domínio ativo liguei o DNSSEC na Cloudflare (DNS > Settings > DNSSEC). No registro.br, no mesmo formulário dos servidores DNS, cliquei em + DNSSEC e colei o Key Tag e o Digest do registro DS. O digest copiei pelo botão, porque aparece cortado.
4. SSL/TLS em Full (Strict). Em SSL/TLS > Edge Certificates liguei Always Use HTTPS.

## VPS (Hetzner)

1. Criei a conta, verifiquei com cartão e criei o projeto scriptamanent em console.hetzner.cloud.

2. Gerei a chave SSH no PowerShell, com senha na chave

   ```powershell
   ssh-keygen -t ed25519 -f "$env:USERPROFILE\.ssh\hetzner_scriptamanent" -C "scriptamanent-vps"
   ```

3. Firewall scriptamanent-fw com uma regra de entrada só, TCP 22, origem na rede IPv6 da minha casa (os quatro primeiros blocos do IPv6 seguidos de `::/64`). Nenhuma regra de saída. Pra ver minha rede IPv6

   ```powershell
   curl.exe -6 ifconfig.me
   ```

4. Servidor scriptamanent-prod
   - CX23 (Cost-Optimized, x86, 2 vCPU, 4 GB)
   - Ubuntu 24.04
   - IPv4 e IPv6 ligados
   - chave SSH do passo 2, firewall do passo 3 e backups ligados
   - no Cloud config colei o arquivo abaixo trocando `CHAVE_PUBLICA_SSH` pelo conteúdo do .pub, tudo numa linha só

   ```yaml
   #cloud-config
   package_update: true
   package_upgrade: true
   packages:
     - ca-certificates
     - curl
     - unattended-upgrades
   users:
     - name: deploy
       groups: sudo
       shell: /bin/bash
       sudo: "ALL=(ALL) NOPASSWD:ALL"
       ssh_authorized_keys:
         - CHAVE_PUBLICA_SSH
   ssh_pwauth: false
   disable_root: true
   write_files:
     - path: /etc/ssh/sshd_config.d/00-endurecimento.conf
       content: |
         PermitRootLogin no
         PasswordAuthentication no
         KbdInteractiveAuthentication no
         AllowUsers deploy
   runcmd:
     - fallocate -l 2G /swapfile
     - chmod 600 /swapfile
     - mkswap /swapfile
     - swapon /swapfile
     - echo '/swapfile none swap sw 0 0' >> /etc/fstab
     - curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
     - sh /tmp/get-docker.sh
     - usermod -aG docker deploy
     - systemctl restart ssh
     - dpkg-reconfigure -f noninteractive unattended-upgrades
   ```

5. Primeiro acesso, com a rede IPv6 do painel trocando o `/64` por `::1`

   ```powershell
   ssh -i "$env:USERPROFILE\.ssh\hetzner_scriptamanent" deploy@IPV6_DO_SERVIDOR
   ```

   Lá dentro conferi

   ```bash
   cloud-init status        # status: done
   docker version
   docker compose version
   free -h                  # swap de 2 GB
   ```

   Depois `sudo reboot`

6. Atalho no arquivo `%USERPROFILE%\.ssh\config`

   ```
   Host scriptamanent
       HostName IPV6_DO_SERVIDOR
       User deploy
       IdentityFile ~/.ssh/hetzner_scriptamanent
   ```

   Agora é só `ssh scriptamanent`.

## Cloudflare Tunnel

1. Ativei o Zero Trust no plano gratuito. Em Networks > Tunnels criei o túnel scriptamanent, tipo Cloudflared, ambiente Docker. Do comando que ele mostra peguei só o token e guardei fora do repositório.

2. Na VPS salvei o token no `.env` sem aparecer na tela. Depois do comando cola o token e dá Enter.

   ```bash
   mkdir -p ~/teste-tunel && cd ~/teste-tunel
   read -rs TUNNEL_TOKEN && printf 'TUNNEL_TOKEN=%s\n' "$TUNNEL_TOKEN" > .env && chmod 600 .env && unset TUNNEL_TOKEN
   ```

3. `compose.yaml` do teste

   ```yaml
   services:
     web:
       image: nginx:alpine
       restart: unless-stopped
     tunnel:
       image: cloudflare/cloudflared:latest
       command: tunnel run
       environment:
         TUNNEL_TOKEN: ${TUNNEL_TOKEN}
       restart: unless-stopped
   ```

4. `docker compose up -d` e `docker compose logs tunnel`, até aparecer "Registered tunnel connection".

5. No túnel adicionei a rota (published application) com o domínio scriptamanent.com.br, Service URL `http://web:80`. Conferi se apareceu a página do nginx no site.

6. Desliguei com `docker compose down`. A pasta fica lá por causa do token.

## Falta

- travar o cloudflared na versão 2026.10.7 no compose de produção
- rota www redirecionando pro endereço sem www
