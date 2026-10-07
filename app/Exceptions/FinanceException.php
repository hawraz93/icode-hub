<?php

namespace App\Exceptions;

use DomainException;

/**
 * A business-rule refusal (overpayment, wrong currency, paying a cancelled invoice...).
 * The message is user-facing Kurdish text, safe to show in the UI and Telegram.
 */
class FinanceException extends DomainException
{
}
