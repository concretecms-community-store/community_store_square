<?php
namespace Concrete\Package\CommunityStoreSquare;

use Concrete\Core\Package\Package;
use Whoops\Exception\ErrorException;
use \Concrete\Core\Support\Facade\Route;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_square';
    protected $appVersionRequired = '8.0';
    protected $pkgVersion = '1.0';
    protected $packageDependencies = ['community_store'=>'2.5'];

    protected $pkgAutoloaderRegistries = [
        'src/CommunityStore' => '\Concrete\Package\CommunityStoreSquare\Src\CommunityStore',
    ];

    public function on_start()
    {
        Route::register('/checkout/squarecaptureorder/{token}/{idempotencyKey}','\Concrete\Package\CommunityStoreSquare\Src\CommunityStore\Payment\Methods\CommunityStoreSquare\CommunityStoreSquarePaymentMethod::captureOrder');
        Route::register('/checkout/squarecaptureorder/{token}/{idempotencyKey}/{verificationToken}','\Concrete\Package\CommunityStoreSquare\Src\CommunityStore\Payment\Methods\CommunityStoreSquare\CommunityStoreSquarePaymentMethod::captureOrder');
        require __DIR__ . '/vendor/autoload.php';
    }

    public function getPackageDescription()
    {
        return t("Square Payment Method for Community Store");
    }

    public function getPackageName()
    {
        return t("Square Payment Method");
    }

    public function install()
    {
        $installed = $this->app->make('Concrete\Core\Package\PackageService')->getInstalledHandles();

        if (!(is_array($installed) && in_array('community_store', $installed))) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_square', 'Square Payments', $pkg);
        }
    }

    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_square');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

}


