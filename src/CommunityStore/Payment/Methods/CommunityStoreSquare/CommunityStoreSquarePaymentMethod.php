<?php
namespace Concrete\Package\CommunityStoreSquare\Src\CommunityStore\Payment\Methods\CommunityStoreSquare;

use Concrete\Core\Support\Facade\Url;
use Square\SquareClient;
use Square\Models\Money;
use Square\Exceptions\ApiException;
use Square\Models\CreatePaymentRequest;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\Support\Facade\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as StorePrice;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;

class CommunityStoreSquarePaymentMethod extends StorePaymentMethod
{

    public function dashboardForm()
    {
        $this->set('squareMode', Config::get('community_store_square.mode'));
        $this->set('squareCurrency', Config::get('community_store_square.currency'));
        $this->set('squareCountry', Config::get('community_store_square.country'));
        $this->set('squareSandboxApplicationId', Config::get('community_store_square.sandboxApplicationId'));
        $this->set('squareSandboxAccessToken', Config::get('community_store_square.sandboxAccessToken'));
        $this->set('squareSandboxLocation', Config::get('community_store_square.sandboxLocation'));
        $this->set('squareLiveApplicationId', Config::get('community_store_square.liveApplicationId'));
        $this->set('squareLiveAccessToken', Config::get('community_store_square.liveAccessToken'));
        $this->set('squareLiveLocation', Config::get('community_store_square.liveLocation'));
        $this->set('squareEnableGooglePay', Config::get('community_store_square.enableGooglePay'));
        $this->set('squareEnableApplePay', Config::get('community_store_square.enableApplePay'));
        $this->set('form', app()->make("helper/form"));

        $gateways = array(
            'square_form'=>'Form'
        );

        $this->set('squareGateways', $gateways);

        $currencies = array(
          'UAD'=>t('Australian Dollars'),
          'CAD'=>t('Canadian Dollar'),
          'JPY'=>t('Japanese Yen'),
          'USD'=>t('US Dollars'),
          'GBP'=>t('British Pound'),
          'EUR'=>t('Euro')
        );

        $countryCodes = array(
            'AU'=>t('Australia'),
            'CA'=>t('Canada'),
            'FR'=>t('France'),
            'IE'=>t('Ireland'),
            'JP'=>t('Japan (Apple Pay and Google Pay not supported)'),
            'ES'=>t('Spain'),
            'US'=>t('United States'),
            'GB'=>t('United Kingdom')
        );

        $this->set('squareCurrencies', $currencies);
        $this->set('squareCountryCodes', $countryCodes);
    }

    public function save(array $data = [])
    {
        Config::save('community_store_square.mode', $data['squareMode']);
        Config::save('community_store_square.currency', $data['squareCurrency']);
        Config::save('community_store_square.country', $data['squareCountry']);
        Config::save('community_store_square.sandboxApplicationId', $data['squareSandboxApplicationId']);
        Config::save('community_store_square.sandboxAccessToken', $data['squareSandboxAccessToken']);
        Config::save('community_store_square.sandboxLocation', $data['squareSandboxLocation']);
        Config::save('community_store_square.liveApplicationId', $data['squareLiveApplicationId']);
        Config::save('community_store_square.liveAccessToken', $data['squareLiveAccessToken']);
        Config::save('community_store_square.liveLocation', $data['squareLiveLocation']);
        Config::save('community_store_square.enableGooglePay', isset($data['squareEnableGooglePay']) ? '1' : '0');
        Config::save('community_store_square.enableApplePay', isset($data['squareEnableApplePay']) ? '1' : '0');
    }

    public function validate($args, $e)
    {
        return $e;
    }

    public function checkoutForm()
    {
        $customer = new StoreCustomer();
        $currency = Config::get('community_store_square.currency');
        $mode =  Config::get('community_store_square.mode');

        $this->set('squareCurrencyCode', Config::get('community_store_square.currency'));
        $this->set('squareCountryCode', Config::get('community_store_square.country'));

        if ($mode == 'live') {
            $this->set('squareApplicationID', Config::get('community_store_square.liveApplicationId'));
            $this->set('squareLocationID', Config::get('community_store_square.liveLocation'));

        } else {
            $this->set('squareApplicationID', Config::get('community_store_square.sandboxApplicationId'));
            $this->set('squareLocationID', Config::get('community_store_square.sandboxLocation'));
        }

        $this->set('email', $customer->getEmail());
        $this->set('form', app()->make("helper/form"));

        //$currencyMultiplier = StorePrice::getCurrencyMultiplier($currency);

        $this->set('squareTotal', number_format(StoreCalculator::getGrandTotal(), 2, '.', ''));


        $pmID = StorePaymentMethod::getByHandle('community_store_square')->getID();
        $this->set('pmID', $pmID);
        $idempotencyKey = app()->make('helper/validation/identifier')->getString(18);
        $this->set('idempotencyKey', $idempotencyKey);

        $this->set('enableGooglePay',Config::get('community_store_square.enableGooglePay'));
        $this->set('enableApplePay', Config::get('community_store_square.enableApplePay'));
    }

    public function captureOrder($token, $idempotencyKey, $verificationToken = '')
    {
        $currency = Config::get('community_store_square.currency');
        $mode =  Config::get('community_store_square.mode');
        if ($mode == 'live') {
            $access_token = Config::get('community_store_square.liveAccessToken');
            $locationID = Config::get('community_store_square.liveLocation');
        } else {
            $access_token = Config::get('community_store_square.sandboxAccessToken');
            $locationID = Config::get('community_store_square.sandboxLocation');
        }

        $currencyMultiplier = StorePrice::getCurrencyMultiplier($currency);
        $total = number_format(StoreCalculator::getGrandTotal() * $currencyMultiplier, 0, '', '');

        $square_client = new SquareClient([
            'accessToken' => $access_token,
            'environment' => ($mode == 'live' ? 'production' : 'sandbox'),
        ]);

        $payments_api = $square_client->getPaymentsApi();

        $money = new Money();
        $money->setAmount($total);
        $money->setCurrency($currency);

        $error = '';

        try {
            // Every payment you process with the SDK must have a unique idempotency key.
            // If you're unsure whether a particular payment succeeded, you can reattempt
            // it with the same idempotency key without worrying about double charging
            // the buyer.
            $create_payment_request = new CreatePaymentRequest($token, $idempotencyKey);
            $create_payment_request->setLocationId($locationID);
            $create_payment_request->setAmountMoney($money);

            if ($verificationToken) {
                $create_payment_request->setVerificationToken($verificationToken);
            }

            $response = $payments_api->createPayment($create_payment_request);

            if ($response->isSuccess()) {
                $id = $response->getResult()->getPayment()->getId();

                $pm = PaymentMethod::getByHandle('community_store_square');
                $order = \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order::add($pm, $id);

                // unset the shipping type, as next order might be unshippable
                Session::set('community_store.smID', '');
                Session::set('notes', '');

                $request = app()->make(\Concrete\Core\Http\Request::class);
                $referrer = $request->server->get('HTTP_REFERER');
                $c = \Concrete\Core\Page\Page::getByPath(parse_url($referrer, PHP_URL_PATH));
                $al = \Concrete\Core\Multilingual\Page\Section\Section::getBySectionOfSite($c);
                $langpath = '';
                if ($al !== null) {
                    $langpath = $al->getCollectionHandle();
                }
                $returnUrl = \Concrete\Core\Support\Facade\Url::to($langpath . '/checkout/complete') . '';

                return new JsonResponse(['redirect'=>$returnUrl]);

            } else {
                $message = $response->getErrors()[0]->getDetail();

                if (strpos($message, 'GENERIC_DECLINE') !== false ||  strpos($message, 'INSUFFICIENT_FUNDS') !== false ) {
                    $message = 'Your card payment was declined';
                }

               $error = str_replace('_', ' ', $message);
            }
        } catch (ApiException $e) {
            $error = $e . '';

        }

        return new JsonResponse(['error'=>$error]);

    }

    public function getPaymentMethodName()
    {
        return 'Square';
    }

    public function getPaymentMethodDisplayName()
    {
        return $this->getPaymentMethodName();
    }

    public function getName()
    {
        return $this->getPaymentMethodName();
    }

    public function headerScripts($view) {
        $mode =  Config::get('community_store_square.mode');

        if ($mode == 'live') {
            $web_payment_sdk_url = "https://web.squarecdn.com/v1/square.js";
        } else {
            $web_payment_sdk_url = "https://sandbox.web.squarecdn.com/v1/square.js";
        }

        $view->addHeaderItem('<script src="'. $web_payment_sdk_url . '" id="square-script"></script>');
    }
}

return __NAMESPACE__;
