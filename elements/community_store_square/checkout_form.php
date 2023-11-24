<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>


<form id="payment-form">

    <?php if ($enableGooglePay) { ?>
    <div id="google-pay-button" alt="google-pay" type="button"></div>
    <?php } ?>

    <?php if ($enableApplePay) { ?>
    <div id="apple-pay-button" alt="apple-pay" type="button"></div>
    <?php } ?>

    <?php if ($enableGooglePay || $enableApplePay) { ?>
    <h4 id="google-pay-or" class="mt-4 d-none "><?= t('Or pay by credit/debit card'); ?></h4>
    <?php } ?>

    <div id="card-container"></div>
    <button id="card-button"
            class="d-none btn btn-success btn-lg p-2 w-100 mb-2 rounded-pill" type="button"><?= t('Pay'); ?></button>
    <div id="payment-flow-message" class="text-center"></div>

</form>
<div id="payment-status-container" class="text-danger fw-bold">
</div>


<script>

    async function CardPay(fieldEl, buttonEl) {
        // Create a card payment object and attach to page
        const card = await window.payments.card({
            style: {
                '.input-container.is-focus': {
                    borderColor: '#006AFF'
                },
                '.message-text.is-error': {
                    color: '#BF0020'
                }
            }
        });
        await card.attach(fieldEl);

        async function eventHandler(event) {
            // Clear any existing messages
            window.paymentFlowMessageEl.innerText = '';

            try {
                document.getElementById('card-button').classList.add('disabled');

                const result = await card.tokenize();

                document.getElementById('card-button').classList.remove('disabled');
                if (result.status === 'OK') {
                    document.getElementById('card-button').classList.add('disabled');
                    document.getElementById('card-button').innerHTML = 'Processing, please wait';
                    // Use global method from sq-payment-flow.js

                    var amount =  '<?= $squareTotal; ?>';

                    const verificationDetails = {
                        amount: amount,
                        /* collected from the buyer */
                        billingContact: {

                        },
                        currencyCode: '<?= $squareCurrencyCode; ?>',
                        intent: 'CHARGE',
                    };

                    const verificationResults = await payments.verifyBuyer(
                        result.token,
                        verificationDetails
                    );

                    if (verificationResults && verificationResults.token) {
                        window.createPayment(result.token, verificationResults.token);
                    } else {
                        window.createPayment(result.token);
                    }

                }
            } catch (e) {

                document.getElementById('card-button').classList.remove('disabled');
                if (e.message) {
                    console.log(e)
                    window.showError(`Error: ${e.message}`);
                } else {
                    window.showError('Something went wrong');
                }
            }
        }

        buttonEl.classList.remove('d-none');
        buttonEl.addEventListener('click', eventHandler);
    }

    async function ApplePay(buttonEl) {
        const paymentRequest = window.payments.paymentRequest(
            // Use global method from sq-payment-flow.js
            window.getPaymentRequest()
        );

        let applePay;
        try {
            applePay = await window.payments.applePay(paymentRequest);

        } catch (e) {
            console.error(e)
            return;
        }

        async function eventHandler(event) {
            // Clear any existing messages
            window.paymentFlowMessageEl.innerText = '';

            try {
                const result = await applePay.tokenize();
                if (result.status === 'OK') {
                    // Use global method from sq-payment-flow.js
                    document.getElementById('card-button').classList.add('disabled');
                    document.getElementById('card-button').innerHTML = 'Processing, please wait';
                    window.createPayment(result.token);
                }
            } catch (e) {
                if (e.message) {
                    window.showError(`Error: ${e.message}`);
                } else {
                    window.showError('Something went wrong');
                }
            }
        }

        buttonEl.addEventListener('click', eventHandler);
    }

    async function GooglePay(buttonEl) {
        const paymentRequest = window.payments.paymentRequest(
            // Use global method from sq-payment-flow.js
            window.getPaymentRequest()
        );
        const googlePay = await payments.googlePay(paymentRequest);
        await googlePay.attach(buttonEl);

        document.getElementById('google-pay-or').classList.remove('d-none')

        async function eventHandler(event) {
            // Clear any existing messages
            window.paymentFlowMessageEl.innerText = '';

            try {
                const result = await googlePay.tokenize();
                if (result.status === 'OK') {
                    // Use global method from sq-payment-flow.js
                    window.createPayment(result.token);
                }
            } catch (e) {
                if (e.message) {
                    window.showError(`Error: ${e.message}`);
                } else {
                    window.showError('Something went wrong');
                }
            }
        }

        buttonEl.addEventListener('click', eventHandler);
    }

    async function SquarePaymentFlow() {

        window.paymentFlowMessageEl = document.getElementById('payment-flow-message');

        window.showSuccess = function (message) {
            window.paymentFlowMessageEl.classList.add('success');
            window.paymentFlowMessageEl.classList.remove('error');
            window.paymentFlowMessageEl.innerText = message;
        }

        window.showError = function (message) {

            window.paymentFlowMessageEl.classList.add('error');
            window.paymentFlowMessageEl.classList.remove('success');
            window.paymentFlowMessageEl.innerText = message;
        }
        // Create card payment object and attach to page

        var cc = document.getElementById('card-container');
        cc.id = 'card-container-changed';
        CardPay(cc, document.getElementById('card-button'));

        // Create Apple pay instance
        <?php if ($enableApplePay) { ?>
        if (window.ApplePaySession) {
            ApplePay(document.getElementById('apple-pay-button'));
        } else {
            document.getElementById('apple-pay-button').remove();
        }
        <?php } ?>

        <?php if ($enableGooglePay) { ?>
        var gpb = document.getElementById('google-pay-button');
        gpb.id = 'gbp-init';
        // Create Google pay instance
        GooglePay(gpb);
        <?php } ?>

    }

    window.payments = Square.payments('<?= $squareApplicationID; ?>', '<?= $squareLocationID; ?>');

    window.createPayment = async function (token, verification) {

        return fetch("<?= \URL::to('/checkout/squarecaptureorder'); ?>/" +  token + '/' + '<?= $idempotencyKey;?>' + (verification ? ('/' + verification) : '') , {
            method: "post"
        })
            .then((response) => response.json())
            .then((response) => {
                // javascript redirect
                if (response.error) {
                    window.paymentFlowMessageEl.classList.add('error');
                    window.paymentFlowMessageEl.classList.remove('success');
                    window.paymentFlowMessageEl.innerText = response.error;

                } else {
                    window.location.href = response.redirect;
                }
            });
    }

</script>


<script>

    // Hardcoded for testing purpose, only used for Apple Pay and Google Pay
    window.getPaymentRequest = function () {
        return {
            countryCode: '<?= $squareCountryCode; ?>',
            currencyCode: '<?= $squareCurrencyCode; ?>',
            lineItems: [
                {amount: '<?= $squareTotal; ?>', label: 'Payment', pending: false}
            ],
            requestBillingContact: false,
            total: {amount: '<?= $squareTotal; ?>', label: 'Total', pending: false},
        };
    };

    SquarePaymentFlow();
    var button = document.querySelector("[data-payment-method-id='<?= $pmID; ?>'] .store-btn-complete-order");
    button.remove();
</script>


<style>
    .gpay-card-info-container {
        width: 100% !important;
        margin-bottom: 10px;
    }

    #apple-pay-button {
        height: 38px;
        width: 100%;
        display: inline-block;
        -webkit-appearance: -apple-pay-button;
        -apple-pay-button-type: plain;
        -apple-pay-button-style: black;
    }
</style>


