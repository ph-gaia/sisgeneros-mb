ALTER TABLE `sisgeneros_mb`.`requests_invoices` 
ADD COLUMN `invoice_date_emission` DATETIME NULL AFTER `invoice_date`,
ADD COLUMN `date_delivery` DATETIME NULL AFTER `number_order_bank_date`;
