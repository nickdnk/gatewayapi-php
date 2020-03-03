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

    /**
     * Webhook constructor.
     *
     * @param $messageId
     * @param $phoneNumber
     */
    public function __construct(int $messageId, int $phoneNumber)
    {

        $this->messageId = $messageId;
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {

        return $this->messageId;
    }

    /**
     * @return int
     */
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
    private static function construct(array $data): Webhook
    {

        if (array_key_exists('id', $data)
            && array_key_exists('msisdn', $data)) {

            if (array_key_exists('receiver', $data)
                && array_key_exists('message', $data)
                && array_key_exists('senttime', $data)
                && array_key_exists('webhook_label', $data)) {

                return new IncomingMessageWebhook(
                    $data['id'],
                    $data['msisdn'],
                    $data['receiver'],
                    $data['message'],
                    $data['senttime'],
                    $data['webhook_label'],
                    array_key_exists('sender', $data) ? $data['sender'] : null,
                    array_key_exists('mcc', $data) ? $data['mcc'] : null,
                    array_key_exists('mnc', $data) ? $data['mnc'] : null,
                    array_key_exists('validity_period', $data) ? $data['validity_period'] : null,
                    array_key_exists('encoding', $data) ? $data['encoding'] : null,
                    array_key_exists('udh', $data) ? $data['udh'] : null,
                    array_key_exists('payload', $data) ? $data['payload'] : null,
                    array_key_exists('country_code', $data) ? $data['country_code'] : null,
                    array_key_exists('country_prefix', $data) ? $data['country_prefix'] : null
                );

            }

            if (array_key_exists('time', $data)
                && array_key_exists('status', $data)) {

                return new DeliveryStatusWebhook(
                    $data['id'],
                    $data['msisdn'],
                    $data['time'],
                    $data['status'],
                    array_key_exists('userref', $data) ? $data['userref'] : null,
                    array_key_exists('charge_status', $data) ? $data['charge_status'] : null,
                    array_key_exists('country_code', $data) ? $data['country_code'] : null,
                    array_key_exists('country_prefix', $data) ? $data['country_prefix'] : null,
                    array_key_exists('error', $data) ? $data['error'] : null,
                    array_key_exists('code', $data) ? $data['code'] : null
                );

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
                        case 'HS384';
                            $algo = 'sha384';
                            break;
                        case 'HS512';
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
     * @param string $jwt
     * @param string $secret
     *
     * @return DeliveryStatusWebhook|IncomingMessageWebhook
     * @throws WebhookException
     */
    final public static function constructFromJWT(string $jwt, string $secret): Webhook
    {

        return self::construct(self::parseAndValidateJWT($jwt, $secret));

    }


}
