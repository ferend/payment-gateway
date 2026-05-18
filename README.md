# Payment Gateway

A simple, self-hosted payment page using PayTR iFrame API. Accepts credit/debit card payments via a clean dark-themed UI with password protection.

## Features

- Password-protected access
- PayTR iFrame API integration (PCI-DSS compliant)
- Success/fail result pages
- Async callback handler for payment verification
- All credentials stored as environment variables (never in code)
- Dockerized for easy deployment via Dokku

## Requirements

- A server with [Dokku](https://dokku.com) installed
- A [PayTR](https://www.paytr.com) merchant account (Merchant ID, Key, Salt)
- A domain with DNS pointing to your server

## Project Structure

```
payment-gateway/
├── Dockerfile
├── README.md
├── .htaccess
├── index.php          # Password gate + payment form + iframe
├── callback.php       # PayTR async notification handler
├── success.php        # Payment success page
└── fail.php           # Payment fail page
```

## Environment Variables

| Variable | Description | Example |
|---|---|---|
| `PAYTR_MERCHANT_ID` | Your PayTR merchant ID | `123456` |
| `PAYTR_MERCHANT_KEY` | Your PayTR merchant key | `abcdef...` |
| `PAYTR_MERCHANT_SALT` | Your PayTR merchant salt | `xyz123...` |
| `PAYTR_TEST_MODE` | Test mode (1) or live (0) | `1` |
| `ACCESS_PASSWORD` | Password to access the payment page | `mysecretpass` |


## License

Private. Not for redistribution.
