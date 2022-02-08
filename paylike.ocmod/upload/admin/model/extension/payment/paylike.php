<?php

class ModelExtensionPaymentPaylike extends Model
{
    public function install()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "paylike_transaction` (
         `paylike_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
         `order_id` int(11) NOT NULL,
         `transaction_id` char(50) NOT NULL,
         `transaction_type` char(10) NOT NULL,
         `transaction_currency` char(5) NOT NULL,
         `order_amount` decimal(15,4) NOT NULL,
         `transaction_amount` decimal(15,4) NOT NULL,
         `total_amount` decimal(15,4) NOT NULL,
         `history` tinyint(1) NOT NULL,
         `date_added` datetime NOT NULL,
         PRIMARY KEY (`paylike_transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->addEvents();
    }

    public function uninstall()
    {
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "paylike_transaction`");

        $this->deleteEvents();
    }

    /********************************* EVENTS ADD/DELETE BEGIN *************************************/
    public function addEvents()
    {
        $this->load->model('setting/event');

        /** Check if event is in database 'event' table (the result of getEventByCode is an array). */
        if(empty($this->model_setting_event->getEventByCode('paylike_do_transaction_on_order_status_change'))) {
            /** Make sure that the event is introduce only once in DB. */
            /** addEvent($code, $trigger, $action, $status = 1, $sort_order = 0); */
            $this->model_setting_event->addEvent(
                'paylike_do_transaction_on_order_status_change',
                'catalog/controller/api/order/history/after',
                'extension/payment/paylike_transaction/doTransactionOnOrderStatusChange'
            );
        }
    }

    public function deleteEvents()
    {
        $this->load->model('setting/event');
        /** deleteEventByCode($code); */
        $this->model_setting_event->deleteEventByCode('paylike_do_transaction_on_order_status_change');
    }
    /********************************* EVENTS ADD/DELETE END *************************************/

    /**
     * UPGARDE Paylike transactions table
     * from paylike_admin to paylike_transaction
     */
    public function upgrade()
    {
        if (!is_null($this->config->get('paylike_status'))) {
            $val = $this->config->get('paylike_status');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_status', $val);
            $this->config->set('payment_paylike_status', $val);
        }
        if (!is_null($this->config->get('paylike_payment_method_title'))) {
            $val = $this->config->get('paylike_payment_method_title');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_method_title', $val);
            $this->config->set('payment_paylike_method_title', $val);
        }
        if (!is_null($this->config->get('paylike_title'))) {
            $val = $this->config->get('paylike_title');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_checkout_title', $val);
            $this->config->set('payment_paylike_checkout_title', $val);
        }
        if (!is_null($this->config->get('paylike_description'))) {
            $val = $this->config->get('paylike_description');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_checkout_description', $val);
            $this->config->set('payment_paylike_checkout_description', $val);
        }
        if (!is_null($this->config->get('paylike_mode'))) {
            $val = $this->config->get('paylike_mode');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_api_mode', $val);
            $this->config->set('payment_paylike_api_mode', $val);
        }
        if (!is_null($this->config->get('paylike_test_key'))) {
            $val = $this->config->get('paylike_test_key');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_public_key_test', $val);
            $this->config->set('payment_paylike_public_key_test', $val);
        }
        if (!is_null($this->config->get('paylike_test_app_key'))) {
            $val = $this->config->get('paylike_test_app_key');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_app_key_test', $val);
            $this->config->set('payment_paylike_app_key_test', $val);
        }
        if (!is_null($this->config->get('paylike_live_key'))) {
            $val = $this->config->get('paylike_live_key');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_public_key_live', $val);
            $this->config->set('payment_paylike_public_key_live', $val);
        }
        if (!is_null($this->config->get('paylike_live_app_key'))) {
            $val = $this->config->get('paylike_live_app_key');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_app_key_live', $val);
            $this->config->set('payment_paylike_app_key_live', $val);
        }
        if (!is_null($this->config->get('paylike_capture'))) {
            $val = $this->config->get('paylike_capture');
            if ($val == '1') {
                $val = 'instant';
            } else {
                $val = 'delayed';
            }
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_capture_mode', $val);
            $this->config->set('payment_paylike_capture_mode', $val);
        }
        if (!is_null($this->config->get('paylike_total'))) {
            $val = $this->config->get('paylike_total');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_minimum_total', $val);
            $this->config->set('payment_paylike_minimum_total', $val);
        }
        if (!is_null($this->config->get('paylike_geo_zone_id'))) {
            $val = $this->config->get('paylike_geo_zone_id');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_geo_zone', $val);
            $this->config->set('payment_paylike_geo_zone', $val);
        }
        if (!is_null($this->config->get('paylike_sort_order'))) {
            $val = $this->config->get('paylike_sort_order');
            $this->model_setting_setting->editSettingValue('payment_paylike', 'payment_paylike_sort_order', $val);
            $this->config->set('payment_paylike_sort_order', $val);
        }

        $query = $this->db->query("SELECT p.order_id, p.trans_id, p.amount, p.captured, o.currency_code, o.date_added FROM `" . DB_PREFIX . "paylike_admin` AS p LEFT JOIN `" . DB_PREFIX . "order` AS o ON p.order_id = o.order_id");
        if ($query->num_rows > 0) {
            foreach ($query->rows as $row) {
                if ($row['currency_code']!='') {
                    if (in_array($row['currency_code'], array('BIF', 'BYR', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'))) {
                        $order_amount = $row['amount'];
                    } elseif (in_array($row['currency_code'], array('BHD', 'IQD', 'JOD', 'KWD', 'OMR', 'TND'))) {
                        $order_amount = $row['amount'] / 1000;
                    } else {
                        $order_amount = $row['amount'] / 100;
                    }
                    if ($row['captured']=='YES') {
                        $transaction_type = 'Capture';
                        $transaction_amount = $order_amount;
                        $total_amount = $order_amount;
                    } else {
                        $transaction_type = 'Authorize';
                        $transaction_amount = 0;
                        $total_amount = 0;
                    }
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "paylike_transaction`
                                      SET order_id = '" . $row['order_id'] . "',
                                          transaction_id = '" . $row['trans_id'] . "',
                                          transaction_type = '" . $transaction_type . "',
                                          transaction_currency = '" . strtoupper($row['currency_code']) . "',
                                          order_amount = '" . $order_amount . "',
                                          transaction_amount = '" . $transaction_amount . "',
                                          total_amount = '" . $total_amount . "',
                                          history = '0',
                                          date_added = '" . $row['date_added'] . "'"
                                    );
                }
            }
        }

        $this->db->query("RENAME TABLE `" . DB_PREFIX . "paylike_admin` TO `" . DB_PREFIX . "paylike_transaction_archive`");

        $vqmodfile = str_replace('catalog/', '', DIR_CATALOG) . 'vqmod/xml/vqmod_paylike.xml';
        if (is_file($vqmodfile)) {
            unlink($vqmodfile);
        }
    }


    public function getCcLogos()
    {
        return array(
            array ( 'name' => 'Mastercard', 'logo' => 'mastercard.png' ),
            array ( 'name' => 'Mastercard Maestro', 'logo' => 'maestro.png' ),
            array ( 'name' => 'Visa', 'logo' => 'visa.png' ),
            array ( 'name' => 'Visa Electron', 'logo' => 'visaelectron.png' ),
        );
    }


    public function getTransactionTypes()
    {
        return array( 'Authorize', 'Capture', 'Refund', 'Void' );
    }

    /**
     * GET total amount of Paylike transactions (filtered)
     */
    public function getTotalTransactions($data = array())
    {
        $sql = "SELECT COUNT(order_id) AS total FROM `" . DB_PREFIX . "paylike_transaction` WHERE history = '0'";

        if (!empty($data['filter_order_id'])) {
            $sql .= " AND order_id = '" . (int)$data['filter_order_id'] . "'";
        } else {
            $sql .= " AND order_id > 0";
        }

        if (!empty($data['filter_transaction_id'])) {
            $sql .= " AND transaction_id = '" . $data['transaction_id'] . "'";
        }

        if (!empty($data['filter_transaction_type'])) {
            $sql .= " AND transaction_type = '" . $data['filter_transaction_type'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * GET Paylike transactions
     */
    public function getTransactions($data = array())
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "paylike_transaction` WHERE history = '0'";

        if (!empty($data['filter_order_id'])) {
            $sql .= " AND order_id = '" . (int)$data['filter_order_id'] . "'";
        } else {
            $sql .= " AND order_id > 0";
        }

        if (!empty($data['filter_transaction_id'])) {
            $sql .= " AND transaction_id = '" . $data['transaction_id'] . "'";
        }

        if (!empty($data['filter_transaction_type'])) {
            $sql .= " AND transaction_type = '" . $data['filter_transaction_type'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        $sort_data = array(
            'order_id',
            'transaction_id',
            'transaction_type',
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY order_id";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * GET last Paylike transaction by paylike_transaction_id
     */
    public function getLastTransaction($ref)
    {
        $query = $this->db->query("SELECT *
                                    FROM `" . DB_PREFIX . "paylike_transaction`
                                    WHERE transaction_id = '" . $ref . "'
                                    ORDER BY paylike_transaction_id
                                    DESC
                                    LIMIT 1"
                                );
        return $query->row;
    }

    /**
     * ADD transaction in paylike_transaction table
     */
    public function addTransaction($data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "paylike_transaction`
                            SET history = 1
                            WHERE history = '0'
                            AND transaction_id = '" . $data['transaction_id'] . "'"
                        );
        $this->db->query("INSERT INTO `" . DB_PREFIX . "paylike_transaction`
                            SET order_id = '" . $data['order_id'] . "',
                            transaction_id = '" . $data['transaction_id'] . "',
                            transaction_type = '" . $data['transaction_type'] . "',
                            transaction_currency = '" . $data['transaction_currency'] . "',
                            order_amount = '" . $data['order_amount'] . "',
                            transaction_amount = '" . $data['transaction_amount'] . "',
                            total_amount = '" . $data['total_amount'] . "',
                            history = '" . $data['history'] . "',
                            date_added = '" . $data['date_added'] . "'"
                        );
    }

    /**
     * UPDATE order
     */
    public function updateOrder($data, $new_order_status_id)
    {
        if ($new_order_status_id > 0) {
            /** Update order. */
            $this->db->query("UPDATE `" . DB_PREFIX . "order`
                                SET order_status_id = '" . $new_order_status_id . "',
                                    date_modified = NOW()
                                WHERE order_id = '" . $data['order_id'] . "'"
                            );

            $comment = 'Paylike transaction: ref:' . $data['transaction_id'];
            $comment .= "\r\n" . 'Type: ' . $data['transaction_type'] . ', Amount: ' . $data['transaction_amount'] . ' ' . strtoupper($data['transaction_currency']);

            /** Update order history. */
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history
                                SET order_id = '" . $data['order_id'] . "',
                                order_status_id = '" . $new_order_status_id . "',
                                notify = '0',
                                comment = '" . $this->db->escape($comment) . "',
                                date_added = NOW()"
                            );
        }
    }
}
