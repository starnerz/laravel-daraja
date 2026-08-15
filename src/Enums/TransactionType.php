<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Enums;

/**
 * Transaction types accepted by M-Pesa Express (STK Push).
 */
enum TransactionType: string
{
    /** Payment to a Pay Bill short code, with an account reference. */
    case PayBill = 'CustomerPayBillOnline';

    /** Payment to a Buy Goods till number. */
    case BuyGoods = 'CustomerBuyGoodsOnline';
}
