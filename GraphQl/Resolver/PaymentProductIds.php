<?php
declare(strict_types=1);

namespace Worldline\RedirectPayment\GraphQl\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Worldline\PaymentCore\Model\Ui\PaymentProductsProvider;

class PaymentProductIds implements ResolverInterface
{
    /**
     * @var Json;
     */
    private $jsonSerializer;

    public function __construct(Json $jsonSerializer)
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        $payProductIds = array_keys(PaymentProductsProvider::PAYMENT_PRODUCTS);

        return [
            'product_ids' => $this->jsonSerializer->serialize($payProductIds)
        ];
    }
}
