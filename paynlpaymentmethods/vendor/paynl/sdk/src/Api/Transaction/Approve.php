<?php

namespace Paynl\Api\Transaction;

use Paynl\Error;

/**
 * Description of Approve
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Approve extends Transaction
{
    protected $apiTokenRequired = true;

    /**
     * @var string
     */
    private $transactionId;
    protected $version = 12;

    /**
     * Set the transactionId
     *
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @inheritdoc
     * @throws Error\Required TransactionId is required
     */
    protected function getData()
    {
        if (empty($this->transactionId)) {
            throw new Error\Required('TransactionId is required');
        }

        $this->data['orderId'] = $this->transactionId;

        return parent::getData();
    }

    /**
     * @inheritdoc
     */
    public function doRequest($endpoint = null, $version = null)
    {
        return parent::doRequest('transaction/approve');
    }
}
