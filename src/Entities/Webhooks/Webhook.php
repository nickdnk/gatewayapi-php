<?php


namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Exceptions\WebhookException;
use Psr\Http\Message\RequestInterface;

/**
 * Class Webhook
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
abstract class Webhook
{

    private $messageId, $phoneNumber;

    protected function __construct(int $messageId, int $phoneNumber)
    {

        $this->messageId = $messageId;
        $this->phoneNumber = $phoneNumber;
    }

    public function getMessageId(): int
    {

        return $this->messageId;
    }

    public function getPhoneNumber(): int
    {

        return $this->phoneNumber;
    }

    /**
     * @param array $data
     *
     * @return DeliveryStatusWebhook|IncomingMessageWebhook
     * @throws WebhookException
     */
    private static function constructWebhook(array $data): Webhook
    {

        if (array_key_exists('id', $data)
            && array_key_exists('msisdn', $data)) {

            if (array_key_exists('receiver', $data)
                && array_key_exists('message', $data)
                && array_key_exists('senttime', $data)
                && array_key_exists('webhook_label', $data)) {

                return IncomingMessageWebhook::constructFromArray($data);

            }

            if (array_key_exists('time', $data)
                && array_key_exists('status', $data)) {

                return DeliveryStatusWebhook::constructFromArray($data);

            }

        }

        throw new WebhookException(
            'Webhook missing required keys. Got: ' . implode(',', array_keys($data))
        );

    }

    /**
     * @param string $jwt
     * @param string $secret
     *
     * @return array
     * @throws WebhookException
     */
    private static function parseAndValidateJWT(string $jwt, string $secret): array
    {

        $split = explode('.', $jwt);

        if (count($split) === 3) {

            $header = json_decode(base64_decode($split[0]));
            $payload = json_decode(base64_decode($split[1]), true);

            if ($header && $payload) {

                if (property_exists($header, 'alg')) {

                    switch ($header->alg) {

                        case 'HS256':
                            $algo = 'sha256';
                            break;
                        case 'HS384':
                            $algo = 'sha384';
                            break;
                        case 'HS512':
                            $algo = 'sha512';
                            break;
                        default:
                            $algo = null;

                    }

                    if ($algo
                        && rtrim(
                               strtr(
                                   base64_encode(hash_hmac($algo, $split[0] . '.' . $split[1], $secret, true)),
                                   "+/",
                                   "-_"
                               ),
                               "="
                           ) === $split[2]) {

                        return $payload;

                    } else {

                        throw new WebhookException('Webhook failed signature validation.');

                    }

                }

            }

        }

        throw new WebhookException('Failed to parse webhook header as JWT.');

    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     * @throws WebhookException
     */
    private static function getJWTFromRequest(RequestInterface $request): string
    {

        $token = $request->getHeaderLine('X-Gwapi-Signature');

        if (!$token) {
            throw new WebhookException('Missing webhook JWT header.');
        }

        return $token;

    }

    /**
     * Constructs a webhook from a PSR-7 request object. This automatically reads the JWT header, parses and validates
     * it and returns one of the two possible webhook types. Note that the body of the request is entirely ignored,
     * as the JWT header contains the full payload of the webhook.
     *
     * @param RequestInterface $request
     * @param string           $secret
     *
     * @return DeliveryStatusWebhook|IncomingMessageWebhook
     * @throws WebhookException
     */
    final public static function constructFromRequest(RequestInterface $request, string $secret): Webhook
    {

        return self::constructFromJWT(
            self::getJWTFromRequest($request),
            $secret
        );
    }

    /**
     * Parses a webhook using a JWT directly. This is equivalent to using `constructFromRequest()` if you have
     * correctly extracted the JWT from the 'X-Gwapi-Signature' HTTP header of the request.
     *
     * @param string $jwt
     * @param string $secret
     *
     * @return DeliveryStatusWebhook|IncomingMessageWebhook
     * @throws WebhookException
     */
    final public static function constructFromJWT(string $jwt, string $secret): Webhook
    {

        return self::constructWebhook(self::parseAndValidateJWT($jwt, $secret));

    }


}
