FROM archlinux/base

RUN echo -e '\n[aur]\nServer = https://repo.pierre-schmitz.com/$repo/os/$arch\n' >> /etc/pacman.conf
RUN pacman -Syu --noconfirm php php-intl php-apcu php-ast composer xdebug geoip2-database yarn
ADD etc/ /etc
