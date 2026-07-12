-- Run this if your tsf database was created before the Mobile Money/Card rewrite.

ALTER TABLE donors
    ADD COLUMN IF NOT EXISTS mobile_network ENUM('mtn','telecel','airteltigo') DEFAULT NULL AFTER payment_method;

UPDATE donors
SET mobile_network = CASE payment_method
    WHEN 'momo' THEN 'mtn'
    WHEN 'pesa' THEN 'telecel'
    ELSE NULL
END;

UPDATE donors
SET payment_method = CASE
    WHEN payment_method IN ('momo', 'pesa') THEN 'mobile_money'
    WHEN payment_method IN ('card', 'gpay') THEN 'card'
    ELSE payment_method
END;

ALTER TABLE donors
    MODIFY payment_method ENUM('mobile_money','card') NOT NULL;
