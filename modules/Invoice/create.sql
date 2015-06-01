CREATE TABLE `invoice` (
  `id` varchar(36) NOT NULL default '',
  `prefix` varchar(14) NOT NULL,
  `invoice_number` int(11) NOT NULL,
  `quote_number` int(11) NULL default NULL,
  `date_entered` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified_user_id` varchar(36) NOT NULL default '',
  `assigned_user_id` varchar(36) default NULL,
  `created_by` varchar(36) default NULL,
  `deleted` tinyint(1) NOT NULL default '0',
  `name` varchar(100) default NULL,
  `opportunity_id` varchar(36) default NULL,
  `invoice_stage` varchar(25) NOT NULL default '',
  `purchase_order_num` varchar(100) default NULL,
  `due_date` date NOT NULL default '0000-00-00',
  `billing_account_id` varchar(36) default NULL,
  `billing_contact_id` varchar(36) default NULL,
  `billing_address_street` varchar(150) default NULL,
  `billing_address_city` varchar(100) default NULL,
  `billing_address_state` varchar(100) default NULL,
  `billing_address_postalcode` varchar(20) default NULL,
  `billing_address_country` varchar(100) default NULL,
  `shipping_account_id` varchar(36) default NULL,
  `shipping_contact_id` varchar(36) default NULL,
  `shipping_address_street` varchar(150) default NULL,
  `shipping_address_city` varchar(100) default NULL,
  `shipping_address_state` varchar(100) default NULL,
  `shipping_address_postalcode` varchar(20) default NULL,
  `shipping_address_country` varchar(100) default NULL,
  `currency_id` varchar(36) default NULL,
  `taxrate_id` varchar(36) default NULL,
  `shipping_provider_id` varchar(36) default NULL,
  `description` text,
  `line_items` text,
  `amount` float NOT NULL default '0',
  `amount_usdollar` double default NULL,
  `terms` varchar(14) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `idx_invoice_number` (`prefix`, `invoice_number`),
  KEY `idx_invoice_name` (`name`)
) TYPE=MyISAM;

-- Invoice Subject, Invoice Number, Invoice Stage, Due Date

-- Quote Stage, Valid Until

ALTER TABLE quotes ADD COLUMN terms varchar(14) NOT NULL;

INSERT INTO config (category, name, value) VALUES('company', 'invoice_prefix', '');
INSERT INTO config (category, name, value) VALUES('company', 'invoice_number_sequence', '');


ALTER TABLE quotes ADD COLUMN `prefix` varchar(14) NOT NULL;

ALTER TABLE invoice MODIFY `quote_number` varchar(26) NOT NULL DEFAULT '';







