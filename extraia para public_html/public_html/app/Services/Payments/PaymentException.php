<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Erro de comunicação/validação com um gateway. A mensagem é segura para
 * ser exibida ao usuário; detalhes técnicos ficam apenas no log.
 */
class PaymentException extends RuntimeException
{
}
