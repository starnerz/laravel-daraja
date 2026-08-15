<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Enums;

/**
 * CommandID values used across the Daraja APIs.
 */
enum CommandId: string
{
    // C2B
    case CustomerPayBillOnline = 'CustomerPayBillOnline';
    case CustomerBuyGoodsOnline = 'CustomerBuyGoodsOnline';

    // B2C
    case SalaryPayment = 'SalaryPayment';
    case BusinessPayment = 'BusinessPayment';
    case PromotionPayment = 'PromotionPayment';

    // B2B
    case BusinessPayBill = 'BusinessPayBill';
    case BusinessBuyGoods = 'BusinessBuyGoods';
    case BusinessToBusinessTransfer = 'BusinessToBusinessTransfer';
    case MerchantToMerchantTransfer = 'MerchantToMerchantTransfer';
    case DisburseFundsToBusiness = 'DisburseFundsToBusiness';

    /** Loads funds into a B2C short code for disbursement. */
    case BusinessPayToBulk = 'BusinessPayToBulk';

    /** Pays into a customer's Pochi la Biashara business wallet. */
    case BusinessPayToPochi = 'BusinessPayToPochi';

    // Account services
    case AccountBalance = 'AccountBalance';
    case TransactionStatusQuery = 'TransactionStatusQuery';
    case TransactionReversal = 'TransactionReversal';
}
