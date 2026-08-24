<?php


namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Exceptions\WebhookException;
use Psr\Http\Message\RequestInterface;

/**
 * Class Webhook
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
readonly abstract class Webhook
{

    protected function __construct(private int $messageId, private int $phoneNumber)
    {
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
     * @throws WebhookException
     */
    private static function constructWebhook(array $data): DeliveryStatusWebhook|IncomingMessageWebhook
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
     * @return array<string, mixed> the decoded JWT payload
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

                    $algo = match ($header->alg) {
                        'HS256' => 'sha256',
                        'HS384' => 'sha384',
                        'HS512' => 'sha512',
                        default => null,
                    };

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
     * @throws WebhookException
     */
    final public static function constructFromRequest(RequestInterface $request, string $secret): DeliveryStatusWebhook|IncomingMessageWebhook
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
     * @throws WebhookException
     */
    final public static function constructFromJWT(string $jwt, string $secret): DeliveryStatusWebhook|IncomingMessageWebhook
    {

        return self::constructWebhook(self::parseAndValidateJWT($jwt, $secret));

    }


}
