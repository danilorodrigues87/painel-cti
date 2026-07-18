-- QR base64 do Mercado Pago nas faturas SaaS (opcional; sem isso o painel gera QR pelo copia-e-cola)
ALTER TABLE saas_faturas
  ADD COLUMN pix_qr_base64 MEDIUMTEXT NULL AFTER pix_copia_cola;
