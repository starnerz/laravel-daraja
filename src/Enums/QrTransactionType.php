<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Enums;

/**
 * Transaction types accepted by the Dynamic QR API.
 */
enum QrTransactionType: string
{
    /** Pay Merchant (Buy Goods). */
    case BuyGoods = 'BG';

    /** Withdraw cash at an agent till. */
    case WithdrawAtAgent = 'WA';

    /** Pay bill or business number. */
    case PayBill = 'PB';

    /** Send money to a mobile number. */
    case SendMoney = 'SM';

    /** Send to business — business number CPI in MSISDN format. */
    case SendToBusiness = 'SB';
}
