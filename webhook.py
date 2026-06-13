import hmac
import secrets

class Request:
    """Generic request model."""

    body: bytes
    headers: dict[str, str]

def verify_request(request: Request, signature_key: bytes) -> bool:
    signature = request.headers.get("Signature")
    if not signature:
        return False

    expected_signature = hmac.digest(
        signature_key,
        request.body,
        "SHA256",
    )
    return secrets.compare_digest(
        signature.removeprefix("v1="),
        expected_signature.hex(),
    )